<?php
if (!defined('ABSPATH')) {exit;}

/** Official RPO JSON and ORSR HTML adapters. No configured proxy is a legal register. */
class Vorkei_Ico_Atlas_Registers {
 const RPO='https://api.statistics.sk/rpo/v1/';
 const ORSR='https://www.orsr.sk/';
 private $deadline=0.0;
 private $refresh=false;

 public function enrich(string $ico,array $profile,bool $refresh=false): array {
  if(!preg_match('/^[0-9]{8}$/D',$ico)) {return $profile;}
  $this->deadline=microtime(true)+20; $this->refresh=$refresh;
  $p=Vorkei_Ico_Atlas_Profile::blank($ico);
  $this->rpo($ico,$p);
  $this->orsr($ico,$p);
  return Vorkei_Ico_Atlas_Profile::merge($profile,$p);
 }

 private function fetch(string $url,string $format): array {
  $key='vorkei_atlas_register_'.md5($url);
  $cached=$this->refresh?false:get_transient($key);
  if(is_array($cached) && isset($cached['body'],$cached['retrieved_at'])) {$cached['cache_reused']=true;return $cached;}
  $remaining=$this->deadline-microtime(true);
  if($remaining<0.5) {throw new RuntimeException('Časový rozpočet registra bol vyčerpaný.');}
  $r=wp_safe_remote_get($url,['timeout'=>min(5,$remaining),'redirection'=>2,'sslverify'=>true,'limit_response_size'=>2097152,'headers'=>['Accept'=>$format==='json'?'application/json':'text/html']]);
  if(is_wp_error($r)) {throw new RuntimeException('Oficiálny register je dočasne nedostupný.');}
  $code=(int)wp_remote_retrieve_response_code($r);$body=wp_remote_retrieve_body($r);
  if($code!==200) {throw new RuntimeException('Oficiálny register vrátil HTTP '.$code.'.');}
  if($format==='json') {
   $body=json_decode($body,true);
   if(!is_array($body)) {throw new RuntimeException('Register nevrátil platný JSON.');}
  } elseif(!is_string($body)||stripos($body,'<html')===false) {throw new RuntimeException('Register nevrátil platnú HTML stránku.');}
  $result=['body'=>$body,'retrieved_at'=>gmdate('c'),'cache_reused'=>false];
  set_transient($key,$result,3600);return $result;
 }

 private function rpo(string $ico,array &$p): void {
  $search=self::RPO.'search?identifier='.rawurlencode($ico);$url=$search;
  try {
   $s=$this->fetch($search,'json');$data=$s['body'];
   if(!isset($data['results'])||!is_array($data['results'])) {throw new RuntimeException('RPO vrátil neznámu štruktúru výsledkov.');}
   $matches=[];
   foreach($data['results'] as $v) {if(is_array($v)&&$this->has_identifier($v,$ico)&&isset($v['id'])&&ctype_digit((string)$v['id'])) {$matches[(string)$v['id']]=$v;}}
   if(!$matches) {$p['checks']['rpo']=$this->check('not_found',$search,$s);return;}
   if(count($matches)!==1) {$p['checks']['rpo']=$this->check('conflict',$search,$s)+['error'=>'Rovnaké IČO sa zhoduje s viacerými záznamami RPO.'];return;}
   $id=(string)array_key_first($matches);$url=self::RPO.'entity/'.$id.'?showHistoricalData=true';
   $r=$this->fetch($url,'json');$d=$r['body'];
   if(!$this->has_identifier($d,$ico)||(string)($d['id']??'')!==$id) {throw new RuntimeException('Identita detailu RPO sa nezhoduje s hľadaným IČO.');}
   $asof=$this->date($d['dbModificationDate']??null);
   $this->record($p,'registration',['record_type'=>'rpo_entity','source_record'=>$this->without_placeholder_dates($d)],'RPO',$url,'entity:'.$id,$asof,$r);
   $collections=['fullNames'=>'registration','addresses'=>'registration','legalForms'=>'registration','activities'=>'activities','statutoryBodies'=>'people','stakeholders'=>'ownership','authorizations'=>'registration','equities'=>'ownership','deposits'=>'ownership'];
   $current=[];
   foreach($collections as $key=>$group) {
    if(!is_array($d[$key]??null)) {continue;}
    foreach($d[$key] as $row) {
     if(!is_array($row)) {continue;}
     $from=$this->date($row['validFrom']??null);$to=$this->date($row['validTo']??null);$isCurrent=$this->current($row);
     $value=['record_type'=>$key,'is_current'=>$isCurrent,'source_record'=>$this->without_placeholder_dates($row)];
     if(in_array($key,['statutoryBodies','stakeholders','deposits'],true)) {$value+=['name'=>$this->person_name($row),'role'=>$row['stakeholderType']['value']??null,'identifier'=>$row['identifier']??null,'address'=>isset($row['address'])?$this->address($row['address']):null];}
     if($key==='activities') {$value['description']=$row['economicActivityDescription']??null;}
     $rid='entity:'.$id.':'.$key.':'.substr(hash('sha256',wp_json_encode($row)),0,24);
     $this->record($p,$group,$value,'RPO',$url,$rid,$asof,$r,$from,$to);
     if(!$isCurrent) {$this->record($p,'history',$value,'RPO',$url,$rid,$asof,$r,$from,$to);}
     else {$current[$key][]=$row;}
    }
   }
   foreach(['fullNames'=>'name','legalForms'=>'legal_form'] as $source=>$field) {
    $v=$this->latest($current[$source]??[]);$value=$v['value']??null;
    if(is_array($value)) {$value=$value['value']??null;}
    $this->field($p,$field,$value,'RPO',$url,$id,$asof,$r,$v);
   }
   $lf=$this->latest($current['legalForms']??[]);
   $this->field($p,'legal_form_code',$lf['value']['code']??null,'RPO',$url,$id,$asof,$r,$lf);
   $a=$this->latest($current['addresses']??[]);
   if($a) {
    $this->field($p,'address',$this->address($a),'RPO',$url,$id,$asof,$r,$a);
    foreach(['street'=>trim(($a['street']??'').' '.($a['buildingNumber']??'')),'city'=>$a['municipality']['value']??null,'postal_code'=>$a['postalCodes'][0]??null,'country'=>$a['country']['value']??null] as $k=>$v) {$this->field($p,$k,$v,'RPO',$url,$id,$asof,$r,$a);}
   }
   $this->field($p,'founded_on',$this->date($d['establishment']??null),'RPO',$url,$id,$asof,$r);
   $end=$this->date($d['termination']??null);
   $this->field($p,'dissolved_on',$end,'RPO',$url,$id,$asof,$r);
   // Existence in RPO alone is not a claim of commercial activity or a clean risk check.
   if($end && $end<=gmdate('Y-m-d')) {$this->field($p,'registration_status','terminated','RPO',$url,$id,$asof,$r);}
   foreach(['statutoryBodies'=>'statutaries','stakeholders'=>'shareholders','activities'=>'business_activities','authorizations'=>'representation'] as $key=>$field) {
    if(!empty($current[$key])) {$this->field($p,$field,$this->without_placeholder_dates($current[$key]),'RPO',$url,$id.':'.$key,$asof,$r);}
   }
   foreach($current['equities']??[] as $v) {
    foreach(['value'=>'registered_capital','valuePaid'=>'paid_capital'] as $a=>$field) {if(isset($v[$a])) {$this->field($p,$field,['amount'=>$v[$a],'currency'=>$v['currency']['code']??null],'RPO',$url,$id.':'.$field,$asof,$r,$v);}}
   }
   $sr=$d['sourceRegister']??[];
   foreach(['registrationOffices'=>'register_court','registrationNumbers'=>'register_number'] as $key=>$field) {$v=$this->latest(array_values(array_filter($sr[$key]??[],[$this,'current'])));$this->field($p,$field,$v['value']??null,'RPO',$url,$id,$asof,$r,$v);}
   $stats=$d['statisticalCodes']??[];$statsDate=$this->date($stats['statCodesActualization']??null);
   foreach(['nace_code'=>$stats['mainActivity']['code']??null,'nace_description'=>$stats['mainActivity']['value']??null,'esa2010_code'=>$stats['esa2010']['code']??null] as $key=>$v) {$this->field($p,$key,$v,'RPO',$url,$id,$statsDate,$r);}
   $p['checks']['rpo']=$this->check('observed',$url,$r)+['source_updated_at'=>$asof,'record_id'=>$id,'historical_data_requested'=>true];
  } catch(Throwable $e) {$p['checks']['rpo']=['availability_status'=>'source_unavailable','checked_at'=>gmdate('c'),'source_url'=>$url,'error'=>$e->getMessage()];}
 }

 private function orsr(string $ico,array &$p): void {
  $search=self::ORSR.'hladaj_ico.asp?ICO='.rawurlencode($ico).'&SID=0';$url=$search;
  try {
   $s=$this->fetch($search,'html');$doc=$this->dom($s['body']);$xp=new DOMXPath($doc);$links=[];
   foreach($xp->query('//a[@href]') as $a) {
    $href=html_entity_decode($a->getAttribute('href'),ENT_QUOTES,'UTF-8');
    $parts=parse_url($href);$path=ltrim($parts['path']??'','/');
    if($path!=='vypis.asp'||(!empty($parts['host'])&&!in_array(strtolower($parts['host']),['orsr.sk','www.orsr.sk'],true))) {continue;}
    parse_str($parts['query']??'',$q);
    if(!ctype_digit((string)($q['ID']??''))||!ctype_digit((string)($q['SID']??''))||(string)($q['P']??'0')!=='0'||isset($q['lan'])) {continue;}
    $candidate=self::ORSR.'vypis.asp?ID='.$q['ID'].'&SID='.$q['SID'].'&P=0';$links[$candidate]=(int)$q['ID'];
   }
   if(!$links) {
    $text=$this->plain($doc->documentElement);
    if(preg_match('/(?:Žiadne|žiadne|nenájden|nenašiel|Záznamy:\s*0)/u',$text)) {$p['checks']['orsr']=$this->check('not_found',$search,$s);return;}
    throw new RuntimeException('ORSR nevrátil rozpoznateľný výsledok vyhľadávania.');
   }
   arsort($links,SORT_NUMERIC);$issues=[];$transferred=[];$primaries=[];$fetchFailed=false;
   foreach(array_slice(array_keys($links),0,3) as $candidate) {
    $url=$candidate;
    try {
     $r=$this->fetch($candidate,'html');$parsed=$this->parse_orsr($r['body']);
     if(($parsed['ico']??'')!==$ico) {$issues[]='Výpis sa nezhoduje s hľadaným IČO.';continue;}
     if(!empty($parsed['transferred'])) {$transferred[]=['source_url'=>$candidate,'register_number'=>$parsed['register_number']??null];continue;}
     $primaries[]=['parsed'=>$parsed,'response'=>$r,'url'=>$candidate];
    } catch(Throwable $e) {$issues[]=$e->getMessage();$fetchFailed=true;}
   }
   if(count($primaries)>1) {
    $p['checks']['orsr']=$this->check('conflict',$search,$s)+['error'=>'IČO sa zhoduje s viacerými nepresunutými spismi ORSR; primárny spis nebol zvolený.','candidate_urls'=>array_keys($links),'primary_files'=>array_column($primaries,'url'),'issues'=>$issues,'transferred_files'=>$transferred];return;
   }
   if(count($links)>3) {
    $p['checks']['orsr']=$this->check('partial',$search,$s)+['error'=>'Počet kandidátov prekročil limit troch spisov; primárny spis nebol jednoznačne potvrdený.','candidate_urls'=>array_keys($links),'issues'=>$issues,'transferred_files'=>$transferred];return;
   }
   if(!$primaries||$fetchFailed) {$p['checks']['orsr']=$this->check('source_unavailable',$search,$s)+['error'=>'Nepodarilo sa jednoznačne potvrdiť primárny spis ORSR.','issues'=>$issues,'transferred_files'=>$transferred];return;}
   $found=$primaries[0]['parsed'];$retrieved=$primaries[0]['response'];$url=$primaries[0]['url'];$id=(string)$found['record_id'];$asof=$found['source_updated_at'];
   $this->record($p,'registration',['record_type'=>'orsr_registration','ico'=>$ico,'register_court'=>$found['register_court'],'register_number'=>$found['register_number'],'extract_date'=>$found['extract_date'],'transferred'=>false],'ORSR',$url,$id,$asof,$retrieved);
   $map=['Obchodné meno'=>'name','Sídlo'=>'address','Právna forma'=>'legal_form','Deň zápisu'=>'founded_on','Deň výmazu'=>'dissolved_on','Konanie menom spoločnosti'=>'representation'];
   foreach($map as $label=>$field) {
    $row=$found['sections'][$label][0]??null;if(!$row) {continue;}$value=$row['text'];
    if(in_array($field,['founded_on','dissolved_on'],true)) {$value=$this->date($value);}
    $this->field($p,$field,$value,'ORSR',$url,$id,$asof,$retrieved,['validFrom'=>$row['valid_from'],'validTo'=>$row['valid_to']]);
   }
   foreach(['register_court','register_number'] as $key) {$this->field($p,$key,$found[$key],'ORSR',$url,$id,$asof,$retrieved);}
   $end=$found['sections']['Deň výmazu'][0]['text']??null;$end=$this->date($end);
   $this->field($p,'registration_status',$end && $end<=gmdate('Y-m-d')?'terminated':'registered_without_termination','ORSR',$url,$id,$asof,$retrieved);
   $groups=['Predmet podnikania (činnosti)'=>'activities','Predmet činnosti'=>'activities','Štatutárny orgán'=>'people','Spoločníci'=>'ownership','Akcionári'=>'ownership','Výška vkladu každého spoločníka'=>'ownership','Výška základného imania'=>'ownership','Základné imanie'=>'ownership','Dozorná rada'=>'people','Prokúra'=>'people'];
   foreach($found['sections'] as $label=>$rows) {
    $group=$groups[$label]??'registration';
    foreach($rows as $row) {
     $rid=$id.':'.substr(hash('sha256',$label.wp_json_encode($row)),0,24);
     $this->record($p,$group,['record_type'=>$label,'text'=>$row['text'],'is_current'=>true],'ORSR',$url,$rid,$asof,$retrieved,$row['valid_from'],$row['valid_to']);
    }
   }
   $p['checks']['orsr']=$this->check('observed',$url,$retrieved)+['record_id'=>$id,'source_updated_at'=>$asof,'extract_date'=>$found['extract_date'],'primary_file_confirmed'=>true,'transferred_files'=>$transferred,'candidate_urls'=>array_keys($links),'issues'=>$issues];
  } catch(Throwable $e) {$p['checks']['orsr']=['availability_status'=>'source_unavailable','checked_at'=>gmdate('c'),'source_url'=>$url,'error'=>$e->getMessage()];}
 }

 /** Parse CP1250 by field cells, never by a greedy company-name regex. */
 private function parse_orsr(string $html): array {
  $doc=$this->dom($html);$xp=new DOMXPath($doc);$all=$this->plain($doc->documentElement);
  if(strpos($all,'Výpis z Obchodného registra')===false) {throw new RuntimeException('Stránka nie je výpis ORSR.');}
  $sections=[];
  foreach($xp->query('//table[not(ancestor::table)]/tr | //table[not(ancestor::table)]/tbody/tr') as $tr) {
   $cells=$xp->query('./td',$tr);if($cells->length!==2) {continue;}
   $label=rtrim($this->one_line($this->plain($cells->item(0))),': ');
   if($label===''||mb_strlen($label)>100) {continue;}
   $rows=[];
   foreach($xp->query('./table/tr | ./table/tbody/tr',$cells->item(1)) as $entry) {
    $cs=$xp->query('./td',$entry);if(!$cs->length) {continue;}
    $value=trim($this->plain($cs->item(0)));if($value==='') {continue;}
    $dates=$cs->length>1?$this->one_line($this->plain($cs->item(1))):'';
    preg_match('/\bod:\s*(\d{1,2}\.\d{1,2}\.\d{4})/u',$dates,$from);preg_match('/\bdo:\s*(\d{1,2}\.\d{1,2}\.\d{4})/u',$dates,$to);
    $rows[]=['text'=>$value,'valid_from'=>$this->date($from[1]??null),'valid_to'=>$this->date($to[1]??null)];
   }
   if($rows) {$sections[$label]=$rows;}
  }
  $ico=preg_replace('/\s+/u','',$sections['IČO'][0]['text']??'');
  if(!preg_match('/^[0-9]{8}$/D',$ico)||empty($sections['Obchodné meno'][0]['text'])) {throw new RuntimeException('Výpis ORSR nemá úplné identifikačné polia.');}
  preg_match('/Výpis z Obchodného registra\s+([^\n]+)/u',$all,$court);preg_match('/Oddiel:\s*(\S+).*?Vložka číslo:\s*(\S+)/us',$all,$number);
  preg_match('/Dátum aktualizácie údajov:\s*(\d{1,2}\.\d{1,2}\.\d{4})/u',$all,$updated);preg_match('/Dátum výpisu:\s*(\d{1,2}\.\d{1,2}\.\d{4})/u',$all,$extract);
  return ['ico'=>$ico,'record_id'=>($number[1]??'').'/'.($number[2]??''),'register_number'=>($number[1]??'').'/'.($number[2]??''),'register_court'=>trim($court[1]??''),'source_updated_at'=>$this->date($updated[1]??null),'extract_date'=>$this->date($extract[1]??null),'transferred'=>strpos($all,'Spis odstúpený na iný registrový súd')!==false,'sections'=>$sections];
 }

 private function dom(string $html): DOMDocument {
  if(!class_exists('DOMDocument')) {throw new RuntimeException('Na čítanie ORSR je potrebné PHP DOM.');}
  $encoding=preg_match('/charset\s*=\s*["\x27]?utf-8/i',substr($html,0,2000))?'UTF-8':'Windows-1250';
  $utf=$html;
  if($encoding!=='UTF-8') {
   $utf=false;
   if(function_exists('iconv')) {try {$utf=@iconv('Windows-1250','UTF-8',$html);}catch(Throwable $e) {$utf=false;}}
   if($utf===false&&function_exists('mb_convert_encoding')) {try {$utf=mb_convert_encoding($html,'UTF-8','Windows-1250');}catch(Throwable $e) {$utf=false;}}
   if($utf===false) {throw new RuntimeException('Server nepodporuje konverziu Windows-1250 pre ORSR.');}
  }
  // The downloaded document still declares CP1250 after conversion. Libxml may
  // honor that META over the XML hint and decode already converted bytes twice.
  $utf=preg_replace('/charset\s*=\s*(?:windows-1250|cp1250)/i','charset=UTF-8',$utf);
  $dom=new DOMDocument();$old=libxml_use_internal_errors(true);
  $ok=$dom->loadHTML('<?xml encoding="UTF-8">'.$utf,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);libxml_clear_errors();libxml_use_internal_errors($old);
  if(!$ok) {throw new RuntimeException('HTML ORSR sa nedá spracovať.');}return $dom;
 }
 private function plain($node): string {
  $out=$this->node_text($node);
  $out=str_replace("\xc2\xa0",' ',$out);$lines=array_map([$this,'one_line'],explode("\n",$out));return trim(implode("\n",array_filter($lines,static function($v){return $v!=='';})));
 }
 private function node_text($node): string {
  if($node instanceof DOMText) {return $node->nodeValue;}
  if(in_array(strtolower($node->nodeName),['script','style'],true)) {return '';}
  $out='';foreach($node->childNodes as $child) {$out.=$this->node_text($child);$name=strtolower($child->nodeName);if(in_array($name,['br','tr','p','div'],true)) {$out.="\n";}elseif($name==='td') {$out.=' ';}}return $out;
 }
 private function one_line(string $s): string {return trim(preg_replace('/\s+/u',' ',$s));}
 private function has_identifier(array $d,string $ico): bool {foreach($d['identifiers']??[] as $v) {if(is_array($v)&&preg_replace('/\s+/u','',(string)($v['value']??''))===$ico) {return true;}}return false;}
 public function current(array $v): bool {$from=$this->date($v['validFrom']??null);$to=$this->date($v['validTo']??null);$today=gmdate('Y-m-d');return (!$from||$from<=$today)&&(!$to||$to>=$today);}
 private function latest(array $rows): array {usort($rows,static function($a,$b){return strcmp($b['validFrom']??'',$a['validFrom']??'');});return $rows[0]??[];}
 private function date($value): ?string {
  if(!is_string($value)) {return null;}$value=trim($value);
  if(preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:T.*)?$/D',$value,$m)) {$date=$m[1].'-'.$m[2].'-'.$m[3];}
  elseif(preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/D',$value,$m)) {$date=sprintf('%04d-%02d-%02d',(int)$m[3],(int)$m[2],(int)$m[1]);}
  else {return null;}
  if(in_array($date,['1800-01-01','0001-01-01','0000-00-00'],true)) {return null;}
  [$y,$m,$d]=array_map('intval',explode('-',$date));return checkdate($m,$d,$y)?$date:null;
 }
 private function without_placeholder_dates(array $v): array {
  foreach($v as $key=>$value) {
   if(is_array($value)) {$v[$key]=$this->without_placeholder_dates($value);}
   elseif(is_string($value)&&in_array($value,['1800-01-01','0001-01-01','0000-00-00'],true)) {$v[$key]=null;$v['_excluded_source_placeholders'][$key]=$value;}
  }return $v;
 }
 private function person_name(array $v): ?string {return $v['personName']['formatedName']??$v['personName']['formattedName']??$v['fullName']??null;}
 private function address(array $v): string {return implode(', ',array_filter([trim(($v['street']??'').' '.($v['buildingNumber']??'')),trim(($v['postalCodes'][0]??'').' '.($v['municipality']['value']??'')),$v['country']['value']??''],static function($s){return $s!=='';}));}
 private function check(string $status,string $url,array $r): array {return ['availability_status'=>$status,'checked_at'=>$r['retrieved_at'],'retrieved_at'=>$r['retrieved_at'],'last_attempt_at'=>gmdate('c'),'cache_reused'=>$r['cache_reused'],'source_url'=>$url];}
 private function envelope($value,string $source,string $url,string $id,?string $asof,array $r,?string $from=null,?string $to=null): array {
  $e=Vorkei_Ico_Atlas_Profile::evidence($value,$source,$url,$id,$asof);$e['retrieved_at']=$r['retrieved_at'];$e['source_updated_at']=$asof;$e['valid_from']=$from;$e['valid_to']=$to;return $e;
 }
 private function field(array &$p,string $key,$value,string $source,string $url,string $id,?string $asof,array $r,array $valid=[]): void {
  if($value===null||$value==='') {return;}$e=$this->envelope($value,$source,$url,$id,$asof,$r,$this->date($valid['validFrom']??null),$this->date($valid['validTo']??null));
  if(method_exists('Vorkei_Ico_Atlas_Profile','observe')) {Vorkei_Ico_Atlas_Profile::observe($p,$key,$e);}else {Vorkei_Ico_Atlas_Profile::field($p,$key,$value,$source,$url,$id,$asof);}
 }
 private function record(array &$p,string $group,array $value,string $source,string $url,string $id,?string $asof,array $r,?string $from=null,?string $to=null): void {$p['groups'][$group]['availability_status']='observed';$p['groups'][$group]['records'][]=$this->envelope($value,$source,$url,$id,$asof,$r,$from,$to);}
}
