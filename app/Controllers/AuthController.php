<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Jwt;
use App\Core\Permissions;
use App\Core\Request;
use App\Core\Response;

final class AuthController extends Controller
{
    public function login(Request $request): Response
    {
        $data = $request->json();

        $this->validate($data, [
            'username' => 'required|string|max:50',
            'password' => 'required|string|max:255',
        ]);

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM pengguna WHERE username = :u LIMIT 1');
        $stmt->execute(['u' => $data['username']]);
        $user = $stmt->fetch();

        if ($user === false || !password_verify((string) $data['password'], $user['password_hash'])) {
            throw HttpException::unauthorized('Username atau password salah');
        }

        if ($user['status'] === 'terkunci') {
            throw HttpException::forbidden('Akun terkunci. Hubungi administrator.');
        }

        if ($user['status'] !== 'aktif') {
            throw HttpException::forbidden('Akun tidak aktif');
        }

        $pdo->prepare('UPDATE pengguna SET last_login_at = NOW() WHERE id_pengguna = :id')
            ->execute(['id' => $user['id_pengguna']]);

        $detail = $this->penggunaDetail((int) $user['id_pengguna']);
        $token = Jwt::issue([
            'sub' => (int) $user['id_pengguna'],
            'type' => 'pengguna',
            'username' => $user['username'],
        ]);

        return Response::ok([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'user' => $detail,
        ], 'Login berhasil');
    }

    public function loginPasien(Request $request): Response
    {
        $data = $request->json();

        $this->validate($data, [
            'username' => 'required|string|max:50',
            'password' => 'required|string|max:255',
        ]);

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT a.*, p.nama_lengkap
             FROM akun_pasien a
             INNER JOIN pasien p ON p.nik = a.nik
             WHERE a.username = :u1 OR a.email_login = :u2
             LIMIT 1'
        );
        $stmt->execute(['u1' => $data['username'], 'u2' => $data['username']]);
        $akun = $stmt->fetch();

        if ($akun === false || !password_verify((string) $data['password'], $akun['password_hash'])) {
            throw HttpException::unauthorized('Username atau password salah');
        }

        if ($akun['status'] !== 'aktif') {
            throw HttpException::forbidden('Akun tidak aktif');
        }

        $pdo->prepare('UPDATE akun_pasien SET last_login_at = NOW() WHERE nik = :nik')
            ->execute(['nik' => $akun['nik']]);

        $token = Jwt::issue([
            'sub' => $akun['nik'],
            'type' => 'pasien',
            'username' => $akun['username'],
        ]);

        return Response::ok([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'user' => [
                'type' => 'pasien',
                'nik' => $akun['nik'],
                'nama_lengkap' => $akun['nama_lengkap'],
                'username' => $akun['username'],
                'email' => $akun['email_login'],
                'roles' => ['pasien'],
                'permissions' => Permissions::forRole('pasien'),
            ],
        ], 'Login berhasil');
    }

    public function registerPasien(Request $request): Response
    {
        $data = $request->json();

        $this->validate($data, [
            'nik' => 'required|digits:16|unique:pasien,nik',
            'nama_lengkap' => 'required|string|max:120',
            'jenis_kelamin' => 'required|in:L,P',
            'alamat' => 'required|string|max:2000',
            'tempat_lahir' => 'nullable|string|max:80',
            'tanggal_lahir' => 'nullable|date',
            'golongan_darah' => 'nullable|in:A,B,AB,O,Tidak diketahui',
            'no_hp' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:120',
            'id_asuransi' => 'nullable|integer|exists:asuransi,id_asuransi',
            'nomor_peserta_asuransi' => 'nullable|string|max:60',
            'kelurahan' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'kota_kabupaten' => 'nullable|string|max:100',
            'provinsi' => 'nullable|string|max:100',
            'kode_pos' => 'nullable|string|max:10',
            'nama_kontak_darurat' => 'nullable|string|max:120',
            'no_kontak_darurat' => 'nullable|string|max:20',
            'hubungan_kontak_darurat' => 'nullable|string|max:50',
            'username' => 'required|string|min:4|max:50|alpha_dash|unique:akun_pasien,username',
            'password' => 'required|string|min:6|max:255',
            'konfirmasi_password' => 'required|string|max:255',
        ]);

        if ($data['password'] !== $data['konfirmasi_password']) {
            throw HttpException::unprocessable('Konfirmasi password tidak cocok', [
                'konfirmasi_password' => ['Konfirmasi password tidak cocok'],
            ]);
        }

        $result = Database::transaction(function () use ($data) {
            $pasien = array_intersect_key($data, array_flip([
                'nik', 'id_asuransi', 'nomor_peserta_asuransi', 'nama_lengkap',
                'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'golongan_darah',
                'no_hp', 'email', 'alamat', 'kelurahan', 'kecamatan',
                'kota_kabupaten', 'provinsi', 'kode_pos', 'nama_kontak_darurat',
                'no_kontak_darurat', 'hubungan_kontak_darurat',
            ]));

            $pdo = Database::connection();

            $columns = implode(', ', array_map(static fn (string $c): string => "`{$c}`", array_keys($pasien)));
            $placeholders = implode(', ', array_map(static fn (string $c): string => ":{$c}", array_keys($pasien)));
            $pdo->prepare("INSERT INTO pasien ({$columns}) VALUES ({$placeholders})")->execute($pasien);

            $pdo->prepare(
                'INSERT INTO akun_pasien (nik, username, email_login, password_hash, status)
                 VALUES (:nik, :username, :email, :password, :status)'
            )->execute([
                'nik' => $data['nik'],
                'username' => $data['username'],
                'email' => $data['email'] ?? null,
                'password' => password_hash((string) $data['password'], PASSWORD_DEFAULT),
                'status' => 'aktif',
            ]);

            return $data['nik'];
        });

        return Response::created([
            'nik' => $result,
            'username' => $data['username'],
        ], 'Registrasi pasien berhasil');
    }

    public function me(Request $request): Response
    {
        $user = $request->user();

        if ($user === []) {
            throw HttpException::unauthorized();
        }

        return Response::ok($user);
    }

    public function refresh(Request $request): Response
    {
        $user = $request->user();

        $token = Jwt::issue([
            'sub' => $user['type'] === 'pasien' ? $user['nik'] : (int) $user['id'],
            'type' => $user['type'],
            'username' => $user['username'] ?? '',
        ]);

        return Response::ok([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], 'Token diperbarui');
    }

    /** @return array<string, mixed> */
    private function penggunaDetail(int $id): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT id_pengguna, nama_lengkap, username, email, no_hp, status, last_login_at
             FROM pengguna WHERE id_pengguna = :id'
        );
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        $roleStmt = $pdo->prepare(
            'SELECT r.nama_role FROM roles r
             INNER JOIN pengguna_roles pr ON pr.id_role = r.id_role
             WHERE pr.id_pengguna = :id'
        );
        $roleStmt->execute(['id' => $id]);
        $roles = $roleStmt->fetchAll(\PDO::FETCH_COLUMN);

        $permStmt = $pdo->prepare(
            'SELECT DISTINCT p.kode_permission FROM permissions p
             INNER JOIN role_permissions rp ON rp.id_permission = p.id_permission
             INNER JOIN pengguna_roles pr ON pr.id_role = rp.id_role
             WHERE pr.id_pengguna = :id'
        );
        $permStmt->execute(['id' => $id]);
        $permissions = $permStmt->fetchAll(\PDO::FETCH_COLUMN);

        if (in_array('super_admin', $roles, true)) {
            $permissions = $pdo->query('SELECT kode_permission FROM permissions')->fetchAll(\PDO::FETCH_COLUMN);
        }

        return [
            'type' => 'pengguna',
            'id' => (int) $user['id_pengguna'],
            'nama_lengkap' => $user['nama_lengkap'],
            'username' => $user['username'],
            'email' => $user['email'],
            'no_hp' => $user['no_hp'],
            'status' => $user['status'],
            'last_login_at' => $user['last_login_at'],
            'roles' => array_values($roles),
            'permissions' => array_values($permissions),
        ];
    }
}
