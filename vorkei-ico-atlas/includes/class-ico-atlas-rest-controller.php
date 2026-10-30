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
		// GET /wp-json/vorkei-ico/v1/lookup?ico=... (public or read access)
		register_rest_route(
			$this->namespace,
			'/lookup',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'handle_lookup' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'ico' => [
						'required'          => true,
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
		$ico    = $request->get_param( 'ico' );
		$client = new Vorkei_Ico_Atlas_Client();
		$result = $client->lookup( $ico );

		if ( ! empty( $result['found'] ) ) {
			$enricher = new Vorkei_Ico_Atlas_Enricher();
			$enricher->save_lookup_entity( $result );
		}

		return new WP_REST_Response( $result, 200 );
	}

	public function handle_batch_audit( WP_REST_Request $request ): WP_REST_Response {
		$limit    = (int) $request->get_param( 'limit' ) ?: 20;
		$enricher = new Vorkei_Ico_Atlas_Enricher();
		$result   = $enricher->enrich_unverified_entities( $limit );

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
		if ( '' !== $str && in_array( $str[0], [ '=', '+', '-', '@', "\t", "\r" ], true ) ) {
			return "'" . $str;
		}
		return $str;
	}

	public function handle_export( WP_REST_Request $request ): WP_REST_Response {
		$enricher = new Vorkei_Ico_Atlas_Enricher();
		$entities = $enricher->get_entities( 'all', 1000 );

		if ( 'csv' === $request->get_param( 'format' ) ) {
			$output = fopen( 'php://temp', 'r+' );
			// UTF-8 BOM for Microsoft Excel compatibility
			fputs( $output, "\xEF\xBB\xBF" );

			$headers = [ 'ID', 'Typ', 'Názov', 'IČO', 'Právna forma', 'Adresa', 'Status', 'Aktívny', 'Rizikový', 'Dôvod rizika', 'Overené dňa' ];
			fputcsv( $output, array_map( [ $this, 'sanitize_csv_cell' ], $headers ) );

			foreach ( $entities as $item ) {
				$row = [
					$item['_ID'] ?? '',
					$item['entity_type'] ?? '',
					$item['name'] ?? '',
					$item['ico'] ?? '',
					$item['legal_form'] ?? '',
					$item['address'] ?? '',
					$item['company_status'] ?? '',
					! empty( $item['is_active'] ) ? 'Áno' : 'Nie',
					! empty( $item['is_risky'] ) ? 'Áno' : 'Nie',
					$item['risk_reason'] ?? '',
					$item['verified_at'] ?? '',
				];
				fputcsv( $output, array_map( [ $this, 'sanitize_csv_cell' ], $row ) );
			}

			rewind( $output );
			$csv_content = stream_get_contents( $output );
			fclose( $output );

			header( 'Content-Type: text/csv; charset=UTF-8' );
			header( 'Content-Disposition: attachment; filename="vorkei-ico-audit-' . gmdate( 'Y-m-d' ) . '.csv"' );
			echo $csv_content;
			exit;
		}

		return new WP_REST_Response(
			[
				'count' => count( $entities ),
				'items' => $entities,
			],
			200
		);
	}
}
