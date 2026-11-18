<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Tindakan extends Model
{
    protected string $table = 'tindakan';
    protected string $primaryKey = 'id_tindakan';

    protected array $fillable = ['kode_tindakan', 'nama_tindakan', 'keterangan', 'tarif', 'status'];
    protected array $filterable = ['kode_tindakan', 'nama_tindakan', 'status'];
    protected array $searchable = ['kode_tindakan', 'nama_tindakan', 'keterangan'];
    protected array $sortable = ['id_tindakan', 'kode_tindakan', 'nama_tindakan', 'tarif', 'status'];
}
