<?php
/**
 * Auto-Updater client for Vorkei Statement Bridge.
 * Connects with api.vorkei.com/plugins/update-check
 *
 * @package    Vorkei_Statement_Bridge
 * @subpackage Vorkei_Statement_Bridge/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Vorkei_Updater {

	/**
	 * Plugin version.
	 */
	protected string $version;

	/**
	 * Plugin slug.
	 */
	protected string $slug = 'vorkei-statement-bridge';

	/**
	 * Plugin basename.
	 */
	protected string $plugin_basename;

	/**
	 * Remote update API endpoint URL.
	 */
	protected string $update_endpoint;

	/**
	 * Cache key for transient.
	 */
	protected string $cache_key = 'vorkei_bridge_update_info';

	/**
	 * Cache TTL in seconds (12 hours).
	 */
	protected int $cache_ttl = 43200;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->version         = defined( 'VORKEI_BRIDGE_VERSION' ) ? VORKEI_BRIDGE_VERSION : '1.0.0';
		$this->plugin_basename = defined( 'VORKEI_BRIDGE_BASENAME' ) ? VORKEI_BRIDGE_BASENAME : 'vorkei-statement-bridge/vorkei-statement-bridge.php';
		
		$custom_endpoint = function_exists( 'get_option' ) ? get_option( 'vorkei_bridge_update_url', '' ) : '';
		if ( ! empty( $custom_endpoint ) ) {
			$this->update_endpoint = function_exists( 'esc_url_raw' ) ? esc_url_raw( $custom_endpoint ) : (string) $custom_endpoint;
		} else {
			$this->update_endpoint = 'https://pdf-t3.vorkei.com/wp-json/vorkei/v1/update-check';
		}

		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );
		add_filter( 'plugins_api', [ $this, 'plugins_api_info' ], 20, 3 );
		add_action( 'upgrader_process_complete', [ $this, 'purge_cache' ], 10, 2 );

		// Allow manual refresh if requested by admin
		if ( is_admin() && isset( $_GET['vorkei_force_update_check'] ) && function_exists( 'check_admin_referer' ) && check_admin_referer( 'vorkei_check_update_nonce' ) ) {
			add_action( 'admin_init', [ $this, 'force_check_now' ] );
		}
	}

	/**
	 * Get update endpoint URL.
	 */
	public function get_update_endpoint(): string {
		return $this->update_endpoint;
	}

	/**
	 * Hook into WordPress update transient to inject new version if available.
	 *
	 * @param object|bool $transient Transient data.
	 * @return object
	 */
	public function check_update( $transient ) {
		if ( empty( $transient ) || ! is_object( $transient ) ) {
			$transient = new stdClass();
		}

		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$remote = $this->fetch_remote_info();
		if ( ! $remote || ! is_object( $remote ) || empty( $remote->version ) ) {
			return $transient;
		}

		if ( version_compare( $this->version, $remote->version, '<' ) ) {
			$item = new stdClass();
			$item->id           = 'vorkei.com/' . $this->slug;
			$item->slug         = $this->slug;
			$item->plugin       = $this->plugin_basename;
			$item->new_version  = sanitize_text_field( $remote->version );
			$item->url          = ! empty( $remote->homepage ) ? esc_url( $remote->homepage ) : 'https://vorkei.com';
			$item->package      = ! empty( $remote->download_url ) ? esc_url_raw( $remote->download_url ) : '';
			$item->tested       = ! empty( $remote->tested ) ? sanitize_text_field( $remote->tested ) : '6.7';
			$item->requires_php = ! empty( $remote->requires_php ) ? sanitize_text_field( $remote->requires_php ) : '8.1';
			
			$plugin_url = defined( 'VORKEI_BRIDGE_URL' ) ? VORKEI_BRIDGE_URL : '';
			$item->icons = [
				'1x'      => ! empty( $remote->icons->{'1x'} ) ? esc_url( $remote->icons->{'1x'} ) : $plugin_url . 'assets/icon-128x128.png',
				'2x'      => ! empty( $remote->icons->{'2x'} ) ? esc_url( $remote->icons->{'2x'} ) : $plugin_url . 'assets/icon-256x256.png',
				'default' => ! empty( $remote->icons->default ) ? esc_url( $remote->icons->default ) : $plugin_url . 'assets/icon-256x256.png',
			];
			$item->banners = [
				'low'  => ! empty( $remote->banners->low ) ? esc_url( $remote->banners->low ) : $plugin_url . 'assets/banner-772x250.jpg',
				'high' => ! empty( $remote->banners->high ) ? esc_url( $remote->banners->high ) : $plugin_url . 'assets/banner-772x250.jpg',
			];

			$transient->response[ $this->plugin_basename ] = $item;
			if ( isset( $transient->no_update[ $this->plugin_basename ] ) ) {
				unset( $transient->no_update[ $this->plugin_basename ] );
			}
		} else {
			$no_update_item = new stdClass();
			$no_update_item->id          = 'vorkei.com/' . $this->slug;
			$no_update_item->slug        = $this->slug;
			$no_update_item->plugin      = $this->plugin_basename;
			$no_update_item->new_version = $this->version;
			$no_update_item->url         = 'https://vorkei.com';
			$no_update_item->package     = '';

			$transient->no_update[ $this->plugin_basename ] = $no_update_item;
			if ( isset( $transient->response[ $this->plugin_basename ] ) ) {
				unset( $transient->response[ $this->plugin_basename ] );
			}
		}

		return $transient;
	}

	/**
	 * Hook into plugins_api for view details modal popup.
	 *
	 * @param false|object|array $res
	 * @param string             $action
	 * @param object             $args
	 * @return object
	 */
	public function plugins_api_info( $res, string $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || $this->slug !== $args->slug ) {
			return $res;
		}

		$remote = $this->fetch_remote_info();
		if ( ! $remote || ! is_object( $remote ) ) {
			return $res;
		}

		$plugin_url = defined( 'VORKEI_BRIDGE_URL' ) ? VORKEI_BRIDGE_URL : '';

		$info = new stdClass();
		$info->name           = ! empty( $remote->name ) ? sanitize_text_field( $remote->name ) : 'Vorkei Statement Bridge';
		$info->slug           = $this->slug;
		$info->version        = ! empty( $remote->version ) ? sanitize_text_field( $remote->version ) : $this->version;
		$info->author         = ! empty( $remote->author ) ? wp_kses_post( $remote->author ) : '<a href="https://vorkei.com">vorkei</a>';
		$info->author_profile = ! empty( $remote->author_profile ) ? esc_url( $remote->author_profile ) : 'https://vorkei.com';
		$info->homepage       = ! empty( $remote->homepage ) ? esc_url( $remote->homepage ) : 'https://vorkei.com';
		$info->requires       = ! empty( $remote->requires ) ? sanitize_text_field( $remote->requires ) : '6.2';
		$info->tested         = ! empty( $remote->tested ) ? sanitize_text_field( $remote->tested ) : '6.7';
		$info->requires_php   = ! empty( $remote->requires_php ) ? sanitize_text_field( $remote->requires_php ) : '8.1';
		$info->download_link  = ! empty( $remote->download_url ) ? esc_url_raw( $remote->download_url ) : '';
		$info->trunk          = $info->download_link;
		$info->last_updated   = ! empty( $remote->last_updated ) ? sanitize_text_field( $remote->last_updated ) : date( 'Y-m-d' );
		$info->rating         = isset( $remote->rating ) ? (int) $remote->rating : 100;
		$info->num_ratings    = isset( $remote->num_ratings ) ? (int) $remote->num_ratings : 0;
		$info->active_installs= isset( $remote->active_installs ) ? (int) $remote->active_installs : 1;

		$info->banners = [
			'low'  => ! empty( $remote->banners->low ) ? esc_url( $remote->banners->low ) : $plugin_url . 'assets/banner-772x250.jpg',
			'high' => ! empty( $remote->banners->high ) ? esc_url( $remote->banners->high ) : $plugin_url . 'assets/banner-772x250.jpg',
		];
		$info->icons = [
			'1x'      => ! empty( $remote->icons->{'1x'} ) ? esc_url( $remote->icons->{'1x'} ) : $plugin_url . 'assets/icon-128x128.png',
			'2x'      => ! empty( $remote->icons->{'2x'} ) ? esc_url( $remote->icons->{'2x'} ) : $plugin_url . 'assets/icon-256x256.png',
			'svg'     => $plugin_url . 'assets/icon.svg',
			'default' => $plugin_url . 'assets/icon-256x256.png',
		];

		$info->sections = [
			'description'  => ! empty( $remote->sections->description ) ? wp_kses_post( $remote->sections->description ) : '<p>' . esc_html__( 'Bezpečné REST API prepojenie bankových výpisov Tatra banky s WordPress databázou a JetEngine CCT. Automatické inteligentné párovanie platieb s WooCommerce objednávkami.', 'vorkei-bridge' ) . '</p>',
			'installation' => ! empty( $remote->sections->installation ) ? wp_kses_post( $remote->sections->installation ) : '<p>' . esc_html__( 'Kliknite na Aktualizovať teraz priamo v zozname pluginov WordPress.', 'vorkei-bridge' ) . '</p>',
			'changelog'    => ! empty( $remote->sections->changelog ) ? wp_kses_post( $remote->sections->changelog ) : '<h4>' . esc_html( $info->version ) . '</h4><ul><li>' . esc_html__( 'Pravidelné vylepšenia stability, bezpečnosti a párovania.', 'vorkei-bridge' ) . '</li></ul>',
		];

		return $info;
	}

	/**
	 * Fetch remote update info with caching.
	 *
	 * @param bool $force Force fetch ignoring cache.
	 * @return object|false
	 */
	public function fetch_remote_info( bool $force = false ) {
		if ( ! $force ) {
			$cached = get_transient( $this->cache_key );
			if ( false !== $cached && is_object( $cached ) ) {
				return $cached;
			}
		}

		$site_url = function_exists( 'home_url' ) ? home_url() : '';
		$license_key = get_option( 'vorkei_bridge_license_key', '' );

		$args = [
			'timeout'    => 10,
			'user-agent' => 'WordPress/' . ( function_exists( 'get_bloginfo' ) ? get_bloginfo( 'version' ) : '6.7' ) . '; ' . $site_url,
			'headers'    => [
				'Accept' => 'application/json',
			],
			'body'       => [
				'slug'        => $this->slug,
				'version'     => $this->version,
				'site_url'    => $site_url,
				'php_version' => PHP_VERSION,
				'license_key' => $license_key,
			],
		];

		if ( function_exists( 'wp_remote_post' ) ) {
			$response = wp_remote_post( $this->update_endpoint, $args );
		} else {
			return false;
		}

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== (int) $code ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		if ( ! is_object( $data ) || empty( $data->version ) ) {
			return false;
		}

		set_transient( $this->cache_key, $data, $this->cache_ttl );

		return $data;
	}

	/**
	 * Purge update cache on plugin upgrade.
	 *
	 * @param object|null $upgrader
	 * @param array       $options
	 */
	public function purge_cache( $upgrader = null, array $options = [] ): void {
		if ( empty( $options ) || ( isset( $options['action'] ) && 'update' === $options['action'] && isset( $options['type'] ) && 'plugin' === $options['type'] ) ) {
			delete_transient( $this->cache_key );
		}
	}

	/**
	 * Handle manual force update check from admin action.
	 */
	public function force_check_now(): void {
		delete_transient( $this->cache_key );
		$this->fetch_remote_info( true );
		if ( function_exists( 'wp_safe_redirect' ) ) {
			wp_safe_redirect( remove_query_arg( [ 'vorkei_force_update_check', '_wpnonce' ] ) );
			exit;
		}
	}
}
