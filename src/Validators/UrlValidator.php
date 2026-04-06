<?php

declare(strict_types=1);

namespace App\Validators;

final class UrlValidator
{
    private const MAX_ID = 2147483647; // PHP_INT_MAX на 64-битных системах

    /**
     * Валидирует ID задачи из URL
     */
    public static function validateTaskId(string $idString): int
    {
        // Проверяем, что это целое число
        if (!ctype_digit($idString)) {
            throw new \InvalidArgumentException('ID должен быть числом');
        }

        $id = (int) $idString;

        // Проверяем диапазон
        if ($id < 1) {
            throw new \InvalidArgumentException('ID должен быть положительным числом');
        }

        if ($id > self::MAX_ID) {
            throw new \InvalidArgumentException('ID слишком большой');
        }

        return $id;
    }

    /**
     * Экранирует потенциально опасные символы в строках
     */
    public static function sanitizeString(string $input): string
    {
        // Удаляем null bytes и управляющие символы
        $cleaned = preg_replace('/[\x00-\x1F\x7F]/', '', $input);
        
        // Ограничиваем длину
        return mb_substr($cleaned, 0, 1000);
    }
}
