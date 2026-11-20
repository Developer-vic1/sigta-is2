<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Solo CLI');}
require dirname(__DIR__).'/app/Core/Autoload.php';
use Sigta\Core\Db;
try {
    $rows=Db::all("SELECT c.TABLE_NAME,c.ORDINAL_POSITION,c.COLUMN_NAME,c.COLUMN_TYPE,c.IS_NULLABLE,c.COLUMN_DEFAULT,c.COLUMN_KEY,c.EXTRA,
       COALESCE(k.REFERENCED_TABLE_NAME,'') AS FK_TABLE,COALESCE(k.REFERENCED_COLUMN_NAME,'') AS FK_COLUMN
       FROM information_schema.COLUMNS c
       LEFT JOIN information_schema.KEY_COLUMN_USAGE k ON k.TABLE_SCHEMA=c.TABLE_SCHEMA AND k.TABLE_NAME=c.TABLE_NAME AND k.COLUMN_NAME=c.COLUMN_NAME AND k.REFERENCED_TABLE_NAME IS NOT NULL
       WHERE c.TABLE_SCHEMA=DATABASE() ORDER BY c.TABLE_NAME,c.ORDINAL_POSITION");
    if(!$rows)throw new RuntimeException('No hay tablas en la base actual');
    $path=dirname(__DIR__).'/docs/datos/DICCIONARIO_EXPORTADO_MYSQL.csv';
    $out=fopen($path,'wb');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,array_keys($rows[0]));
    foreach($rows as $row)fputcsv($out,array_values($row));fclose($out);
    echo "Exportado directamente de information_schema: $path (".count($rows)." columnas).\n";
}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
