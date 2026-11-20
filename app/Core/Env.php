<?php
declare(strict_types=1);
namespace Sigta\Core;
final class Env {
    private static ?array $data = null;
    public static function load(): void {
        if (self::$data !== null) return;
        self::$data = [];
        $file = dirname(__DIR__,2) . '/.env';
        if (!is_file($file)) return;
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line,'#') || !str_contains($line,'=')) continue;
            [$key,$val] = explode('=',$line,2);
            self::$data[trim($key)] = trim(trim($val), "\"'");
        }
    }
    public static function get(string $key, ?string $default = null): ?string {
        self::load();
        $v = getenv($key);
        return $v !== false ? (string)$v : (self::$data[$key] ?? $default);
    }
}
