<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Role extends Model
{
    protected string $table = 'roles';
    protected string $primaryKey = 'id_role';

    protected array $fillable = ['nama_role', 'deskripsi'];
    protected array $filterable = ['nama_role'];
    protected array $searchable = ['nama_role', 'deskripsi'];
    protected array $sortable = ['id_role', 'nama_role', 'created_at'];
}
