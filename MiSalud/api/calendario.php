<?php
require_once __DIR__.'/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');
$u=current_user();
if(!$u || $u['rol']!=='paciente'){ http_response_code(403);echo json_encode(['error'=>'Acceso no autorizado']);exit; }
$medico=(int)($_GET['medico']??0);
$mes=(string)($_GET['mes']??'');
if(!$medico||!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$mes)){http_response_code(400);echo json_encode(['error'=>'Solicitud inválida']);exit;}
$start=$mes.'-01';$last=date('Y-m-t',strtotime($start));
$today=date('Y-m-d');$limit=date('Y-m-d',strtotime('+90 days'));
$days=[];
$schedules=all('SELECT h.dia_semana,h.inicio,h.fin FROM horarios_medicos h JOIN medicos m ON m.id=h.medico_id JOIN usuarios u ON u.id=m.usuario_id WHERE h.medico_id=? AND m.verificado=1 AND u.activo=1',[$medico]);
$occupied=all('SELECT fecha,hora FROM citas WHERE medico_id=? AND fecha BETWEEN ? AND ? AND estado="programada"',[$medico,$start,$last]);
$busy=[];
foreach($occupied as $x){$busy[$x['fecha']][substr($x['hora'],0,5)]=true;}
for($dt=new DateTimeImmutable($start);$dt->format('Y-m-d')<=$last;$dt=$dt->modify('+1 day')){
 $date=$dt->format('Y-m-d');$status='closed';
 if($date>=$today && $date<=$limit){
  $capacity=0;$free=0;$week=(int)$dt->format('N');
  foreach($schedules as $h){if((int)$h['dia_semana']!==$week)continue;
    $t=strtotime($date.' '.$h['inicio']);$end=strtotime($date.' '.$h['fin']);
    while($t+1800<=$end){$time=date('H:i',$t);$capacity++;
      if(empty($busy[$date][$time]) && ($date!==$today || $time>date('H:i')))$free++;
      $t+=1800;
    }
  }
  $status=$free>0?'available':($capacity>0?'full':'closed');
 }
 $days[$date]=$status;
}
echo json_encode(['dias'=>$days],JSON_UNESCAPED_UNICODE);
