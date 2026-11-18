<?php

declare(strict_types=1);

namespace App\Core;

use App\Config\Config;

final class Jwt
{
    /** @param array<string, mixed> $claims */
    public static function issue(array $claims): string
    {
        $header = self::base64UrlEncode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT',
        ], JSON_UNESCAPED_SLASHES));

        $now = time();
        $payload = array_merge($claims, [
            'iat' => $now,
            'exp' => $now + Config::int('JWT_TTL', 3600),
            'iss' => Config::get('APP_NAME', 'Klinik API'),
        ]);

        $body = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = self::sign($header . '.' . $body);

        return $header . '.' . $body . '.' . $signature;
    }

    /** @return array<string, mixed> */
    public static function decode(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw HttpException::unauthorized('Token tidak valid');
        }

        [$header, $body, $signature] = $parts;
        $expected = self::sign($header . '.' . $body);

        if (!hash_equals($expected, $signature)) {
            throw HttpException::unauthorized('Signature token tidak valid');
        }

        $headerData = json_decode(self::base64UrlDecode($header), true);

        if (!is_array($headerData) || ($headerData['alg'] ?? '') !== 'HS256') {
            throw HttpException::unauthorized('Algoritma token tidak didukung');
        }

        $claims = json_decode(self::base64UrlDecode($body), true);

        if (!is_array($claims)) {
            throw HttpException::unauthorized('Payload token tidak valid');
        }

        if (isset($claims['exp']) && time() >= (int) $claims['exp']) {
            throw HttpException::unauthorized('Token sudah kedaluwarsa');
        }

        return $claims;
    }

    private static function sign(string $data): string
    {
        $secret = Config::get('JWT_SECRET');

        if ($secret === null || $secret === '') {
            throw new \RuntimeException('JWT_SECRET belum dikonfigurasi di .env');
        }

        return self::base64UrlEncode(hash_hmac('sha256', $data, $secret, true));
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;

        if ($remainder > 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode(strtr($data, '-_', '+/'), true);
    }
}
