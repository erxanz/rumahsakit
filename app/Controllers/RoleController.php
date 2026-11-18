<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CrudController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Role;

final class RoleController extends CrudController
{
    protected string $modelClass = Role::class;
    protected string $resourceName = 'role';

    protected array $storeRules = [
        'nama_role' => 'required|string|max:50|unique:roles,nama_role',
        'deskripsi' => 'nullable|string|max:255',
        'permissions' => 'nullable',
    ];

    protected array $updateRules = [
        'nama_role' => 'required|string|max:50|unique:roles,nama_role',
        'deskripsi' => 'nullable|string|max:255',
        'permissions' => 'nullable',
    ];

    public function show(Request $request): Response
    {
        $payload = parent::show($request)->data();
        $row = $payload['data'] ?? [];

        $stmt = Database::connection()->prepare(
            'SELECT p.id_permission, p.kode_permission, p.nama_permission, p.deskripsi
             FROM permissions p
             INNER JOIN role_permissions rp ON rp.id_permission = p.id_permission
             WHERE rp.id_role = :id
             ORDER BY p.kode_permission'
        );
        $stmt->execute(['id' => $row['id_role']]);
        $row['permissions'] = $stmt->fetchAll();

        return Response::ok($row);
    }

    protected function afterStore(Request $request, int|string $id, array $payload): void
    {
        $this->syncPermissions((int) $id, $request->input('permissions'));
    }

    protected function afterUpdate(Request $request, int|string $id, array $payload): void
    {
        $this->syncPermissions((int) $id, $request->input('permissions'), true);
    }

    private function syncPermissions(int $idRole, mixed $permissions, bool $force = false): void
    {
        if ($permissions === null) {
            return;
        }

        if (!is_array($permissions)) {
            throw \App\Core\HttpException::unprocessable('Format permissions tidak valid');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $pdo->prepare('DELETE FROM role_permissions WHERE id_role = :id')
                ->execute(['id' => $idRole]);

            $stmt = $pdo->prepare(
                'INSERT INTO role_permissions (id_role, id_permission) VALUES (:id_role, :id_permission)'
            );

            foreach ($permissions as $permId) {
                $stmt->execute(['id_role' => $idRole, 'id_permission' => (int) $permId]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
