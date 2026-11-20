<?php
declare(strict_types=1);
namespace Sigta\Controllers;
use Sigta\Core\{Auth,Http};
use Sigta\Services\{Academic,Documents};
final class DocumentController {
    public static function upload(int $appId):void {
        Auth::require('estudiante');$milestone=isset($_POST['milestone_id'])&&$_POST['milestone_id']!==''?Http::positiveInt('milestone_id'):null;
        Documents::upload($appId,Http::positiveInt('document_type_id'),$milestone,$_FILES['document']??[]);
        Http::flash('success','Documento recibido y versionado correctamente');Http::redirect('postulaciones/'.$appId);
    }
    public static function download(int $versionId):never {Auth::require();Documents::download($versionId);}
    public static function review(int $versionId):void {
        Auth::require();$v=Documents::version($versionId);
        Documents::review($versionId,Http::input('decision',20),Http::input('comments',1000));
        Http::flash('success','Revisión registrada');Http::redirect('postulaciones/'.$v['application_id']);
    }
}
