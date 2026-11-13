<?php
require_once __DIR__.'/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
 $u=current_user();if(!$u){http_response_code(401);echo json_encode(['error'=>'Inicia sesión']);exit;}
 $month=$_GET['mes']??date('Y-m');
 if(!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$month)){http_response_code(400);echo json_encode(['error'=>'Mes inválido']);exit;}
 $from=$month.'-01';$to=date('Y-m-d',strtotime($from.' +1 month'));
 $params=[$from,$to];$filter='';
 if($u['rol']==='paciente'){$filter=' AND p.usuario_id=?';$params[]=$u['id'];}
 elseif($u['rol']==='medico'){$filter=' AND m.usuario_id=?';$params[]=$u['id'];}
 elseif($u['rol']==='admin'){
  $medico=(int)($_GET['medico']??0);if($medico>0){$filter=' AND m.id=?';$params[]=$medico;}
 }else{http_response_code(403);echo json_encode(['error'=>'No autorizado']);exit;}
 $sql='SELECT c.id,c.fecha,TIME_FORMAT(c.hora,"%H:%i") hora,c.estado,CONCAT(pu.nombre," ",pu.apellido_paterno) paciente,CONCAT(mu.nombre," ",mu.apellido_paterno) medico,COALESCE(e.nombre,"Medicina") especialidad,COALESCE(co.nombre,"Por asignar") consultorio FROM citas c JOIN pacientes p ON p.id=c.paciente_id JOIN usuarios pu ON pu.id=p.usuario_id JOIN medicos m ON m.id=c.medico_id JOIN usuarios mu ON mu.id=m.usuario_id LEFT JOIN especialidades e ON e.id=m.especialidad_id LEFT JOIN consultorios co ON co.id=c.consultorio_id WHERE c.fecha>=? AND c.fecha<?'.$filter.' ORDER BY c.fecha,c.hora';
 $rows=all($sql,$params);
 if($u['rol']==='paciente')foreach($rows as &$item)unset($item['paciente']);
 echo json_encode(['eventos'=>$rows,'mes'=>$month],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
} catch(Throwable $ex){error_log('[MiSalud API eventos] '.$ex->getMessage());http_response_code(500);echo json_encode(['error'=>'No fue posible consultar las citas']);}
