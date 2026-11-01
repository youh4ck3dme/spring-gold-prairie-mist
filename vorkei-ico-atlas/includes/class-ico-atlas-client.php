<?php
if (!defined('ABSPATH')) {exit;}
/** Bounded, resumable acquisition. Refresh starts a new run; continue resumes it. */
class Vorkei_Ico_Atlas_Client {
 public function lookup(string $ico,bool $refresh=false,bool $continue=false): array {
  $ico=preg_replace('/\s+/u','',$ico);
  if(!preg_match('/^[0-9]{8}$/D',$ico)) {return ['found'=>false,'ico'=>$ico,'verification_status'=>'invalid_input','error'=>'IČO musí obsahovať presne 8 číslic.'];}
  $cachekey='vorkei_ico21_'.$ico;$workkey='vorkei_ico21_work_'.$ico;
  if(!$refresh&&!$continue&&is_array($cached=get_transient($cachekey))) {return $cached;}
  $w=$refresh?false:get_transient($workkey);
  if(!is_array($w)) {$w=['phase'=>0,'profile'=>Vorkei_Ico_Atlas_Profile::blank($ico),'ruz_reset'=>$refresh];}
  $p=$w['profile']; $pending=true;
  if($w['phase']===0) {
   $p=(new Vorkei_Ico_Atlas_Registers())->enrich($ico,$p,$refresh);$w['phase']=1;
  } elseif($w['phase']===1) {
   $p=$this->proxy($ico,$p);$p=(new Vorkei_Ico_Atlas_Tax())->enrich($ico,$p,$refresh);$w['phase']=2;
  } else {
   if(get_option('vorkei_ico_ruz_enabled','yes')==='yes') {
    $p=(new Vorkei_Ico_Atlas_Ruz())->enrich($ico,$p,!empty($w['ruz_reset']));$w['ruz_reset']=false;
   }
   $pending=!empty($p['checks']['ruz']['pending'])&&empty($p['checks']['ruz']['error']);
  }
  $p=Vorkei_Ico_Atlas_Financials::enrich($p);$w['profile']=$p;
  set_transient($workkey,$w,3600);
  $result=Vorkei_Ico_Atlas_Profile::summarize($p);$result['pending']=$pending;
  $result['acquisition_phase']=$w['phase'];
  if(!$result['found']) {$result['error']=$pending?'Získavam údaje z ďalšieho zdroja…':'Žiadny dostupný zdroj nepotvrdil identitu. Pozrite výsledky kontrol.';}
  set_transient($cachekey,$result,$pending?60:($result['found']?3600:60));return $result;
 }
 private function proxy(string $ico,array $p): array {
  $url=(string)get_option('vorkei_ico_api_url','https://ico-api.vorkei.com/api/company/proxy-search?ico=');
  $check=['availability_status'=>'not_checked','checked_at'=>gmdate('c'),'source_url'=>$url];
  if($url===''||wp_parse_url($url,PHP_URL_SCHEME)!=='https') {$check['availability_status']='not_configured';$p['checks']['registry']=$check;return $p;}
  $response=wp_safe_remote_get($url.rawurlencode($ico),['timeout'=>5,'redirection'=>2,'sslverify'=>true,'limit_response_size'=>8388608,'headers'=>['Accept'=>'application/json','X-ICO-LOOKUP-CONTRACT'=>'js_default']]);
  if(is_wp_error($response)) {$check['availability_status']='source_unavailable';}
  else {
   $code=wp_remote_retrieve_response_code($response);$d=json_decode(wp_remote_retrieve_body($response),true);
   if($code!==200||!is_array($d)) {$check['availability_status']='source_unavailable';}
   elseif(($d['found']??null)===false) {$check['availability_status']='not_found';}
   elseif(!is_string($d['name']??null)||trim($d['name'])===''||($d['ico']??$ico)!==$ico) {$check['availability_status']='invalid_response';}
   else {$p=Vorkei_Ico_Atlas_Profile::merge($p,$this->normalize($ico,$d,$url)['profile']);$check['availability_status']='observed';}
  }
  $p['checks']['registry']=$check;return Vorkei_Ico_Atlas_Profile::merge($p,Vorkei_Ico_Atlas_Profile::blank($ico));
 }
 public function normalize(string $ico,array $d,string $url): array {
  $p=Vorkei_Ico_Atlas_Profile::blank($ico);
  if(in_array($d['schema_version']??'',['2.0','2.1'],true)&&is_array($d['profile']??null)&&Vorkei_Ico_Atlas_Profile::valid($d['profile'],$ico)) {
   // An upstream's asserted official provenance is retained, not promoted to our direct check.
   foreach($d['profile']['fields'] as $key=>$original) {
    $e=Vorkei_Ico_Atlas_Profile::evidence($original['value'],'Konfigurované registračné API',$url.rawurlencode($ico),$ico.':'.$key);
    $e['upstream_evidence']=$original;$e['match_method']='upstream_declared';Vorkei_Ico_Atlas_Profile::observe($p,$key,$e);
   }
   foreach($d['profile']['groups'] as $key=>$group) {
    if(!isset($p['groups'][$key])) {continue;}
    $p['groups'][$key]['availability_status']=$group['availability_status']??'not_checked';
    foreach($group['records']??[] as $i=>$original) {
     $e=Vorkei_Ico_Atlas_Profile::evidence($original['value']??null,'Konfigurované registračné API',$url.rawurlencode($ico),$ico.':'.$key.':'.$i);
     $e['upstream_evidence']=$original;$e['match_method']='upstream_declared';$p['groups'][$key]['records'][]=$e;
    }
   }
  }
  foreach($d as $k=>$v) {
   if(in_array($k,['profile','found','schema_version'],true)||$v===null||$v==='') {continue;}
   Vorkei_Ico_Atlas_Profile::field($p,$k,$v,'Konfigurované registračné API',$url.rawurlencode($ico),$ico);
  }
  // Empty lists and undocumented legacy activity claims do not verify legal status.
  if(array_key_exists('statutaries',$d)&&$d['statutaries']===[]) {$p['groups']['people']['availability_status']='not_checked';}
  return Vorkei_Ico_Atlas_Profile::summarize($p);
 }
}
