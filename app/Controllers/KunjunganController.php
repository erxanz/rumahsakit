<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CrudController;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Models\Kunjungan;

final class KunjunganController extends CrudController
{
    protected string $modelClass = Kunjungan::class;
    protected string $resourceName = 'kunjungan';

    protected array $storeRules = [
        'nik' => 'required|digits:16|exists:pasien,nik',
        'id_poli' => 'required|integer|exists:poli,id_poli',
        'id_dokter' => 'nullable|integer|exists:dokter,id_dokter',
        'id_jadwal' => 'nullable|integer|exists:jadwal_dokter,id_jadwal',
        'tanggal_antrean' => 'nullable|date',
        'sumber_pendaftaran' => 'required|in:loket,online',
        'nomor_antrean' => 'nullable|integer|min:1',
        'kode_booking' => 'nullable|string|max:40',
        'jenis_kunjungan' => 'required|in:baru,lama,kontrol',
        'keluhan' => 'nullable|string|max:2000',
        'status' => 'nullable|in:menunggu,diperiksa,selesai,batal',
    ];

    protected array $updateRules = [
        'id_poli' => 'required|integer|exists:poli,id_poli',
        'id_dokter' => 'nullable|integer|exists:dokter,id_dokter',
        'id_jadwal' => 'nullable|integer|exists:jadwal_dokter,id_jadwal',
        'tanggal_antrean' => 'nullable|date',
        'sumber_pendaftaran' => 'required|in:loket,online',
        'nomor_antrean' => 'nullable|integer|min:1',
        'kode_booking' => 'nullable|string|max:40',
        'jenis_kunjungan' => 'required|in:baru,lama,kontrol',
        'keluhan' => 'nullable|string|max:2000',
        'status' => 'nullable|in:menunggu,diperiksa,selesai,batal',
        'status_verifikasi' => 'nullable|in:tidak_perlu,menunggu,terverifikasi,ditolak',
        'catatan_verifikasi' => 'nullable|string|max:255',
    ];

    protected function baseFilters(Request $request): array
    {
        if ($request->isPasien()) {
            return ['nik' => (string) $request->nik()];
        }

        return [];
    }

    protected function authorizeRow(Request $request, array $row): void
    {
        if ($request->isPasien() && $row['nik'] !== $request->nik()) {
            throw HttpException::forbidden('Anda hanya dapat mengakses kunjungan milik sendiri');
        }
    }

    protected function beforeValidate(Request $request, array $data): array
    {
        if ($request->isPasien()) {
            $data['nik'] = (string) $request->nik();
        }

        return $data;
    }

    protected function beforeStore(Request $request, array $payload): array
    {
        if ($request->isPasien()) {
            $payload['nik'] = (string) $request->nik();
        }

        $payload['no_kunjungan'] = $this->generateNoKunjungan();
        $payload['id_pengguna_pendaftaran'] = $request->isPasien() ? null : $request->penggunaId();
        $payload['tanggal_kunjungan'] = date('Y-m-d H:i:s');

        $sumber = (string) $payload['sumber_pendaftaran'];

        if ($sumber === 'online') {
            foreach (['kode_booking', 'nomor_antrean', 'tanggal_antrean'] as $field) {
                if (empty($payload[$field])) {
                    throw HttpException::unprocessable(
                        'Pendaftaran online wajib menyertakan kode booking, nomor antrean, dan tanggal antrean',
                        [$field => ["{$field} wajib diisi untuk pendaftaran online"]]
                    );
                }
            }

            $payload['status_verifikasi'] = $payload['status_verifikasi'] ?? 'menunggu';
        } else {
            $payload['kode_booking'] = null;
            $payload['status_verifikasi'] = 'tidak_perlu';
            $payload['tanggal_antrean'] = $payload['tanggal_antrean'] ?? date('Y-m-d');
            $payload['nomor_antrean'] = $payload['nomor_antrean']
                ?? $this->nextNomorAntrean(
                    isset($payload['id_jadwal']) ? (int) $payload['id_jadwal'] : null,
                    (string) $payload['tanggal_antrean'],
                    (int) $payload['id_poli']
                );
        }

        $payload['status'] = $payload['status'] ?? 'menunggu';

        return $payload;
    }

    /** @param array<string, mixed> $payload */
    public function verify(Request $request): Response
    {
        $model = $this->model();
        $id = $request->intParam('id');
        $row = $model->find($id);

        if ($row === null) {
            throw HttpException::notFound('Kunjungan tidak ditemukan');
        }

        $data = $request->json();

        $this->validate($data, [
            'status_verifikasi' => 'required|in:terverifikasi,ditolak',
            'catatan_verifikasi' => 'nullable|string|max:255',
        ]);

        if ($row['sumber_pendaftaran'] !== 'online') {
            throw HttpException::badRequest('Hanya pendaftaran online yang perlu diverifikasi');
        }

        $payload = [
            'status_verifikasi' => $data['status_verifikasi'],
            'catatan_verifikasi' => $data['catatan_verifikasi'] ?? null,
        ];

        if ($data['status_verifikasi'] === 'ditolak') {
            $payload['status'] = 'batal';
        }

        $model->updateById($id, $payload);
        $this->audit(
            $request,
            'Verifikasi pendaftaran online: ' . $data['status_verifikasi'],
            'kunjungan',
            $id,
            $payload
        );

        return Response::ok($model->find($id), 'Verifikasi kunjungan berhasil');
    }

    public function cancel(Request $request): Response
    {
        $model = $this->model();
        $id = $request->intParam('id');
        $row = $model->find($id);

        if ($row === null) {
            throw HttpException::notFound('Kunjungan tidak ditemukan');
        }

        $this->authorizeRow($request, $row);

        if ($row['status'] === 'selesai') {
            throw HttpException::badRequest('Kunjungan yang sudah selesai tidak dapat dibatalkan');
        }

        $model->updateById($id, ['status' => 'batal']);
        $this->audit($request, 'Membatalkan kunjungan', 'kunjungan', $id, ['status' => 'batal']);

        return Response::ok($model->find($id), 'Kunjungan berhasil dibatalkan');
    }

    /** @return list<array<string, mixed>> */
    public function antrean(Request $request): Response
    {
        $tanggal = (string) $request->queryValue('tanggal', date('Y-m-d'));
        $idPoli = $request->queryValue('id_poli');

        $sql = 'SELECT k.id_kunjungan, k.no_kunjungan, k.nomor_antrean, k.status, k.jenis_kunjungan,
                       k.keluhan, k.tanggal_antrean, k.sumber_pendaftaran, k.status_verifikasi,
                       p.nama_lengkap AS nama_pasien, p.nik,
                       pl.nama_poli, d.nama_dokter
                FROM kunjungan k
                INNER JOIN pasien p ON p.nik = k.nik
                INNER JOIN poli pl ON pl.id_poli = k.id_poli
                LEFT JOIN dokter d ON d.id_dokter = k.id_dokter
                WHERE k.tanggal_antrean = :tanggal';

        $params = ['tanggal' => $tanggal];

        if ($request->isPasien()) {
            $sql .= ' AND k.nik = :nik';
            $params['nik'] = $request->nik();
        } elseif ($idPoli !== null && $idPoli !== '') {
            $sql .= ' AND k.id_poli = :id_poli';
            $params['id_poli'] = (int) $idPoli;
        }

        $sql .= ' ORDER BY k.nomor_antrean IS NULL, k.nomor_antrean, k.id_kunjungan';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return Response::ok($stmt->fetchAll());
    }

    private function generateNoKunjungan(): string
    {
        $prefix = 'KJ-' . date('Ymd') . '-';

        $stmt = Database::connection()->prepare(
            'SELECT no_kunjungan FROM kunjungan WHERE no_kunjungan LIKE :prefix ORDER BY id_kunjungan DESC LIMIT 1'
        );
        $stmt->execute(['prefix' => $prefix . '%']);
        $last = $stmt->fetchColumn();

        $next = $last === false
            ? 1
            : ((int) substr((string) $last, strlen($prefix))) + 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function nextNomorAntrean(?int $idJadwal, string $tanggal, int $idPoli): int
    {
        $sql = 'SELECT COALESCE(MAX(nomor_antrean), 0) + 1 FROM kunjungan
                WHERE tanggal_antrean = :tanggal AND id_poli = :id_poli';
        $params = ['tanggal' => $tanggal, 'id_poli' => $idPoli];

        if ($idJadwal !== null) {
            $sql .= ' AND id_jadwal = :id_jadwal';
            $params['id_jadwal'] = $idJadwal;
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }
}
