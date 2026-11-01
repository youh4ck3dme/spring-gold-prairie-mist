(function($){
'use strict';
const states={observed:'Zistené',not_disclosed:'Nevyplnené v podklade',not_configured:'Nenastavené',unsupported_search:'Zdroj nepodporuje zvolený identifikátor',not_checked:'Neoverené',not_found:'Bez nálezu v tomto zdroji',not_published:'Nezverejnené',not_applicable:'Nevzťahuje sa',source_unavailable:'Zdroj nedostupný',requires_access:'Vyžaduje prístup',partial:'Načítava sa ďalšia časť',stale:'Starší údaj',conflict:'Rozpor údajov',unconfirmed:'Nepotvrdené'};
const groups={registration:'Registrácia a adresy',activities:'Činnosti',people:'Osoby a funkcie',ownership:'Vlastníctvo',financials:'Financie a závierky',metrics:'Vypočítané ukazovatele',tax:'Dane a DPH',accounts:'Bankové účty',debts:'Zverejnené dlhy',proceedings:'Konania',public_sector:'Zmluvy a obstarávanie',subsidies:'Dotácie a pomoc',licenses:'Oprávnenia',intellectual_property:'Duševné vlastníctvo',contacts:'Kontakty',history:'História'};
const names={operating_revenue:'Výnosy z hospodárskej činnosti',service_sales:'Tržby výrobky a služby',goods_sales:'Tržby tovar',profit_after_tax:'Zisk po zdanení',profit_before_tax:'Zisk pred zdanením',assets:'Aktíva',equity:'Vlastné imanie',liabilities:'Záväzky',person_name:'Meno',name:'Názov',role:'Funkcia',valid_from:'Účinné od',valid_to:'Účinné do',amount:'Vklad',paid_capital:'Splatené imanie',register_court:'Registrový súd',register_number:'Registračné číslo',representation:'Konanie menom spoločnosti',statutaries:'Štatutárne osoby',shareholders:'Spoločníci',business_activities:'Predmety podnikania',nace_description:'Hlavná činnosť',vat_payer_from:'Platiteľ DPH od',paid:'Splatené',currency:'Mena',source_updated_at:'Zdroj aktualizovaný',retrieved_at:'Získané',vat_registry_match:'Nález v registri DPH',vat_registration_type:'Druh registrácie DPH',vat_payer:'Platiteľ DPH',registered_capital:'Základné imanie',registered_capital_paid:'Splatené imanie',registration_status:'Zápis v registri',registration_number:'Registračné číslo',registration_court:'Registrový súd',name:'Názov',ico:'IČO',dic:'DIČ',vat_id:'IČ DPH',status:'Právny stav',active:'Aktivita podľa zdroja',legal_form:'Právna forma',legal_form_code:'Kód právnej formy',address:'Sídlo',street:'Ulica',city:'Obec',postal_code:'PSČ',country:'Štát',founded_on:'Vznik',dissolved_on:'Zánik',nace_code:'NACE – kód zo zdroja',organization_size_code:'Veľkostná kategória – kód',ownership_type_code:'Druh vlastníctva – kód',region_code:'Kraj – kód',district_code:'Okres – kód',municipality_code:'Obec – kód',nazovUJ:'Názov účtovnej jednotky',obdobieOd:'Obdobie od',obdobieDo:'Obdobie do',datumPodania:'Podané',datumSchvalenia:'Schválené',datumZostavenia:'Zostavené',idSablony:'Identifikátor šablóny',mena:'Mena a jednotka',obsah:'Obsah výkazu',tabulky:'Tabuľky',prilohy:'Prílohy',data:'Hodnoty podľa šablóny',typ:'Typ',konsolidovana:'Konsolidovaná závierka'};
function label(k){return names[k]||k;}
function value(v,depth=0){
 if(v===null||v===undefined)return $('<span>').text('Nezistené');
 if(typeof v!=='object')return $('<span>').text(typeof v==='boolean'?(v?'Áno':'Nie'):String(v));
 if(depth>12)return $('<pre>').text(JSON.stringify(v,null,2));
 const dl=$('<dl class="atlas-values">');Object.entries(v).forEach(([k,x])=>{dl.append($('<dt>').text(label(k)),$('<dd>').append(value(x,depth+1)));});return dl;
}
function link(url,text){try{const u=new URL(url);if(u.protocol==='https:')return $('<a target="_blank" rel="noopener noreferrer">').attr('href',u.href).text(text);}catch(e){}return $('<span>').text(text);}
function evidence(e){
 const box=$('<div class="atlas-evidence">');
 if(e.value && typeof e.value==='object') box.append($('<details>').append($('<summary>').text('Pôvodné údaje zo zdroja'),value(e.value))); else box.append(value(e.value));
 const foot=$('<p class="description">').append(link(e.source_url,e.source||'Zdroj')).append(document.createTextNode(' · Aktualizácia zdroja: '+(e.source_updated_at||'Zdroj neuvádza')+(e.valid_from?' · Účinné od: '+e.valid_from:'')+(e.valid_to?' · Do: '+e.valid_to:'')+' · Získané: '+(e.retrieved_at||'Neznáme')+' · '+(states[e.availability_status]||e.availability_status||'Neoverené')));
 box.append(foot);return box;
}
function render(r){
 const out=$('#vorkei-quick-result').empty().show();
 if(!r.found){out.append($('<p role="status">').text(r.error||'Bez nálezu.')); if(r.pending)setTimeout(()=>{if(activeIco===r.ico)lookup(r.ico,false,true);},600);return;}
 out.append($('<h3>').text(r.name+' · '+r.ico));
 const legal=r.stale?'Uložený profil – aktuálne overenie sa nepodarilo':r.verified&&r.active===true?'Evidovaná spoločnosť bez zobrazeného výmazu':r.verified&&r.is_risky?'Zdroj uvádza neaktivitu alebo zánik':'Právny stav nepotvrdený';
 out.append($('<p class="atlas-status">').text(legal),$('<p>').text(r.status||''),$('<p class="description">').text('Zápis v registri nepotvrdzuje hospodársku aktivitu ani bezdlžnosť. Rozsah kontrol je uvedený nižšie.'));
 const p=r.profile||{}; const f=$('<details>').append($('<summary>').text('Základné údaje a pôvod'));
 Object.entries(p.fields||{}).filter(([k,e])=>!['status','active'].includes(k)&&!(k==='statutaries'&&(!Array.isArray(e.value)||!e.value.length))).forEach(([k,e])=>{let shown=e; if(['statutaries','shareholders'].includes(k)&&Array.isArray(e.value)) shown={...e,value:e.value.map(x=>(x.personName?.formatedName||x.personName?.formattedName||x.fullName||x.name||'')+(x.stakeholderType?.value?' — '+x.stakeholderType.value:'')).join('; ')};f.append($('<h4>').text(label(k)),evidence(shown));});out.append(f);
 renderFinancials(out,p);
 if(Object.keys(p.observations||{}).length)out.append($('<details>').append($('<summary>').text('Všetky pozorovania a ich zdroje'),value(p.observations)));
 const downloads=$('<p class="atlas-downloads">');[['json','Profil JSON'],['csv','Údaje a dôkazy CSV'],['financial_csv','Financie CSV']].forEach(([format,text])=>downloads.append($('<a class="button">').attr('href',vorkeiIcoAtlas.root+'vorkei-ico/v1/export?ico='+encodeURIComponent(r.ico)+'&format='+format+'&_wpnonce='+encodeURIComponent(vorkeiIcoAtlas.nonce)).text(text)));out.append(downloads);
 Object.entries(p.groups||{}).forEach(([k,g])=>{
 const d=$('<details>').append($('<summary>').text((groups[k]||k)+' · '+(states[g.availability_status]||g.availability_status)+' · '+(g.records||[]).length+' záznamov'));
 (g.records||[]).forEach((e,i)=>{const row=$('<details>').append($('<summary>').text((e.value?.obdobieOd?e.value.obdobieOd+' – '+e.value.obdobieDo+' · ':'')+(e.source_record_id||'Záznam '+(i+1))),evidence(e));
 (e.decoded_tables||[]).forEach(t=>{const wrap=$('<div class="atlas-financial-table">').append($('<h4>').text(t.name+' · '+(Array.isArray(t.current_period)?t.current_period:[t.current_period?.from,t.current_period?.to]).filter(Boolean).join(' – ')), $('<p class="description">').text('Mena / jednotky: '+(t.currency_and_unit||'Zdroj neuvádza'))); const table=$('<table class="widefat striped">').append($('<thead>').append($('<tr>').append($('<th>').text('Riadok'),$('<th>').text('Ukazovateľ'),$('<th>').text('Bežné obdobie'),$('<th>').text('Predchádzajúce obdobie')))); const body=$('<tbody>'); t.rows.forEach(x=>body.append($('<tr>').append($('<td>').text(x.row),$('<td>').text(x.label),$('<td>').text(x.current===null||x.current===''?'Nezverejnené':x.current),$('<td>').text(x.previous===null||x.previous===''?'Nezverejnené':x.previous))));table.append(body);wrap.append(table,link(t.template_url,'Oficiálna šablóna'));row.append(wrap);});
 (e.value?.prilohy||[]).forEach(a=>{if(/^\d+$/.test(String(a.id)))row.append($('<p>').append(link('https://www.registeruz.sk/cruz-public/domain/financialreport/attachment/'+a.id,a.meno||'Príloha')));});d.append(row);});out.append(d);
 });
 out.append($('<details>').append($('<summary>').text('Vykonané kontroly'),value(p.checks||{})));
 if(p.conflicts?.length)out.append($('<details>').append($('<summary>').text('Rozpory údajov'),value(p.conflicts)));
 const progress=$('<p role="status">');out.append(progress);
 if(r.pending && !p.checks?.ruz?.error){progress.text('Získavam ďalšie účtovné obdobia…');setTimeout(()=>{if(activeIco===r.ico)lookup(r.ico,false,true);},600);}
 else progress.text(p.checks?.ruz?.error||'Dokončené zdroje sú uvedené v kontrolách. Nepripojené oblasti zostávajú neoverené.');
}
function renderFinancials(out,p){
 const rows=(p.groups?.financials?.records||[]).filter(x=>!x.superseded&&x.normalized_facts&&Object.keys(x.normalized_facts).length);
 if(!rows.length)return;
 rows.sort((a,b)=>String(Object.values(a.normalized_facts)[0].period?.from||'').localeCompare(String(Object.values(b.normalized_facts)[0].period?.from||'')));
 const box=$('<section class="atlas-financial-table atlas-summary">').append($('<h3>').text('Financie podľa podaných závierok'));
 const table=$('<table class="widefat striped">');const keys=['operating_revenue','profit_after_tax','assets','equity'];
 table.append($('<thead>').append($('<tr>').append($('<th>').text('Obdobie'),...keys.map(k=>$('<th>').text(label(k))),$('<th>').text('Podklad'))));
 const body=$('<tbody>');
 rows.forEach(r=>{const f=r.normalized_facts;const first=Object.values(f)[0];const period=first.period||{};const tr=$('<tr>').append($('<td>').text((period.from||'?')+' – '+(period.to||'?')));
 keys.forEach(k=>{const v=f[k]?.value;tr.append($('<td>').text(v===null||v===undefined?'Nevyplnené':String(v)+' '+(f[k].currency==='EUR'?'€':f[k].currency||'')));});
 tr.append($('<td>').append(link(r.source_url,'RÚZ '+(first.report_id||''))));body.append(tr);});
 table.append(body);box.append(table,$('<p class="description">').text('Nevyplnené bunky zostávajú neznáme. Vykázaná nula je odlišná hodnota. Historické obdobia nemusia zachytávať dnešný stav firmy.'));out.append(box);
}
let busy=false, activeIco=null;
function lookup(ico,refresh,continuation=false){if(busy)return;busy=true;activeIco=ico;$('#vorkei-search-spinner').addClass('is-active');
 $.ajax({url:vorkeiIcoAtlas.root+'vorkei-ico/v1/lookup',data:{ico,refresh:refresh?'true':'false',continue:continuation?'true':'false'},headers:{'X-WP-Nonce':vorkeiIcoAtlas.nonce}}).done(render).fail(e=>{$('#vorkei-quick-result').show().empty().append($('<p role="alert">').text(e.responseJSON?.error||e.responseJSON?.message||'Spojenie zlyhalo; uložené údaje zostávajú zachované.'));}).always(()=>{busy=false;$('#vorkei-search-spinner').removeClass('is-active');});}
$(function(){
 $('#vorkei-fs-discover').on('click',function(){const result=$('#vorkei-fs-result').empty().text('Načítavam metadáta uloženým kľúčom…');$.ajax({url:vorkeiIcoAtlas.root+'vorkei-ico/v1/sources',headers:{'X-WP-Nonce':vorkeiIcoAtlas.nonce}}).done(r=>{result.empty().append(value(r));}).fail(()=>result.text('Metadáta sa nepodarilo načítať. Skontrolujte uložený kľúč.'));});
 $('#vorkei-btn-quick-lookup').on('click',()=>lookup(String($('#vorkei-quick-ico').val()).trim(),true));
 $('#vorkei-quick-ico').on('keydown',e=>{if(e.key==='Enter')lookup(String(e.target.value).trim(),true);});
 $('.vorkei-row-audit').on('click',function(){const ico=String($(this).data('ico'));$('#vorkei-quick-ico').val(ico);lookup(ico,true);});
 $('#vorkei-run-batch-audit').on('click',function(){const btn=$(this).prop('disabled',true);$('#vorkei-batch-progress').show();
 function next(job){$.ajax({url:vorkeiIcoAtlas.root+'vorkei-ico/v1/batch-audit',method:'POST',contentType:'application/json',data:JSON.stringify({limit:1,job_id:job}),headers:{'X-WP-Nonce':vorkeiIcoAtlas.nonce}}).done(r=>{
 $('#vorkei-progress-status').text('Spracované '+r.processed+' / '+r.total+' · Chyby '+r.failed);$('#vorkei-progress-fill').css('width',(r.total?r.processed/r.total*100:100)+'%');
 if(!r.done)setTimeout(()=>next(r.job_id),800);else{btn.prop('disabled',false);$('#vorkei-progress-status').append(document.createTextNode(' · Dokončené. Detail firmy pokračuje v načítaní starších závierok po otvorení.'));}
 }).fail(()=>{btn.prop('disabled',false);$('#vorkei-progress-status').text('Audit prerušený. Uložené výsledky zostávajú zachované.');});}next('');
 });
});
})(jQuery);
