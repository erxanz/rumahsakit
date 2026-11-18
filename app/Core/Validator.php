<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string|list<string>> $rules
     * @param array{table?:string,column?:string,pk?:string,value?:string|int} $ignore unique ignore context
     */
    public static function validate(array $data, array $rules, array $ignore = []): void
    {
        $errors = [];

        foreach ($rules as $field => $definition) {
            $fieldRules = is_array($definition) ? $definition : explode('|', $definition);
            $value = $data[$field] ?? null;
            $isEmpty = $value === null || $value === '';

            $required = in_array('required', $fieldRules, true);

            if ($isEmpty && !$required) {
                continue;
            }

            $messages = self::checkField($field, $value, $fieldRules, $ignore, $data);

            if ($messages !== []) {
                $errors[$field] = $messages;
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    /**
     * @param list<string> $fieldRules
     * @param array<string, mixed> $ignore
     * @param array<string, mixed> $data
     * @return list<string>
     */
    private static function checkField(
        string $field,
        mixed $value,
        array $fieldRules,
        array $ignore,
        array $data
    ): array {
        $messages = [];

        foreach ($fieldRules as $rule) {
            if ($rule === 'nullable') {
                continue;
            }

            $name = $rule;
            $param = null;

            if (str_contains($rule, ':')) {
                [$name, $param] = explode(':', $rule, 2);
            }

            if ($name !== 'required' && ($value === null || $value === '')) {
                continue;
            }

            $error = self::applyRule($name, $param, $field, $value, $ignore, $data, $fieldRules);

            if ($error !== null) {
                $messages[] = $error;
            }
        }

        return $messages;
    }

    /**
     * @param array<string, mixed> $ignore
     * @param array<string, mixed> $data
     * @param list<string> $fieldRules
     */
    private static function applyRule(
        string $name,
        ?string $param,
        string $field,
        mixed $value,
        array $ignore,
        array $data,
        array $fieldRules = []
    ): ?string {
        $label = self::label($field);
        $isNumericField = in_array('numeric', $fieldRules, true)
            || in_array('integer', $fieldRules, true);

        return match ($name) {
            'required' => ($value === null || $value === '')
                ? "{$label} wajib diisi"
                : null,

            'string' => (is_string($value) || is_int($value) || is_float($value))
                ? null
                : "{$label} harus berupa teks",

            'numeric' => (is_numeric($value))
                ? null
                : "{$label} harus berupa angka",

            'integer' => self::isInteger($value)
                ? null
                : "{$label} harus berupa bilangan bulat",

            'boolean' => in_array($value, [true, false, 0, 1, '0', '1'], true)
                ? null
                : "{$label} harus berupa boolean",

            'email' => filter_var((string) $value, FILTER_VALIDATE_EMAIL)
                ? null
                : "{$label} harus berupa email yang valid",

            'date' => self::isDate($value)
                ? null
                : "{$label} harus berupa tanggal (YYYY-MM-DD)",

            'datetime' => self::isDateTime($value)
                ? null
                : "{$label} harus berupa tanggal dan waktu yang valid",

            'time' => preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', (string) $value)
                ? null
                : "{$label} harus berupa jam (HH:MM atau HH:MM:SS)",

            'min' => self::checkMin($value, (string) $param, $label, $isNumericField),

            'max' => self::checkMax($value, (string) $param, $label, $isNumericField),

            'digits' => is_string($value) && preg_match('/^\d+$/', $value) && strlen($value) === (int) $param
                ? null
                : "{$label} harus terdiri dari {$param} digit angka",

            'in' => in_array((string) $value, explode(',', (string) $param), true)
                ? null
                : "{$label} harus salah satu dari: " . str_replace(',', ', ', (string) $param),

            'regex' => preg_match($param, (string) $value)
                ? null
                : "{$label} tidak memenuhi format yang diizinkan",

            'alpha_dash' => preg_match('/^[A-Za-z0-9_\-.]+$/', (string) $value)
                ? null
                : "{$label} hanya boleh huruf, angka, strip, underscore, atau titik",

            'unique' => self::checkUnique($param, $value, $ignore, $label),

            'exists' => self::checkExists($param, $value, $label),

            default => "{$label} memiliki aturan validasi tidak dikenal: {$name}",
        };
    }

    private static function checkMin(mixed $value, string $param, string $label, bool $numeric = false): ?string
    {
        if ($numeric && is_numeric($value)) {
            return (float) $value >= (float) $param ? null : "{$label} minimal {$param}";
        }

        return mb_strlen((string) $value) >= (int) $param
            ? null
            : "{$label} minimal {$param} karakter";
    }

    private static function checkMax(mixed $value, string $param, string $label, bool $numeric = false): ?string
    {
        if ($numeric && is_numeric($value)) {
            return (float) $value <= (float) $param ? null : "{$label} maksimal {$param}";
        }

        return mb_strlen((string) $value) <= (int) $param
            ? null
            : "{$label} maksimal {$param} karakter";
    }

    /** @param array<string, mixed> $ignore */
    private static function checkUnique(?string $param, mixed $value, array $ignore, string $label): ?string
    {
        if ($param === null || !str_contains($param, ',')) {
            return "{$label} memiliki konfigurasi unique tidak valid";
        }

        [$table, $column] = explode(',', $param, 2);
        $pdo = Database::connection();

        $sql = "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = :value";
        $params = ['value' => $value];

        if (!empty($ignore['pk']) && array_key_exists('value', $ignore)) {
            $sql .= " AND `{$ignore['pk']}` <> :ignore";
            $params['ignore'] = $ignore['value'];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return ((int) $stmt->fetchColumn()) > 0
            ? "{$label} sudah terpakai"
            : null;
    }

    private static function checkExists(?string $param, mixed $value, string $label): ?string
    {
        if ($param === null || !str_contains($param, ',')) {
            return "{$label} memiliki konfigurasi exists tidak valid";
        }

        [$table, $column] = explode(',', $param, 2);
        $pdo = Database::connection();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = :value");
        $stmt->execute(['value' => $value]);

        return ((int) $stmt->fetchColumn()) > 0
            ? null
            : "{$label} tidak merujuk data yang ada";
    }

    private static function isInteger(mixed $value): bool
    {
        if (is_int($value)) {
            return true;
        }

        return is_string($value) && preg_match('/^-?\d+$/', $value) === 1;
    }

    private static function isDate(mixed $value): bool
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        [$y, $m, $d] = array_map('intval', explode('-', $value));

        return checkdate($m, $d, $y);
    }

    private static function isDateTime(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        try {
            new \DateTimeImmutable($value);

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    private static function label(string $field): string
    {
        return str_replace('_', ' ', $field);
    }
}
