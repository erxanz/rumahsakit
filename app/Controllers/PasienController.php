<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CrudController;
use App\Models\Pasien;

final class PasienController extends CrudController
{
    protected string $modelClass = Pasien::class;
    protected string $resourceName = 'pasien';

    protected array $storeRules = [
        'nik' => 'required|digits:16|unique:pasien,nik',
        'id_asuransi' => 'nullable|integer|exists:asuransi,id_asuransi',
        'nomor_peserta_asuransi' => 'nullable|string|max:60',
        'nama_lengkap' => 'required|string|max:120',
        'tempat_lahir' => 'nullable|string|max:80',
        'tanggal_lahir' => 'nullable|date',
        'jenis_kelamin' => 'required|in:L,P',
        'golongan_darah' => 'nullable|in:A,B,AB,O,Tidak diketahui',
        'no_hp' => 'nullable|string|max:20',
        'email' => 'nullable|email|max:120',
        'alamat' => 'required|string|max:2000',
        'kelurahan' => 'nullable|string|max:100',
        'kecamatan' => 'nullable|string|max:100',
        'kota_kabupaten' => 'nullable|string|max:100',
        'provinsi' => 'nullable|string|max:100',
        'kode_pos' => 'nullable|string|max:10',
        'nama_kontak_darurat' => 'nullable|string|max:120',
        'no_kontak_darurat' => 'nullable|string|max:20',
        'hubungan_kontak_darurat' => 'nullable|string|max:50',
    ];

    protected array $updateRules = [
        'nik' => 'required|digits:16|unique:pasien,nik',
        'id_asuransi' => 'nullable|integer|exists:asuransi,id_asuransi',
        'nomor_peserta_asuransi' => 'nullable|string|max:60',
        'nama_lengkap' => 'required|string|max:120',
        'tempat_lahir' => 'nullable|string|max:80',
        'tanggal_lahir' => 'nullable|date',
        'jenis_kelamin' => 'required|in:L,P',
        'golongan_darah' => 'nullable|in:A,B,AB,O,Tidak diketahui',
        'no_hp' => 'nullable|string|max:20',
        'email' => 'nullable|email|max:120',
        'alamat' => 'required|string|max:2000',
        'kelurahan' => 'nullable|string|max:100',
        'kecamatan' => 'nullable|string|max:100',
        'kota_kabupaten' => 'nullable|string|max:100',
        'provinsi' => 'nullable|string|max:100',
        'kode_pos' => 'nullable|string|max:10',
        'nama_kontak_darurat' => 'nullable|string|max:120',
        'no_kontak_darurat' => 'nullable|string|max:20',
        'hubungan_kontak_darurat' => 'nullable|string|max:50',
    ];

    protected function baseFilters(\App\Core\Request $request): array
    {
        if ($request->isPasien()) {
            return ['nik' => (string) $request->nik()];
        }

        return [];
    }

    protected function authorizeRow(\App\Core\Request $request, array $row): void
    {
        if ($request->isPasien() && $row['nik'] !== $request->nik()) {
            throw \App\Core\HttpException::forbidden('Anda hanya dapat mengakses data diri sendiri');
        }
    }
}
