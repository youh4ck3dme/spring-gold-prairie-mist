<?php
/**
 * Plugin Name:       VORKEI IČO ATLAS – Register SR & Risk Radar
 * Plugin URI:        https://vorkei.com/ico-atlas
 * Description:       Doložiteľné firemné profily, priamy konektor RÚZ, rozšírený kontrakt registračného API, JetEngine CCT a Vorkei Bridge.
 * Version:           2.2.0
 * Requires at least: 6.2
 * Requires PHP:      8.1
 * Author:            vorkei
 * Author URI:        https://vorkei.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       vorkei-ico-atlas
 * Domain Path:       /languages
 *
 * @package           Vorkei_Ico_Atlas
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VORKEI_ICO_ATLAS_VERSION', '2.2.0' );
define( 'VORKEI_ICO_ATLAS_PATH', plugin_dir_path( __FILE__ ) );
define( 'VORKEI_ICO_ATLAS_URL', plugin_dir_url( __FILE__ ) );
define( 'VORKEI_ICO_ATLAS_BASENAME', plugin_basename( __FILE__ ) );

// Core includes
require_once VORKEI_ICO_ATLAS_PATH . 'includes/class-ico-atlas-registers.php';
require_once VORKEI_ICO_ATLAS_PATH . 'includes/class-ico-atlas-tax.php';
require_once VORKEI_ICO_ATLAS_PATH . 'includes/class-ico-atlas-financials.php';
require_once VORKEI_ICO_ATLAS_PATH . 'includes/class-ico-atlas-profile.php';
require_once VORKEI_ICO_ATLAS_PATH . 'includes/class-ico-atlas-ruz.php';
require_once VORKEI_ICO_ATLAS_PATH . 'includes/class-ico-atlas-activator.php';
require_once VORKEI_ICO_ATLAS_PATH . 'includes/class-ico-atlas-client.php';
require_once VORKEI_ICO_ATLAS_PATH . 'includes/class-ico-atlas-enricher.php';
require_once VORKEI_ICO_ATLAS_PATH . 'includes/class-ico-atlas-rest-controller.php';

if ( is_admin() ) {
	require_once VORKEI_ICO_ATLAS_PATH . 'admin/class-ico-atlas-admin.php';
}

register_activation_hook( __FILE__, [ 'Vorkei_Ico_Atlas_Activator', 'activate' ] );

final class Vorkei_Ico_Atlas {

	private static ?Vorkei_Ico_Atlas $instance = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action('vorkei_ico_refresh',[$this,'scheduled_refresh']);
        add_action('vorkei_ico_continue',[$this,'scheduled_refresh']);
        add_action( 'plugins_loaded', [ $this, 'init' ] );
		add_action( 'rest_api_init', [ $this, 'init_rest' ] );
		add_filter( 'plugin_action_links_' . VORKEI_ICO_ATLAS_BASENAME, [ $this, 'add_action_links' ] );

		// Hook into Vorkei Statement Bridge auto-sync
		add_action( 'vorkei_bridge_sync_completed', [ $this, 'on_bridge_sync' ], 10, 2 );
	}

	public function init(): void {
		Vorkei_Ico_Atlas_Activator::maybe_upgrade();
        if(!wp_next_scheduled('vorkei_ico_refresh')) {wp_schedule_event(time()+300,'hourly','vorkei_ico_refresh');}
		load_plugin_textdomain( 'vorkei-ico-atlas', false, dirname( VORKEI_ICO_ATLAS_BASENAME ) . '/languages' );

		if ( is_admin() ) {
			Vorkei_Ico_Atlas_Admin::get_instance();
		}
	}

	public function init_rest(): void {
		$controller = new Vorkei_Ico_Atlas_REST_Controller();
		$controller->register_routes();
	}

	public function add_action_links( array $links ): array {
		$dashboard_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=vorkei-ico-atlas' ) ),
			esc_html__( 'Audit Dashboard', 'vorkei-ico-atlas' )
		);
		array_unshift( $links, $dashboard_link );
		return $links;
	}

	/**
	 * Callback upon statement sync from Vorkei Bridge.
	 *
	 * @param array $stats
	 * @param array $statements
	 */
    public function scheduled_refresh(): void {
        if(get_option('vorkei_ico_auto_enrich','yes')!=='yes') {return;}
        $this->on_bridge_sync([],[]);
    }

	public function on_bridge_sync( array $stats, array $statements ): void {
		$auto_enrich = get_option( 'vorkei_ico_auto_enrich', 'yes' );
		if ( 'yes' !== $auto_enrich ) {
			return;
		}

		$enricher = new Vorkei_Ico_Atlas_Enricher();
		$job=$enricher->enrich_unverified_entities(1,(string)get_option('vorkei_ico_background_job',''));
        update_option('vorkei_ico_background_job',$job['done']?'':$job['job_id']);
        if(!$job['done'] && !wp_next_scheduled('vorkei_ico_continue')) {wp_schedule_single_event(time()+60,'vorkei_ico_continue');}
	}
}

Vorkei_Ico_Atlas::get_instance();

register_deactivation_hook(__FILE__, static function() {wp_clear_scheduled_hook('vorkei_ico_refresh');wp_clear_scheduled_hook('vorkei_ico_continue');});
