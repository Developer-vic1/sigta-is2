<?php
declare(strict_types=1);
namespace Sigta\Controllers;
use Sigta\Core\{Auth,Db,Http};
use Sigta\Services\{Academic,Defense,Excellence,Letters};
final class DefenseController {
    public static function schedule():void {
        Auth::require('secretaria','director','admin');$id=Http::positiveInt('application_id');
        Defense::schedule($id,Http::positiveInt('room_id'),Http::dateTime('starts_at'),Http::dateTime('ends_at'));
        Http::flash('success','Defensa programada y validada sin cruces');Http::redirect('postulaciones/'.$id);
    }
    public static function score(int $defenseId):void {
        Auth::require('tutor','tribunal');$assignment=Http::positiveInt('assignment_id');$grade=Http::input('grade',8);
        if(!is_numeric($grade))throw new \DomainException('La nota debe ser numérica');
        Defense::score($defenseId,$assignment,(float)$grade,Http::input('comments',700));
        $d=Db::one('SELECT application_id FROM defenses WHERE id=?',[$defenseId]);Http::flash('success','Calificación registrada');Http::redirect('postulaciones/'.$d['application_id']);
    }
    public static function finalize(int $defenseId):void {
        Auth::require('secretaria','director','admin');$a=Db::one('SELECT application_id FROM defenses WHERE id=?',[$defenseId]);
        $id=Defense::finalize($defenseId);Http::flash('success','Acta de demostración generada (ID '.$id.')');Http::redirect('postulaciones/'.$a['application_id']);
    }
    public static function download(int $id):never {Auth::require();Defense::downloadAct($id);}
    public static function letter(int $id):never {Auth::require();Letters::assignment($id);}
    public static function binding(int $id):void {
        Auth::require('director','admin');Letters::binding($id);
        Http::flash('success','Autorización de empastado generada');Http::redirect('postulaciones/'.$id);
    }
    public static function downloadBinding(int $id):never {Auth::require();Letters::downloadBinding($id);}

    public static function excellence(int $id):void {
        Auth::require('director','admin');$grade=Http::input('grade',8);
        if(!is_numeric($grade))throw new \DomainException('Nota inválida');
        Excellence::close($id,(float)$grade,Http::input('basis_note',800));
        Http::flash('success','Resolución académica simulada emitida');Http::redirect('postulaciones/'.$id);
    }
    public static function downloadResolution(int $id):never {Auth::require();Excellence::download($id);}

}
