<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CrudController;
use App\Models\AuditLogModel;

final class AuditLogController extends CrudController
{
    protected string $modelClass = AuditLogModel::class;
    protected string $resourceName = 'audit log';

    protected array $storeRules = [];
    protected array $updateRules = [];

    public function store(\App\Core\Request $request): \App\Core\Response
    {
        throw \App\Core\HttpException::forbidden('Audit log hanya dapat dibaca');
    }

    public function update(\App\Core\Request $request): \App\Core\Response
    {
        throw \App\Core\HttpException::forbidden('Audit log hanya dapat dibaca');
    }

    public function destroy(\App\Core\Request $request): \App\Core\Response
    {
        throw \App\Core\HttpException::forbidden('Audit log hanya dapat dibaca');
    }
}
