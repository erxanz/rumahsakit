<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class RekamMedis extends Model
{
    protected string $table = 'rekam_medis';
    protected string $primaryKey = 'id_rekam_medis';

    protected array $fillable = [
        'id_kunjungan',
        'id_dokter',
        'tekanan_darah',
        'suhu_celsius',
        'berat_kg',
        'tinggi_cm',
        'diagnosis',
        'catatan_pemeriksaan',
        'tindakan_dan_terapi',
        'resep_obat',
    ];
    protected array $filterable = ['id_kunjungan', 'id_dokter'];
    protected array $searchable = ['diagnosis', 'catatan_pemeriksaan', 'tindakan_dan_terapi', 'resep_obat'];
    protected array $sortable = ['id_rekam_medis', 'id_kunjungan', 'id_dokter', 'created_at', 'updated_at'];
}
