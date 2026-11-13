<?php require_once __DIR__.'/../includes/layout.php';$u=require_role('paciente');$p=row('SELECT * FROM pacientes WHERE usuario_id=?',[$u['id']]);if($_SERVER['REQUEST_METHOD']==='POST'){check_csrf();$existing=row('SELECT * FROM turnos_virtuales WHERE paciente_id=? AND fecha=CURDATE() AND estado="en_espera"',[$p['id']]);if(!$existing){db()->prepare('INSERT INTO turnos_virtuales(paciente_id,fecha,estado) VALUES(?,CURDATE(),"en_espera")')->execute([$p['id']]);flash('success','Se generó tu turno para hoy.');}else flash('success','Ya tienes un turno activo para hoy.');go('paciente/mi_turno.php');}$t=row('SELECT t.*, (SELECT COUNT(*) FROM turnos_virtuales x WHERE x.fecha=t.fecha AND x.estado="en_espera" AND x.id<=t.id) posicion FROM turnos_virtuales t WHERE t.paciente_id=? AND t.fecha=CURDATE() AND t.estado="en_espera" ORDER BY t.id DESC LIMIT 1',[$p['id']]);$ahead=$t?max(0,(int)$t['posicion']-1):0;
$served=row('SELECT COUNT(*) total FROM turnos_virtuales WHERE fecha=CURDATE() AND estado="atendido"');
$waiting=row('SELECT COUNT(*) total FROM turnos_virtuales WHERE fecha=CURDATE() AND estado="en_espera"');
header_ui('Gestor de turnos');
?>
<div class="page-intro"><span class="eyebrow teal">TURNO VIRTUAL</span><h2>Tu tiempo también importa 🎟️</h2><p>Solicita un turno para consulta general, consulta tu lugar en la fila y evita esperar sin información.</p></div>
<div class="turn-stats"><div class="turn-stat"><span>◷</span><div><small>Personas en espera</small><strong><?= (int)($waiting['total']??0)?></strong></div></div><div class="turn-stat"><span>✓</span><div><small>Turnos atendidos hoy</small><strong><?= (int)($served['total']??0)?></strong></div></div><div class="turn-stat"><span>♡</span><div><small>Servicio</small><strong class="turn-service">Consulta general</strong></div></div></div>
<div class="turn-layout">
 <section class="panel turn-main">
 <?php if($t):?>
  <div class="turn-success">✓ Turno registrado correctamente</div>
  <p class="turn-overline">TU NÚMERO DE TURNO</p>
  <div class="turn-big-number"><?=str_pad($t['id'],4,'0',STR_PAD_LEFT)?></div>
  <span class="badge badge-mint">● En espera</span>
  <div class="turn-divider"></div>
  <div class="turn-position"><span>Tu posición aproximada en la fila</span><strong><?= (int)$t['posicion']?></strong></div>
  <div class="turn-progress"><span style="width:<?=max(8,100/(max(1,(int)$t['posicion'])))?>%"></span></div>
  <p class="turn-note"><?= $ahead===0?'Tu turno está entre los primeros en espera.':$ahead.' '.($ahead===1?'persona está':'personas están').' delante de ti.'?> El orden podría cambiar por prioridades clínicas.</p>
  <button class="btn btn-ghost btn-wide" onclick="location.reload()" type="button">↻ Actualizar mi posición</button>
 <?php else:?>
  <div class="turn-symbol">🎟️</div><h3>Solicita tu turno de atención</h3><p>Regístrate en la fila virtual de consulta general. El sistema generará un número de turno para hoy.</p>
  <div class="turn-patient-data"><div><small>Paciente</small><strong><?=e(full_name($u))?></strong></div><div><small>CURP</small><strong><?=e($p['curp'])?></strong></div><div><small>Teléfono de contacto</small><strong><?=e($u['telefono'])?></strong></div></div>
  <form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><button class="btn btn-primary btn-wide">Obtener mi turno →</button></form>
 <?php endif;?>
 </section>
 <aside class="panel turn-instructions"><h3>¿Cómo funciona?</h3><div class="turn-step"><span>1</span><div><strong>Solicita tu turno</strong><p>Utilizamos tus datos de paciente registrado para identificarte.</p></div></div><div class="turn-step"><span>2</span><div><strong>Consulta tu posición</strong><p>Regresa a esta sección para revisar el estado de tu turno.</p></div></div><div class="turn-step"><span>3</span><div><strong>Acude a tu consulta</strong><p>Espera las indicaciones del personal médico para pasar a consultorio.</p></div></div><div class="turn-soft-note">✦ Este sistema no sustituye los servicios de urgencias. Si necesitas atención inmediata, acude al servicio correspondiente.</div></aside>
</div>
<?php footer_ui();?>
