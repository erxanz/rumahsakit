<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Pengguna extends Model
{
    protected string $table = 'pengguna';
    protected string $primaryKey = 'id_pengguna';

    protected array $fillable = [
        'nama_lengkap',
        'username',
        'email',
        'password_hash',
        'no_hp',
        'status',
    ];
    protected array $filterable = ['username', 'nama_lengkap', 'email', 'status'];
    protected array $searchable = ['nama_lengkap', 'username', 'email', 'no_hp'];
    protected array $sortable = ['id_pengguna', 'nama_lengkap', 'username', 'status', 'created_at', 'last_login_at'];
    protected array $hidden = ['password_hash'];
}
