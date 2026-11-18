<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Permission extends Model
{
    protected string $table = 'permissions';
    protected string $primaryKey = 'id_permission';

    protected array $fillable = ['kode_permission', 'nama_permission', 'deskripsi'];
    protected array $filterable = ['kode_permission'];
    protected array $searchable = ['kode_permission', 'nama_permission', 'deskripsi'];
    protected array $sortable = ['id_permission', 'kode_permission', 'nama_permission'];
}
