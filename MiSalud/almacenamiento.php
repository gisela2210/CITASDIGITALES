<?php
require_once __DIR__.'/includes/layout.php';
require_once __DIR__.'/includes/plan.php';
$u=require_role(['paciente','medico']);
$isDoctor=$u['rol']==='medico';
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();$action=$_POST['action']??'';
 if($action==='upload' && isset($_FILES['archivo'])){
  $f=$_FILES['archivo'];$quota=user_storage((int)$u['id']);$size=(int)($f['size']??0);
  if(($f['error']??1)!==UPLOAD_ERR_OK||$size<1||$size>5242880||$quota['used']+$size>$quota['limit']){flash('error','Archivo demasiado grande, sin espacio o falló la carga. Límite por archivo: 5 MB.');go('almacenamiento.php');}
  $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);$types=['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png'];
  if(!isset($types[$mime])){flash('error','Solo se permiten archivos PDF, JPG o PNG.');go('almacenamiento.php');}
  $internal=bin2hex(random_bytes(20)).'.'.$types[$mime];$destination=__DIR__.'/private_storage/'.$internal;
  if(!move_uploaded_file($f['tmp_name'],$destination)){flash('error','No fue posible guardar el archivo.');go('almacenamiento.php');}
  $original=mb_substr(basename(str_replace('\\','/',(string)$f['name'])),0,180);
  try{db()->prepare('INSERT INTO archivos_usuario(usuario_id,nombre_original,nombre_interno,tamano_bytes,tipo) VALUES(?,?,?,?,?)')->execute([$u['id'],$original,$internal,$size,$mime]);}catch(Throwable $ex){@unlink($destination);throw $ex;}
  flash('success','Archivo guardado en tu espacio personal.');go('almacenamiento.php');
 }
 if($action==='delete'){
  $file=row('SELECT * FROM archivos_usuario WHERE id=? AND usuario_id=?',[(int)($_POST['id']??0),$u['id']]);if($file){db()->prepare('DELETE FROM archivos_usuario WHERE id=? AND usuario_id=?')->execute([$file['id'],$u['id']]);@unlink(__DIR__.'/private_storage/'.$file['nombre_interno']);}
  flash('success','Archivo eliminado.');go('almacenamiento.php');
 }
}
$s=user_storage((int)$u['id']);
$files=all('SELECT * FROM archivos_usuario WHERE usuario_id=? ORDER BY id DESC',[$u['id']]);
header_ui('Mis archivos'); ?>
<div class="page-intro"><h2>Almacenamiento personal</h2><p><?= $isDoctor?'Guarda documentos de forma privada dentro del espacio de tu plan.':'Guarda documentos personales relacionados con tu uso de la plataforma.' ?></p></div>
<div class="support-grid">
<?php if($isDoctor): ?>
<section class="panel support-card"><h2>Espacio de tu plan</h2><p><?=e($s['plan']['nombre']??'Inicial')?></p><div class="metric"><?=bytes_label($s['used'])?> / <?=bytes_label($s['limit'])?></div><div class="support-meter"><span style="width:<?=$s['limit']?min(100,round($s['used']/$s['limit']*100)):0?>%"></span></div><p class="muted">Formatos permitidos: PDF, JPG y PNG. Tamaño máximo por archivo: 5 MB.</p></section>
<?php else: ?>
<section class="panel support-card"><h2>Mis documentos privados</h2><p class="muted">Aquí puedes subir archivos de apoyo para tu cuenta. Si necesitas más espacio, solicita ayuda desde soporte.</p><ul class="muted" style="padding-left:18px;margin:12px 0 0"><li>Formatos permitidos: PDF, JPG y PNG</li><li>Tamaño máximo por archivo: 5 MB</li><li>Tus archivos solo son visibles para tu cuenta</li></ul></section>
<?php endif; ?>
<section class="panel support-card"><h2>Subir documento</h2><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="upload"><label>Seleccionar archivo<input type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png" required></label><button class="btn btn-primary">Guardar archivo</button></form></section>
</div>
<section class="panel support-card"><h2>Mis documentos</h2><div class="table-wrap"><table class="support-table"><thead><tr><th>Nombre</th><th>Tamaño</th><th>Acciones</th></tr></thead><tbody><?php foreach($files as $f):?><tr><td><?=e($f['nombre_original'])?></td><td><?=bytes_label((int)$f['tamano_bytes'])?></td><td><a class="btn btn-ghost btn-sm" href="<?=url('descargar_archivo.php?id='.$f['id'])?>">Descargar</a><form method="post" style="display:inline" onsubmit="return confirm('¿Eliminar este archivo?')"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$f['id']?>"><button class="btn btn-sm btn-ghost">Eliminar</button></form></td></tr><?php endforeach;?></tbody></table></div><?php if(!$files):?><p class="muted">Aún no has guardado archivos.</p><?php endif;?></section><?php footer_ui(); ?>
