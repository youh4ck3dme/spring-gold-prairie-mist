<?php
if (!defined('ABSPATH')) { exit; }
/** Immutable observations plus a deterministic current view. */
class Vorkei_Ico_Atlas_Profile {
 const GROUPS=['registration','activities','people','ownership','financials','metrics','tax','accounts','debts','proceedings','public_sector','subsidies','licenses','intellectual_property','contacts','history'];
 public static function blank(string $ico): array {
  $p=['schema_version'=>'2.1','ico'=>$ico,'fields'=>[],'observations'=>[],'groups'=>[],'checks'=>[],'conflicts'=>[]];
  foreach(self::GROUPS as $g) {$p['groups'][$g]=['availability_status'=>'not_checked','records'=>[]];} return $p;
 }
 public static function valid(array $p,string $ico): bool {
  if(($p['ico']??null)!==$ico||!is_array($p['fields']??null)||!is_array($p['groups']??null)) {return false;}
  foreach($p['fields'] as $e) {if(!is_array($e)||!array_key_exists('value',$e)||!is_string($e['source_url']??null)) {return false;}}
  foreach($p['groups'] as $g) {if(!is_array($g)||!is_array($g['records']??null)) {return false;}}
  return true;
 }
 public static function evidence($value,string $source,string $url,string $id='',?string $asof=null,string $status='observed'): array {
  return ['value'=>$value,'source'=>$source,'source_url'=>esc_url_raw($url,['https']),'source_record_id'=>$id,'retrieved_at'=>gmdate('c'),'source_updated_at'=>$asof,'valid_as_of'=>null,'valid_from'=>null,'valid_to'=>null,'match_method'=>'exact_ico','kind'=>'reported','availability_status'=>$status];
 }
 private static function migrate(array $e): array {
  if(!array_key_exists('source_updated_at',$e) && ($e['source']??'')==='RÚZ') {$e['source_updated_at']=$e['valid_as_of']??null;$e['valid_as_of']=null;}
  if(is_array($e['value']??null) && $e['value']===[]) {$e['availability_status']='not_checked';}
  $e += ['kind'=>'reported','match_method'=>'exact_ico','retrieved_at'=>null,'source_updated_at'=>null,'valid_as_of'=>null,'valid_from'=>null,'valid_to'=>null,'availability_status'=>'observed'];
  return $e;
 }
 private static function fingerprint(array $e): string {
  $copy=$e; unset($copy['retrieved_at'],$copy['availability_status'],$copy['selection_reason']);
  return hash('sha256',wp_json_encode($copy));
 }
 public static function field(array &$p,string $key,$value,string $source,string $url,string $id='',?string $asof=null): void {
  if($value===null||$value==='') {return;}
  self::observe($p,$key,self::evidence($value,$source,$url,$id,$asof));
 }
 public static function observe(array &$p,string $key,array $e): void {
  $e=self::migrate($e);
  if(in_array($key,['active','status'],true)&&self::source_key($e)==='registry') {$e['availability_status']='unconfirmed';$e['kind']='candidate';$e['match_method']='upstream_declared';}
  $observations=$p['observations'][$key]??[];
  if(isset($p['fields'][$key])) {$observations[] = self::migrate($p['fields'][$key]);}
  $observations[]=$e;$unique=[];
  foreach($observations as $v) {if(is_array($v)&&array_key_exists('value',$v)) {$unique[self::fingerprint($v)]=$v;}}
  $p['observations'][$key]=array_values($unique);
  self::select($p,$key);
 }
 private static function source_key(array $e): string {
  $host=strtolower((string)parse_url($e['source_url']??'',PHP_URL_HOST));
  if(in_array($host,['orsr.sk','www.orsr.sk','sluzby.orsr.sk'],true)) {return 'orsr';}
  if($host==='api.statistics.sk'||$host==='rpo.statistics.sk') {return 'rpo';}
  if(str_ends_with($host,'.financnasprava.sk')||$host==='financnasprava.sk') {return 'fs';}
  if($host==='www.registeruz.sk'||$host==='registeruz.sk') {return 'ruz';}
  return 'registry';
 }
 private static function select(array &$p,string $key): void {
  $rows=$p['observations'][$key]??[];$best=null;$score=-PHP_INT_MAX;
  foreach($rows as $e) {
   $src=self::source_key($e);$checkKey=$src==='fs'?(str_starts_with($key,'vat_')?'fs_vat':($key==='dic'?'fs_dic':'fs')):$src;$status=$p['checks'][$checkKey]['availability_status']??$p['checks'][$src]['availability_status']??'observed';
   $usable=($e['availability_status']??'observed')==='observed';
   $ended=!empty($e['valid_to'])&&$e['valid_to']<gmdate('Y-m-d');
   $stale=in_array($status,['source_unavailable','invalid_response','requires_access','not_found','stale','conflict','partial','unsupported','not_configured'],true);
   $rank=['orsr'=>100,'rpo'=>90,'ruz'=>50,'registry'=>20,'fs'=>70][$src];
   if(in_array($key,['dic','vat_id','vat_registration_type','vat_registered_on','vat_payer_since','vat_payer_from','vat_registered','vat_payer','vat_registry_match','vat_registration_changed_on'],true)&&$src==='fs') {$rank=120;}
   $current=($usable?1000:0)+($ended?-2000:0)+($stale?-500:0)+$rank;
   $date=$e['valid_from']??$e['source_updated_at']??'';
   $bestdate=$best['valid_from']??$best['source_updated_at']??'';
   if($best===null||$current>$score||($current===$score&&strcmp($date,$bestdate)>0)||($current===$score&&$date===$bestdate&&strcmp($e['retrieved_at']??'',$best['retrieved_at']??'')>=0)) {
    $best=$e;$score=$current;if($stale&&$usable) {$best['availability_status']='stale';}
   }
  }
  if($best!==null) {$best['selection_reason']='Aktuálna hodnota podľa zdroja, účinnosti a dostupnosti';$p['fields'][$key]=$best;}
 }
 private static function comparable(string $key,$value): string {
  if(!is_string($value)) {return wp_json_encode($value);}
  if($key==='name') {return mb_strtolower(preg_replace('/[\s.,]+/u','',$value),'UTF-8');}
  if($key==='address') {
   $text=mb_strtolower($value,'UTF-8');$text=str_replace(['slovenská republika','slovensko'],'',$text);
   $text=preg_replace('/(\d{3})\s+(\d{2})(?!\d)/u','$1$2',$text);
   $tokens=preg_split('/[\s,]+/u',trim($text),-1,PREG_SPLIT_NO_EMPTY);sort($tokens);return implode(' ',$tokens);
  }
  return $value;
 }
 public static function merge(array $old,array $new): array {
  $p=array_replace(self::blank((string)($new['ico']??$old['ico']??'')),$old);
  $p['schema_version']='2.1';$p['checks']=array_replace($old['checks']??[],$new['checks']??[]);
  foreach([$old,$new] as $part) {
   foreach($part['observations']??[] as $k=>$values) {foreach($values as $e) {self::observe($p,$k,$e);}}
   foreach($part['fields']??[] as $k=>$e) {self::observe($p,$k,$e);}
  }
  foreach($new['groups']??[] as $k=>$g) {
   $previous=$p['groups'][$k]??['records'=>[]]; $records=[];
   foreach(array_merge($previous['records']??[],$g['records']??[]) as $e) {
    $e=self::migrate($e);
    $identity=($e['source']??'').':'.($e['source_record_id']??'').':'.hash('sha256',wp_json_encode($e['value']??null));
    $records[$identity]=$e;
   }
   $p['groups'][$k]=array_replace($previous,$g);$p['groups'][$k]['records']=array_values($records);
   if(!empty($previous['records'])&&empty($g['records'])&&in_array($g['availability_status']??'',['not_found','source_unavailable','requires_access'],true)) {
    $p['groups'][$k]['last_attempt_status']=$g['availability_status'];$p['groups'][$k]['availability_status']='stale';
   }
  }
  $conflicts=array_merge($old['conflicts']??[],$new['conflicts']??[]);
  foreach($p['observations'] as $key=>$values) {
   $distinct=[];
   foreach($values as $e) {
    if(!empty($e['valid_to'])||($e['availability_status']??'observed')!=='observed') {continue;}
    $distinct[self::comparable($key,$e['value'])]=true;
   }
   if(count($distinct)>1) {$conflicts[]=['field'=>$key,'kind'=>'different_source_observations','selected_source'=>$p['fields'][$key]['source']??'','resolution'=>'retain_all_observations'];}
  }
  $p['conflicts']=array_values(array_unique($conflicts,SORT_REGULAR));
  return $p;
 }
 /** One projection for storage, dashboard and both export formats. */
 public static function summarize(array $p): array {
  $v=static fn($k)=>$p['fields'][$k]['value']??null;
  $found=is_string($v('name'))&&$v('name')!=='';
  $legal=$p['fields']['registration_status']??null;$source=$legal?self::source_key($legal):'';
  $fresh=$legal && ($legal['availability_status']??'observed')==='observed'&&in_array($source,['rpo','orsr'],true);
  $registered=$fresh&&in_array($legal['value'],['registered','registered_without_termination'],true);
  $ended=$fresh&&in_array($legal['value'],['terminated','dissolved'],true);
  if(($p['checks']['fs_vat']['availability_status']??'')==='not_found') {
   foreach(['vat_id','vat_registered_on','vat_payer_from','vat_registration_type'] as $field) {if(isset($p['fields'][$field])) {$p['fields'][$field]['availability_status']='stale';}}
  }
  $status=$registered?'Evidovaná spoločnosť bez zobrazeného výmazu':($ended?'Zdroj uvádza zánik spoločnosti':'Právny stav aktuálne nepotvrdený');
  $address=$v('address');
  if(is_array($address)) {$address=implode(', ',array_filter([$address['street']??'',trim(($address['postal_code']??'').' '.($address['city']??'')),$address['country']??'']));}
  return ['schema_version'=>'2.1','ico'=>$p['ico'],'found'=>$found,'name'=>$v('name'),'legal_form'=>$v('legal_form'),'address'=>$address,'dic'=>$v('dic'),'vat_id'=>(($p['checks']['fs_vat']['availability_status']??'')==='not_found'?null:$v('vat_id')),'founded_on'=>$v('founded_on'),'dissolved_on'=>$v('dissolved_on'),'active'=>$registered?true:($ended?false:null),'verified'=>(bool)($registered||$ended),'verification_status'=>$registered?'verified_registered':($ended?'verified_risky':'unconfirmed'),'status'=>$status,'is_risky'=>$ended,'risk_reason'=>$ended?$status:null,'verified_at'=>($registered||$ended)?str_replace('T',' ',substr($legal['retrieved_at']??'',0,19)):null,'profile'=>$p,'pending'=>!empty($p['checks']['ruz']['pending'])&&empty($p['checks']['ruz']['error']),'source'=>'Pozri pôvod jednotlivých údajov'];
 }
 public static function metrics(array $f): array {
  $out=[];$rules=['profit_margin'=>['profit_after_tax','revenue',100],'equity_ratio'=>['equity','assets',100],'debt_ratio'=>['liabilities','assets',100],'current_ratio'=>['current_assets','current_liabilities',1]];
  foreach($rules as $key=>[$a,$b,$scale]) {
   if(!isset($f[$a],$f[$b])) {continue;}$x=$f[$a];$y=$f[$b];
   foreach(['period','currency','unit','consolidated'] as $dim) {if(!isset($x[$dim],$y[$dim])||$x[$dim]!==$y[$dim]) {continue 2;}}
   if(!is_numeric($x['value']??null)||!is_numeric($y['value']??null)||(float)$y['value']==0) {continue;}
   $out[$key]=['value'=>(float)$x['value']/(float)$y['value']*$scale,'kind'=>'calculated','formula'=>"$a / $b * $scale",'inputs'=>[$a=>$x,$b=>$y],'period'=>$x['period'],'unit'=>$scale===100?'%':'ratio'];
  }return $out;
 }
}
