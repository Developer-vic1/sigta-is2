<?php
declare(strict_types=1);
namespace Sigta\Controllers;
use Sigta\Core\{Auth,Db,Http,Security};
use Sigta\Services\Academic;
final class AcademicController {
    private static function back(int $id):never{Http::flash('success','Operación registrada correctamente');Http::redirect('postulaciones/'.$id);}
    public static function create():void {
        Auth::require('estudiante');$call=Http::positiveInt('call_id');$modality=Http::positiveInt('modality_id');
        $topic=Http::input('topic',220);$summary=Http::input('abstract',2000);
        $id=Academic::create(Auth::id(),$call,$modality,$topic,$summary);
        $matches=Academic::similarity($topic,$id);
        Http::flash('success','Postulación creada. '.($matches?'Se detectaron temas potencialmente similares: revisión humana recomendada.':'No hay coincidencias altas en los títulos registrados.'));
        Http::redirect('postulaciones/'.$id);
    }
    public static function submit(int $id):void {Auth::require('estudiante');Academic::submit($id);self::back($id);}
    public static function review(int $id):void {Auth::require('director','admin');Academic::review($id,Http::input('decision',20),Http::input('note',1000));self::back($id);}
    public static function assign(int $id):void {
        Auth::require('director','admin');Academic::assign($id,Http::positiveInt('teacher_id'),Http::input('role_code',12),Http::positiveInt('seat'));self::back($id);
    }
    public static function assignAuto(int $id):void {
        Auth::require('director','admin');$specialty=isset($_POST['specialty_id'])&&$_POST['specialty_id']!==''?Http::positiveInt('specialty_id'):null;
        $chosen=Academic::assignBalanced($id,Http::input('role_code',12),Http::positiveInt('seat'),$specialty);
        Http::flash('success','Docente asignado automáticamente (ID '.$chosen.'). Revisa la designación.');Http::redirect('postulaciones/'.$id);
    }
    public static function requirement(int $id):void {
        Auth::require('secretaria','director','admin');$a=Academic::application($id);Academic::requireView($a);$requirement=Http::positiveInt('requirement_id');
        $status=Http::input('status',16);$notes=Http::input('notes',300);
        if(!in_array($status,['aprobado','rechazado','pendiente'],true))throw new \DomainException('Estado inválido');
        if(!Db::one('SELECT 1 FROM requirement_verifications WHERE application_id=? AND requirement_id=?',[$id,$requirement]))throw new \DomainException('Requisito no asociado a la postulación');
        if($status==='aprobado'){
            $document=Db::one('SELECT dv.id FROM documents d JOIN document_types dt ON dt.id=d.document_type_id JOIN requirements r ON r.code=dt.code JOIN document_versions dv ON dv.document_id=d.id WHERE d.application_id=? AND r.id=? LIMIT 1',[$id,$requirement]);
            if(!$document)throw new \DomainException('No puede aprobarse un requisito sin su documento adjunto');
        }
        Db::run('UPDATE requirement_verifications SET status=?,notes=?,verified_by=?,verified_at=NOW() WHERE application_id=? AND requirement_id=?',[$status,$notes,Auth::id(),$id,$requirement]);
        Security::audit(Auth::id(),'requirement.verified','applications',$id,['requirement'=>$requirement,'status'=>$status]);self::back($id);
    }
    public static function milestone(int $id):void {
        Auth::require('tutor','director','admin');$a=Academic::application($id);Academic::requireView($a);
        if(!Auth::has('director','admin')&&!Db::one("SELECT id FROM assignments WHERE application_id=? AND teacher_id=? AND role_code='tutor' AND ended_at IS NULL",[$id,Auth::id()]))throw new \DomainException('Solo el tutor asignado puede crear hitos');
        if(!in_array($a['status'],['aprobada','en_desarrollo','habilitada'],true))throw new \DomainException('El expediente no admite nuevos hitos');
        $title=Http::input('title',140);$date=Http::dateTime('due_at');
        if(strlen($title)<4||strtotime($date)<time())throw new \DomainException('Hito o vencimiento inválido');
        $next=Db::one('SELECT COALESCE(MAX(sequence_no),0)+1 n FROM milestones WHERE application_id=?',[$id]);
        Db::run('INSERT INTO milestones(application_id,title,sequence_no,due_at,created_by) VALUES(?,?,?,?,?)',[$id,$title,$next['n'],$date,Auth::id()]);
        Academic::notify((int)$a['student_id'],'Nuevo hito académico: '.$title,$id);Security::audit(Auth::id(),'milestone.created','applications',$id);self::back($id);
    }
    public static function evaluate(int $assignmentId):void {
        Auth::require('tutor','tribunal');$ass=Db::one('SELECT application_id FROM assignments WHERE id=?',[$assignmentId]);if(!$ass)throw new \DomainException('Asignación no encontrada');
        $scores=$_POST['scores']??[];if(!is_array($scores))throw new \DomainException('Rúbrica inválida');
        Academic::evaluate($assignmentId,($_POST['sufficient']??'0')==='1',Http::input('comments',1000),$scores);
        self::back((int)$ass['application_id']);
    }
}
