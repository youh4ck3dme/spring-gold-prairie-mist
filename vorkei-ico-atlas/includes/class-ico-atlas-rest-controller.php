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
		// GET /wp-json/vorkei-ico/v1/lookup?ico=...
		register_rest_route(
			$this->namespace,
			'/lookup',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'handle_lookup' ],
				'permission_callback' => [ $this, 'check_read_permissions' ],
				'args'                => [
					'ico' => [
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		// POST /wp-json/vorkei-ico/v1/batch-audit
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

		// GET /wp-json/vorkei-ico/v1/stats
		register_rest_route(
			$this->namespace,
			'/stats',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'handle_stats' ],
				'permission_callback' => [ $this, 'check_read_permissions' ],
			]
		);

		// GET /wp-json/vorkei-ico/v1/export
		register_rest_route(
			$this->namespace,
			'/export',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'handle_export' ],
				'permission_callback' => [ $this, 'check_manage_permissions' ],
			]
		);
	}

	public function check_read_permissions(): bool {
		return current_user_can( 'read' );
	}

	public function check_manage_permissions(): bool {
		return current_user_can( 'manage_options' );
	}

	public function handle_lookup( WP_REST_Request $request ): WP_REST_Response {
		$ico    = $request->get_param( 'ico' );
		$client = new Vorkei_Ico_Atlas_Client();
		$result = $client->lookup( $ico );

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

	public function handle_export(): WP_REST_Response {
		$enricher = new Vorkei_Ico_Atlas_Enricher();
		$entities = $enricher->get_entities( 'all', 1000 );

		return new WP_REST_Response(
			[
				'count' => count( $entities ),
				'items' => $entities,
			],
			200
		);
	}
}
