<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;

final class PermissionMiddleware
{
    public static function handle(Request $request, array $params, callable $next): Response
    {
        $permission = $params[0] ?? '';

        if ($permission === '') {
            throw HttpException::forbidden('Permission tidak dikonfigurasi');
        }

        $user = $request->user();

        if ($user === []) {
            throw HttpException::unauthorized('Tidak terautentikasi');
        }

        $granted = $user['permissions'] ?? [];

        if (!in_array($permission, $granted, true)) {
            throw HttpException::forbidden(
                'Anda tidak memiliki izin: ' . $permission
            );
        }

        return $next($request);
    }
}
