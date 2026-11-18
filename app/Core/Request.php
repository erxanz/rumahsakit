<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /** @var array<string, string> */
    private array $routeParams = [];

    /** @var array<string, mixed>|null */
    private ?array $jsonBody = null;

    /** @var array<string, mixed> */
    private array $user = [];

    private function __construct(
        private readonly string $method,
        private readonly string $path,
        /** @var array<string, mixed> */
        private readonly array $query,
        /** @var array<string, mixed> */
        private readonly array $body,
        /** @var array<string, string> */
        private readonly array $headers,
        private readonly string $ip,
        private readonly string $userAgent
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') ?: '/';

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = (string) $value;
            }
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }

        return new self(
            $method,
            $path,
            $_GET,
            [],
            $headers,
            self::resolveIp(),
            (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')
        );
    }

    private static function resolveIp(): string
    {
        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', (string) $_SERVER[$key])[0]);

                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function ip(): string
    {
        return $this->ip;
    }

    public function userAgent(): string
    {
        return $this->userAgent;
    }

    /** @return array<string, mixed> */
    public function query(): array
    {
        return $this->query;
    }

    public function queryValue(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return array_merge($this->query, $this->json());
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $json = $this->json();

        return $json[$key] ?? $this->query[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function json(): array
    {
        if ($this->jsonBody !== null) {
            return $this->jsonBody;
        }

        $contentType = $this->header('content-type') ?? '';
        $raw = file_get_contents('php://input') ?: '';

        if ($raw !== '' && str_contains(strtolower($contentType), 'application/json')) {
            $decoded = json_decode($raw, true);
            $this->jsonBody = is_array($decoded) ? $decoded : [];
        } elseif (!empty($_POST)) {
            $this->jsonBody = $_POST;
        } else {
            $this->jsonBody = [];
        }

        return $this->jsonBody;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('authorization') ?? '';

        if (preg_match('/^Bearer\s+(\S+)$/i', $auth, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /** @param array<string, string> $params */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function param(string $key, ?string $default = null): ?string
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function intParam(string $key): int
    {
        return (int) ($this->routeParams[$key] ?? 0);
    }

    /** @return array<string, mixed> */
    public function user(): array
    {
        return $this->user;
    }

    public function setUser(array $user): void
    {
        $this->user = $user;
    }

    public function isPasien(): bool
    {
        return ($this->user['type'] ?? '') === 'pasien';
    }

    public function penggunaId(): ?int
    {
        $id = $this->user['id'] ?? null;

        return $id === null ? null : (int) $id;
    }

    public function nik(): ?string
    {
        return $this->user['nik'] ?? null;
    }
}
