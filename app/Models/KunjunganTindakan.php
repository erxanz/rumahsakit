<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class KunjunganTindakan extends Model
{
    protected string $table = 'kunjungan_tindakan';
    protected string $primaryKey = 'id_kunjungan';

    protected array $fillable = [
        'id_kunjungan',
        'id_tindakan',
        'jumlah',
        'tarif_saat_transaksi',
        'catatan',
    ];
    protected array $filterable = ['id_kunjungan', 'id_tindakan'];
    protected array $searchable = ['catatan'];
    protected array $sortable = ['id_kunjungan', 'id_tindakan', 'jumlah', 'tarif_saat_transaksi'];

    /** @return list<array<string, mixed>> */
    public function listByKunjungan(int $idKunjungan): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT kt.id_kunjungan, kt.id_tindakan, kt.jumlah, kt.tarif_saat_transaksi, kt.catatan,
                    t.kode_tindakan, t.nama_tindakan,
                    (kt.jumlah * kt.tarif_saat_transaksi) AS subtotal
             FROM kunjungan_tindakan kt
             INNER JOIN tindakan t ON t.id_tindakan = kt.id_tindakan
             WHERE kt.id_kunjungan = :id
             ORDER BY t.nama_tindakan'
        );
        $stmt->execute(['id' => $idKunjungan]);

        return $stmt->fetchAll();
    }
}
