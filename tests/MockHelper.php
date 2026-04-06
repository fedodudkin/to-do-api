<?php

declare(strict_types=1);

namespace Tests;

/**
 * Вспомогательный класс для мокирования php://input в тестах
 */
final class MockHelper
{
    private static ?string $mockInput = null;

    public static function setInput(string $input): void
    {
        self::$mockInput = $input;
    }

    public static function clearInput(): void
    {
        self::$mockInput = null;
    }

    public static function getInput(): ?string
    {
        return self::$mockInput;
    }

    /**
     * Устанавливает мок для file_get_contents в пространстве имен App
     */
    public static function installFileGetContentsMock(): void
    {
        // Создаем мок в пространстве имен App\Controllers
        if (!function_exists('App\\Controllers\\file_get_contents')) {
            eval('
                namespace App\\Controllers {
                    function file_get_contents(string $filename): string|false {
                        if ($filename === "php://input") {
                            return \\Tests\\MockHelper::getInput() ?? "";
                        }
                        return \\file_get_contents($filename);
                    }
                }
            ');
        }
    }
}
