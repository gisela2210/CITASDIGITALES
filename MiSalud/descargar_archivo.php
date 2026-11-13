<?php
require_once __DIR__.'/includes/functions.php';$u=require_role(['medico','paciente']);$f=row('SELECT * FROM archivos_usuario WHERE id=? AND usuario_id=?',[(int)($_GET['id']??0),$u['id']]);
if(!$f){http_response_code(404);exit('Archivo no encontrado');}
$path=__DIR__.'/private_storage/'.$f['nombre_interno'];if(!is_file($path)){http_response_code(404);exit('Archivo no disponible');}
header('Content-Type: '.$f['tipo']);header('X-Content-Type-Options: nosniff');header('Content-Disposition: attachment; filename="documento_'.(int)$f['id'].'.'.pathinfo($f['nombre_interno'],PATHINFO_EXTENSION).'"');header('Content-Length: '.filesize($path));readfile($path);exit;
