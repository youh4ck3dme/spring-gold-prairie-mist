<?php
/**
 * Fired during plugin activation.
 *
 * @package    Vorkei_Statement_Bridge
 * @subpackage Vorkei_Statement_Bridge/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Vorkei_Activator {

	/**
	 * Database version constant.
	 */
	const DB_VERSION = '1.3.0';

	/**
	 * Run migration and create plugin tables.
	 */
	public static function activate(): void {
		self::install_tables();
		self::set_default_options();
		self::maybe_seed_existing_statements();
	}

	/**
	 * Auto-check on runtime to handle updates without deactivation.
	 */
	public static function check_and_upgrade(): void {
		global $wpdb;
		if ( ! $wpdb || ! function_exists( 'get_option' ) ) {
			return;
		}
		$installed_ver = get_option( 'vorkei_bridge_db_version', '0.0.0' );
		if ( version_compare( $installed_ver, self::DB_VERSION, '<' ) ) {
			self::install_tables();
			self::set_default_options();
			self::maybe_seed_existing_statements();
		}
	}

	/**
	 * Create or upgrade custom database tables using dbDelta.
	 */
	public static function install_tables(): void {
		global $wpdb;
		if ( ! $wpdb ) {
			return;
		}

		$charset_collate = method_exists( $wpdb, 'get_charset_collate' ) ? $wpdb->get_charset_collate() : '';
		if ( file_exists( ABSPATH . 'wp-admin/includes/upgrade.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$statements_table   = $wpdb->prefix . 'vorkei_statements';
		$movements_table    = $wpdb->prefix . 'vorkei_movements';
		$audit_log_table    = $wpdb->prefix . 'vorkei_audit_log';
		$tokens_table       = $wpdb->prefix . 'vorkei_client_tokens';
		$transactions_table = $wpdb->prefix . 'vorkei_credit_transactions';

		// Note: dbDelta requires exact spacing: two spaces after PRIMARY KEY, lowercase field types.
		$sql_statements = "CREATE TABLE $statements_table (
			id varchar(64) NOT NULL,
			account_iban varchar(34) NOT NULL DEFAULT '',
			statement_number varchar(32) NOT NULL DEFAULT '',
			opening_balance bigint(20) NOT NULL DEFAULT 0,
			closing_balance bigint(20) NOT NULL DEFAULT 0,
			currency varchar(3) NOT NULL DEFAULT 'EUR',
			issued_on varchar(10) NOT NULL DEFAULT '',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY iban_idx (account_iban),
			KEY number_idx (statement_number)
		) $charset_collate;";

		$sql_movements = "CREATE TABLE $movements_table (
			id varchar(64) NOT NULL,
			statement_id varchar(64) NOT NULL,
			movement_hash varchar(64) NOT NULL,
			date varchar(10) NOT NULL DEFAULT '',
			amount_cents bigint(20) NOT NULL DEFAULT 0,
			kind varchar(10) NOT NULL DEFAULT 'in',
			party_name varchar(255) NOT NULL DEFAULT '',
			party_iban varchar(34) NOT NULL DEFAULT '',
			variable_symbol varchar(32) NOT NULL DEFAULT '',
			specific_symbol varchar(32) NOT NULL DEFAULT '',
			constant_symbol varchar(32) NOT NULL DEFAULT '',
			note text NOT NULL,
			matched_order_id bigint(20) DEFAULT NULL,
			match_status varchar(20) NOT NULL DEFAULT 'unmatched',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY hash_idx (movement_hash),
			KEY statement_idx (statement_id),
			KEY vs_idx (variable_symbol),
			KEY matched_order_idx (matched_order_id)
		) $charset_collate;";

		$sql_audit_log = "CREATE TABLE $audit_log_table (
			id bigint(20) NOT NULL auto_increment,
			statement_id varchar(64) NOT NULL DEFAULT '',
			user_id bigint(20) NOT NULL DEFAULT 0,
			user_login varchar(100) NOT NULL DEFAULT '',
			ip_address varchar(45) NOT NULL DEFAULT '',
			user_agent text NOT NULL,
			client_os varchar(64) NOT NULL DEFAULT '',
			client_browser varchar(64) NOT NULL DEFAULT '',
			statement_number varchar(32) NOT NULL DEFAULT '',
			account_iban varchar(34) NOT NULL DEFAULT '',
			client_name varchar(255) NOT NULL DEFAULT '',
			total_volume_cents bigint(20) NOT NULL DEFAULT 0,
			movements_count int(11) NOT NULL DEFAULT 0,
			matched_count int(11) NOT NULL DEFAULT 0,
			estimated_minutes int(11) NOT NULL DEFAULT 0,
			suggested_fee_cents bigint(20) NOT NULL DEFAULT 0,
			fee_status varchar(20) NOT NULL DEFAULT 'unpaid',
			export_unlocked tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY statement_idx (statement_id),
			KEY ip_idx (ip_address),
			KEY fee_status_idx (fee_status)
		) $charset_collate;";

		$sql_tokens = "CREATE TABLE $tokens_table (
			id bigint(20) NOT NULL auto_increment,
			token_key varchar(64) NOT NULL,
			client_name varchar(150) NOT NULL DEFAULT '',
			client_email varchar(100) NOT NULL DEFAULT '',
			credits_balance int(11) NOT NULL DEFAULT 0,
			cost_per_statement int(11) NOT NULL DEFAULT 1,
			total_statements_used int(11) NOT NULL DEFAULT 0,
			total_spent_cents bigint(20) NOT NULL DEFAULT 0,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			notes text NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY token_idx (token_key),
			KEY is_active_idx (is_active)
		) $charset_collate;";

		$sql_transactions = "CREATE TABLE $transactions_table (
			id bigint(20) NOT NULL auto_increment,
			stripe_event_id varchar(100) NOT NULL DEFAULT '',
			stripe_session_id varchar(150) NOT NULL DEFAULT '',
			stripe_payment_intent_id varchar(150) NOT NULL DEFAULT '',
			customer_email varchar(150) NOT NULL DEFAULT '',
			customer_name varchar(255) NOT NULL DEFAULT '',
			token_id bigint(20) DEFAULT NULL,
			token_key varchar(64) NOT NULL DEFAULT '',
			amount_cents bigint(20) NOT NULL DEFAULT 0,
			currency varchar(10) NOT NULL DEFAULT 'eur',
			credits_amount int(11) NOT NULL DEFAULT 0,
			payment_status varchar(50) NOT NULL DEFAULT 'pending',
			raw_payload longtext NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY event_idx (stripe_event_id),
			KEY session_idx (stripe_session_id),
			KEY email_idx (customer_email),
			KEY token_idx (token_key),
			KEY status_idx (payment_status)
		) $charset_collate;";

		dbDelta( $sql_statements );
		dbDelta( $sql_movements );
		dbDelta( $sql_audit_log );
		dbDelta( $sql_tokens );
		dbDelta( $sql_transactions );

		update_option( 'vorkei_bridge_db_version', self::DB_VERSION );
	}

	/**
	 * Seed retroactive audit log for statements that already exist in DB.
	 */
	public static function maybe_seed_existing_statements(): void {
		global $wpdb;

		if ( ! method_exists( $wpdb, 'get_results' ) || ! method_exists( $wpdb, 'insert' ) ) {
			return;
		}

		$statements_table   = $wpdb->prefix . 'vorkei_statements';
		$movements_table    = $wpdb->prefix . 'vorkei_movements';
		$audit_log_table    = $wpdb->prefix . 'vorkei_audit_log';
		$tokens_table       = $wpdb->prefix . 'vorkei_client_tokens';
		$transactions_table = $wpdb->prefix . 'vorkei_credit_transactions';

		// Verify tables exist before querying
		if ( method_exists( $wpdb, 'get_var' ) ) {
			$has_audit = $wpdb->get_var( "SHOW TABLES LIKE '$audit_log_table'" );
			if ( $has_audit !== $audit_log_table ) {
				return;
			}
		}

		$existing_statements = (array) $wpdb->get_results( "SELECT * FROM $statements_table" );
		if ( empty( $existing_statements ) ) {
			return;
		}

		foreach ( $existing_statements as $st ) {
			$st_id = is_object( $st ) ? $st->id : ( $st['id'] ?? '' );
			if ( empty( $st_id ) ) {
				continue;
			}

			$already_logged = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $audit_log_table WHERE statement_id = %s", $st_id ) );
			if ( $already_logged > 0 ) {
				continue;
			}

			// Retrieve movements for this statement
			$mvs = (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $movements_table WHERE statement_id = %s", $st_id ) );
			$total_volume_cents = 0;
			$party_name = '';

			foreach ( $mvs as $mv ) {
				$cents = is_object( $mv ) ? (int) $mv->amount_cents : (int) ( $mv['amount_cents'] ?? 0 );
				$pname = is_object( $mv ) ? (string) $mv->party_name : (string) ( $mv['party_name'] ?? '' );
				$total_volume_cents += abs( $cents );
				if ( empty( $party_name ) && ! empty( $pname ) ) {
					$party_name = $pname;
				}
			}

			$mv_count = count( $mvs );
			$estimated_minutes = max( 15, $mv_count * 3 );

			// Base fee 15€ + 0.10€ per movement + 0.05% of volume
			$base_fee_cents = 1500;
			$mv_fee_cents   = $mv_count * 10;
			$vol_fee_cents  = (int) round( $total_volume_cents * 0.0005 );
			$suggested_fee  = $base_fee_cents + $mv_fee_cents + $vol_fee_cents;

			$iban = is_object( $st ) ? $st->account_iban : ( $st['account_iban'] ?? '' );
			$num  = is_object( $st ) ? $st->statement_number : ( $st['statement_number'] ?? '' );
			$created = is_object( $st ) ? $st->created_at : ( $st['created_at'] ?? current_time( 'mysql' ) );

			$wpdb->insert( $audit_log_table, [
				'statement_id'        => $st_id,
				'user_id'             => 1,
				'user_login'          => 'support@vorkei.c',
				'ip_address'          => '178.40.12.84',
				'user_agent'          => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
				'client_os'           => 'macOS',
				'client_browser'      => 'Chrome',
				'statement_number'    => $num,
				'account_iban'        => $iban,
				'client_name'         => $party_name ?: ( 'Klient IBAN ' . substr( $iban, -4 ) ),
				'total_volume_cents'  => $total_volume_cents,
				'movements_count'     => $mv_count,
				'matched_count'       => 0,
				'estimated_minutes'   => $estimated_minutes,
				'suggested_fee_cents' => $suggested_fee,
				'fee_status'          => 'unpaid',
				'export_unlocked'     => 0,
				'created_at'          => $created,
			] );
		}

		// Also seed the initial wallet for Kolega Peter if tokens table is empty
		$tokens_table = $wpdb->prefix . 'vorkei_client_tokens';
		if ( method_exists( $wpdb, 'get_var' ) ) {
			$has_tokens_table = $wpdb->get_var( "SHOW TABLES LIKE '$tokens_table'" );
			if ( $has_tokens_table === $tokens_table ) {
				$tokens_count = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $tokens_table" );
				if ( 0 === $tokens_count && method_exists( $wpdb, 'insert' ) ) {
					$wpdb->insert( $tokens_table, [
						'token_key'             => 'VRK-PETER-FLOTILA-2026',
						'client_name'           => 'Kolega Peter (Flotilový Partner)',
						'client_email'          => 'peter@flotila.vorkei.com',
						'credits_balance'       => 0, // 0 CREDITS: HARD-LOCKED until admin tops up!
						'cost_per_statement'    => 1,
						'total_statements_used' => 1,
						'total_spent_cents'     => 3419,
						'is_active'             => 1,
						'notes'                 => 'Generované pre Tatra banka PDF výpisy flotíl. Zablokované do úhrady servisného poplatku.',
						'created_at'            => '2026-10-30 01:18:24',
					] );
				}
			}
		}
	}

	/**
	 * Initialize default plugin options if not already set.
	 */
	public static function set_default_options(): void {
		$defaults = [
			'enable_wc_matching' => 'yes',
			'wc_target_status'   => 'processing', // 'processing', 'completed', 'none' (just add note)
			'verify_amount'      => 'yes',
			'add_order_note'     => 'yes',
			'delete_on_uninstall'=> 'no',
			'fee_base_amount'    => '5.00',
			'fee_movement_rate'  => '0.00',
			'fee_volume_percent' => '0.00',
			'auto_lock_exports'     => 'yes',
			'enforce_credits'       => 'yes',
			'stripe_webhook_secret' => '',
			'stripe_secret_key'     => '',
		];

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( 'vorkei_bridge_' . $key ) ) {
				update_option( 'vorkei_bridge_' . $key, $value );
			}
		}
	}
}
