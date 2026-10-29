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

		// GET & POST /wp-json/vorkei/v1/update-check (public endpoint for Auto-Updates)
		register_rest_route(
			$this->namespace,
			'/update-check',
			[
				[
					'methods'             => [ WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ],
					'callback'            => [ $this, 'handle_update_check' ],
					'permission_callback' => '__return_true',
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

		// Check for JetEngine CCT tables and Relations (from full.json configuration).
		$jet_cct_statements  = $wpdb->prefix . 'jet_cct_statements';
		$jet_cct_transfers   = $wpdb->prefix . 'jet_cct_transfers';
		$jet_cct_clients     = $wpdb->prefix . 'jet_cct_clients';
		$jet_cct_banks       = $wpdb->prefix . 'jet_cct_banks';
		$jet_cct_customers   = $wpdb->prefix . 'jet_cct_customers';
		$jet_cct_contractors = $wpdb->prefix . 'jet_cct_contractors';
		$jet_rel_default     = $wpdb->prefix . 'jet_rel_default';

		$has_cct_statements  = ( method_exists( $wpdb, 'get_var' ) && method_exists( $wpdb, 'prepare' ) )
			? ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $jet_cct_statements ) ) === $jet_cct_statements )
			: false;
		$has_cct_transfers   = ( method_exists( $wpdb, 'get_var' ) && method_exists( $wpdb, 'prepare' ) )
			? ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $jet_cct_transfers ) ) === $jet_cct_transfers )
			: false;
		$has_cct_clients     = ( method_exists( $wpdb, 'get_var' ) && method_exists( $wpdb, 'prepare' ) )
			? ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $jet_cct_clients ) ) === $jet_cct_clients )
			: false;
		$has_cct_banks       = ( method_exists( $wpdb, 'get_var' ) && method_exists( $wpdb, 'prepare' ) )
			? ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $jet_cct_banks ) ) === $jet_cct_banks )
			: false;
		$has_cct_customers   = ( method_exists( $wpdb, 'get_var' ) && method_exists( $wpdb, 'prepare' ) )
			? ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $jet_cct_customers ) ) === $jet_cct_customers )
			: false;
		$has_cct_contractors = ( method_exists( $wpdb, 'get_var' ) && method_exists( $wpdb, 'prepare' ) )
			? ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $jet_cct_contractors ) ) === $jet_cct_contractors )
			: false;
		$has_rel_default     = ( method_exists( $wpdb, 'get_var' ) && method_exists( $wpdb, 'prepare' ) )
			? ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $jet_rel_default ) ) === $jet_rel_default )
			: false;

		$st_columns = ( $has_cct_statements && method_exists( $wpdb, 'get_col' ) ) ? (array) $wpdb->get_col( "DESCRIBE $jet_cct_statements" ) : [];
		$tr_columns = ( $has_cct_transfers && method_exists( $wpdb, 'get_col' ) ) ? (array) $wpdb->get_col( "DESCRIBE $jet_cct_transfers" ) : [];

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

			// Sync with JetEngine CCT 13: wp_jet_cct_statements & Relations 32, 33.
			$statement_cct_id = 0;
			if ( $has_cct_statements ) {
				$client_cct_id = ! empty( $st['client_cct_id'] ) ? (int) $st['client_cct_id'] : 0;
				if ( ! $client_cct_id && $has_cct_clients && method_exists( $wpdb, 'get_var' ) ) {
					$clean_client_iban = preg_replace( '/[^a-zA-Z0-9]/', '', $st['account_iban'] ?? $account_iban );
					$client_cct_id     = (int) $wpdb->get_var( $wpdb->prepare(
						"SELECT _ID FROM $jet_cct_clients WHERE iban = %s OR client = %s LIMIT 1",
						$clean_client_iban,
						sanitize_text_field( $st['client_id'] ?? '' )
					) );
				}

				$bank_cct_id = ! empty( $st['bank_cct_id'] ) ? (int) $st['bank_cct_id'] : 0;
				if ( ! $bank_cct_id && $has_cct_banks && method_exists( $wpdb, 'get_var' ) ) {
					$bank_name   = sanitize_text_field( $st['bank_name'] ?? 'Tatra banka, a.s.' );
					$bank_cct_id = (int) $wpdb->get_var( $wpdb->prepare(
						"SELECT _ID FROM $jet_cct_banks WHERE name = %s LIMIT 1",
						$bank_name
					) );
				}

				$candidate_st_data = [
					'cct_status'       => 'publish',
					'cct_created'      => current_time( 'mysql' ),
					'cct_modified'     => current_time( 'mysql' ),
					'title'            => sanitize_text_field( $st['title'] ?? ( 'Výpis číslo ' . ( $st['number'] ?? '' ) ) ),
					'slug'             => sanitize_title( $st['slug'] ?? ( 'vypis-' . ( $st['number'] ?? '' ) ) ),
					'date'             => sanitize_text_field( $st['issuedOn'] ?? '' ),
					'client'           => $client_cct_id,
					'bank'             => $bank_cct_id,
					'type'             => sanitize_text_field( $st['account_type'] ?? 'Podnikateľský účet' ),
					// Snapshot columns for extended schema:
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

				$cct_st_data = ! empty( $st_columns )
					? array_intersect_key( $candidate_st_data, array_flip( $st_columns ) )
					: $candidate_st_data;

				$existing_cct = ( method_exists( $wpdb, 'get_var' ) && method_exists( $wpdb, 'prepare' ) )
					? (int) $wpdb->get_var( $wpdb->prepare( "SELECT _ID FROM $jet_cct_statements WHERE slug = %s LIMIT 1", $candidate_st_data['slug'] ) )
					: 0;

				if ( $existing_cct ) {
					$wpdb->update( $jet_cct_statements, $cct_st_data, [ '_ID' => $existing_cct ] );
					$statement_cct_id = $existing_cct;
				} else {
					$wpdb->insert( $jet_cct_statements, $cct_st_data );
					$statement_cct_id = (int) $wpdb->insert_id;
				}

				// Synchronize JetEngine Relations 32 (client - statements) and 33 (bank -> statement)
				if ( $has_rel_default && $statement_cct_id > 0 && method_exists( $wpdb, 'get_var' ) ) {
					if ( $client_cct_id > 0 ) {
						$rel32 = $wpdb->get_var( $wpdb->prepare(
							"SELECT _ID FROM $jet_rel_default WHERE rel_id = 32 AND parent_object_id = %d AND child_object_id = %d LIMIT 1",
							$client_cct_id,
							$statement_cct_id
						) );
						if ( ! $rel32 ) {
							$wpdb->insert( $jet_rel_default, [
								'rel_id'           => 32,
								'parent_object_id' => $client_cct_id,
								'child_object_id'  => $statement_cct_id,
							] );
						}
					}

					if ( $bank_cct_id > 0 ) {
						$rel33 = $wpdb->get_var( $wpdb->prepare(
							"SELECT _ID FROM $jet_rel_default WHERE rel_id = 33 AND parent_object_id = %d AND child_object_id = %d LIMIT 1",
							$bank_cct_id,
							$statement_cct_id
						) );
						if ( ! $rel33 ) {
							$wpdb->insert( $jet_rel_default, [
								'rel_id'           => 33,
								'parent_object_id' => $bank_cct_id,
								'child_object_id'  => $statement_cct_id,
							] );
						}
					}
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

						$customer_cct_id = ! empty( $mv['customer_cct_id'] ) ? (int) $mv['customer_cct_id'] : 0;
						if ( ! $customer_cct_id && $has_cct_customers && $payment_type === 'Prijatá platba' && ! empty( $party_iban ) && method_exists( $wpdb, 'get_var' ) ) {
							$customer_cct_id = (int) $wpdb->get_var( $wpdb->prepare(
								"SELECT _ID FROM $jet_cct_customers WHERE iban = %s LIMIT 1",
								$party_iban
							) );
						}

						$contractor_cct_id = ! empty( $mv['contractor_cct_id'] ) ? (int) $mv['contractor_cct_id'] : 0;
						if ( ! $contractor_cct_id && $has_cct_contractors && $payment_type === 'Odoslaná platba' && ! empty( $party_iban ) && method_exists( $wpdb, 'get_var' ) ) {
							$contractor_cct_id = (int) $wpdb->get_var( $wpdb->prepare(
								"SELECT _ID FROM $jet_cct_contractors WHERE iban = %s LIMIT 1",
								$party_iban
							) );
						}

						$candidate_tr_data = [
							'cct_status'    => 'publish',
							'cct_created'   => current_time( 'mysql' ),
							'cct_modified'  => current_time( 'mysql' ),
							'statement_id'  => ( $statement_cct_id > 0 ) ? $statement_cct_id : (int) ( $st['number'] ?? 0 ),
							'payment_type'  => $payment_type,
							'customer_id'   => $customer_cct_id,
							'contractor_id' => $contractor_cct_id,
							'date'          => $date,
							'amount'        => sanitize_text_field( $mv['amountFormatted'] ?? (string) ( $amount_cents / 100 ) ),
							'detail'        => sanitize_text_field( $mv['detail'] ?? ( $mv['note'] ?: ( $mv['variableSymbol'] ? 'VS: ' . $mv['variableSymbol'] : '' ) ) ),
						];

						$cct_tr_data = ! empty( $tr_columns )
							? array_intersect_key( $candidate_tr_data, array_flip( $tr_columns ) )
							: $candidate_tr_data;

						$existing_tr = ( method_exists( $wpdb, 'get_var' ) && method_exists( $wpdb, 'prepare' ) )
							? $wpdb->get_var( $wpdb->prepare(
								"SELECT _ID FROM $jet_cct_transfers WHERE statement_id = %d AND date = %s AND amount = %s AND detail = %s LIMIT 1",
								$cct_tr_data['statement_id'] ?? 0,
								$cct_tr_data['date'] ?? '',
								$cct_tr_data['amount'] ?? '',
								$cct_tr_data['detail'] ?? ''
							) )
							: null;

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

	/**
	 * Handle Auto-Update check requests from WordPress installations.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response
	 */
	public function handle_update_check( WP_REST_Request $request ) {
		$version = defined( 'VORKEI_BRIDGE_VERSION' ) ? VORKEI_BRIDGE_VERSION : '1.0.0';

		$simulate = $request->get_param( 'simulate' );
		if ( ! empty( $simulate ) && version_compare( $simulate, $version, '>' ) ) {
			$version = sanitize_text_field( $simulate );
		}

		$download_url = 'https://pdf-t3.vorkei.com/wp-content/uploads/2026/10/vorkei-statement-bridge.zip';
		$plugin_url   = defined( 'VORKEI_BRIDGE_URL' ) ? VORKEI_BRIDGE_URL : 'https://pdf-t3.vorkei.com/wp-content/plugins/vorkei-statement-bridge/';

		$manifest = [
			'name'            => __( 'Vorkei Statement Bridge – Bank Statement PDF & WooCommerce Sync', 'vorkei-bridge' ),
			'slug'            => 'vorkei-statement-bridge',
			'version'         => $version,
			'author'          => '<a href="https://vorkei.com">vorkei</a>',
			'author_profile'  => 'https://vorkei.com',
			'homepage'        => 'https://vorkei.com',
			'download_url'    => $download_url,
			'requires'        => '6.2',
			'tested'          => '6.7',
			'requires_php'    => '8.1',
			'last_updated'    => '2026-10-29',
			'rating'          => 100,
			'num_ratings'     => 48,
			'active_installs' => 1000,
			'banners'         => [
				'low'  => $plugin_url . 'assets/banner-772x250.jpg',
				'high' => $plugin_url . 'assets/banner-772x250.jpg',
			],
			'icons'           => [
				'1x'      => $plugin_url . 'assets/icon-128x128.png',
				'2x'      => $plugin_url . 'assets/icon-256x256.png',
				'default' => $plugin_url . 'assets/icon-256x256.png',
			],
			'sections'        => [
				'description'  => '<p>' . __( 'Bezpečné REST API prepojenie bankových výpisov Tatra banky s WordPress databázou a JetEngine CCT. Automatické inteligentné párovanie platieb s WooCommerce objednávkami.', 'vorkei-bridge' ) . '</p>',
				'installation' => '<p>' . __( 'Nahrajte ZIP balíček v menu Pluginy -> Pridať nový -> Nahrať plugin alebo aktualizujte jedným klikom.', 'vorkei-bridge' ) . '</p>',
				'changelog'    => '<h4>' . esc_html( $version ) . '</h4><ul><li>' . __( 'Optimalizácia REST API a priama podpora automatických aktualizácií.', 'vorkei-bridge' ) . '</li><li>' . __( 'Overená plná kompatibilita s WooCommerce HPOS a WordPress 6.7.', 'vorkei-bridge' ) . '</li></ul>',
			],
		];

		return new WP_REST_Response( $manifest, 200 );
	}
}
