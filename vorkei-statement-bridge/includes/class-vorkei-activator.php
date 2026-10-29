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
	const DB_VERSION = '1.0.0';

	/**
	 * Run migration and create plugin tables.
	 */
	public static function activate(): void {
		self::install_tables();
		self::set_default_options();
	}

	/**
	 * Auto-check on runtime to handle updates without deactivation.
	 */
	public static function check_and_upgrade(): void {
		if ( ! function_exists( 'get_option' ) ) {
			return;
		}
		$installed_ver = get_option( 'vorkei_bridge_db_version', '0.0.0' );
		if ( version_compare( $installed_ver, self::DB_VERSION, '<' ) ) {
			self::install_tables();
			self::set_default_options();
		}
	}

	/**
	 * Create or upgrade custom database tables using dbDelta.
	 */
	public static function install_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		if ( file_exists( ABSPATH . 'wp-admin/includes/upgrade.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$statements_table = $wpdb->prefix . 'vorkei_statements';
		$movements_table  = $wpdb->prefix . 'vorkei_movements';

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

		dbDelta( $sql_statements );
		dbDelta( $sql_movements );

		update_option( 'vorkei_bridge_db_version', self::DB_VERSION );
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
		];

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( 'vorkei_bridge_' . $key ) ) {
				update_option( 'vorkei_bridge_' . $key, $value );
			}
		}
	}
}
