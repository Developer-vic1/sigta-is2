<?php
declare(strict_types=1);
namespace Sigta\Core;
use Sigta\Controllers\{AuthController,PortalController,AcademicController,DocumentController,DefenseController,AdminController};
final class Router {
    public static function dispatch(): void {
        $path=rawurldecode(parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)??'/');
        $base=dirname($_SERVER['SCRIPT_NAME']??'/index.php');if($base!=='.'&&$base!=='/'&&str_starts_with($path,$base.'/'))$path=substr($path,strlen($base));
        if($path==='')$path='/';$method=strtoupper($_SERVER['REQUEST_METHOD']??'GET');
        if($method==='POST')Security::verifyCsrf();
        $routes=[
            ['GET','#^/$#',[PortalController::class,'dashboard']],
            ['GET','#^/login$#',[AuthController::class,'form']],
            ['POST','#^/login$#',[AuthController::class,'login']],
            ['POST','#^/logout$#',[AuthController::class,'logout']],
            ['POST','#^/perfil/clave$#',[AuthController::class,'password']],
            ['GET','#^/postulaciones$#',[PortalController::class,'applications']],
            ['POST','#^/postulaciones$#',[AcademicController::class,'create']],
            ['GET','#^/postulaciones/(\d+)$#',[PortalController::class,'show']],
            ['POST','#^/postulaciones/(\d+)/enviar$#',[AcademicController::class,'submit']],
            ['POST','#^/postulaciones/(\d+)/revisar$#',[AcademicController::class,'review']],
            ['POST','#^/postulaciones/(\d+)/asignar$#',[AcademicController::class,'assign']],
            ['POST','#^/postulaciones/(\d+)/asignar-auto$#',[AcademicController::class,'assignAuto']],
            ['POST','#^/postulaciones/(\d+)/requisito$#',[AcademicController::class,'requirement']],
            ['POST','#^/postulaciones/(\d+)/hito$#',[AcademicController::class,'milestone']],
            ['POST','#^/postulaciones/(\d+)/documento$#',[DocumentController::class,'upload']],
            ['GET','#^/documentos/(\d+)/descargar$#',[DocumentController::class,'download']],
            ['POST','#^/documentos/(\d+)/revision$#',[DocumentController::class,'review']],
            ['POST','#^/evaluaciones/(\d+)$#',[AcademicController::class,'evaluate']],
            ['GET','#^/defensas$#',[PortalController::class,'defenses']],
            ['POST','#^/defensas/programar$#',[DefenseController::class,'schedule']],
            ['POST','#^/defensas/(\d+)/calificar$#',[DefenseController::class,'score']],
            ['POST','#^/defensas/(\d+)/finalizar$#',[DefenseController::class,'finalize']],
            ['GET','#^/actas/(\d+)/descargar$#',[DefenseController::class,'download']],
            ['GET','#^/asignaciones/(\d+)/carta$#',[DefenseController::class,'letter']],
            ['POST','#^/postulaciones/(\d+)/empastado$#',[DefenseController::class,'binding']],
            ['GET','#^/empastados/(\d+)/descargar$#',[DefenseController::class,'downloadBinding']],
            ['POST','#^/postulaciones/(\d+)/excelencia$#',[DefenseController::class,'excellence']],
            ['GET','#^/resoluciones/(\d+)/descargar$#',[DefenseController::class,'downloadResolution']],
            ['GET','#^/admin/usuarios$#',[AdminController::class,'users']],
            ['POST','#^/admin/usuarios$#',[AdminController::class,'createUser']],
            ['POST','#^/admin/usuarios/(\d+)/estado$#',[AdminController::class,'userStatus']],
            ['POST','#^/admin/usuarios/(\d+)/rol$#',[AdminController::class,'grantRole']],
            ['POST','#^/admin/docentes/especialidad$#',[AdminController::class,'specialty']],
            ['GET','#^/admin/convocatorias$#',[AdminController::class,'calls']],
            ['POST','#^/admin/convocatorias$#',[AdminController::class,'createCall']],
            ['POST','#^/admin/reglamentos$#',[AdminController::class,'createRegulation']],
            ['GET','#^/reportes$#',[AdminController::class,'reports']],
            ['GET','#^/notificaciones$#',[AdminController::class,'notifications']],
            ['POST','#^/notificaciones/leer$#',[AdminController::class,'readNotifications']],
            ['GET','#^/perfil$#',[AdminController::class,'profile']],
            ['GET','#^/salud$#',[AdminController::class,'health']],
        ];
        foreach($routes as [$m,$pattern,$call])if($m===$method&&preg_match($pattern,$path,$matches)){
            array_shift($matches);$matches=array_map('intval',$matches);call_user_func_array($call,$matches);return;
        }
        http_response_code(404);\Sigta\Views\Layout::error('Página no encontrada','La ruta solicitada no existe.');
    }
}
