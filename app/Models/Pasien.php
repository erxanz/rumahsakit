<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Pasien extends Model
{
    protected string $table = 'pasien';
    protected string $primaryKey = 'nik';

    protected array $fillable = [
        'nik',
        'id_asuransi',
        'nomor_peserta_asuransi',
        'nama_lengkap',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'golongan_darah',
        'no_hp',
        'email',
        'alamat',
        'kelurahan',
        'kecamatan',
        'kota_kabupaten',
        'provinsi',
        'kode_pos',
        'nama_kontak_darurat',
        'no_kontak_darurat',
        'hubungan_kontak_darurat',
    ];
    protected array $filterable = [
        'jenis_kelamin',
        'golongan_darah',
        'id_asuransi',
        'kota_kabupaten',
        'provinsi',
        'nama_lengkap',
    ];
    protected array $searchable = ['nik', 'nama_lengkap', 'no_hp', 'email', 'alamat'];
    protected array $sortable = ['nik', 'nama_lengkap', 'tanggal_lahir', 'jenis_kelamin', 'created_at'];
}
