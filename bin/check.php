<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);
require dirname(__DIR__).'/app/Core/Autoload.php';
$checks=['PHP >= 8.2'=>PHP_VERSION_ID>=80200,'PDO'=>extension_loaded('pdo'),'pdo_mysql'=>extension_loaded('pdo_mysql'),'fileinfo'=>extension_loaded('fileinfo'),'openssl'=>extension_loaded('openssl'),'iconv'=>extension_loaded('iconv'),'.env'=>is_file(dirname(__DIR__).'/.env'),'storage/private/documents escribible'=>is_writable(dirname(__DIR__).'/storage/private/documents'),'storage/private/acts escribible'=>is_writable(dirname(__DIR__).'/storage/private/acts')];
foreach($checks as $name=>$yes)echo ($yes?'[OK] ':'[FALTA] ').$name."\n";
exit(in_array(false,$checks,true)?1:0);
