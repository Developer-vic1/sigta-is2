<?php
declare(strict_types=1);
namespace Sigta\Services;
use Sigta\Core\{Db,Auth,Env,Security};
final class Documents {
    private static function dir(): string {return dirname(__DIR__,2).'/storage/private/documents';}
    public static function upload(int $appId,int $typeId,?int $milestoneId,array $file): void {
        $a=Academic::application($appId);Academic::requireView($a);
        if(!Auth::has('estudiante')||Auth::id()!==(int)$a['student_id'])throw new \DomainException('Solo el estudiante titular puede adjuntar documentos');
        if(!in_array($a['status'],['borrador','observada','presentada','aprobada','en_desarrollo','habilitada'],true))throw new \DomainException('La postulación está cerrada para cargas');
        if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||!is_uploaded_file($file['tmp_name']??''))throw new \DomainException('Archivo no recibido o excede el tamaño permitido');
        $max=(int)Env::get('UPLOAD_MAX_MB','8')*1024*1024;$size=(int)$file['size'];
        if($size<1||$size>$max)throw new \DomainException('El tamaño máximo permitido es '.Env::get('UPLOAD_MAX_MB','8').' MB');
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if(!in_array($mime,['application/pdf','image/jpeg','image/png'],true))throw new \DomainException('Solo PDF, JPG o PNG están permitidos');
        $type=Db::one('SELECT code FROM document_types WHERE id=?',[$typeId]);if(!$type)throw new \DomainException('Tipo de documento inválido');
        if($milestoneId!==null&&!Db::one('SELECT id FROM milestones WHERE id=? AND application_id=?',[$milestoneId,$appId]))throw new \DomainException('Hito no pertenece al expediente');
        if($type['code']==='AVANCE'&&$milestoneId===null)throw new \DomainException('Selecciona el hito del avance');
        if($type['code']!=='AVANCE'&&$milestoneId!==null)throw new \DomainException('Tipo no compatible con hito');
        $name=basename(str_replace('\\','/',(string)$file['name']));$name=substr(preg_replace('/[^\p{L}\p{N}._-]+/u','_', $name)?:'archivo',0,175);
        $key=bin2hex(random_bytes(24));$dest=self::dir().'/'.$key;
        if(!move_uploaded_file($file['tmp_name'],$dest))throw new \RuntimeException('No se pudo almacenar el archivo');
        @chmod($dest,0600);
        $sha=hash_file('sha256',$dest);
        try{Db::transaction(function()use($appId,$typeId,$milestoneId,$name,$key,$mime,$size,$sha){
            Db::one('SELECT id FROM applications WHERE id=? FOR UPDATE',[$appId]);
            $doc=Db::one('SELECT id FROM documents WHERE application_id=? AND document_type_id=? AND milestone_id <=> ? LIMIT 1',[$appId,$typeId,$milestoneId]);
            if(!$doc){Db::run('INSERT INTO documents(application_id,document_type_id,milestone_id,created_by) VALUES(?,?,?,?)',[$appId,$typeId,$milestoneId,Auth::id()]);$docId=Db::id();}
            else $docId=(int)$doc['id'];
            $version=Db::one('SELECT COALESCE(MAX(version_number),0)+1 AS number FROM document_versions WHERE document_id=?',[$docId]);
            Db::run('INSERT INTO document_versions(document_id,version_number,storage_key,original_filename,mime_type,file_size,sha256,uploaded_by) VALUES(?,?,?,?,?,?,?,?)',[$docId,$version['number'],$key,$name,$mime,$size,$sha,Auth::id()]);$vid=Db::id();
            if($milestoneId!==null){Db::run('INSERT INTO milestone_submissions(milestone_id,version_id,submitted_by) VALUES(?,?,?)',[$milestoneId,$vid,Auth::id()]);Db::run("UPDATE milestones SET status='entregado' WHERE id=?",[$milestoneId]);}
            $uploadedType=Db::one('SELECT code FROM document_types WHERE id=?',[$typeId]);
            $req=Db::one('SELECT id FROM requirements WHERE code=?',[$uploadedType['code']]);
            if($req)Db::run("UPDATE requirement_verifications SET status='pendiente',notes='Documento reemplazado: requiere nueva verificación',verified_at=NULL,verified_by=NULL WHERE application_id=? AND requirement_id=?",[$appId,$req['id']]);
            Db::run('UPDATE applications SET last_progress_at=NOW() WHERE id=?',[$appId]);
            Security::audit(Auth::id(),'document.uploaded','document_versions',$vid,['application_id'=>$appId,'type_id'=>$typeId]);
        });}catch(\Throwable $e){@unlink($dest);throw $e;}
    }
    public static function version(int $id): array {
        $v=Db::one('SELECT dv.*,d.application_id,d.document_type_id,dt.code type_code,d.milestone_id FROM document_versions dv JOIN documents d ON d.id=dv.document_id JOIN document_types dt ON dt.id=d.document_type_id WHERE dv.id=?',[$id]);
        if(!$v)throw new \DomainException('Versión documental inexistente');return $v;
    }
    public static function download(int $id): never {
        $v=self::version($id);$a=Academic::application((int)$v['application_id']);Academic::requireView($a);
        $path=self::dir().'/'.$v['storage_key'];if(!is_file($path))throw new \DomainException('Archivo no encontrado en almacenamiento');
        header('Content-Type: '.$v['mime_type']);header('Content-Length: '.filesize($path));header('X-Content-Type-Options: nosniff');
        $name=str_replace(['"',"\r","\n"],'_', $v['original_filename']);
        header('Content-Disposition: attachment; filename="documento-'.(int)$id.'"; filename*=UTF-8\'\''.rawurlencode($name));
        Security::audit(Auth::id(),'document.downloaded','document_versions',$id);readfile($path);exit;
    }
    public static function review(int $versionId,string $decision,string $comments): void {
        if(!in_array($decision,['aprobado','observado','rechazado'],true))throw new \DomainException('Decisión inválida');
        if(strlen(trim($comments))<8)throw new \DomainException('Explica la revisión (mínimo 8 caracteres)');
        $v=self::version($versionId);$a=Academic::application((int)$v['application_id']);Academic::requireView($a);
        $authorized=Auth::has('director','secretaria','admin') || (bool)Db::one("SELECT id FROM assignments WHERE application_id=? AND teacher_id=? AND role_code='revisor' AND ended_at IS NULL",[$a['id'],Auth::id()]);
        if(!$authorized)throw new \DomainException('No puedes revisar este documento');
        if(!in_array($a['status'],['presentada','observada','aprobada','en_desarrollo','habilitada'],true))throw new \DomainException('Expediente no revisable');
        $latest=Db::one('SELECT MAX(id) AS id FROM document_versions WHERE document_id=?',[$v['document_id']]);
        if((int)$latest['id']!==$versionId)throw new \DomainException('Debes revisar la versión más reciente');
        Db::run('INSERT INTO document_reviews(version_id,reviewer_id,decision,comments) VALUES(?,?,?,?)',[$versionId,Auth::id(),$decision,$comments]);
        Academic::notify((int)$a['student_id'],'Documento '.$v['type_code'].' revisado: '.$decision,(int)$a['id']);
        Security::audit(Auth::id(),'document.reviewed','document_versions',$versionId,['decision'=>$decision]);
    }
}
