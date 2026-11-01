<?php
if (!defined('ABSPATH')) { exit; }

/** Decode only the verified, two-column MF/18008/2014 micro-entity form. */
class Vorkei_Ico_Atlas_Financials {
 const NORMALIZER = 'ruz-687-v1';
 const TEMPLATE_URL = 'https://www.registeruz.sk/cruz-public/api/sablona?id=687';
 const UNIT_FORM_URL = 'https://www.registeruz.sk/cruz-public/domain/financialreport/attachment/11040868';
 private const FIELDS = [
  'assets'=>[0,1,'Majetok spolu'], 'noncurrent_assets'=>[0,2,'Neobežný majetok'],
  'current_assets'=>[0,14,'Obežný majetok'], 'short_term_receivables'=>[0,17,'Krátkodobé pohľadávky'],
  'cash_and_bank'=>[0,22,'Peniaze a účty v bankách'],
  'equity'=>[1,25,'Vlastné imanie'], 'share_capital'=>[1,26,'Základné imanie'],
  'liabilities'=>[1,34,'Záväzky'], 'current_liabilities_excluding_loans'=>[1,38,'Krátkodobé záväzky okrem rezerv, úverov a výpomoci'],
  'operating_revenue'=>[2,1,'Výnosy z hospodárskej činnosti'], 'goods_sales'=>[2,2,'Tržby z predaja tovaru'],
  'service_sales'=>[2,3,'Tržby z predaja vlastných výrobkov a služieb'],
  'operating_expenses'=>[2,8,'Náklady na hospodársku činnosť'], 'personnel_expenses'=>[2,12,'Osobné náklady'],
  'operating_profit'=>[2,18,'Výsledok hospodárenia z hospodárskej činnosti'],
  'financial_revenue'=>[2,20,'Výnosy z finančnej činnosti'], 'financial_expenses'=>[2,27,'Náklady na finančnú činnosť'],
  'profit_before_tax'=>[2,35,'Výsledok hospodárenia pred zdanením'], 'income_tax'=>[2,36,'Daň z príjmov'],
  'profit_after_tax'=>[2,38,'Výsledok hospodárenia po zdanení'],
 ];

 /** Raw record.value is never changed; all interpretation lives beside it. */
 public static function enrich(array $profile): array {
  $records=$profile['groups']['financials']['records']??[];
  if (!$records) { return $profile; }
  self::mark_versions($records);
  $template=null; $entities=[]; $statements=[];
  foreach ($records as $record) {
   $d=$record['value']??null;
   if (!is_array($d)) { continue; }
   if ((int)($d['id']??0)===687 && isset($d['tabulky'],$d['nariadenieMF'])) { $template=$d; }
   if (isset($d['idUJ'],$d['idUctovnychVykazov'])) { $statements[(string)$d['id']]=$d; }
  }
  foreach ($profile['groups']['registration']['records']??[] as $record) {
   $d=$record['value']??null;
   if (is_array($d) && isset($d['id'],$d['ico'])) { $entities[(string)$d['id']]=$d; }
  }
  $metrics=array_values(array_filter($profile['groups']['metrics']['records']??[],static fn($r)=>($r['normalizer']??'')!==self::NORMALIZER));
  $profile['conflicts']=array_values(array_filter($profile['conflicts']??[],static fn($c)=>($c['normalizer']??'')!==self::NORMALIZER));
  $observations=[];
  foreach ($records as &$record) {
   $d=$record['value']??null;
   if (!is_array($d) || (int)($d['idSablony']??0)!==687 || !isset($d['obsah']['tabulky'])) { continue; }
   $record['normalizer']=self::NORMALIZER;
   $record['decoded_tables']=[]; $record['normalized_facts']=[]; $record['comparative_facts']=[]; $record['financial_issues']=[];
   if (!self::valid_template($template)) { $record['financial_issues'][]='template_687_missing_or_invalid'; continue; }
   $title=$d['obsah']['titulnaStrana']??[];
   if (!empty($title['ico']) && (string)$title['ico']!==($profile['ico']??'')) { $record['financial_issues'][]='report_ico_mismatch'; continue; }
   $period=self::period($title['obdobieOd']??null,$title['obdobieDo']??null);
   $previous=self::period($title['predchadzajuceObdobieOd']??null,$title['predchadzajuceObdobieDo']??null);
   if (!$period) { $record['financial_issues'][]='current_period_not_disclosed'; }
   $statement=$statements[(string)($d['idUctovnejZavierky']??'')]??[];
   $entity=$entities[(string)($statement['idUJ']??'')]??[];
   $consolidated=is_bool($statement['konsolidovana']??null)?$statement['konsolidovana']:(($entity['konsolidovana']??null)===false?false:null);
   $identity=['report_id'=>$d['id']??null,'statement_id'=>$d['idUctovnejZavierky']??null,
    'report_version'=>hash('sha256',wp_json_encode($d)), 'entity_name'=>$title['nazovUctovnejJednotky']??null,
    'ico'=>$title['ico']??null,'consolidated'=>$consolidated];
   $record['report_identity']=$identity;
   $unitUrl=self::unit_url($d);
   $decoded=[]; $filled=0; $blank=0;
   foreach ($d['obsah']['tabulky'] as $i=>$table) {
    $spec=$template['tabulky'][$i]??null; $data=$table['data']??null;
    if (!is_array($spec) || !is_array($data) || array_keys($data)!==range(0,count($data)-1) || count($data)!==count($spec['riadky'])*2) {
     $record['financial_issues'][]='table_'.(string)$i.'_invalid_layout'; continue;
    }
    $rows=[];
    foreach ($spec['riadky'] as $j=>$row) {
     $rawCurrent=$data[$j*2]; $rawPrevious=$data[$j*2+1];
     $current=self::number($rawCurrent); $comparative=self::number($rawPrevious);
     $rows[]=['row'=>$row['cisloRiadku'],'label'=>$row['text']['sk']??'',
      'current'=>$rawCurrent,'previous'=>$rawPrevious,'current_value'=>$current,'previous_value'=>$comparative,
      'current_availability_status'=>self::cell_status($rawCurrent),'previous_availability_status'=>self::cell_status($rawPrevious)];
     $decoded[$i][(int)$row['cisloRiadku']]=[$rawCurrent,$rawPrevious,$row['text']['sk']??''];
     if ($rawCurrent===null || $rawCurrent==='') { $blank++; } else { $filled++; }
    }
    $record['decoded_tables'][]=['name'=>$spec['nazov']['sk']??'','table_index'=>$i,'template_id'=>687,
     'template_url'=>self::TEMPLATE_URL,'current_period'=>$period,'previous_period'=>$previous,
     'currency'=>'EUR','unit'=>'1','currency_and_unit'=>'EUR, celé eurá','unit_source_url'=>$unitUrl,'rows'=>$rows];
   }
   if ($blank>0 && $filled>0 && $blank/($blank+$filled)>.8) { $record['financial_issues'][]='sparse_report_preserve_explicit_zero_and_blank'; }
   foreach (self::FIELDS as $code=>[$table,$row,$label]) {
    if (!isset($decoded[$table][$row])) { continue; }
    $cell=$decoded[$table][$row];
    $record['normalized_facts'][$code]=self::fact($record,$identity,$code,$label,$table,$row,0,$cell[0],$period,$unitUrl,$cell[2]);
    if ($previous) { $record['comparative_facts'][$code]=self::fact($record,$identity,$code,$label,$table,$row,1,$cell[1],$previous,$unitUrl,$cell[2]); }
   }
   foreach (['normalized_facts','comparative_facts'] as $scope) {
    $facts=$record[$scope];
    foreach ($facts as $fact) {
     if ($fact['value']!==null && $fact['period']!==null && is_bool($fact['consolidated'])) { $observations[]=$fact; }
    }
    foreach (self::ratios($record,$facts,$scope) as $metric) { $metrics[]=$metric; }
   }
  }
  unset($record);
  self::comparison_conflicts($profile,$observations);
  $profile['groups']['financials']['records']=$records;
  $profile['groups']['metrics']['records']=$metrics;
  if ($metrics) { $profile['groups']['metrics']['availability_status']='observed'; }
  return $profile;
 }

 private static function valid_template(?array $template): bool {
  if (!$template || (int)($template['id']??0)!==687 || ($template['nariadenieMF']??'')!=='MF/18008/2014' || count($template['tabulky']??[])!==3) { return false; }
  foreach ([[1,23],[24,45],[1,38]] as $i=>[$from,$to]) {
   $table=$template['tabulky'][$i];
   if ((int)($table['pocetDatovychStlpcov']??0)!==2 || array_column($table['riadky']??[],'cisloRiadku')!==range($from,$to)) { return false; }
  }
  return true;
 }
 private static function mark_versions(array &$records): void {
  $latest=[];
  foreach ($records as $i=>$record) {
   if ((int)($record['value']['idSablony']??0)!==687) { continue; }
   $key=($record['source']??'').':'.($record['source_record_id']??'');
   $date=($record['source_updated_at']??'').'|'.($record['retrieved_at']??'');
   if (!isset($latest[$key]) || strcmp($date,$latest[$key]['date'])>=0) { $latest[$key]=['index'=>$i,'date'=>$date]; }
  }
  foreach ($records as $i=>&$record) {
   if ((int)($record['value']['idSablony']??0)!==687) { continue; }
   $key=($record['source']??'').':'.($record['source_record_id']??'');
   $record['superseded']=$latest[$key]['index']!==$i;
   if ($record['superseded']) { $record['superseded_by']=hash('sha256',wp_json_encode($records[$latest[$key]['index']]['value'])); }
   else { unset($record['superseded_by']); }
  }
  unset($record);
 }
 private static function period($from,$to): ?array {
  if (!is_string($from) || !is_string($to) || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D',$from) || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D',$to) || $from>$to) { return null; }
  return ['from'=>$from,'to'=>$to];
 }
 private static function number($raw) {
  if (is_int($raw)) { return $raw; }
  if (!is_string($raw) || !preg_match('/^-?\d+$/D',$raw)) { return null; }
  $value=filter_var($raw,FILTER_VALIDATE_INT);
  return $value===false?null:$value;
 }
 private static function cell_status($raw): string {
  if ($raw===null || $raw==='') { return 'not_disclosed'; }
  return self::number($raw)===null?'invalid_value':'observed';
 }
 private static function unit_url(array $report): string {
  foreach ($report['prilohy']??[] as $attachment) {
   if (($attachment['mimeType']??'')==='application/pdf' && (int)($attachment['pocetStran']??0)>0 && preg_match('/^MUJ_/i',$attachment['meno']??'') && !empty($attachment['id'])) {
    return 'https://www.registeruz.sk/cruz-public/domain/financialreport/attachment/'.(int)$attachment['id'];
   }
  }
  return self::UNIT_FORM_URL;
 }
 private static function fact(array $record,array $identity,string $code,string $label,int $table,int $row,int $column,$raw,?array $period,string $unitUrl,string $sourceLabel): array {
  return $identity+['value'=>self::number($raw),'raw_value'=>$raw,'fieldcode'=>$code,'label'=>$label,
   'source_label'=>$sourceLabel,'table'=>$table,'row'=>$row,'column'=>$column===0?'current':'comparative',
   'column_index'=>$column,'source_column'=>$table===1?$column+3:$column+1,'period'=>$period,
   'currency'=>'EUR','unit'=>'1','unit_basis'=>'MF/18008/2014, Úč MÚJ: v celých eurách','unit_source_url'=>$unitUrl,
   'template_id'=>687,'template_url'=>self::TEMPLATE_URL,'kind'=>'reported','source'=>$record['source']??'RÚZ',
   'source_url'=>$record['source_url']??'','source_record_id'=>$record['source_record_id']??'',
   'retrieved_at'=>$record['retrieved_at']??null,'source_updated_at'=>$record['source_updated_at']??null,
   'valid_as_of'=>null,'availability_status'=>self::cell_status($raw),'match_method'=>'exact_ico',
   'superseded'=>$record['superseded']??false];
 }
 private static function ratios(array $record,array $facts,string $scope): array {
  $out=[];
  foreach (['net_profit_margin'=>['profit_after_tax','operating_revenue','Čistý zisk / výnosy z hospodárskej činnosti'],
   'equity_ratio'=>['equity','assets','Vlastné imanie / majetok'], 'debt_ratio'=>['liabilities','assets','Záväzky / majetok']] as $code=>[$numerator,$denominator,$label]) {
   $a=$facts[$numerator]??null; $b=$facts[$denominator]??null;
   if (!$a || !$b || $a['value']===null || $b['value']===null || $b['value']===0 || $a['period']===null || !is_bool($a['consolidated'])) { continue; }
   foreach (['period','currency','unit','consolidated','report_id','report_version','column'] as $dimension) {
    if ($a[$dimension]!==$b[$dimension]) { continue 2; }
   }
   $out[]=['value'=>$a['value']/$b['value']*100,'metric_code'=>$code,'label'=>$label,'kind'=>'calculated',
    'formula'=>$numerator.' / '.$denominator.' * 100','inputs'=>[$numerator=>$a,$denominator=>$b],
    'period'=>$a['period'],'unit'=>'%','currency'=>null,'consolidated'=>$a['consolidated'],
    'source'=>'Výpočet IČO ATLAS','source_url'=>$record['source_url']??'',
    'source_record_id'=>'financial-metric:'.$a['report_id'].':'.$a['report_version'].':'.$scope.':'.$code,
    'retrieved_at'=>$record['retrieved_at']??null,'source_updated_at'=>$record['source_updated_at']??null,
    'valid_as_of'=>null,'availability_status'=>'observed','match_method'=>'exact_report_inputs',
    'normalizer'=>self::NORMALIZER,'report_id'=>$a['report_id'],'report_version'=>$a['report_version'],
    'superseded'=>$record['superseded']??false];
  }
  return $out;
 }
 private static function comparison_conflicts(array &$profile,array $observations): void {
  $byPeriod=[]; $seen=[];
  foreach ($observations as $fact) {
   if (!empty($fact['superseded'])) { continue; }
   $key=wp_json_encode([$fact['fieldcode'],$fact['period'],$fact['currency'],$fact['unit'],$fact['consolidated']]);
   foreach ($byPeriod[$key]??[] as $other) {
    if ($other['column']===$fact['column'] || $other['value']===$fact['value']) { continue; }
    $ids=[$other['report_id'].':'.$other['column'],$fact['report_id'].':'.$fact['column']]; sort($ids);
    $id=hash('sha256',$key.wp_json_encode($ids));
    if (isset($seen[$id])) { continue; } $seen[$id]=true;
    $profile['conflicts'][]=['kind'=>'financial_comparison','normalizer'=>self::NORMALIZER,'field'=>$fact['fieldcode'],
     'period'=>$fact['period'],'previous'=>$other,'incoming'=>$fact,'resolution'=>'preserve_both_report_observations'];
   }
   $byPeriod[$key][]=$fact;
  }
 }
}
