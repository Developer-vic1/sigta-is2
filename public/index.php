<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/Core/Autoload.php';
\Sigta\Core\Security::init();
try { \Sigta\Core\Router::dispatch(); }
catch(\DomainException $e) {
    if(($_SERVER['REQUEST_METHOD']??'GET')==='POST') {\Sigta\Core\Http::flash('error',$e->getMessage());\Sigta\Core\Http::redirect($_POST['_return']??'');}
    http_response_code(403);\Sigta\Views\Layout::error('Operación no permitida',$e->getMessage());
} catch(\Throwable $e) {
    error_log('[SIGTA] '.$e);
    http_response_code(500);
    \Sigta\Views\Layout::error('Error interno','No fue posible completar la operación. Revisa los registros del servidor.');
}
