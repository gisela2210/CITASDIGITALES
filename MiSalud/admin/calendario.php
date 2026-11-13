<?php require_once __DIR__.'/../includes/layout.php';require_once __DIR__.'/../includes/calendar_view.php';require_role('admin');header_ui('Calendario general'); ?>
<div class="page-intro"><span class="eyebrow teal">CONTROL DE CITAS</span><h2>Agenda general del hospital 🗓️</h2><p>Supervisa todas las consultas agendadas, atendidas y canceladas. Filtra por médico y selecciona cada fecha para ver los detalles.</p></div>
<?php calendar_view('admin');footer_ui(); ?>
