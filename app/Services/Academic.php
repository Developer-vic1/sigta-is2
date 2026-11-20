<?php
declare(strict_types=1);
namespace Sigta\Services;
use Sigta\Core\{Db,Auth,Security};
final class Academic {
    public static function application(int $id): array {
        $a=Db::one('SELECT a.*,u.full_name AS student_name,u.email AS student_email,s.career_id,c.name AS career_name,m.name AS modality_name,m.requires_profile,m.requires_tutor,m.requires_tribunal,m.requires_defense,ca.title AS call_title,ca.progress_days FROM applications a JOIN students s ON s.user_id=a.student_id JOIN users u ON u.id=a.student_id JOIN careers c ON c.id=s.career_id JOIN modalities m ON m.id=a.modality_id JOIN calls ca ON ca.id=a.call_id WHERE a.id=?',[$id]);
        if(!$a)throw new \DomainException('Postulación no encontrada');return $a;
    }
    public static function canView(array $a): bool {
        if(Auth::managesCareer((int)$a['career_id']))return true;
        if(Auth::id()===(int)$a['student_id'])return true;
        return (bool)Db::one('SELECT id FROM assignments WHERE application_id=? AND teacher_id=? AND ended_at IS NULL',[$a['id'],Auth::id()]);
    }
    public static function requireView(array $a): void {if(!self::canView($a))throw new \DomainException('No tienes acceso al expediente');}
    public static function notify(int $userId,string $message,int $appId): void {Db::run('INSERT INTO notifications(user_id,application_id,content) VALUES(?,?,?)',[$userId,$appId,$message]);}
    public static function create(int $studentId,int $callId,int $modalityId,string $topic,string $abstract): int {
        if(mb_strlen_safe($topic)<8)throw new \DomainException('El título del tema debe tener al menos 8 caracteres');
        return Db::transaction(function()use($studentId,$callId,$modalityId,$topic,$abstract){
            $check=Db::one('SELECT s.career_id,ca.id,ca.closes_at,ca.opens_at,ca.active FROM students s JOIN calls ca ON ca.career_id=s.career_id JOIN call_modalities cm ON cm.call_id=ca.id AND cm.modality_id=? JOIN career_modalities cmm ON cmm.career_id=s.career_id AND cmm.modality_id=? WHERE s.user_id=? AND ca.id=?',[$modalityId,$modalityId,$studentId,$callId]);
            if(!$check||!$check['active']||$check['opens_at']>date('Y-m-d H:i:s')||$check['closes_at']<date('Y-m-d H:i:s'))throw new \DomainException('Modalidad o convocatoria no vigente para tu carrera');
            $same=Db::one('SELECT id FROM applications WHERE student_id=? AND call_id=?',[$studentId,$callId]);
            if($same)throw new \DomainException('Ya tienes una postulación en esta convocatoria');
            Db::run('INSERT INTO applications(student_id,call_id,modality_id,topic,abstract_text) VALUES(?,?,?,?,?)',[$studentId,$callId,$modalityId,$topic,$abstract]);$id=Db::id();
            Db::run('INSERT INTO requirement_verifications(application_id,requirement_id) SELECT ?,requirement_id FROM modality_requirements WHERE modality_id=?',[$id,$modalityId]);
            Security::audit($studentId,'application.created','applications',$id);return $id;
        });
    }
    public static function submit(int $id): void {
        $a=self::application($id);if((int)$a['student_id']!==Auth::id())throw new \DomainException('Solo el estudiante puede enviar la postulación');
        if(!in_array($a['status'],['borrador','observada'],true))throw new \DomainException('El estado actual no permite enviar');
        $missing=Db::all('SELECT r.code FROM modality_requirements mr JOIN requirements r ON r.id=mr.requirement_id WHERE mr.modality_id=? AND NOT EXISTS (SELECT 1 FROM documents d JOIN document_types dt ON dt.id=d.document_type_id JOIN document_versions dv ON dv.document_id=d.id WHERE d.application_id=? AND dt.code=r.code)',[$a['modality_id'],$id]);
        if($missing)throw new \DomainException('Adjunta los documentos: '.implode(', ',array_column($missing,'code')));
        if((int)$a['requires_profile']===1){
            $profile=Db::one("SELECT dv.id FROM documents d JOIN document_types t ON d.document_type_id=t.id JOIN document_versions dv ON dv.document_id=d.id WHERE d.application_id=? AND t.code='PERFIL' ORDER BY dv.id DESC LIMIT 1",[$id]);
            if(!$profile)throw new \DomainException('Debes adjuntar tu perfil de proyecto');
        }
        Db::run("UPDATE applications SET status='presentada',submitted_at=COALESCE(submitted_at,NOW()),last_progress_at=NOW() WHERE id=?",[$id]);
        Security::audit(Auth::id(),'application.submitted','applications',$id);
    }
    public static function review(int $id,string $decision,string $note): void {
        if(!in_array($decision,['observada','aprobada','rechazada'],true))throw new \DomainException('Decisión no permitida');
        if($decision!=='aprobada'&&mb_strlen_safe($note)<8)throw new \DomainException('Debes explicar la observación o rechazo (mínimo 8 caracteres)');
        Db::transaction(function()use($id,$decision,$note){
            $a=self::application($id);self::requireView($a);
            if(!in_array($a['status'],['presentada','observada'],true))throw new \DomainException('La postulación no se encuentra en revisión');
            if($decision==='aprobada'){
                $missing=Db::one("SELECT COUNT(*) n FROM requirement_verifications WHERE application_id=? AND status<>'aprobado'",[$id]);
                if((int)$missing['n']>0)throw new \DomainException('Existen requisitos sin verificar');
                if((int)$a['requires_profile']){
                    $approved=Db::one("SELECT dr.decision FROM document_versions dv JOIN documents d ON d.id=dv.document_id JOIN document_types dt ON dt.id=d.document_type_id LEFT JOIN document_reviews dr ON dr.id=(SELECT MAX(r2.id) FROM document_reviews r2 WHERE r2.version_id=dv.id) WHERE d.application_id=? AND dt.code='PERFIL' ORDER BY dv.id DESC LIMIT 1",[$id]);
                    if(!$approved||$approved['decision']!=='aprobado')throw new \DomainException('El perfil necesita revisión aprobada de su versión vigente');
                }
            }
            Db::run("UPDATE applications SET status=?,decision_note=?,decided_by=?,approved_at=IF(?='aprobada',NOW(),approved_at),last_progress_at=NOW() WHERE id=?",[$decision,$note,Auth::id(),$decision,$id]);
            self::notify((int)$a['student_id'],'La postulación cambió a: '.$decision,$id);
            Security::audit(Auth::id(),'application.review','applications',$id,['decision'=>$decision]);
        });
    }
    public static function assign(int $id,int $teacherId,string $role,int $seat): void {
        if(!in_array($role,['tutor','tribunal','revisor'],true)||$seat<1||$seat>3)throw new \DomainException('Asignación inválida');
        if($role==='tutor'&&$seat!==1)throw new \DomainException('Tutor debe ocupar la plaza 1');
        if($role==='tribunal'&&$seat>2)throw new \DomainException('El prototipo contempla dos tribunales');
        Db::transaction(function()use($id,$teacherId,$role,$seat){
            $a=self::application($id);self::requireView($a);
            if($role==='revisor'){
                if(!in_array($a['status'],['presentada','observada'],true))throw new \DomainException('El revisor de perfil se asigna durante la revisión');
            }elseif(!in_array($a['status'],['aprobada','en_desarrollo','habilitada'],true))throw new \DomainException('Se requiere postulación aprobada para designar tutor/tribunal');
            if(($role==='tutor'&&!(int)$a['requires_tutor'])||($role==='tribunal'&&!(int)$a['requires_tribunal']))throw new \DomainException('La modalidad no requiere esa designación');
            if(!Db::one('SELECT t.user_id FROM teachers t JOIN users u ON u.id=t.user_id AND u.active=1 WHERE t.user_id=?',[$teacherId]))throw new \DomainException('El usuario no es un docente habilitado');
            $prior=Db::one('SELECT id FROM assignments WHERE application_id=? AND role_code=? AND seat=? AND ended_at IS NULL',[$id,$role,$seat]);
            if($prior)throw new \DomainException('Esa plaza está ocupada. Finaliza la asignación anterior antes de reemplazarla');
            $dup=Db::one('SELECT id FROM assignments WHERE application_id=? AND teacher_id=? AND ended_at IS NULL',[$id,$teacherId]);
            if($dup)throw new \DomainException('Un docente no puede ocupar dos roles en una misma postulación');
            Db::run('INSERT INTO assignments(application_id,teacher_id,role_code,seat,assigned_by) VALUES(?,?,?,?,?)',[$id,$teacherId,$role,$seat,Auth::id()]);
            if($role!=='revisor'&&$a['status']==='aprobada')Db::run("UPDATE applications SET status='en_desarrollo' WHERE id=?",[$id]);
            self::notify($teacherId,'Tienes una asignación académica como '.$role,$id);
            Security::audit(Auth::id(),'assignment.created','applications',$id,['teacher'=>$teacherId,'role'=>$role,'seat'=>$seat]);
        });
    }
    public static function assignBalanced(int $id,string $role,int $seat,?int $specialtyId=null): int {
        $rows=Db::all('SELECT t.user_id,COALESCE(active_load.cnt,0) load_count,IF(ts.teacher_id IS NULL,1,0) specialty_penalty FROM teachers t JOIN users u ON u.id=t.user_id AND u.active=1 LEFT JOIN (SELECT teacher_id,COUNT(*) cnt FROM assignments WHERE ended_at IS NULL GROUP BY teacher_id) active_load ON active_load.teacher_id=t.user_id LEFT JOIN teacher_specialties ts ON ts.teacher_id=t.user_id AND ts.specialty_id=? WHERE NOT EXISTS (SELECT 1 FROM assignments a WHERE a.application_id=? AND a.teacher_id=t.user_id AND a.ended_at IS NULL) ORDER BY specialty_penalty,load_count,t.user_id LIMIT 1',[$specialtyId??0,$id]);
        if(!$rows)throw new \DomainException('No hay docentes disponibles');
        $teacher=(int)$rows[0]['user_id'];self::assign($id,$teacher,$role,$seat);return $teacher;
    }
    public static function canTeacher(int $appId): bool {return (bool)Db::one('SELECT id FROM assignments WHERE application_id=? AND teacher_id=? AND ended_at IS NULL',[$appId,Auth::id()]);}
    public static function sufficiency(int $appId): bool {
        $a=self::application($appId);
        $required=[];
        if((int)$a['requires_tutor'])$required[]=['tutor',1];
        if((int)$a['requires_tribunal']){$required[]=['tribunal',1];$required[]=['tribunal',2];}
        foreach($required as [$role,$seat]){
            $row=Db::one('SELECT e.sufficient FROM assignments ass LEFT JOIN evaluations e ON e.assignment_id=ass.id WHERE ass.application_id=? AND ass.role_code=? AND ass.seat=? AND ass.ended_at IS NULL',[$appId,$role,$seat]);
            if(!$row||$row['sufficient']===null||!(int)$row['sufficient'])return false;
        }
        return $required!==[];
    }
    public static function evaluate(int $assignmentId,bool $sufficient,string $comments,array $scores): void {
        Db::transaction(function()use($assignmentId,$sufficient,$comments,$scores){
            $ass=Db::one('SELECT ass.*,a.modality_id,a.status,a.student_id,r.id rubric_id FROM assignments ass JOIN applications a ON a.id=ass.application_id JOIN rubrics r ON r.modality_id=a.modality_id AND r.active=1 WHERE ass.id=? AND ass.ended_at IS NULL ORDER BY r.id DESC LIMIT 1',[$assignmentId]);
            if(!$ass|| (int)$ass['teacher_id']!==Auth::id())throw new \DomainException('La asignación no pertenece a tu cuenta');
            if(!in_array($ass['status'],['en_desarrollo','habilitada'],true))throw new \DomainException('Este expediente no admite nuevas evaluaciones');
            if(strlen(trim($comments))<8)throw new \DomainException('Ingresa una observación de al menos 8 caracteres');
            $criteria=Db::all('SELECT id,weight_percent FROM rubric_criteria WHERE rubric_id=?',[$ass['rubric_id']]);
            if(!$criteria)throw new \DomainException('No hay rúbrica configurada');
            $total=array_sum(array_map(fn($r)=>(float)$r['weight_percent'],$criteria));
            if(abs($total-100.0)>0.001)throw new \DomainException('Los pesos de la rúbrica deben sumar 100%');
            foreach($criteria as $c){$v=$scores[(int)$c['id']]??null;if(!is_numeric($v)||(float)$v<0||(float)$v>100)throw new \DomainException('Cada criterio requiere una nota entre 0 y 100');}
            Db::run('INSERT INTO evaluations(assignment_id,rubric_id,sufficient,comments) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE sufficient=VALUES(sufficient),comments=VALUES(comments),evaluated_at=NOW()',[$assignmentId,$ass['rubric_id'],$sufficient?1:0,$comments]);
            $e=Db::one('SELECT id FROM evaluations WHERE assignment_id=?',[$assignmentId]);Db::run('DELETE FROM evaluation_scores WHERE evaluation_id=?',[$e['id']]);
            foreach($criteria as $c)Db::run('INSERT INTO evaluation_scores(evaluation_id,criterion_id,score) VALUES(?,?,?)',[$e['id'],$c['id'],$scores[(int)$c['id']]]);
            if(self::sufficiency((int)$ass['application_id'])) Db::run("UPDATE applications SET status='habilitada',last_progress_at=NOW() WHERE id=?",[$ass['application_id']]);
            else Db::run("UPDATE applications SET status='en_desarrollo' WHERE id=?",[$ass['application_id']]);
            self::notify((int)$ass['student_id'],'Se registró una evaluación académica',$ass['application_id']);
            Security::audit(Auth::id(),'evaluation.saved','assignments',$assignmentId,['sufficient'=>$sufficient]);
        });
    }
    public static function similarity(string $topic,int $self=0): array {
        $list=Db::all('SELECT id,topic FROM applications WHERE id<>? ORDER BY id DESC LIMIT 500',[$self]);$output=[];
        $normalize=static function(string $s):string {$s=strtolower(trim($s));$s=preg_replace('/[^\p{L}\p{N}]+/u',' ', $s)??$s;return preg_replace('/\s+/u',' ', $s)??$s;};
        $norm=$normalize($topic);
        foreach($list as $row){similar_text($norm,$normalize($row['topic']),$pct);if($pct>=65)$output[]=['id'=>$row['id'],'topic'=>$row['topic'],'percent'=>round($pct,1)];}
        usort($output,fn($a,$b)=>$b['percent']<=>$a['percent']);return array_slice($output,0,5);
    }
}
function mb_strlen_safe(string $s):int {return count(preg_split('//u',$s,-1,PREG_SPLIT_NO_EMPTY));}
