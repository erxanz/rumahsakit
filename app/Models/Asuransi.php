<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Asuransi extends Model
{
    protected string $table = 'asuransi';
    protected string $primaryKey = 'id_asuransi';

    protected array $fillable = [
        'nama_asuransi',
        'jenis_asuransi',
        'nomor_kontak',
        'alamat',
        'status',
    ];
    protected array $filterable = ['nama_asuransi', 'jenis_asuransi', 'status'];
    protected array $searchable = ['nama_asuransi', 'nomor_kontak', 'alamat'];
    protected array $sortable = ['id_asuransi', 'nama_asuransi', 'jenis_asuransi', 'status', 'created_at'];
}
