<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Dokter extends Model
{
    protected string $table = 'dokter';
    protected string $primaryKey = 'id_dokter';

    protected array $fillable = [
        'id_pengguna',
        'id_poli',
        'kode_dokter',
        'nama_dokter',
        'no_sip',
        'spesialisasi',
        'no_hp',
        'alamat',
        'status',
    ];
    protected array $filterable = ['id_poli', 'kode_dokter', 'nama_dokter', 'spesialisasi', 'status'];
    protected array $searchable = ['kode_dokter', 'nama_dokter', 'spesialisasi', 'no_sip'];
    protected array $sortable = ['id_dokter', 'kode_dokter', 'nama_dokter', 'spesialisasi', 'status', 'created_at'];
}
