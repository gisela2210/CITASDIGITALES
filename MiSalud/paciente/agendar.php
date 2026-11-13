<?php require_once __DIR__.'/../includes/layout.php';$u=require_role('paciente');$error='';if($_SERVER['REQUEST_METHOD']==='POST'){check_csrf();$mid=(int)($_POST['medico_id']??0);$fecha=$_POST['fecha']??'';$hora=$_POST['hora']??'';$m=row('SELECT m.*,u.activo FROM medicos m JOIN usuarios u ON u.id=m.usuario_id WHERE m.id=?',[$mid]);$validDate=DateTime::createFromFormat('!Y-m-d',$fecha);$validTime=preg_match('/^\d{2}:\d{2}$/',$hora);$weekday=$validDate?(int)$validDate->format('N'):0;$schedule=($m&&$validDate&&$validTime)?row('SELECT * FROM horarios_medicos WHERE medico_id=? AND dia_semana=? AND ? >= inicio AND ? < fin',[$mid,$weekday,$hora,$hora]):null;
 if(!$m||!$m['activo']||!$m['verificado'])$error='Selecciona un médico verificado.';
 elseif(!$validDate||$validDate->format('Y-m-d')!==$fecha||$fecha<date('Y-m-d')||$fecha>date('Y-m-d',strtotime('+90 days')))$error='Selecciona una fecha válida dentro de los próximos 90 días.';
 elseif(!$validTime||!$schedule||((int)substr($hora,3,2)%30)!==0)$error='Ese horario no está disponible.';
 elseif($fecha===date('Y-m-d')&&$hora<=date('H:i'))$error='Selecciona una hora futura.';
 else{try{$q=db()->prepare('INSERT INTO citas(paciente_id,medico_id,consultorio_id,fecha,hora,estado) VALUES((SELECT id FROM pacientes WHERE usuario_id=?),?,?,?,?,"programada")');$q->execute([$u['id'],$mid,$m['consultorio_id']?:null,$fecha,$hora]);flash('success','Tu cita quedó agendada correctamente.');go('paciente/mis_citas.php');}catch(PDOException $ex){$error='Ese horario acaba de ocuparse o ya tienes una cita con el mismo médico a esa hora.';}}
}
$specs=all('SELECT * FROM especialidades ORDER BY nombre');
$meds=all('SELECT m.id,m.especialidad_id,m.verificado,u.nombre,u.apellido_paterno,e.nombre especialidad,co.nombre consultorio FROM medicos m JOIN usuarios u ON u.id=m.usuario_id LEFT JOIN especialidades e ON e.id=m.especialidad_id LEFT JOIN consultorios co ON co.id=m.consultorio_id WHERE m.verificado=1 AND u.activo=1 AND m.especialidad_id IS NOT NULL ORDER BY u.nombre');
header_ui('Agendar una cita');
?>
<div class="page-intro"><span class="eyebrow teal">AGENDA MÉDICA</span><h2>Tu próxima consulta comienza aquí ✨</h2><p>Encuentra al profesional ideal y reserva un espacio disponible en unos cuantos pasos.</p></div>
<?php if($error):?><div class="alert alert-error"><?=e($error)?></div><?php endif;?>
<form method="post" class="booking-experience" id="booking-form">
 <input type="hidden" name="csrf" value="<?=csrf()?>">
 <input type="hidden" id="appointment-date" name="fecha" required>
 <input type="hidden" id="appointment-time" name="hora" required>
 <section class="panel booking-main">
  <div class="wizard-progress"><span class="active">01 <b>Especialidad</b></span><span>02 <b>Profesional</b></span><span>03 <b>Fecha y hora</b></span></div>
  <div class="booking-section"><span class="section-index">01</span><div><h3>¿Qué especialidad necesitas?</h3><p>Selecciona el tipo de atención médica.</p></div></div>
  <label class="field-label" for="specialty">Especialidad médica</label>
  <select class="booking-select" id="specialty" required><option value="">Elegir especialidad</option><?php foreach($specs as $sp):?><option value="<?=$sp['id']?>"><?=e($sp['nombre'])?></option><?php endforeach;?></select>
  <div class="booking-section"><span class="section-index">02</span><div><h3>Elige a tu médico</h3><p>Solo se muestran profesionales verificados y activos.</p></div></div>
  <label class="field-label" for="doctor">Médico disponible</label>
  <select class="booking-select" id="doctor" name="medico_id" required><option value="">Primero selecciona una especialidad</option><?php foreach($meds as $m):?><option value="<?=$m['id']?>" data-specialty="<?=$m['especialidad_id']?>" data-office="<?=e($m['consultorio']??'Por confirmar')?>">Dr(a). <?=e($m['nombre'].' '.$m['apellido_paterno'])?> ✓</option><?php endforeach;?></select>
  <div class="verified-hint">✦ Aquí solo aparecen médicos activos y habilitados por administración. Su cédula debe verificarse de manera independiente.</div><p class="booking-help" id="doctor-feedback" role="status">Selecciona una especialidad para conocer a los médicos disponibles.</p>
  <div class="booking-section"><span class="section-index">03</span><div><h3>Escoge fecha y horario</h3><p>Los días verdes tienen horarios libres. Los rojos están completos.</p></div></div>
  <div class="calendar-shell">
   <div class="calendar-top"><button type="button" class="cal-nav" id="cal-prev" aria-label="Mes anterior">‹</button><h3 id="cal-month">Calendario</h3><button type="button" class="cal-nav" id="cal-next" aria-label="Mes siguiente">›</button></div>
   <div class="calendar-weekdays"><span>Lun</span><span>Mar</span><span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span><span>Dom</span></div>
   <div id="booking-calendar" class="calendar-days" role="group" aria-label="Días disponibles"></div>
   <div class="calendar-legend"><span><i class="legend-green"></i> Disponible</span><span><i class="legend-red"></i> Completo</span><span><i class="legend-gray"></i> Sin consulta</span></div>
   <p class="calendar-feedback" id="calendar-message" aria-live="polite">Selecciona una especialidad y un médico para consultar su agenda.</p>
  </div>
  <div class="time-slot-area"><div class="time-slot-head"><h3>Horarios disponibles</h3><span id="selected-date-label">Selecciona un día del calendario</span></div><div id="time-slots" class="time-slots"><span class="time-placeholder">Aquí aparecerán los horarios libres.</span></div></div>
 </section>
 <aside class="panel booking-aside"><div class="booking-aside-title">♡ Resumen de tu cita</div><p>Revisa tus datos antes de confirmar.</p>
  <div class="booking-detail"><span>Especialidad</span><strong id="sum-specialty">Por elegir</strong></div>
  <div class="booking-detail"><span>Profesional</span><strong id="sum-doctor">Por elegir</strong></div>
  <div class="booking-detail"><span>Fecha</span><strong id="sum-date">Por elegir</strong></div>
  <div class="booking-detail"><span>Hora</span><strong id="sum-time">Por elegir</strong></div>
  <div class="booking-detail"><span>Consultorio</span><strong id="sum-office">Por confirmar</strong></div>
  <div class="summary-friendly">🔒 Tus datos se utilizarán únicamente para gestionar tu cita.</div>
  <button class="btn btn-primary btn-wide" type="submit" id="confirm-booking" disabled>Confirmar cita →</button>
  <small class="booking-tip">Los horarios se actualizan al consultar la agenda. La reservación queda confirmada al guardar.</small>
 </aside>
</form>
<script>window.MISALUD_CALENDAR={api:"<?=url('api/calendario.php')?>",slots:"<?=url('api/disponibilidad.php')?>"};</script>
<?php footer_ui();?>
