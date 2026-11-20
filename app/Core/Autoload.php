<?php
declare(strict_types=1);
spl_autoload_register(function(string $class):void{
    $prefix='Sigta\\';if(!str_starts_with($class,$prefix))return;
    $file=dirname(__DIR__).'/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if(is_file($file))require $file;
});
\date_default_timezone_set(\Sigta\Core\Env::get('APP_TIMEZONE','America/La_Paz'));
