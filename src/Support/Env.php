<?php

declare(strict_types=1);

namespace App\Support;

final class Env
{
    /**
     * Загружает переменные окружения из указанного файла в глобальные массивы $_ENV и getenv.
     */
    public static function load(string $path): void
    {
        if (!is_readable($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if ($name === '') {
                continue;
            }
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"'))
                || (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }
            if (getenv($name) === false && ($_ENV[$name] ?? null) === null) {
                $_ENV[$name] = $value;
                putenv($name . '=' . $value);
            }
        }
    }

    public static function string(string $key, string $default = ''): string
    {
        $v = $_ENV[$key] ?? getenv($key);
        if ($v === false || $v === null || $v === '') {
            return $default;
        }
        return (string) $v;
    }

    public static function int(string $key, int $default): int
    {
        $v = self::string($key, (string) $default);
        return (int) $v;
    }
}
