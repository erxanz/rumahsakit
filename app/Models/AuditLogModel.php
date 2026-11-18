<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AuditLogModel extends Model
{
    protected string $table = 'audit_log';
    protected string $primaryKey = 'id_log';

    protected array $fillable = [
        'id_pengguna',
        'aktivitas',
        'nama_tabel',
        'id_data',
        'detail',
        'ip_address',
        'user_agent',
    ];
    protected array $filterable = ['id_pengguna', 'nama_tabel', 'aktivitas'];
    protected array $searchable = ['aktivitas', 'nama_tabel', 'id_data', 'detail'];
    protected array $sortable = ['id_log', 'created_at', 'aktivitas', 'nama_tabel'];
}
