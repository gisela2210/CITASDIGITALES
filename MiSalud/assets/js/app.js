document.addEventListener('DOMContentLoaded',()=>{
 document.querySelectorAll('.toggle-pass').forEach(b=>b.addEventListener('click',()=>{const el=document.getElementById(b.dataset.target);if(!el)return;const showing=el.type==='password';el.type=showing?'text':'password';b.setAttribute('aria-pressed',String(showing));b.setAttribute('aria-label',showing?'Ocultar contraseña':'Mostrar contraseña');b.title=showing?'Ocultar contraseña':'Mostrar contraseña';}));
 const curp=document.getElementById('curp'),birth=document.getElementById('birth');
 if(curp&&birth){
  const f=()=>{
   const v=curp.value.toUpperCase();curp.value=v;
   let yr=Number(v.slice(4,6)),mm=Number(v.slice(6,8)),dd=Number(v.slice(8,10)),century=/\d/.test(v[16]||'')?1900:2000;
   const date=new Date(century+yr,mm-1,dd);
   birth.value=v.length===18&&date.getFullYear()===century+yr&&date.getMonth()===mm-1&&date.getDate()===dd?`${century+yr}-${String(mm).padStart(2,'0')}-${String(dd).padStart(2,'0')}`:'';
  };
  curp.addEventListener('input',f);f();
 }
});

document.addEventListener('DOMContentLoaded',()=>{
 const cfg=window.MISALUD_CALENDAR;if(!cfg)return;
 const $=id=>document.getElementById(id);
 const spec=$('specialty'),doc=$('doctor'),date=$('appointment-date'),time=$('appointment-time');
 const grid=$('booking-calendar'),message=$('calendar-message'),slots=$('time-slots'),doctorFeedback=$('doctor-feedback');
 if(!spec||!doc||!grid)return;
 const today=new Date();today.setHours(12,0,0,0);
 const limit=new Date(today);limit.setDate(limit.getDate()+90);
 let month=new Date(today.getFullYear(),today.getMonth(),1),reqMonth=0,reqHours=0;
 const pad=x=>String(x).padStart(2,'0');
 const fmt=d=>`${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
 const readable=s=>new Date(s+'T12:00:00').toLocaleDateString('es-MX',{day:'numeric',month:'long',year:'numeric'});
 const doctors=[...doc.querySelectorAll('option[data-specialty]')].map(o=>({id:o.value,specialty:o.dataset.specialty,office:o.dataset.office,name:o.textContent.trim()}));
 function summary(){
  $('sum-specialty').textContent=spec.value?spec.selectedOptions[0].textContent:'Por elegir';
  $('sum-doctor').textContent=doc.value?doc.selectedOptions[0].textContent:'Por elegir';
  $('sum-date').textContent=date.value?readable(date.value):'Por elegir';
  $('sum-time').textContent=time.value?time.value+' h':'Por elegir';
  $('sum-office').textContent=doc.value?doc.selectedOptions[0].dataset.office||'Por confirmar':'Por confirmar';
  $('confirm-booking').disabled=!(spec.value&&doc.value&&date.value&&time.value);
 }
 function clearHours(){reqHours++;time.value='';slots.replaceChildren();const item=document.createElement('span');item.className='time-placeholder';item.textContent='Selecciona un día disponible para ver horarios.';slots.append(item);summary();}
 function clearDate(){date.value='';$('selected-date-label').textContent='Selecciona un día del calendario';clearHours();}
 function rebuildDoctors(){
  const group=doctors.filter(d=>d.specialty===spec.value);
  doc.replaceChildren();const blank=new Option(spec.value?'Selecciona un médico':'Primero selecciona una especialidad','');doc.add(blank);
  group.forEach(d=>{const o=new Option(d.name,d.id);o.dataset.office=d.office;doc.add(o);});
  doc.disabled=!group.length;
  doctorFeedback.textContent=!spec.value?'Selecciona una especialidad para conocer a los médicos disponibles.':group.length?`${group.length} médico${group.length===1?'':'s'} disponible${group.length===1?'':'s'} en esta especialidad.`:'Aún no hay médicos habilitados en esta especialidad. El administrador debe asignar la especialidad y aprobar al médico.';
 }
 function renderDays(days){
  grid.replaceChildren();const year=month.getFullYear(),mon=month.getMonth();
  const lead=(new Date(year,mon,1).getDay()+6)%7;
  for(let i=0;i<lead;i++){const e=document.createElement('span');e.className='cal-empty';grid.append(e);}
  const count=new Date(year,mon+1,0).getDate();
  for(let d=1;d<=count;d++){
   const day=fmt(new Date(year,mon,d));const state=days[day]||'closed';
   const b=document.createElement('button');b.type='button';b.className='cal-day '+state+(day===date.value?' chosen':'');b.textContent=d;b.dataset.date=day;
   b.title=state==='available'?'Horarios disponibles':state==='full'?'Sin cupo':'No disponible';b.disabled=state!=='available';
   b.setAttribute('aria-pressed',String(day===date.value));b.setAttribute('aria-label',readable(day)+': '+b.title);
   if(!b.disabled)b.addEventListener('click',()=>selectDate(day));grid.append(b);
  }
 }
 async function loadMonth(){
  const run=++reqMonth;
  $('cal-month').textContent=month.toLocaleDateString('es-MX',{month:'long',year:'numeric'});
  $('cal-prev').disabled=month.getFullYear()===today.getFullYear()&&month.getMonth()===today.getMonth();
  $('cal-next').disabled=month>=new Date(limit.getFullYear(),limit.getMonth(),1);
  if(!doc.value){renderDays({});message.textContent='Selecciona un médico para consultar días disponibles.';return;}
  grid.innerHTML='<p class="calendar-loading">Consultando disponibilidad…</p>';
  try{
   const url=new URL(cfg.api,location.href);url.searchParams.set('medico',doc.value);url.searchParams.set('mes',fmt(month).slice(0,7));
   const res=await fetch(url,{credentials:'same-origin',headers:{Accept:'application/json'}});if(!res.ok)throw Error('request');
   const data=await res.json();if(run!==reqMonth)return;renderDays(data.dias||{});
   message.textContent=Object.values(data.dias||{}).includes('available')?'Selecciona un día verde para elegir tu horario.':'No hay horarios libres este mes. Puedes consultar otro mes o médico.';
  }catch(e){if(run!==reqMonth)return;renderDays({});message.textContent='No se pudo consultar la disponibilidad. Actualiza la página e intenta nuevamente.';}
 }
 async function selectDate(day){
  date.value=day;$('selected-date-label').textContent=readable(day);clearHours();
  grid.querySelectorAll('button.cal-day').forEach(b=>{const picked=b.dataset.date===day;b.classList.toggle('chosen',picked);b.setAttribute('aria-pressed',String(picked));});
  const run=++reqHours;slots.innerHTML='<span class="time-placeholder">Consultando horarios…</span>';
  try{
   const url=new URL(cfg.slots,location.href);url.searchParams.set('medico',doc.value);url.searchParams.set('fecha',day);
   const res=await fetch(url,{credentials:'same-origin',headers:{Accept:'application/json'}});if(!res.ok)throw Error('request');
   const result=await res.json();if(run!==reqHours)return;slots.replaceChildren();
   if(!result.horas?.length){slots.innerHTML='<span class="time-placeholder">Este día ya no tiene cupos. Elige otro día.</span>';date.value='';summary();await loadMonth();return;}
   result.horas.forEach(h=>{const b=document.createElement('button');b.type='button';b.className='time-choice';b.textContent=h+' h';b.addEventListener('click',()=>{time.value=h;slots.querySelectorAll('.time-choice').forEach(el=>{el.classList.remove('selected');el.setAttribute('aria-pressed','false');});b.classList.add('selected');b.setAttribute('aria-pressed','true');summary();});slots.append(b);});
   summary();
  }catch(e){if(run!==reqHours)return;slots.innerHTML='<span class="time-placeholder">No se pudieron consultar los horarios.</span>';}
 }
 spec.addEventListener('change',()=>{reqMonth++;clearDate();const preset=new URLSearchParams(location.search).get("especialidad");
 if(preset){const wanted=preset.toLowerCase();const match=[...spec.options].find(o=>o.textContent.trim().toLowerCase()===wanted);if(match) spec.value=match.value;}
 rebuildDoctors();summary();loadMonth();});
 doc.addEventListener('change',()=>{reqMonth++;clearDate();summary();loadMonth();});
 $('cal-prev').addEventListener('click',()=>{month.setMonth(month.getMonth()-1);clearDate();loadMonth();});
 $('cal-next').addEventListener('click',()=>{month.setMonth(month.getMonth()+1);clearDate();loadMonth();});
 $('booking-form').addEventListener('submit',e=>{if(!(doc.value&&date.value&&time.value)){e.preventDefault();message.textContent='Completa médico, fecha y horario antes de confirmar.';}});
 rebuildDoctors();summary();loadMonth();
});

window.addEventListener('DOMContentLoaded',()=>{
 const toggle=document.getElementById('assistant-toggle'),panel=document.getElementById('assistant-box');
 if(!toggle||!panel)return;
 const followup=document.getElementById('assistant-followup');
 const question=document.getElementById('assistant-question');
 const optionsWrap=document.getElementById('assistant-followup-options');
 const answer=document.getElementById('assistant-answer');
 const reset=document.getElementById('assistant-reset');
 const link=document.getElementById('assistant-link');
 const rootOptions=document.getElementById('assistant-root-options');
 const topics={
  piel:{q:'¿Qué describe mejor tu situación?',opts:[['comezon','Tengo irritación, manchas o comezón'],['cabello','Es sobre cabello o uñas'],['grave','Es una herida o alergia fuerte']],res:{comezon:['Dermatología','Dermatología suele ser la mejor opción para molestias en la piel como irritación, manchas o comezón.'],cabello:['Dermatología','Dermatología también puede orientar sobre cuero cabelludo, cabello y uñas.'],grave:['Medicina general','Si hay una reacción fuerte o una herida importante, empieza con medicina general o acude a urgencias si es severo.']},spec:'Dermatología'},
  ninos:{q:'¿La atención es para una persona menor de edad?',opts:[['si','Sí, es para un niño o adolescente'],['vacunas','Es por control, revisión o vacunas'],['no','No, es para un adulto']],res:{si:['Pediatría','Pediatría es la especialidad indicada para la atención infantil.'],vacunas:['Pediatría','Pediatría puede ayudarte con control de niño sano, revisiones y seguimiento general.'],no:['Medicina general','Si es una persona adulta y no sabes el área, puedes iniciar con medicina general.']},spec:'Pediatría'},
  corazon:{q:'¿Qué síntoma es el principal?',opts:[['presion','Presión alta o palpitaciones'],['dolor','Dolor en el pecho frecuente'],['duda','Solo quiero valoración preventiva']],res:{presion:['Cardiología','Cardiología puede orientarte en temas de presión, palpitaciones o control cardiovascular.'],dolor:['Cardiología','Si el dolor en el pecho es intenso o súbito, busca atención urgente de inmediato.'],duda:['Cardiología','Para una valoración preventiva del corazón, cardiología es una buena opción.']},spec:'Cardiología'},
  hormonas:{q:'¿Qué necesitas principalmente?',opts:[['peso','Control de peso y alimentación'],['azucar','Temas de azúcar, tiroides u hormonas'],['habitos','Mejorar hábitos y nutrición']],res:{peso:['Nutrición','Nutrición puede ayudarte a organizar un plan de alimentación y control de peso.'],azucar:['Endocrinología','Endocrinología suele ser la más adecuada para tiroides, hormonas o control metabólico.'],habitos:['Nutrición','Nutrición te orienta para mejorar tus hábitos alimenticios.']},spec:'Nutrición'},
  vista:{q:'¿Cuál se acerca más a tu motivo?',opts:[['revision','Revisión de vista o graduación'],['molestia','Ardor, visión borrosa o cansancio visual'],['dolor','Dolor fuerte o pérdida repentina de visión']],res:{revision:['Oftalmología','Oftalmología es ideal para revisar la vista y detectar cambios visuales.'],molestia:['Oftalmología','Oftalmología puede orientarte si hay visión borrosa, ardor o cansancio visual.'],dolor:['Oftalmología','Si existe dolor intenso o pérdida repentina de visión, acude de inmediato a valoración urgente.']},spec:'Oftalmología'},
  general:{q:'¿Cómo prefieres empezar?',opts:[['revision','Quiero una revisión general'],['sintomas','Tengo síntomas variados y no sé el área'],['orientacion','Solo quiero orientación inicial']],res:{revision:['Medicina general','Medicina general es un excelente primer paso para una revisión global.'],sintomas:['Medicina general','Cuando no está claro el área, medicina general ayuda a canalizarte correctamente.'],orientacion:['Medicina general','Puedes empezar con medicina general para recibir una primera valoración.']},spec:'Medicina general'}
 };
 function open(on){panel.hidden=!on;toggle.setAttribute('aria-expanded',String(on));}
 function resetAssistant(){followup.hidden=true;question.textContent='';optionsWrap.innerHTML='';answer.innerHTML='<div class="assistant-placeholder">Selecciona una opción para comenzar.</div>';rootOptions.querySelectorAll('button').forEach(btn=>btn.disabled=false);link.removeAttribute('data-specialty');}
 function showResult(topic,key){
   const entry=topics[topic]; if(!entry||!entry.res[key]) return;
   const [specialty,text]=entry.res[key];
   answer.innerHTML=`<div class="assistant-result"><strong>Área sugerida: ${specialty}</strong><p>${text}</p><small>Esto es solo una orientación general. Si hay una urgencia, acude a urgencias o llama al 911.</small></div>`;
   link.dataset.specialty=specialty;
 }
 function buildFollowup(topic){
   const entry=topics[topic]; if(!entry) return;
   followup.hidden=false; question.textContent=entry.q; optionsWrap.innerHTML='';
   entry.opts.forEach(([key,label])=>{
     const b=document.createElement('button');b.type='button';b.textContent=label;b.addEventListener('click',()=>showResult(topic,key));optionsWrap.append(b);
   });
   answer.innerHTML='<div class="assistant-placeholder">Elige la opción que mejor se parezca a tu caso.</div>';
 }
 toggle.addEventListener('click',()=>open(panel.hidden));
 document.getElementById('assistant-close')?.addEventListener('click',()=>open(false));
 reset?.addEventListener('click',resetAssistant);
 rootOptions?.querySelectorAll('[data-help]').forEach(btn=>btn.addEventListener('click',()=>buildFollowup(btn.dataset.help)));
 link?.addEventListener('click',e=>{
   const specialty=link.dataset.specialty;
   if(specialty){
     const url=new URL(link.href,location.href);url.searchParams.set('especialidad',specialty);link.href=url.toString();
   }
 });
 resetAssistant();
});

// Edad del médico calculada sin almacenar valores inconsistentes
document.addEventListener('DOMContentLoaded',()=>{
 const birth=document.getElementById('mp-fecha_nacimiento'),age=document.getElementById('doctor-age');
 if(!birth||!age)return;
 const update=()=>{
  if(!birth.value){age.value='No registrada';return;}
  const parts=birth.value.split('-').map(Number);
  const today=new Date();
  let years=today.getFullYear()-parts[0];
  if(today.getMonth()+1<parts[1]||(today.getMonth()+1===parts[1]&&today.getDate()<parts[2]))years--;
  age.value=years>=0&&years<130?years+' años':'Fecha no válida';
 };
 birth.addEventListener('change',update);update();
});

// Accesibilidad y mensajes por campo en el perfil médico
window.addEventListener('DOMContentLoaded',()=>{
 const form=document.getElementById('medico-profile-form');
 if(form){
  form.addEventListener('submit',e=>{
   form.querySelectorAll('.client-field-error').forEach(n=>n.remove());
   const invalid=[...form.querySelectorAll('input,select')].filter(el=>!el.disabled&&!el.checkValidity());
   if(invalid.length){
    e.preventDefault();
    invalid.forEach(el=>{
     let msg=el.validity.valueMissing?'Este campo es obligatorio.':el.validity.patternMismatch?'Revisa el formato solicitado.':'Verifica este valor.';
     el.setAttribute('aria-invalid','true');
     const p=document.createElement('p');p.className='field-error client-field-error';p.textContent=msg;
     el.closest('label')?.after(p);
    });
    invalid[0].focus({preventScroll:false});
   }
  });
 }
 const photo=document.getElementById('doctor-photo-input');
 photo?.addEventListener('change',()=>{
  if(!photo.files?.length)return;
  const f=photo.files[0];
  if(f.size>2097152||!['image/jpeg','image/png','image/webp'].includes(f.type)){alert('Solo se permiten fotos JPG, PNG o WebP de hasta 2 MB.');photo.value='';return;}
  document.getElementById('doctor-photo-form')?.requestSubmit();
 });
});
