<?php
/**
 * Auto-Enricher and Batch Audit Engine for VORKEI IČO ATLAS.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Vorkei_Ico_Atlas_Enricher {

	private Vorkei_Ico_Atlas_Client $client;

	public function __construct() {
		$this->client = new Vorkei_Ico_Atlas_Client();
	}

	/**
	 * Get list of JetEngine CCT entity tables.
	 */
	public function get_target_tables(): array {
		global $wpdb;
		return [
			'customers'   => $wpdb->prefix . 'jet_cct_customers',
			'contractors' => $wpdb->prefix . 'jet_cct_contractors',
			'clients'     => $wpdb->prefix . 'jet_cct_clients',
		];
	}

	/**
	 * Enrich a specific entity record.
	 */
	public function enrich_entity( string $table_key, int $id, string $ico ): array {
		global $wpdb;
		$tables = $this->get_target_tables();
		$table  = $tables[ $table_key ] ?? null;

		if ( ! $table ) {
			return [ 'success' => false, 'error' => 'Neznáma tabuľka' ];
		}

		$lookup = $this->client->lookup( $ico );
		if ( empty( $lookup['found'] ) ) {
			return [
				'success' => false,
				'error'   => $lookup['error'] ?? 'Subjekt nenájdený',
			];
		}

		$update_data = [
			'ico'            => $lookup['ico'],
			'legal_form'     => $lookup['legal_form'],
			'address'        => $lookup['address'],
			'company_status' => $lookup['status'],
			'is_active'      => $lookup['active'] ? 1 : 0,
			'is_risky'       => $lookup['is_risky'] ? 1 : 0,
			'risk_reason'    => $lookup['risk_reason'],
			'verified_at'    => current_time( 'mysql' ),
		];

		// Optionally update official name if current name was empty or generic
		$current_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$table} WHERE _ID = %d", $id ) );
		if ( empty( $current_name ) && ! empty( $lookup['name'] ) ) {
			$update_data['name'] = $lookup['name'];
		}

		$wpdb->update( $table, $update_data, [ '_ID' => $id ] );

		return [
			'success' => true,
			'data'    => $lookup,
		];
	}

	/**
	 * Batch enrich unverified entities.
	 */
	public function enrich_unverified_entities( int $limit = 20 ): array {
		global $wpdb;
		$tables = $this->get_target_tables();
		$stats  = [
			'processed' => 0,
			'verified'  => 0,
			'risky'     => 0,
			'failed'    => 0,
		];

		foreach ( $tables as $key => $table ) {
			if ( $stats['processed'] >= $limit ) {
				break;
			}

			$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) === $table;
			if ( ! $table_exists ) {
				continue;
			}

			// Find entities with an IČO that haven't been verified yet
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT _ID, ico, name FROM {$table} WHERE ico IS NOT NULL AND ico != '' AND (verified_at IS NULL OR verified_at = '0000-00-00 00:00:00') LIMIT %d",
					$limit - $stats['processed']
				),
				ARRAY_A
			);

			foreach ( $rows as $row ) {
				$res = $this->enrich_entity( $key, (int) $row['_ID'], $row['ico'] );
				$stats['processed']++;
				if ( ! empty( $res['success'] ) ) {
					$stats['verified']++;
					if ( ! empty( $res['data']['is_risky'] ) ) {
						$stats['risky']++;
					}
				} else {
					$stats['failed']++;
				}
			}
		}

		return $stats;
	}

	/**
	 * Fetch overall audit statistics for admin dashboard.
	 */
	public function get_audit_stats(): array {
		global $wpdb;
		$tables = $this->get_target_tables();

		$total_entities    = 0;
		$verified_active   = 0;
		$verified_risky    = 0;
		$unverified_or_raw = 0;

		foreach ( $tables as $key => $table ) {
			$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) === $table;
			if ( ! $table_exists ) {
				continue;
			}

			$total_entities += (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
			$verified_active += (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE verified_at IS NOT NULL AND is_active = 1 AND is_risky = 0" );
			$verified_risky += (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_risky = 1" );
		}

		$unverified_or_raw = max( 0, $total_entities - $verified_active - $verified_risky );

		return [
			'total'      => $total_entities,
			'active'     => $verified_active,
			'risky'      => $verified_risky,
			'unverified' => $unverified_or_raw,
		];
	}

	/**
	 * Get unified entity list across CCTs for audit table.
	 */
	public function get_entities( string $filter = 'all', int $limit = 50, int $offset = 0 ): array {
		global $wpdb;
		$tables = $this->get_target_tables();
		$items  = [];

		foreach ( $tables as $type => $table ) {
			$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) === $table;
			if ( ! $table_exists ) {
				continue;
			}

			$where = '1=1';
			if ( 'risky' === $filter ) {
				$where = 'is_risky = 1';
			} elseif ( 'active' === $filter ) {
				$where = 'verified_at IS NOT NULL AND is_active = 1 AND is_risky = 0';
			} elseif ( 'unverified' === $filter ) {
				$where = 'verified_at IS NULL OR verified_at = "0000-00-00 00:00:00"';
			}

			$results = $wpdb->get_results(
				"SELECT _ID, name, iban, swift, ico, legal_form, address, company_status, is_active, is_risky, risk_reason, verified_at 
				 FROM {$table} WHERE {$where} ORDER BY _ID DESC LIMIT {$limit}",
				ARRAY_A
			);

			foreach ( $results as $r ) {
				$r['entity_type'] = $type;
				$items[]          = $r;
			}
		}

		return $items;
	}
}
