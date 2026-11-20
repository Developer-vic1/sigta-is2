<?php
declare(strict_types=1);
namespace Sigta\Services;
use Sigta\Core\{Auth,Db,Security};
final class Letters {
    public static function assignment(int $id):never {
        $ass=Db::one('SELECT ass.*,u.full_name teacher_name,a.topic,a.id application_id FROM assignments ass JOIN users u ON u.id=ass.teacher_id JOIN applications a ON a.id=ass.application_id WHERE ass.id=?',[$id]);
        if(!$ass)throw new \DomainException('Asignación inexistente');
        $a=Academic::application((int)$ass['application_id']);Academic::requireView($a);
        $pdf=Pdf::make('SIGTA - CARTA DE DESIGNACION (DEMO)',[
            'Docente: '.$ass['teacher_name'], 'Estudiante: '.$a['student_name'],
            'Proyecto: '.$ass['topic'],'Funcion: '.$ass['role_code'].' - plaza '.$ass['seat'],
            'Designacion registrada: '.$ass['assigned_at'],
            'Documento de demostracion academica, sin firmas ni efectos institucionales.'
        ]);
        Security::audit(Auth::id(),'letter.generated','assignments',$id);
        header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="SIGTA-Carta-'.$id.'.pdf"');header('Content-Length: '.strlen($pdf));echo $pdf;exit;
    }
    public static function binding(int $appId):void {
        Db::transaction(function()use($appId){
            $a=Academic::application($appId);Academic::requireView($a);
            if($a['status']!=='habilitada'||!(int)$a['requires_profile']||!Academic::sufficiency($appId))throw new \DomainException('No corresponde autorizar empastado antes de los dictámenes aprobados');
            $final=Db::one("SELECT dv.id,dv.sha256 FROM document_versions dv JOIN documents d ON d.id=dv.document_id JOIN document_types dt ON dt.id=d.document_type_id WHERE d.application_id=? AND dt.code='FINAL' ORDER BY dv.id DESC LIMIT 1",[$appId]);
            if(!$final)throw new \DomainException('Se requiere la versión final del documento');
            $pending=Db::one("SELECT COUNT(*) n FROM requirement_verifications WHERE application_id=? AND status<>'aprobado'",[$appId]);
            if((int)$pending['n'])throw new \DomainException('Requisitos pendientes de verificación');
            $pdf=Pdf::make('SIGTA - AUTORIZACION DE EMPASTADO (DEMO)',[
                'Estudiante: '.$a['student_name'],'Tema: '.$a['topic'],
                'Documento final version: '.$final['id'],'Huella SHA256: '.$final['sha256'],
                'Dictamen de suficiencia: aprobado en el prototipo.',
                'No representa una autorizacion oficial de la universidad.',
                'Fecha: '.date('Y-m-d H:i:s')
            ]);
            $key=bin2hex(random_bytes(24)).'.pdf';$path=dirname(__DIR__,2).'/storage/private/acts/'.$key;
            if(file_put_contents($path,$pdf,LOCK_EX)===false)throw new \RuntimeException('Fallo al crear archivo');
            try{
                Db::run('INSERT INTO binding_authorizations(application_id,final_version_id,storage_key,sha256,authorized_by) VALUES(?,?,?,?,?)',[$appId,$final['id'],$key,hash('sha256',$pdf),Auth::id()]);
                Academic::notify((int)$a['student_id'],'Autorización académica de empastado disponible (prototipo)',$appId);
                Security::audit(Auth::id(),'binding.authorized','applications',$appId);
            }catch(\Throwable $e){@unlink($path);throw $e;}
        });
    }
    public static function downloadBinding(int $id):never {
        $b=Db::one('SELECT * FROM binding_authorizations WHERE id=?',[$id]);if(!$b)throw new \DomainException('Autorización inexistente');
        Academic::requireView(Academic::application((int)$b['application_id']));
        $path=dirname(__DIR__,2).'/storage/private/acts/'.$b['storage_key'];if(!is_file($path))throw new \DomainException('Archivo no disponible');
        header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="SIGTA-Empastado-'.$id.'.pdf"');header('Content-Length: '.filesize($path));readfile($path);exit;
    }
}
