<?php
if (!defined('ABSPATH')) { exit; }
/** Official RUZ API. Resumable task queue, bounded work per HTTP request. */
class Vorkei_Ico_Atlas_Ruz {
 const BASE='https://www.registeruz.sk/cruz-public/';
 const TTL=3600;
 private function cache_key(string $path): string { return 'vorkei_ruz_'.md5(self::BASE.'api/'.$path); }
 /** The successful transport timestamp travels with cached data, never with a cache read. */
 private function fetch(string $path): array {
  $url=self::BASE.'api/'.$path; $key=$this->cache_key($path); $cached=get_transient($key);
  if (is_array($cached) && isset($cached['data'],$cached['retrieved_at']) && is_array($cached['data'])) { return $cached; }
  $response=wp_safe_remote_get($url,['timeout'=>8,'sslverify'=>true,'redirection'=>2,'limit_response_size'=>8388608,'headers'=>['Accept'=>'application/json']]);
  if (is_wp_error($response)) { throw new RuntimeException('Zdroj RÚZ je nedostupný.'); }
  $code=wp_remote_retrieve_response_code($response); $data=json_decode(wp_remote_retrieve_body($response),true);
  if ($code!==200 || !is_array($data)) { throw new RuntimeException('RÚZ HTTP '.$code.' alebo neplatný JSON.'); }
  if (str_starts_with($path,'uctovne-jednotky?')) {
   if (!isset($data['id']) || !is_array($data['id'])) { throw new RuntimeException('RÚZ vrátil neplatný zoznam účtovných jednotiek.'); }
  } elseif (!isset($data['id']) || !is_scalar($data['id'])) { throw new RuntimeException('RÚZ vrátil neplatný záznam.'); }
  $result=['data'=>$data,'retrieved_at'=>gmdate('c')]; set_transient($key,$result,self::TTL); return $result;
 }
 public function enrich(string $ico,array $profile,bool $refresh=false): array {
  $key='vorkei_ruz_job_'.$ico; $manifestKey='vorkei_ruz_keys_'.$ico; $job=get_transient($key);
  if ($refresh) { $this->invalidate($ico,is_array($job)?$job:[],$profile); $job=false; }
  if (!is_array($job) || time()-($job['started_at']??0)>self::TTL) {
   $job=['started_at'=>time(),'queue'=>[['uctovne-jednotky','zmenene-od=2000-01-01&ico='.$ico]],
    'seen'=>[],'profile'=>Vorkei_Ico_Atlas_Profile::blank($ico),'failed'=>[],'last_retrieved_at'=>null];
  }
  $p=$job['profile']; $error=null;
  for ($i=0;$i<3 && !empty($job['queue']);$i++) {
   [$type,$arg]=$job['queue'][0]; $task=$type.'?'.$arg;
   try { $fetched=$this->fetch($task); } catch (RuntimeException $e) { $error=$e->getMessage(); break; }
   $d=$fetched['data']; $retrieved=$fetched['retrieved_at']; $job['last_retrieved_at']=$retrieved;
   $manifest=get_transient($manifestKey); $manifest=is_array($manifest)?$manifest:[];
   $manifest[$task]=true; set_transient($manifestKey,$manifest,self::TTL);
   array_shift($job['queue']); $job['seen'][$task]=true;
   $id=is_scalar($d['id']??null)?(string)$d['id']:'';
   $updated=is_string($d['datumPoslednejUpravy']??null)?$d['datumPoslednejUpravy']:null;
   if ($type==='uctovne-jednotky') {
    foreach ($d['id'] as $value) { $this->queue($job,'uctovna-jednotka','id='.(int)$value); }
    if (!empty($d['existujeDalsieId']) && $d['id']) { $this->queue($job,$type,'zmenene-od=2000-01-01&ico='.$ico.'&pokracovat-za-id='.(int)end($d['id'])); }
   } elseif ($type==='uctovna-jednotka') {
    if (($d['ico']??'')!==$ico) { $job['failed'][]='IČO sa nezhoduje: '.$id; continue; }
    $url=self::BASE.'api/'.$task;
    $map=['nazovUJ'=>'name','dic'=>'dic','ulica'=>'street','mesto'=>'city','psc'=>'postal_code','datumZalozenia'=>'founded_on','datumZrusenia'=>'dissolved_on','pravnaForma'=>'legal_form_code','skNace'=>'nace_code','velkostOrganizacie'=>'organization_size_code','druhVlastnictva'=>'ownership_type_code','kraj'=>'region_code','okres'=>'district_code','sidlo'=>'municipality_code'];
    foreach ($map as $from=>$to) { $this->field($p,$to,$d[$from]??null,$url,$id,$updated,$retrieved); }
    $this->field($p,'address',implode(', ',array_filter([$d['ulica']??'',trim(($d['psc']??'').' '.($d['mesto']??''))])),$url,$id,$updated,$retrieved);
    $this->record($p,'registration',$d,$task,$updated,$retrieved);
    foreach ($d['idUctovnychZavierok']??[] as $value) { $this->queue($job,'uctovna-zavierka','id='.(int)$value); }
    foreach ($d['idVyrocnychSprav']??[] as $value) { $this->queue($job,'vyrocna-sprava','id='.(int)$value); }
   } else {
    $this->record($p,'financials',$d,$task,$updated,$retrieved);
    foreach ($d['idUctovnychVykazov']??[] as $value) { $this->queue($job,'uctovny-vykaz','id='.(int)$value); }
    if (isset($d['idSablony'])) { $this->queue($job,'sablona','id='.(int)$d['idSablony']); }
   }
  }
  $pending=!empty($job['queue']);
  $p['checks']['ruz']=['availability_status'=>$error?'source_unavailable':($pending?'partial':(!empty($p['fields']['name'])?'observed':'not_found')),
   'checked_at'=>gmdate('c'),'retrieved_at'=>$job['last_retrieved_at'],'pending'=>$pending,'remaining_tasks'=>count($job['queue']),
   'completed_tasks'=>count($job['seen']),'error'=>$error,'issues'=>$job['failed'],'source_url'=>self::BASE.'home/api'];
  if ($job['failed']) { $p['checks']['ruz']['availability_status']='conflict'; }
  if ($error) {
   foreach (['registration','financials'] as $group) {
    if (empty($p['groups'][$group]['records'])) { $p['groups'][$group]['availability_status']='source_unavailable'; }
    else { $p['groups'][$group]['last_attempt_status']='source_unavailable'; }
   }
  }
  $job['profile']=$p; set_transient($key,$job,self::TTL);
  $merged=Vorkei_Ico_Atlas_Profile::merge($profile,$p);
  return class_exists('Vorkei_Ico_Atlas_Financials')?Vorkei_Ico_Atlas_Financials::enrich($merged):$merged;
 }
 private function invalidate(string $ico,array $job,array $profile): void {
  $manifest=get_transient('vorkei_ruz_keys_'.$ico); $tasks=is_array($manifest)?array_keys($manifest):[];
  $tasks=array_merge($tasks,array_keys($job['seen']??[]),['uctovne-jednotky?zmenene-od=2000-01-01&ico='.$ico]);
  foreach ($job['queue']??[] as $task) { $tasks[]=$task[0].'?'.$task[1]; }
  foreach ([$profile,$job['profile']??[]] as $p) {
   foreach (['registration','financials'] as $group) {
    foreach ($p['groups'][$group]['records']??[] as $record) {
     $id=$record['source_record_id']??'';
     if (($record['source']??'')==='RÚZ' && preg_match('/^(uctovn[ay]-jednotka|uctovna-zavierka|uctovny-vykaz|vyrocna-sprava|sablona)\?id=\d+$/D',$id)) { $tasks[]=$id; }
    }
   }
  }
  foreach (array_unique($tasks) as $task) { delete_transient($this->cache_key($task)); }
  delete_transient('vorkei_ruz_job_'.$ico); delete_transient('vorkei_ruz_keys_'.$ico);
 }
 private function field(array &$p,string $field,$value,string $url,string $id,?string $updated,string $retrieved): void {
  if ($value===null || $value==='') { return; }
  $e=Vorkei_Ico_Atlas_Profile::evidence($value,'RÚZ',$url,$id,$updated);
  $e['retrieved_at']=$retrieved; $e['source_updated_at']=$updated; $e['valid_as_of']=null;
  Vorkei_Ico_Atlas_Profile::observe($p,$field,$e);
 }
 private function queue(array &$job,string $type,string $arg): void {
  if (!isset($job['seen'][$type.'?'.$arg]) && !in_array([$type,$arg],$job['queue'],true)) { $job['queue'][]=[$type,$arg]; }
 }
 private function record(array &$p,string $group,array $data,string $path,?string $updated,string $retrieved): void {
  $e=Vorkei_Ico_Atlas_Profile::evidence($data,'RÚZ',self::BASE.'api/'.$path,$path,$updated);
  $e['retrieved_at']=$retrieved; $e['source_updated_at']=$updated; $e['valid_as_of']=null;
  $p['groups'][$group]['availability_status']='observed'; $p['groups'][$group]['records'][]=$e;
 }
}
