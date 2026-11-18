<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CrudController;
use App\Core\HttpException;
use App\Core\Request;
use App\Models\Pengguna;

final class PenggunaController extends CrudController
{
    protected string $modelClass = Pengguna::class;
    protected string $resourceName = 'pengguna';

    protected array $storeRules = [
        'nama_lengkap' => 'required|string|max:120',
        'username' => 'required|string|max:50|alpha_dash|unique:pengguna,username',
        'email' => 'nullable|email|max:120|unique:pengguna,email',
        'password' => 'required|string|min:6|max:255',
        'no_hp' => 'nullable|string|max:20',
        'status' => 'required|in:aktif,nonaktif,terkunci',
        'roles' => 'nullable',
    ];

    protected array $updateRules = [
        'nama_lengkap' => 'required|string|max:120',
        'username' => 'required|string|max:50|alpha_dash|unique:pengguna,username',
        'email' => 'nullable|email|max:120|unique:pengguna,email',
        'password' => 'nullable|string|min:6|max:255',
        'no_hp' => 'nullable|string|max:20',
        'status' => 'required|in:aktif,nonaktif,terkunci',
        'roles' => 'nullable',
    ];

    protected function beforeStore(Request $request, array $payload): array
    {
        $payload['password_hash'] = password_hash((string) $payload['password'], PASSWORD_DEFAULT);
        unset($payload['password']);

        $payload['__roles'] = $request->input('roles');

        return $payload;
    }

    protected function afterStore(Request $request, int|string $id, array $payload): void
    {
        $this->syncRoles((int) $id, $payload['__roles'] ?? null);
        unset($payload['__roles']);
    }

    protected function beforeUpdate(Request $request, array $row, array $payload): array
    {
        if (!empty($payload['password'])) {
            $payload['password_hash'] = password_hash((string) $payload['password'], PASSWORD_DEFAULT);
        }

        unset($payload['password']);
        $payload['__roles'] = $request->input('roles');

        return $payload;
    }

    protected function afterUpdate(Request $request, int|string $id, array $payload): void
    {
        $this->syncRoles((int) $id, $payload['__roles'] ?? null, true);
    }

    /** @param mixed $roles */
    private function syncRoles(int $idPengguna, mixed $roles, bool $force = false): void
    {
        if ($roles === null && !$force) {
            return;
        }

        if ($roles === null) {
            return;
        }

        if (!is_array($roles)) {
            throw HttpException::unprocessable('Format roles tidak valid', [
                'roles' => ['Roles harus berupa array id_role'],
            ]);
        }

        $pdo = \App\Core\Database::connection();
        $pdo->beginTransaction();

        try {
            $pdo->prepare('DELETE FROM pengguna_roles WHERE id_pengguna = :id')
                ->execute(['id' => $idPengguna]);

            $stmt = $pdo->prepare(
                'INSERT INTO pengguna_roles (id_pengguna, id_role) VALUES (:id_pengguna, :id_role)'
            );

            foreach ($roles as $roleId) {
                $stmt->execute([
                    'id_pengguna' => $idPengguna,
                    'id_role' => (int) $roleId,
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
