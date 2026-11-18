<?php

declare(strict_types=1);

use App\Config\Config;
use App\Core\Autoloader;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\ValidationException;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Core/Autoloader.php';
Autoloader::register();

Config::load(BASE_PATH . '/.env');

date_default_timezone_set(Config::get('APP_TIMEZONE', 'Asia/Jakarta'));

// CORS (sesuaikan APP_CORS_ORIGIN di .env untuk produksi)
$origin = Config::get('APP_CORS_ORIGIN', '*');

if (!headers_sent()) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept');
    header('Access-Control-Max-Age: 86400');
    header('Vary: Origin');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$response = null;

try {
    $request = Request::fromGlobals();
    $router = new Router();

    require BASE_PATH . '/app/Routes/api.php';

    $response = $router->dispatch($request);
} catch (ValidationException $e) {
    $response = Response::error($e->getMessage(), $e->status(), $e->errors());
} catch (HttpException $e) {
    $response = Response::error($e->getMessage(), $e->status(), $e->errors() ?: null);
} catch (\Throwable $e) {
    $debug = Config::bool('APP_DEBUG', false);

    $payload = [
        'success' => false,
        'message' => $debug ? $e->getMessage() : 'Terjadi kesalahan pada server',
    ];

    if ($debug) {
        $payload['exception'] = get_class($e);
        $payload['file'] = $e->getFile() . ':' . $e->getLine();
        $payload['trace'] = explode("\n", $e->getTraceAsString())[0] ?? null;
    }

    $response = Response::json($payload, 500);
}

$response->send();
