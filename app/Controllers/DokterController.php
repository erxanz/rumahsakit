<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CrudController;
use App\Models\Dokter;

final class DokterController extends CrudController
{
    protected string $modelClass = Dokter::class;
    protected string $resourceName = 'dokter';

    protected array $storeRules = [
        'id_poli' => 'required|integer|exists:poli,id_poli',
        'id_pengguna' => 'nullable|integer|exists:pengguna,id_pengguna',
        'kode_dokter' => 'required|string|max:30|unique:dokter,kode_dokter',
        'nama_dokter' => 'required|string|max:120',
        'no_sip' => 'nullable|string|max:80|unique:dokter,no_sip',
        'spesialisasi' => 'nullable|string|max:100',
        'no_hp' => 'nullable|string|max:20',
        'alamat' => 'nullable|string|max:2000',
        'status' => 'required|in:aktif,nonaktif',
    ];

    protected array $updateRules = [
        'id_poli' => 'required|integer|exists:poli,id_poli',
        'id_pengguna' => 'nullable|integer|exists:pengguna,id_pengguna',
        'kode_dokter' => 'required|string|max:30|unique:dokter,kode_dokter',
        'nama_dokter' => 'required|string|max:120',
        'no_sip' => 'nullable|string|max:80|unique:dokter,no_sip',
        'spesialisasi' => 'nullable|string|max:100',
        'no_hp' => 'nullable|string|max:20',
        'alamat' => 'nullable|string|max:2000',
        'status' => 'required|in:aktif,nonaktif',
    ];
}
