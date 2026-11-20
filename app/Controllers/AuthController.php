<?php
declare(strict_types=1);
namespace Sigta\Controllers;
use Sigta\Core\{Auth,Security,Db,Http};
use Sigta\Views\Layout as L;
final class AuthController {
    public static function form():void {
        if(Auth::user())Http::redirect('');
        $out='<section class="login-intro"><span class="kicker">SISTEMA INTEGRAL DE GESTIÓN DE TITULACIÓN ACADÉMICA</span><h2>Tu trayectoria de titulación, organizada en un solo lugar.</h2><p>Postulaciones, evaluaciones, tribunales y defensas con trazabilidad en cada etapa.</p><div class="login-stats"><div><strong>5</strong><span>Modalidades académicas</span></div><div><strong>1</strong><span>Expediente centralizado</span></div></div></section>';
        $out.='<section class="card login-card"><div class="card-kicker">BIENVENIDO A SIGTA</div><h2>Ingresar a tu cuenta</h2><p class="muted">Utiliza tus credenciales del entorno de demostración.</p><form method="post" action="'.Http::url('login').'">'.L::csrf().'<label>Correo electrónico<input type="email" name="email" required autocomplete="username" maxlength="190" placeholder="nombre@universidad.edu.bo"></label><label>Contraseña<input type="password" name="password" required autocomplete="current-password" placeholder="••••••••••••"></label><button type="submit" class="button full">Iniciar sesión <span>→</span></button></form><div class="notice">Por seguridad, no existen contraseñas predeterminadas. El administrador se crea mediante el instalador CLI.</div></section>';
        L::page('Bienvenido a SIGTA',$out);
    }
    public static function login():void {
        $email=strtolower(Http::input('email',190));$pass=$_POST['password']??'';
        if(!is_string($pass)||strlen($pass)>200)throw new \DomainException('Credenciales inválidas');
        if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new \DomainException('Correo inválido');
        if(!Auth::login($email,$pass))throw new \DomainException('Correo o contraseña incorrectos');
        Http::flash('success','Sesión iniciada correctamente');Http::redirect('');
    }
    public static function logout():void {Auth::logout();Http::redirect('login');}
    public static function password():void {
        Auth::require();$current=$_POST['current_password']??'';$new=$_POST['new_password']??'';
        if(!is_string($current)||!is_string($new))throw new \DomainException('Entrada inválida');
        $u=Db::one('SELECT password_hash FROM users WHERE id=?',[Auth::id()]);
        if(!password_verify($current,$u['password_hash']))throw new \DomainException('La contraseña actual no coincide');
        $hash=Security::passwordHash($new);
        Db::run('UPDATE users SET password_hash=? WHERE id=?',[$hash,Auth::id()]);
        Security::audit(Auth::id(),'account.password_changed','users',Auth::id());Auth::logout();Http::flash('success','Clave actualizada. Vuelve a ingresar.');Http::redirect('login');
    }
}
