<?php
declare(strict_types=1);
namespace Sigta\Controllers;
use Sigta\Core\{Auth,Db,Http,Security};
use Sigta\Views\Layout as L;
final class AdminController {
    public static function users():void {
        Auth::require('admin');$roles=Db::all('SELECT id,code,name FROM roles ORDER BY id');$careers=Db::all('SELECT id,name FROM careers WHERE active=1');
        $list=Db::all("SELECT u.id,u.full_name,u.email,u.active,GROUP_CONCAT(r.name ORDER BY r.id SEPARATOR ', ') roles FROM users u LEFT JOIN user_roles ur ON ur.user_id=u.id LEFT JOIN roles r ON r.id=ur.role_id GROUP BY u.id ORDER BY u.id DESC LIMIT 100");
        $teachers=Db::all('SELECT t.user_id id,u.full_name name FROM teachers t JOIN users u ON u.id=t.user_id WHERE u.active=1 ORDER BY u.full_name');
        $specialties=Db::all('SELECT id,name FROM specialties ORDER BY name');
        ob_start();echo '<div class="section-heading"><div><h2>Directorio de usuarios</h2><p>Cuentas de demostración y permisos asignados</p></div></div><div class="split-grid"><section class="card"><h3>Alta de cuenta</h3><form method="post" action="'.Http::url('admin/usuarios').'">'.L::csrf().'<label>Nombre completo<input required name="full_name" minlength="5" maxlength="150"></label><label>Correo institucional<input type="email" required name="email" maxlength="190"></label><label>Contraseña inicial<input type="password" required name="password" minlength="12" autocomplete="new-password"><small>12–72 bytes, mayúscula, minúscula y número.</small></label><label>Rol<select name="role_id" required>'.L::option($roles,'id','name').'</select></label><label>Código académico/laboral<input name="identity_code" maxlength="30" placeholder="Ej. SIS-2026-001"></label><label>Carrera (estudiantes)<select name="career_id">'.L::option($careers).'</select></label><button class="button full" type="submit">Crear usuario</button></form></section><section class="card"><h3>Usuarios registrados</h3><div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th></tr></thead><tbody>';
        foreach($list as $u)echo '<tr><td>'.L::e($u['full_name']).'</td><td>'.L::e($u['email']).'</td><td>'.L::e($u['roles']).'</td></tr>';
        echo '</tbody></table></div></section></div>';
        echo '<div class="split-grid"><section class="card"><h3>Agregar permiso a usuario existente</h3><form method="post" action="'.Http::url('admin/usuarios/0/rol').'" data-grant-form>'.L::csrf().'<label>Usuario<select name="user_id" required>'.L::option($list,'id','full_name').'</select></label><label>Nuevo rol<select name="role_id" required>'.L::option($roles,'id','name').'</select></label><label>Código de matrícula/docente (si aplica)<input name="identity_code" maxlength="30"></label><label>Carrera (si aplica)<select name="career_id">'.L::option($careers).'</select></label><button class="button" type="submit">Agregar permiso</button></form></section><section class="card"><h3>Especialidad docente</h3><p class="muted">Se utiliza al sugerir asignaciones equilibradas.</p><form method="post" action="'.Http::url('admin/docentes/especialidad').'">'.L::csrf().'<label>Docente<select name="teacher_id" required>'.L::option($teachers).'</select></label><label>Especialidad<select name="specialty_id" required>'.L::option($specialties).'</select></label><button class="button" type="submit">Vincular especialidad</button></form></section></div>';
        L::page('Usuarios y seguridad',ob_get_clean(),'Administra permisos y datos ficticios de prueba. No uses credenciales institucionales reales.');
    }
    public static function createUser():void {
        Auth::require('admin');$name=Http::input('full_name',150);$email=strtolower(Http::input('email',190));$password=$_POST['password']??'';
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($name)<5||!is_string($password))throw new \DomainException('Datos de cuenta inválidos');
        $roleId=Http::positiveInt('role_id');$role=Db::one('SELECT code FROM roles WHERE id=?',[$roleId]);if(!$role)throw new \DomainException('Rol inválido');
        $code=Http::input('identity_code',30);$career=null;
        if(in_array($role['code'],['estudiante','director','secretaria'],true)){$career=Http::positiveInt('career_id');if(!Db::one('SELECT id FROM careers WHERE id=?',[$career]))throw new \DomainException('Carrera inválida');}
        if(in_array($role['code'],['estudiante','tutor','tribunal'],true)&&strlen($code)<3)throw new \DomainException('El código académico es obligatorio');
        $hash=Security::passwordHash($password);
        Db::transaction(function()use($name,$email,$hash,$roleId,$role,$code,$career){
            Db::run('INSERT INTO users(full_name,email,password_hash) VALUES(?,?,?)',[$name,$email,$hash]);$uid=Db::id();
            Db::run('INSERT INTO user_roles(user_id,role_id) VALUES(?,?)',[$uid,$roleId]);
            if($role['code']==='estudiante')Db::run('INSERT INTO students(user_id,career_id,enrollment_code) VALUES(?,?,?)',[$uid,$career,$code]);
            if(in_array($role['code'],['director','secretaria'],true))Db::run('INSERT INTO staff_careers(user_id,career_id) VALUES(?,?)',[$uid,$career]);
            if(in_array($role['code'],['tutor','tribunal'],true))Db::run('INSERT INTO teachers(user_id,employee_code) VALUES(?,?)',[$uid,$code]);
            Security::audit(Auth::id(),'user.created','users',$uid,['role'=>$role['code']]);
        });Http::flash('success','Usuario creado sin exposición de contraseña en el historial');Http::redirect('admin/usuarios');
    }
    public static function userStatus(int $id):void {
        Auth::require('admin');
        if($id===Auth::id())throw new \DomainException('No puedes desactivar tu propia cuenta');
        $target=Db::one('SELECT id,active FROM users WHERE id=?',[$id]);if(!$target)throw new \DomainException('Usuario no encontrado');
        $status=Http::input('active',1);if(!in_array($status,['0','1'],true))throw new \DomainException('Estado inválido');
        if($status==='0'){
            $last=Db::one("SELECT COUNT(DISTINCT u.id) n FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id WHERE r.code='admin' AND u.active=1 AND u.id<>?",[$id]);
            if(!(int)$last['n'] && Db::one("SELECT 1 FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=? AND r.code='admin'",[$id]))throw new \DomainException('No puedes desactivar al último administrador');
        }
        Db::run('UPDATE users SET active=? WHERE id=?',[(int)$status,$id]);
        Security::audit(Auth::id(),'user.status','users',$id,['active'=>(int)$status]);
        Http::flash('success','Estado de usuario actualizado');Http::redirect('admin/usuarios');
    }
    public static function grantRole(int $routeId):void {
        Auth::require('admin');
        $id=Http::positiveInt('user_id');$roleId=Http::positiveInt('role_id');
        $role=Db::one('SELECT code FROM roles WHERE id=?',[$roleId]);
        if(!$role||!Db::one('SELECT id FROM users WHERE id=?',[$id]))throw new \DomainException('Usuario o rol no encontrado');
        $code=Http::input('identity_code',30);
        $career=null;if(in_array($role['code'],['estudiante','director','secretaria'],true))$career=Http::positiveInt('career_id');
        Db::transaction(function()use($id,$roleId,$role,$code,$career){
            Db::run('INSERT IGNORE INTO user_roles(user_id,role_id) VALUES(?,?)',[$id,$roleId]);
            if($role['code']==='estudiante'&&!Db::one('SELECT 1 FROM students WHERE user_id=?',[$id])){
                if(strlen($code)<3)throw new \DomainException('Código de matrícula requerido');
                Db::run('INSERT INTO students(user_id,career_id,enrollment_code) VALUES(?,?,?)',[$id,$career,$code]);
            }
            if(in_array($role['code'],['tutor','tribunal'],true)&&!Db::one('SELECT 1 FROM teachers WHERE user_id=?',[$id])){
                if(strlen($code)<3)throw new \DomainException('Código docente requerido');
                Db::run('INSERT INTO teachers(user_id,employee_code) VALUES(?,?)',[$id,$code]);
            }
            if(in_array($role['code'],['director','secretaria'],true))Db::run('INSERT IGNORE INTO staff_careers(user_id,career_id) VALUES(?,?)',[$id,$career]);
            Security::audit(Auth::id(),'user.role_added','users',$id,['role'=>$role['code']]);
        });
        Http::flash('success','Permiso asociado');Http::redirect('admin/usuarios');
    }
    public static function specialty():void {
        Auth::require('admin');$teacher=Http::positiveInt('teacher_id');$specialty=Http::positiveInt('specialty_id');
        if(!Db::one('SELECT user_id FROM teachers WHERE user_id=?',[$teacher])||!Db::one('SELECT id FROM specialties WHERE id=?',[$specialty]))throw new \DomainException('Docente o especialidad inexistente');
        Db::run('INSERT IGNORE INTO teacher_specialties(teacher_id,specialty_id) VALUES(?,?)',[$teacher,$specialty]);
        Security::audit(Auth::id(),'teacher.specialty','teachers',$teacher,['specialty'=>$specialty]);
        Http::flash('success','Especialidad asociada');Http::redirect('admin/usuarios');
    }
    public static function calls():void {
        Auth::require('director','admin');$careers=Auth::has('admin')?Db::all('SELECT * FROM careers WHERE active=1'):Db::all('SELECT c.* FROM careers c JOIN staff_careers s ON s.career_id=c.id WHERE s.user_id=? AND c.active=1',[Auth::id()]);$modalities=Db::all('SELECT * FROM modalities');
        $regulations=Auth::has('admin')?Db::all('SELECT cr.*,c.name career FROM career_regulations cr JOIN careers c ON c.id=cr.career_id ORDER BY cr.id DESC LIMIT 25'):Db::all('SELECT cr.*,c.name career FROM career_regulations cr JOIN careers c ON c.id=cr.career_id JOIN staff_careers sc ON sc.career_id=cr.career_id WHERE sc.user_id=? ORDER BY cr.id DESC LIMIT 25',[Auth::id()]);
        $calls=Auth::has('admin')?Db::all('SELECT ca.*,c.name career FROM calls ca JOIN careers c ON c.id=ca.career_id ORDER BY ca.id DESC LIMIT 100'):Db::all('SELECT ca.*,c.name career FROM calls ca JOIN careers c ON c.id=ca.career_id JOIN staff_careers sc ON sc.career_id=ca.career_id AND sc.user_id=? ORDER BY ca.id DESC LIMIT 100',[Auth::id()]);
        ob_start();echo '<div class="split-grid"><section class="card"><h3>Nueva convocatoria</h3><form method="post" action="'.Http::url('admin/convocatorias').'">'.L::csrf().'<label>Título<input name="title" required maxlength="160" placeholder="Convocatoria de titulación 2026-II"></label><label>Carrera<select name="career_id" required>'.L::option($careers).'</select></label><div class="two-fields"><label>Apertura<input type="datetime-local" name="opens_at" required></label><label>Cierre<input type="datetime-local" name="closes_at" required></label></div><label>Plazo máximo sin avance (días)<input type="number" name="progress_days" min="1" max="1460" value="180" required></label><label>Modalidades habilitadas</label><div class="checks">';
        foreach($modalities as $m)echo '<label class="check"><input type="checkbox" name="modalities[]" value="'.L::e($m['id']).'" checked> '.L::e($m['name']).'</label>';
        echo '</div><button class="button full" type="submit">Publicar convocatoria</button></form></section><section class="card"><h3>Convocatorias existentes</h3><div class="table-wrap"><table><thead><tr><th>Título / carrera</th><th>Apertura</th><th>Cierre</th></tr></thead><tbody>';
        foreach($calls as $call)echo '<tr><td><strong>'.L::e($call['title']).'</strong><small class="block">'.L::e($call['career']).'</small></td><td>'.L::e($call['opens_at']).'</td><td>'.L::e($call['closes_at']).'</td></tr>';
        echo '</tbody></table></div></section></div>';
        echo '<div class="split-grid"><section class="card"><h3>Versión de reglamento por carrera</h3><p class="muted">Texto de referencia institucional proporcionado por el equipo; no validar normativa sin aprobación formal.</p><form method="post" action="'.Http::url('admin/reglamentos').'">'.L::csrf().'<label>Carrera<select name="career_id" required>'.L::option($careers).'</select></label><label>Identificador de versión<input name="version_code" maxlength="35" required placeholder="2026-II"></label><label>Título<input name="title" maxlength="160" required></label><label>Fecha de vigencia<input name="effective_at" type="date" required></label><label>Condiciones y observaciones<textarea name="regulation_text" maxlength="10000" required rows="5" placeholder="Anotar el texto de laboratorio o el reglamento verificado"></textarea></label><button type="submit" class="button">Registrar versión</button></form></section><section class="card"><h3>Versiones registradas</h3>';
        foreach($regulations as $r)echo '<div class="metric-line"><div><strong>'.L::e($r['title']).'</strong><small class="block">'.L::e($r['career']).' · v'.L::e($r['version_code']).' · '.L::e($r['effective_at']).'</small></div></div>';
        if(!$regulations)echo '<p class="muted">No existen versiones de reglamento configuradas.</p>';
        echo '</section></div>';L::page('Convocatorias y modalidades',ob_get_clean(),'Habilita las modalidades académicas y establece sus plazos.');
    }
    public static function createCall():void {
        Auth::require('director','admin');$title=Http::input('title',160);$career=Http::positiveInt('career_id');
        if(!Auth::managesCareer($career))throw new \DomainException('Carrera no autorizada');
        $start=Http::dateTime('opens_at');$end=Http::dateTime('closes_at');$days=Http::positiveInt('progress_days');
        $mods=$_POST['modalities']??[];if(!is_array($mods)||!$mods||$days>1460||$start>=$end)throw new \DomainException('Convocatoria o modalidades inválidas');
        Db::transaction(function()use($title,$career,$start,$end,$days,$mods){
            Db::run('INSERT INTO calls(career_id,title,opens_at,closes_at,progress_days,created_by) VALUES(?,?,?,?,?,?)',[$career,$title,$start,$end,$days,Auth::id()]);$id=Db::id();
            foreach(array_unique($mods) as $mod){$mid=filter_var($mod,FILTER_VALIDATE_INT);if(!$mid||!Db::one('SELECT 1 FROM career_modalities WHERE career_id=? AND modality_id=?',[$career,$mid]))throw new \DomainException('Modalidad incompatible');Db::run('INSERT INTO call_modalities(call_id,modality_id) VALUES(?,?)',[$id,$mid]);}
            Security::audit(Auth::id(),'call.created','calls',$id);
        });Http::flash('success','Convocatoria registrada');Http::redirect('admin/convocatorias');
    }
    public static function createRegulation():void {
        Auth::require('director','admin');$career=Http::positiveInt('career_id');
        if(!Auth::managesCareer($career))throw new \DomainException('Carrera no asignada a tu cuenta');
        $version=Http::input('version_code',35);$title=Http::input('title',160);$content=Http::input('regulation_text',10000);$date=Http::input('effective_at',10);
        $parsed=\DateTimeImmutable::createFromFormat('!Y-m-d',$date);
        if(!$parsed||$parsed->format('Y-m-d')!==$date||strlen($content)<15||strlen($version)<2)throw new \DomainException('Reglamento o fecha inválidos');
        Db::run('INSERT INTO career_regulations(career_id,version_code,title,regulation_text,effective_at,created_by) VALUES(?,?,?,?,?,?)',[$career,$version,$title,$content,$date,Auth::id()]);
        Security::audit(Auth::id(),'regulation.created','career_regulations',Db::id());
        Http::flash('success','Versión de reglamento registrada');Http::redirect('admin/convocatorias');
    }
    public static function reports():void {
        Auth::require('director','secretaria','admin');
        if(Auth::has('admin')){
            $stats=Db::all('SELECT status,COUNT(*) n FROM applications GROUP BY status ORDER BY n DESC');
            $mods=Db::all('SELECT m.name,COUNT(a.id) n FROM modalities m LEFT JOIN applications a ON a.modality_id=m.id GROUP BY m.id,m.name ORDER BY n DESC');
            $load=Db::all("SELECT u.full_name,COUNT(ass.id) n FROM teachers t JOIN users u ON u.id=t.user_id LEFT JOIN assignments ass ON ass.teacher_id=t.user_id AND ass.ended_at IS NULL GROUP BY t.user_id,u.full_name ORDER BY n DESC LIMIT 12");
            $audit=Db::all('SELECT a.event_code,a.entity_type,a.entity_id,a.created_at,u.full_name actor FROM audit_logs a LEFT JOIN users u ON u.id=a.actor_user_id ORDER BY a.id DESC LIMIT 30');
        } else {
            $uid=Auth::id();
            $stats=Db::all('SELECT a.status,COUNT(*) n FROM applications a JOIN students st ON st.user_id=a.student_id JOIN staff_careers sc ON sc.career_id=st.career_id WHERE sc.user_id=? GROUP BY a.status ORDER BY n DESC',[$uid]);
            $mods=Db::all('SELECT m.name,COUNT(a.id) n FROM modalities m JOIN applications a ON a.modality_id=m.id JOIN students st ON st.user_id=a.student_id JOIN staff_careers sc ON sc.career_id=st.career_id WHERE sc.user_id=? GROUP BY m.id,m.name ORDER BY n DESC',[$uid]);
            $load=Db::all("SELECT u.full_name,COUNT(ass.id) n FROM assignments ass JOIN users u ON u.id=ass.teacher_id JOIN applications a ON a.id=ass.application_id JOIN students st ON st.user_id=a.student_id JOIN staff_careers sc ON sc.career_id=st.career_id WHERE sc.user_id=? AND ass.ended_at IS NULL GROUP BY u.id,u.full_name ORDER BY n DESC LIMIT 12",[$uid]);
            $audit=[]; // Auditoría global restringida al administrador técnico.
        }
        ob_start();echo '<div class="three-grid"><section class="card"><h3>Por estado</h3>';foreach($stats as $r)echo '<div class="metric-line">'.L::badge($r['status']).'<strong>'.L::e($r['n']).'</strong></div>';echo '</section><section class="card"><h3>Por modalidad</h3>';foreach($mods as $r)echo '<div class="metric-line"><span>'.L::e($r['name']).'</span><strong>'.L::e($r['n']).'</strong></div>';echo '</section><section class="card"><h3>Carga docente</h3>';foreach($load as $r)echo '<div class="metric-line"><span>'.L::e($r['full_name']).'</span><strong>'.L::e($r['n']).'</strong></div>';echo '</section></div><section class="card"><h3>Bitácora de auditoría</h3><div class="table-wrap"><table><thead><tr><th>Operación</th><th>Entidad</th><th>Usuario</th><th>Fecha</th></tr></thead><tbody>';foreach($audit as $r)echo '<tr><td>'.L::e($r['event_code']).'</td><td>'.L::e($r['entity_type'].' #'.$r['entity_id']).'</td><td>'.L::e($r['actor']??'Sistema').'</td><td>'.L::e($r['created_at']).'</td></tr>';echo '</tbody></table></div></section>';L::page('Indicadores y auditoría',ob_get_clean(),'Datos extraídos de la base MySQL: no se utilizan cifras inventadas.');
    }
    public static function notifications():void {
        Auth::require();$items=Db::all('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 80',[Auth::id()]);
        ob_start();echo '<section class="card"><div class="section-heading"><div><h2>Avisos académicos</h2><p>Cambios registrados en tus expedientes y asignaciones.</p></div><form method="post" action="'.Http::url('notificaciones/leer').'">'.L::csrf().'<button class="button secondary" type="submit">Marcar como leídos</button></form></div>';
        foreach($items as $n)echo '<div class="notification '.(!$n['read_at']?'unread':'').'"><span class="dot"></span><div>'.L::e($n['content']).'<small class="block">'.L::e($n['created_at']).'</small></div>'.($n['application_id']?'<a href="'.Http::url('postulaciones/'.$n['application_id']).'">Ver expediente →</a>':'').'</div>';
        if(!$items)echo '<p class="muted">Todavía no tienes notificaciones.</p>';echo '</section>';L::page('Notificaciones',ob_get_clean());
    }
    public static function readNotifications():void {Auth::require();Db::run('UPDATE notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL',[Auth::id()]);Http::redirect('notificaciones');}
    public static function profile():void {
        Auth::require();$u=Auth::user();$content='<section class="card profile-card"><div class="eyebrow">MI CUENTA</div><h2>'.L::e($u['full_name']).'</h2><p>'.L::e($u['email']).'</p><p class="muted">Roles: '.L::e(implode(', ',$u['roles'])).'</p><h3>Actualizar contraseña</h3><form method="post" action="'.Http::url('perfil/clave').'">'.L::csrf().'<label>Contraseña actual<input type="password" name="current_password" required autocomplete="current-password"></label><label>Nueva contraseña<input type="password" name="new_password" required minlength="12" autocomplete="new-password"></label><button class="button" type="submit">Cambiar contraseña</button></form></section>';L::page('Mi perfil',$content);
    }
    public static function health():void {header('Content-Type: application/json; charset=utf-8');echo json_encode(['service'=>'sigta','status'=>'running','time'=>date(DATE_ATOM)],JSON_THROW_ON_ERROR);}
}
