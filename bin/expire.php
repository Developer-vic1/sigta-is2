<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Solo CLI');}
require dirname(__DIR__).'/app/Core/Autoload.php';
use Sigta\Core\{Db,Security};
try{
    $rows=Db::all("SELECT a.id,a.student_id FROM applications a JOIN calls c ON c.id=a.call_id WHERE a.status IN('aprobada','en_desarrollo','habilitada') AND COALESCE(a.last_progress_at,a.approved_at) < DATE_SUB(NOW(), INTERVAL c.progress_days DAY)");
    $count=0;
    foreach($rows as $row){
        Db::transaction(function()use($row,&$count){
            $updated=Db::run("UPDATE applications SET status='caducada' WHERE id=? AND status IN('aprobada','en_desarrollo','habilitada')",[$row['id']]);
            if($updated){Db::run('INSERT INTO notifications(user_id,application_id,content) VALUES(?,?,?)',[$row['student_id'],$row['id'],'Perfil caducado por plazo sin avance según configuración de convocatoria']);Security::audit(null,'application.expired','applications',(int)$row['id']);$count++;}
        });
    }
    $warnings=0;
    $upcoming=Db::all("SELECT a.id,a.student_id,DATEDIFF(DATE_ADD(COALESCE(a.last_progress_at,a.approved_at),INTERVAL c.progress_days DAY),NOW()) days_left FROM applications a JOIN calls c ON c.id=a.call_id WHERE a.status IN('aprobada','en_desarrollo','habilitada') AND DATEDIFF(DATE_ADD(COALESCE(a.last_progress_at,a.approved_at),INTERVAL c.progress_days DAY),NOW()) IN (15,7,1)");
    foreach($upcoming as $item){
        $message='Plazo reglamentario: quedan '.$item['days_left'].' día(s) para registrar avance';
        $exists=Db::one('SELECT 1 FROM notifications WHERE user_id=? AND application_id=? AND content=?',[$item['student_id'],$item['id'],$message]);
        if(!$exists){Db::run('INSERT INTO notifications(user_id,application_id,content) VALUES(?,?,?)',[$item['student_id'],$item['id'],$message]);$warnings++;}
    }
    echo "Caducidades aplicadas: $count | alertas previas nuevas: $warnings\n";
}catch(Throwable $e){fwrite(STDERR,"Error: ".$e->getMessage()."\n");exit(1);}
