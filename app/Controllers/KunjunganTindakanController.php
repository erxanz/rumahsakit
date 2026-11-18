<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Models\KunjunganTindakan;

final class KunjunganTindakanController extends Controller
{
    public function index(Request $request): Response
    {
        $idKunjungan = $request->intParam('id');
        $this->assertKunjunganExists($idKunjungan, $request);

        $model = new KunjunganTindakan();

        return Response::ok($model->listByKunjungan($idKunjungan));
    }

    public function store(Request $request): Response
    {
        $idKunjungan = $request->intParam('id');
        $kunjungan = $this->assertKunjunganExists($idKunjungan, $request);

        $data = $request->json();

        $this->validate($data, [
            'id_tindakan' => 'required|integer|exists:tindakan,id_tindakan',
            'jumlah' => 'required|integer|min:1|max:999',
            'catatan' => 'nullable|string|max:1000',
        ]);

        if ($kunjungan['status'] === 'batal') {
            throw HttpException::badRequest('Kunjungan sudah dibatalkan');
        }

        $pdo = Database::connection();

        $exists = $pdo->prepare(
            'SELECT COUNT(*) FROM kunjungan_tindakan WHERE id_kunjungan = :k AND id_tindakan = :t'
        );
        $exists->execute(['k' => $idKunjungan, 't' => $data['id_tindakan']]);

        if ((int) $exists->fetchColumn() > 0) {
            throw HttpException::conflict('Tindakan sudah tercatat pada kunjungan ini');
        }

        $tarifStmt = $pdo->prepare('SELECT tarif FROM tindakan WHERE id_tindakan = :id');
        $tarifStmt->execute(['id' => $data['id_tindakan']]);
        $tarif = (float) $tarifStmt->fetchColumn();

        $pdo->prepare(
            'INSERT INTO kunjungan_tindakan (id_kunjungan, id_tindakan, jumlah, tarif_saat_transaksi, catatan)
             VALUES (:k, :t, :j, :tarif, :catatan)'
        )->execute([
            'k' => $idKunjungan,
            't' => $data['id_tindakan'],
            'j' => (int) $data['jumlah'],
            'tarif' => $tarif,
            'catatan' => $data['catatan'] ?? null,
        ]);

        $this->refreshTotalTagihan($idKunjungan);
        $this->audit($request, 'Menambahkan tindakan kunjungan', 'kunjungan_tindakan', $idKunjungan, $data);

        $model = new KunjunganTindakan();

        return Response::created($model->listByKunjungan($idKunjungan), 'Tindakan berhasil ditambahkan');
    }

    public function destroy(Request $request): Response
    {
        $idKunjungan = $request->intParam('id');
        $idTindakan = $request->intParam('tindakanId');
        $this->assertKunjunganExists($idKunjungan, $request);

        $stmt = Database::connection()->prepare(
            'DELETE FROM kunjungan_tindakan WHERE id_kunjungan = :k AND id_tindakan = :t'
        );
        $stmt->execute(['k' => $idKunjungan, 't' => $idTindakan]);

        if ($stmt->rowCount() === 0) {
            throw HttpException::notFound('Tindakan tidak ditemukan pada kunjungan ini');
        }

        $this->refreshTotalTagihan($idKunjungan);
        $this->audit($request, 'Menghapus tindakan kunjungan', 'kunjungan_tindakan', $idKunjungan, [
            'id_tindakan' => $idTindakan,
        ]);

        $model = new KunjunganTindakan();

        return Response::ok($model->listByKunjungan($idKunjungan), 'Tindakan berhasil dihapus');
    }

    /** @return array<string, mixed> */
    private function assertKunjunganExists(int $id, Request $request): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id_kunjungan, nik, status FROM kunjungan WHERE id_kunjungan = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row === false) {
            throw HttpException::notFound('Kunjungan tidak ditemukan');
        }

        if ($request->isPasien() && $row['nik'] !== $request->nik()) {
            throw HttpException::forbidden('Anda hanya dapat mengakses kunjungan milik sendiri');
        }

        return $row;
    }

    private function refreshTotalTagihan(int $idKunjungan): void
    {
        Database::connection()->prepare(
            'UPDATE pembayaran p
             SET p.total_tagihan = COALESCE((
                 SELECT SUM(kt.jumlah * kt.tarif_saat_transaksi)
                 FROM kunjungan_tindakan kt
                 WHERE kt.id_kunjungan = p.id_kunjungan
             ), 0),
             p.status = CASE
                 WHEN p.jumlah_dibayar >= COALESCE((
                     SELECT SUM(kt.jumlah * kt.tarif_saat_transaksi)
                     FROM kunjungan_tindakan kt
                     WHERE kt.id_kunjungan = p.id_kunjungan
                 ), 0) AND p.jumlah_dibayar > 0 THEN "lunas"
                 WHEN p.jumlah_dibayar > 0 THEN "sebagian"
                 ELSE "belum_bayar"
             END
             WHERE p.id_kunjungan = :id'
        )->execute(['id' => $idKunjungan]);
    }
}
