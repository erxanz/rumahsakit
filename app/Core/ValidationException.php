<?php

declare(strict_types=1);

namespace App\Core;

final class ValidationException extends HttpException
{
    /** @param array<string, list<string>> $errors */
    public function __construct(array $errors, string $message = 'Data tidak valid')
    {
        parent::__construct($message, 422, $errors);
    }
}
