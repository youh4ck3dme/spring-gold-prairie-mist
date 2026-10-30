<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Vorkei_Statement_Bridge
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check if user decided to purge data upon deletion.
$delete_data = get_option( 'vorkei_bridge_delete_on_uninstall', 'no' );

if ( 'yes' === $delete_data ) {
	global $wpdb;

	$statements_table = $wpdb->prefix . 'vorkei_statements';
	$movements_table  = $wpdb->prefix . 'vorkei_movements';
	$audit_log_table  = $wpdb->prefix . 'vorkei_audit_log';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS $audit_log_table" );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS $movements_table" );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS $statements_table" );

	// Delete all registered options.
	delete_option( 'vorkei_bridge_db_version' );
	delete_option( 'vorkei_bridge_enable_wc_matching' );
	delete_option( 'vorkei_bridge_wc_target_status' );
	delete_option( 'vorkei_bridge_verify_amount' );
	delete_option( 'vorkei_bridge_add_order_note' );
	delete_option( 'vorkei_bridge_api_token' );
	delete_option( 'vorkei_bridge_delete_on_uninstall' );
	delete_option( 'vorkei_bridge_fee_base_amount' );
	delete_option( 'vorkei_bridge_fee_movement_rate' );
	delete_option( 'vorkei_bridge_fee_volume_percent' );
	delete_option( 'vorkei_bridge_auto_lock_exports' );
	delete_transient( 'vorkei_bridge_sync_cache' );
}
