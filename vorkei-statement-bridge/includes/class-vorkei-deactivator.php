<?php
/**
 * Fired during plugin deactivation.
 *
 * @package    Vorkei_Statement_Bridge
 * @subpackage Vorkei_Statement_Bridge/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Vorkei_Deactivator {

	/**
	 * Clean up temporary transients and flush rewrite rules.
	 */
	public static function deactivate(): void {
		delete_transient( 'vorkei_bridge_sync_cache' );
		flush_rewrite_rules();
	}
}
