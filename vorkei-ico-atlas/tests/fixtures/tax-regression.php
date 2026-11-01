<?php
// Standalone, deterministic tests: no network or file writes.
if(PHP_SAPI!=='cli') {http_response_code(403);exit;}
define('ABSPATH',__DIR__.'/');
$options=[];$cache=[];$http=[];$calls=[];
function esc_url_raw($v,$p=[]){return $v;}
function wp_json_encode($v,$flags=0){return json_encode($v,$flags);}
function get_option($k,$d=false){global $options;return $options[$k]??$d;}
function get_transient($k){global $cache;return $cache[$k]??false;}
function set_transient($k,$v,$ttl){global $cache;$cache[$k]=$v;}
function is_wp_error($r){return $r instanceof Exception;}
function wp_safe_remote_get($url,$args){global $http,$calls;$calls[]=[$url,$args];return $http[$url]??new Exception('offline');}
function wp_remote_retrieve_response_code($r){return $r['code'];}
function wp_remote_retrieve_body($r){return is_string($r['body'])?$r['body']:json_encode($r['body']);}
function tax_ok($condition,$label){static $n=0;if(!$condition){throw new Exception('FAIL '.$label);}echo 'PASS '.(++$n).' '.$label."\n";}
require __DIR__.'/../../includes/class-ico-atlas-profile.php';
require __DIR__.'/../../includes/class-ico-atlas-tax.php';
$tax=new Vorkei_Ico_Atlas_Tax();$ico='51226511';$base=Vorkei_Ico_Atlas_Tax::API;
$blank=Vorkei_Ico_Atlas_Profile::blank($ico);
$r=$tax->enrich($ico,$blank);
tax_ok($r['checks']['fs']['availability_status']==='requires_access'&&count($calls)===0,'missing API key performs no request');
tax_ok($r['checks']['fs_dic']['availability_status']==='requires_access'&&$r['checks']['fs_vat']['availability_status']==='requires_access','separate source checks exist');
tax_ok($tax->discover()['availability_status']==='requires_access','discovery requires administrator credential');
$options=['vorkei_ico_fs_api_key'=>'fixture-secret','vorkei_ico_fs_dic_slug'=>'configured_dic','vorkei_ico_fs_vat_slug'=>'configured_vat'];
$catalog=[['name'=>'DIČ fixture','slug'=>'configured_dic','searchable'=>['ico'],'update_date'=>'2026-10-29 05:00:00'],['name'=>'DPH fixture','slug'=>'configured_vat','searchable'=>['ICO'],'update_date'=>'2026-10-30 05:00:00']];
$http[$base.'/lists']=['code'=>200,'body'=>$catalog];
foreach($catalog as $m) {$http[$base.'/lists/'.$m['slug']]=['code'=>200,'body'=>$m];}
$dic_query=$base.'/data/configured_dic/search?page=1&column=ico&search='.$ico;
$vat_query=$base.'/data/configured_vat/search?page=1&column=ICO&search='.$ico;
$vat_page2=$base.'/data/configured_vat/search?page=2&column=ICO&search='.$ico;
$http[$dic_query]=['code'=>200,'body'=>['page'=>1,'pages'=>1,'data'=>[['ico'=>$ico,'dic'=>'2120638003','nazov_ds'=>'Fixture']]]];
$http[$vat_query]=['code'=>200,'body'=>['page'=>1,'pages'=>2,'data'=>[['ICO'=>'51226512','IC_DPH'=>'SK0000000000']]]];
$http[$vat_page2]=['code'=>200,'body'=>['page'=>2,'pages'=>2,'data'=>[['ICO'=>'51 226 511','IC_DPH'=>'SK2120638003','DRUH_REG_DPH'=>'§7a','DATUM_REG'=>'14.12.2023','DATUM_ZMENY_DRUHU_REG'=>null]]]];
$r=$tax->enrich($ico,$blank,true);
tax_ok($r['fields']['dic']['value']==='2120638003','DIČ from exact IČO match');
tax_ok($r['fields']['vat_id']['value']==='SK2120638003'&&$r['checks']['fs_vat']['pages_checked']===2,'search checks all pages and ignores a different IČO');
tax_ok($r['fields']['vat_payer']['value']===false&&$r['fields']['vat_registered']['value']===true,'§7a registration is distinguished from VAT payer');
tax_ok($r['fields']['vat_registered_on']['value']==='2023-12-14','registration date is normalized with source evidence');
tax_ok($r['fields']['dic']['source_updated_at']==='2026-10-29 05:00:00'&&$r['fields']['vat_id']['source_updated_at']==='2026-10-30 05:00:00','per-list source dates are retained');
tax_ok(strpos(json_encode($r),'fixture-secret')===false,'credential is excluded from the entire profile');
tax_ok($calls[0][1]['headers']['key']==='fixture-secret'&&$calls[0][1]['redirection']===0,'credential stays in a header with redirects disabled');
$count=count($calls);$tax->enrich($ico,$blank,false);
tax_ok(count($calls)===$count,'one hour request cache avoids repeated calls');
$tax->enrich($ico,$blank,true);
tax_ok(count($calls)>$count,'explicit refresh bypasses request cache');
$discovery=$tax->discover();
tax_ok(count($discovery['lists'])===2&&$discovery['lists'][0]['searchable']===['ico']&&strpos(json_encode($discovery),'fixture-secret')===false,'discovery returns real metadata without credentials');

$cache=[];$calls=[];$unsupported=$catalog;$unsupported[1]['searchable']=['ic_dph'];
$http[$base.'/lists/configured_vat']=['code'=>200,'body'=>$unsupported[1]];
$r2=$tax->enrich($ico,$r,true);
tax_ok($r2['checks']['fs_vat']['availability_status']==='unsupported','unsupported search column is reported without guessing');
tax_ok(!array_filter($calls,static function($c)use($vat_query){return $c[0]===$vat_query;}),'unsupported column never triggers a search request');
tax_ok($r2['fields']['vat_id']['value']==='SK2120638003','unsupported query preserves previously obtained VAT evidence');

$http[$base.'/lists/configured_vat']=['code'=>200,'body'=>$catalog[1]];
$http[$vat_query]=['code'=>200,'body'=>['page'=>1,'pages'=>1,'itemsCount'=>0,'data'=>[]]];
$r3=$tax->enrich($ico,$blank,true);
tax_ok($r3['checks']['fs_vat']['availability_status']==='not_found'&&$r3['fields']['vat_registry_match']['value']===false&&!isset($r3['fields']['vat_id']),'complete empty list match does not manufacture an IČ DPH');

$http[$vat_query]=['code'=>503,'body'=>'updating'];
$r4=$tax->enrich($ico,$r,true);
tax_ok($r4['checks']['fs_vat']['availability_status']==='source_unavailable'&&$r4['fields']['vat_id']['value']==='SK2120638003','updating source preserves prior evidence');
$http[$vat_query]=['code'=>200,'body'=>['page'=>1,'pages'=>2,'data'=>[['ICO'=>$ico,'IC_DPH'=>'SK9999999999']]]];
$http[$vat_page2]=['code'=>503,'body'=>'updating'];
$r5=$tax->enrich($ico,$r,true);
tax_ok($r5['fields']['vat_id']['value']==='SK2120638003','incomplete pagination cannot replace a prior verified value');
$http[$base.'/lists']=['code'=>401,'body'=>'unauthorized'];
tax_ok($tax->enrich($ico,$blank,true)['checks']['fs']['availability_status']==='requires_access','authentication failure is not a negative registry result');
$cache=[];$calls=[];$http[$base.'/lists']=['code'=>429,'body'=>'rate limited'];
$tax->enrich($ico,$blank,true);$count=count($calls);$limited=$tax->enrich($ico,$blank,true);
tax_ok(count($calls)===$count&&$limited['checks']['fs']['availability_status']==='source_unavailable','429 pauses subsequent transport without a false negative');
echo "ALL TAX REGRESSIONS PASSED\n";
