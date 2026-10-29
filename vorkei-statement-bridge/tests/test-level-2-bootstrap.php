<?php
namespace Automattic\WooCommerce\Utilities {
	class FeaturesUtil {
		public static function declare_compatibility($feature, $file, $positive = true) {
			$GLOBALS['hpos_declared'][$feature] = ['file' => $file, 'positive' => $positive];
		}
	}
}

namespace {
	echo "🔍 [TEST 2] WordPress Mock Bootstrapping & Inicializácia...\n";

	// 1. Nastavenie mock prostredia WordPress
	define('ABSPATH', '/var/www/html/wp/');

	$GLOBALS['wp_actions'] = [];
	$GLOBALS['wp_filters'] = [];
	$GLOBALS['wp_activation_hooks'] = [];
	$GLOBALS['wp_deactivation_hooks'] = [];
	$GLOBALS['hpos_declared'] = [];

	function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {
		$GLOBALS['wp_actions'][$tag][] = ['callback' => $callback, 'priority' => $priority];
	}

	function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {
		$GLOBALS['wp_filters'][$tag][] = ['callback' => $callback, 'priority' => $priority];
	}

	function do_action($tag, ...$args) {
		if (!empty($GLOBALS['wp_actions'][$tag])) {
			foreach ($GLOBALS['wp_actions'][$tag] as $hook) {
				call_user_func_array($hook['callback'], $args);
			}
		}
	}

	function apply_filters($tag, $value, ...$args) {
		if (!empty($GLOBALS['wp_filters'][$tag])) {
			foreach ($GLOBALS['wp_filters'][$tag] as $hook) {
				$value = call_user_func_array($hook['callback'], array_merge([$value], $args));
			}
		}
		return $value;
	}

	function register_activation_hook($file, $callback) {
		$GLOBALS['wp_activation_hooks'][$file] = $callback;
	}

	function register_deactivation_hook($file, $callback) {
		$GLOBALS['wp_deactivation_hooks'][$file] = $callback;
	}

	function plugin_dir_path($file) {
		return dirname($file) . '/';
	}

	function plugin_dir_url($file) {
		return 'https://example.com/wp-content/plugins/' . basename(dirname($file)) . '/';
	}

	function plugin_basename($file) {
		return basename(dirname($file)) . '/' . basename($file);
	}

	function esc_url_raw($url) {
		return filter_var($url, FILTER_SANITIZE_URL);
	}

	function esc_url($url) {
		return htmlspecialchars((string)$url, ENT_QUOTES, 'UTF-8');
	}

	function sanitize_text_field($str) {
		return trim(strip_tags((string)$str));
	}

	function is_admin() {
		return true;
	}

	function get_option($name, $default = false) {
		return '1.0.0';
	}

	function update_option($name, $value) {
		return true;
	}

	function load_plugin_textdomain($domain, $deprecated = false, $plugin_rel_path = false) {
		return true;
	}

	// Dummy WP_REST_Controller base class
	class WP_REST_Controller {
		protected $namespace;
		protected $rest_base;
	}

	// 2. Načítanie pluginu
	$pluginFile = dirname(__DIR__) . '/vorkei-statement-bridge.php';
	require_once $pluginFile;

	// Overenie konštánt
	assert(defined('VORKEI_BRIDGE_VERSION'), 'VORKEI_BRIDGE_VERSION nie je definovaná');
	assert(defined('VORKEI_BRIDGE_PATH'), 'VORKEI_BRIDGE_PATH nie je definovaná');
	assert(defined('VORKEI_BRIDGE_URL'), 'VORKEI_BRIDGE_URL nie je definovaná');
	assert(defined('VORKEI_BRIDGE_BASENAME'), 'VORKEI_BRIDGE_BASENAME nie je definovaná');
	echo "  ✓ Konštanty pluginu sú definované správne (Verzia: " . VORKEI_BRIDGE_VERSION . ")\n";

	// Overenie HPOS hooku
	do_action('before_woocommerce_init');
	assert(!empty($GLOBALS['hpos_declared']['custom_order_tables']), 'HPOS custom_order_tables deklarácia zlyhala!');
	echo "  ✓ WooCommerce HPOS (custom_order_tables) kompatibilita je úspešne deklarovaná\n";

	// Overenie aktivačných hookov
	assert(!empty($GLOBALS['wp_activation_hooks'][$pluginFile]), 'Chýba register_activation_hook');
	assert(!empty($GLOBALS['wp_deactivation_hooks'][$pluginFile]), 'Chýba register_deactivation_hook');
	echo "  ✓ Aktivačný aj deaktivačný hook sú zaregistrované\n";

	// Simulácia plugins_loaded hooku
	do_action('plugins_loaded');

	// Overenie Singletonu
	$instance1 = \Vorkei_Statement_Bridge::get_instance();
	$instance2 = \Vorkei_Statement_Bridge::get_instance();
	assert($instance1 === $instance2, 'Singleton Vorkei_Statement_Bridge nevrátil rovnakú inštanciu');
	echo "  ✓ Singleton pattern Vorkei_Statement_Bridge overený\n";

	// Overenie načítania tried
	assert(class_exists('Vorkei_Activator'), 'Trieda Vorkei_Activator chýba');
	assert(class_exists('Vorkei_Deactivator'), 'Trieda Vorkei_Deactivator chýba');
	assert(class_exists('Vorkei_Matcher'), 'Trieda Vorkei_Matcher chýba');
	assert(class_exists('Vorkei_REST_Controller'), 'Trieda Vorkei_REST_Controller chýba');
	assert(class_exists('Vorkei_Updater'), 'Trieda Vorkei_Updater chýba');
	assert(class_exists('Vorkei_Admin'), 'Trieda Vorkei_Admin chýba');
	assert($instance1->updater instanceof \Vorkei_Updater, 'Vorkei_Updater inštancia nebola inicializovaná');
	echo "  ✓ Všetky kľúčové triedy (vrátane Vorkei_Updater) sú správne načítané a existujú\n";

	echo "🎉 TEST 2 ÚSPEŠNÝ: WordPress bootstrapping a registrácia hookov prebehli bezchybne.\n\n";
	exit(0);
}
