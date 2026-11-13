<?php
require_once __DIR__.'/../includes/layout.php';
require_once __DIR__.'/../includes/widgets.php';
require_once __DIR__.'/../includes/calendar_view.php';
$u=require_role('medico');
$m=row('SELECT m.*,u.nombre,u.apellido_paterno,u.apellido_materno FROM medicos m JOIN usuarios u ON u.id=m.usuario_id WHERE usuario_id=?',[$u['id']]);
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();
 $action=$_POST['action']??'';
 if($action==='schedule'){
  $day=(int)($_POST['dia']??0);$start=$_POST['inicio']??'';$end=$_POST['fin']??'';
  if($day<1||$day>7||!preg_match('/^\d\d:\d\d$/',$start)||!preg_match('/^\d\d:\d\d$/',$end)||$end<=$start)$error='Revisa el día y las horas.';
  else {db()->prepare('INSERT INTO horarios_medicos(medico_id,dia_semana,inicio,fin) VALUES(?,?,?,?)')->execute([$m['id'],$day,$start,$end]);flash('success','Horario guardado.');go('medico/agenda.php');}
 }elseif($action==='delete_schedule'){
  db()->prepare('DELETE FROM horarios_medicos WHERE id=? AND medico_id=?')->execute([(int)$_POST['id'],$m['id']]);flash('success','Horario eliminado.');go('medico/agenda.php');
 }elseif($action==='complete'){
  $cid=(int)($_POST['id']??0);$rec=trim($_POST['receta']??'');$c=row('SELECT * FROM citas WHERE id=? AND medico_id=? AND estado="programada"',[$cid,$m['id']]);
  if(!$c||mb_strlen($rec)>5000)$error='No se pudo registrar la consulta.';
  else{db()->beginTransaction();try{db()->prepare('UPDATE citas SET estado="atendida" WHERE id=? AND medico_id=?')->execute([$cid,$m['id']]);if($rec)db()->prepare('INSERT INTO recetas(cita_id,contenido) VALUES(?,?)')->execute([$cid,$rec]);db()->commit();flash('success','Consulta marcada como atendida.');go('medico/agenda.php');}catch(Throwable $ex){db()->rollBack();$error='Ocurrió un error al guardar.';}}
 }elseif($action==='expediente'){
  $pid=(int)($_POST['paciente_id']??0);
  $sexo=$_POST['sexo']??'';
  $domicilio=trim($_POST['domicilio']??'');
  $ocupacion=trim($_POST['ocupacion']??'');
  $contacto_nombre=trim($_POST['contacto_nombre']??'');
  $contacto_telefono=trim($_POST['contacto_telefono']??'');
  $allowed=['','Femenino','Masculino','No binario','Prefiero no decir'];
  $owns=row('SELECT c.id FROM citas c WHERE c.paciente_id=? AND c.medico_id=? LIMIT 1',[$pid,$m['id']]);
  if(!$owns)$error='No puedes editar el expediente de este paciente.';
  elseif(!in_array($sexo,$allowed,true))$error='Selecciona un sexo válido.';
  elseif($contacto_telefono!=='' && !preg_match('/^\d{10}$/',$contacto_telefono))$error='El teléfono del contacto de emergencia debe tener 10 dígitos.';
  elseif(mb_strlen($domicilio)>250 || mb_strlen($ocupacion)>120 || mb_strlen($contacto_nombre)>150)$error='Algunos campos exceden el tamaño permitido.';
  else{
   db()->prepare('UPDATE pacientes SET sexo=?,domicilio=?,ocupacion=?,contacto_emergencia_nombre=?,contacto_emergencia_telefono=? WHERE id=?')
     ->execute([$sexo?:null,$domicilio?:null,$ocupacion?:null,$contacto_nombre?:null,$contacto_telefono?:null,$pid]);
   flash('success','Expediente del paciente actualizado.');go('medico/agenda.php');
  }
 }
}
$dates=all('SELECT c.*,u.nombre,u.apellido_paterno,u.apellido_materno,u.telefono,p.id paciente_id,p.curp,p.fecha_nacimiento,p.sexo,p.domicilio,p.ocupacion,p.contacto_emergencia_nombre,p.contacto_emergencia_telefono,r.contenido receta,co.nombre consultorio FROM citas c JOIN pacientes p ON p.id=c.paciente_id JOIN usuarios u ON u.id=p.usuario_id LEFT JOIN recetas r ON r.cita_id=c.id LEFT JOIN consultorios co ON co.id=c.consultorio_id WHERE c.medico_id=? ORDER BY c.fecha DESC,c.hora DESC LIMIT 100',[$m['id']]);
$sch=all('SELECT * FROM horarios_medicos WHERE medico_id=? ORDER BY dia_semana,inicio',[$m['id']]);
$days=[1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'];
function edad_paciente(?string $fecha):string{ if(!$fecha) return '—'; try{$birth=new DateTime($fecha); return (string)$birth->diff(new DateTime('today'))->y.' años'; }catch(Throwable $e){ return '—'; } }
header_ui('Mi agenda');
?>
<div class="page-intro"><h2>Tu agenda y disponibilidad 🗓</h2><p>Configura cuándo atiendes y consulta tus pacientes programados.</p></div>
<?php if($error):?><div class="alert alert-error"><?=e($error)?></div><?php endif;?>
<div class="doctor-id-card panel" style="margin-bottom:16px"><div><span class="eyebrow teal">IDENTIFICACIÓN PROFESIONAL</span><h3 style="margin:6px 0 2px">Dr(a). <?=e(trim($u['nombre'].' '.$u['apellido_paterno'].' '.$u['apellido_materno']))?></h3><p class="muted" style="margin:0">Cédula profesional: <strong><?=e($m['cedula']?:'No registrada')?></strong></p></div><div class="badge badge-mint"><?=$m['verificado']?'✓ Verificado':'Pendiente de verificación'?></div></div>
<?php calendar_view('medico'); ?>
<section class="panel" style="margin:16px 0"><h2>Exportar agenda</h2><p class="muted">Descarga un listado de tus citas y pacientes en CSV o PDF. Los archivos contienen datos personales: guárdalos de forma segura.</p><form action="<?=url('medico/exportar.php')?>" method="get" class="form-grid"><label>Desde<input type="date" name="desde" value="<?=date('Y-01-01')?>" required></label><label>Hasta<input type="date" name="hasta" value="<?=date('Y-12-31')?>" required></label><div class="full" style="display:flex;gap:10px;flex-wrap:wrap"><button class="btn btn-ghost" name="formato" value="csv">Descargar CSV</button><button class="btn btn-primary" name="formato" value="pdf">Descargar PDF</button></div></form></section>
<div class="dashboard-grid">
 <div class="panel">
  <h2>Horarios de consulta</h2>
  <form method="post" class="form-stack"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="schedule"><label>Día<select name="dia"><?php foreach($days as $k=>$d):?><option value="<?=$k?>"><?=$d?></option><?php endforeach;?></select></label><div class="form-grid"><label>Desde<input type="time" name="inicio" value="09:00" step="1800" required></label><label>Hasta<input type="time" name="fin" value="14:00" step="1800" required></label></div><button class="btn btn-primary">+ Agregar horario</button></form>
  <div class="schedule-list"><?php foreach($sch as $s):?><div class="appointment-row"><div class="appointment-info"><strong><?=$days[$s['dia_semana']]?></strong><small><?=ui_time($s['inicio'])?> – <?=ui_time($s['fin'])?></small></div><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="delete_schedule"><input type="hidden" name="id" value="<?=$s['id']?>"><button class="link-danger">Eliminar</button></form></div><?php endforeach;?></div>
 </div>
 <div class="panel">
  <h2>Próximas consultas</h2><p class="muted">Las citas aparecen cuando el paciente las confirma.</p>
  <?php if(!$dates): empty_state('Agenda vacía','Aún no hay pacientes agendados.'); else: foreach($dates as $c): ?>
   <div class="appointment-row agenda-row">
    <div class="date-square"><strong><?=date('d',strtotime($c['fecha']))?></strong><span><?=date('M',strtotime($c['fecha']))?></span></div>
    <div class="appointment-info">
      <strong><?=e(trim($c['nombre'].' '.$c['apellido_paterno'].' '.$c['apellido_materno']))?></strong>
      <small><?=ui_time($c['hora'])?> · <?=e(ucfirst($c['estado']))?><?= $c['consultorio']?' · '.e($c['consultorio']):'' ?></small>
      <details class="receipt-detail patient-record-box"><summary>Ver expediente del paciente</summary>
        <div class="patient-record-grid">
          <div class="patient-record-card"><span>Nombre completo del paciente</span><strong><?=e(trim($c['nombre'].' '.$c['apellido_paterno'].' '.$c['apellido_materno']))?></strong></div>
          <div class="patient-record-card"><span>Número de cédula del doctor</span><strong><?=e($m['cedula'])?></strong></div>
          <div class="patient-record-card"><span>Sexo</span><strong><?=e($c['sexo']?:'No registrado')?></strong></div>
          <div class="patient-record-card"><span>Fecha de nacimiento y edad</span><strong><?=e(ui_date($c['fecha_nacimiento']))?> · <?=e(edad_paciente($c['fecha_nacimiento']))?></strong></div>
          <div class="patient-record-card"><span>Teléfono de contacto</span><strong><?=e($c['telefono'])?></strong></div>
          <div class="patient-record-card"><span>Domicilio completo</span><strong><?=e($c['domicilio']?:'No registrado')?></strong></div>
          <div class="patient-record-card"><span>Ocupación</span><strong><?=e($c['ocupacion']?:'No registrada')?></strong></div>
          <div class="patient-record-card"><span>Contacto de emergencia</span><strong><?=e($c['contacto_emergencia_nombre']?:'No registrado')?><?= $c['contacto_emergencia_telefono']?' · '.e($c['contacto_emergencia_telefono']):'' ?></strong></div>
        </div>
        <form method="post" class="form-stack patient-record-form"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="expediente"><input type="hidden" name="paciente_id" value="<?=$c['paciente_id']?>">
          <div class="form-grid">
            <label>Sexo<select name="sexo"><option value="">Sin especificar</option><?php foreach(['Femenino','Masculino','No binario','Prefiero no decir'] as $sx):?><option value="<?=$sx?>" <?=$c['sexo']===$sx?'selected':''?>><?=$sx?></option><?php endforeach;?></select></label>
            <label>Ocupación<input name="ocupacion" maxlength="120" value="<?=e($c['ocupacion'])?>" placeholder="Ej. Estudiante, comerciante"></label>
            <label class="full">Domicilio completo<input name="domicilio" maxlength="250" value="<?=e($c['domicilio'])?>" placeholder="Calle, número, colonia, ciudad"></label>
            <label>Nombre del familiar responsable o contacto de emergencia<input name="contacto_nombre" maxlength="150" value="<?=e($c['contacto_emergencia_nombre'])?>" placeholder="Nombre completo"></label>
            <label>Teléfono del contacto de emergencia<input name="contacto_telefono" maxlength="10" pattern="[0-9]{10}" value="<?=e($c['contacto_emergencia_telefono'])?>" placeholder="10 dígitos"></label>
          </div>
          <button class="btn btn-sm btn-primary">Guardar expediente</button>
        </form>
      </details>
      <?php if($c['estado']==='programada'):?><details class="receipt-detail"><summary>Finalizar consulta / receta</summary><form method="post" class="form-stack"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="complete"><input type="hidden" name="id" value="<?=$c['id']?>"><textarea name="receta" maxlength="5000" rows="3" placeholder="Indicaciones o receta (opcional)"></textarea><button class="btn btn-sm btn-primary">Marcar atendida</button></form></details><?php elseif($c['receta']):?><details class="receipt-detail"><summary>Ver receta guardada</summary><div class="summary-box"><p><?=nl2br(e($c['receta']))?></p></div></details><?php endif;?>
    </div>
   </div>
  <?php endforeach; endif; ?>
 </div>
</div>
<?php footer_ui(); ?>
