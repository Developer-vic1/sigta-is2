<?php
declare(strict_types=1);
namespace Sigta\Core;
final class Security {
    public static function init(): void {
        if(session_status()===PHP_SESSION_ACTIVE)return;
        $https=!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off';
        ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');
        session_name(Env::get('SESSION_NAME','SIGTASESSID'));
        session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$https,'httponly'=>true,'samesite'=>'Lax']);session_start();
        if(!isset($_SESSION['issued_at']))$_SESSION['issued_at']=time();
        if(time()-$_SESSION['issued_at']>900){session_regenerate_id(true);$_SESSION['issued_at']=time();}
        if(isset($_SESSION['last_activity'])&&time()-$_SESSION['last_activity']>2700){unset($_SESSION['user_id']);session_regenerate_id(true);}
        $_SESSION['last_activity']=time();
        if(!isset($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));
        header('X-Content-Type-Options: nosniff');header('X-Frame-Options: DENY');header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
        header('Cache-Control: private, no-store');
        if($https)header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    public static function csrf(): string {return (string)($_SESSION['csrf']??'');}
    public static function verifyCsrf(): void {
        $token=$_POST['_csrf']??'';
        if(!is_string($token)||!hash_equals(self::csrf(),$token)) throw new \DomainException('La sesión del formulario venció. Actualiza la página.');
    }
    public static function passwordHash(string $password): string {
        self::checkPassword($password);
        return password_hash($password,defined('PASSWORD_ARGON2ID')?PASSWORD_ARGON2ID:PASSWORD_BCRYPT);
    }
    public static function checkPassword(string $password): void {
        if(strlen($password)<12||strlen($password)>72)throw new \DomainException('La contraseña debe tener entre 12 y 72 bytes.');
        if(!preg_match('/[A-Z]/',$password)||!preg_match('/[a-z]/',$password)||!preg_match('/[0-9]/',$password))throw new \DomainException('Incluye mayúsculas, minúsculas y números.');
    }
    public static function audit(?int $user,string $event,string $entity,int $entityId,array $details=[]): void {
        $iphash=hash_hmac('sha256',(string)($_SERVER['REMOTE_ADDR']??'CLI'),Env::get('APP_KEY',''));
        Db::run('INSERT INTO audit_logs(actor_user_id,event_code,entity_type,entity_id,details_json,ip_hash) VALUES(?,?,?,?,?,?)',[$user,$event,$entity,$entityId,json_encode($details,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$iphash]);
    }
}
