<?php
// Offline regression against actual public RPO JSON and Windows-1250 ORSR samples.
if(PHP_SAPI!=='cli') {exit;}
define('ABSPATH',__DIR__.'/');
$cache=[];$http=[];$calls=[];
function esc_url_raw($v,$allowed=[]) {return $v;}
function wp_json_encode($v,$flags=0) {return json_encode($v,$flags);}
function get_transient($k) {global $cache;return $cache[$k]??false;}
function set_transient($k,$v,$ttl) {global $cache;$cache[$k]=$v;}
function is_wp_error($v) {return $v instanceof Exception;}
function wp_safe_remote_get($url,$args) {global $http,$calls;$calls[]=$url;return $http[$url]??new Exception('offline');}
function wp_remote_retrieve_response_code($r) {return $r['code'];}
function wp_remote_retrieve_body($r) {return $r['body'];}
function assert_register($test,$label) {if(!$test) {throw new RuntimeException('FAIL '.$label);}echo 'PASS '.$label."\n";}
require __DIR__.'/../../includes/class-ico-atlas-profile.php';
require __DIR__.'/../../includes/class-ico-atlas-registers.php';
$root='https://api.statistics.sk/rpo/v1/';$or='https://www.orsr.sk/';$ico='51226511';
$search=$root.'search?identifier='.$ico;$entity=$root.'entity/9377936?showHistoricalData=true';$os=$or.'hladaj_ico.asp?ICO='.$ico.'&SID=0';$primary=$or.'vypis.asp?ID=694228&SID=3&P=0';$transferred=$or.'vypis.asp?ID=406191&SID=4&P=0';
foreach([$search=>'registers-rpo-search.json',$entity=>'registers-rpo-entity.json',$os=>'registers-orsr-search.html',$primary=>'registers-orsr-primary.html',$transferred=>'registers-orsr-transferred.html'] as $url=>$file) {$http[$url]=['code'=>200,'body'=>file_get_contents(__DIR__.'/'.$file)];}
$adapter=new Vorkei_Ico_Atlas_Registers();$p=$adapter->enrich($ico,Vorkei_Ico_Atlas_Profile::blank($ico));
assert_register($p['fields']['name']['value']==='EB-EU s. r. o.','actual company name with Slovak encoding');
assert_register(($p['checks']['rpo']['availability_status']??null)==='observed'&&($p['checks']['orsr']['availability_status']??null)==='observed','actual RPO and ORSR parsed');
assert_register($p['fields']['registration_status']['value']==='registered_without_termination','registration existence is distinct from business activity');
assert_register($p['fields']['register_number']['value']==='Sro/49430/S','current Banska Bystrica file selected');
assert_register($p['checks']['orsr']['source_url']===$primary,'no hardcoded primary identifier in discovery');
assert_register(count($p['fields']['statutaries']['value'])===1&&$p['fields']['statutaries']['value'][0]['personName']['formatedName']==='Erik Babčan','current statutory body excludes historical entries');
assert_register(count($p['fields']['shareholders']['value'])===2,'two current shareholders');
assert_register(count($p['groups']['history']['records'])>=5,'dated previous names addresses and people retained');
$ownership=$p['fields']['shareholders']['value'];$t=array_values(array_filter($ownership,fn($r)=>($r['identifier']??'')==='52374220'))[0];
assert_register($t['establishment']===null,'source placeholder date is not a real founding date');
$orRecords=array_values(array_filter($p['groups']['people']['records'],fn($r)=>$r['source']==='ORSR'));
assert_register(str_contains($orRecords[1]['value']['text']??'','Erik Babčan'),'ORSR person text keeps spaces and accents');
assert_register($p['checks']['orsr']['source_updated_at']==='2026-10-29'&&$p['checks']['orsr']['extract_date']==='2026-10-30','source update is separate from extraction date');
$before=count($calls);$adapter->enrich($ico,Vorkei_Ico_Atlas_Profile::blank($ico));assert_register(count($calls)===$before,'hour cache reused');
$adapter->enrich($ico,Vorkei_Ico_Atlas_Profile::blank($ico),true);assert_register(count($calls)>$before,'explicit refresh bypasses transport cache');
// Two exact non-transferred files must not be resolved by numeric link ordering.
$cache=[];$original=$http[$transferred]['body'];$http[$transferred]['body']=str_replace('49430/S','49431/S',$http[$primary]['body']);
$q=$adapter->enrich($ico,Vorkei_Ico_Atlas_Profile::blank($ico),true);
assert_register($q['checks']['orsr']['availability_status']==='conflict'&&!isset($q['fields']['registration_status']),'multiple exact primary files are a conflict, never arbitrary legal proof');
assert_register($q['fields']['name']['source']==='RPO'&&count($q['checks']['orsr']['primary_files'])===2,'ambiguous ORSR keeps independently verified RPO identity');
$http[$transferred]['body']=$original;
// The three-file cap is enforced without declaring uninspected candidates safe.
$cache=[];$originalSearch=$http[$os]['body'];$before=count($calls);
foreach([700000,700001] as $candidateId) {
 $u=$or.'vypis.asp?ID='.$candidateId.'&SID=3&P=0';
 $http[$os]['body'].='<a href="vypis.asp?ID='.$candidateId.'&SID=3&P=0">Aktualny</a>';
 $http[$u]=['code'=>200,'body'=>$original];
}
$q=$adapter->enrich($ico,Vorkei_Ico_Atlas_Profile::blank($ico),true);
$detailCalls=array_filter(array_slice($calls,$before),fn($u)=>str_contains($u,'/vypis.asp?'));
assert_register(count($detailCalls)===3&&$q['checks']['orsr']['availability_status']==='partial'&&!isset($q['fields']['registration_status']),'three-file limit does not bypass unresolved primary ambiguity');
$http[$os]['body']=$originalSearch;
// A transferred file can never become the primary legal proof.
$cache=[];$http[$primary]['body']=$http[$transferred]['body'];$q=$adapter->enrich($ico,Vorkei_Ico_Atlas_Profile::blank($ico),true);
assert_register($q['checks']['orsr']['availability_status']==='source_unavailable'&&!isset($q['fields']['registration_status']),'transferred-only response does not confirm current file');
// Preserve old evidence on a failed refresh.
$http=[];$cache=[];$q=$adapter->enrich($ico,$p,true);
assert_register($q['fields']['name']['value']==='EB-EU s. r. o.'&&$q['checks']['rpo']['availability_status']==='source_unavailable','failed refresh preserves evidence');
echo "REGISTERS CHECKS PASSED\n";
