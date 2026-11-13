<?php
require_once __DIR__.'/functions.php';
function bytes_label(int $bytes):string{return $bytes>=1073741824?number_format($bytes/1073741824,2).' GB':number_format($bytes/1048576,1).' MB';}
function user_storage(int $uid):array{
 $plan=row('SELECT s.id,s.nombre,s.limite_bytes,s.descripcion FROM planes_usuarios pu JOIN planes_servicio s ON s.id=pu.plan_id WHERE pu.usuario_id=?',[$uid]);
 if(!$plan)$plan=row('SELECT * FROM planes_servicio ORDER BY id LIMIT 1');
 $used=row('SELECT COALESCE(SUM(tamano_bytes),0) bytes FROM archivos_usuario WHERE usuario_id=?',[$uid]);
 return ['plan'=>$plan,'used'=>(int)($used['bytes']??0),'limit'=>(int)($plan['limite_bytes']??0)];
}
