<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Kunjungan extends Model
{
    protected string $table = 'kunjungan';
    protected string $primaryKey = 'id_kunjungan';

    protected array $fillable = [
        'no_kunjungan',
        'nik',
        'id_poli',
        'id_dokter',
        'id_jadwal',
        'id_pengguna_pendaftaran',
        'tanggal_kunjungan',
        'tanggal_antrean',
        'sumber_pendaftaran',
        'kode_booking',
        'nomor_antrean',
        'status_verifikasi',
        'catatan_verifikasi',
        'jenis_kunjungan',
        'keluhan',
        'status',
    ];
    protected array $filterable = [
        'nik',
        'id_poli',
        'id_dokter',
        'id_jadwal',
        'sumber_pendaftaran',
        'status_verifikasi',
        'jenis_kunjungan',
        'status',
        'tanggal_antrean',
    ];
    protected array $searchable = ['no_kunjungan', 'kode_booking', 'nik', 'keluhan'];
    protected array $sortable = [
        'id_kunjungan',
        'no_kunjungan',
        'tanggal_kunjungan',
        'tanggal_antrean',
        'status',
        'status_verifikasi',
        'jenis_kunjungan',
    ];
}
