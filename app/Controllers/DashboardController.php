<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class DashboardController extends Controller
{
    public function stats(Request $request): Response
    {
        $pdo = Database::connection();

        $count = static function (string $sql, array $params = []): int {
            $stmt = Database::connection()->prepare($sql);
            $stmt->execute($params);

            return (int) $stmt->fetchColumn();
        };

        $kunjunganHariIni = $count(
            'SELECT COUNT(*) FROM kunjungan WHERE DATE(tanggal_kunjungan) = CURDATE()'
        );

        $data = [
            'total_pasien' => $count('SELECT COUNT(*) FROM pasien'),
            'total_dokter_aktif' => $count("SELECT COUNT(*) FROM dokter WHERE status = 'aktif'"),
            'total_poli_aktif' => $count("SELECT COUNT(*) FROM poli WHERE status = 'aktif'"),
            'kunjungan_hari_ini' => $kunjunganHariIni,
            'antrean_menunggu' => $count("SELECT COUNT(*) FROM kunjungan WHERE status = 'menunggu'"),
            'pendaftaran_online_menunggu' => $count(
                "SELECT COUNT(*) FROM kunjungan WHERE sumber_pendaftaran = 'online' AND status_verifikasi = 'menunggu'"
            ),
            'pendapatan_hari_ini' => $this->pendapatanHariIni($pdo),
            'tagihan_belum_lunas' => $this->tagihanBelumLunas($pdo),
            'kunjungan_7_hari' => $this->kunjungan7Hari($pdo),
            'kunjungan_per_poli' => $this->kunjunganPerPoli($pdo),
        ];

        return Response::ok($data);
    }

    private function pendapatanHariIni(\PDO $pdo): float
    {
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(jumlah_dibayar), 0) FROM pembayaran
             WHERE status IN ("sebagian", "lunas")
               AND DATE(COALESCE(tanggal_pembayaran, created_at)) = CURDATE()'
        );
        $stmt->execute();

        return (float) $stmt->fetchColumn();
    }

    private function tagihanBelumLunas(\PDO $pdo): float
    {
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(total_tagihan - jumlah_dibayar), 0) FROM pembayaran
             WHERE status IN ("belum_bayar", "sebagian")'
        );
        $stmt->execute();

        return (float) $stmt->fetchColumn();
    }

    /** @return list<array<string, mixed>> */
    private function kunjungan7Hari(\PDO $pdo): array
    {
        $stmt = $pdo->query(
            'SELECT DATE(tanggal_kunjungan) AS tanggal, COUNT(*) AS jumlah
             FROM kunjungan
             WHERE tanggal_kunjungan >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(tanggal_kunjungan)
             ORDER BY tanggal'
        );

        return $stmt->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    private function kunjunganPerPoli(\PDO $pdo): array
    {
        $stmt = $pdo->query(
            'SELECT p.nama_poli, COUNT(k.id_kunjungan) AS jumlah
             FROM poli p
             LEFT JOIN kunjungan k ON k.id_poli = p.id_poli
                AND DATE(k.tanggal_kunjungan) = CURDATE()
             GROUP BY p.id_poli, p.nama_poli
             ORDER BY jumlah DESC'
        );

        return $stmt->fetchAll();
    }
}
