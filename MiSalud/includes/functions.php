<?php
require_once __DIR__.'/../config/database.php';
// Configuración de la aplicación: errores técnicos al registro, no a la pantalla del paciente.
date_default_timezone_set('America/Mexico_City');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
set_exception_handler(function(Throwable $ex):void {
 error_log('[MiSalud] '.$ex);
 if(!headers_sent()) http_response_code(500);
 $dir=str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/MiSalud/index.php')); if(in_array(basename($dir),['paciente','medico','admin','api'],true))$dir=dirname($dir); $home=rtrim($dir,'/').'/index.php';
 echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MiSalud · Aviso</title><style>body{font-family:Arial,sans-serif;margin:0;min-height:100vh;display:grid;place-items:center;background:linear-gradient(135deg,#edf9f8,#f3f0ff);color:#254c5e}.box{margin:18px;background:white;border-radius:24px;padding:42px;max-width:460px;text-align:center;box-shadow:0 14px 50px #12575c18}.symbol{font-size:40px}a{display:inline-block;margin-top:18px;padding:12px 22px;background:#0eaa9e;color:#fff;text-decoration:none;border-radius:12px}p{color:#617e8b;line-height:1.7}</style></head><body><div class="box"><div class="symbol">♡</div><h1>No pudimos completar la solicitud</h1><p>Ocurrió un problema temporal. Intenta nuevamente. Si persiste, contacta al administrador de MiSalud.</p><a href="'.$home.'">Volver al inicio</a></div></body></html>';
});

if(session_status()!==PHP_SESSION_ACTIVE){session_start();}
function e($v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function url($path=''):string{
 $dir=str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/MiSalud/index.php'));
 if(in_array(basename($dir),['paciente','medico','admin','api'],true))$dir=dirname($dir);
 $base=rtrim($dir,'/');
 return ($base?:'').'/'.ltrim($path,'/');
}
function go($path):never{header('Location: '.url($path));exit;}
function csrf():string{if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));return $_SESSION['csrf'];}
function check_csrf():void{if(!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')){http_response_code(419);exit('La sesión del formulario expiró. Regresa y recarga la página.');}}
function flash($type,$message):void{$_SESSION['flash']=['type'=>$type,'message'=>$message];}
function current_user():?array{
 if(empty($_SESSION['uid']))return null;
 $stmt=db()->prepare('SELECT id,nombre,apellido_paterno,apellido_materno,correo,telefono,rol,activo FROM usuarios WHERE id=?');$stmt->execute([$_SESSION['uid']]);$u=$stmt->fetch();return ($u&&$u['activo'])?$u:null;
}
function require_role($roles):array{$u=current_user();if(!$u)go('login.php');if(!in_array($u['rol'],(array)$roles,true)){http_response_code(403);exit('No tienes permiso para esta sección.');}return $u;}
function full_name($u):string{return trim($u['nombre'].' '.($u['apellido_paterno']??'').' '.($u['apellido_materno']??''));}
function user_id():int{return (int)($_SESSION['uid']??0);}
function curp_valid(string $s):bool{return (bool)preg_match('/^[A-ZÑ&]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/u',strtoupper($s));}
function birth_from_curp(string $s):?string{
 $year=(int)substr($s,4,2);$m=(int)substr($s,6,2);$d=(int)substr($s,8,2);
 $century=ctype_digit(substr($s,16,1))?1900:2000; // Convención habitual CURP; confirmar contra documento
 $yyyy=$century+$year;return checkdate($m,$d,$yyyy)?sprintf('%04d-%02d-%02d',$yyyy,$m,$d):null;
}
function valid_pass(string $p):bool{return strlen($p)>=9&&preg_match('/[A-Z]/',$p)&&preg_match('/[a-z]/',$p)&&preg_match('/\d/',$p)&&preg_match('/[^A-Za-z0-9]/',$p);}
function row(string $sql,array $args=[]):?array{$q=db()->prepare($sql);$q->execute($args);return $q->fetch()?:null;}
function all(string $sql,array $args=[]):array{$q=db()->prepare($sql);$q->execute($args);return $q->fetchAll();}
function count_rows(string $table):int{if(!in_array($table,['usuarios','pacientes','medicos','citas','especialidades','donaciones'],true))return 0;return (int)db()->query("SELECT COUNT(*) FROM $table")->fetchColumn();}
function ui_date($date):string{return $date?date('d/m/Y',strtotime($date)):'—';}
function ui_time($time):string{return $time?substr($time,0,5):'—';}
