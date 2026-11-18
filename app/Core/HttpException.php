<?php

declare(strict_types=1);

namespace App\Core;

class HttpException extends \RuntimeException
{
    /** @param array<string, list<string>> $errors */
    public function __construct(
        string $message,
        private readonly int $status = 400,
        private readonly array $errors = []
    ) {
        parent::__construct($message, $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    public static function badRequest(string $message = 'Permintaan tidak valid'): self
    {
        return new self($message, 400);
    }

    public static function unauthorized(string $message = 'Tidak terautentikasi'): self
    {
        return new self($message, 401);
    }

    public static function forbidden(string $message = 'Akses ditolak'): self
    {
        return new self($message, 403);
    }

    public static function notFound(string $message = 'Data tidak ditemukan'): self
    {
        return new self($message, 404);
    }

    public static function conflict(string $message = 'Konflik data'): self
    {
        return new self($message, 409);
    }

    /** @param array<string, list<string>> $errors */
    public static function unprocessable(string $message = 'Validasi gagal', array $errors = []): self
    {
        return new self($message, 422, $errors);
    }
}
