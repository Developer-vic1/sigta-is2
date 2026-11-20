<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/Core/Autoload.php';
use Sigta\Services\Pdf;
use Sigta\Core\Security;
use Sigta\Core\Http;
$ok=0;$bad=0;
function test(string $name, callable $assert):void {global $ok,$bad;try{$assert();echo "OK   $name\n";$ok++;}catch(Throwable $e){echo "FAIL $name: {$e->getMessage()}\n";$bad++;}}
function check(bool $v):void{if(!$v)throw new RuntimeException('Aserción no satisfecha');}
test('Hash de contraseña seguro y verificable',function(){ $hash=Security::passwordHash('ClaveSegura2026Ab');check(password_verify('ClaveSegura2026Ab',$hash));check(!password_verify('mal',$hash));check(str_starts_with($hash,'$argon2id$')||str_starts_with($hash,'$2y$'));});
test('Rechazo de contraseña corta',function(){try{Security::checkPassword('Corta1');throw new RuntimeException('No se rechazó');}catch(DomainException $e){check(str_contains($e->getMessage(),'contraseña'));}});
test('Rechazo de contraseña débil',function(){try{Security::checkPassword('todominusculasmuylarga');throw new RuntimeException('No se rechazó');}catch(DomainException $e){check(str_contains($e->getMessage(),'mayúsculas'));}});
test('Escape HTML anti-XSS',fn()=>check(Http::h('<script>')==='&lt;script&gt;'));
test('Identificadores positivos',function(){check(Http::getId('42')===42);try{Http::getId('-1');throw new RuntimeException('No se rechazó');}catch(DomainException $e){}});
test('PDF independiente con cabecera y referencias',function(){ $pdf=Pdf::make('SIGTA - ACTA',['Alumno: Usuario Demo','Nota: 95/100']);check(str_starts_with($pdf,'%PDF-1.4'));check(str_contains($pdf,'xref'));check(str_contains($pdf,'startxref'));});
test('Escapes de texto en PDF',function(){ $pdf=Pdf::make('Hola (PDF)',['Texto con \\ y (paréntesis)']);check(str_contains($pdf,'\\(PDF\\)'));check(str_contains($pdf,'\\(par'));});
echo "\nResultados: $ok OK, $bad fallos\n";exit($bad?1:0);
