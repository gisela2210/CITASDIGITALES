<?php
require_once __DIR__.'/layout.php';
function show_profile(string $role):void {
 $u=require_role($role);
 $profile=$role==='paciente'?row('SELECT * FROM pacientes WHERE usuario_id=?',[$u['id']]):row('SELECT * FROM medicos WHERE usuario_id=?',[$u['id']]);
 $errors=[];$values=[];
 $addrFields=['calle'=>'Calle','numero_exterior'=>'Número exterior','numero_interior'=>'Número interior','colonia'=>'Colonia','codigo_postal'=>'Código postal','municipio'=>'Municipio o alcaldía','estado'=>'Estado'];
 if($role==='medico'){
  foreach($addrFields as $key=>$label)$values[$key]=(string)($profile['direccion_'.$key]??'');
  if(!$values['calle'] && !empty($profile['domicilio']))$values['calle']=$profile['domicilio'];
  $values['telefono_emergencia']=(string)($profile['telefono_emergencia']??'');
  $values['fecha_nacimiento']=(string)($profile['fecha_nacimiento']??'');
 }
 $values['correo']=(string)$u['correo'];$values['telefono']=(string)$u['telefono'];
 $values['especialidad_id']=(string)($profile['especialidad_id']??'');
 $values['consultorio_id']=(string)($profile['consultorio_id']??'');
 if($_SERVER['REQUEST_METHOD']==='POST'){
  check_csrf();
  $fields=$role==='medico'?array_merge(array_keys($addrFields),['telefono_emergencia','fecha_nacimiento','especialidad_id','consultorio_id']):[];
  foreach(array_merge(['correo','telefono'],$fields) as $key)$values[$key]=trim((string)($_POST[$key]??''));
  if($values['correo']==='')$errors['correo']='Es obligatorio escribir tu correo electrónico.';
  elseif(!filter_var($values['correo'],FILTER_VALIDATE_EMAIL))$errors['correo']='Escribe un correo electrónico válido.';
  if($values['telefono']==='')$errors['telefono']='Es obligatorio escribir tu teléfono celular.';
  elseif(!preg_match('/^\d{10}$/',$values['telefono']))$errors['telefono']='El teléfono celular debe tener exactamente 10 dígitos.';
  $pass=(string)($_POST['password']??'');
  if($pass!==''&&!valid_pass($pass))$errors['password']='Utiliza al menos 9 caracteres, mayúscula, minúscula, número y símbolo.';
  if($role==='medico'){
   if($values['especialidad_id']==='')$errors['especialidad_id']='Selecciona tu especialidad.';
   elseif(!ctype_digit($values['especialidad_id'])||!row('SELECT id FROM especialidades WHERE id=?',[(int)$values['especialidad_id']]))$errors['especialidad_id']='Selecciona una especialidad válida.';
   if($values['consultorio_id']!==''&&(!ctype_digit($values['consultorio_id'])||!row('SELECT id FROM consultorios WHERE id=?',[(int)$values['consultorio_id']])))$errors['consultorio_id']='Selecciona un consultorio válido.';
   if($values['telefono_emergencia']==='')$errors['telefono_emergencia']='Indica un teléfono de emergencia.';
   elseif(!preg_match('/^\d{10}$/',$values['telefono_emergencia']))$errors['telefono_emergencia']='El teléfono de emergencia debe tener 10 dígitos.';
   foreach($addrFields as $k=>$label){
    if($k!=='numero_interior'&&$values[$k]==='')$errors[$k]='Completa el campo '.$label.'.';
    elseif(mb_strlen($values[$k])>($k==='calle'?100:($k==='numero_interior'||$k==='numero_exterior'?20:($k==='codigo_postal'?5:80))))$errors[$k]='El campo '.$label.' es demasiado largo.';
   }
   if($values['codigo_postal']!==''&&!preg_match('/^\d{5}$/',$values['codigo_postal']))$errors['codigo_postal']='El código postal debe contener exactamente 5 números.';
   if($values['fecha_nacimiento']==='')$errors['fecha_nacimiento']='Selecciona tu fecha de nacimiento.';
   else{
    $birth=DateTimeImmutable::createFromFormat('!Y-m-d',$values['fecha_nacimiento']);
    if(!$birth||$birth->format('Y-m-d')!==$values['fecha_nacimiento']||$birth>new DateTimeImmutable('today')||$birth<new DateTimeImmutable('1900-01-01'))$errors['fecha_nacimiento']='Ingresa una fecha de nacimiento válida.';
   }
   $f=$_FILES['documento_cedula']??null;
   if(empty($profile['cedula_documento'])&&(!$f||$f['error']===UPLOAD_ERR_NO_FILE))$errors['documento_cedula']='Debes adjuntar el documento de tu cédula profesional.';
   if($f && $f['error']!==UPLOAD_ERR_NO_FILE){
    if($f['error']!==UPLOAD_ERR_OK||!is_uploaded_file($f['tmp_name']))$errors['documento_cedula']='No se recibió correctamente el documento. Intenta subirlo de nuevo.';
    elseif($f['size']<1||$f['size']>5242880)$errors['documento_cedula']='La cédula debe pesar entre 1 byte y 5 MB.';
    elseif(!in_array((new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']),['application/pdf','image/jpeg','image/png'],true))$errors['documento_cedula']='La cédula debe estar en formato PDF, JPG o PNG.';
   }
  }
  if(!$errors){
   $oldFile=null;$newFile=null;
   try{
    if($role==='medico'&&isset($f)&&$f['error']===UPLOAD_ERR_OK){
     $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
     $ext=['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png'][$mime];
     $newFile=bin2hex(random_bytes(20)).'.'.$ext;
     $path=__DIR__.'/../private_storage/'.$newFile;
     if(!move_uploaded_file($f['tmp_name'],$path))throw new RuntimeException('No se pudo guardar el documento de cédula. Revisa permisos de la carpeta private_storage.');
     $oldFile=$profile['cedula_documento']??null;
    }
    db()->beginTransaction();
    db()->prepare('UPDATE usuarios SET correo=?,telefono=? WHERE id=?')->execute([$values['correo'],$values['telefono'],$u['id']]);
    if($pass!=='')db()->prepare('UPDATE usuarios SET password_hash=? WHERE id=?')->execute([password_hash($pass,PASSWORD_DEFAULT),$u['id']]);
    if($role==='paciente'){
     $peso=($_POST['peso']??'')!==''?(float)$_POST['peso']:null;
     $altura=($_POST['altura']??'')!==''?(float)$_POST['altura']:null;
     if(($peso!==null&&($peso<1||$peso>500))||($altura!==null&&($altura<0.3||$altura>2.7)))throw new RuntimeException('Revisa peso y altura.');
     db()->prepare('UPDATE pacientes SET peso=?,altura=? WHERE usuario_id=?')->execute([$peso,$altura,$u['id']]);
    }else{
     $address=$values['calle'].' '.$values['numero_exterior'].($values['numero_interior']!==''?' Int. '.$values['numero_interior']:'').', '.$values['colonia'].', C.P. '.$values['codigo_postal'].', '.$values['municipio'].', '.$values['estado'];
     if(mb_strlen($address)>250) throw new RuntimeException('El domicilio completo excede el límite de 250 caracteres.');
     $sql='UPDATE medicos SET telefono_emergencia=?,domicilio=?,fecha_nacimiento=?,direccion_calle=?,direccion_numero_exterior=?,direccion_numero_interior=?,direccion_colonia=?,direccion_codigo_postal=?,direccion_municipio=?,direccion_estado=?,especialidad_id=?,consultorio_id=?,verificado=CASE WHEN NOT (especialidad_id <=> ?) OR ? IS NOT NULL THEN 0 ELSE verificado END';
     $params=[$values['telefono_emergencia'],$address,$values['fecha_nacimiento'],$values['calle'],$values['numero_exterior'],$values['numero_interior']?:null,$values['colonia'],$values['codigo_postal'],$values['municipio'],$values['estado'],(int)$values['especialidad_id'],$values['consultorio_id']!==''?(int)$values['consultorio_id']:null,(int)$values['especialidad_id'],$newFile];
     if($newFile!==null){$sql.=',cedula_documento=?';$params[]=$newFile;}
     $sql.=' WHERE usuario_id=?';$params[]=$u['id'];
     db()->prepare($sql)->execute($params);
    }
    db()->commit();
    if($oldFile&&$newFile)@unlink(__DIR__.'/../private_storage/'.basename($oldFile));
    flash('success','Tu perfil se guardó correctamente.');go($role.'/perfil.php');
   }catch(Throwable $ex){
    if(db()->inTransaction())db()->rollBack();
    if($newFile)@unlink(__DIR__.'/../private_storage/'.$newFile);
    error_log('[MiSalud perfil] '.$ex->getMessage());
    $errors['general']='No se pudo guardar. Revisa que hayas instalado los campos nuevos en la base de datos desde instalar.php. Si el problema continúa, revisa el registro de errores del servidor.';
   }
  }
 }
 $specs=$role==='medico'?all('SELECT * FROM especialidades ORDER BY nombre'):[];
 $rooms=$role==='medico'?all('SELECT * FROM consultorios ORDER BY nombre'):[];
 $edad='No registrada';
 if($role==='medico'&&!empty($values['fecha_nacimiento']))try{$edad=(new DateTimeImmutable($values['fecha_nacimiento']))->diff(new DateTimeImmutable('today'))->y.' años';}catch(Throwable $e){}
 header_ui('Mi perfil');
 ?>
 <div class="page-intro doctor-profile-intro"><h2>Tu información personal ⚙</h2><p>Los campos con <strong class="required-star">*</strong> son obligatorios.</p></div>
 <section class="panel narrow-panel <?= $role==='medico'?'doctor-profile-centered':'' ?>">
 <?php if($errors): ?><div class="alert alert-error profile-alert" role="alert"><strong>Revisa los campos antes de guardar:</strong><ul><?php foreach($errors as $msg):?><li><?=e($msg)?></li><?php endforeach;?></ul></div><?php endif; ?>
 <div class="profile-banner">
 <?php if($role==='medico'): ?>
 <form id="doctor-photo-form" class="doctor-photo-form" method="post" enctype="multipart/form-data" action="<?=url('medico/actualizar_foto.php')?>">
  <input type="hidden" name="csrf" value="<?=csrf()?>">
  <label class="doctor-photo-button" for="doctor-photo-input" title="Cambiar foto de perfil">
   <?php if(!empty($profile['foto_perfil'])):?><img class="doctor-profile-photo" src="<?=url('foto_medico.php')?>" alt="Foto del médico"><?php else:?><span class="big-avatar"><?=e(mb_substr($u['nombre'],0,1))?></span><?php endif;?>
   <span class="avatar-camera" aria-hidden="true">✎</span>
  </label>
  <input id="doctor-photo-input" class="visually-hidden" type="file" name="foto_perfil" accept=".jpg,.jpeg,.png,.webp" aria-label="Subir fotografía de perfil" required>
 </form>
 <?php else: ?><div class="big-avatar"><?=e(mb_substr($u['nombre'],0,1))?></div><?php endif;?>
 <div><strong><?=e(full_name($u))?></strong><p><?=e(ucfirst($role))?><?php if($role==='medico'):?> · <?=$profile['verificado']?'✓ Verificado':'Pendiente de verificación'?> · Cédula: <?=e($profile['cedula']??'No registrada')?><?php endif;?></p><?php if($role==='medico'):?><small class="muted">Haz clic en tu foto para cambiarla (JPG, PNG o WebP, máximo 2 MB).</small><?php endif;?></div>
 </div>
 <form method="post" enctype="multipart/form-data" class="form-stack" id="medico-profile-form" novalidate>
 <input type="hidden" name="csrf" value="<?=csrf()?>">
 <?php
 $field=function(string $k,string $label,string $type='text',string $extra='',bool $required=true)use(&$values,&$errors):void{
  $id='mp-'.$k;$val=$values[$k]??'';
  echo '<label for="'.e($id).'">'.e($label).($required?' <span class="required-star">*</span>':'').'<input id="'.e($id).'" name="'.e($k).'" type="'.e($type).'" value="'.e($val).'" '.($required?'required ':'').$extra.' aria-invalid="'.(isset($errors[$k])?'true':'false').'" aria-describedby="error-'.e($k).'"></label>';
  if(isset($errors[$k]))echo '<p class="field-error" id="error-'.e($k).'">'.e($errors[$k]).'</p>';
 };
 $field('correo','Correo electrónico','email','autocomplete="email"');
 $field('telefono','Teléfono celular','tel','maxlength="10" pattern="[0-9]{10}" inputmode="numeric" autocomplete="tel"');
 if($role==='paciente'):
 ?>
 <div class="form-grid"><label>Peso (kg)<input name="peso" type="number" step="0.1" min="1" max="500" value="<?=e($profile['peso'])?>"></label><label>Altura (m)<input name="altura" type="number" step="0.01" min="0.3" max="2.7" value="<?=e($profile['altura'])?>"></label></div>
 <?php else: ?>
 <label for="mp-especialidad_id">Especialidad <span class="required-star">*</span><select name="especialidad_id" id="mp-especialidad_id" required aria-invalid="<?=isset($errors['especialidad_id'])?'true':'false'?>"><option value="">Seleccionar especialidad</option><?php foreach($specs as $sp):?><option value="<?=$sp['id']?>" <?=$values['especialidad_id']==$sp['id']?'selected':''?>><?=e($sp['nombre'])?></option><?php endforeach;?></select></label><?php if(isset($errors['especialidad_id'])):?><p class="field-error"><?=e($errors['especialidad_id'])?></p><?php endif;?>
 <label>Consultorio (asignado por administración)<select name="consultorio_id"><option value="">Por asignar</option><?php foreach($rooms as $room):?><option value="<?=$room['id']?>" <?=$values['consultorio_id']==$room['id']?'selected':''?>><?=e($room['nombre'])?></option><?php endforeach;?></select></label>
 <label>Cédula profesional <span class="required-star">*</span><input value="<?=e($profile['cedula']??'')?>" disabled aria-label="Cédula profesional registrada"></label>
 <h3 class="doctor-profile-subtitle">Identificación y contacto</h3>
 <label>Documento de cédula <span class="required-star"><?=empty($profile['cedula_documento'])?'*':''?></span><input name="documento_cedula" type="file" accept=".pdf,.jpg,.jpeg,.png" <?=empty($profile['cedula_documento'])?'required':''?> aria-invalid="<?=isset($errors['documento_cedula'])?'true':'false'?>"><small>PDF, JPG o PNG · máximo 5 MB</small></label><?php if(isset($errors['documento_cedula'])):?><p class="field-error"><?=e($errors['documento_cedula'])?></p><?php endif;?>
 <?php if(!empty($profile['cedula_documento'])):?><a class="btn btn-ghost btn-sm" href="<?=url('documento_medico.php')?>">Consultar cédula guardada</a><?php endif;?>
 <?php $field('telefono_emergencia','Teléfono de emergencia','tel','maxlength="10" pattern="[0-9]{10}" inputmode="numeric"'); ?>
 <h3 class="doctor-profile-subtitle">Domicilio</h3>
 <div class="doctor-address-grid">
 <?php $field('calle','Calle','text','maxlength="100"'); ?>
 <?php $field('numero_exterior','Número exterior','text','maxlength="20"'); ?>
 <?php $field('numero_interior','Número interior','text','maxlength="20"',false); ?>
 <?php $field('colonia','Colonia','text','maxlength="80"'); ?>
 <?php $field('codigo_postal','Código postal','text','maxlength="5" pattern="[0-9]{5}" inputmode="numeric"'); ?>
 <?php $field('municipio','Municipio o alcaldía','text','maxlength="80"'); ?>
 <?php $field('estado','Estado','text','maxlength="80"'); ?>
 </div>
 <div class="form-grid"><div><?php $field('fecha_nacimiento','Fecha de nacimiento','date','max="'.date('Y-m-d').'"'); ?></div><label>Edad (automática)<input id="doctor-age" value="<?=e($edad)?>" readonly></label></div>
 <?php endif; ?>
 <label for="mp-password">Nueva contraseña (opcional)<input id="mp-password" name="password" type="password" autocomplete="new-password" placeholder="Déjalo vacío para conservar tu contraseña"></label>
 <?php if(isset($errors['password'])):?><p class="field-error"><?=e($errors['password'])?></p><?php endif;?>
 <button class="btn btn-primary">Guardar cambios →</button>
 <p class="form-note">Al actualizar el documento de cédula, su verificación volverá a quedar pendiente.</p>
 </form></section>
 <?php footer_ui();
}
