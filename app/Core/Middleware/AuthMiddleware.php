<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Jwt;
use App\Core\Permissions;
use App\Core\Request;
use App\Core\Response;

final class AuthMiddleware
{
    public static function handle(Request $request, array $params, callable $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null) {
            throw HttpException::unauthorized('Token Bearer tidak ditemukan');
        }

        $claims = Jwt::decode($token);
        $type = (string) ($claims['type'] ?? 'pengguna');

        if ($type === 'pasien') {
            $request->setUser(self::resolvePasien((string) ($claims['sub'] ?? '')));
        } else {
            $request->setUser(self::resolvePengguna((int) ($claims['sub'] ?? 0)));
        }

        return $next($request);
    }

    /** @return array<string, mixed> */
    private static function resolvePengguna(int $id): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT id_pengguna, nama_lengkap, username, email, no_hp, status, last_login_at
             FROM pengguna WHERE id_pengguna = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        if ($user === false) {
            throw HttpException::unauthorized('Akun pengguna tidak ditemukan');
        }

        if ($user['status'] !== 'aktif') {
            throw HttpException::forbidden('Akun pengguna tidak aktif');
        }

        $roleStmt = $pdo->prepare(
            'SELECT r.nama_role
             FROM roles r
             INNER JOIN pengguna_roles pr ON pr.id_role = r.id_role
             WHERE pr.id_pengguna = :id'
        );
        $roleStmt->execute(['id' => $id]);
        $roles = $roleStmt->fetchAll(\PDO::FETCH_COLUMN);

        $permStmt = $pdo->prepare(
            'SELECT DISTINCT p.kode_permission
             FROM permissions p
             INNER JOIN role_permissions rp ON rp.id_permission = p.id_permission
             INNER JOIN pengguna_roles pr ON pr.id_role = rp.id_role
             WHERE pr.id_pengguna = :id'
        );
        $permStmt->execute(['id' => $id]);
        $permissions = $permStmt->fetchAll(\PDO::FETCH_COLUMN);

        if (in_array('super_admin', $roles, true)) {
            $permStmt = $pdo->query('SELECT kode_permission FROM permissions');
            $permissions = $permStmt->fetchAll(\PDO::FETCH_COLUMN);
        }

        return [
            'type' => 'pengguna',
            'id' => (int) $user['id_pengguna'],
            'nama' => $user['nama_lengkap'],
            'username' => $user['username'],
            'email' => $user['email'],
            'roles' => array_values($roles),
            'permissions' => array_values($permissions),
        ];
    }

    /** @return array<string, mixed> */
    private static function resolvePasien(string $nik): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT a.nik, a.username, a.email_login, a.status, p.nama_lengkap, p.no_hp, p.jenis_kelamin
             FROM akun_pasien a
             INNER JOIN pasien p ON p.nik = a.nik
             WHERE a.nik = :nik LIMIT 1'
        );
        $stmt->execute(['nik' => $nik]);
        $user = $stmt->fetch();

        if ($user === false) {
            throw HttpException::unauthorized('Akun pasien tidak ditemukan');
        }

        if ($user['status'] !== 'aktif') {
            throw HttpException::forbidden('Akun pasien tidak aktif');
        }

        return [
            'type' => 'pasien',
            'id' => $user['nik'],
            'nik' => $user['nik'],
            'nama' => $user['nama_lengkap'],
            'username' => $user['username'],
            'email' => $user['email_login'],
            'roles' => ['pasien'],
            'permissions' => Permissions::forRole('pasien'),
        ];
    }
}
