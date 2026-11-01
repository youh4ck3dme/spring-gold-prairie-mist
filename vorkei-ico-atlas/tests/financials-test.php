<?php
if (PHP_SAPI!=='cli') { exit; }
define('ABSPATH',__DIR__.'/');
$finCache=[]; $finHttp=[]; $finCalls=[];
function esc_url_raw($value,$protocols=[]) { return $value; }
function wp_json_encode($value,$flags=0) { return json_encode($value,$flags); }
function get_transient($key) { global $finCache; return $finCache[$key]??false; }
function set_transient($key,$value,$ttl) { global $finCache; $finCache[$key]=$value; }
function delete_transient($key) { global $finCache; unset($finCache[$key]); }
function is_wp_error($value) { return $value instanceof Exception; }
function wp_safe_remote_get($url,$options) { global $finHttp,$finCalls; $finCalls[]=$url; return $finHttp[$url]??new Exception('offline'); }
function wp_remote_retrieve_response_code($response) { return $response['code']; }
function wp_remote_retrieve_body($response) { return $response['body']; }
require __DIR__.'/../includes/class-ico-atlas-profile.php';
require __DIR__.'/../includes/class-ico-atlas-financials.php';
require __DIR__.'/../includes/class-ico-atlas-ruz.php';
function fin_ok($condition,string $message): void { if (!$condition) { throw new RuntimeException('FAIL '.$message); } echo 'PASS '.$message."\n"; }
function fin_reports(array $profile): array {
 $reports=[];
 foreach ($profile['groups']['financials']['records'] as $record) {
  if (($record['value']['idSablony']??0)===687) { $reports[(string)$record['value']['id']]=$record; }
 }
 return $reports;
}
$raw=json_decode(file_get_contents(__DIR__.'/fixtures/fin-csv-profile.json'),true);
$profile=Vorkei_Ico_Atlas_Financials::enrich($raw); $reports=fin_reports($profile);
fin_ok(count($reports)===7,'all seven CSV periods decoded');
$expected=[6367716=>[8376,8376,3376,3376],6825779=>[7663,7404,33987,-972],7311534=>[18354,7686,44216,282],
 7724700=>[14186,8240,39241,554],8276215=>[16044,10939,48138,1792],8689103=>[17852,12710,46800,1771],9208437=>[0,null,null,0]];
foreach ($expected as $id=>$values) {
 $facts=$reports[(string)$id]['normalized_facts'];
 fin_ok([$facts['assets']['value'],$facts['equity']['value'],$facts['operating_revenue']['value'],$facts['profit_after_tax']['value']]===$values,'verified 687 row mapping '.$id);
}
foreach ($raw['groups']['financials']['records'] as $i=>$record) { fin_ok($record['value']===$profile['groups']['financials']['records'][$i]['value'],'original raw value unchanged '.$record['source_record_id']); }
$facts=$reports['9208437']['normalized_facts'];
fin_ok($facts['assets']['availability_status']==='observed' && $facts['assets']['raw_value']==='0','explicit zero is reported evidence');
fin_ok($facts['equity']['availability_status']==='not_disclosed' && $facts['equity']['value']===null,'blank equity stays missing');
fin_ok($reports['9208437']['comparative_facts']===[],'no invented comparative period for 2023');
fin_ok($reports['8689103']['comparative_facts']['assets']['period']===['from'=>'2021-01','to'=>'2021-12'],'comparison period is from title');
fin_ok($reports['8689103']['normalized_facts']['service_sales']['value']===46800,'own products and services kept distinct');
fin_ok($reports['8689103']['normalized_facts']['goods_sales']['value']===null,'blank goods sales never treated as zero');
fin_ok($reports['8689103']['normalized_facts']['assets']['currency']==='EUR' && $reports['8689103']['normalized_facts']['assets']['unit']==='1','verified whole-euro currency and unit');
fin_ok(str_ends_with($reports['8689103']['normalized_facts']['assets']['unit_source_url'],'/10183554'),'whole-euro evidence links to primary report PDF');
$metrics=$profile['groups']['metrics']['records'];
$margin=array_values(array_filter($metrics,static fn($metric)=>$metric['report_id']===8689103 && $metric['metric_code']==='net_profit_margin' && $metric['inputs']['profit_after_tax']['column']==='current'));
fin_ok(count($margin)===1 && abs($margin[0]['value']-1771/46800*100)<0.000001 && isset($margin[0]['formula'],$margin[0]['inputs']),'ratio retains exact reported inputs and formula');
fin_ok(count(array_filter($metrics,static fn($metric)=>$metric['report_id']===9208437))===0,'sparse 2023 and zero denominator produce no ratios');
fin_ok(count(array_filter($profile['conflicts'],static fn($conflict)=>($conflict['kind']??'')==='financial_comparison' && $conflict['field']==='assets'))>=1,'2019 current-vs-2020 comparative asset contradiction retained');
$again=Vorkei_Ico_Atlas_Financials::enrich($profile);
fin_ok(count($again['groups']['metrics']['records'])===count($metrics) && count($again['conflicts'])===count($profile['conflicts']),'normalization idempotent');
$bad=$raw;
foreach ($bad['groups']['financials']['records'] as &$record) { if (($record['value']['id']??0)===8689103) { array_pop($record['value']['obsah']['tabulky'][0]['data']); } } unset($record);
$bad=fin_reports(Vorkei_Ico_Atlas_Financials::enrich($bad));
fin_ok(!isset($bad['8689103']['normalized_facts']['assets']) && in_array('table_0_invalid_layout',$bad['8689103']['financial_issues'],true),'invalid row count cannot yield shifted financial values');
$changed=$raw; $new=$reports['8689103']; unset($new['normalized_facts'],$new['decoded_tables'],$new['comparative_facts']);
$new['value']['obsah']['tabulky'][0]['data'][0]='18000'; $new['source_updated_at']='2024-01-01'; $changed['groups']['financials']['records'][]=$new;
$changed=Vorkei_Ico_Atlas_Financials::enrich($changed);
$versions=array_values(array_filter($changed['groups']['financials']['records'],static fn($record)=>($record['value']['id']??0)===8689103));
fin_ok(count($versions)===2 && $versions[0]['superseded']===true && $versions[1]['superseded']===false,'superseded report version retained alongside new version');

// RUZ transport: completed job, resume, manual refresh, HTML and cached time.
$base=Vorkei_Ico_Atlas_Ruz::BASE.'api/'; $ico='51226511'; $blank=Vorkei_Ico_Atlas_Profile::blank($ico);
$entity=$raw['groups']['registration']['records'][0]['value']; $entity['idUctovnychZavierok']=[11,12,13];
$finHttp[$base.'uctovne-jednotky?zmenene-od=2000-01-01&ico='.$ico]=['code'=>200,'body'=>json_encode(['id'=>[1735997],'existujeDalsieId'=>false])];
$finHttp[$base.'uctovna-jednotka?id=1735997']=['code'=>200,'body'=>json_encode($entity)];
foreach ([11,12,13] as $id) { $finHttp[$base.'uctovna-zavierka?id='.$id]=['code'=>200,'body'=>json_encode(['id'=>$id,'idUJ'=>1735997,'obdobieOd'=>'2022-01','obdobieDo'=>'2022-12','idUctovnychVykazov'=>[]])]; }
$ruz=new Vorkei_Ico_Atlas_Ruz(); $result=$ruz->enrich($ico,$blank,true);
fin_ok($result['checks']['ruz']['pending']===true && count($finCalls)===3,'initial request bounded to three transport tasks');
$result=$ruz->enrich($ico,$result,false);
fin_ok($result['checks']['ruz']['pending']===false && count($finCalls)===5,'continuation resumes the unfinished queue');
$count=count($finCalls); $timestamp=$result['fields']['dic']['retrieved_at'];
$cachedResult=$ruz->enrich($ico,$blank,false);
fin_ok(count($finCalls)===$count && $cachedResult['fields']['dic']['retrieved_at']===$timestamp,'completed job and cache preserve original successful fetch timestamp');
delete_transient('vorkei_ruz_job_'.$ico);
$entityCacheKey='vorkei_ruz_'.md5($base.'uctovna-jednotka?id=1735997');
$finCache[$entityCacheKey]['retrieved_at']='2001-01-01T00:00:00+00:00';
$cachedResult=$ruz->enrich($ico,$blank,false);
fin_ok(count($finCalls)===$count && $cachedResult['fields']['dic']['retrieved_at']==='2001-01-01T00:00:00+00:00','transport cache timestamp is not replaced by a new job timestamp');
fin_ok($cachedResult['observations']['dic'][0]['retrieved_at']==='2001-01-01T00:00:00+00:00','field history also retains actual cached retrieval timestamp');
$entity['nazovUJ']='Refreshed legal name'; $finHttp[$base.'uctovna-jednotka?id=1735997']['body']=json_encode($entity);
$refreshed=$ruz->enrich($ico,$result,true);
fin_ok(count($finCalls)===$count+3 && $refreshed['fields']['name']['value']==='Refreshed legal name','manual refresh really invalidates job and relevant API caches');
fin_ok($refreshed['fields']['dic']['source_updated_at']==='2026-07-03' && $refreshed['fields']['dic']['valid_as_of']===null,'source update date never used as validity date');
$finHttp[$base.'uctovne-jednotky?zmenene-od=2000-01-01&ico=00000000']=['code'=>200,'body'=>'<html>upstream error</html>'];
$badHtml=$ruz->enrich('00000000',Vorkei_Ico_Atlas_Profile::blank('00000000'),true);
fin_ok($badHtml['checks']['ruz']['availability_status']==='source_unavailable','HTTP 200 HTML is unavailable, never not found');
$finHttp[$base.'uctovne-jednotky?zmenene-od=2000-01-01&ico=00000001']=['code'=>200,'body'=>'{"id":[],"existujeDalsieId":false}'];
$notFound=$ruz->enrich('00000001',Vorkei_Ico_Atlas_Profile::blank('00000001'),true);
fin_ok($notFound['checks']['ruz']['availability_status']==='not_found','only successful valid empty list yields not found');
echo "FINANCIAL AND RUZ TESTS PASSED\n";
