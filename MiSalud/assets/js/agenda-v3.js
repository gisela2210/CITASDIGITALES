document.addEventListener('DOMContentLoaded',()=>{
 const root=document.getElementById('unified-calendar');if(!root)return;
 const grid=root.querySelector('[data-days]'),monthLabel=root.querySelector('[data-month]'),detail=document.getElementById('unified-day-details');
 const filter=document.getElementById('calendar-doctor-filter');const api=root.dataset.api;
 const now=new Date(),view=new Date(now.getFullYear(),now.getMonth(),1);let selected=now.getDate(),events=[];
 const pad=n=>String(n).padStart(2,'0');const iso=(y,m,d)=>`${y}-${pad(m+1)}-${pad(d)}`;
 const status={programada:'Programada',atendida:'Atendida',cancelada:'Cancelada'};
 const safe=s=>{const e=document.createElement('span');e.textContent=String(s??'');return e.innerHTML;};
 function draw(){const y=view.getFullYear(),m=view.getMonth();monthLabel.textContent=view.toLocaleDateString('es-MX',{month:'long',year:'numeric'});grid.replaceChildren();
 const lead=(new Date(y,m,1).getDay()+6)%7;for(let i=0;i<lead;i++){const slot=document.createElement('div');slot.className='uc-empty';grid.append(slot);}
 const total=new Date(y,m+1,0).getDate();
 for(let d=1;d<=total;d++){
 const day=iso(y,m,d),matches=events.filter(e=>e.fecha===day),active=matches.filter(e=>e.estado==='programada');
 const btn=document.createElement('button');btn.type='button';btn.className='uc-day'+(selected===d?' uc-picked':'')+(day===iso(now.getFullYear(),now.getMonth(),now.getDate())?' uc-today':'');
 btn.innerHTML=`<span class="uc-num">${d}</span>${matches.length?`<span class="uc-dot">${active.length} activa${active.length===1?'':'s'}</span>`:''}`;
 btn.setAttribute('aria-label',`${d}, ${matches.length} citas`);btn.addEventListener('click',()=>{selected=d;draw();});grid.append(btn);
 }const day=iso(y,m,selected),items=events.filter(e=>e.fecha===day);detail.querySelector('[data-selected-day]').textContent=new Date(y,m,selected).toLocaleDateString('es-MX',{weekday:'long',day:'numeric',month:'long'});
 const area=detail.querySelector('[data-event-list]');area.replaceChildren();if(!items.length){area.innerHTML='<div class="uc-no-events">🌿 No tienes citas registradas para este día.</div>';return;}
 items.forEach(e=>{const el=document.createElement('article');el.className='uc-event';const name=root.dataset.role==='paciente'?`Dr(a). ${safe(e.medico)}`:root.dataset.role==='medico'?safe(e.paciente):`${safe(e.paciente)} · Dr(a). ${safe(e.medico)}`;
 el.innerHTML=`<span class="uc-hour">${safe(e.hora)}</span><div><strong>${name}</strong><small>${safe(e.especialidad)} · ${safe(e.consultorio)}</small></div><span class="uc-state ${safe(e.estado)}">${status[e.estado]||safe(e.estado)}</span>`;area.append(el);});
 }
 async function load(){grid.innerHTML='<div class="uc-loading">Cargando calendario…</div>';const params=new URLSearchParams({mes:iso(view.getFullYear(),view.getMonth(),1).slice(0,7)});if(filter?.value)params.set('medico',filter.value);
 try{const response=await fetch(`${api}?${params}`,{credentials:'same-origin',headers:{Accept:'application/json'}});if(!response.ok)throw new Error('error');const payload=await response.json();events=payload.eventos||[];draw();}catch(err){grid.innerHTML='<p class="uc-error">No fue posible consultar el calendario. Recarga la página.</p>';detail.querySelector('[data-event-list]').replaceChildren();}}
 root.querySelector('[data-prev]').addEventListener('click',()=>{view.setMonth(view.getMonth()-1);selected=1;load();});root.querySelector('[data-next]').addEventListener('click',()=>{view.setMonth(view.getMonth()+1);selected=1;load();});if(filter)filter.addEventListener('change',load);load();
});
