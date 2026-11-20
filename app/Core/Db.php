<?php
declare(strict_types=1);
namespace Sigta\Core;
use PDO;
use PDOException;
final class Db {
    private static ?PDO $db=null;
    public static function conn(): PDO {
        if (self::$db) return self::$db;
        $host=Env::get('DB_HOST','127.0.0.1'); $port=Env::get('DB_PORT','3306'); $name=Env::get('DB_DATABASE','sigta');
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) throw new \RuntimeException('Nombre de base de datos inválido');
        self::$db = new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",Env::get('DB_USERNAME','root'),Env::get('DB_PASSWORD',''),[
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES=>false,
            PDO::ATTR_STRINGIFY_FETCHES=>false,
        ]);
        self::$db->exec("SET time_zone = '-04:00'");
        return self::$db;
    }
    public static function all(string $sql,array $args=[]): array { $s=self::conn()->prepare($sql); $s->execute($args);return $s->fetchAll(); }
    public static function one(string $sql,array $args=[]): ?array { $s=self::conn()->prepare($sql); $s->execute($args);$r=$s->fetch(); return $r===false?null:$r; }
    public static function run(string $sql,array $args=[]): int { $s=self::conn()->prepare($sql); $s->execute($args); return $s->rowCount(); }
    public static function id(): int { return (int) self::conn()->lastInsertId(); }
    public static function transaction(callable $callback): mixed {
        $db=self::conn(); $db->beginTransaction();
        try { $value=$callback();$db->commit();return $value; } catch (\Throwable $e) {if ($db->inTransaction())$db->rollBack();throw $e;}
    }
}
