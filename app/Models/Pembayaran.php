<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Pembayaran extends Model
{
    protected string $table = 'pembayaran';
    protected string $primaryKey = 'id_pembayaran';

    protected array $fillable = [
        'id_kunjungan',
        'id_pengguna_kasir',
        'nomor_pembayaran',
        'tanggal_pembayaran',
        'total_tagihan',
        'jumlah_dibayar',
        'metode_pembayaran',
        'status',
        'catatan',
    ];
    protected array $filterable = ['id_kunjungan', 'metode_pembayaran', 'status', 'nomor_pembayaran'];
    protected array $searchable = ['nomor_pembayaran', 'catatan'];
    protected array $sortable = [
        'id_pembayaran',
        'nomor_pembayaran',
        'tanggal_pembayaran',
        'total_tagihan',
        'jumlah_dibayar',
        'status',
        'created_at',
    ];
}
