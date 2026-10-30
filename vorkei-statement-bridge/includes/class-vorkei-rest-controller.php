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

		// GET /wp-json/vorkei/v1/export/{id}
		register_rest_route(
			$this->namespace,
			'/export/(?P<id>[a-zA-Z0-9_\-]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'export_statement' ],
					'permission_callback' => [ $this, 'check_permissions' ],
					'args'                => [
						'format' => [
							'default'           => 'csv',
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				],
			]
		);

		// GET /wp-json/vorkei/v1/audit-logs
		register_rest_route(
			$this->namespace,
			'/audit-logs',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_audit_logs' ],
					'permission_callback' => [ $this, 'check_permissions' ],
					'args'                => [
						'page'     => [
							'default'           => 1,
							'sanitize_callback' => 'absint',
						],
						'per_page' => [
							'default'           => 50,
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);

		// POST /wp-json/vorkei/v1/audit-logs/{id}/unlock
		register_rest_route(
			$this->namespace,
			'/audit-logs/(?P<id>\d+)/unlock',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'toggle_export_lock' ],
					'permission_callback' => [ $this, 'check_permissions' ],
				],
			]
		);

		// GET /wp-json/vorkei/v1/wallets
		register_rest_route(
			$this->namespace,
			'/wallets',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_wallets' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_wallet' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
				],
			]
		);

		// POST /wp-json/vorkei/v1/wallets/{id}/topup
		register_rest_route(
			$this->namespace,
			'/wallets/(?P<id>\d+)/topup',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'topup_wallet' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
				],
			]
		);

		// POST /wp-json/vorkei/v1/wallets/{id}/toggle-active
		register_rest_route(
			$this->namespace,
			'/wallets/(?P<id>\d+)/toggle-active',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'toggle_wallet_active' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
				],
			]
		);

		// DELETE /wp-json/vorkei/v1/wallets/{id}
		register_rest_route(
			$this->namespace,
			'/wallets/(?P<id>\d+)',
			[
				[
					'methods'             => defined( 'WP_REST_Server::DELETABLE' ) ? WP_REST_Server::DELETABLE : 'DELETE',
					'callback'            => [ $this, 'delete_wallet' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
				],
			]
		);

		// POST /wp-json/vorkei/v1/stripe-webhook
		register_rest_route(
			$this->namespace,
			'/stripe-webhook',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'handle_stripe_webhook' ],
					'permission_callback' => '__return_true',
				],
			]
		);

		// POST /wp-json/vorkei/v1/stripe-reconcile
		register_rest_route(
			$this->namespace,
			'/stripe-reconcile',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'handle_stripe_reconcile' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
				],
			]
		);

		// GET /wp-json/vorkei/v1/credit-transactions
		register_rest_route(
			$this->namespace,
			'/credit-transactions',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_credit_transactions' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
				],
			]
		);

		// POST /wp-json/vorkei/v1/checkout/create-session
		register_rest_route(
			$this->namespace,
			'/checkout/create-session',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'handle_create_checkout_session' ],
					'permission_callback' => '__return_true',
				],
			]
		);

		// GET /wp-json/vorkei/v1/order-status
		register_rest_route(
			$this->namespace,
			'/order-status',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'handle_order_status' ],
					'permission_callback' => '__return_true',
				],
			]
		);

		// POST /wp-json/vorkei/v1/tokens/revoke
		register_rest_route(
			$this->namespace,
			'/tokens/revoke',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'handle_revoke_token' ],
					'permission_callback' => [ $this, 'check_token_or_admin_permission' ],
				],
			]
		);
	}

	/**
	 * Verify permissions for accessing endpoints.
	 *
	 * Supports WordPress Application Passwords, Bearer API Token (if configured),
	 * client wallet tokens, and standard logged-in user capabilities.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function check_permissions( $request ) {
		global $wpdb;

		// 1. Check optional Master Secret API Token header.
		$custom_token = function_exists( 'get_option' ) ? get_option( 'vorkei_bridge_api_token', '' ) : '';
		$auth_header  = $request->get_header( 'authorization' );
		$bearer_token = '';
		if ( $auth_header && preg_match( '/Bearer\s+(.*)$/i', $auth_header, $matches ) ) {
			$bearer_token = trim( $matches[1] );
		}
		if ( empty( $bearer_token ) ) {
			$bearer_token = sanitize_text_field( $request->get_header( 'x-vorkei-token' ) ?: ( $request->get_param( 'api_token' ) ?: '' ) );
		}

		if ( ! empty( $custom_token ) && ! empty( $bearer_token ) ) {
			if ( hash_equals( trim( $custom_token ), $bearer_token ) ) {
				return true;
			}
		}

		// 2. Check Client Partner Wallet Tokens.
		if ( ! empty( $bearer_token ) && $wpdb && method_exists( $wpdb, 'get_row' ) ) {
			$tokens_table = $wpdb->prefix . 'vorkei_client_tokens';
			$wallet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tokens_table WHERE token_key = %s LIMIT 1", $bearer_token ), ARRAY_A );
			if ( $wallet ) {
				if ( (int) ( $wallet['is_active'] ?? 1 ) === 0 ) {
					return new WP_Error(
						'vorkei_token_suspended',
						__( 'Tento klientsky API Token je pozastavený. Kontaktujte administrátora.', 'vorkei-bridge' ),
						[ 'status' => 403 ]
					);
				}
				$request->set_param( '_vorkei_client_wallet', $wallet );
				return true;
			}
		}

		// 3. Check WordPress User Capability (compatible with Application Passwords).
		$allowed = current_user_can( 'manage_woocommerce' ) ||
		           current_user_can( 'edit_shop_orders' ) ||
		           current_user_can( 'manage_options' ) ||
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
	 * Verify admin permissions for managing wallets.
	 *
	 * @param WP_REST_Request $request
	 * @return bool|WP_Error
	 */
	public function check_admin_permissions( $request ) {
		$custom_token = function_exists( 'get_option' ) ? get_option( 'vorkei_bridge_api_token', '' ) : '';
		$auth_header  = $request->get_header( 'authorization' );
		$bearer_token = '';
		if ( $auth_header && preg_match( '/Bearer\s+(.*)$/i', $auth_header, $matches ) ) {
			$bearer_token = trim( $matches[1] );
		}
		if ( empty( $bearer_token ) ) {
			$bearer_token = sanitize_text_field( $request->get_header( 'x-vorkei-token' ) ?: ( $request->get_param( 'api_token' ) ?: '' ) );
		}

		if ( ! empty( $custom_token ) && ! empty( $bearer_token ) && hash_equals( trim( $custom_token ), $bearer_token ) ) {
			return true;
		}

		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'Na túto operáciu sú vyžadované administrátorské oprávnenia.', 'vorkei-bridge' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}

	/**
	 * Verify token owner or admin permissions.
	 *
	 * @param WP_REST_Request $request
	 * @return bool|WP_Error
	 */
	public function check_token_or_admin_permission( $request ) {
		global $wpdb;

		// 1. Admin permission check
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$custom_token = function_exists( 'get_option' ) ? get_option( 'vorkei_bridge_api_token', '' ) : '';
		$auth_header  = $request->get_header( 'authorization' );
		$bearer_token = '';
		if ( $auth_header && preg_match( '/Bearer\s+(.*)$/i', $auth_header, $matches ) ) {
			$bearer_token = trim( $matches[1] );
		}
		if ( empty( $bearer_token ) ) {
			$bearer_token = sanitize_text_field( $request->get_header( 'x-vorkei-token' ) ?: ( $request->get_param( 'api_token' ) ?: ( $request->get_param( 'token_key' ) ?: '' ) ) );
		}

		if ( ! empty( $custom_token ) && ! empty( $bearer_token ) && hash_equals( trim( $custom_token ), $bearer_token ) ) {
			return true;
		}

		// 2. Client token check
		if ( ! empty( $bearer_token ) && $wpdb && method_exists( $wpdb, 'get_row' ) ) {
			$tokens_table = $wpdb->prefix . 'vorkei_client_tokens';
			$wallet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tokens_table WHERE token_key = %s LIMIT 1", $bearer_token ), ARRAY_A );
			if ( $wallet ) {
				$request->set_param( '_vorkei_client_wallet', $wallet );
				return true;
			}
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'Na túto operáciu je vyžadovaný platný API Token alebo administrátorské oprávnenie.', 'vorkei-bridge' ),
			[ 'status' => rest_authorization_required_code() ]
		);
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

		if ( count( $statements ) > 100 ) {
			return new WP_Error(
				'vorkei_payload_too_large',
				__( 'Maximálny počet výpisov v jednej požiadavke je 100.', 'vorkei-bridge' ),
				[ 'status' => 413 ]
			);
		}

		$account_iban = sanitize_text_field( $params['account']['iban'] ?? '' );
		$stats = [
			'statements_saved' => 0,
			'movements_saved'  => 0,
			'orders_matched'   => 0,
			'duplicates_skipped' => 0,
		];

		// Hard Paywall: Check client credit balance if credit enforcement is active.
		$enforce_credits = function_exists( 'get_option' ) ? get_option( 'vorkei_bridge_enforce_credits', 'yes' ) : 'yes';
		$client_wallet   = ( method_exists( $request, 'get_param' ) ) ? $request->get_param( '_vorkei_client_wallet' ) : null;

		// If client wallet was not identified from Bearer token, check if logged in user is non-admin and has token assigned
		if ( ! $client_wallet && function_exists( 'current_user_can' ) && ! current_user_can( 'manage_options' ) && $wpdb && method_exists( $wpdb, 'get_row' ) ) {
			$current_user_id = function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0;
			if ( $current_user_id > 0 ) {
				$tokens_table  = $wpdb->prefix . 'vorkei_client_tokens';
				$client_wallet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tokens_table WHERE notes LIKE %s LIMIT 1", '%user_id:' . $current_user_id . '%' ), ARRAY_A );
			}
		}

		if ( 'yes' === $enforce_credits && ! empty( $client_wallet ) ) {
			if ( (int) ( $client_wallet['is_active'] ?? 1 ) === 0 ) {
				return new WP_Error(
					'vorkei_token_suspended',
					__( 'Tento klientsky API Token je pozastavený. Kontaktujte administrátora.', 'vorkei-bridge' ),
					[ 'status' => 403 ]
				);
			}

			$statement_count    = count( $statements );
			$cost_per_statement = max( 1, (int) ( $client_wallet['cost_per_statement'] ?? 1 ) );
			$required_credits   = $statement_count * $cost_per_statement;
			$current_balance    = (int) ( $client_wallet['credits_balance'] ?? 0 );

			if ( $current_balance < $required_credits ) {
				return new WP_Error(
					'vorkei_insufficient_credits',
					sprintf(
						/* translators: 1: Required credits, 2: Current balance */
						__( 'Nedostatočný počet kreditov pre spracovanie výpisov. Potrebné: %1$d kreditov, Aktuálny zostatok: %2$d kreditov. Dobite si peňaženku.', 'vorkei-bridge' ),
						$required_credits,
						$current_balance
					),
					[
						'status'           => 402, // HTTP 402 Payment Required
						'credits_balance'  => $current_balance,
						'credits_required' => $required_credits,
						'token_key'        => $client_wallet['token_key'] ?? '',
						'client_name'      => $client_wallet['client_name'] ?? '',
					]
				);
			}
		}

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

					// Calculate unique idempotent hash scoped by date, amount, party, symbol, statement, and account.
					$movement_hash = hash( 'sha256', $date . '|' . $amount_cents . '|' . $party_iban . '|' . $variable_symbol . '|' . $statement_id . '|' . $account_iban );

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

		// Forensic Blackbox: Record audit log for partner activity & billing
		$audit_log_table = $wpdb->prefix . 'vorkei_audit_log';
		if ( method_exists( $wpdb, 'insert' ) ) {
			$ip_address = '127.0.0.1';
			if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
				$ip_address = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
			} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				$parts      = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
				$ip_address = trim( $parts[0] );
			} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
				$ip_address = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
			}

			$user_agent = sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown' );
			$client_os  = 'Neznámy OS';
			if ( preg_match( '/macintosh|mac os x/i', $user_agent ) ) {
				$client_os = 'macOS';
			} elseif ( preg_match( '/windows/i', $user_agent ) ) {
				$client_os = 'Windows';
			} elseif ( preg_match( '/linux/i', $user_agent ) ) {
				$client_os = 'Linux';
			} elseif ( preg_match( '/iphone|ipad/i', $user_agent ) ) {
				$client_os = 'iOS';
			} elseif ( preg_match( '/android/i', $user_agent ) ) {
				$client_os = 'Android';
			}

			$client_browser = 'Neznámy prehliadač';
			if ( preg_match( '/edg/i', $user_agent ) ) {
				$client_browser = 'Edge';
			} elseif ( preg_match( '/chrome/i', $user_agent ) ) {
				$client_browser = 'Chrome';
			} elseif ( preg_match( '/safari/i', $user_agent ) ) {
				$client_browser = 'Safari';
			} elseif ( preg_match( '/firefox/i', $user_agent ) ) {
				$client_browser = 'Firefox';
			} elseif ( preg_match( '/curl/i', $user_agent ) ) {
				$client_browser = 'cURL / CLI';
			}

			$current_user_id = function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0;
			$user_obj        = function_exists( 'get_userdata' ) ? get_userdata( $current_user_id ) : null;
			$user_login      = $user_obj ? $user_obj->user_login : 'api_token';

			if ( ! empty( $client_wallet ) ) {
				$user_login = ( ! empty( $client_wallet['client_name'] ) ? $client_wallet['client_name'] : 'Partner' ) . ' (' . ( $client_wallet['token_key'] ?? '' ) . ')';
			}

			$auto_lock       = function_exists( 'get_option' ) ? get_option( 'vorkei_bridge_auto_lock_exports', 'yes' ) : 'yes';
			$export_unlocked = ( 'no' === $auto_lock ) ? 1 : 0;

			$fee_base_amount    = function_exists( 'get_option' ) ? (float) get_option( 'vorkei_bridge_fee_base_amount', 5.00 ) : 5.00;
			$fee_movement_rate  = function_exists( 'get_option' ) ? (float) get_option( 'vorkei_bridge_fee_movement_rate', 0.00 ) : 0.00;
			$fee_volume_percent = function_exists( 'get_option' ) ? (float) get_option( 'vorkei_bridge_fee_volume_percent', 0.00 ) : 0.00;

			foreach ( $statements as $st ) {
				$st_id = sanitize_text_field( $st['id'] ?? '' );
				if ( empty( $st_id ) ) {
					continue;
				}

				$st_movements = ( ! empty( $st['movements'] ) && is_array( $st['movements'] ) ) ? $st['movements'] : [];

				$total_volume_cents = 0;
				$primary_party      = '';
				foreach ( $st_movements as $mv ) {
					$mv_cents = abs( (int) ( $mv['amountCents'] ?? round( ( (float) ( $mv['amount'] ?? 0 ) ) * 100 ) ) );
					$total_volume_cents += $mv_cents;
					$pname = sanitize_text_field( $mv['partyName'] ?? ( $mv['party_name'] ?? '' ) );
					if ( empty( $primary_party ) && ! empty( $pname ) ) {
						$primary_party = $pname;
					}
				}

				$mv_count            = count( $st_movements );
				$estimated_minutes   = max( 15, $mv_count * 3 );

				$fee_base_cents      = (int) round( $fee_base_amount * 100 );
				$fee_mv_cents        = (int) round( $mv_count * $fee_movement_rate * 100 );
				$fee_vol_cents       = (int) round( ( $total_volume_cents * $fee_volume_percent ) / 100 );
				$suggested_fee_cents = $fee_base_cents + $fee_mv_cents + $fee_vol_cents;

				$client_name = ! empty( $st['client_name'] )
					? sanitize_text_field( $st['client_name'] )
					: ( $primary_party ?: 'Neznámy subjekt' );

				$wpdb->insert( $audit_log_table, [
					'statement_id'        => $st_id,
					'user_id'             => $current_user_id,
					'user_login'          => $user_login,
					'ip_address'          => $ip_address,
					'user_agent'          => substr( $user_agent, 0, 500 ),
					'client_os'           => $client_os,
					'client_browser'      => $client_browser,
					'statement_number'    => sanitize_text_field( $st['number'] ?? '' ),
					'account_iban'        => sanitize_text_field( $st['account_iban'] ?? $account_iban ),
					'client_name'         => $client_name,
					'total_volume_cents'  => $total_volume_cents,
					'movements_count'     => $mv_count,
					'matched_count'       => $stats['orders_matched'],
					'estimated_minutes'   => $estimated_minutes,
					'suggested_fee_cents' => $suggested_fee_cents,
					'fee_status'          => 'unpaid',
					'export_unlocked'     => $export_unlocked,
					'created_at'          => current_time( 'mysql' ),
				] );
			}
		}

		// Deduct credits if client wallet was used.
		$credits_deducted  = 0;
		$credits_remaining = null;
		if ( ! empty( $client_wallet ) ) {
			$statement_count    = count( $statements );
			$cost_per_statement = max( 1, (int) ( $client_wallet['cost_per_statement'] ?? 1 ) );
			$credits_deducted   = $statement_count * $cost_per_statement;
			$credits_remaining  = max( 0, (int) ( $client_wallet['credits_balance'] ?? 0 ) - $credits_deducted );
			$statement_cost_cents = $statement_count * (int) round( $fee_base_amount * 100 );

			if ( $wpdb && method_exists( $wpdb, 'query' ) ) {
				$tokens_table = $wpdb->prefix . 'vorkei_client_tokens';
				$query = $wpdb->prepare(
					"UPDATE $tokens_table 
					 SET credits_balance = credits_balance - %d, 
					     total_statements_used = total_statements_used + %d,
					     total_spent_cents = total_spent_cents + %d,
					     updated_at = %s 
					 WHERE id = %d AND credits_balance >= %d",
					$credits_deducted,
					$statement_count,
					$statement_cost_cents,
					current_time( 'mysql' ),
					(int) $client_wallet['id'],
					$credits_deducted
				);
				$affected = $wpdb->query( $query );
				if ( false === $affected || 0 === $affected ) {
					return new WP_Error(
						'vorkei_concurrency_insufficient_credits',
						__( 'Súbežná požiadavka vyčerpala kredit alebo je zostatok nedostatočný.', 'vorkei-bridge' ),
						[ 'status' => 402 ]
					);
				}
			}
		}

		$response_payload = [
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
		];

		if ( ! empty( $client_wallet ) ) {
			$response_payload['wallet'] = [
				'client_name'        => $client_wallet['client_name'] ?? '',
				'credits_deducted'   => $credits_deducted,
				'credits_remaining'  => $credits_remaining,
				'cost_per_statement' => (int) ( $client_wallet['cost_per_statement'] ?? 1 ),
			];
		}

		return rest_ensure_response( $response_payload );
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

	/**
	 * Export bank statement into CSV or JSON, with Forensic watermark/locking for unpaid commercial use.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|void
	 */
	public function export_statement( WP_REST_Request $request ) {
		global $wpdb;

		$id     = sanitize_text_field( $request->get_param( 'id' ) );
		$format = sanitize_text_field( $request->get_param( 'format' ) ?: 'csv' );

		$statements_table = $wpdb->prefix . 'vorkei_statements';
		$movements_table  = $wpdb->prefix . 'vorkei_movements';
		$audit_table      = $wpdb->prefix . 'vorkei_audit_log';

		$statement = method_exists( $wpdb, 'get_row' )
			? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $statements_table WHERE id = %s LIMIT 1", $id ), ARRAY_A )
			: null;

		if ( ! $statement ) {
			return new WP_Error( 'not_found', __( 'Výpis nebol nájdený.', 'vorkei-bridge' ), [ 'status' => 404 ] );
		}

		$movements = method_exists( $wpdb, 'get_results' )
			? (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $movements_table WHERE statement_id = %s ORDER BY date ASC", $id ), ARRAY_A )
			: [];

		$audit = method_exists( $wpdb, 'get_row' )
			? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $audit_table WHERE statement_id = %s ORDER BY id DESC LIMIT 1", $id ), ARRAY_A )
			: null;

		$is_unlocked = $audit ? ( (int) ( $audit['export_unlocked'] ?? 0 ) === 1 ) : false;

		if ( 'json' === $format ) {
			$export_movements = [];
			foreach ( $movements as $idx => $mv ) {
				if ( ! $is_unlocked && $idx >= 2 ) {
					$mv['party_name'] = '*** ZAMKNUTÝ EXPORT (VORKEI BRIDGE) ***';
					$mv['party_iban'] = '*** ZAMKNUTÉ ***';
					$mv['note']       = 'LICENCIA NEOVERENÁ — PRE ODOMKNUTIE KONTAKTUJTE ADMINISTRÁTORA';
				}
				$export_movements[] = $mv;
			}

			return rest_ensure_response( [
				'status'    => $is_unlocked ? 'unlocked' : 'locked',
				'warning'   => $is_unlocked ? null : __( 'Tento export je zamknutý licenčným systémom Vorkei Statement Bridge. Kontaktujte administrátora pre odomknutie komerčného exportu.', 'vorkei-bridge' ),
				'statement' => $statement,
				'movements' => $export_movements,
			] );
		}

		// CSV Export
		$filename = 'vorkei_export_' . sanitize_file_name( $id ) . ( $is_unlocked ? '_unlocked' : '_locked' ) . '.csv';

		if ( ! headers_sent() ) {
			header( 'Content-Type: text/csv; charset=UTF-8' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );
		}

		$output = fopen( 'php://output', 'w' );
		// Write UTF-8 BOM
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		if ( ! $is_unlocked ) {
			fputcsv( $output, [ '# =========================================================================' ] );
			fputcsv( $output, [ '# UPOZORNENIE: TENTO EXPORT JE ZAMKNUTÝ LICENCIOU VORKEI STATEMENT BRIDGE' ] );
			fputcsv( $output, [ '# PRE ODOMKNUTIE PLNEJ VERZIE PRE ÚČTOVNÍCTVO KONTAKTUJTE SPRÁVCU SYSTÉMU' ] );
			fputcsv( $output, [ '# =========================================================================' ] );
		}

		fputcsv( $output, [ 'Dátum', 'Suma (€)', 'Druh', 'Partner', 'IBAN partnera', 'Variabilný symbol', 'Poznámka' ], ';' );

		foreach ( $movements as $idx => $mv ) {
			$amt = number_format( (int) ( $mv['amount_cents'] ?? 0 ) / 100, 2, ',', '' );
			if ( ! $is_unlocked && $idx >= 2 ) {
				fputcsv( $output, [
					$mv['date'] ?? '',
					$amt,
					$mv['kind'] ?? '',
					'*** ZAMKNUTÉ (VORKEI LICENCIA) ***',
					'*** ZAMKNUTÉ ***',
					$mv['variable_symbol'] ?? '',
					'NEZAPLATENÁ ASISTENCIA — KONTAKTUJTE SPRÁVCU',
				], ';' );
			} else {
				fputcsv( $output, [
					$mv['date'] ?? '',
					$amt,
					$mv['kind'] ?? '',
					$mv['party_name'] ?? '',
					$mv['party_iban'] ?? '',
					$mv['variable_symbol'] ?? '',
					$mv['note'] ?? '',
				], ';' );
			}
		}

		fclose( $output );
		exit;
	}

	/**
	 * Retrieve forensic audit logs.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_audit_logs( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$table = $wpdb->prefix . 'vorkei_audit_log';
		$page  = absint( $request->get_param( 'page' ) ?: 1 );
		$limit = absint( $request->get_param( 'per_page' ) ?: 50 );
		$off   = ( $page - 1 ) * $limit;

		$logs = method_exists( $wpdb, 'get_results' )
			? (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d", $limit, $off ), ARRAY_A )
			: [];

		$total = ( method_exists( $wpdb, 'get_var' ) )
			? (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table" )
			: count( $logs );

		$response = rest_ensure_response( $logs );
		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) ceil( max( 1, $total ) / $limit ) );

		return $response;
	}

	/**
	 * Toggle export lock for a specific audit log item.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function toggle_export_lock( WP_REST_Request $request ) {
		global $wpdb;

		$id    = absint( $request->get_param( 'id' ) );
		$table = $wpdb->prefix . 'vorkei_audit_log';

		$row = method_exists( $wpdb, 'get_row' )
			? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d LIMIT 1", $id ), ARRAY_A )
			: null;

		if ( ! $row ) {
			return new WP_Error( 'not_found', __( 'Záznam auditu nebol nájdený.', 'vorkei-bridge' ), [ 'status' => 404 ] );
		}

		$new_state = ( (int) $row['export_unlocked'] === 1 ) ? 0 : 1;

		if ( method_exists( $wpdb, 'update' ) ) {
			$wpdb->update( $table, [ 'export_unlocked' => $new_state ], [ 'id' => $id ] );
		}

		return rest_ensure_response( [
			'success'         => true,
			'id'              => $id,
			'export_unlocked' => $new_state,
			'message'         => $new_state ? __( 'Export bol úspešne odomknutý.', 'vorkei-bridge' ) : __( 'Export bol uzamknutý.', 'vorkei-bridge' ),
		] );
	}

	/**
	 * Retrieve list of client partner wallets.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_wallets( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$tokens_table = $wpdb->prefix . 'vorkei_client_tokens';
		$wallets      = ( $wpdb && method_exists( $wpdb, 'get_results' ) )
			? (array) $wpdb->get_results( "SELECT * FROM $tokens_table ORDER BY created_at DESC", ARRAY_A )
			: [];

		return rest_ensure_response( [
			'success' => true,
			'wallets' => $wallets,
			'count'   => count( $wallets ),
		] );
	}

	/**
	 * Create a new client partner wallet / API token.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_wallet( WP_REST_Request $request ) {
		global $wpdb;

		$params      = $request->get_json_params() ?: $request->get_params();
		$client_name = sanitize_text_field( $params['client_name'] ?? '' );

		if ( empty( $client_name ) ) {
			return new WP_Error(
				'vorkei_missing_client_name',
				__( 'Meno partnera je povinné.', 'vorkei-bridge' ),
				[ 'status' => 400 ]
			);
		}

		$client_email       = sanitize_email( $params['client_email'] ?? '' );
		$credits_balance    = max( 0, (int) ( $params['credits_balance'] ?? 0 ) );
		$cost_per_statement = max( 1, (int) ( $params['cost_per_statement'] ?? 1 ) );
		$notes              = sanitize_textarea_field( $params['notes'] ?? '' );

		$token_key = ! empty( $params['token_key'] )
			? preg_replace( '/[^A-Za-z0-9_\-]/', '', sanitize_text_field( $params['token_key'] ) )
			: ( 'VRK-' . strtoupper( substr( md5( uniqid( (string) ( function_exists( 'wp_rand' ) ? wp_rand() : rand() ), true ) ), 0, 16 ) ) );

		$tokens_table = $wpdb->prefix . 'vorkei_client_tokens';

		if ( method_exists( $wpdb, 'get_var' ) ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $tokens_table WHERE token_key = %s LIMIT 1", $token_key ) );
			if ( $exists ) {
				return new WP_Error(
					'vorkei_token_exists',
					__( 'Tento API Token už existuje. Zvoľte unikátny kľúč.', 'vorkei-bridge' ),
					[ 'status' => 400 ]
				);
			}
		}

		$data = [
			'token_key'             => $token_key,
			'client_name'           => $client_name,
			'client_email'          => $client_email,
			'credits_balance'       => $credits_balance,
			'cost_per_statement'    => $cost_per_statement,
			'total_statements_used' => 0,
			'total_spent_cents'     => 0,
			'is_active'             => 1,
			'notes'                 => $notes,
			'created_at'            => current_time( 'mysql' ),
		];

		if ( method_exists( $wpdb, 'insert' ) ) {
			$wpdb->insert( $tokens_table, $data );
			$insert_id = $wpdb->insert_id ?? 0;
		} else {
			$insert_id = 0;
		}

		$data['id'] = $insert_id;

		return rest_ensure_response( [
			'success' => true,
			'message' => __( 'Klientska peňaženka / API token úspešne vytvorený.', 'vorkei-bridge' ),
			'wallet'  => $data,
		] );
	}

	/**
	 * Top up credits for a partner wallet.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function topup_wallet( WP_REST_Request $request ) {
		global $wpdb;

		$id      = absint( $request->get_param( 'id' ) );
		$params  = $request->get_json_params() ?: $request->get_params();
		$credits = (int) ( $params['credits'] ?? 5 );

		$tokens_table = $wpdb->prefix . 'vorkei_client_tokens';
		$wallet       = ( $wpdb && method_exists( $wpdb, 'get_row' ) )
			? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tokens_table WHERE id = %d LIMIT 1", $id ), ARRAY_A )
			: null;

		if ( ! $wallet ) {
			return new WP_Error(
				'vorkei_wallet_not_found',
				__( 'Klientska peňaženka nebola nájdená.', 'vorkei-bridge' ),
				[ 'status' => 404 ]
			);
		}

		$new_balance = max( 0, (int) $wallet['credits_balance'] + $credits );

		if ( method_exists( $wpdb, 'update' ) ) {
			$wpdb->update(
				$tokens_table,
				[
					'credits_balance' => $new_balance,
					'updated_at'      => current_time( 'mysql' ),
				],
				[ 'id' => $id ]
			);
		}

		return rest_ensure_response( [
			'success'         => true,
			'id'              => $id,
			'client_name'     => $wallet['client_name'],
			'added_credits'   => $credits,
			'credits_balance' => $new_balance,
			'message'         => sprintf(
				/* translators: 1: Added credits, 2: Partner name, 3: Total balance */
				__( 'Úspešne pridaných %1$d kreditov pre partnera %2$s. Nový zostatok: %3$d kreditov.', 'vorkei-bridge' ),
				$credits,
				$wallet['client_name'],
				$new_balance
			),
		] );
	}

	/**
	 * Toggle wallet active status (activate/suspend).
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function toggle_wallet_active( WP_REST_Request $request ) {
		global $wpdb;

		$id           = absint( $request->get_param( 'id' ) );
		$tokens_table = $wpdb->prefix . 'vorkei_client_tokens';
		$wallet       = ( $wpdb && method_exists( $wpdb, 'get_row' ) )
			? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tokens_table WHERE id = %d LIMIT 1", $id ), ARRAY_A )
			: null;

		if ( ! $wallet ) {
			return new WP_Error(
				'vorkei_wallet_not_found',
				__( 'Klientska peňaženka nebola nájdená.', 'vorkei-bridge' ),
				[ 'status' => 404 ]
			);
		}

		$new_status = ( (int) $wallet['is_active'] === 1 ) ? 0 : 1;

		if ( method_exists( $wpdb, 'update' ) ) {
			$wpdb->update(
				$tokens_table,
				[
					'is_active'  => $new_status,
					'updated_at' => current_time( 'mysql' ),
				],
				[ 'id' => $id ]
			);
		}

		return rest_ensure_response( [
			'success'   => true,
			'id'        => $id,
			'is_active' => $new_status,
			'message'   => $new_status
				? __( 'API Token bol aktivovaný.', 'vorkei-bridge' )
				: __( 'API Token bol pozastavený.', 'vorkei-bridge' ),
		] );
	}

	/**
	 * Delete a client partner wallet.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_wallet( WP_REST_Request $request ) {
		global $wpdb;

		$id           = absint( $request->get_param( 'id' ) );
		$tokens_table = $wpdb->prefix . 'vorkei_client_tokens';

		if ( method_exists( $wpdb, 'delete' ) ) {
			$wpdb->delete( $tokens_table, [ 'id' => $id ] );
		}

		return rest_ensure_response( [
			'success' => true,
			'id'      => $id,
			'message' => __( 'Klientska peňaženka bola zmazaná.', 'vorkei-bridge' ),
		] );
	}

	/**
	 * Verify Stripe Webhook HMAC-SHA256 signature.
	 *
	 * @param string $raw_payload Raw webhook payload.
	 * @param string $sig_header  Value of Stripe-Signature header.
	 * @param string $secret      Configured webhook secret (whsec_...).
	 * @param int    $tolerance   Allowed timestamp skew in seconds (default: 300).
	 * @return bool
	 */
	public function verify_stripe_signature( string $raw_payload, string $sig_header, string $secret, int $tolerance = 300 ): bool {
		if ( empty( $sig_header ) || empty( $secret ) ) {
			return false;
		}

		$timestamp  = 0;
		$signatures = [];

		$parts = explode( ',', $sig_header );
		foreach ( $parts as $part ) {
			$kv = explode( '=', trim( $part ), 2 );
			if ( count( $kv ) === 2 ) {
				if ( 't' === $kv[0] ) {
					$timestamp = (int) $kv[1];
				} elseif ( 'v1' === $kv[0] ) {
					$signatures[] = $kv[1];
				}
			}
		}

		if ( 0 === $timestamp || empty( $signatures ) ) {
			return false;
		}

		// Check timestamp freshness to prevent replay attacks
		if ( $tolerance > 0 && abs( time() - $timestamp ) > $tolerance ) {
			return false;
		}

		$signed_payload = $timestamp . '.' . $raw_payload;
		$expected_sig   = hash_hmac( 'sha256', $signed_payload, $secret );

		foreach ( $signatures as $sig ) {
			if ( hash_equals( $expected_sig, $sig ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Process a Stripe checkout session object and allocate credits to customer wallet.
	 *
	 * @param array  $session
	 * @param string $event_id
	 * @param string $raw_payload
	 * @param bool   $is_reconcile
	 * @return array
	 */
	public function process_stripe_checkout_session( array $session, string $event_id, string $raw_payload, bool $is_reconcile = false ): array {
		global $wpdb;

		$tx_table     = $wpdb->prefix . 'vorkei_credit_transactions';
		$tokens_table = $wpdb->prefix . 'vorkei_client_tokens';

		$session_id     = sanitize_text_field( $session['id'] ?? '' );
		$payment_status = sanitize_text_field( $session['payment_status'] ?? 'unpaid' );
		$payment_intent = sanitize_text_field( $session['payment_intent'] ?? '' );
		$amount_total   = (int) ( $session['amount_total'] ?? 0 );
		$currency       = strtolower( sanitize_text_field( $session['currency'] ?? 'eur' ) );

		$customer_email = sanitize_email( $session['customer_details']['email'] ?? ( $session['customer_email'] ?? '' ) );
		$customer_name  = sanitize_text_field( $session['customer_details']['name'] ?? '' );

		// Determine credits to allocate
		$credits = 0;
		if ( isset( $session['metadata']['credits'] ) ) {
			$credits = (int) $session['metadata']['credits'];
		} elseif ( $amount_total >= 10000 ) {
			$credits = 20;
		} elseif ( $amount_total >= 5000 ) {
			$credits = 10;
		} elseif ( $amount_total >= 500 ) {
			$credits = (int) round( $amount_total / 500 );
		} else {
			$credits = 1;
		}

		// 1. Check idempotency: does transaction already exist for this event_id or session_id?
		$existing = null;
		if ( method_exists( $wpdb, 'get_row' ) ) {
			if ( ! empty( $event_id ) ) {
				$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tx_table WHERE stripe_event_id = %s LIMIT 1", $event_id ), ARRAY_A );
			}
			if ( ! $existing && ! empty( $session_id ) ) {
				$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tx_table WHERE stripe_session_id = %s LIMIT 1", $session_id ), ARRAY_A );
			}
		}

		if ( $existing && 'completed' === ( $existing['payment_status'] ?? '' ) ) {
			return [
				'status'         => 'already_processed',
				'transaction_id' => (int) $existing['id'],
				'credits'        => (int) $existing['credits_amount'],
				'token_key'      => $existing['token_key'],
				'wallet_id'      => (int) $existing['token_id'],
				'customer_email' => $existing['customer_email'],
			];
		}

		// 2. Only credit when payment_status is 'paid'
		if ( 'paid' === $payment_status ) {
			$token_id  = null;
			$token_key = '';

			// Check if customer already has a wallet token
			if ( ! empty( $customer_email ) && method_exists( $wpdb, 'get_row' ) ) {
				$wallet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tokens_table WHERE client_email = %s LIMIT 1", $customer_email ), ARRAY_A );
				if ( $wallet ) {
					$token_id  = (int) $wallet['id'];
					$token_key = $wallet['token_key'];
					// Update credits
					if ( method_exists( $wpdb, 'query' ) ) {
						$wpdb->query( $wpdb->prepare(
							"UPDATE $tokens_table SET credits_balance = credits_balance + %d, total_spent_cents = total_spent_cents + %d, updated_at = CURRENT_TIMESTAMP WHERE id = %d",
							$credits,
							$amount_total,
							$token_id
						) );
					}
				}
			}

			// If no wallet exists, auto-create a new one!
			if ( ! $token_id && method_exists( $wpdb, 'insert' ) ) {
				$token_key = 'VRK-' . strtoupper( substr( md5( uniqid( (string) ( function_exists( 'wp_rand' ) ? wp_rand() : rand() ), true ) ), 0, 16 ) );
				$wpdb->insert( $tokens_table, [
					'token_key'             => $token_key,
					'client_name'           => $customer_name ?: ( 'Zákazník ' . $customer_email ),
					'client_email'          => $customer_email,
					'credits_balance'       => $credits,
					'cost_per_statement'    => 1,
					'total_statements_used' => 0,
					'total_spent_cents'     => $amount_total,
					'is_active'             => 1,
					'notes'                 => 'Vytvorené automaticky cez Stripe platbu ' . $session_id,
				] );
				$token_id = (int) $wpdb->insert_id;
			}

			// Insert or update transaction in credit transactions table
			if ( $existing && method_exists( $wpdb, 'update' ) ) {
				$wpdb->update(
					$tx_table,
					[
						'token_id'       => $token_id,
						'token_key'      => $token_key,
						'amount_cents'   => $amount_total,
						'credits_amount' => $credits,
						'payment_status' => 'completed',
						'raw_payload'    => $raw_payload,
					],
					[ 'id' => $existing['id'] ]
				);
				$tx_id = (int) $existing['id'];
			} elseif ( method_exists( $wpdb, 'insert' ) ) {
				$wpdb->insert( $tx_table, [
					'stripe_event_id'          => $event_id ?: ( 'rec_' . uniqid() ),
					'stripe_session_id'        => $session_id,
					'stripe_payment_intent_id' => $payment_intent,
					'customer_email'           => $customer_email,
					'customer_name'            => $customer_name,
					'token_id'                 => $token_id,
					'token_key'                => $token_key,
					'amount_cents'             => $amount_total,
					'currency'                 => $currency,
					'credits_amount'           => $credits,
					'payment_status'           => 'completed',
					'raw_payload'              => $raw_payload,
				] );
				$tx_id = (int) $wpdb->insert_id;
			}

			return [
				'status'         => 'completed',
				'transaction_id' => $tx_id ?? 0,
				'credits'        => $credits,
				'token_key'      => $token_key,
				'wallet_id'      => $token_id,
				'customer_email' => $customer_email,
			];
		} else {
			// Pending / Unpaid state (e.g. async bank transfer or delayed card)
			if ( method_exists( $wpdb, 'insert' ) && ! $existing ) {
				$wpdb->insert( $tx_table, [
					'stripe_event_id'          => $event_id ?: ( 'pending_' . uniqid() ),
					'stripe_session_id'        => $session_id,
					'stripe_payment_intent_id' => $payment_intent,
					'customer_email'           => $customer_email,
					'customer_name'            => $customer_name,
					'amount_cents'             => $amount_total,
					'currency'                 => $currency,
					'credits_amount'           => $credits,
					'payment_status'           => 'pending',
					'raw_payload'              => $raw_payload,
				] );
				$tx_id = (int) $wpdb->insert_id;
			}

			return [
				'status'         => 'pending',
				'transaction_id' => $tx_id ?? ( $existing['id'] ?? 0 ),
				'credits'        => 0,
				'message'        => 'Platba ešte nebola potvrdená (čaká sa na úhradu). Kredity zatiaľ neboli pripísané.',
			];
		}
	}

	/**
	 * Handle Stripe Webhook incoming request.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_stripe_webhook( WP_REST_Request $request ) {
		global $wpdb;

		$raw_body   = $request->get_body();
		$sig_header = $request->get_header( 'stripe-signature' );

		$secret = function_exists( 'get_option' ) ? ( get_option( 'vorkei_stripe_webhook_secret', '' ) ?: get_option( 'vorkei_bridge_stripe_webhook_secret', '' ) ) : '';
		if ( empty( $secret ) && defined( 'VORKEI_STRIPE_WEBHOOK_SECRET' ) ) {
			$secret = VORKEI_STRIPE_WEBHOOK_SECRET;
		}

		if ( empty( $secret ) ) {
			return new WP_Error(
				'stripe_webhook_not_configured',
				__( 'Stripe Webhook Secret nie je nakonfigurovaný vo WordPress administrácii.', 'vorkei-bridge' ),
				[ 'status' => 500 ]
			);
		}

		if ( empty( $sig_header ) || ! $this->verify_stripe_signature( $raw_body, $sig_header, $secret ) ) {
			return new WP_Error(
				'stripe_invalid_signature',
				__( 'Neplatný Stripe podpis požiadavky (HMAC-SHA256 zlyhal).', 'vorkei-bridge' ),
				[ 'status' => 400 ]
			);
		}

		$payload = json_decode( $raw_body, true );
		if ( ! is_array( $payload ) || empty( $payload['type'] ) ) {
			return new WP_Error(
				'stripe_invalid_payload',
				__( 'Neplatný formát JSON payloadu.', 'vorkei-bridge' ),
				[ 'status' => 400 ]
			);
		}

		// Environment separation check
		if ( isset( $payload['livemode'] ) ) {
			$is_live_event  = (bool) $payload['livemode'];
			$is_test_secret = ( strpos( $secret, 'whsec_test_' ) === 0 || strpos( $secret, 'test' ) !== false );
			$is_live_secret = ( strpos( $secret, 'whsec_live_' ) === 0 || ( ! $is_test_secret && strpos( $secret, 'whsec_' ) === 0 ) );

			if ( $is_live_event && $is_test_secret ) {
				return new WP_Error(
					'stripe_environment_mismatch',
					__( 'Prijatý livemode event v testovacom prostredí. Požiadavka bola odmietnutá.', 'vorkei-bridge' ),
					[ 'status' => 400 ]
				);
			}
			if ( ! $is_live_event && $is_live_secret && ! $is_test_secret ) {
				return new WP_Error(
					'stripe_environment_mismatch',
					__( 'Prijatý test event v produkčnom live prostredí. Požiadavka bola odmietnutá.', 'vorkei-bridge' ),
					[ 'status' => 400 ]
				);
			}
		}

		$event_type = sanitize_text_field( $payload['type'] );
		$event_id   = sanitize_text_field( $payload['id'] ?? '' );

		switch ( $event_type ) {
			case 'checkout.session.completed':
				$session = $payload['data']['object'] ?? [];
				$result  = $this->process_stripe_checkout_session( $session, $event_id, $raw_body );
				return rest_ensure_response( [
					'received' => true,
					'event'    => $event_type,
					'result'   => $result,
				] );

			case 'checkout.session.async_payment_succeeded':
				$session = $payload['data']['object'] ?? [];
				$session['payment_status'] = 'paid';
				$result  = $this->process_stripe_checkout_session( $session, $event_id, $raw_body );
				return rest_ensure_response( [
					'received' => true,
					'event'    => $event_type,
					'result'   => $result,
				] );

			case 'checkout.session.async_payment_failed':
				$session    = $payload['data']['object'] ?? [];
				$session_id = sanitize_text_field( $session['id'] ?? '' );
				if ( ! empty( $session_id ) && method_exists( $wpdb, 'update' ) ) {
					$wpdb->update(
						$wpdb->prefix . 'vorkei_credit_transactions',
						[
							'payment_status' => 'failed',
							'raw_payload'    => $raw_body,
						],
						[ 'stripe_session_id' => $session_id ]
					);
				}
				return rest_ensure_response( [
					'received' => true,
					'event'    => $event_type,
					'status'   => 'failed',
				] );

			case 'charge.refunded':
				$charge = $payload['data']['object'] ?? [];
				$pi_id  = sanitize_text_field( $charge['payment_intent'] ?? '' );
				if ( ! empty( $pi_id ) && method_exists( $wpdb, 'update' ) ) {
					$wpdb->update(
						$wpdb->prefix . 'vorkei_credit_transactions',
						[
							'payment_status' => 'refunded',
							'raw_payload'    => $raw_body,
						],
						[ 'stripe_payment_intent_id' => $pi_id ]
					);
				}
				return rest_ensure_response( [
					'received' => true,
					'event'    => $event_type,
					'status'   => 'refunded',
				] );

			case 'charge.dispute.created':
				$dispute   = $payload['data']['object'] ?? [];
				$charge_id = sanitize_text_field( $dispute['charge'] ?? '' );
				return rest_ensure_response( [
					'received' => true,
					'event'    => $event_type,
					'status'   => 'disputed',
				] );

			default:
				// Return 200 for other unhandled events so Stripe stops retrying
				return rest_ensure_response( [
					'received' => true,
					'event'    => $event_type,
					'status'   => 'ignored',
				] );
		}
	}

	/**
	 * Reconcile a Stripe checkout session manually or via fallback verification.
	 *
	 * @param string $session_id
	 * @return array
	 */
	public function reconcile_stripe_session( string $session_id ): array {
		global $wpdb;

		$session_id = trim( sanitize_text_field( $session_id ) );
		if ( empty( $session_id ) ) {
			return [ 'success' => false, 'message' => __( 'Session ID je prázdne.', 'vorkei-bridge' ) ];
		}

		$tx_table = $wpdb->prefix . 'vorkei_credit_transactions';
		$existing = ( method_exists( $wpdb, 'get_row' ) )
			? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tx_table WHERE stripe_session_id = %s LIMIT 1", $session_id ), ARRAY_A )
			: null;

		if ( $existing && 'completed' === ( $existing['payment_status'] ?? '' ) ) {
			return [
				'success'   => true,
				'status'    => 'already_completed',
				'message'   => __( 'Platba už bola v minulosti úspešne vyrovnaná a kredity pripísané.', 'vorkei-bridge' ),
				'credits'   => (int) $existing['credits_amount'],
				'token_key' => $existing['token_key'],
				'email'     => $existing['customer_email'],
			];
		}

		$secret_key = function_exists( 'get_option' ) ? ( get_option( 'vorkei_stripe_secret_key', '' ) ?: get_option( 'vorkei_bridge_stripe_secret_key', '' ) ) : '';
		if ( empty( $secret_key ) && defined( 'VORKEI_STRIPE_SECRET_KEY' ) ) {
			$secret_key = VORKEI_STRIPE_SECRET_KEY;
		}

		if ( empty( $secret_key ) ) {
			return [
				'success' => false,
				'message' => __( 'Stripe Secret Key (sk_...) nie je nakonfigurovaný v nastaveniach Vorkei Bridge.', 'vorkei-bridge' ),
			];
		}

		$api_url  = 'https://api.stripe.com/v1/checkout/sessions/' . urlencode( $session_id );
		$response = wp_remote_get( $api_url, [
			'headers' => [
				'Authorization' => 'Bearer ' . $secret_key,
			],
			'timeout' => 20,
		] );

		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'message' => __( 'Chyba spojenia so Stripe API: ', 'vorkei-bridge' ) . $response->get_error_message(),
			];
		}

		$code    = wp_remote_retrieve_response_code( $response );
		$body    = wp_remote_retrieve_body( $response );
		$session = json_decode( $body, true );

		if ( 200 !== $code || empty( $session['id'] ) ) {
			return [
				'success' => false,
				'message' => __( 'Stripe vrátil chybu (', 'vorkei-bridge' ) . $code . '): ' . ( $session['error']['message'] ?? 'Neznáma relácia' ),
			];
		}

		if ( 'paid' !== ( $session['payment_status'] ?? '' ) ) {
			return [
				'success' => false,
				'status'  => $session['payment_status'] ?? 'unpaid',
				'message' => __( 'Platba v Stripe ešte nie je v stave "paid" (aktuálny stav: ', 'vorkei-bridge' ) . ( $session['payment_status'] ?? 'unpaid' ) . ').',
			];
		}

		// Process session and allocate credits
		$result = $this->process_stripe_checkout_session( $session, 'reconcile_' . $session['id'], $body, true );

		return [
			'success'   => true,
			'status'    => 'reconciled',
			'message'   => __( 'Platba bola úspešne vyrovnaná a kredity pripísané.', 'vorkei-bridge' ),
			'credits'   => $result['credits'] ?? 0,
			'token_key' => $result['token_key'] ?? '',
			'email'     => $result['customer_email'] ?? '',
		];
	}

	/**
	 * REST API endpoint callback for manual Stripe payment reconciliation.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_stripe_reconcile( WP_REST_Request $request ) {
		$params     = $request->get_json_params() ?: $request->get_params();
		$session_id = sanitize_text_field( $params['session_id'] ?? '' );

		if ( empty( $session_id ) ) {
			return new WP_Error(
				'missing_session_id',
				__( 'Je potrebné zadať parameter session_id.', 'vorkei-bridge' ),
				[ 'status' => 400 ]
			);
		}

		$result = $this->reconcile_stripe_session( $session_id );

		return rest_ensure_response( $result );
	}

	/**
	 * REST API endpoint to retrieve all credit transactions.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_credit_transactions( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$tx_table     = $wpdb->prefix . 'vorkei_credit_transactions';
		$transactions = ( $wpdb && method_exists( $wpdb, 'get_results' ) )
			? (array) $wpdb->get_results( "SELECT * FROM $tx_table ORDER BY created_at DESC LIMIT 100", ARRAY_A )
			: [];

		return rest_ensure_response( [
			'count'        => count( $transactions ),
			'transactions' => $transactions,
		] );
	}

	/**
	 * Create a server-authoritative Stripe Checkout Session.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_create_checkout_session( WP_REST_Request $request ) {
		global $wpdb;

		$params         = $request->get_json_params() ?: $request->get_params();
		$package_key    = sanitize_text_field( $params['package'] ?? 'starter' );
		$customer_email = sanitize_email( $params['customer_email'] ?? '' );
		$token_key      = sanitize_text_field( $params['token_key'] ?? '' );

		$packages = [
			'single' => [
				'name'         => '1 výpis',
				'credits'      => 1,
				'amount_cents' => 500,
				'currency'     => 'eur',
				'description'  => '1 Tatra banka výpis / 5,00 €',
			],
			'starter' => [
				'name'         => '10 výpisov',
				'credits'      => 10,
				'amount_cents' => 5000,
				'currency'     => 'eur',
				'description'  => '10 Tatra banka výpisov / 50,00 €',
			],
			'business' => [
				'name'         => '20 výpisov',
				'credits'      => 20,
				'amount_cents' => 10000,
				'currency'     => 'eur',
				'description'  => '20 Tatra banka výpisov / 100,00 €',
			],
		];

		if ( ! isset( $packages[ $package_key ] ) ) {
			return new WP_Error(
				'invalid_package',
				__( 'Neplatný balíček kreditov.', 'vorkei-bridge' ),
				[ 'status' => 400 ]
			);
		}

		$selected   = $packages[ $package_key ];
		$secret_key = function_exists( 'get_option' ) ? ( get_option( 'vorkei_stripe_secret_key', '' ) ?: get_option( 'vorkei_bridge_stripe_secret_key', '' ) ) : '';
		if ( empty( $secret_key ) && defined( 'VORKEI_STRIPE_SECRET_KEY' ) ) {
			$secret_key = VORKEI_STRIPE_SECRET_KEY;
		}

		// Mock or Sandbox mode when Stripe secret is not configured
		if ( empty( $secret_key ) ) {
			$mock_session_id = 'cs_test_' . substr( md5( uniqid( (string) ( function_exists( 'wp_rand' ) ? wp_rand() : rand() ), true ) ), 0, 24 );

			if ( $wpdb && method_exists( $wpdb, 'insert' ) ) {
				$tx_table = $wpdb->prefix . 'vorkei_credit_transactions';
				$wpdb->insert( $tx_table, [
					'stripe_event_id'          => 'mock_init_' . uniqid(),
					'stripe_session_id'        => $mock_session_id,
					'stripe_payment_intent_id' => 'pi_mock_' . uniqid(),
					'customer_email'           => $customer_email,
					'customer_name'            => 'Test Zákazník',
					'token_key'                => $token_key,
					'amount_cents'             => $selected['amount_cents'],
					'currency'                 => $selected['currency'],
					'credits_amount'           => $selected['credits'],
					'payment_status'           => 'pending',
					'raw_payload'              => json_encode( [ 'mode' => 'mock_test', 'package' => $package_key ] ),
				] );
			}

			return rest_ensure_response( [
				'success'      => true,
				'mode'         => 'mock_test',
				'session_id'   => $mock_session_id,
				'checkout_url' => 'https://pdf-t3.vorkei.com/order-success?session_id=' . $mock_session_id,
				'package'      => $package_key,
				'credits'      => $selected['credits'],
				'amount_cents' => $selected['amount_cents'],
				'currency'     => $selected['currency'],
			] );
		}

		// Real Stripe API integration
		$api_url = 'https://api.stripe.com/v1/checkout/sessions';
		$post_fields = [
			'mode'                                                 => 'payment',
			'payment_method_types[0]'                              => 'card',
			'line_items[0][price_data][currency]'                  => $selected['currency'],
			'line_items[0][price_data][unit_amount]'               => (string) $selected['amount_cents'],
			'line_items[0][price_data][product_data][name]'        => 'Vorkei Statement Bridge – ' . $selected['name'],
			'line_items[0][price_data][product_data][description]' => $selected['description'],
			'line_items[0][quantity]'                              => '1',
			'metadata[package]'                                    => $package_key,
			'metadata[credits]'                                    => (string) $selected['credits'],
			'metadata[token_key]'                                  => $token_key,
			'success_url'                                          => 'https://pdf-t3.vorkei.com/order-success?session_id={CHECKOUT_SESSION_ID}',
			'cancel_url'                                           => 'https://pdf-t3.vorkei.com/#cennik',
		];

		if ( ! empty( $customer_email ) ) {
			$post_fields['customer_email'] = $customer_email;
		}

		$body = http_build_query( $post_fields );

		$response = wp_remote_post( $api_url, [
			'headers' => [
				'Authorization' => 'Bearer ' . $secret_key,
				'Content-Type'  => 'application/x-www-form-urlencoded',
			],
			'body'    => $body,
			'timeout' => 20,
		] );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'stripe_api_error',
				__( 'Chyba komunikácie so Stripe API: ', 'vorkei-bridge' ) . $response->get_error_message(),
				[ 'status' => 500 ]
			);
		}

		$code    = wp_remote_retrieve_response_code( $response );
		$body    = wp_remote_retrieve_body( $response );
		$session = json_decode( $body, true );

		if ( 200 !== $code || empty( $session['id'] ) ) {
			return new WP_Error(
				'stripe_session_creation_failed',
				__( 'Stripe zlyhal pri vytváraní relácie: ', 'vorkei-bridge' ) . ( $session['error']['message'] ?? 'Neznáma chyba' ),
				[ 'status' => $code ?: 500 ]
			);
		}

		// Record pending transaction
		if ( $wpdb && method_exists( $wpdb, 'insert' ) ) {
			$tx_table = $wpdb->prefix . 'vorkei_credit_transactions';
			$wpdb->insert( $tx_table, [
				'stripe_event_id'          => 'init_' . uniqid(),
				'stripe_session_id'        => $session['id'],
				'stripe_payment_intent_id' => $session['payment_intent'] ?? '',
				'customer_email'           => $customer_email,
				'customer_name'            => '',
				'token_key'                => $token_key,
				'amount_cents'             => $selected['amount_cents'],
				'currency'                 => $selected['currency'],
				'credits_amount'           => $selected['credits'],
				'payment_status'           => 'pending',
				'raw_payload'              => $body,
			] );
		}

		return rest_ensure_response( [
			'success'      => true,
			'session_id'   => $session['id'],
			'checkout_url' => $session['url'] ?? '',
			'package'      => $package_key,
			'credits'      => $selected['credits'],
			'amount_cents' => $selected['amount_cents'],
			'currency'     => $selected['currency'],
		] );
	}

	/**
	 * Retrieve order, payment, and wallet delivery status by session_id.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_order_status( WP_REST_Request $request ) {
		global $wpdb;

		$params     = $request->get_params();
		$session_id = sanitize_text_field( $params['session_id'] ?? '' );

		if ( empty( $session_id ) ) {
			return new WP_Error(
				'missing_session_id',
				__( 'Parameter session_id je povinný.', 'vorkei-bridge' ),
				[ 'status' => 400 ]
			);
		}

		$tx_table     = $wpdb->prefix . 'vorkei_credit_transactions';
		$tokens_table = $wpdb->prefix . 'vorkei_client_tokens';

		$tx = ( $wpdb && method_exists( $wpdb, 'get_row' ) )
			? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tx_table WHERE stripe_session_id = %s LIMIT 1", $session_id ), ARRAY_A )
			: null;

		// If not found in DB or still pending, attempt Stripe reconciliation
		if ( ( ! $tx || 'completed' !== ( $tx['payment_status'] ?? '' ) ) && strpos( $session_id, 'cs_' ) === 0 ) {
			$reconcile = $this->reconcile_stripe_session( $session_id );
			if ( ! empty( $reconcile['success'] ) && 'reconciled' === ( $reconcile['status'] ?? '' ) ) {
				$tx = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tx_table WHERE stripe_session_id = %s LIMIT 1", $session_id ), ARRAY_A );
			}
		}

		if ( ! $tx ) {
			return new WP_Error(
				'order_not_found',
				__( 'Objednávka s týmto Session ID nebola nájdená. Kontaktujte podporu na support@vorkei.com.', 'vorkei-bridge' ),
				[ 'status' => 404, 'support_email' => 'support@vorkei.com' ]
			);
		}

		$token_key = $tx['token_key'] ?? '';
		$wallet    = null;
		if ( ! empty( $token_key ) && method_exists( $wpdb, 'get_row' ) ) {
			$wallet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tokens_table WHERE token_key = %s LIMIT 1", $token_key ), ARRAY_A );
		}
		if ( ! $wallet && ! empty( $tx['token_id'] ) && method_exists( $wpdb, 'get_row' ) ) {
			$wallet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tokens_table WHERE id = %d LIMIT 1", (int) $tx['token_id'] ), ARRAY_A );
		}

		$balance = $wallet ? (int) $wallet['credits_balance'] : (int) $tx['credits_amount'];
		$redacted = ( strlen( $token_key ) > 8 )
			? ( substr( $token_key, 0, 8 ) . '...' . substr( $token_key, -4 ) )
			: $token_key;

		$zip_file = dirname( __DIR__, 2 ) . '/vorkei-statement-bridge.zip';
		$checksum = file_exists( $zip_file ) ? hash_file( 'sha256', $zip_file ) : 'a0cfc89041718abf0e197a4a84f4a3c3b67fe3c32bd6e58b7b156bd5b2f90f3b';

		return rest_ensure_response( [
			'success'         => true,
			'session_id'      => $tx['stripe_session_id'],
			'payment_status'  => $tx['payment_status'],
			'credits'         => (int) $tx['credits_amount'],
			'credits_balance' => $balance,
			'amount_cents'    => (int) $tx['amount_cents'],
			'currency'        => $tx['currency'],
			'customer_email'  => $tx['customer_email'],
			'customer_name'   => $tx['customer_name'],
			'token_key'       => ( 'completed' === $tx['payment_status'] ) ? $token_key : '',
			'redacted_token'  => $redacted,
			'download_url'    => 'https://pdf-t3.vorkei.com/wp-content/uploads/2026/10/vorkei-statement-bridge.zip',
			'plugin_version'  => '1.0.0',
			'checksum_sha256' => $checksum,
			'support_email'   => 'support@vorkei.com',
		] );
	}

	/**
	 * Revoke and regenerate an API Token securely.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_revoke_token( WP_REST_Request $request ) {
		global $wpdb;

		$params    = $request->get_json_params() ?: $request->get_params();
		$token_key = sanitize_text_field( $params['token_key'] ?? '' );

		if ( empty( $token_key ) ) {
			$wallet = $request->get_param( '_vorkei_client_wallet' );
			if ( ! empty( $wallet['token_key'] ) ) {
				$token_key = $wallet['token_key'];
			}
		}

		if ( empty( $token_key ) ) {
			return new WP_Error(
				'missing_token_key',
				__( 'Je potrebné zadať token_key.', 'vorkei-bridge' ),
				[ 'status' => 400 ]
			);
		}

		$tokens_table = $wpdb->prefix . 'vorkei_client_tokens';
		$wallet       = ( $wpdb && method_exists( $wpdb, 'get_row' ) )
			? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tokens_table WHERE token_key = %s LIMIT 1", $token_key ), ARRAY_A )
			: null;

		if ( ! $wallet ) {
			return new WP_Error(
				'vorkei_token_not_found',
				__( 'API Token nebol nájdený.', 'vorkei-bridge' ),
				[ 'status' => 404 ]
			);
		}

		$new_token = 'VRK-' . strtoupper( substr( md5( uniqid( (string) ( function_exists( 'wp_rand' ) ? wp_rand() : rand() ), true ) ), 0, 16 ) );

		if ( method_exists( $wpdb, 'update' ) ) {
			$wpdb->update(
				$tokens_table,
				[
					'token_key'  => $new_token,
					'updated_at' => current_time( 'mysql' ),
				],
				[ 'id' => (int) $wallet['id'] ]
			);
		}

		$redacted_old = ( strlen( $token_key ) > 8 )
			? ( substr( $token_key, 0, 8 ) . '...' . substr( $token_key, -4 ) )
			: $token_key;

		return rest_ensure_response( [
			'success'            => true,
			'message'            => __( 'API Token bol úspešne revokovaný a vygenerovaný nový.', 'vorkei-bridge' ),
			'old_token_redacted' => $redacted_old,
			'new_token_key'      => $new_token,
			'client_name'        => $wallet['client_name'],
		] );
	}
}
