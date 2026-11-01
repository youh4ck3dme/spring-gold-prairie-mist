<?php
/**
 * REST API Controller for VORKEI IČO ATLAS.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Vorkei_Ico_Atlas_REST_Controller {

	private string $namespace = 'vorkei-ico/v1';

	public function register_routes(): void {
        register_rest_route($this->namespace,'/sources',['methods'=>WP_REST_Server::READABLE,'callback'=>static fn()=>new WP_REST_Response((new Vorkei_Ico_Atlas_Tax())->discover(),200),'permission_callback'=>[$this,'check_manage_permissions']]);
		// Full company evidence is restricted to administrators.
		register_rest_route(
			$this->namespace,
			'/lookup',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'handle_lookup' ],
				'permission_callback' => [ $this, 'check_manage_permissions' ],
				'args'                => [
					'ico' => [
						'required'          => true,
                        'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		// POST /wp-json/vorkei-ico/v1/batch-audit (protected admin endpoint)
		register_rest_route(
			$this->namespace,
			'/batch-audit',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_batch_audit' ],
				'permission_callback' => [ $this, 'check_manage_permissions' ],
				'args'                => [
					'limit' => [
						'default'           => 20,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		// GET /wp-json/vorkei-ico/v1/stats (protected admin endpoint)
		register_rest_route(
			$this->namespace,
			'/stats',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'handle_stats' ],
				'permission_callback' => [ $this, 'check_manage_permissions' ],
			]
		);

		// GET /wp-json/vorkei-ico/v1/export (protected admin endpoint, supports ?format=csv)
		register_rest_route(
			$this->namespace,
			'/export',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'handle_export' ],
				'permission_callback' => [ $this, 'check_manage_permissions' ],
				'args'                => [
					'format' => [
						'default'           => 'json',
						'sanitize_callback' => 'sanitize_key',
					],
				],
			]
		);
	}

	/**
	 * Capability check requiring manage_options (Administrator).
	 * Returns 401 for unauthenticated visitors, 403 for insufficient capabilities.
	 */
	public function check_manage_permissions(): bool|WP_Error {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Na túto operáciu nemáte dostatočné oprávnenia (vyžaduje sa manage_options).', 'vorkei-ico-atlas' ),
				[ 'status' => is_user_logged_in() ? 403 : 401 ]
			);
		}
		return true;
	}

	public function handle_lookup( WP_REST_Request $request ): WP_REST_Response {
        $ico=(string)$request->get_param('ico');
        $e=new Vorkei_Ico_Atlas_Enricher();
        $result=$e->lookup_and_save($ico, rest_sanitize_boolean($request->get_param('refresh')), rest_sanitize_boolean($request->get_param('continue')));
        $e->sync_cct($result);
        return new WP_REST_Response($result, ($result['verification_status']??'')==='invalid_input'?400:200);
    }

	public function handle_batch_audit( WP_REST_Request $request ): WP_REST_Response {
		$limit    = (int) $request->get_param( 'limit' ) ?: 20;
		$enricher = new Vorkei_Ico_Atlas_Enricher();
		$result   = $enricher->enrich_unverified_entities( $limit, sanitize_key((string)$request->get_param('job_id')) );

		return new WP_REST_Response( $result, 200 );
	}

	public function handle_stats(): WP_REST_Response {
		$enricher = new Vorkei_Ico_Atlas_Enricher();
		$stats    = $enricher->get_audit_stats();

		return new WP_REST_Response( $stats, 200 );
	}

	/**
	 * Sanitizes a CSV field to prevent CSV Formula Injection (CWE-1236).
	 * Prepend single apostrophe if field starts with =, +, -, @, tab, or carriage return.
	 */
	public function sanitize_csv_cell( mixed $value ): string {
		$str = (string) ( $value ?? '' );
        if(preg_match('/^-?[0-9]+(?:\.[0-9]+)?$/D',$str)) {return $str;}
		if ( '' !== $str && (preg_match('/^[\x00-\x20]*[=+@-]/', $str) || in_array($str[0],["\t","\r","\n"],true)) ) {
			return "'" . $str;
		}
		return $str;
	}

 public function handle_export(WP_REST_Request $request): WP_REST_Response {
  $e=new Vorkei_Ico_Atlas_Enricher();$profiles=[];
  $requested=(string)$request->get_param('ico');
  $icos=preg_match('/^[0-9]{8}$/D',$requested)?[$requested]:$e->get_icos();
  foreach($icos as $ico) {$profiles[]=$e->load((string)$ico)??['ico'=>$ico,'availability_status'=>'not_checked'];}
  $format=$request->get_param('format');
  if(!in_array($format,['csv','financial_csv'],true)) {return new WP_REST_Response(['schema_version'=>'2.1','count'=>count($profiles),'items'=>$profiles],200);}
  $csv=$this->build_csv($profiles,$format==='financial_csv');
  header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="vorkei-ico-'.($format==='financial_csv'?'financie':'evidence').'.csv"');echo $csv;exit;
 }
 /** Export helpers are independently testable and never invoke exit or headers. */
 public function build_csv(array $profiles,bool $financial=false): string {
  $out=fopen('php://temp','r+');fwrite($out,"\xEF\xBB\xBF");
  if($financial) {
   $keys=['operating_revenue','service_sales','goods_sales','profit_before_tax','profit_after_tax','assets','equity','liabilities'];
   $titles=array_combine($keys,['výnosy z hospodárskej činnosti','tržby výrobkov a služieb','tržby tovaru','zisk pred zdanením','zisk po zdanení','aktíva','vlastné imanie','záväzky']);
   fputcsv($out,['IČO','Názov','Obdobie od','Obdobie do','Výnosy z hospodárskej činnosti','Tržby výrobky a služby','Tržby tovar','Zisk pred zdanením','Zisk po zdanení','Aktíva','Vlastné imanie','Záväzky','Mena','Jednotka','ID výkazu','Zdroj','Poznámka'],';','"','');
   foreach($profiles as $top) {
    $selected=[];
    foreach($top['profile']['groups']['financials']['records']??[] as $record) {
     $facts=$record['normalized_facts']??[];if(!empty($record['superseded'])||!$facts) {continue;}
     $first=reset($facts);$period=$first['period']??[];
     $key=wp_json_encode($period);
     $sort=($record['source_updated_at']??'').($record['retrieved_at']??'');
     if(!isset($selected[$key])||$sort>=$selected[$key]['sort']) {$selected[$key]=['record'=>$record,'facts'=>$facts,'period'=>$period,'sort'=>$sort];}
    }
    ksort($selected);
    foreach($selected as $item) {
     $facts=$item['facts'];$r=$item['record'];$period=$item['period'];$first=reset($facts);
     $row=[$top['ico'],$top['name']??'',$period['from']??'',$period['to']??''];$missing=[];
     foreach($keys as $k) {$v=$facts[$k]['value']??null;$row[]=$v===null?'':$v;if($v===null) {$missing[]=$titles[$k];}}
     $row=array_merge($row,[$first['currency']??'',$first['unit']??'',$first['report_id']??($r['value']['id']??''),$r['source_url']??'',$missing?'Nevyplnené položky: '.implode(', ',$missing):'']);
     fputcsv($out,array_map([$this,'sanitize_csv_cell'],$row),';','"','');
    }
   }
  } else {
   fputcsv($out,['ico','path','value_json','source','source_url','source_record_id','retrieved_at','valid_as_of','availability_status','kind','match_method','source_updated_at','valid_from','valid_to','record_json'],';','"','');
   foreach($profiles as $top) {
    $ico=$top['ico'];$p=$top['profile']??[];
    foreach($p['fields']??[] as $field=>$e) {$this->csv_evidence($out,$ico,'fields.'.$field,$e);}
    foreach($p['observations']??[] as $field=>$values) {foreach($values as $i=>$e) {$this->csv_evidence($out,$ico,"observations.$field.$i",$e);}}
    foreach($p['groups']??[] as $group=>$g) {
     $meta=$g;unset($meta['records']);$this->csv_evidence($out,$ico,'groups.'.$group,['value'=>$meta,'availability_status'=>$g['availability_status']??'not_checked']);
     foreach($g['records']??[] as $i=>$e) {$this->csv_evidence($out,$ico,"groups.$group.records.$i",$e);}
    }
    foreach(['checks','conflicts'] as $key) {$this->csv_evidence($out,$ico,$key,['value'=>$p[$key]??[]]);}
    $summary=$top;unset($summary['profile']);$this->csv_evidence($out,$ico,'summary',['value'=>$summary]);
   }
  }
  rewind($out);$csv=stream_get_contents($out);fclose($out);return $csv;
 }
 private function csv_evidence($out,string $ico,string $path,array $e): void {
  $row=[$ico,$path,wp_json_encode($e['value']??null,JSON_UNESCAPED_UNICODE),$e['source']??'',$e['source_url']??'',$e['source_record_id']??'',$e['retrieved_at']??'',$e['valid_as_of']??'',$e['availability_status']??'',$e['kind']??'',$e['match_method']??'',$e['source_updated_at']??'',$e['valid_from']??'',$e['valid_to']??'',wp_json_encode($e,JSON_UNESCAPED_UNICODE)];
  fputcsv($out,array_map([$this,'sanitize_csv_cell'],$row),';','"','');
 }
}
