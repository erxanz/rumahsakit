<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CrudController;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Models\Pembayaran;

final class PembayaranController extends CrudController
{
    protected string $modelClass = Pembayaran::class;
    protected string $resourceName = 'pembayaran';

    protected array $storeRules = [
        'id_kunjungan' => 'required|integer|exists:kunjungan,id_kunjungan',
        'total_tagihan' => 'nullable|numeric|min:0',
        'jumlah_dibayar' => 'nullable|numeric|min:0',
        'metode_pembayaran' => 'required|in:tunai,transfer,asuransi,lainnya',
        'catatan' => 'nullable|string|max:2000',
    ];

    protected array $updateRules = [
        'total_tagihan' => 'nullable|numeric|min:0',
        'jumlah_dibayar' => 'nullable|numeric|min:0',
        'metode_pembayaran' => 'required|in:tunai,transfer,asuransi,lainnya',
        'catatan' => 'nullable|string|max:2000',
    ];

    protected function beforeStore(Request $request, array $payload): array
    {
        $pdo = Database::connection();

        $kunjunganStmt = $pdo->prepare('SELECT status FROM kunjungan WHERE id_kunjungan = :id');
        $kunjunganStmt->execute(['id' => $payload['id_kunjungan']]);

        if ($kunjunganStmt->fetchColumn() === false) {
            throw HttpException::notFound('Kunjungan tidak ditemukan');
        }

        $dupStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM pembayaran WHERE id_kunjungan = :id AND status <> 'dibatalkan'"
        );
        $dupStmt->execute(['id' => $payload['id_kunjungan']]);

        if ((int) $dupStmt->fetchColumn() > 0) {
            throw HttpException::conflict('Pembayaran untuk kunjungan ini sudah tercatat');
        }

        $payload['nomor_pembayaran'] = $this->generateNomor();
        $payload['id_pengguna_kasir'] = $request->penggunaId();

        if (empty($payload['total_tagihan'])) {
            $payload['total_tagihan'] = $this->hitungTotalTagihan((int) $payload['id_kunjungan']);
        }

        $payload['jumlah_dibayar'] = $payload['jumlah_dibayar'] ?? 0;
        $payload['status'] = $this->resolveStatus(
            (float) $payload['total_tagihan'],
            (float) $payload['jumlah_dibayar']
        );

        if ($payload['jumlah_dibayar'] > 0) {
            $payload['tanggal_pembayaran'] = date('Y-m-d H:i:s');
        }

        return $payload;
    }

    public function bayar(Request $request): Response
    {
        $model = $this->model();
        $id = $request->intParam('id');
        $row = $model->find($id);

        if ($row === null) {
            throw HttpException::notFound('Pembayaran tidak ditemukan');
        }

        if ($row['status'] === 'dibatalkan') {
            throw HttpException::badRequest('Pembayaran sudah dibatalkan');
        }

        if ($row['status'] === 'lunas') {
            throw HttpException::badRequest('Tagihan sudah lunas');
        }

        $data = $request->json();

        $this->validate($data, [
            'jumlah_bayar' => 'required|numeric|min:0.01',
            'metode_pembayaran' => 'nullable|in:tunai,transfer,asuransi,lainnya',
            'catatan' => 'nullable|string|max:2000',
        ]);

        $jumlahBaru = (float) $row['jumlah_dibayar'] + (float) $data['jumlah_bayar'];
        $total = (float) $row['total_tagihan'];

        if ($jumlahBaru > $total) {
            throw HttpException::unprocessable('Jumlah pembayaran melebihi total tagihan', [
                'jumlah_bayar' => ['Jumlah pembayaran melebihi total tagihan'],
            ]);
        }

        $payload = [
            'jumlah_dibayar' => $jumlahBaru,
            'status' => $this->resolveStatus($total, $jumlahBaru),
            'tanggal_pembayaran' => $row['tanggal_pembayaran'] ?? date('Y-m-d H:i:s'),
        ];

        if (!empty($data['metode_pembayaran'])) {
            $payload['metode_pembayaran'] = $data['metode_pembayaran'];
        }

        if (!empty($data['catatan'])) {
            $payload['catatan'] = $data['catatan'];
        }

        $model->updateById($id, $payload);
        $this->audit($request, 'Mencatat pembayaran', 'pembayaran', $id, $payload);

        return Response::ok($model->find($id), 'Pembayaran berhasil dicatat');
    }

    public function show(Request $request): Response
    {
        $payload = parent::show($request)->data();
        $row = $payload['data'] ?? [];

        $stmt = Database::connection()->prepare(
            'SELECT k.no_kunjungan, k.status AS status_kunjungan, k.tanggal_kunjungan,
                    p.nama_lengkap AS nama_pasien, p.nik, pl.nama_poli
             FROM kunjungan k
             INNER JOIN pasien p ON p.nik = k.nik
             INNER JOIN poli pl ON pl.id_poli = k.id_poli
             WHERE k.id_kunjungan = :id'
        );
        $stmt->execute(['id' => $row['id_kunjungan']]);
        $row['kunjungan'] = $stmt->fetch() ?: null;

        $tindakan = Database::connection()->prepare(
            'SELECT t.kode_tindakan, t.nama_tindakan, kt.jumlah, kt.tarif_saat_transaksi,
                    (kt.jumlah * kt.tarif_saat_transaksi) AS subtotal
             FROM kunjungan_tindakan kt
             INNER JOIN tindakan t ON t.id_tindakan = kt.id_tindakan
             WHERE kt.id_kunjungan = :id'
        );
        $tindakan->execute(['id' => $row['id_kunjungan']]);
        $row['rincian_tindakan'] = $tindakan->fetchAll();

        return Response::ok($row);
    }

    private function resolveStatus(float $total, float $dibayar): string
    {
        if ($dibayar <= 0) {
            return 'belum_bayar';
        }

        return $dibayar + 0.00001 >= $total ? 'lunas' : 'sebagian';
    }

    private function hitungTotalTagihan(int $idKunjungan): float
    {
        $stmt = Database::connection()->prepare(
            'SELECT COALESCE(SUM(jumlah * tarif_saat_transaksi), 0)
             FROM kunjungan_tindakan WHERE id_kunjungan = :id'
        );
        $stmt->execute(['id' => $idKunjungan]);

        return (float) $stmt->fetchColumn();
    }

    private function generateNomor(): string
    {
        $prefix = 'PY-' . date('Ymd') . '-';

        $stmt = Database::connection()->prepare(
            'SELECT nomor_pembayaran FROM pembayaran
             WHERE nomor_pembayaran LIKE :prefix
             ORDER BY id_pembayaran DESC LIMIT 1'
        );
        $stmt->execute(['prefix' => $prefix . '%']);
        $last = $stmt->fetchColumn();

        $next = $last === false
            ? 1
            : ((int) substr((string) $last, strlen($prefix))) + 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
