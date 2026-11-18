<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    /** @param array<string, mixed>|list<mixed> $data */
    private function __construct(
        private readonly array $data,
        private readonly int $status = 200,
        /** @var array<string, string> */
        private readonly array $headers = []
    ) {
    }

    /** @param array<string, mixed>|list<mixed> $data */
    public static function json(array $data, int $status = 200, array $headers = []): self
    {
        return new self($data, $status, $headers);
    }

    /** @param array<string, mixed>|list<mixed> $data */
    public static function ok(mixed $data = [], ?string $message = null): self
    {
        $payload = ['success' => true];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        $payload['data'] = $data;

        return new self($payload, 200);
    }

    /** @param array<string, mixed>|list<mixed> $data */
    public static function created(mixed $data = [], ?string $message = null): self
    {
        $payload = ['success' => true];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        $payload['data'] = $data;

        return new self($payload, 201);
    }

    public static function noContent(): self
    {
        return new self([], 204);
    }

    /** @param array<string, list<string>>|null $errors */
    public static function error(string $message, int $status = 400, ?array $errors = null): self
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null && $errors !== []) {
            $payload['errors'] = $errors;
        }

        return new self($payload, $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        return $this->data;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');

            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }
        }

        if ($this->status === 204) {
            return;
        }

        echo json_encode(
            $this->data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
        );
    }
}
