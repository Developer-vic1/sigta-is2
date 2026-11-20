<?php
declare(strict_types=1);
namespace Sigta\Services;
use Sigta\Core\{Db,Auth,Security};
/** Ruta de Excelencia Académica; NO sustituye la resolución institucional oficial. */
final class Excellence {
    public static function close(int $appId,float $grade,string $basis):int {
        if($grade<0||$grade>100||strlen(trim($basis))<15)throw new \DomainException('Calificación o fundamento insuficiente');
        return Db::transaction(function()use($appId,$grade,$basis){
            $a=Academic::application($appId);Academic::requireView($a);
            if((int)$a['requires_defense']||(int)$a['requires_tutor']||(int)$a['requires_tribunal']||$a['status']!=='aprobada')throw new \DomainException('Solo se admite la modalidad de resolución sin defensa aprobada');
            $pending=Db::one("SELECT COUNT(*) n FROM requirement_verifications WHERE application_id=? AND status<>'aprobado'",[$appId]);
            if((int)$pending['n'])throw new \DomainException('Verifica todos los requisitos');
            $pdf=Pdf::make('SIGTA - RESOLUCION DE TITULACION (DEMO)',[
                'Estudiante: '.$a['student_name'],'Carrera: '.$a['career_name'],
                'Modalidad: '.$a['modality_name'],'Promedio academico: '.number_format($grade,2).'/100',
                'Fundamento: '.$basis,'Fecha de registro: '.date('Y-m-d H:i:s'),
                'Documento generado solo con fines de evaluacion academica.',
                'NO es resolucion oficial ni acredita otorgamiento de titulo.'
            ]);
            $key=bin2hex(random_bytes(24)).'.pdf';$path=dirname(__DIR__,2).'/storage/private/acts/'.$key;
            if(file_put_contents($path,$pdf,LOCK_EX)===false)throw new \RuntimeException('Fallo al generar el PDF');
            try{
                Db::run('INSERT INTO resolutions(application_id,final_grade,basis_note,storage_key,sha256,issued_by) VALUES(?,?,?,?,?,?)',[$appId,round($grade,2),$basis,$key,hash('sha256',$pdf),Auth::id()]);$id=Db::id();
                Db::run("UPDATE applications SET status='concluida' WHERE id=?",[$appId]);
                Academic::notify((int)$a['student_id'],'Resolución de excelencia académica de demostración emitida',$appId);
                Security::audit(Auth::id(),'resolution.issued','resolutions',$id);return $id;
            }catch(\Throwable $e){@unlink($path);throw $e;}
        });
    }
    public static function download(int $id):never {
        $r=Db::one('SELECT * FROM resolutions WHERE id=?',[$id]);if(!$r)throw new \DomainException('Resolución no encontrada');
        Academic::requireView(Academic::application((int)$r['application_id']));
        $path=dirname(__DIR__,2).'/storage/private/acts/'.$r['storage_key'];
        if(!is_file($path))throw new \DomainException('Archivo no disponible');
        header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="SIGTA-Resolucion-'.$id.'.pdf"');header('Content-Length: '.filesize($path));readfile($path);exit;
    }
}
