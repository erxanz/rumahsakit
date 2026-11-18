<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CrudController;
use App\Core\HttpException;
use App\Core\Request;
use App\Models\JadwalDokter;

final class JadwalDokterController extends CrudController
{
    protected string $modelClass = JadwalDokter::class;
    protected string $resourceName = 'jadwal dokter';

    private const HARI_LIST = 'Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu';

    protected array $storeRules = [
        'id_dokter' => 'required|integer|exists:dokter,id_dokter',
        'hari' => 'required|in:' . self::HARI_LIST,
        'jam_mulai' => 'required|time',
        'jam_selesai' => 'required|time',
        'kuota_pasien' => 'nullable|integer|min:1',
        'status' => 'required|in:aktif,nonaktif',
    ];

    protected array $updateRules = [
        'id_dokter' => 'required|integer|exists:dokter,id_dokter',
        'hari' => 'required|in:' . self::HARI_LIST,
        'jam_mulai' => 'required|time',
        'jam_selesai' => 'required|time',
        'kuota_pasien' => 'nullable|integer|min:1',
        'status' => 'required|in:aktif,nonaktif',
    ];

    protected function beforeStore(Request $request, array $payload): array
    {
        return $this->assertJam($payload);
    }

    protected function beforeUpdate(Request $request, array $row, array $payload): array
    {
        if (isset($payload['jam_mulai'], $payload['jam_selesai'])) {
            $payload = $this->assertJam($payload);
        }

        return $payload;
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    private function assertJam(array $payload): array
    {
        if (strtotime((string) $payload['jam_selesai']) <= strtotime((string) $payload['jam_mulai'])) {
            throw HttpException::unprocessable('Jam selesai harus setelah jam mulai', [
                'jam_selesai' => ['Jam selesai harus setelah jam mulai'],
            ]);
        }

        return $payload;
    }
}
