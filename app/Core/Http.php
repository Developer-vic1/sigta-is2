<?php
declare(strict_types=1);
namespace Sigta\Core;
final class Http {
    public static function url(string $path=''): string {
        $base=dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php');
        if ($base==='/' || $base==='\\' || $base==='.')$base='';
        return $base . '/' . ltrim($path,'/');
    }
    public static function redirect(string $path): never { header('Location: '.self::url($path), true,303); exit; }
    public static function h(mixed $val): string {return htmlspecialchars((string)($val??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
    public static function old(string $key, string $fallback=''): string {return self::h($_POST[$key]??$fallback);}
    public static function flash(string $type,string $message): void { $_SESSION['flash']=['type'=>$type,'message'=>$message]; }
    public static function fail(string $message,string $url=''): never {self::flash('error',$message);self::redirect($url?:($_SERVER['HTTP_REFERER']??'/'));}
    public static function input(string $key,int $max=255): string {
        $val=$_POST[$key]??''; if(!is_string($val))throw new \DomainException('Parámetro inválido: '.$key);
        $val=trim($val); if(strlen($val)>$max*4)throw new \DomainException('Texto demasiado extenso: '.$key);
        if(!preg_match('//u',$val))throw new \DomainException('Codificación inválida');
        if(count(preg_split('//u',$val,-1,PREG_SPLIT_NO_EMPTY))>$max)throw new \DomainException('Límite excedido: '.$key);
        return $val;
    }
    public static function positiveInt(string $key): int { $v=filter_var($_POST[$key]??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if($v===false)throw new \DomainException("ID inválido: $key");return (int)$v;}
    public static function getId(mixed $value): int {if(!is_scalar($value) || !ctype_digit((string)$value) || (int)$value<1)throw new \DomainException('Identificador inválido');return (int)$value;}
    public static function dateTime(string $key): string {
        $value=self::input($key,35); $d=\DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i',$value);
        if(!$d || $d->format('Y-m-d\\TH:i')!==$value)throw new \DomainException('Fecha y hora inválidas');return $d->format('Y-m-d H:i:s');
    }
}
