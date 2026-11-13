<?php
require_once __DIR__.'/../includes/layout.php';
$u=require_role('admin'); $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();$id=(int)($_POST['id']??0);$action=$_POST['action']??'';
 $m=row('SELECT * FROM medicos WHERE id=?',[$id]);
 if(!$m){$error='No encontramos el registro de ese médico.';}
 elseif($action==='save'){
  $sp=(int)($_POST['especialidad_id']??0);$office=(int)($_POST['consultorio_id']??0);
  if(!$sp || !row('SELECT id FROM especialidades WHERE id=?',[$sp]))$error='Selecciona una especialidad válida.';
  elseif($office && !row('SELECT id FROM consultorios WHERE id=?',[$office]))$error='Selecciona un consultorio válido.';
  else{db()->prepare('UPDATE medicos SET verificado=CASE WHEN NOT (especialidad_id <=> ?) THEN 0 ELSE verificado END,especialidad_id=?,consultorio_id=? WHERE id=?')->execute([$sp,$sp,$office?:null,$id]);flash('success','Datos profesionales guardados. Si cambiaste la especialidad, revisa nuevamente la verificación.');go('admin/medicos.php');}
 }elseif($action==='verify'){
  $v=(int)($_POST['verify']??0);
  if($v && !$m['especialidad_id'])$error='Primero asigna una especialidad al médico.';
  else{db()->prepare('UPDATE medicos SET verificado=? WHERE id=?')->execute([$v?1:0,$id]);flash('success',$v?'Médico habilitado para aparecer en el buscador.':'Verificación revocada.');go('admin/medicos.php');}
 }
}
$rows=all('SELECT m.*,u.nombre,u.apellido_paterno,u.correo,u.activo,e.nombre especialidad,co.nombre consultorio,(SELECT COUNT(*) FROM horarios_medicos hm WHERE hm.medico_id=m.id) horarios_configurados FROM medicos m JOIN usuarios u ON u.id=m.usuario_id LEFT JOIN especialidades e ON e.id=m.especialidad_id LEFT JOIN consultorios co ON co.id=m.consultorio_id ORDER BY m.verificado,m.id DESC');
$specs=all('SELECT id,nombre FROM especialidades ORDER BY nombre');
$rooms=all('SELECT id,nombre FROM consultorios ORDER BY nombre');
header_ui('Gestión de médicos');
?>
<div class="page-intro"><span class="eyebrow teal">ADMINISTRACIÓN</span><h2>Médicos y especialidades 🩺</h2><p>Asigna la especialidad, el consultorio y revisa la identidad profesional antes de activar al médico.</p></div>
<?php if($error):?><div class="alert alert-error"><?=e($error)?></div><?php endif;?>
<div class="alert alert-info"><strong>Para vincular un médico al paciente:</strong> asigna su especialidad, asegúrate de que esté activo, verifica su documentación por un procedimiento independiente y activa su verificación. Después, el médico debe configurar horarios en <strong>Mi calendario</strong>.</div>
<?php if(!$rows):?><div class="panel"><p>No hay médicos registrados. Crea una cuenta desde el registro de médicos.</p></div><?php endif;?>
<div class="doctor-admin-list">
<?php foreach($rows as $r):?>
<article class="panel doctor-admin-card">
 <div class="doctor-admin-head"><div class="doctor-admin-avatar">✚</div><div><h3><?=e($r['nombre'].' '.$r['apellido_paterno'])?></h3><small><?=e($r['correo'])?> · Cédula declarada: <?=e($r['cedula'])?></small></div><span class="badge <?=$r['verificado']?'badge-mint':'badge-muted'?>"><?=$r['verificado']?'✓ Habilitado':'Pendiente'?></span></div>
 <?php if(!$r['activo']):?><p class="alert alert-error">Cuenta desactivada: no aparecerá en el buscador.</p><?php elseif(!$r['especialidad_id']):?><p class="alert alert-info">No aparece al paciente: falta asignar especialidad.</p><?php elseif(!$r['verificado']):?><p class="alert alert-info">No aparece al paciente: falta verificar y habilitar al médico.</p><?php elseif(!(int)$r['horarios_configurados']):?><p class="alert alert-info">Puede aparecer en el listado, pero aún no tendrá días disponibles: debe configurar horarios.</p><?php else:?><p class="alert alert-success">✓ Médico habilitado, con especialidad y horarios configurados.</p><?php endif;?>
 <form method="post" class="doctor-admin-fields"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=$r['id']?>">
 <label>Especialidad<select name="especialidad_id" required><option value="">Seleccionar especialidad</option><?php foreach($specs as $sp):?><option value="<?=$sp['id']?>" <?=$r['especialidad_id']==$sp['id']?'selected':''?>><?=e($sp['nombre'])?></option><?php endforeach;?></select></label>
 <label>Consultorio<select name="consultorio_id"><option value="">Por asignar</option><?php foreach($rooms as $co):?><option value="<?=$co['id']?>" <?=$r['consultorio_id']==$co['id']?'selected':''?>><?=e($co['nombre'])?></option><?php endforeach;?></select></label>
 <button class="btn btn-ghost" type="submit">Guardar datos</button></form>
 <div class="doctor-admin-footer"><span class="muted">La verificación no sustituye revisar cédula e identidad de forma oficial.</span><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="verify"><input type="hidden" name="id" value="<?=$r['id']?>"><input type="hidden" name="verify" value="<?=$r['verificado']?0:1?>"><button type="submit" class="btn btn-sm <?=$r['verificado']?'btn-ghost':'btn-primary'?>" <?=(!$r['especialidad_id']||!$r['activo'])&&!$r['verificado']?'disabled':''?>><?=$r['verificado']?'Revocar verificación':'Habilitar médico'?></button></form></div>
</article>
<?php endforeach;?>
</div>
<?php footer_ui();?>
