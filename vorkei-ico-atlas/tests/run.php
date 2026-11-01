<?php
if(PHP_SAPI!=='cli') {exit;}
define('ABSPATH',__DIR__.'/');define('ARRAY_A','ARRAY_A');
$cache=[];$options=['vorkei_ico_ruz_enabled'=>'yes'];$http=[];$calls=[];
function esc_url_raw($s,$p=[]){return $s;}
function get_option($k,$d=false){global $options;return $options[$k]??$d;}
function update_option($k,$v){global $options;$options[$k]=$v;}
function delete_option($k){global $options;unset($options[$k]);}
function add_option($k,$v,...$args){global $options;if(isset($options[$k]))return false;$options[$k]=$v;return true;}
function get_transient($k){global $cache;return $cache[$k]??false;}
function set_transient($k,$v,$ttl){global $cache;$cache[$k]=$v;}
function delete_transient($k){global $cache;unset($cache[$k]);}
function wp_json_encode($v,$f=0){return json_encode($v,$f);}
function wp_parse_url($u,$c){return parse_url($u,$c);}
class WP_Error extends Exception {public function __construct($code='',$message='',...$args){parent::__construct($message?:$code);}function get_error_message(){return $this->getMessage();}}
function is_wp_error($v){return $v instanceof Exception;}
function wp_safe_remote_get($u,$o){global $http,$calls;$calls[]=$u;return $http[$u]??new WP_Error('offline');}
function wp_remote_retrieve_response_code($r){return $r['code'];}
function wp_remote_retrieve_body($r){return $r['body'];}
function apply_filters($n,$v,...$a){return $v;}
function get_current_user_id(){return 1;}
function wp_generate_uuid4(){return 'test-job';}
function current_user_can($p){return false;}
function is_user_logged_in(){return false;}
function __($s,$d=''){return $s;}
function rest_sanitize_boolean($v){return $v===true||$v==='true'||$v===1;}
class WP_REST_Request {function __construct(private $a){}function get_param($k){return $this->a[$k]??null;}}
class WP_REST_Response {function __construct(public $data,public $status=200){}}
foreach(['profile','financials','registers','tax','ruz','client','enricher','rest-controller'] as $f)require __DIR__.'/../includes/class-ico-atlas-'.$f.'.php';
function ok($yes,$why){static $n=0;if(!$yes)throw new RuntimeException('FAIL '.$why);echo 'PASS '.(++$n).' '.$why."\n";}
$ico='51226511';$root='https://www.registeruz.sk/cruz-public/api/';
$raw=json_decode(file_get_contents(__DIR__.'/fixtures/legacy-profile-eb-eu.json'),true);
$p=Vorkei_Ico_Atlas_Profile::merge(Vorkei_Ico_Atlas_Profile::blank($ico),$raw);
ok($p['schema_version']==='2.1'&&isset($p['observations']['dic']),'2.0 profiles migrate additively');
ok($p['fields']['name']['valid_as_of']===null&&$p['fields']['name']['source_updated_at']==='2026-07-03','RUZ update date is not legal validity');
ok(!Vorkei_Ico_Atlas_Profile::summarize($p)['verified'],'legacy proxy true does not verify legal activity');
ok($p['fields']['active']['availability_status']==='unconfirmed'&&$p['fields']['active']['kind']==='candidate','legacy activity is explicitly an unconfirmed upstream claim');
$a=Vorkei_Ico_Atlas_Profile::blank($ico);Vorkei_Ico_Atlas_Profile::field($a,'name','Official','ORSR','https://www.orsr.sk/vypis.asp?ID=1','1');
$b=Vorkei_Ico_Atlas_Profile::blank($ico);Vorkei_Ico_Atlas_Profile::field($b,'name','Old','RÚZ',$root.'uctovna-jednotka?id=1','1');
$c=Vorkei_Ico_Atlas_Profile::merge($a,$b);ok($c['fields']['name']['value']==='Official'&&count($c['observations']['name'])===2,'source precedence retains conflicting observations');
Vorkei_Ico_Atlas_Profile::field($c,'name','Official','RPO','https://api.statistics.sk/rpo/v1/entity/1','1');ok(count($c['observations']['name'])===3,'corroborating sources are retained');
$claimed=Vorkei_Ico_Atlas_Profile::blank('51226511');Vorkei_Ico_Atlas_Profile::field($claimed,'name','Claim','ORSR','https://www.orsr.sk/vypis.asp?ID=9','9');Vorkei_Ico_Atlas_Profile::field($claimed,'registration_status','registered','ORSR','https://www.orsr.sk/vypis.asp?ID=9','9');
$assertion=(new Vorkei_Ico_Atlas_Client())->normalize('51226511',['name'=>'Claim','schema_version'=>'2.1','profile'=>$claimed],'https://proxy.test/?ico=');ok(!$assertion['verified'],'upstream claimed provenance cannot impersonate a direct official check');
foreach(['https://api.statistics.sk/rpo/v1/search?identifier='.$ico=>'registers-rpo-search.json','https://api.statistics.sk/rpo/v1/entity/9377936?showHistoricalData=true'=>'registers-rpo-entity.json','https://www.orsr.sk/hladaj_ico.asp?ICO='.$ico.'&SID=0'=>'registers-orsr-search.html','https://www.orsr.sk/vypis.asp?ID=694228&SID=3&P=0'=>'registers-orsr-primary.html','https://www.orsr.sk/vypis.asp?ID=406191&SID=4&P=0'=>'registers-orsr-transferred.html'] as $url=>$file) {$http[$url]=['code'=>200,'body'=>file_get_contents(__DIR__.'/fixtures/'.$file)];}
$http[$root.'uctovne-jednotky?zmenene-od=2000-01-01&ico='.$ico]=['code'=>200,'body'=>json_encode(['id'=>[1735997],'existujeDalsieId'=>false])];
foreach(['registration','financials'] as $group)foreach($raw['groups'][$group]['records'] as $r) {$http[$r['source_url']]=['code'=>200,'body'=>json_encode($r['value'])];}
$client=new Vorkei_Ico_Atlas_Client();ok($client->lookup('123')['verification_status']==='invalid_input','invalid ICO not padded');
$r=$client->lookup($ico,true);ok($r['pending']&&$r['acquisition_phase']===1,'first phase yields bounded continuation');
ok($r['name']==='EB-EU s. r. o.'&&$r['verification_status']==='verified_registered','actual official identity and legal registration projected');
ok(count($r['profile']['fields']['statutaries']['value'])===1&&count($r['profile']['fields']['shareholders']['value'])===2,'current officers and shareholders replace empty proxy');
for($i=0;$i<20&&$r['pending'];$i++) {$r=$client->lookup($ico,false,true);}
ok(!$r['pending'],'all acquisition phases finish');
ok($r['profile']['checks']['fs']['availability_status']==='requires_access','missing FS key is explicit, never a negative tax result');
$reports=array_values(array_filter($r['profile']['groups']['financials']['records'],fn($e)=>!empty($e['normalized_facts'])));ok(count($reports)===7,'all seven annual financial reports normalized');
$report2023=array_values(array_filter($reports,fn($e)=>$e['value']['id']===9208437))[0];
ok($report2023['normalized_facts']['assets']['value']===0&&$report2023['normalized_facts']['equity']['value']===null,'explicit zero differs from blank');
$count=count($calls);$cached=$client->lookup($ico);ok(count($calls)===$count&&$cached['profile']===$r['profile'],'cache read preserves retrieval evidence and makes no HTTP requests');
$failed=Vorkei_Ico_Atlas_Profile::blank($ico);$failed['checks']['rpo']=['availability_status'=>'source_unavailable'];$failed['checks']['orsr']=['availability_status'=>'source_unavailable'];
$stale=Vorkei_Ico_Atlas_Profile::summarize(Vorkei_Ico_Atlas_Profile::merge($r['profile'],$failed));ok(!$stale['verified']&&$stale['active']===null&&$stale['name']===$r['name'],'failed official refresh preserves identity without current legal verification');
$rest=new Vorkei_Ico_Atlas_REST_Controller();$csv=$rest->build_csv([$r]);$stream=fopen('php://memory','r+');fwrite($stream,substr($csv,3));rewind($stream);$headers=fgetcsv($stream,0,';','"','');$records=[];while(($row=fgetcsv($stream,0,';','"',''))!==false)$records[]=array_combine($headers,$row);
ok(in_array('record_json',$headers,true)&&in_array('source_updated_at',$headers,true),'evidence CSV preserves metadata columns');
$exportReport=array_values(array_filter($records,fn($x)=>str_contains($x['path'],'groups.financials.records.')&&str_contains($x['source_record_id'],'9208437')))[0];$envelope=json_decode($exportReport['record_json'],true);
ok(isset($envelope['normalized_facts'],$envelope['decoded_tables'])&&$envelope['normalized_facts']['equity']['value']===null,'CSV carries full decoded envelope without null loss');
ok(count(array_filter($records,fn($x)=>$x['path']==='summary'))===1&&count(array_filter($records,fn($x)=>$x['path']==='conflicts'))===1,'CSV includes root legal status and conflicts');
$fin=$rest->build_csv([$r],true);$stream=fopen('php://memory','r+');fwrite($stream,substr($fin,3));rewind($stream);fgetcsv($stream,0,';','"','');$years=[];while(($row=fgetcsv($stream,0,';','"',''))!==false)$years[substr($row[2],0,4)]=$row;
ok(count($years)===7&&$years['2022'][4]==='46800'&&$years['2022'][8]==='1771','readable financial CSV has seven periods and verified 2022 figures');
ok($years['2023'][4]===''&&$years['2023'][8]==='0'&&$years['2023'][10]==='','financial CSV distinguishes zero from undisclosed');
ok($rest->sanitize_csv_cell('=HYPERLINK("bad")')[0]==="'"&&$rest->sanitize_csv_cell('-972')==='-972','CSV blocks formulas and retains numeric negative amounts');
ok($rest->check_manage_permissions() instanceof WP_Error,'full profiles remain restricted to administrators');
class FakeDB {
 public $prefix='wp_';public $rows=[];
 function prepare($s,...$args){foreach($args as $a)$s=preg_replace('/%[sd]/',is_int($a)?$a:"'".str_replace("'","''",$a)."'",$s,1);return $s;}
 function esc_like($s){return $s;}function get_charset_collate(){return '';}
 function get_col($s){return str_contains($s,'DESCRIBE')?['id','ico','name','profile_json']:array_keys($this->rows);}
 function get_var($s){if(str_contains($s,'SHOW TABLES'))return 'wp_vorkei_ico_entities';if(str_contains($s,'profile_json'))return $this->rows['51226511']['profile_json']??null;if(str_contains($s,'SELECT id'))return isset($this->rows['51226511'])?1:null;return null;}
 function get_row($s,$mode){return $this->rows['51226511']??null;}
 function update($t,$d,$w){$this->rows[$w['ico']??'51226511']=array_merge($this->rows[$w['ico']??'51226511']??[],$d);return 1;}
 function insert($t,$d){$this->rows[$d['ico']]=$d;return 1;}
}
$wpdb=new FakeDB();$enricher=new Vorkei_Ico_Atlas_Enricher();$enricher->save_lookup_entity($r);$saved=$enricher->load($ico);
ok($saved['name']===$saved['profile']['fields']['name']['value']&&count($saved['profile']['groups']['financials']['records'])===count($r['profile']['groups']['financials']['records']),'storage projection matches selected evidence without duplicate reports');
$round=$enricher->lookup_and_save($ico,true);ok($round['pending']&&$round['acquisition_phase']===1,'stored REST projection preserves non-RUZ phase continuation');
for($i=0;$i<20&&$round['pending'];$i++)$round=$enricher->lookup_and_save($ico,false,true);
ok(!$round['pending']&&count(array_filter($round['profile']['groups']['financials']['records'],fn($e)=>!empty($e['normalized_facts'])))===7,'stored continuation completes all acquisition phases');
$legacy=['ico'=>$ico,'found'=>true,'name'=>'Old','active'=>true,'verified'=>true,'profile'=>$raw];$wpdb->rows[$ico]['profile_json']=json_encode($legacy);$migrated=$enricher->load($ico);
ok($migrated['schema_version']==='2.1'&&!$migrated['verified']&&$migrated['active']===null,'reading legacy storage reprojects stale proxy activity safely');
$captured='';function dbDelta($sql){global $captured;$captured=$sql;}
require __DIR__.'/../includes/class-ico-atlas-activator.php';Vorkei_Ico_Atlas_Activator::maybe_upgrade();
ok(str_contains($captured,'profile_json LONGTEXT')&&get_option('vorkei_ico_schema_version')==='2.1','versioned schema upgrade remains additive');
file_put_contents(sys_get_temp_dir().'/vorkei-ico21-profile.json',wp_json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo "CORE 2.1 REGRESSIONS PASSED\n";
