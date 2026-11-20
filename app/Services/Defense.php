<?php
declare(strict_types=1);
namespace Sigta\Services;
use Sigta\Core\{Db,Auth,Security};
final class Defense {
    public static function schedule(int $appId,int $roomId,string $start,string $end): void {
        $a=Academic::application($appId);Academic::requireView($a);
        if(!(int)$a['requires_defense'])throw new \DomainException('La modalidad no requiere defensa pública');
        if($a['status']!=='habilitada'||!Academic::sufficiency($appId))throw new \DomainException('Faltan dictámenes suficientes de tutor y/o tribunales');
        $unverified=Db::one("SELECT COUNT(*) AS n FROM requirement_verifications WHERE application_id=? AND status<>'aprobado'",[$appId]);
        if((int)$unverified['n']>0)throw new \DomainException('Existen requisitos documentales pendientes de re-verificación');
        if((int)$a['requires_profile']){
            $profile=Db::one("SELECT dr.decision FROM document_versions dv JOIN documents d ON d.id=dv.document_id JOIN document_types dt ON dt.id=d.document_type_id LEFT JOIN document_reviews dr ON dr.id=(SELECT MAX(r2.id) FROM document_reviews r2 WHERE r2.version_id=dv.id) WHERE d.application_id=? AND dt.code='PERFIL' ORDER BY dv.id DESC LIMIT 1",[$appId]);
            if(!$profile||$profile['decision']!=='aprobado')throw new \DomainException('La versión vigente del perfil requiere aprobación académica');
        }
        if(strtotime($start)===false||strtotime($end)===false||strtotime($start)>=strtotime($end)||strtotime($start)<time())throw new \DomainException('Las fechas de defensa deben ser futuras y ordenadas');
        if(strtotime($end)-strtotime($start)>3*3600)throw new \DomainException('Una defensa no puede durar más de tres horas');
        $final=Db::one("SELECT dv.id FROM documents d JOIN document_types dt ON dt.id=d.document_type_id JOIN document_versions dv ON dv.document_id=d.id WHERE d.application_id=? AND dt.code='FINAL' LIMIT 1",[$appId]);
        if((int)$a['requires_profile']&&!$final)throw new \DomainException('Falta el documento final de graduación');
        $conn=Db::conn();$got=Db::one("SELECT GET_LOCK('sigta:defense:scheduling',10) got");if(!(int)($got['got']??0))throw new \DomainException('Agenda ocupada. Vuelve a intentar');
        try{Db::transaction(function()use($a,$appId,$roomId,$start,$end){
            $room=Db::one('SELECT id FROM rooms WHERE id=? AND active=1',[$roomId]);if(!$room)throw new \DomainException('Sala no disponible');
            $clash=Db::one("SELECT id FROM defenses WHERE room_id=? AND status='programada' AND starts_at<? AND ends_at>?",[$roomId,$end,$start]);
            if($clash)throw new \DomainException('El aula ya está ocupada en este horario');
            $teachers=Db::all("SELECT DISTINCT a2.teacher_id FROM assignments a1 JOIN assignments a2 ON a2.teacher_id=a1.teacher_id AND a2.ended_at IS NULL JOIN defenses d ON d.application_id=a2.application_id WHERE a1.application_id=? AND a1.ended_at IS NULL AND a1.role_code IN('tutor','tribunal') AND a2.role_code IN('tutor','tribunal') AND d.status='programada' AND d.starts_at<? AND d.ends_at>?",[$appId,$end,$start]);
            if($teachers)throw new \DomainException('Hay docentes asignados a otra defensa en ese horario');
            $prior=Db::one('SELECT id,status FROM defenses WHERE application_id=?',[$appId]);
            if($prior&&$prior['status']!=='cancelada')throw new \DomainException('La postulación ya tiene defensa activa');
            if($prior)Db::run("UPDATE defenses SET room_id=?,starts_at=?,ends_at=?,status='programada',scheduled_by=?,scheduled_at=NOW() WHERE id=?",[$roomId,$start,$end,Auth::id(),$prior['id']]);
            else Db::run('INSERT INTO defenses(application_id,room_id,starts_at,ends_at,scheduled_by) VALUES(?,?,?,?,?)',[$appId,$roomId,$start,$end,Auth::id()]);
            Db::run("UPDATE applications SET status='programada' WHERE id=?",[$appId]);
            Academic::notify((int)$a['student_id'],'Defensa programada para '.$start,$appId);
            Security::audit(Auth::id(),'defense.scheduled','applications',$appId,['room'=>$roomId,'start'=>$start,'end'=>$end]);
        });}finally{Db::one("SELECT RELEASE_LOCK('sigta:defense:scheduling') released");}
    }
    public static function score(int $defenseId,int $assignmentId,float $grade,string $comments): void {
        if($grade<0||$grade>100)throw new \DomainException('Calificación entre 0 y 100');
        $row=Db::one("SELECT d.id,d.application_id,d.status,d.starts_at, a.teacher_id, a.role_code FROM defenses d JOIN assignments a ON a.application_id=d.application_id AND a.ended_at IS NULL WHERE d.id=? AND a.id=?",[$defenseId,$assignmentId]);
        if(!$row||(int)$row['teacher_id']!==Auth::id())throw new \DomainException('No eres miembro evaluador de esta defensa');
        if($row['status']!=='programada'||strtotime($row['starts_at'])>time())throw new \DomainException('No puedes registrar calificación antes de la defensa');
        if(!in_array($row['role_code'],['tutor','tribunal'],true))throw new \DomainException('Rol sin autorización para calificar');
        if(Db::one('SELECT 1 FROM defense_scores WHERE defense_id=? AND assignment_id=?',[$defenseId,$assignmentId]))throw new \DomainException('La calificación ya fue registrada y no puede modificarse sin procedimiento formal');
        Db::run('INSERT INTO defense_scores(defense_id,assignment_id,grade,comments) VALUES(?,?,?,?)',[$defenseId,$assignmentId,round($grade,2),$comments]);
        Security::audit(Auth::id(),'defense.scored','defenses',$defenseId,['assignment'=>$assignmentId]);
    }
    public static function finalize(int $defenseId): int {
        return Db::transaction(function()use($defenseId){
            $def=Db::one('SELECT d.*,a.student_id,a.modality_id,a.topic,a.status application_status,u.full_name student_name,r.name room_name FROM defenses d JOIN applications a ON a.id=d.application_id JOIN users u ON u.id=a.student_id JOIN rooms r ON r.id=d.room_id WHERE d.id=? FOR UPDATE',[$defenseId]);
            if(!$def||$def['status']!=='programada')throw new \DomainException('La defensa no está pendiente de cierre');
            Academic::requireView(Academic::application((int)$def['application_id']));
            if(strtotime($def['starts_at'])>time())throw new \DomainException('La defensa aún no ocurrió');
            $req=Db::all("SELECT ass.id,ass.role_code,ass.seat,ds.grade FROM assignments ass LEFT JOIN defense_scores ds ON ds.assignment_id=ass.id AND ds.defense_id=? WHERE ass.application_id=? AND ass.ended_at IS NULL AND ass.role_code IN('tutor','tribunal')",[$defenseId,$def['application_id']]);
            $mod=Db::one('SELECT requires_tutor,requires_tribunal FROM modalities WHERE id=?',[$def['modality_id']]);
            $expected=((int)$mod['requires_tutor'])+(((int)$mod['requires_tribunal'])*2);
            if(count($req)!==$expected||array_filter($req,fn($r)=>$r['grade']===null))throw new \DomainException('Faltan calificaciones de los miembros del tribunal/tutor');
            $weightRows=Db::all('SELECT assignment_role,weight_percent FROM modality_grade_weights WHERE modality_id=?',[$def['modality_id']]);
            $weighted=0;$weightSum=0;
            foreach($weightRows as $w){
                $grades=array_values(array_map(fn($r)=>(float)$r['grade'],array_filter($req,fn($r)=>$r['role_code']===$w['assignment_role'])));
                if(!$grades)throw new \DomainException('No hay notas del rol: '.$w['assignment_role']);
                $weighted+=(array_sum($grades)/count($grades))*(float)$w['weight_percent']/100;$weightSum+=(float)$w['weight_percent'];
            }
            if(abs($weightSum-100)>0.001)throw new \DomainException('Los pesos finales no suman 100%');
            $grade=round($weighted,2);
            $content=Pdf::make('SIGTA - ACTA DE DEFENSA (PROTOTIPO ACADEMICO)',[
                'Numero de defensa: '.$defenseId,'Postulante: '.$def['student_name'],
                'Tema: '.$def['topic'],'Sala: '.$def['room_name'],
                'Fecha y hora: '.$def['starts_at'],
                'Calificacion ponderada final: '.number_format($grade,2).' / 100',
                'Este documento es una simulacion academica; NO es un acta institucional oficial.',
                'Emision: '.date('Y-m-d H:i:s'),'Firmas institucionales: PENDIENTES DE VALIDACION EXTERNA'
            ]);
            $key=bin2hex(random_bytes(24)).'.pdf';$path=dirname(__DIR__,2).'/storage/private/acts/'.$key;
            if(file_put_contents($path,$content,LOCK_EX)===false)throw new \RuntimeException('No se pudo generar el acta');
            try{
                Db::run('INSERT INTO acts(defense_id,final_grade,storage_key,sha256,issued_by) VALUES(?,?,?,?,?)',[$defenseId,$grade,$key,hash('sha256',$content),Auth::id()]);$id=Db::id();
                Db::run("UPDATE defenses SET status='finalizada' WHERE id=?",[$defenseId]);
                Db::run("UPDATE applications SET status='concluida' WHERE id=?",[$def['application_id']]);
                Academic::notify((int)$def['student_id'],'Defensa concluida; acta académica de prueba disponible',(int)$def['application_id']);
                Security::audit(Auth::id(),'act.generated','acts',$id,['grade'=>$grade]);
                return $id;
            }catch(\Throwable $e){@unlink($path);throw $e;}
        });
    }
    public static function downloadAct(int $id): never {
        $act=Db::one('SELECT ac.*,d.application_id FROM acts ac JOIN defenses d ON d.id=ac.defense_id WHERE ac.id=?',[$id]);
        if(!$act)throw new \DomainException('Acta inexistente');Academic::requireView(Academic::application((int)$act['application_id']));
        $file=dirname(__DIR__,2).'/storage/private/acts/'.$act['storage_key'];if(!is_file($file))throw new \DomainException('Acta no disponible');
        header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="SIGTA-Acta-'.$id.'.pdf"');header('Content-Length: '.filesize($file));readfile($file);exit;
    }
}
