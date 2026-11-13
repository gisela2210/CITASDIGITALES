<?php
require_once __DIR__.'/../includes/functions.php';
$u=require_role('medico');
if($_SERVER['REQUEST_METHOD']!=='POST')go('medico/perfil.php');
check_csrf();
$f=$_FILES['foto_perfil']??null;
if(!$f||$f['error']!==UPLOAD_ERR_OK||!is_uploaded_file($f['tmp_name'])||$f['size']<1||$f['size']>2097152){flash('error','La fotografía no se pudo subir. Usa JPG, PNG o WebP de máximo 2 MB.');go('medico/perfil.php');}
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
$types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
if(!isset($types[$mime])){flash('error','Solo se permiten fotos JPG, PNG o WebP.');go('medico/perfil.php');}
$filename=bin2hex(random_bytes(20)).'.'.$types[$mime];
$dest=__DIR__.'/../private_storage/'.$filename;
if(!move_uploaded_file($f['tmp_name'],$dest)){flash('error','No se pudo guardar la foto. Verifica los permisos de almacenamiento.');go('medico/perfil.php');}
try{
 $m=row('SELECT foto_perfil FROM medicos WHERE usuario_id=?',[$u['id']]);
 db()->prepare('UPDATE medicos SET foto_perfil=? WHERE usuario_id=?')->execute([$filename,$u['id']]);
 if(!empty($m['foto_perfil']))@unlink(__DIR__.'/../private_storage/'.basename($m['foto_perfil']));
 flash('success','Fotografía actualizada.');
}catch(Throwable $e){@unlink($dest);error_log('[MiSalud foto] '.$e->getMessage());flash('error','No se pudo actualizar la fotografía.');}
go('medico/perfil.php');
