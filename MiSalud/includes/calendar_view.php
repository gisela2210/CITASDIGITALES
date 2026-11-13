<?php
/** Requiere includes/layout.php y usuario autenticado. */
function calendar_view(string $role):void{ ?>
<section class="panel uc-panel" id="unified-calendar" data-role="<?=e($role)?>" data-api="<?=e(url('api/eventos.php'))?>">
 <div class="uc-heading"><div><span class="eyebrow teal">CALENDARIO CONECTADO</span><h2>Mis citas en el calendario</h2><p>Selecciona un día para consultar los detalles registrados.</p></div><div class="uc-controls"><button type="button" class="cal-nav" data-prev aria-label="Mes anterior">‹</button><strong data-month>Cargando…</strong><button type="button" class="cal-nav" data-next aria-label="Mes siguiente">›</button></div></div>
 <?php if($role==='admin'): $meds=all('SELECT m.id,u.nombre,u.apellido_paterno FROM medicos m JOIN usuarios u ON u.id=m.usuario_id ORDER BY u.nombre'); ?><label class="uc-filter">Filtrar médico <select id="calendar-doctor-filter"><option value="">Todos los médicos</option><?php foreach($meds as $md):?><option value="<?=$md['id']?>"><?=e($md['nombre'].' '.$md['apellido_paterno'])?></option><?php endforeach;?></select></label><?php endif; ?>
 <div class="calendar-weekdays uc-week"><span>Lun</span><span>Mar</span><span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span><span>Dom</span></div><div data-days class="uc-days"></div>
 <div class="uc-footer"><span><i class="uc-legend"></i> Citas activas</span><span>Selecciona un día para ver los detalles</span></div>
</section><section class="panel uc-detail" id="unified-day-details"><h2>Agenda del <span data-selected-day>día seleccionado</span></h2><div data-event-list aria-live="polite"></div></section>
<script src="<?=e(url('assets/js/agenda-v3.js'))?>" defer></script>
<?php }
