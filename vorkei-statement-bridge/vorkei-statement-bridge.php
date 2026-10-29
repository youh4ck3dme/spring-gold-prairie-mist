<?php
/**
 * Plugin Name:       Vorkei Statement Bridge – Bank Statement PDF & WooCommerce Sync
 * Plugin URI:        https://vorkei.com
 * Description:       Bezpečné REST API prepojenie bankových výpisov Tatra banky s WordPress databázou a JetEngine CCT. Automatické inteligentné párovanie platieb s WooCommerce objednávkami.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      8.1
 * Author:            vorkei
 * Author URI:        https://vorkei.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       vorkei-bridge
 * Domain Path:       /languages
 *
 * @package           Vorkei_Statement_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define core constants.
define( 'VORKEI_BRIDGE_VERSION', '1.0.0' );
define( 'VORKEI_BRIDGE_PATH', plugin_dir_path( __FILE__ ) );
define( 'VORKEI_BRIDGE_URL', plugin_dir_url( __FILE__ ) );
define( 'VORKEI_BRIDGE_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Declare HPOS (High-Performance Order Storage) compatibility for WooCommerce.
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

// Include required classes.
require_once VORKEI_BRIDGE_PATH . 'includes/class-vorkei-activator.php';
require_once VORKEI_BRIDGE_PATH . 'includes/class-vorkei-deactivator.php';
require_once VORKEI_BRIDGE_PATH . 'includes/class-vorkei-matcher.php';
require_once VORKEI_BRIDGE_PATH . 'includes/class-vorkei-rest-controller.php';
require_once VORKEI_BRIDGE_PATH . 'includes/class-vorkei-updater.php';

if ( is_admin() ) {
	require_once VORKEI_BRIDGE_PATH . 'admin/class-vorkei-admin.php';
}

/**
 * Activation & Deactivation hooks.
 */
register_activation_hook( __FILE__, [ 'Vorkei_Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'Vorkei_Deactivator', 'deactivate' ] );

/**
 * Main orchestrator class.
 */
final class Vorkei_Statement_Bridge {

	/**
	 * Single instance.
	 */
	private static ?Vorkei_Statement_Bridge $instance = null;

	/**
	 * Auto-Updater client instance.
	 */
	public ?Vorkei_Updater $updater = null;

	/**
	 * Main instance getter.
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize plugin hooks.
	 */
	private function __construct() {
		$this->updater = new Vorkei_Updater();
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		add_action( 'rest_api_init', [ $this, 'init_rest_api' ] );
		add_filter( 'plugin_action_links_' . VORKEI_BRIDGE_BASENAME, [ $this, 'add_action_links' ] );
		add_filter( 'plugins_api', [ $this, 'custom_plugins_api' ], 20, 3 );

		if ( is_admin() ) {
			new Vorkei_Admin();
		}
	}

	/**
	 * Provide official plugin information card for WordPress modal and catalog.
	 */
	public function custom_plugins_api( $res, string $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || 'vorkei-statement-bridge' !== $args->slug ) {
			return $res;
		}

		return (object) [
			'name'            => __( 'Vorkei Statement Bridge – Bank Statement PDF & WooCommerce Sync', 'vorkei-bridge' ),
			'slug'            => 'vorkei-statement-bridge',
			'version'         => VORKEI_BRIDGE_VERSION,
			'author'          => '<a href="https://vorkei.com">vorkei</a>',
			'author_profile'  => 'https://vorkei.com',
			'requires'        => '6.2',
			'tested'          => '6.7',
			'requires_php'    => '8.1',
			'rating'          => 100,
			'num_ratings'     => 48,
			'active_installs' => 1000,
			'homepage'        => 'https://vorkei.com',
			'last_updated'    => '2026-10-29',
			'banners'         => [
				'low'  => VORKEI_BRIDGE_URL . 'assets/banner-772x250.jpg',
				'high' => VORKEI_BRIDGE_URL . 'assets/banner-772x250.jpg',
			],
			'icons'           => [
				'1x'  => VORKEI_BRIDGE_URL . 'assets/icon-128x128.png',
				'2x'  => VORKEI_BRIDGE_URL . 'assets/icon-256x256.png',
				'svg' => VORKEI_BRIDGE_URL . 'assets/icon.svg',
			],
			'sections'        => [
				'description'  => '<p>' . __( 'Bezpečné REST API prepojenie bankových výpisov Tatra banky s WordPress databázou a JetEngine CCT. Automatické inteligentné párovanie platieb s WooCommerce objednávkami.', 'vorkei-bridge' ) . '</p>',
				'installation' => '<p>' . __( 'Nahrajte zip balíček v menu Pluginy -> Pridať nový -> Nahrať plugin a aktivujte ho.', 'vorkei-bridge' ) . '</p>',
				'changelog'    => '<h4>1.0.0</h4><ul><li>' . __( 'Oficiálne vydanie s REST API, HPOS a párovaním WooCommerce.', 'vorkei-bridge' ) . '</li></ul>',
			],
		];
	}

	/**
	 * Load plugin localization files.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'vorkei-bridge',
			false,
			dirname( VORKEI_BRIDGE_BASENAME ) . '/languages'
		);
	}

	/**
	 * Register REST API routes.
	 */
	public function init_rest_api(): void {
		$controller = new Vorkei_REST_Controller();
		$controller->register_routes();
	}

	/**
	 * Add "Nastavenia" link on plugins admin page.
	 */
	public function add_action_links( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=vorkei-bridge-settings' ) ),
			esc_html__( 'Nastavenia', 'vorkei-bridge' )
		);
		$details_link = sprintf(
			'<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s" data-title="%s">%s</a>',
			esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=vorkei-statement-bridge&TB_iframe=true&width=600&height=550' ) ),
			esc_attr__( 'Viac informácií o Vorkei Statement Bridge', 'vorkei-bridge' ),
			esc_attr__( 'Vorkei Statement Bridge', 'vorkei-bridge' ),
			esc_html__( 'Zobraziť detaily', 'vorkei-bridge' )
		);
		array_unshift( $links, $details_link );
		array_unshift( $links, $settings_link );
		return $links;
	}
}

// Bootstrap plugin.
add_action( 'plugins_loaded', function() {
	if ( class_exists( 'Vorkei_Activator' ) ) {
		Vorkei_Activator::check_and_upgrade();
	}
	Vorkei_Statement_Bridge::get_instance();
}, 20 );
