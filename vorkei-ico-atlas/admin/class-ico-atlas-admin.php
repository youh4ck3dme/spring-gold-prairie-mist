<?php
/**
 * Admin Panel Manager for VORKEI IČO ATLAS.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Vorkei_Ico_Atlas_Admin {

	private static ?Vorkei_Ico_Atlas_Admin $instance = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function register_menu(): void {
		add_menu_page(
			__( 'VORKEI IČO ATLAS', 'vorkei-ico-atlas' ),
			__( 'IČO ATLAS', 'vorkei-ico-atlas' ),
			'manage_options',
			'vorkei-ico-atlas',
			[ $this, 'render_dashboard' ],
			'dashicons-building',
			58
		);
	}

	public function register_settings(): void {
		register_setting( 'vorkei_ico_settings', 'vorkei_ico_api_url', [
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => 'https://ico-api.vorkei.com/api/company/proxy-search?ico=',
		] );

		register_setting( 'vorkei_ico_settings', 'vorkei_ico_auto_enrich', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'yes',
		] );
	}

	public function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_vorkei-ico-atlas' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'vorkei-ico-atlas-admin',
			VORKEI_ICO_ATLAS_URL . 'admin/assets/admin.css',
			[],
			VORKEI_ICO_ATLAS_VERSION
		);

		wp_enqueue_script(
			'vorkei-ico-atlas-admin',
			VORKEI_ICO_ATLAS_URL . 'admin/assets/admin.js',
			[ 'jquery' ],
			VORKEI_ICO_ATLAS_VERSION,
			true
		);

		wp_localize_script(
			'vorkei-ico-atlas-admin',
			'vorkeiIcoAtlas',
			[
				'root'  => esc_url_raw( rest_url() ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			]
		);
	}

	public function render_dashboard(): void {
		$enricher = new Vorkei_Ico_Atlas_Enricher();
		$stats    = $enricher->get_audit_stats();
		$filter   = sanitize_text_field( $_GET['filter'] ?? 'all' );
		$entities = $enricher->get_entities( $filter, 50 );

		require_once VORKEI_ICO_ATLAS_PATH . 'admin/views/dashboard.php';
	}
}
