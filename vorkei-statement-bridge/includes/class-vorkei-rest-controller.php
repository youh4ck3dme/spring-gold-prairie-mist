<?php
/**
 * REST API Controller for Vorkei Statement Bridge.
 *
 * @package    Vorkei_Statement_Bridge
 * @subpackage Vorkei_Statement_Bridge/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Vorkei_REST_Controller extends WP_REST_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = 'vorkei/v1';
		$this->rest_base = 'statements';
	}

	/**
	 * Register REST routes for the plugin.
	 */
	public function register_routes(): void {
		// POST /wp-json/vorkei/v1/sync
		register_rest_route(
			$this->namespace,
			'/sync',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'handle_sync' ],
					'permission_callback' => [ $this, 'check_permissions' ],
					'args'                => $this->get_sync_schema_args(),
				],
			]
		);

		// GET /wp-json/vorkei/v1/statements
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_statements' ],
					'permission_callback' => [ $this, 'check_permissions' ],
					'args'                => [
						'page'     => [
							'default'           => 1,
							'sanitize_callback' => 'absint',
						],
						'per_page' => [
							'default'           => 20,
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);

		// GET /wp-json/vorkei/v1/statements/{id}
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[a-zA-Z0-9_\-]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_statement' ],
					'permission_callback' => [ $this, 'check_permissions' ],
				],
			]
		);

		// GET /wp-json/vorkei/v1/movements
		register_rest_route(
			$this->namespace,
			'/movements',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_movements' ],
					'permission_callback' => [ $this, 'check_permissions' ],
					'args'                => [
						'page'            => [
							'default'           => 1,
							'sanitize_callback' => 'absint',
						],
						'per_page'        => [
							'default'           => 50,
							'sanitize_callback' => 'absint',
						],
						'variable_symbol' => [
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						],
						'statement_id'    => [
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				],
			]
		);
	}

	/**
	 * Verify permissions for accessing endpoints.
	 *
	 * Supports WordPress Application Passwords, Bearer API Token (if configured),
	 * and standard logged-in user capabilities.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function check_permissions( $request ) {
		// 1. Check optional Secret API Token header.
		$custom_token = get_option( 'vorkei_bridge_api_token', '' );
		if ( ! empty( $custom_token ) ) {
			$auth_header = $request->get_header( 'authorization' );
			if ( $auth_header && preg_match( '/Bearer\s+(.*)$/i', $auth_header, $matches ) ) {
				if ( hash_equals( trim( $custom_token ), trim( $matches[1] ) ) ) {
					return true;
				}
			}
		}

		// 2. Check WordPress User Capability (compatible with Application Passwords).
		$allowed = current_user_can( 'manage_woocommerce' ) ||
		           current_user_can( 'edit_shop_orders' ) ||
		           current_user_can( 'edit_posts' );

		/**
		 * Filter permission result.
		 *
		 * @param bool            $allowed
		 * @param WP_REST_Request $request
		 */
		$allowed = apply_filters( 'vorkei_bridge_rest_permission', $allowed, $request );

		if ( ! $allowed ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Nemáte oprávnenie na prístup k Vorkei Statement Bridge API.', 'vorkei-bridge' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * Handle statements & movements synchronization payload.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_sync( WP_REST_Request $request ) {
		global $wpdb;

		$params     = $request->get_json_params();
		$statements = $params['statements'] ?? [];

		if ( empty( $statements ) || ! is_array( $statements ) ) {
			return new WP_Error(
				'vorkei_invalid_data',
				__( 'Chýbajúce alebo neplatné dáta výpisov.', 'vorkei-bridge' ),
				[ 'status' => 400 ]
			);
		}

		$account_iban = sanitize_text_field( $params['account']['iban'] ?? '' );
		$stats = [
			'statements_saved' => 0,
			'movements_saved'  => 0,
			'orders_matched'   => 0,
			'duplicates_skipped' => 0,
		];

		$statements_table = $wpdb->prefix . 'vorkei_statements';
		$movements_table  = $wpdb->prefix . 'vorkei_movements';

		// Check for JetEngine CCT tables (ID 13: statements, ID 14: transfers).
		$jet_cct_statements = $wpdb->prefix . 'jet_cct_statements';
		$jet_cct_transfers  = $wpdb->prefix . 'jet_cct_transfers';
		$has_cct_statements = ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $jet_cct_statements ) ) === $jet_cct_statements );
		$has_cct_transfers  = ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $jet_cct_transfers ) ) === $jet_cct_transfers );

		foreach ( $statements as $st ) {
			if ( empty( $st['id'] ) ) {
				continue;
			}

			$statement_id = sanitize_text_field( $st['id'] );
			$statement_data = [
				'id'               => $statement_id,
				'account_iban'     => $account_iban,
				'statement_number' => sanitize_text_field( $st['number'] ?? '' ),
				'opening_balance'  => (int) ( $st['openingCents'] ?? 0 ),
				'closing_balance'  => (int) ( $st['closingCents'] ?? 0 ),
				'currency'         => sanitize_text_field( $st['currency'] ?? 'EUR' ),
				'issued_on'        => sanitize_text_field( $st['issuedOn'] ?? '' ),
			];

			$wpdb->replace( $statements_table, $statement_data );
			$stats['statements_saved']++;

			// Sync with JetEngine CCT 13: wp_jet_cct_statements if available.
			if ( $has_cct_statements ) {
				$cct_st_data = [
					'cct_status'       => 'publish',
					'cct_created'      => current_time( 'mysql' ),
					'cct_modified'     => current_time( 'mysql' ),
					'title'            => sanitize_text_field( $st['title'] ?? ( 'Výpis číslo ' . ( $st['number'] ?? '' ) ) ),
					'slug'             => sanitize_title( $st['slug'] ?? ( 'vypis-' . ( $st['number'] ?? '' ) ) ),
					'date'             => sanitize_text_field( $st['issuedOn'] ?? '' ),
					'bank_name'        => sanitize_text_field( $st['bank_name'] ?? 'Tatra banka, a.s.' ),
					'bank_data'        => wp_kses_post( $st['bank_data'] ?? '' ),
					'client_id'        => sanitize_text_field( $st['client_id'] ?? '' ),
					'client_name'      => sanitize_text_field( $st['client_name'] ?? '' ),
					'client_data'      => sanitize_textarea_field( $st['client_data'] ?? '' ),
					'client_email'     => sanitize_email( $st['client_email'] ?? '' ),
					'account_type'     => sanitize_text_field( $st['account_type'] ?? 'Podnikateľský účet' ),
					'account_number'   => sanitize_text_field( $st['account_number'] ?? '' ),
					'account_iban'     => sanitize_text_field( $st['account_iban'] ?? $account_iban ),
					'account_swift'    => sanitize_text_field( $st['account_swift'] ?? 'TATRSKBXXXX' ),
					'account_currency' => sanitize_text_field( $st['currency'] ?? 'EUR' ),
				];

				$existing_cct = $wpdb->get_var( $wpdb->prepare( "SELECT _ID FROM $jet_cct_statements WHERE slug = %s LIMIT 1", $cct_st_data['slug'] ) );
				if ( $existing_cct ) {
					$wpdb->update( $jet_cct_statements, $cct_st_data, [ '_ID' => $existing_cct ] );
				} else {
					$wpdb->insert( $jet_cct_statements, $cct_st_data );
				}
			}

			if ( ! empty( $st['movements'] ) && is_array( $st['movements'] ) ) {
				foreach ( $st['movements'] as $mv ) {
					if ( empty( $mv['id'] ) ) {
						continue;
					}

					$date            = sanitize_text_field( $mv['date'] ?? '' );
					$amount_cents    = (int) ( $mv['amountCents'] ?? 0 );
					$party_iban      = preg_replace( '/[^a-zA-Z0-9]/', '', sanitize_text_field( $mv['partyIban'] ?? '' ) );
					$variable_symbol = preg_replace( '/[^a-zA-Z0-9_\-]/', '', sanitize_text_field( $mv['variableSymbol'] ?? '' ) );

					// Calculate unique idempotent hash.
					$movement_hash = hash( 'sha256', $date . '|' . $amount_cents . '|' . $party_iban . '|' . $variable_symbol . '|' . $statement_id );

					$movement_row = [
						'id'              => sanitize_text_field( $mv['id'] ),
						'statement_id'    => $statement_id,
						'movement_hash'   => $movement_hash,
						'date'            => $date,
						'amount_cents'    => $amount_cents,
						'kind'            => sanitize_text_field( $mv['kind'] ?? 'in' ),
						'party_name'      => sanitize_text_field( $mv['partyName'] ?? '' ),
						'party_iban'      => $party_iban,
						'variable_symbol' => $variable_symbol,
						'specific_symbol' => preg_replace( '/[^a-zA-Z0-9_\-]/', '', sanitize_text_field( $mv['specificSymbol'] ?? '' ) ),
						'constant_symbol' => preg_replace( '/[^a-zA-Z0-9_\-]/', '', sanitize_text_field( $mv['constantSymbol'] ?? '' ) ),
						'note'            => sanitize_textarea_field( $mv['note'] ?? '' ),
					];

					// Check if movement was already matched previously (e.g. manually in admin).
					$existing = $wpdb->get_row(
						$wpdb->prepare( "SELECT matched_order_id, match_status FROM $movements_table WHERE id = %s LIMIT 1", $movement_row['id'] ),
						ARRAY_A
					);

					$matched_order_id = null;
					$match_status     = 'unmatched';

					if ( ! empty( $existing['matched_order_id'] ) ) {
						$matched_order_id = (int) $existing['matched_order_id'];
						$match_status     = $existing['match_status'] ?: 'matched';
					} elseif ( class_exists( 'Vorkei_Matcher' ) ) {
						$matched_order_id = Vorkei_Matcher::match_order( $movement_row, $statement_data );
						if ( $matched_order_id ) {
							$match_status = 'matched';
							$stats['orders_matched']++;
						}
					}

					$movement_row['matched_order_id'] = $matched_order_id;
					$movement_row['match_status']     = $match_status;

					$wpdb->replace( $movements_table, $movement_row );
					$stats['movements_saved']++;

					// Sync with JetEngine CCT 14: wp_jet_cct_transfers if available.
					if ( $has_cct_transfers ) {
						$payment_type = sanitize_text_field( $mv['payment_type'] ?? ( $mv['kind'] === 'in' ? 'Prijatá platba' : ( $mv['kind'] === 'out' ? 'Odoslaná platba' : 'Transakčná daň' ) ) );
						$cct_tr_data = [
							'cct_status'    => 'publish',
							'cct_created'   => current_time( 'mysql' ),
							'cct_modified'  => current_time( 'mysql' ),
							'statement_id'  => (int) ( $st['number'] ?? 0 ),
							'payment_type'  => $payment_type,
							'customer_id'   => ! empty( $mv['customer_id'] ) ? (int) $mv['customer_id'] : 0,
							'contractor_id' => ! empty( $mv['contractor_id'] ) ? (int) $mv['contractor_id'] : 0,
							'date'          => $date,
							'amount'        => sanitize_text_field( $mv['amountFormatted'] ?? (string) ( $amount_cents / 100 ) ),
							'detail'        => sanitize_text_field( $mv['detail'] ?? ( $mv['note'] ?: ( $mv['variableSymbol'] ? 'VS: ' . $mv['variableSymbol'] : '' ) ) ),
						];

						$existing_tr = $wpdb->get_var( $wpdb->prepare(
							"SELECT _ID FROM $jet_cct_transfers WHERE statement_id = %d AND date = %s AND amount = %s AND detail = %s LIMIT 1",
							$cct_tr_data['statement_id'],
							$cct_tr_data['date'],
							$cct_tr_data['amount'],
							$cct_tr_data['detail']
						) );

						if ( $existing_tr ) {
							$wpdb->update( $jet_cct_transfers, $cct_tr_data, [ '_ID' => $existing_tr ] );
						} else {
							$wpdb->insert( $jet_cct_transfers, $cct_tr_data );
						}
					}
				}
			}
		}

		return rest_ensure_response( [
			'success'   => true,
			'message'   => sprintf(
				/* translators: 1: Count of statements, 2: Count of movements, 3: Count of matched orders */
				__( 'Úspešne synchronizované: %1$d výpisov, %2$d pohybov a %3$d spárovaných objednávok.', 'vorkei-bridge' ),
				$stats['statements_saved'],
				$stats['movements_saved'],
				$stats['orders_matched']
			),
			'stats'     => $stats,
			'timestamp' => current_time( 'mysql' ),
		] );
	}

	/**
	 * Retrieve paginated list of statements.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_statements( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = min( 100, max( 1, (int) $request->get_param( 'per_page' ) ) );
		$offset   = ( $page - 1 ) * $per_page;

		$table = $wpdb->prefix . 'vorkei_statements';
		$total = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table" );
		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			),
			ARRAY_A
		);

		$response = rest_ensure_response( $items );
		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) ceil( $total / $per_page ) );

		return $response;
	}

	/**
	 * Retrieve single statement with its movements.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_statement( WP_REST_Request $request ) {
		global $wpdb;

		$id = sanitize_text_field( $request->get_param( 'id' ) );
		$statements_table = $wpdb->prefix . 'vorkei_statements';
		$movements_table  = $wpdb->prefix . 'vorkei_movements';

		$statement = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $statements_table WHERE id = %s LIMIT 1", $id ),
			ARRAY_A
		);

		if ( ! $statement ) {
			return new WP_Error( 'not_found', __( 'Výpis nebol nájdený.', 'vorkei-bridge' ), [ 'status' => 404 ] );
		}

		$movements = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $movements_table WHERE statement_id = %s ORDER BY date ASC", $id ),
			ARRAY_A
		);

		$statement['movements'] = $movements;

		return rest_ensure_response( $statement );
	}

	/**
	 * Retrieve list of movements with filters.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_movements( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$page            = max( 1, (int) $request->get_param( 'page' ) );
		$per_page        = min( 100, max( 1, (int) $request->get_param( 'per_page' ) ) );
		$offset          = ( $page - 1 ) * $per_page;
		$variable_symbol = sanitize_text_field( $request->get_param( 'variable_symbol' ) ?? '' );
		$statement_id    = sanitize_text_field( $request->get_param( 'statement_id' ) ?? '' );

		$table  = $wpdb->prefix . 'vorkei_movements';
		$where  = [ '1=1' ];
		$values = [];

		if ( ! empty( $variable_symbol ) ) {
			$where[]  = 'variable_symbol = %s';
			$values[] = $variable_symbol;
		}

		if ( ! empty( $statement_id ) ) {
			$where[]  = 'statement_id = %s';
			$values[] = $statement_id;
		}

		$where_sql = implode( ' AND ', $where );

		// Count
		if ( ! empty( $values ) ) {
			$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $table WHERE $where_sql", ...$values ) );
		} else {
			$total = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table" );
		}

		// Items query
		$query_sql = "SELECT * FROM $table WHERE $where_sql ORDER BY date DESC, created_at DESC LIMIT %d OFFSET %d";
		$query_args = array_merge( $values, [ $per_page, $offset ] );
		$items = $wpdb->get_results( $wpdb->prepare( $query_sql, ...$query_args ), ARRAY_A );

		$response = rest_ensure_response( $items );
		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) ceil( $total / $per_page ) );

		return $response;
	}

	/**
	 * JSON Schema definitions for endpoint payload validation.
	 *
	 * @return array
	 */
	private function get_sync_schema_args(): array {
		return [
			'account' => [
				'type'        => 'object',
				'required'    => false,
				'properties'  => [
					'iban' => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			],
			'statements' => [
				'type'        => 'array',
				'required'    => true,
				'items'       => [
					'type'       => 'object',
					'required'   => [ 'id', 'number' ],
					'properties' => [
						'id'           => [ 'type' => 'string' ],
						'number'       => [ 'type' => 'string' ],
						'openingCents' => [ 'type' => 'integer' ],
						'closingCents' => [ 'type' => 'integer' ],
						'currency'     => [ 'type' => 'string' ],
						'issuedOn'     => [ 'type' => 'string' ],
						'movements'    => [ 'type' => 'array' ],
					],
				],
			],
		];
	}
}
