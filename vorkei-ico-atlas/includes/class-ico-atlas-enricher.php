<?php
if(!defined('ABSPATH')) {exit;}
class Vorkei_Ico_Atlas_Enricher {
 public function get_target_tables(): array {
  global $wpdb; $out=[];
  foreach(['entities'=>'vorkei_ico_entities','customers'=>'jet_cct_customers','contractors'=>'jet_cct_contractors','clients'=>'jet_cct_clients'] as $key=>$suffix) {
   $t=$wpdb->prefix.$suffix;
   if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($t)))===$t) {$out[$key]=$t;}
  }
  return $out;
 }
 public function load(string $ico): ?array {
  global $wpdb; $t=$wpdb->prefix.'vorkei_ico_entities';
  $json=$wpdb->get_var($wpdb->prepare("SELECT profile_json FROM {$t} WHERE ico=%s",$ico));
  $d=json_decode((string)$json,true);
  if(!is_array($d)) {return null;}
  if(is_array($d['profile']??null)) {
   $profile=Vorkei_Ico_Atlas_Profile::merge(Vorkei_Ico_Atlas_Profile::blank($ico),$d['profile']);
   $profile=Vorkei_Ico_Atlas_Financials::enrich($profile);
   $pending=$d['pending']??false;
   $d=array_replace($d,Vorkei_Ico_Atlas_Profile::summarize($profile));$d['pending']=$pending;
  } return $d;
 }
 public function save_lookup_entity(array $lookup): void {
  global $wpdb;
  if(empty($lookup['found'])||!preg_match('/^[0-9]{8}$/D',$lookup['ico']??'')) {return;}
  $t=$wpdb->prefix.'vorkei_ico_entities'; $ico=$lookup['ico']; $old=$this->load($ico);
  if($old) {
   $lookup['profile']=Vorkei_Ico_Atlas_Profile::merge($old['profile'],$lookup['profile']);
  }
  $pending=$lookup['pending']??false;
  $lookup['profile']=Vorkei_Ico_Atlas_Financials::enrich($lookup['profile']);
  $lookup=array_replace($lookup,Vorkei_Ico_Atlas_Profile::summarize($lookup['profile']));$lookup['pending']=$pending;

  $best_year=null;$best_rev=null;$best_prof=null;$best_eq=null;
  foreach(($lookup['profile']['groups']['financials']['records']??[]) as $rec) {
   if(!empty($rec['normalized_facts'])) {
    $f=$rec['normalized_facts'];
    $period=$f['operating_revenue']['period']??$f['profit_after_tax']['period']??[];
    $to_date=$period['to']??'';
    $year=(int)substr($to_date,0,4);
    if($year>=2000) {
     $rev=$f['operating_revenue']['value']??null;
     $prof=$f['profit_after_tax']['value']??$f['profit_before_tax']['value']??null;
     $eq=$f['equity']['value']??null;
     if(($year>$best_year||$best_year===null)&&($rev!==null||$prof!==null||$eq!==null)) {
      $best_year=$year;$best_rev=$rev;$best_prof=$prof;$best_eq=$eq;
     }
    }
   }
  }
  if($best_year!==null) {
   $lookup['last_revenue']=$best_rev;
   $lookup['last_profit']=$best_prof;
   $lookup['last_equity']=$best_eq;
   $lookup['financials_year']=$best_year;
  }
  if(isset($lookup['profile']['fields']['tax_reliability']['value'])) {$lookup['tax_reliability']=$lookup['profile']['fields']['tax_reliability']['value'];}
  if(!empty($lookup['profile']['fields']['registered_ibans']['value'])) {$lookup['iban_checked_at']=gmdate('Y-m-d H:i:s');}

  $data=['ico'=>$ico,'name'=>$lookup['name'],'profile_json'=>wp_json_encode($lookup,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE),'verification_status'=>$lookup['verification_status'],'last_attempt_at'=>gmdate('Y-m-d H:i:s'),'last_success_at'=>gmdate('Y-m-d H:i:s')];
  foreach(['legal_form','address','founded_on','dissolved_on','risk_reason','verified_at','last_revenue','last_profit','last_equity','financials_year','tax_reliability','iban_checked_at'] as $k) {if(isset($lookup[$k])) {$data[$k]=is_scalar($lookup[$k])?$lookup[$k]:wp_json_encode($lookup[$k]);}}
  $data['is_active']=$lookup['active']===true?1:0;
  $data['is_risky']=!empty($lookup['is_risky'])?1:0;$data['verified_at']=$lookup['verified_at']??null;
  $data['company_status']=$lookup['status'];
  $id=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t} WHERE ico=%s",$ico));
  if($id) {$wpdb->update($t,$data,['id'=>(int)$id]);} else {$wpdb->insert($t,$data);}
 }
 public function lookup_and_save(string $ico,bool $refresh=false,bool $continue=false): array {
  if(!preg_match('/^[0-9]{8}$/D',preg_replace('/\s+/u','',$ico))) {return (new Vorkei_Ico_Atlas_Client())->lookup($ico,$refresh,$continue);}
  $ico=preg_replace('/\s+/u','',$ico); $lock='vorkei_ico_lock_'.$ico;
  if((int)get_option($lock,0)<time()-120) {delete_option($lock);}
  if(!add_option($lock,time(),'','no')) {return ['found'=>false,'ico'=>$ico,'verification_status'=>'busy','error'=>'Profil práve aktualizuje iná požiadavka.'];}
  try {return $this->perform_lookup($ico,$refresh,$continue);} finally {delete_option($lock);}
 }
 private function perform_lookup(string $ico,bool $refresh=false,bool $continue=false): array {
  $r=(new Vorkei_Ico_Atlas_Client())->lookup($ico,$refresh,$continue);
  if($r['found']) {$this->save_lookup_entity($r); return $this->load($ico)??$r;}
  $old=$this->load($ico);
  if($old) {
   $old['last_attempt']=['at'=>gmdate('c'),'status'=>$r['verification_status'],'error'=>$r['error']??null];
   $old['profile']=Vorkei_Ico_Atlas_Profile::merge($old['profile'],$r['profile']??Vorkei_Ico_Atlas_Profile::blank($ico));
   $old=array_replace($old,Vorkei_Ico_Atlas_Profile::summarize($old['profile']));$old['pending']=$r['pending']??false;
   $old['stale']=true; $old['verified']=false; $old['active']=null;$old['verification_status']='stale';
   global $wpdb; $wpdb->update($wpdb->prefix.'vorkei_ico_entities',['profile_json'=>wp_json_encode($old),'verification_status'=>'stale','last_attempt_at'=>gmdate('Y-m-d H:i:s')],['ico'=>$ico]);
   return $old;
  }
  return $r;
 }
 /** Fill only empty mapped CCT columns; JetEngine definitions are owned by JetEngine. */
 public function sync_cct(array $r): void {
  global $wpdb;
  if(empty($r['found'])) {return;}
  foreach($this->get_target_tables() as $key=>$t) {
   if($key==='entities') {continue;}
   $cols=(array)$wpdb->get_col("DESCRIBE `{$t}`");
   $map=apply_filters('vorkei_ico_cct_field_map',['ico'=>'ico','name'=>'name','legal_form'=>'legal_form','address'=>'address','founded_on'=>'founded_on','dic'=>'dic','last_revenue'=>'last_revenue','last_profit'=>'last_profit','last_equity'=>'last_equity','financials_year'=>'financials_year','tax_reliability'=>'tax_reliability','iban_checked_at'=>'iban_checked_at'],$key);
   $updates=[];
   foreach($map as $field=>$col) {
    if(!in_array($col,$cols,true)||!preg_match('/^[a-zA-Z0-9_]+$/D',$col)||$field==='ico') {continue;}
    $value=$r[$field]??$r['profile']['fields'][$field]['value']??null;
    if($value!==null && is_scalar($value) && $value!=='') {$updates[$col]=$value;}
   }
   $icocol=$map['ico']??'ico';
   if(!in_array($icocol,$cols,true)||!preg_match('/^[a-zA-Z0-9_]+$/D',$icocol)) {continue;}

   $existing_year=null;
   if(in_array('financials_year',$cols,true)) {
    $existing_year=$wpdb->get_var($wpdb->prepare("SELECT `financials_year` FROM `{$t}` WHERE `{$icocol}`=%s LIMIT 1",$r['ico']));
   }

   foreach($updates as $col=>$val) {
    if(in_array($col,['last_revenue','last_profit','last_equity','financials_year'],true)) {
     $new_year=(int)($r['financials_year']??0);
     if($existing_year!==null && $existing_year!=='' && $new_year<(int)$existing_year) {
      continue;
     }
     $wpdb->query($wpdb->prepare("UPDATE `{$t}` SET `{$col}`=%s WHERE `{$icocol}`=%s",$val,$r['ico']));
    } else {
     $wpdb->query($wpdb->prepare("UPDATE `{$t}` SET `{$col}`=%s WHERE `{$icocol}`=%s AND (`{$col}` IS NULL OR `{$col}`='')",$val,$r['ico']));
    }
   }
  }
 }
 public function enrich_entity(string $key,int $id,string $ico): array {
  $r=$this->lookup_and_save($ico,true); $this->sync_cct($r); return ['success'=>$r['found'],'data'=>$r];
 }
 /** Unified IČO index, SQL UNION deduplicates before pagination. */
 public function get_icos(): array {
  global $wpdb; $parts=[];
  foreach($this->get_target_tables() as $key=>$t) {
   $cols=(array)$wpdb->get_col("DESCRIBE {$t}");
   $map=apply_filters('vorkei_ico_cct_field_map',['ico'=>'ico'],$key);$col=$map['ico']??'ico';
   if(in_array($col,$cols,true)&&preg_match('/^[a-zA-Z0-9_]+$/D',$col)) {$parts[]="SELECT `{$col}` AS ico FROM {$t} WHERE `{$col}` REGEXP '^[0-9]{8}$'";}
  }
  return $parts?(array)$wpdb->get_col(implode(' UNION ',$parts).' ORDER BY ico'):[];
 }
 public function get_entities(string $filter='all',int $limit=50,int $offset=0): array {
  global $wpdb; $items=[]; $t=$wpdb->prefix.'vorkei_ico_entities';
  foreach($this->get_icos() as $ico) {
   $r=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE ico=%s",$ico),ARRAY_A);
   $r=$r?:['ico'=>$ico,'name'=>'','legal_form'=>'','address'=>'','company_status'=>'Neoverené','is_active'=>0,'is_risky'=>0,'verified_at'=>null];
   $r['_ID']=$r['id']??0; $r['entity_type']='entities';
   $active=($r['verification_status']??'')==='verified_registered'; $risky=!empty($r['is_risky']);
   if($filter==='active'&&!$active||$filter==='risky'&&!$risky||$filter==='unverified'&&($active||$risky)) {continue;}
   $items[]=$r;
  }
  return array_slice($items,max(0,$offset),max(0,$limit));
 }
 public function get_audit_stats(): array {
  $s=['total'=>0,'active'=>0,'risky'=>0,'unverified'=>0];
  foreach($this->get_entities('all',PHP_INT_MAX) as $r) {
   $s['total']++;
   if(!empty($r['is_risky'])) {$s['risky']++;}
   elseif(($r['verification_status']??'')==='verified_registered') {$s['active']++;}
   else {$s['unverified']++;}
  } return $s;
 }
 public function enrich_unverified_entities(int $limit=1,string $job_id=''): array {
  $key='vorkei_batch_'.get_current_user_id().'_'.$job_id; $job=$job_id?get_transient($key):false;
  if(!$job) {
   $job_id=wp_generate_uuid4(); $key='vorkei_batch_'.get_current_user_id().'_'.$job_id;
   $job=['icos'=>$this->get_icos(),'offset'=>0,'verified'=>0,'found'=>0,'failed'=>0,'risky'=>0,'attempts'=>[]];
  }
  $count=0;
  while($count<min(2,max(1,$limit)) && $job['offset']<count($job['icos'])) {
   $ico=$job['icos'][$job['offset']++];$job['attempts'][$ico]=($job['attempts'][$ico]??0)+1;$r=$this->lookup_and_save($ico,$job['attempts'][$ico]===1,$job['attempts'][$ico]>1);$this->sync_cct($r);
   if(!empty($r['pending']) && $job['attempts'][$ico]<100) {$job['icos'][]=$ico;}
   $job[$r['found']?'found':'failed']++; if(!empty($r['verified'])) {$job['verified']++;} if(!empty($r['is_risky'])) {$job['risky']++;} $count++;
  }
  set_transient($key,$job,3600);
  return ['job_id'=>$job_id,'processed'=>$job['offset'],'total'=>count($job['icos']),'verified'=>$job['verified'],'failed'=>$job['failed'],'risky'=>$job['risky'],'done'=>$job['offset']>=count($job['icos'])];
 }
}
