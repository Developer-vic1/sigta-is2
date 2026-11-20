<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Solo CLI');}
require dirname(__DIR__).'/app/Core/Autoload.php';
use Sigta\Core\{Db,Security};
if(!extension_loaded('pdo_mysql')){fwrite(STDERR,"pdo_mysql no disponible\n");exit(1);}
// Solo en entorno local. Credenciales aleatorias mostradas una sola vez.
if(\Sigta\Core\Env::get('APP_ENV','production')!=='local'){fwrite(STDERR,"Este script está bloqueado fuera de APP_ENV=local.\n");exit(1);}
try {
    $career=Db::one("SELECT id FROM careers WHERE code='SIS'");
    if(!$career)throw new RuntimeException('Primero ejecuta bin/setup.php');
    $people=[
        ['Director Demo','direccion@demo.invalid','director',null],
        ['Secretaría Demo','secretaria@demo.invalid','secretaria',null],
        ['Estudiante Demo','estudiante@demo.invalid','estudiante','SIS-DEMO-001'],
        ['Docente Tutor Demo','tutor@demo.invalid','tutor','DOC-DEMO-001'],
        ['Tribunal Uno Demo','tribunal1@demo.invalid','tribunal','DOC-DEMO-002'],
        ['Tribunal Dos Demo','tribunal2@demo.invalid','tribunal','DOC-DEMO-003'],
    ];
    $creator=Db::one("SELECT u.id FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id WHERE r.code='admin' ORDER BY u.id LIMIT 1");
    if(!$creator)throw new RuntimeException('No existe administrador');
    foreach($people as [$name,$email,$role,$code]){
        if(Db::one('SELECT id FROM users WHERE email=?',[$email])){echo "Ya existe: $email (se conserva su contraseña)\n";continue;}
        $pass='Sg!'.bin2hex(random_bytes(10)).'Aa9';
        Db::transaction(function()use($name,$email,$role,$code,$pass,$career){
            Db::run('INSERT INTO users(full_name,email,password_hash) VALUES(?,?,?)',[$name,$email,Security::passwordHash($pass)]);$id=Db::id();
            $r=Db::one('SELECT id FROM roles WHERE code=?',[$role]);
            Db::run('INSERT INTO user_roles(user_id,role_id) VALUES(?,?)',[$id,$r['id']]);
            if($role==='estudiante')Db::run('INSERT INTO students(user_id,career_id,enrollment_code) VALUES(?,?,?)',[$id,$career['id'],$code]);
            if(in_array($role,['director','secretaria'],true))Db::run('INSERT INTO staff_careers(user_id,career_id) VALUES(?,?)',[$id,$career['id']]);
            if(in_array($role,['tutor','tribunal'],true)){
                Db::run('INSERT INTO teachers(user_id,employee_code) VALUES(?,?)',[$id,$code]);
                if($role==='tribunal'){
                    $other=Db::one("SELECT id FROM roles WHERE code='tutor'");Db::run('INSERT INTO user_roles(user_id,role_id) VALUES(?,?)',[$id,$other['id']]);
                }
            }
        });
        echo sprintf("%-27s : %-31s | Clave: %s\n",$name,$email,$pass);
    }
    $call=Db::one("SELECT id FROM calls WHERE title='Convocatoria demostrativa 2026' LIMIT 1");
    if(!$call){
        Db::run("INSERT INTO calls(career_id,title,opens_at,closes_at,progress_days,created_by) VALUES(?,'Convocatoria demostrativa 2026',DATE_SUB(NOW(),INTERVAL 1 DAY),DATE_ADD(NOW(),INTERVAL 45 DAY),180,?)",[$career['id'],$creator['id']]);
        $callId=Db::id();Db::run('INSERT INTO call_modalities(call_id,modality_id) SELECT ?,id FROM modalities',[$callId]);
    }
    echo "Convocatoria de prueba disponible. Estos usuarios son ficticios: no los uses en producción.\n";
}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
