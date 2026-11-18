<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CrudController;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Models\RekamMedis;

final class RekamMedisController extends CrudController
{
    protected string $modelClass = RekamMedis::class;
    protected string $resourceName = 'rekam medis';

    protected array $storeRules = [
        'id_kunjungan' => 'required|integer|exists:kunjungan,id_kunjungan',
        'id_dokter' => 'required|integer|exists:dokter,id_dokter',
        'tekanan_darah' => 'nullable|string|max:20',
        'suhu_celsius' => 'nullable|numeric|min:0|max:60',
        'berat_kg' => 'nullable|numeric|min:0|max:500',
        'tinggi_cm' => 'nullable|numeric|min:0|max:300',
        'diagnosis' => 'required|string|max:5000',
        'catatan_pemeriksaan' => 'nullable|string|max:5000',
        'tindakan_dan_terapi' => 'nullable|string|max:5000',
        'resep_obat' => 'nullable|string|max:5000',
    ];

    protected array $updateRules = [
        'id_kunjungan' => 'required|integer|exists:kunjungan,id_kunjungan',
        'id_dokter' => 'required|integer|exists:dokter,id_dokter',
        'tekanan_darah' => 'nullable|string|max:20',
        'suhu_celsius' => 'nullable|numeric|min:0|max:60',
        'berat_kg' => 'nullable|numeric|min:0|max:500',
        'tinggi_cm' => 'nullable|numeric|min:0|max:300',
        'diagnosis' => 'required|string|max:5000',
        'catatan_pemeriksaan' => 'nullable|string|max:5000',
        'tindakan_dan_terapi' => 'nullable|string|max:5000',
        'resep_obat' => 'nullable|string|max:5000',
    ];

    protected function baseFilters(Request $request): array
    {
        if ($request->isPasien()) {
            return ['id_kunjungan' => $this->kunjunganIdsPasien($request)];
        }

        return [];
    }

    protected function beforeStore(Request $request, array $payload): array
    {
        $this->assertBelongsToPasien($request, (int) $payload['id_kunjungan']);

        $exists = Database::connection()->prepare(
            'SELECT COUNT(*) FROM rekam_medis WHERE id_kunjungan = :id'
        );
        $exists->execute(['id' => $payload['id_kunjungan']]);

        if ((int) $exists->fetchColumn() > 0) {
            throw HttpException::conflict('Rekam medis untuk kunjungan ini sudah ada');
        }

        return $payload;
    }

    protected function afterStore(Request $request, int|string $id, array $payload): void
    {
        Database::connection()->prepare(
            "UPDATE kunjungan SET status = 'selesai' WHERE id_kunjungan = :id AND status <> 'batal'"
        )->execute(['id' => $payload['id_kunjungan']]);
    }

    protected function authorizeRow(Request $request, array $row): void
    {
        $this->assertBelongsToPasien($request, (int) $row['id_kunjungan']);
    }

    private function assertBelongsToPasien(Request $request, int $idKunjungan): void
    {
        if (!$request->isPasien()) {
            return;
        }

        $stmt = Database::connection()->prepare(
            'SELECT nik FROM kunjungan WHERE id_kunjungan = :id'
        );
        $stmt->execute(['id' => $idKunjungan]);
        $nik = $stmt->fetchColumn();

        if ($nik === false || $nik !== $request->nik()) {
            throw HttpException::forbidden('Rekam medis ini bukan milik Anda');
        }
    }

    /** @return list<string> */
    private function kunjunganIdsPasien(Request $request): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id_kunjungan FROM kunjungan WHERE nik = :nik'
        );
        $stmt->execute(['nik' => $request->nik()]);

        return array_map('strval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }
}
