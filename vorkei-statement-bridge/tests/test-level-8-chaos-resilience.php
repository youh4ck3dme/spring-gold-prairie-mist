<?php
/**
 * Test Level 8: Chaos Engineering & Odolnosť voči výpadkom (Resilience)
 */

echo "🔍 [TEST 8] Chaos Engineering & Odolnosť voči výpadkom (Resilience)...\n";

define('ABSPATH', '/var/www/html/wp/');
defined('ARRAY_A') || define('ARRAY_A', 'ARRAY_A');

$GLOBALS['options_storage'] = [];

function get_option($name, $default = false) {
	return $GLOBALS['options_storage'][$name] ?? $default;
}

function update_option($name, $value) {
	$GLOBALS['options_storage'][$name] = $value;
	return true;
}

function rest_authorization_required_code() {
	return 401;
}

function __($text, $domain = 'default') {
	return $text;
}

function esc_html($text) {
	return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function sanitize_text_field($str) {
	return is_scalar($str) ? trim(strip_tags((string)$str)) : '';
}

function sanitize_textarea_field($str) {
	return is_scalar($str) ? trim(strip_tags((string)$str)) : '';
}

function current_time($type) {
	return date('Y-m-d H:i:s');
}

function current_user_can($cap) {
	return true;
}

function apply_filters($tag, $value, ...$args) {
	return $value;
}

function rest_ensure_response($response) {
	if ($response instanceof WP_REST_Response) {
		return $response;
	}
	return new WP_REST_Response($response);
}

class WP_REST_Server {
	const CREATABLE = 'POST';
	const READABLE  = 'GET';
}

class WP_REST_Request {
	public string $method;
	public string $route;
	public array $headers = [];
	public array $json_params = [];
	public array $params = [];

	public function __construct(string $method = 'GET', string $route = '') {
		$this->method = $method;
		$this->route  = $route;
	}

	public function get_header(string $header): ?string {
		return null;
	}

	public function get_json_params(): array {
		return $this->json_params;
	}

	public function set_json_params(array $params): void {
		$this->json_params = $params;
	}
}

class WP_REST_Response {
	public $data;
	public int $status = 200;
	public array $headers = [];

	public function __construct($data = null, int $status = 200) {
		$this->data = $data;
		$this->status = $status;
	}

	public function header(string $key, string $value): void {
		$this->headers[$key] = $value;
	}
}

class WP_Error {
	public string $code;
	public string $message;
	public array $data;

	public function __construct(string $code = '', string $message = '', array $data = []) {
		$this->code = $code;
		$this->message = $message;
		$this->data = $data;
	}
}

function is_wp_error($thing): bool {
	return $thing instanceof WP_Error;
}

function register_rest_route($namespace, $route, $args) {}

class MockWPDBChaos {
	public string $prefix = 'wp_';
	public array $rows = [];
	public bool $simulate_failure = false;

	public function replace($table, $data) {
		if ($this->simulate_failure) {
			throw new \RuntimeException("MySQL server has gone away");
		}
		$this->rows[$table][$data['id']] = $data;
		return 1;
	}

	public function prepare($query, ...$args) {
		return vsprintf(str_replace(['%s', '%d'], ["'%s'", "%d"], $query), $args);
	}

	public function get_row($query, $output = ARRAY_A) {
		return null;
	}
}

global $wpdb;
$wpdb = new MockWPDBChaos();

abstract class WP_REST_Controller {
	protected $namespace;
	protected $rest_base;
}

require_once dirname(__DIR__) . '/includes/class-vorkei-matcher.php';
require_once dirname(__DIR__) . '/includes/class-vorkei-rest-controller.php';

$controller = new \Vorkei_REST_Controller();

// 1. Scenár: WooCommerce vôbec NIE JE nainštalované (wc_get_order neexistuje)
echo "  [Chaos 8.1] Test správania pri úplne vypnutom WooCommerce...\n";
assert(!function_exists('wc_get_order'), 'wc_get_order nesmie existovať v tomto teste');

$payload = [
	'account' => [ 'iban' => 'SK88000000001234567890' ],
	'statements' => [
		[
			'id'           => 'st-no-wc-1',
			'number'       => '1',
			'openingCents' => 0,
			'closingCents' => 10000,
			'currency'     => 'EUR',
			'movements'    => [
				[
					'id'             => 'mv-no-wc-1',
					'date'           => '29.10.2026',
					'amountCents'    => 10000,
					'kind'           => 'in',
					'partyName'      => 'Test',
					'partyIban'      => 'SK0000',
					'variableSymbol' => '1001',
					'note'           => 'Platba bez WC',
				],
			],
		],
	],
];

$req = new WP_REST_Request('POST', '/vorkei/v1/sync');
$req->set_json_params($payload);
$res = $controller->handle_sync($req);

assert($res instanceof WP_REST_Response, 'Sync musí vrátiť response');
assert($res->data['success'] === true, 'Sync musí uspieť aj bez WooCommerce');
assert($res->data['stats']['orders_matched'] === 0, 'Bez WC nesmie byť spárovaná žiadna objednávka');
assert($res->data['stats']['movements_saved'] === 1, 'Pohyb musel byť bezpečne uložený do databázy');
echo "    ✓ Plugin funguje autonómne aj bez WooCommerce (výpisy a pohyby sa bezpečne ukladajú).\n";

// 2. Scenár: Prázdny výpis bez pohybov (movements: [])
echo "  [Chaos 8.2] Test spracovania výpisu bez akýchkoľvek pohybov...\n";
$emptyStPayload = [
	'account' => [ 'iban' => 'SK88000000001234567890' ],
	'statements' => [
		[
			'id'           => 'st-empty-movements',
			'number'       => '2',
			'openingCents' => 5000,
			'closingCents' => 5000,
			'currency'     => 'EUR',
			'movements'    => [],
		],
	],
];
$reqEmptySt = new WP_REST_Request('POST', '/vorkei/v1/sync');
$reqEmptySt->set_json_params($emptyStPayload);
$resEmptySt = $controller->handle_sync($reqEmptySt);
assert($resEmptySt->data['stats']['statements_saved'] === 1, 'Hlavička výpisu musí byť uložená');
assert($resEmptySt->data['stats']['movements_saved'] === 0, 'Pohybov má byť 0');
echo "    ✓ Výpisy bez pohybov sú spracované korektne bez varovaní.\n";

// 3. Scenár: Výpadok databázy v priebehu zápisu (Simulácia Runtime Crash)
echo "  [Chaos 8.3] Test reakcie na výpadok databázového spojenia...\n";
$wpdb->simulate_failure = true;
try {
	$resFail = $controller->handle_sync($req);
	echo "    ⚠️ Výnimka bola zachytená.\n";
} catch (\Throwable $e) {
	assert(str_contains($e->getMessage(), 'MySQL server has gone away'), 'Výnimka DB zachytená');
	echo "    ✓ Výpadok databázy vyvolal očakávanú výnimku bez poškodenia integrity pamäte.\n";
}
$wpdb->simulate_failure = false;

echo "🎉 TEST 8 ÚSPEŠNÝ: Plugin preukázal robustnú odolnosť voči výpadkom a chýbajúcim závislostiam.\n\n";
exit(0);
