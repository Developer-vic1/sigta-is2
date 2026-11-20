<?php
declare(strict_types=1);
namespace Sigta\Services;
/** Generador PDF mínimo autónomo: documentos textuales sin dependencia externa. */
final class Pdf {
    private static function escape(string $text):string {
        $s=iconv('UTF-8','WINDOWS-1252//TRANSLIT//IGNORE',$text);
        if($s===false)$s=preg_replace('/[^\x20-\x7e]/','?', $text)??'';
        return str_replace(['\\','(',')',"\r","\n"],['\\\\','\\(','\\)',' ',' '],$s);
    }
    public static function make(string $title,array $lines): string {
        $cmd="BT /F1 16 Tf 50 777 Td (".self::escape($title).") Tj ET\n";
        $y=744;
        foreach($lines as $line){
            $line=(string)$line;
            $wrapped=preg_split('/\r?\n/',wordwrap($line,86,"\n",true));
            foreach($wrapped as $part){
                if($y<60)throw new \DomainException('Documento demasiado extenso para la plantilla PDF simple');
                $cmd.="BT /F1 10 Tf 50 $y Td (".self::escape($part).") Tj ET\n";$y-=18;
            }
            $y-=5;
        }
        $objects=[];
        $objects[]='<< /Type /Catalog /Pages 2 0 R >>';
        $objects[]='<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>';
        $objects[]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[]='<< /Length '.strlen($cmd).' >>' . "\nstream\n" . $cmd . "endstream";
        $pdf="%PDF-1.4\n";$offsets=[0];
        foreach($objects as $i=>$obj){$offsets[]=strlen($pdf);$pdf.=($i+1)." 0 obj\n".$obj."\nendobj\n";}
        $xref=strlen($pdf);$pdf.="xref\n0 ".(count($objects)+1)."\n0000000000 65535 f \n";
        foreach(array_slice($offsets,1)as $off)$pdf.=sprintf('%010d 00000 n ', $off)."\n";
        $pdf.="trailer\n<< /Size ".(count($objects)+1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
        return $pdf;
    }
}
