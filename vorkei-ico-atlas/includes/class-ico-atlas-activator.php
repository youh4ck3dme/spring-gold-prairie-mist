<?php
/**
 * Plugin Activator for VORKEI IČO ATLAS.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Vorkei_Ico_Atlas_Activator {

	public static function maybe_upgrade(): void {
		if ( get_option( 'vorkei_ico_schema_version' ) !== '2.1' || get_option( 'vorkei_ico_atlas_version' ) !== '2.2.0' ) {
			self::activate();
		}
	}

	public static function activate(): void {
		self::create_entities_table();
		delete_transient( 'vorkei_cct_schema_checked' );
		self::ensure_cct_columns( true );
		self::set_default_options();
		global $wpdb;
		$cols = (array) $wpdb->get_col( 'DESCRIBE ' . $wpdb->prefix . 'vorkei_ico_entities' );
		if ( in_array( 'profile_json', $cols, true ) || in_array( 'last_revenue', $cols, true ) ) {
			update_option( 'vorkei_ico_schema_version', '2.1' );
			update_option( 'vorkei_ico_atlas_version', '2.2.0' );
		}
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
            last_revenue DECIMAL(15,2) DEFAULT NULL,
            last_profit DECIMAL(15,2) DEFAULT NULL,
            last_equity DECIMAL(15,2) DEFAULT NULL,
            financials_year SMALLINT DEFAULT NULL,
            tax_reliability VARCHAR(64) NULL,
            iban_checked_at DATETIME NULL,
            verification_status VARCHAR(40) NULL,
            last_attempt_at DATETIME NULL,
            last_success_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uq_ico (ico),
			KEY idx_active (is_active),
			KEY idx_risky (is_risky),
			KEY idx_verified (verified_at),
			KEY idx_last_revenue (last_revenue),
			KEY idx_last_profit (last_profit)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public static function ensure_cct_columns(bool $force=false): void {
		if (!$force && get_transient('vorkei_cct_schema_checked')) {
			return;
		}

		global $wpdb;

		$tables = [
			$wpdb->prefix . 'jet_cct_customers',
			$wpdb->prefix . 'jet_cct_contractors',
			$wpdb->prefix . 'jet_cct_clients',
		];

		$columns = [
			'ico'             => 'VARCHAR(16) NULL',
			'legal_form'      => 'VARCHAR(128) NULL',
			'address'         => 'TEXT NULL',
			'company_status'  => 'VARCHAR(64) NULL',
			'is_active'       => 'TINYINT(1) DEFAULT 0',
			'is_risky'        => 'TINYINT(1) DEFAULT 0',
			'risk_reason'     => 'TEXT NULL',
			'verified_at'     => 'DATETIME NULL',
			'last_revenue'    => 'DECIMAL(15,2) DEFAULT NULL',
			'last_profit'     => 'DECIMAL(15,2) DEFAULT NULL',
			'last_equity'     => 'DECIMAL(15,2) DEFAULT NULL',
			'financials_year' => 'SMALLINT DEFAULT NULL',
			'tax_reliability' => 'VARCHAR(64) NULL',
			'iban_checked_at' => 'DATETIME DEFAULT NULL',
		];

		foreach ( $tables as $table ) {
			$escaped_table = $wpdb->esc_like( $table );
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $escaped_table ) ) !== $table ) {
				continue;
			}

			$existing_cols = (array) $wpdb->get_col( "DESCRIBE `{$table}`" );

			foreach ( $columns as $col => $def ) {
				if ( ! in_array( $col, $existing_cols, true ) ) {
					$wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$def}" );
				}
			}

			$indexes = [
				'idx_last_revenue' => 'last_revenue',
				'idx_last_profit'  => 'last_profit',
			];
			foreach ( $indexes as $idx_name => $idx_col ) {
				$idx_exists = $wpdb->get_var( $wpdb->prepare(
					"SHOW INDEX FROM `{$table}` WHERE Key_name = %s",
					$idx_name
				) );
				if ( empty( $idx_exists ) ) {
					$wpdb->query( "ALTER TABLE `{$table}` ADD INDEX `{$idx_name}` (`{$idx_col}`)" );
				}
			}
		}

		set_transient( 'vorkei_cct_schema_checked', 1, 86400 );
	}
}
