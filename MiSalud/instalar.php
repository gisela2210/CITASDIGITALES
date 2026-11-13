<?php
/**
 * Instalador local de MiSalud. Uso exclusivo en localhost/XAMPP.
 * No destruye ni vacía ninguna tabla existente.
 * Eliminar este archivo después de instalar.
 */
$ip=$_SERVER['REMOTE_ADDR']??'';
if(!in_array($ip,['127.0.0.1','::1'],true)){
 http_response_code(403);exit('El instalador solo puede usarse desde localhost.');
}
if(session_status()!==PHP_SESSION_ACTIVE) session_start();
if(empty($_SESSION['install_csrf'])) $_SESSION['install_csrf']=bin2hex(random_bytes(32));
require_once __DIR__.'/config/database.php';
$done=false;$error='';$events=[];
$need=['usuarios','especialidades','consultorios','pacientes','medicos','horarios_medicos','citas','recetas','turnos_virtuales','donaciones','planes_servicio','planes_usuarios','archivos_usuario','tickets_soporte'];
$doctorExtra=['telefono_emergencia','domicilio','fecha_nacimiento','foto_perfil','cedula_documento','direccion_calle','direccion_numero_exterior','direccion_numero_interior','direccion_colonia','direccion_codigo_postal','direccion_municipio','direccion_estado'];
$patientExtra=['sexo','domicilio','ocupacion','contacto_emergencia_nombre','contacto_emergencia_telefono'];
function check_install():array{
 global $need,$patientExtra,$doctorExtra;
 try{
  $dbh=new PDO('mysql:host='.DB_HOST.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
  $stmt=$dbh->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=?');$stmt->execute([DB_NAME]);$names=$stmt->fetchAll(PDO::FETCH_COLUMN);
  $missing=array_values(array_diff($need,$names));
  $cols=[];
  if(in_array('pacientes',$names,true)){
   $stmt=$dbh->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=?');$stmt->execute([DB_NAME,'pacientes']);$cols=$stmt->fetchAll(PDO::FETCH_COLUMN);
  }
  $medCols=[];
  if(in_array('medicos',$names,true)){
   $stmt=$dbh->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=?');$stmt->execute([DB_NAME,'medicos']);$medCols=$stmt->fetchAll(PDO::FETCH_COLUMN);
  }
  return ['connected'=>true,'tables'=>count(array_intersect($need,$names)),'missing'=>$missing,'columns'=>array_values(array_diff($patientExtra,$cols)),'doctor_columns'=>array_values(array_diff($doctorExtra,$medCols))];
 }catch(Throwable $e){
  error_log('[MiSalud setup] '.$e->getMessage());
  return ['connected'=>false,'tables'=>0,'missing'=>$need,'columns'=>$patientExtra,'doctor_columns'=>$doctorExtra];
 }
}
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($_SESSION['install_csrf'],$_POST['csrf']??'')){$error='Actualiza la página y vuelve a intentarlo.';}
 else{
  try{
   $dbh=new PDO('mysql:host='.DB_HOST.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
   $sql=file_get_contents(__DIR__.'/database/misalud.sql');
   if($sql===false) throw new RuntimeException('No se encontró el archivo SQL.');
   // Sentencias DDL y INSERT; comentarios completos se descartan previamente.
   $sql=preg_replace('/^\s*--[^\r\n]*$/m','',$sql);
   foreach(explode(';',$sql) as $statement){
    $statement=trim($statement);
    if($statement==='') continue;
    $dbh->exec($statement);
    $events[]='OK';
   }
   $done=true;
  }catch(Throwable $e){
   error_log('[MiSalud install] '.$e->getMessage());
   $error='La instalación no terminó. Revisa que MySQL esté encendido y que el usuario/contraseña en config/database.php sean correctos. Si persiste, revisa el registro de errores de PHP/XAMPP.';
  }
 }
}
$status=check_install();
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalación local de MiSalud</title><style>body{font-family:system-ui,Arial,sans-serif;background:#f3f8fa;color:#234758;margin:0;padding:32px 12px}.wrap{max-width:680px;margin:0 auto;background:#fff;border:1px solid #dcebee;border-radius:22px;padding:32px;box-shadow:0 20px 50px #23475513}h1{font-size:28px;margin:0 0 10px}p{line-height:1.6;color:#557280}.ok{background:#e2f7ef;color:#126851;border-radius:12px;padding:12px 16px;margin:15px 0}.bad{background:#fff0f2;color:#a63d54;border-radius:12px;padding:12px 16px;margin:15px 0}button,a.button{display:inline-block;background:linear-gradient(110deg,#10a998,#386fb2);color:white;text-decoration:none;border:none;border-radius:12px;padding:14px 22px;font-weight:750;cursor:pointer}li{margin:7px 0}code{background:#edf4f5;padding:2px 5px;border-radius:4px}small{display:block;margin-top:22px;color:#647d89}</style></head><body><div class="wrap"><h1>💙 Instalación de MiSalud</h1><p>Un solo proyecto, una sola base de datos. Esta herramienta revisa la instalación local y completa las tablas y columnas faltantes <strong>sin borrar citas ni pacientes existentes</strong>.</p>
<?php if($done):?><div class="ok">✅ Se ejecutaron <?=count($events)?> sentencias SQL. Revisa el estado de abajo.</div><?php endif; ?>
<?php if($error):?><div class="bad"><?=htmlspecialchars($error,ENT_QUOTES,'UTF-8')?></div><?php endif; ?>
<div class="<?= $status['connected']?'ok':'bad' ?>"><?= $status['connected']?'✅ Conexión a MariaDB disponible':'⚠️ No se pudo conectar a MariaDB con los datos actuales' ?></div>
<ul><li>Tablas principales encontradas: <strong><?=$status['tables']?> de <?=count($need)?></strong></li><li>Tablas pendientes: <strong><?=count($status['missing'])?></strong></li><li>Campos pendientes del expediente: <strong><?=count($status['columns'])?></strong></li><li>Campos pendientes del médico: <strong><?=count($status['doctor_columns'])?></strong></li></ul>
<?php if($status['missing']):?><p>Tablas pendientes: <?=htmlspecialchars(implode(', ',$status['missing']),ENT_QUOTES,'UTF-8')?></p><?php endif;?>
<?php if($status['columns']):?><p>Campos pendientes: <?=htmlspecialchars(implode(', ',$status['columns']),ENT_QUOTES,'UTF-8')?></p><?php endif;?>
<?php if($status['doctor_columns']):?><p>Campos pendientes del médico: <?=htmlspecialchars(implode(', ',$status['doctor_columns']),ENT_QUOTES,'UTF-8')?></p><?php endif;?>
<?php if($status['connected']&&!$status['missing']&&!$status['columns']&&!$status['doctor_columns']):?><div class="ok">🎉 La estructura básica está lista. Si aún no tienes usuarios de prueba, utiliza el botón de abajo para cargarlos.</div><a class="button" href="index.php">Abrir MiSalud</a><?php endif;?>
<form method="post" style="margin-top:18px"><input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['install_csrf'],ENT_QUOTES,'UTF-8')?>"><button type="submit">Instalar o actualizar e incluir cuentas demo</button></form>
<p>Las cuentas de prueba y el acceso administrativo aparecen en <code>README_DEMO.txt</code>. Si una cuenta ya existe, esta instalación no le cambia la contraseña.</p>
<small>⚠️ Antes de reparar una base existente, expórtala desde phpMyAdmin. Cuando termines, elimina <code>instalar.php</code> y <code>setup_admin.php</code> de tu carpeta del servidor.</small></div></body></html>
