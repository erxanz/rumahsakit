<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Poli extends Model
{
    protected string $table = 'poli';
    protected string $primaryKey = 'id_poli';

    protected array $fillable = ['kode_poli', 'nama_poli', 'keterangan', 'status'];
    protected array $filterable = ['kode_poli', 'nama_poli', 'status'];
    protected array $searchable = ['kode_poli', 'nama_poli', 'keterangan'];
    protected array $sortable = ['id_poli', 'kode_poli', 'nama_poli', 'status', 'created_at'];
}
