<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('CLI');}
require dirname(__DIR__).'/app/Core/Autoload.php';
use Sigta\Core\Db;
try {
    $db=Db::conn();$database=$db->query('SELECT DATABASE()')->fetchColumn();
    $tables=Db::all('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()');
    $names=array_column($tables,'TABLE_NAME');
    $expected=['users','roles','staff_careers','students','teachers','modalities','calls','applications','documents','document_versions','assignments','evaluations','defenses','defense_scores','acts','resolutions','audit_logs'];
    foreach($expected as $name)if(!in_array($name,$names,true))throw new RuntimeException('Falta tabla '.$name);
    $roles=Db::one('SELECT COUNT(*) n FROM roles');$mods=Db::one('SELECT COUNT(*) n FROM modalities');
    if((int)$roles['n']<6||(int)$mods['n']<5)throw new RuntimeException('Catálogos incompletos');
    $idx=Db::one("SELECT COUNT(*) n FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='assignments' AND INDEX_NAME='uq_active_teacher'");
    if((int)$idx['n']<3)throw new RuntimeException('Falta índice de incompatibilidad activa');
    $fx=Db::one('SELECT COUNT(*) n FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL');
    if((int)$fx['n']<25)throw new RuntimeException('FK insuficientes');
    echo "[OK] MySQL '$database': ".count($names)." tablas, {$roles['n']} roles, {$mods['n']} modalidades, {$fx['n']} referencias FK.\n";
    echo "[NOTA] Comprueba flujo multiusuario, permisos y agenda con los casos CP01–CP12.\n";
}catch(Throwable $e){fwrite(STDERR,"[ERROR] ".$e->getMessage()."\n");exit(1);}
