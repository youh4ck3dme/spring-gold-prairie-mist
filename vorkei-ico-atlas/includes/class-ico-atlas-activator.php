<?php
/**
 * Plugin Activator for VORKEI IČO ATLAS.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Vorkei_Ico_Atlas_Activator {

	public static function maybe_upgrade(): void {
		if (get_option('vorkei_ico_schema_version') !== '2.1') { self::activate(); }
	}

	public static function activate(): void {
		self::create_entities_table();
		self::ensure_cct_columns();
		self::set_default_options();
		global $wpdb;
        $cols=(array)$wpdb->get_col('DESCRIBE '.$wpdb->prefix.'vorkei_ico_entities');
        if(in_array('profile_json',$cols,true)) {update_option('vorkei_ico_schema_version','2.1');}
	}

	public static function set_default_options(): void {
		if ( false === get_option( 'vorkei_ico_api_url' ) ) {
			update_option( 'vorkei_ico_api_url', 'https://ico-api.vorkei.com/api/company/proxy-search?ico=' );
		}
		if ( false === get_option( 'vorkei_ico_auto_enrich' ) ) {
			update_option( 'vorkei_ico_auto_enrich', 'yes' );
		}
		if ( false === get_option( 'vorkei_ico_cache_ttl' ) ) {
			update_option( 'vorkei_ico_cache_ttl', 86400 ); // 24 hours
		}
	}

	public static function create_entities_table(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'vorkei_ico_entities';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			ico VARCHAR(16) NOT NULL,
			name VARCHAR(255) NULL,
			legal_form VARCHAR(128) NULL,
			address TEXT NULL,
			company_status VARCHAR(64) NULL,
			is_active TINYINT(1) DEFAULT 0,
			is_risky TINYINT(1) DEFAULT 0,
			risk_reason TEXT NULL,
			verified_at DATETIME NULL,
			profile_json LONGTEXT NULL,
            founded_on DATE NULL,
            dissolved_on DATE NULL,
            revenue DECIMAL(14,2) NULL,
            profit DECIMAL(14,2) NULL,
            equity DECIMAL(14,2) NULL,
            tax_reliability VARCHAR(64) NULL,
            verification_status VARCHAR(40) NULL,
            last_attempt_at DATETIME NULL,
            last_success_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uq_ico (ico),
			KEY idx_active (is_active),
			KEY idx_risky (is_risky),
			KEY idx_verified (verified_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public static function ensure_cct_columns(): void {
		global $wpdb;

		$tables = [
			$wpdb->prefix . 'jet_cct_customers',
			$wpdb->prefix . 'jet_cct_contractors',
			$wpdb->prefix . 'jet_cct_clients',
		];

		$columns_to_add = [
			'ico'             => 'VARCHAR(16) NULL',
			'legal_form'      => 'VARCHAR(128) NULL',
			'address'         => 'TEXT NULL',
			'company_status'  => 'VARCHAR(64) NULL',
			'is_active'       => 'TINYINT(1) DEFAULT 0',
			'is_risky'        => 'TINYINT(1) DEFAULT 0',
			'risk_reason'     => 'TEXT NULL',
			'verified_at'     => 'DATETIME NULL',
			'revenue'         => 'DECIMAL(14,2) NULL',
			'profit'          => 'DECIMAL(14,2) NULL',
			'equity'          => 'DECIMAL(14,2) NULL',
			'tax_reliability' => 'VARCHAR(64) NULL',
		];

		foreach ( $tables as $table ) {
			// Check if table exists
			$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) === $table;
			if ( ! $table_exists ) {
				continue;
			}

			// Get existing columns
			$existing_columns = (array) $wpdb->get_col( "DESCRIBE {$table}" );

			foreach ( $columns_to_add as $col_name => $col_definition ) {
				if ( ! in_array( $col_name, $existing_columns, true ) ) {
					$wpdb->query( "ALTER TABLE {$table} ADD COLUMN {$col_name} {$col_definition}" );
				}
			}
		}
	}
}
