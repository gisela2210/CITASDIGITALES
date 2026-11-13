<?php
require_once __DIR__.'/functions.php';
function header_ui(string $title,string $section=''):void{
 $u=current_user();$role=$u['rol']??'';
 $nav=[
  'paciente'=>[
    ['dashboard.php','Inicio','⌂'],
    ['agendar.php','Agendar cita','▦'],
    ['mis_citas.php','Mis citas / Calendario','◷'],
    ['mi_turno.php','Mi turno','♧'],
    ['donaciones.php','Donaciones','♡'],
    ['almacenamiento.php','Mis archivos','▣'],
    ['soporte.php','Soporte','⚑'],
  ],
  'medico'=>[
    ['dashboard.php','Inicio','⌂'],
    ['agenda.php','Mi calendario','▦'],
    ['reconocimiento.php','Mis logros','★'],
    ['almacenamiento.php','Mis archivos','▣'],
    ['soporte.php','Soporte / mi plan','⚑'],
  ],
  'admin'=>[
    ['dashboard.php','Panel general','▤'],
    ['medicos.php','Verificar médicos','✓'],
    ['usuarios.php','Usuarios','♙'],
    ['calendario.php','Calendario global','▦'],
    ['estadisticas.php','Estadísticas','◫'],
    ['turnos.php','Gestor de turnos','◷'],
    ['donaciones.php','Donaciones','♡'],
    ['soporte.php','Soporte y planes','⚑'],
  ]
 ];
?><!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?=e($title)?> · MiSalud</title><link rel="stylesheet" href="<?=url('assets/css/style.css?v=9')?>"><script src="<?=url('assets/js/app.js?v=9')?>" defer></script></head><body class="<?= $u?'has-app':'public-page' ?>">
<?php if($u): ?>
<aside class="sidebar">
  <a class="brand brand-side" href="<?=url($role.'/dashboard.php')?>"><img class="brand-logo" src="<?=url('assets/img/misalud-logo.svg')?>" alt="MiSalud"><span>Mi<span>Salud</span><small>Tu bienestar, a tiempo</small></span></a>
  <div class="side-label">MI ESPACIO</div>
  <nav class="side-links"><?php foreach($nav[$role]??[] as $n): ?><a class="<?=basename($_SERVER['PHP_SELF'])===$n[0]?'selected':''?>" href="<?=url($role.'/'.$n[0])?>"><span><?=e($n[2])?></span><?=e($n[1])?></a><?php endforeach; ?></nav>
  <?php $profileUrl=url($role.'/perfil.php'); $photo=($role==='medico') ? row('SELECT foto_perfil FROM medicos WHERE usuario_id=?',[$u['id']]) : null; ?>
  <div class="side-bottom"><a class="account-chip" href="<?=$profileUrl?>"><div class="avatar"><?php if($role==='medico' && !empty($photo['foto_perfil'])):?><img src="<?=url('foto_medico.php')?>" alt="Foto de perfil"><?php else:?><?=e(mb_substr($u['nombre'],0,1))?><?php endif;?></div><div><strong><?=e($u['nombre'])?></strong><small><?=e(ucfirst($role))?></small></div></a><a class="logout logout-clear" title="Salir de mi cuenta" href="<?=url('logout.php')?>">↪ <span>Cerrar sesión</span></a></div>
</aside>
<div class="app-main"><header class="topbar"><button class="mobile-menu" onclick="document.body.classList.toggle('nav-open')" aria-label="Menú">☰</button><div><span class="eyebrow">MI SALUD / <?=e(strtoupper($role))?></span><h1><?=e($title)?></h1></div><div class="top-actions"><span class="today-badge">☀ <?=date('d/m/Y')?></span><details class="account-dropdown"><summary class="round-profile account-avatar" aria-label="Abrir menú de cuenta"><?php if($role==='medico' && !empty($photo['foto_perfil'])):?><img src="<?=url('foto_medico.php')?>" alt="Foto de perfil"><?php else:?><?=e(mb_substr($u['nombre'],0,1))?><?php endif;?></summary><div class="account-menu"><strong><?=e(full_name($u))?></strong><small><?=e(ucfirst($role))?></small><a href="<?=$profileUrl?>">◉ Mi perfil</a><a href="<?=url('logout.php')?>">↪ Cerrar sesión</a></div></details></div></header><main class="content">
<?php else: ?>
<header class="public-header wrap"><a class="brand" href="<?=url('index.php')?>"><img class="brand-logo" src="<?=url('assets/img/misalud-logo.svg')?>" alt="MiSalud"><span>Mi<span>Salud</span></span></a><nav><a href="<?=url('index.php')?>">Inicio</a><a href="<?=url('login.php')?>">Iniciar sesión</a><a class="btn btn-sm btn-primary" href="<?=url('registro.php')?>">Crear cuenta →</a></nav></header>
<?php endif; ?>
<?php if($u && $role==='paciente'): ?>
<div id="assistant-trigger-wrap">
  <button type="button" class="assistant-trigger" id="assistant-toggle" aria-expanded="false" aria-controls="assistant-box">✦ Ayuda para elegir especialidad</button>
  <section class="assistant-box" id="assistant-box" hidden>
    <div class="assistant-head"><strong>Asistente MiSalud</strong><button type="button" id="assistant-close" aria-label="Cerrar asistente">×</button></div>
    <p>Te orientaré con unas preguntas rápidas para sugerirte una especialidad. No realizo diagnósticos médicos.</p>
    <div class="assistant-stage-title">¿Qué tipo de molestia o necesidad tienes?</div>
    <div class="assistant-options" id="assistant-root-options">
      <button type="button" data-help="piel">Piel o cabello</button>
      <button type="button" data-help="ninos">Atención infantil</button>
      <button type="button" data-help="corazon">Corazón / presión</button>
      <button type="button" data-help="hormonas">Peso / hormonas</button>
      <button type="button" data-help="vista">Vista</button>
      <button type="button" data-help="general">No estoy seguro</button>
    </div>
    <div id="assistant-followup" hidden>
      <div class="assistant-stage-title" id="assistant-question"></div>
      <div class="assistant-options assistant-options-followup" id="assistant-followup-options"></div>
    </div>
    <div id="assistant-answer" aria-live="polite"></div>
    <div class="assistant-actions">
      <button type="button" class="btn btn-ghost btn-sm" id="assistant-reset">Volver a empezar</button>
      <a class="btn btn-primary btn-sm" id="assistant-link" href="<?=url('paciente/agendar.php')?>">Ir a agendar cita</a>
    </div>
  </section>
</div>
<?php endif; ?>
<?php if(!empty($_SESSION['flash'])):$f=$_SESSION['flash'];unset($_SESSION['flash']);?><div class="alert alert-<?=e($f['type'])?>" role="alert"><?=e($f['message'])?></div><?php endif;?>
<?php }
function footer_ui():void { ?><?php if(current_user()): ?></main><footer class="app-footer">© <?=date('Y')?> MiSalud · Cuida de ti, nosotros te acompañamos</footer></div><?php else: ?><footer class="public-footer wrap">© <?=date('Y')?> MiSalud · Agenda médica inteligente</footer><?php endif; ?></body></html><?php }
