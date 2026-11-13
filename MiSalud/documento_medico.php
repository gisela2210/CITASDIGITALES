<?php
require_once __DIR__.'/includes/functions.php';
$u=require_role('medico');
$m=row('SELECT cedula_documento FROM medicos WHERE usuario_id=?',[$u['id']]);
if(!$m||empty($m['cedula_documento'])){http_response_code(404);exit('Archivo no encontrado');}
$file=basename($m['cedula_documento']);
$path=__DIR__.'/private_storage/'.$file;
if(!is_file($path)){http_response_code(404);exit('Archivo no disponible');}
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);
$allowed=['application/pdf','image/jpeg','image/png'];
if(!in_array($mime,$allowed,true)){http_response_code(403);exit('Formato no permitido');}
header('Content-Type: '.$mime);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
header('Content-Security-Policy: default-src \'none\'; sandbox');
header('Content-Disposition: attachment; filename=cedula.'.pathinfo($file,PATHINFO_EXTENSION));
header('Content-Length: '.filesize($path));
readfile($path);exit;
