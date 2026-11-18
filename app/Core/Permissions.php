<?php

declare(strict_types=1);

namespace App\Core;

final class Permissions
{
    /** Fallback jika role belum ada di database. @var array<string, list<string>> */
    private const FALLBACK = [
        'pasien' => [
            'dashboard.view',
            'pasien.view',
            'kunjungan.view',
            'kunjungan.manage',
            'rekam_medis.view',
            'pembayaran.view',
        ],
    ];

    /**
     * Daftar kode permission milik sebuah role (sumber kebenaran: tabel permissions).
     *
     * @return list<string>
     */
    public static function forRole(string $namaRole): array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT p.kode_permission
                 FROM permissions p
                 INNER JOIN role_permissions rp ON rp.id_permission = p.id_permission
                 INNER JOIN roles r ON r.id_role = rp.id_role
                 WHERE r.nama_role = :role
                 ORDER BY p.kode_permission'
            );
            $stmt->execute(['role' => $namaRole]);
            $codes = array_values($stmt->fetchAll(\PDO::FETCH_COLUMN));

            if ($codes !== []) {
                return $codes;
            }
        } catch (\Throwable) {
            // jatuh ke fallback
        }

        return self::FALLBACK[$namaRole] ?? [];
    }
}
