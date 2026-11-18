<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function validate(array $data, array $rules, array $ignore = []): void
    {
        Validator::validate($data, $rules, $ignore);
    }

    /** @param array<string, mixed> $payload */
    protected function audit(Request $request, string $aktivitas, ?string $table = null, null|int|string $id = null, ?array $payload = null): void
    {
        AuditLog::record(
            $request->penggunaId(),
            $aktivitas,
            $table,
            $id,
            $payload,
            $request->ip(),
            $request->userAgent()
        );
    }

    /** @param array<string, mixed> $data */
    protected function only(array $data, array $fields): array
    {
        return array_intersect_key($data, array_flip($fields));
    }
}
