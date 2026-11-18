<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class JadwalDokter extends Model
{
    protected string $table = 'jadwal_dokter';
    protected string $primaryKey = 'id_jadwal';

    protected array $fillable = [
        'id_dokter',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'kuota_pasien',
        'status',
    ];
    protected array $filterable = ['id_dokter', 'hari', 'status'];
    protected array $searchable = ['hari'];
    protected array $sortable = ['id_jadwal', 'id_dokter', 'hari', 'jam_mulai', 'jam_selesai', 'status'];
}
