<?php
declare(strict_types=1);
namespace Sigta\Views;
use Sigta\Core\{Auth,Security,Http,Env};
final class Layout {
    public static function e(mixed $x):string{return Http::h($x);}
    public static function csrf():string{return '<input type="hidden" name="_csrf" value="'.self::e(Security::csrf()).'">';}
    public static function option(array $rows,string $key='id',string $label='name',?string $placeholder='Seleccionar...'):string {
        $out=$placeholder!==null?'<option value="">'.self::e($placeholder).'</option>':'';
        foreach($rows as $row)$out.='<option value="'.self::e($row[$key]).'">'.self::e($row[$label]).'</option>';
        return $out;
    }
    public static function badge(string $status):string {
        $map=['borrador'=>'neutral','presentada'=>'info','observada'=>'warning','aprobada'=>'success','en_desarrollo'=>'info','habilitada'=>'success','programada'=>'info','defendida'=>'info','concluida'=>'success','rechazada'=>'danger','caducada'=>'danger','pendiente'=>'warning','aprobado'=>'success','rechazado'=>'danger','finalizada'=>'success'];
        return '<span class="badge badge-'.($map[$status]??'neutral').'">'.self::e(str_replace('_',' ',ucfirst($status))).'</span>';
    }
    public static function page(string $title,string $body,string $subtitle=''):void {
        $user=Auth::user();$name=$user['full_name']??'Invitado';$roles=$user['roles']??[];
        $nav=[['/','Panel general','◫',true],['/postulaciones','Postulaciones','▤',true],['/defensas','Defensas y calendario','▦',true],['/reportes','Reportes','▥',Auth::has('director','secretaria','admin')],['/admin/convocatorias','Convocatorias','◈',Auth::has('director','admin')],['/admin/usuarios','Usuarios y roles','♙',Auth::has('admin')],['/notificaciones','Notificaciones','◉',true],['/perfil','Mi perfil','⚙',true]];
        $path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)??'/';$flash=$_SESSION['flash']??null;unset($_SESSION['flash']);
        $asset=Http::url('assets/css/app.css');$js=Http::url('assets/js/app.js');$home=Http::url('');
        $bodyClass=$user?'app-shell':'login-shell';
        echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="light"><meta name="theme-color" content="#142b49"><title>'.self::e($title).' · SIGTA</title><link rel="stylesheet" href="'.self::e($asset).'"></head><body class="'.$bodyClass.'">';
        if($user){
            echo '<aside class="sidebar" id="sidebar"><a class="brand" href="'.$home.'"><span class="brand-icon">S</span><span><strong>SIGTA</strong><small>Gestión de Titulación</small></span></a><div class="sidebar-label">ÁREA DE TRABAJO</div><nav class="nav">';
            foreach($nav as [$link,$label,$icon,$available])if($available){$active=($link==='/'?$path==='/' : str_starts_with($path,$link));echo '<a class="nav-link '.($active?'active':'').'" href="'.self::e(Http::url($link)).'"><span class="nav-symbol">'.$icon.'</span><span>'.self::e($label).'</span></a>';}
            echo '</nav><div class="sidebar-bottom"><div class="side-profile"><div class="avatar">'.self::e(strtoupper(substr($name,0,1))).'</div><div><strong>'.self::e($name).'</strong><small>'.self::e(implode(' · ',$roles)).'</small></div></div><form method="post" action="'.Http::url('logout').'">'.self::csrf().'<button class="logout" type="submit">↪ Cerrar sesión</button></form></div></aside>';
        }
        echo '<div class="page-main"><header class="topbar"><div class="topbar-heading">';if($user)echo '<button class="mobile-menu" type="button" aria-label="Abrir menú" data-menu>☰</button>';echo '<div><div class="eyebrow">PLATAFORMA ACADÉMICA · PROTOTIPO</div><h1>'.self::e($title).'</h1></div></div><div class="topbar-right"><span class="topbar-year">Gestión 2026</span>'.($user?'<span class="tiny-avatar">'.self::e(strtoupper(substr($name,0,1))).'</span>':'').'</div></header><main class="content">';
        if($subtitle!=='')echo '<p class="page-subtitle">'.self::e($subtitle).'</p>';
        if($flash)echo '<div role="alert" class="alert alert-'.self::e($flash['type']).'">'.self::e($flash['message']).'</div>';
        echo $body.'</main><footer class="footer">SIGTA · Proyecto académico independiente · No es plataforma oficial de UNIFRANZ</footer></div><script src="'.self::e($js).'" defer></script></body></html>';
    }
    public static function error(string $title,string $message):void {self::page($title,'<section class="card empty-state"><h2>'.self::e($message).'</h2><p><a class="button" href="'.Http::url('').'">Volver al inicio</a></p></section>');}
}
