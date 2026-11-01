<?php
if (!defined('ABSPATH')) {exit;}

/** Official FS lists. Credentials remain in request headers and never enter evidence. */
class Vorkei_Ico_Atlas_Tax {
 const API='https://iz.opendata.financnasprava.sk/api';
 const SOURCE='Finančná správa SR';
 const TTL=3600;
 const MAX_PAGES=20;
 private $deadline=0.0;

 /** Read-only administrator discovery; the configured credential is never returned. */
 public function discover(): array {
  $this->deadline=microtime(true)+5.0;
  $result=['checked_at'=>gmdate('c'),'source_url'=>self::API.'/lists','lists'=>[]];
  $key=get_option('vorkei_ico_fs_api_key','');
  if(!is_string($key)||trim($key)===''||preg_match('/[\r\n]/',$key)) {
   $result['availability_status']='requires_access';
   $result['message']='Najprv nastavte vlastný API kľúč Finančnej správy.';
   return $result;
  }
  $response=$this->request('/lists',trim($key),false);
  if(!$response['ok']) {$result['availability_status']=$response['status'];return $result;}
  $catalog=$this->catalog($response['data']);
  if($catalog===null) {$result['availability_status']='invalid_response';return $result;}
  foreach($catalog as $slug=>$entry) {$entry['slug']=$slug;$result['lists'][]=$this->metadata($entry);}
  $result['availability_status']='observed';
  $result['retrieved_at']=$response['retrieved_at'];
  $result['cache_hit']=!empty($response['cache_hit']);
  return $result;
 }

 public function enrich(string $ico,array $profile,bool $refresh=false): array {
  $this->deadline=microtime(true)+18.0;
  $delta=Vorkei_Ico_Atlas_Profile::blank($ico);
  $check=['checked_at'=>gmdate('c'),'source_url'=>self::API,'datasets'=>[]];
  if (!preg_match('/^[0-9]{8}$/D',$ico)) {
   return $this->finish($profile,$delta,$check,'invalid_input','IČO musí obsahovať presne 8 číslic.');
  }
  $key=get_option('vorkei_ico_fs_api_key','');
  if (!is_string($key)||trim($key)==='') {
   return $this->finish($profile,$delta,$check,'requires_access','Oficiálne API FS vyžaduje administrátorom nastavený API kľúč.');
  }
  $key=trim($key);
  // Header injection is rejected without disclosing the supplied value.
  if (preg_match('/[\r\n]/',$key)) {
   return $this->finish($profile,$delta,$check,'requires_access','API kľúč FS má neplatný formát.');
  }
  $configured=['dic'=>get_option('vorkei_ico_fs_dic_slug',''),'vat'=>get_option('vorkei_ico_fs_vat_slug','')];
  $column=get_option('vorkei_ico_fs_ico_column','ICO');
  $column=is_string($column)?trim($column):'';
  if ($column==='') {$column='ICO';}
  $catalog=$this->request('/lists',$key,$refresh);
  if (!$catalog['ok']) {
   return $this->finish($profile,$delta,$check,$catalog['status'],'Metadáta informačných zoznamov FS sa nepodarilo overiť.');
  }
  $lists=$this->catalog($catalog['data']);
  if ($lists===null) {
   return $this->finish($profile,$delta,$check,'invalid_response','API FS vrátilo neplatný katalóg zoznamov.');
  }
  foreach($configured as $type=>$slug) {
   $dataset=['availability_status'=>'requires_access','checked_at'=>gmdate('c')];
   if (!is_string($slug)||!preg_match('/^[A-Za-z0-9_-]+$/D',$slug)) {
    $dataset['message']='Administrátor musí nastaviť identifikátor oficiálneho zoznamu '.$type.'.';
    $check['datasets'][$type]=$dataset; continue;
   }
   $dataset['slug']=$slug;
   $dataset['source_url']=self::API.'/lists/'.rawurlencode($slug);
   if (!isset($lists[$slug])) {
    $dataset['availability_status']='unsupported';
    $dataset['message']='Nastavený identifikátor nebol potvrdený v katalógu API FS.';
    $check['datasets'][$type]=$dataset; continue;
   }
   $meta=$this->request('/lists/'.rawurlencode($slug),$key,$refresh);
   if (!$meta['ok']) {
    $dataset['availability_status']=$meta['status'];
    $check['datasets'][$type]=$dataset; continue;
   }
   if (!is_array($meta['data'])||($meta['data']['slug']??null)!==$slug||!is_array($meta['data']['searchable']??null)) {
    $dataset['availability_status']='invalid_response';
    $dataset['message']='Detail zoznamu FS nemá očakávané metadáta.';
    $check['datasets'][$type]=$dataset; continue;
   }
   $metadata=$this->metadata($meta['data']);
   $dataset['metadata']=$metadata;
   $dataset['metadata_retrieved_at']=$meta['retrieved_at']??null;
   $dataset['source_updated_at']=$metadata['update_date']??null;
   $verified_column=null;
   foreach($metadata['searchable'] as $candidate) {
    if (strcasecmp($candidate,$column)===0) {$verified_column=$candidate;break;}
   }
   if ($verified_column===null) {
    $dataset['availability_status']='unsupported';
    $dataset['message']='Nastavený stĺpec IČO nie je podľa metadát FS vyhľadateľný.';
    $check['datasets'][$type]=$dataset; continue;
   }
   $result=$this->search($ico,$slug,$verified_column,$key,$refresh);
   $dataset['availability_status']=$result['status'];
   $dataset['pages_checked']=$result['pages_checked'];
   $dataset['retrieved_at']=$result['retrieved_at']??null;
   $dataset['cache_hit']=!empty($result['cache_hit']);
   $dataset['source_url']=$result['url'];
   if (!$result['ok']) {
    $dataset['message']='Vyhľadanie vo FS sa nedokončilo; predchádzajúce údaje zostali zachované.';
    $check['datasets'][$type]=$dataset; continue;
   }
   $asof=is_string($metadata['update_date']??null)?$metadata['update_date']:null;
   $retrieved_at=$result['retrieved_at']??gmdate('c');
   $matches=$result['matches'];
   $summary=['type'=>$type,'ico'=>$ico,'matched'=>count($matches),'complete'=>true,'metadata'=>$metadata,'pages_checked'=>$result['pages_checked']];
   $e=Vorkei_Ico_Atlas_Profile::evidence($summary,self::SOURCE,$result['url'],$slug.':'.$ico.':search',$asof,$matches?'observed':'not_found');
   $e['source_updated_at']=$asof;
   $e['retrieved_at']=$retrieved_at;
   $delta['groups']['tax']['records'][]=$e;
   if (!$matches) {
    if ($type==='vat') {$this->field($delta,'vat_registry_match',false,$result['url'],$slug.':'.$ico,$asof,$retrieved_at);}
    $check['datasets'][$type]=$dataset; continue;
   }
   $rows=[];
   foreach($matches as $i=>$row) {
    $row=$this->row($row);
    $rows[]=$row;
    $record=Vorkei_Ico_Atlas_Profile::evidence(['type'=>$type,'source_row'=>$row,'metadata'=>$metadata],self::SOURCE,$result['url'],$slug.':'.$ico.':'.hash('sha256',wp_json_encode($row)),$asof);
    $record['source_updated_at']=$asof;
    $record['retrieved_at']=$retrieved_at;
    $delta['groups']['tax']['records'][]=$record;
   }
   $this->fields($delta,$type,$rows,$result['url'],$slug.':'.$ico,$asof,$retrieved_at);
   $check['datasets'][$type]=$dataset;
  }
  $vat_id=$delta['fields']['vat_id']['value']??null;
  if(is_string($vat_id)&&$vat_id!==''&&isset($lists['ds_dph_iban'])) {
   $iban_res=$this->search_exact($vat_id,'ds_dph_iban','ic_dph','ic_dph',$key,$refresh);
   if($iban_res['ok']&&!empty($iban_res['matches'])) {
    $ibans=[];
    foreach($iban_res['matches'] as $i_row) {
     $norm=$this->row($i_row);
     $clean_iban=strtoupper(preg_replace('/\s+/u','',(string)($norm['IBAN']??$i_row['iban']??'')));
     if($clean_iban!=='') {
      $ibans[]=$clean_iban;
      $rec=Vorkei_Ico_Atlas_Profile::evidence(['type'=>'iban','iban'=>$clean_iban,'source_row'=>$norm],self::SOURCE,$iban_res['url'],'ds_dph_iban:'.$clean_iban,$iban_res['retrieved_at']);
      $delta['groups']['accounts']['records'][]=$rec;
     }
    }
    if(!empty($ibans)) {
     $u_ibans=array_values(array_unique($ibans));
     $delta['groups']['accounts']['availability_status']='observed';
     $this->field($delta,'registered_ibans',$u_ibans,$iban_res['url'],'ds_dph_iban:'.$vat_id,null,$iban_res['retrieved_at']);
     $this->field($delta,'iban_count',count($u_ibans),$iban_res['url'],'ds_dph_iban:'.$vat_id,null,$iban_res['retrieved_at']);
    }
   }
  }
  if(isset($lists['ds_dphz'])) {
   $z_res=$this->search($ico,'ds_dphz',$column,$key,$refresh);
   if($z_res['ok']&&!empty($z_res['matches'])) {
    $this->field($delta,'vat_blacklisted',true,$z_res['url'],'ds_dphz:'.$ico,null,$z_res['retrieved_at']);
    $this->field($delta,'is_risky',true,$z_res['url'],'ds_dphz:'.$ico,null,$z_res['retrieved_at']);
    $this->field($delta,'risk_reason','⚠️ Pozor! Subjekt je na zozname platiteľov DPH s dôvodmi na zrušenie registrácie (čierna listina DPH FS SR).',$z_res['url'],'ds_dphz:'.$ico,null,$z_res['retrieved_at']);
   }
  }
  if(isset($lists['ds_iz_ran'])) {
   $ran_res=$this->search($ico,'ds_iz_ran',$column,$key,$refresh);
   if($ran_res['ok']&&!empty($ran_res['matches'])) {
    $ids_val=$ran_res['matches'][0]['ids']??$ran_res['matches'][0]['IDS']??null;
    if($ids_val) {
     $this->field($delta,'tax_reliability',(string)$ids_val,$ran_res['url'],'ds_iz_ran:'.$ico,null,$ran_res['retrieved_at']);
    }
   }
  }
  $states=array_column($check['datasets'],'availability_status');
  $success=count(array_filter($states,static function($s){return in_array($s,['observed','not_found'],true);}));
  $status=$success===count($states)?(in_array('observed',$states,true)?'observed':'not_found'):($success?'partial':($states[0]??'requires_access'));
  if (!$success && count(array_unique($states))>1) {$status='source_unavailable';}
  return $this->finish($profile,$delta,$check,$status);
 }

 private function finish(array $profile,array $delta,array $check,string $status,string $message=''): array {
  $check['availability_status']=$status;
  if ($message!=='') {$check['message']=$message;}
  $delta['checks']['fs']=$check;
  foreach(['dic','vat'] as $type) {
   $delta['checks']['fs_'.$type]=$check['datasets'][$type]??['availability_status'=>$status,'checked_at'=>$check['checked_at'],'source_url'=>self::API];
  }
  $delta['groups']['tax']['availability_status']=$status;
  return Vorkei_Ico_Atlas_Profile::merge($profile,$delta);
 }

 /** Both an array catalog and the documented slug-keyed catalog are accepted. */
 private function catalog($data): ?array {
  if (!is_array($data)) {return null;}
  $out=[];
  foreach($data as $slug=>$entry) {
   if (!is_array($entry)) {return null;}
   $id=$entry['slug']??(is_string($slug)?$slug:null);
   if (!is_string($id)||!preg_match('/^[A-Za-z0-9_-]+$/D',$id)) {return null;}
   $out[$id]=$entry;
  }
  return $out;
 }

 private function metadata(array $m): array {
  $out=[];
  foreach(['name','slug','url','update_date'] as $k) {if(is_string($m[$k]??null)) {$out[$k]=$m[$k];}}
  $out['searchable']=array_values(array_filter($m['searchable']??[],static function($v){return is_string($v);}));
  return $out;
 }

 private function search(string $ico,string $slug,string $column,string $key,bool $refresh): array {
  $matches=[];$seen=[];$pages_checked=0;$first_url='';$retrieved_at=null;$cache_hit=true;
  for($page=1;$page<=self::MAX_PAGES;$page++) {
   $path='/data/'.rawurlencode($slug).'/search?page='.$page.'&column='.rawurlencode($column).'&search='.rawurlencode($ico);
   $url=self::API.$path;if($first_url==='') {$first_url=$url;}
   $response=$this->request($path,$key,$refresh);
   $pages_checked++;
   if (!$response['ok']) {
    // The API documents 404 for several different causes, so only its explicit
    // search-negative response on the first validated page is a negative result.
    if ($page===1 && !empty($response['search_not_found'])) {
     return ['ok'=>true,'status'=>'not_found','matches'=>[],'pages_checked'=>$pages_checked,'url'=>$first_url,'retrieved_at'=>gmdate('c'),'cache_hit'=>false];
    }
    return ['ok'=>false,'status'=>$response['status'],'matches'=>[],'pages_checked'=>$pages_checked,'url'=>$first_url];
   }
   $d=$response['data'];
   $cache_hit=$cache_hit&&!empty($response['cache_hit']);
   if ($retrieved_at===null) {$retrieved_at=$response['retrieved_at']??gmdate('c');}
   if (!is_array($d)||!is_array($d['data']??null)||!isset($d['page'],$d['pages'])||!is_numeric($d['page'])||!is_numeric($d['pages'])||(int)$d['page']!==$page||(int)$d['pages']<0) {
    return ['ok'=>false,'status'=>'invalid_response','matches'=>[],'pages_checked'=>$pages_checked,'url'=>$first_url];
   }
   foreach($d['data'] as $row) {
    if (!is_array($row)) {return ['ok'=>false,'status'=>'invalid_response','matches'=>[],'pages_checked'=>$pages_checked,'url'=>$first_url];}
    $normalized=$this->row($row);
    $raw=$normalized['ICO']??null;
    if (!is_string($raw)&&!is_int($raw)) {continue;}
    $found=preg_replace('/\s+/u','',(string)$raw);
    if ($found!==$ico) {continue;}
    $hash=hash('sha256',wp_json_encode($row));
    if (!isset($seen[$hash])) {$matches[]=$row;$seen[$hash]=true;}
   }
   if ($page>=(int)$d['pages']) {
    return ['ok'=>true,'status'=>$matches?'observed':'not_found','matches'=>$matches,'pages_checked'=>$pages_checked,'url'=>$first_url,'retrieved_at'=>$retrieved_at,'cache_hit'=>$cache_hit];
   }
  }
  return ['ok'=>false,'status'=>'partial','matches'=>[],'pages_checked'=>$pages_checked,'url'=>$first_url];
 }

 private function search_exact(string $query,string $slug,string $column,string $verify_col,string $key,bool $refresh): array {
  $matches=[];$seen=[];$pages_checked=0;$first_url='';$retrieved_at=null;$cache_hit=true;
  $v_col=strtoupper($verify_col);
  for($page=1;$page<=self::MAX_PAGES;$page++) {
   $path='/data/'.rawurlencode($slug).'/search?page='.$page.'&column='.rawurlencode($column).'&search='.rawurlencode($query);
   $url=self::API.$path;if($first_url==='') {$first_url=$url;}
   $response=$this->request($path,$key,$refresh);
   $pages_checked++;
   if (!$response['ok']) {
    if ($page===1 && !empty($response['search_not_found'])) {
     return ['ok'=>true,'status'=>'not_found','matches'=>[],'pages_checked'=>$pages_checked,'url'=>$first_url,'retrieved_at'=>gmdate('c'),'cache_hit'=>false];
    }
    return ['ok'=>false,'status'=>$response['status'],'matches'=>[],'pages_checked'=>$pages_checked,'url'=>$first_url];
   }
   $d=$response['data'];
   $cache_hit=$cache_hit&&!empty($response['cache_hit']);
   if ($retrieved_at===null) {$retrieved_at=$response['retrieved_at']??gmdate('c');}
   if (!is_array($d)||!is_array($d['data']??null)||!isset($d['page'],$d['pages'])||!is_numeric($d['page'])||!is_numeric($d['pages'])||(int)$d['page']!==$page||(int)$d['pages']<0) {
    return ['ok'=>false,'status'=>'invalid_response','matches'=>[],'pages_checked'=>$pages_checked,'url'=>$first_url];
   }
   foreach($d['data'] as $row) {
    if (!is_array($row)) {return ['ok'=>false,'status'=>'invalid_response','matches'=>[],'pages_checked'=>$pages_checked,'url'=>$first_url];}
    $normalized=$this->row($row);
    $raw=$normalized[$v_col]??null;
    if (!is_string($raw)&&!is_int($raw)) {continue;}
    $found=strtoupper(preg_replace('/\s+/u','',(string)$raw));
    if ($found!==strtoupper(preg_replace('/\s+/u','',$query))) {continue;}
    $hash=hash('sha256',wp_json_encode($row));
    if (!isset($seen[$hash])) {$matches[]=$row;$seen[$hash]=true;}
   }
   if ($page>=(int)$d['pages']) {
    return ['ok'=>true,'status'=>$matches?'observed':'not_found','matches'=>$matches,'pages_checked'=>$pages_checked,'url'=>$first_url,'retrieved_at'=>$retrieved_at,'cache_hit'=>$cache_hit];
   }
  }
  return ['ok'=>false,'status'=>'partial','matches'=>[],'pages_checked'=>$pages_checked,'url'=>$first_url];
 }

 /** Restrict raw rows to the official tax-list fields; never copy credentials. */
 private function row(array $row): array {
  $allowed=['DIC','ICO','IC_DPH','IBAN','IDS','NAZOV_DS','NAZOV_SUBJEKTU','OBEC','PSC','ULICA_CISLO','NAZOV_STATU','STAT','DRUH_REG_DPH','DATUM_REG','DATUM_ZMENY_DRUHU_REG','PLAT_DPH_OD'];
  $out=[];
  foreach($row as $k=>$v) {
   $k=strtoupper((string)$k);
   if (in_array($k,$allowed,true)&&($v===null||is_scalar($v))) {$out[$k]=$v;}
  }
  return $out;
 }

 private function fields(array &$p,string $type,array $rows,string $url,string $id,?string $asof,string $retrieved_at): void {
  $map=$type==='dic'?['DIC'=>'dic']:['IC_DPH'=>'vat_id','DRUH_REG_DPH'=>'vat_registration_type','DATUM_REG'=>'vat_registered_on','DATUM_ZMENY_DRUHU_REG'=>'vat_registration_changed_on','PLAT_DPH_OD'=>'vat_payer_from'];
  foreach($map as $source=>$target) {
   $values=[];
   foreach($rows as $row) {
    $v=$row[$source]??null;
    if ($v===null||$v==='') {continue;}
    $v=(string)$v;
    if (strpos($source,'DATUM_')===0||$source==='PLAT_DPH_OD') {$v=$this->date($v);if($v===null) {continue;}}
    $values[$v]=$v;
   }
   // Multiple source values remain in the records, never silently choose one.
   if (count($values)===1) {$this->field($p,$target,array_values($values)[0],$url,$id,$asof,$retrieved_at);}
  }
  if ($type==='vat') {
   $this->field($p,'vat_registry_match',true,$url,$id,$asof,$retrieved_at);
   $this->field($p,'vat_registered',true,$url,$id,$asof,$retrieved_at);
   $payer=[];
   foreach($rows as $row) {
    $date=$this->date((string)($row['PLAT_DPH_OD']??''));
    if ($date!==null && $date<=gmdate('Y-m-d')) {$payer['yes']=true;continue;}
    $reg=preg_replace('/\s+/u','',(string)($row['DRUH_REG_DPH']??''));
    if (in_array($reg,['§7','§7a'],true)) {$payer['no']=false;}
   }
   if (count($payer)===1) {$this->field($p,'vat_payer',array_values($payer)[0],$url,$id,$asof,$retrieved_at);}
  }
 }

 private function field(array &$p,string $key,$value,string $url,string $id,?string $asof,string $retrieved_at): void {
  $e=Vorkei_Ico_Atlas_Profile::evidence($value,self::SOURCE,$url,$id,$asof);
  $e['source_updated_at']=$asof;
  $e['retrieved_at']=$retrieved_at;
  if (method_exists('Vorkei_Ico_Atlas_Profile','observe')) {Vorkei_Ico_Atlas_Profile::observe($p,$key,$e);}
  else {Vorkei_Ico_Atlas_Profile::field($p,$key,$value,self::SOURCE,$url,$id,$asof);}
 }

 private function date(string $value): ?string {
  $value=trim($value);
  foreach(['!d.m.Y','!Y-m-d'] as $format) {
   $d=DateTimeImmutable::createFromFormat($format,$value,new DateTimeZone('UTC'));
   $errors=DateTimeImmutable::getLastErrors();
   if($d && ($errors===false||(!$errors['warning_count']&&!$errors['error_count']))) {return $d->format('Y-m-d');}
  }
  return null;
 }

 private function request(string $path,string $key,bool $refresh): array {
  $url=self::API.$path;
  $cache_key='vorkei_fs_'.hash('sha256',$url.'|'.hash('sha256',$key));
  if (!$refresh) {
   $cached=get_transient($cache_key);
   if (is_array($cached)&&isset($cached['ok'],$cached['data'])&&$cached['ok']===true) {$cached['cache_hit']=true;return $cached;}
  }
  $pause='vorkei_fs_pause_'.hash('sha256',$key);
  if(get_transient($pause)!==false) {return ['ok'=>false,'status'=>'source_unavailable'];}
  $remaining=$this->deadline-microtime(true);
  if($remaining<0.15) {return ['ok'=>false,'status'=>'partial'];}
  $r=wp_safe_remote_get($url,['timeout'=>min(5.0,$remaining),'redirection'=>0,'sslverify'=>true,'limit_response_size'=>2097152,'headers'=>['Accept'=>'application/json','key'=>$key]]);
  if(is_wp_error($r)) {return ['ok'=>false,'status'=>'source_unavailable'];}
  $code=wp_remote_retrieve_response_code($r);
  $body=wp_remote_retrieve_body($r);
  if($code===429) {set_transient($pause,true,60);return ['ok'=>false,'status'=>'source_unavailable'];}
  if($code!==200) {
   $negative=$code===404 && preg_match('/\bSearch\s+not\s+found\b/i',$body)===1;
   return ['ok'=>false,'status'=>in_array($code,[401,403],true)?'requires_access':'source_unavailable','search_not_found'=>$negative];
  }
  $data=json_decode($body,true);
  if(!is_array($data)||json_last_error()!==JSON_ERROR_NONE) {return ['ok'=>false,'status'=>'invalid_response'];}
  $out=['ok'=>true,'status'=>'observed','data'=>$data,'retrieved_at'=>gmdate('c'),'cache_hit'=>false];
  set_transient($cache_key,$out,self::TTL);
  return $out;
 }
}
