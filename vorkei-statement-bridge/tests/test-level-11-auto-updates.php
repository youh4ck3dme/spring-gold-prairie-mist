<?php
/**
 * Test Level 11: Auto-Updater Client & Server Integrita (api.vorkei.com)
 */

namespace {
	echo "🔍 [TEST 11] Auto-Updater Client & WordPress Update Transient Integrita...\n";

	define('ABSPATH', '/var/www/html/wp/');
	defined('VORKEI_BRIDGE_VERSION') || define('VORKEI_BRIDGE_VERSION', '1.0.0');
	defined('VORKEI_BRIDGE_BASENAME') || define('VORKEI_BRIDGE_BASENAME', 'vorkei-statement-bridge/vorkei-statement-bridge.php');
	defined('VORKEI_BRIDGE_URL') || define('VORKEI_BRIDGE_URL', 'https://vorkei.com/wp-content/plugins/vorkei-statement-bridge/');

	$GLOBALS['wp_transients'] = [];
	$GLOBALS['mock_remote_response'] = null;
	$GLOBALS['mock_remote_code'] = 200;

	function esc_url_raw($url) {
		return filter_var($url, FILTER_SANITIZE_URL);
	}
	function esc_url($url) {
		return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
	}
	function esc_html($text) {
		return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
	}
	function esc_attr($text) {
		return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
	}
	function esc_html__($text, $domain = 'default') {
		return $text;
	}
	function __($text, $domain = 'default') {
		return $text;
	}
	function sanitize_text_field($str) {
		return trim(strip_tags((string)$str));
	}
	function wp_kses_post($html) {
		return $html;
	}
	function get_option($name, $default = false) {
		return $GLOBALS['wp_options'][$name] ?? $default;
	}
	function update_option($name, $value) {
		$GLOBALS['wp_options'][$name] = $value;
		return true;
	}
	function get_transient($key) {
		return $GLOBALS['wp_transients'][$key] ?? false;
	}
	function set_transient($key, $value, $ttl = 0) {
		$GLOBALS['wp_transients'][$key] = $value;
		return true;
	}
	function delete_transient($key) {
		unset($GLOBALS['wp_transients'][$key]);
		return true;
	}
	function is_admin() {
		return true;
	}
	function home_url() {
		return 'https://vorkei.com';
	}
	function get_bloginfo($show = '') {
		return '6.7.0';
	}
	function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {
		$GLOBALS['wp_filters'][$tag][] = ['callback' => $callback, 'priority' => $priority];
	}
	function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {
		$GLOBALS['wp_actions'][$tag][] = ['callback' => $callback, 'priority' => $priority];
	}
	function is_wp_error($thing) {
		return false;
	}
	function wp_remote_retrieve_response_code($response) {
		return $GLOBALS['mock_remote_code'];
	}
	function wp_remote_retrieve_body($response) {
		return is_string($GLOBALS['mock_remote_response']) ? $GLOBALS['mock_remote_response'] : json_encode($GLOBALS['mock_remote_response']);
	}
	function wp_remote_post($url, $args = []) {
		if ($GLOBALS['mock_remote_code'] === 0) {
			return new \Exception('Connection timeout');
		}
		return ['response' => ['code' => $GLOBALS['mock_remote_code']]];
	}

	require_once dirname(__DIR__) . '/includes/class-vorkei-updater.php';

	// =========================================================================
	// Scenár 11.1: Inicializácia Vorkei_Updater
	// =========================================================================
	echo "  [Scenár 11.1] Test inicializácie a predvoleného endpointu...\n";
	$updater = new \Vorkei_Updater();
	assert($updater->get_update_endpoint() === 'https://api.vorkei.com/plugins/update-check', 'Predvolený endpoint nezodpovedá api.vorkei.com');
	echo "    ✓ Predvolený update endpoint https://api.vorkei.com/plugins/update-check správne nastavený.\n";

	// =========================================================================
	// Scenár 11.2: Detekcia novej verzie (v1.1.0) & Transient Injekcia
	// =========================================================================
	echo "  [Scenár 11.2] Test detekcie novej verzie (1.1.0 > 1.0.0) a injekcie do transientu...\n";
	$GLOBALS['mock_remote_code'] = 200;
	$GLOBALS['mock_remote_response'] = [
		'version'      => '1.1.0',
		'download_url' => 'https://api.vorkei.com/plugins/download/vorkei-statement-bridge-1.1.0.zip',
		'tested'       => '6.7',
		'requires_php' => '8.1',
		'homepage'     => 'https://vorkei.com',
		'changelog'    => '<ul><li>Pridaná podpora nových bankových kódov</li><li>Zrýchlené párovanie</li></ul>',
	];
	delete_transient('vorkei_bridge_update_info');

	$transient = new \stdClass();
	$transient->checked = [VORKEI_BRIDGE_BASENAME => '1.0.0'];
	$transient->response = [];
	$transient->no_update = [];

	$updatedTransient = $updater->check_update($transient);

	assert(!empty($updatedTransient->response[VORKEI_BRIDGE_BASENAME]), 'Aktualizácia sa nenachádza v response');
	$updateItem = $updatedTransient->response[VORKEI_BRIDGE_BASENAME];
	assert($updateItem->new_version === '1.1.0', 'Nová verzia nesedí');
	assert($updateItem->package === 'https://api.vorkei.com/plugins/download/vorkei-statement-bridge-1.1.0.zip', 'Download balíček nesedí');
	assert(!empty($updateItem->icons['2x']), 'Chýba ikona pluginu');
	assert(!empty($updateItem->banners['high']), 'Chýba banner pluginu');
	echo "    ✓ Verzia 1.1.0 bola úspešne vložená do update transientu s balíčkom a brandingom.\n";

	// =========================================================================
	// Scenár 11.3: Plugin je aktuálny (remote 1.0.0 == current 1.0.0)
	// =========================================================================
	echo "  [Scenár 11.3] Test stavu 'Plugin je aktuálny' (bez novej verzie)...\n";
	$GLOBALS['mock_remote_response']['version'] = '1.0.0';
	delete_transient('vorkei_bridge_update_info');

	$transient2 = new \stdClass();
	$transient2->checked = [VORKEI_BRIDGE_BASENAME => '1.0.0'];
	$transient2->response = [];
	$transient2->no_update = [];

	$updatedTransient2 = $updater->check_update($transient2);

	assert(empty($updatedTransient2->response[VORKEI_BRIDGE_BASENAME]), 'Response nesmie obsahovať update pri rovnakej verzii');
	assert(!empty($updatedTransient2->no_update[VORKEI_BRIDGE_BASENAME]), 'Záznam musí byť v no_update');
	echo "    ✓ Pri rovnakej verzii (1.0.0) sa aktualizácia neponúka a je v no_update zozname.\n";

	// =========================================================================
	// Scenár 11.4: Modálne okno s detailmi aktualizácie (plugins_api)
	// =========================================================================
	echo "  [Scenár 11.4] Test poskytovania detailov pre modálne okno (plugins_api)...\n";
	$args = (object)['slug' => 'vorkei-statement-bridge'];
	$modalInfo = $updater->plugins_api_info(false, 'plugin_information', $args);

	assert(is_object($modalInfo), 'plugins_api nevrátil objekt');
	assert($modalInfo->slug === 'vorkei-statement-bridge', 'Nesprávny slug v modále');
	assert(!empty($modalInfo->sections['description']), 'Chýba popis v sekciách modálu');
	assert(!empty($modalInfo->sections['changelog']), 'Chýba changelog v sekciách modálu');
	echo "    ✓ Modálne okno WordPress správne zobrazuje changelog, popis a parametre verzie.\n";

	// =========================================================================
	// Scenár 11.5: Odolnosť voči výpadku aktualizačného servera (Resilience)
	// =========================================================================
	echo "  [Scenár 11.5] Test správania pri výpadku servera vorkei.com (HTTP 500 / Timeout)...\n";
	$GLOBALS['mock_remote_code'] = 500;
	$GLOBALS['mock_remote_response'] = 'Internal Server Error';
	delete_transient('vorkei_bridge_update_info');

	$transientFail = new \stdClass();
	$transientFail->checked = [VORKEI_BRIDGE_BASENAME => '1.0.0'];
	$transientFail->response = [];

	$resFail = $updater->check_update($transientFail);
	assert(empty($resFail->response[VORKEI_BRIDGE_BASENAME]), 'Pri chybe 500 sa nesmie poškodiť transient');
	echo "    ✓ Pri výpadku servera systém zlyhal bezpečne (graceful failover), bez fatal errorov.\n";

	// =========================================================================
	// Scenár 11.6: Vyčistenie cache po aktualizácii (upgrader_process_complete)
	// =========================================================================
	echo "  [Scenár 11.6] Test vyčistenia cache transientu po úspešnom update...\n";
	set_transient('vorkei_bridge_update_info', (object)['version' => '1.1.0']);
	assert(get_transient('vorkei_bridge_update_info') !== false, 'Cache transient nebol nastavený');

	$updater->purge_cache(null, ['action' => 'update', 'type' => 'plugin']);
	assert(get_transient('vorkei_bridge_update_info') === false, 'Cache transient nebol vymazaný po update');
	echo "    ✓ Cache transient úspešne premazaný hookom upgrader_process_complete.\n";

	echo "🎉 TEST 11 ÚSPEŠNÝ: Auto-Updater klient spĺňa všetky štandardy WordPress Core a vorkei.com.\n\n";
	exit(0);
}
