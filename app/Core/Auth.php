<?php
declare(strict_types=1);
namespace Sigta\Core;
final class Auth {
    private static ?array $cached=null;
    public static function user(): ?array {
        $id=$_SESSION['user_id']??null;if(!$id)return null;
        if(self::$cached!==null)return self::$cached;
        $user=Db::one('SELECT id,full_name,email,active,created_at FROM users WHERE id=?',[(int)$id]);
        if(!$user||!(int)$user['active']){self::logout();return null;}
        $roles=Db::all('SELECT r.code FROM roles r JOIN user_roles ur ON ur.role_id=r.id WHERE ur.user_id=?',[$id]);
        $user['roles']=array_column($roles,'code');return self::$cached=$user;
    }
    public static function id(): int {return (int)(self::user()['id']??0);}
    public static function has(string ...$roles): bool {return (bool)array_intersect($roles,self::user()['roles']??[]);}
    public static function require(string ...$roles): void {
        if(!self::user())Http::redirect('login');
        if($roles&&!self::has(...$roles))throw new \DomainException('No tienes permisos para esta operación.');
    }
    public static function managesCareer(int $careerId): bool {
        if(self::has('admin'))return true;
        if(!self::has('director','secretaria'))return false;
        return (bool)Db::one('SELECT 1 FROM staff_careers WHERE user_id=? AND career_id=?',[self::id(),$careerId]);
    }
    public static function login(string $email,string $pass): bool {
        $email=strtolower(trim($email));
        $pepper=Env::get('APP_KEY','');$key=hash_hmac('sha256',$email,$pepper);$ip=hash_hmac('sha256',(string)($_SERVER['REMOTE_ADDR']??'local'),$pepper);
        $failed=Db::one("SELECT COUNT(*) AS n FROM login_attempts WHERE success=0 AND created_at >= NOW() - INTERVAL 15 MINUTE AND (identifier_hash=? OR ip_hash=?)",[$key,$ip]);
        if((int)$failed['n']>=8) throw new \DomainException('Demasiados intentos. Intenta nuevamente en 15 minutos.');
        $u=Db::one('SELECT * FROM users WHERE email=? AND active=1',[$email]);
        $valid=$u&&password_verify($pass,$u['password_hash']);
        Db::run('INSERT INTO login_attempts(identifier_hash,ip_hash,success) VALUES(?,?,?)',[$key,$ip,$valid?1:0]);
        if(!$valid)return false;
        session_regenerate_id(true);$_SESSION['user_id']=(int)$u['id'];$_SESSION['issued_at']=time();$_SESSION['csrf']=bin2hex(random_bytes(32));self::$cached=null;
        if(password_needs_rehash($u['password_hash'],defined('PASSWORD_ARGON2ID')?PASSWORD_ARGON2ID:PASSWORD_BCRYPT))Db::run('UPDATE users SET password_hash=? WHERE id=?',[password_hash($pass,defined('PASSWORD_ARGON2ID')?PASSWORD_ARGON2ID:PASSWORD_BCRYPT),$u['id']]);
        Security::audit((int)$u['id'],'auth.login','users',(int)$u['id']);return true;
    }
    public static function logout(): void {
        self::$cached=null;unset($_SESSION['user_id']);$_SESSION['csrf']=bin2hex(random_bytes(32));
        session_regenerate_id(true);
    }
}
