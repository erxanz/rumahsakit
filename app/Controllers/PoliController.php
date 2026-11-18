<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CrudController;
use App\Models\Poli;

final class PoliController extends CrudController
{
    protected string $modelClass = Poli::class;
    protected string $resourceName = 'poli';

    protected array $storeRules = [
        'kode_poli' => 'required|string|max:20|unique:poli,kode_poli',
        'nama_poli' => 'required|string|max:100',
        'keterangan' => 'nullable|string|max:2000',
        'status' => 'required|in:aktif,nonaktif',
    ];

    protected array $updateRules = [
        'kode_poli' => 'required|string|max:20|unique:poli,kode_poli',
        'nama_poli' => 'required|string|max:100',
        'keterangan' => 'nullable|string|max:2000',
        'status' => 'required|in:aktif,nonaktif',
    ];
}
