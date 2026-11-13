<?php
require_once __DIR__.'/includes/layout.php';
require_once __DIR__.'/includes/plan.php';
$u=require_role(['paciente','medico','admin']);
$admin=$u['rol']==='admin';
$doctor=$u['rol']==='medico';
$patient=$u['rol']==='paciente';
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();
 $action=$_POST['action']??'';
 if($admin && $action==='status'){
  $state=$_POST['estado']??'';
  if(in_array($state,['abierto','en_proceso','resuelto'],true)) db()->prepare('UPDATE tickets_soporte SET estado=? WHERE id=?')->execute([$state,(int)$_POST['id']]);
 }elseif($admin && $action==='plan'){
  $uid=(int)($_POST['uid']??0);$pid=(int)($_POST['plan_id']??0);
  if(row('SELECT id FROM usuarios WHERE id=?',[$uid]) && row('SELECT id FROM planes_servicio WHERE id=?',[$pid])) db()->prepare('INSERT INTO planes_usuarios(usuario_id,plan_id) VALUES (?,?) ON DUPLICATE KEY UPDATE plan_id=VALUES(plan_id)')->execute([$uid,$pid]);
 }elseif(!$admin && $action==='ticket'){
  $title=trim($_POST['asunto']??'');$body=trim($_POST['descripcion']??'');
  if(mb_strlen($title)>=5 && mb_strlen($title)<=150 && mb_strlen($body)>=10 && mb_strlen($body)<=2000){
   db()->prepare('INSERT INTO tickets_soporte(usuario_id,asunto,descripcion) VALUES(?,?,?)')->execute([$u['id'],$title,$body]);
  }else{
   flash('error','Completa el asunto y la descripción del problema.');go('soporte.php');
  }
 }
 flash('success','Información actualizada.');go('soporte.php');
}
$plans=all('SELECT * FROM planes_servicio ORDER BY id');
$s=user_storage((int)$u['id']);
$tickets=$admin
 ? all('SELECT t.*,u.nombre,u.correo FROM tickets_soporte t JOIN usuarios u ON u.id=t.usuario_id ORDER BY t.id DESC LIMIT 100')
 : all('SELECT * FROM tickets_soporte WHERE usuario_id=? ORDER BY id DESC LIMIT 50',[$u['id']]);
$users=$admin?all('SELECT id,nombre,correo,rol FROM usuarios WHERE rol!=\'admin\' ORDER BY id DESC LIMIT 200'):[];
header_ui($admin?'Soporte y planes':($doctor?'Soporte y mi plan':'Soporte')); ?>
<div class="page-intro">
  <h2><?= $admin?'Centro de soporte y planes':($doctor?'Tu espacio de soporte profesional':'Ayuda y soporte') ?></h2>
  <p><?= $admin?'Atiende incidencias y asigna capacidad de almacenamiento a cada cuenta.':($doctor?'Consulta tu plan, el espacio disponible y solicita mantenimiento.':'Solicita ayuda cuando tengas un problema con tu cuenta, tus citas o la plataforma.') ?></p>
</div>
<?php if($doctor): ?>
<div class="support-grid">
  <section class="panel support-card">
    <h2>Mi plan: <?=e($s['plan']['nombre']??'Sin configurar')?></h2>
    <p><?=e($s['plan']['descripcion']??'')?></p>
    <div class="metric"><?=bytes_label($s['used'])?> / <?=bytes_label($s['limit'])?></div>
    <div class="support-meter"><span style="width:<?= $s['limit']?min(100,round($s['used']/$s['limit']*100)):0 ?>%"></span></div>
    <p class="muted">El administrador asigna el plan y puede ajustar la capacidad disponible para tu cuenta.</p>
    <a class="btn btn-ghost" href="<?=url('medico/almacenamiento.php')?>">Administrar mis archivos</a>
  </section>
  <section class="panel support-card">
    <h2>Solicitar soporte</h2>
    <form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="ticket"><label>Asunto<input name="asunto" maxlength="150" minlength="5" required placeholder="Ej. No puedo exportar mis citas"></label><label>Descripción<textarea name="descripcion" rows="4" minlength="10" maxlength="2000" required placeholder="Describe el problema sin compartir datos clínicos"></textarea></label><button class="btn btn-primary">Enviar solicitud</button></form>
  </section>
</div>
<?php elseif($patient): ?>
<section class="panel support-card" style="max-width:760px">
  <h2>Solicitar soporte</h2>
  <p class="muted">Cuéntanos el problema para poder ayudarte. Evita compartir información clínica sensible.</p>
  <form method="post" class="form-stack"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="ticket"><label>Asunto<input name="asunto" maxlength="150" minlength="5" required placeholder="Ej. No puedo agendar mi cita"></label><label>Descripción<textarea name="descripcion" rows="5" minlength="10" maxlength="2000" required placeholder="Explica qué pasó y en qué pantalla ocurrió"></textarea></label><div><button class="btn btn-primary">Enviar solicitud</button></div></form>
</section>
<?php else: ?>
<section class="panel support-card">
  <h2>Asignación de almacenamiento</h2>
  <form method="post" class="form-grid"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="plan"><label>Usuario<select name="uid" required><?php foreach($users as $v):?><option value="<?=$v['id']?>"><?=e($v['nombre'].' · '.$v['correo'].' ('.$v['rol'].')')?></option><?php endforeach;?></select></label><label>Plan<select name="plan_id" required><?php foreach($plans as $v):?><option value="<?=$v['id']?>"><?=e($v['nombre'].' · '.bytes_label((int)$v['limite_bytes']))?></option><?php endforeach;?></select></label><div class="full"><button class="btn btn-primary">Asignar plan</button></div></form><p class="muted">Capacidades de ejemplo, configurables en la tabla planes_servicio. No representan un cobro automático.</p>
</section>
<?php endif; ?>
<section class="panel support-card">
  <h2><?= $admin?'Solicitudes recibidas':'Mis solicitudes' ?></h2>
  <div class="table-wrap"><table class="support-table"><thead><tr><th>Folio</th><?php if($admin):?><th>Usuario</th><?php endif;?><th>Asunto</th><th>Fecha</th><th>Estado</th></tr></thead><tbody><?php foreach($tickets as $t):?><tr><td>#<?=$t['id']?></td><?php if($admin):?><td><?=e($t['nombre'])?></td><?php endif;?><td><strong><?=e($t['asunto'])?></strong><small style="display:block"><?=e($t['descripcion'])?></small></td><td><?=ui_date($t['creado_en'])?></td><td><?php if($admin):?><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?=$t['id']?>"><select name="estado" onchange="this.form.submit()" aria-label="Estado del ticket"><?php foreach(['abierto'=>'Abierto','en_proceso'=>'En proceso','resuelto'=>'Resuelto'] as $k=>$v):?><option value="<?=$k?>" <?=$t['estado']===$k?'selected':''?>><?=$v?></option><?php endforeach;?></select></form><?php else:?><?=e($t['estado'])?><?php endif;?></td></tr><?php endforeach;?></tbody></table></div><?php if(!$tickets):?><p class="muted">No hay solicitudes por ahora.</p><?php endif;?>
</section>
<?php footer_ui(); ?>
