<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CrudController;
use App\Models\Permission;

final class PermissionController extends CrudController
{
    protected string $modelClass = Permission::class;
    protected string $resourceName = 'permission';

    protected array $storeRules = [
        'kode_permission' => 'required|string|max:100|unique:permissions,kode_permission',
        'nama_permission' => 'required|string|max:120',
        'deskripsi' => 'nullable|string|max:255',
    ];

    protected array $updateRules = [
        'kode_permission' => 'required|string|max:100|unique:permissions,kode_permission',
        'nama_permission' => 'required|string|max:120',
        'deskripsi' => 'nullable|string|max:255',
    ];
}
