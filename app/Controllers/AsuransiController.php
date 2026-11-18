<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CrudController;
use App\Models\Asuransi;

final class AsuransiController extends CrudController
{
    protected string $modelClass = Asuransi::class;
    protected string $resourceName = 'asuransi';

    protected array $storeRules = [
        'nama_asuransi' => 'required|string|max:120',
        'jenis_asuransi' => 'required|in:BPJS,swasta,lainnya',
        'nomor_kontak' => 'nullable|string|max:30',
        'alamat' => 'nullable|string|max:2000',
        'status' => 'required|in:aktif,nonaktif',
    ];

    protected array $updateRules = [
        'nama_asuransi' => 'required|string|max:120',
        'jenis_asuransi' => 'required|in:BPJS,swasta,lainnya',
        'nomor_kontak' => 'nullable|string|max:30',
        'alamat' => 'nullable|string|max:2000',
        'status' => 'required|in:aktif,nonaktif',
    ];
}
