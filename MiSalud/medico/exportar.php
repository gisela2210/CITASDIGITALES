<?php
require_once __DIR__.'/../includes/functions.php';$u=require_role('medico');$m=row('SELECT id FROM medicos WHERE usuario_id=?',[$u['id']]);
$format=$_GET['formato']??'csv';if(!in_array($format,['csv','pdf'],true)){http_response_code(400);exit('Formato no válido');}
$start=$_GET['desde']??date('Y-01-01');$end=$_GET['hasta']??date('Y-12-31');
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$start)||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$end)||$end<$start){http_response_code(400);exit('Fechas no válidas');}
$rows=all('SELECT c.fecha,c.hora,c.estado,u.nombre,u.apellido_paterno,u.apellido_materno FROM citas c JOIN pacientes pa ON pa.id=c.paciente_id JOIN usuarios u ON u.id=pa.usuario_id WHERE c.medico_id=? AND c.fecha BETWEEN ? AND ? ORDER BY c.fecha,c.hora LIMIT 15000',[$m['id'],$start,$end]);
if($format==='csv'){
 header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="agenda_misalud.csv"');header('X-Content-Type-Options: nosniff');$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,['Fecha','Hora','Paciente','Estado']);foreach($rows as $r){$name=trim($r['nombre'].' '.$r['apellido_paterno'].' '.$r['apellido_materno']);$values=[$r['fecha'],$r['hora'],$name,$r['estado']];foreach($values as &$v)if(preg_match('/^[=+@\-\t\r]/u',$v))$v="'".$v;unset($v);fputcsv($out,$values);}fclose($out);exit;
}
// PDF ligero generado sin dependencias: texto administrativo sin datos clínicos.
function pdf_txt(string $x):string{
 $x=iconv('UTF-8','Windows-1252//TRANSLIT//IGNORE',$x)?:'';return str_replace(["\\",'(',')',"\r","\n"],["\\\\",'\\(','\\)',' ',' '],$x);
}
function pdf_stream(array $lines):void{
 $objects=[];$add=function(string $obj)use(&$objects){$objects[]=$obj;return count($objects);};
 $catalog=$add('');$pages=$add('');$font=$add('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>');$kids=[];
 foreach(array_chunk($lines,45) as $pageLines){$text="BT /F1 10 Tf 40 800 Td 13 TL ";foreach($pageLines as $i=>$line){$text.='('.pdf_txt(mb_substr($line,0,115)).') Tj T* ';}$text.='ET';$stream=$add('<< /Length '.strlen($text).' >>'."\nstream\n".$text."\nendstream");$page=$add('<< /Type /Page /Parent '.$pages.' 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 '.$font.' 0 R >> >> /Contents '.$stream.' 0 R >>');$kids[]=$page.' 0 R';}
 $objects[$catalog-1]='<< /Type /Catalog /Pages '.$pages.' 0 R >>';$objects[$pages-1]='<< /Type /Pages /Kids ['.implode(' ',$kids).'] /Count '.count($kids).' >>';
 $pdf="%PDF-1.4\n";$offsets=[0];foreach($objects as $i=>$o){$offsets[]=strlen($pdf);$pdf.=($i+1).' 0 obj'."\n".$o."\nendobj\n";}$xref=strlen($pdf);$pdf.='xref'."\n0 ".(count($objects)+1)."\n0000000000 65535 f \n";foreach(array_slice($offsets,1) as $off)$pdf.=sprintf('%010d 00000 n ', $off)."\n";$pdf.='trailer << /Size '.(count($objects)+1).' /Root '.$catalog.' 0 R >>'."\nstartxref\n".$xref."\n%%EOF";
 header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="agenda_misalud.pdf"');header('Content-Length: '.strlen($pdf));header('X-Content-Type-Options: nosniff');echo $pdf;
}
$lines=['MISALUD - REPORTE DE CONSULTAS','Medico: '.full_name($u),'Periodo: '.$start.' a '.$end,'Total de citas: '.count($rows),'-----------------------------------------------------------------'];
foreach($rows as $r)$lines[]=$r['fecha'].' '.substr($r['hora'],0,5).'  '.mb_substr(trim($r['nombre'].' '.$r['apellido_paterno'].' '.$r['apellido_materno']),0,43).'  '.$r['estado'];
if(!$rows)$lines[]='No se encontraron consultas en el periodo.';
pdf_stream($lines);exit;
