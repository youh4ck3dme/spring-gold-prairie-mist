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
	 * Get list of entity tables (standalone entities table + any active JetEngine CCT tables).
	 */
	public function get_target_tables(): array {
		global $wpdb;
		$tables = [];

		$entities_table = $wpdb->prefix . 'vorkei_ico_entities';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $entities_table ) ) === $entities_table ) {
			$tables['entities'] = $entities_table;
		}

		$cct = [
			'customers'   => $wpdb->prefix . 'jet_cct_customers',
			'contractors' => $wpdb->prefix . 'jet_cct_contractors',
			'clients'     => $wpdb->prefix . 'jet_cct_clients',
		];

		foreach ( $cct as $key => $table_name ) {
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ) {
				$tables[ $key ] = $table_name;
			}
		}

		return $tables;
	}

	/**
	 * Persist a lookup result into the standalone entities table.
	 */
	public function save_lookup_entity( array $lookup ): void {
		global $wpdb;
		if ( empty( $lookup['found'] ) || empty( $lookup['ico'] ) ) {
			return;
		}

		$table = $wpdb->prefix . 'vorkei_ico_entities';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return;
		}

		$data = [
			'ico'            => $lookup['ico'],
			'name'           => $lookup['name'],
			'legal_form'     => $lookup['legal_form'],
			'address'        => $lookup['address'],
			'company_status' => $lookup['status'],
			'is_active'      => ! empty( $lookup['active'] ) ? 1 : 0,
			'is_risky'       => ! empty( $lookup['is_risky'] ) ? 1 : 0,
			'risk_reason'    => $lookup['risk_reason'] ?? null,
			'verified_at'    => current_time( 'mysql' ),
		];

		$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE ico = %s", $lookup['ico'] ) );
		if ( $existing_id ) {
			$wpdb->update( $table, $data, [ 'id' => (int) $existing_id ] );
		} else {
			$wpdb->insert( $table, $data );
		}
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

		$id_col = ( 'entities' === $table_key ) ? 'id' : '_ID';
		$current_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$table} WHERE {$id_col} = %d", $id ) );
		if ( empty( $current_name ) && ! empty( $lookup['name'] ) ) {
			$update_data['name'] = $lookup['name'];
		}

		$wpdb->update( $table, $update_data, [ $id_col => $id ] );

		// Also mirror into standalone entities table if updating a CCT
		if ( 'entities' !== $table_key ) {
			$this->save_lookup_entity( $lookup );
		}

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

			$id_col = ( 'entities' === $key ) ? 'id' : '_ID';
			$rows   = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT {$id_col} AS entity_id, ico, name FROM {$table} WHERE ico IS NOT NULL AND ico != '' AND (verified_at IS NULL OR verified_at = '0000-00-00 00:00:00') LIMIT %d",
					$limit - $stats['processed']
				),
				ARRAY_A
			);

			if ( ! is_array( $rows ) ) {
				continue;
			}

			foreach ( $rows as $row ) {
				$res = $this->enrich_entity( $key, (int) $row['entity_id'], $row['ico'] );
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

		foreach ( $tables as $key => $table ) {
			$total_entities  += (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
			$verified_active += (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE verified_at IS NOT NULL AND is_active = 1 AND is_risky = 0" );
			$verified_risky  += (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_risky = 1" );
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
	 * Get unified entity list across tables for audit table.
	 */
	public function get_entities( string $filter = 'all', int $limit = 50, int $offset = 0 ): array {
		global $wpdb;
		$tables = $this->get_target_tables();
		$items  = [];

		foreach ( $tables as $type => $table ) {
			$where = '1=1';
			if ( 'risky' === $filter ) {
				$where = 'is_risky = 1';
			} elseif ( 'active' === $filter ) {
				$where = 'verified_at IS NOT NULL AND is_active = 1 AND is_risky = 0';
			} elseif ( 'unverified' === $filter ) {
				$where = 'verified_at IS NULL OR verified_at = "0000-00-00 00:00:00"';
			}

			$id_col  = ( 'entities' === $type ) ? 'id' : '_ID';
			$results = $wpdb->get_results(
				"SELECT {$id_col} AS _ID, name, ico, legal_form, address, company_status, is_active, is_risky, risk_reason, verified_at 
				 FROM {$table} WHERE {$where} ORDER BY {$id_col} DESC LIMIT {$limit}",
				ARRAY_A
			);

			if ( is_array( $results ) ) {
				foreach ( $results as $r ) {
					$r['entity_type'] = $type;
					$items[]          = $r;
				}
			}
		}

		return $items;
	}
}
