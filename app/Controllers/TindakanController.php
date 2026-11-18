<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CrudController;
use App\Models\Tindakan;

final class TindakanController extends CrudController
{
    protected string $modelClass = Tindakan::class;
    protected string $resourceName = 'tindakan';

    protected array $storeRules = [
        'kode_tindakan' => 'required|string|max:30|unique:tindakan,kode_tindakan',
        'nama_tindakan' => 'required|string|max:120',
        'keterangan' => 'nullable|string|max:2000',
        'tarif' => 'required|numeric|min:0',
        'status' => 'required|in:aktif,nonaktif',
    ];

    protected array $updateRules = [
        'kode_tindakan' => 'required|string|max:30|unique:tindakan,kode_tindakan',
        'nama_tindakan' => 'required|string|max:120',
        'keterangan' => 'nullable|string|max:2000',
        'tarif' => 'required|numeric|min:0',
        'status' => 'required|in:aktif,nonaktif',
    ];
}
