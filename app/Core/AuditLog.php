<?php

declare(strict_types=1);

namespace App\Core;

use PDOException;

final class AuditLog
{
    public static function record(
        ?int $idPengguna,
        string $aktivitas,
        ?string $namaTabel = null,
        null|int|string $idData = null,
        ?array $detail = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        try {
            $stmt = Database::connection()->prepare(
                'INSERT INTO audit_log (id_pengguna, aktivitas, nama_tabel, id_data, detail, ip_address, user_agent)
                 VALUES (:id_pengguna, :aktivitas, :nama_tabel, :id_data, :detail, :ip_address, :user_agent)'
            );

            $stmt->execute([
                'id_pengguna' => $idPengguna,
                'aktivitas' => mb_substr($aktivitas, 0, 100),
                'nama_tabel' => $namaTabel,
                'id_data' => $idData === null ? null : (string) $idData,
                'detail' => $detail === null ? null : json_encode($detail, JSON_UNESCAPED_UNICODE),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent === null ? null : mb_substr($userAgent, 0, 255),
            ]);
        } catch (PDOException) {
            // Audit log tidak boleh menggagalkan operasi utama.
        }
    }
}
