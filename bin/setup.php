<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Solo CLI');}
require dirname(__DIR__).'/app/Core/Autoload.php';
use Sigta\Core\{Db,Env,Security};
if(PHP_VERSION_ID<80200){fwrite(STDERR,"Se requiere PHP 8.2 o superior\n");exit(1);}
if(!extension_loaded('pdo_mysql')){fwrite(STDERR,"Debes habilitar pdo_mysql en PHP/Laragon.\n");exit(1);}
if(!is_file(dirname(__DIR__).'/.env')){fwrite(STDERR,"Copia .env.example a .env y configura MySQL.\n");exit(1);}
$envPath=dirname(__DIR__).'/.env';$envContents=file_get_contents($envPath);
if(str_contains($envContents,"APP_KEY=\n")||str_ends_with(trim($envContents),'APP_KEY=')){
    $newKey=bin2hex(random_bytes(32));
    $envContents=preg_replace('/^APP_KEY=$/m','APP_KEY='.$newKey,$envContents);
    file_put_contents($envPath,$envContents,LOCK_EX);
}
try{$pdo=Db::conn();}catch(Throwable $e){fwrite(STDERR,"Conexión MySQL fallida. Crea primero la BD sigta (database/00_create_database.sql).\n");exit(1);}
$exists=$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='users'")->fetchColumn();
if((int)$exists>0){fwrite(STDERR,"El esquema ya existe. Se detiene para evitar sobrescribir datos.\n");exit(1);}
function applySql(string $path):void{
    $script=file_get_contents($path);if($script===false)throw new RuntimeException('Script ilegible');
    $script=preg_replace('/^\s*--.*$/m','',$script)??$script;
    foreach(explode(';',$script) as $statement){$statement=trim($statement);if($statement==='')continue;Sigta\Core\Db::conn()->exec($statement);}
}
try{
    applySql(dirname(__DIR__).'/database/migrations/001_schema.sql');
    applySql(dirname(__DIR__).'/database/migrations/002_constraints.sql');
    applySql(dirname(__DIR__).'/database/seeds/001_catalog.sql');
    echo "Esquema y catálogos instalados.\n";
    $email=strtolower(trim((string)(getenv('SIGTA_BOOTSTRAP_EMAIL')?:'')));
    $password=(string)(getenv('SIGTA_BOOTSTRAP_PASSWORD')?:'');
    if($email===''){$email=strtolower(trim((string)readline('Correo del administrador inicial: ')));}
    if($password===''){
        echo "Contraseña inicial (entrada visible en algunas terminales; utiliza un entorno privado): ";
        $password=trim((string)fgets(STDIN));
    }
    if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new DomainException('Correo inválido');
    $hash=Security::passwordHash($password);
    Db::run('INSERT INTO users(full_name,email,password_hash) VALUES(?,?,?)',['Administrador SIGTA',$email,$hash]);$id=Db::id();
    $role=Db::one("SELECT id FROM roles WHERE code='admin'");Db::run('INSERT INTO user_roles(user_id,role_id) VALUES(?,?)',[$id,$role['id']]);
    echo "Administrador creado: $email\nIMPORTANTE: elimina SIGTA_BOOTSTRAP_PASSWORD del entorno y realiza respaldo de MySQL.\n";
}catch(Throwable $e){fwrite(STDERR,"Error durante instalación: ".$e->getMessage()."\nSi el esquema quedó parcial, elimina SOLO la base nueva de pruebas y repite la instalación.\n");exit(1);}
