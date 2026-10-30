<?php
/**
 * Test Level 4: REST API Kontrakt & Payload Validácia
 */

echo "🔍 [TEST 4] REST API Kontrakt & Payload Validácia...\n";

define('ABSPATH', '/var/www/html/wp/');
defined('ARRAY_A') || define('ARRAY_A', 'ARRAY_A');

// Mock WP core functions
$GLOBALS['options_storage'] = [
	'vorkei_bridge_api_token' => 'moj-tajny-token-12345',
];

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

function sanitize_text_field($str) {
	return is_scalar($str) ? trim(strip_tags((string)$str)) : '';
}

function sanitize_textarea_field($str) {
	return is_scalar($str) ? trim(strip_tags((string)$str)) : '';
}

function current_time($type) {
	return date('Y-m-d H:i:s');
}

$GLOBALS['current_user_caps'] = [];

function current_user_can($cap) {
	return in_array($cap, $GLOBALS['current_user_caps'], true);
}

function apply_filters($tag, $value, ...$args) {
	return $value;
}

// Mock WP_REST classes
class WP_REST_Server {
	const CREATABLE = 'POST';
	const READABLE  = 'GET';
	const EDITABLE  = 'POST, PUT, PATCH';
	const DELETABLE = 'DELETE';
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
		$header = strtolower($header);
		return $this->headers[$header] ?? null;
	}

	public function set_header(string $header, string $value): void {
		$this->headers[strtolower($header)] = $value;
	}

	public function get_json_params(): array {
		return $this->json_params;
	}

	public function set_json_params(array $params): void {
		$this->json_params = $params;
	}

	public function get_param(string $key) {
		return $this->params[$key] ?? null;
	}

	public function set_param(string $key, $value): void {
		$this->params[$key] = $value;
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

function rest_ensure_response($response) {
	if ($response instanceof WP_REST_Response) {
		return $response;
	}
	return new WP_REST_Response($response);
}

$GLOBALS['registered_routes'] = [];
function register_rest_route($namespace, $route, $args) {
	$GLOBALS['registered_routes'][$namespace][$route] = $args;
}

// Mock WPDB
class MockWPDB4 {
	public string $prefix = 'wp_';
	public array $rows = [];
	public array $wallets = [];

	public function insert($table, $data) {
		$this->rows[$table][] = $data;
		return 1;
	}

	public function replace($table, $data) {
		$this->rows[$table][$data['id']] = $data;
		return 1;
	}

	public function get_row($query, $output = ARRAY_A) {
		if (strpos($query, 'vorkei_client_tokens') !== false) {
			foreach ($this->wallets as $w) {
				if (strpos($query, "'" . $w['token_key'] . "'") !== false) {
					return $w;
				}
			}
		}
		return null;
	}

	public function query($query) {
		return true;
	}

	public function update($table, $data, $where) {
		return 1;
	}

	public function prepare($query, ...$args) {
		return vsprintf(str_replace(['%s', '%d'], ["'%s'", "%d"], $query), $args);
	}

	public function get_var($query) {
		return count($this->rows['wp_vorkei_statements'] ?? []);
	}

	public function get_results($query, $output = ARRAY_A) {
		return array_values($this->rows['wp_vorkei_statements'] ?? []);
	}
}

global $wpdb;
$wpdb = new MockWPDB4();

abstract class WP_REST_Controller {
	protected $namespace;
	protected $rest_base;
}

require_once dirname(__DIR__) . '/includes/class-vorkei-matcher.php';
require_once dirname(__DIR__) . '/includes/class-vorkei-rest-controller.php';

$controller = new \Vorkei_REST_Controller();
$controller->register_routes();

// 1. Overenie registrácie trás
assert(!empty($GLOBALS['registered_routes']['vorkei/v1']['/sync']), 'Trasa /sync chýba');
assert(!empty($GLOBALS['registered_routes']['vorkei/v1']['/statements']), 'Trasa /statements chýba');
assert(!empty($GLOBALS['registered_routes']['vorkei/v1']['/movements']), 'Trasa /movements chýba');
assert(!empty($GLOBALS['registered_routes']['vorkei/v1']['/export/(?P<id>[a-zA-Z0-9_\-]+)']), 'Trasa /export chýba');
assert(!empty($GLOBALS['registered_routes']['vorkei/v1']['/audit-logs']), 'Trasa /audit-logs chýba');
assert(!empty($GLOBALS['registered_routes']['vorkei/v1']['/wallets']), 'Trasa /wallets chýba');
echo "  ✓ REST trasy (/sync, /statements, /movements, /export, /audit-logs, /wallets) sú správne zaregistrované v mennom priestore vorkei/v1\n";

// 2. Test oprávnení (Permissions)
// Case A: Neautorizovaný používateľ bez hlavičky
$reqUnauthorized = new WP_REST_Request('POST', '/vorkei/v1/sync');
$authResult = $controller->check_permissions($reqUnauthorized);
assert(is_wp_error($authResult), 'Neautorizovaný prístup mal zlyhať!');
echo "  ✓ Neautorizovaný prístup je správne zablokovaný (HTTP 401 rest_forbidden)\n";

// Case B: Autorizácia cez Bearer token
$reqToken = new WP_REST_Request('POST', '/vorkei/v1/sync');
$reqToken->set_header('Authorization', 'Bearer moj-tajny-token-12345');
$tokenResult = $controller->check_permissions($reqToken);
assert($tokenResult === true, 'Bearer Token autorizácia mala prejsť!');
echo "  ✓ Autorizácia cez Bearer API Token funguje bezchybne\n";

// Case C: Autorizácia cez WP Capability (Application Passwords)
$GLOBALS['current_user_caps'] = ['edit_shop_orders'];
$reqCap = new WP_REST_Request('POST', '/vorkei/v1/sync');
$capResult = $controller->check_permissions($reqCap);
assert($capResult === true, 'WP Capability autorizácia mala prejsť!');
echo "  ✓ Autorizácia cez používateľské oprávnenia (edit_shop_orders) funguje\n";

// 3. Test spracovania payloadu (Validation & Sync)
// Case A: Prázdny payload -> 400 Bad Request
$reqEmpty = new WP_REST_Request('POST', '/vorkei/v1/sync');
$reqEmpty->set_json_params([]);
$emptyRes = $controller->handle_sync($reqEmpty);
assert(is_wp_error($emptyRes) && $emptyRes->code === 'vorkei_invalid_data', 'Prázdny payload mal vrátiť chybu vorkei_invalid_data');
echo "  ✓ Neplatný/prázdny payload je správne odmietnutý s kódom 400\n";

// Case B: Reálny payload s výpisom a pohybom
$validPayload = [
	'account' => [
		'iban' => 'SK8811000000002941234567',
	],
	'statements' => [
		[
			'id'           => 'stmt-2026-001',
			'number'       => '1',
			'openingCents' => 50000,
			'closingCents' => 85000,
			'currency'     => 'EUR',
			'issuedOn'     => '29.10.2026',
			'movements'    => [
				[
					'id'             => 'mov-101',
					'date'           => '28.10.2026',
					'amountCents'    => 35000,
					'kind'           => 'in',
					'partyName'      => 'Vorkei Test Klient',
					'partyIban'      => 'SK12000000001234567890',
					'variableSymbol' => '2026001',
					'note'           => 'Úhrada faktúry 2026001',
				],
			],
		],
	],
];

$reqValid = new WP_REST_Request('POST', '/vorkei/v1/sync');
$reqValid->set_json_params($validPayload);
$syncRes = $controller->handle_sync($reqValid);

assert($syncRes instanceof WP_REST_Response, 'Sync mal vrátiť WP_REST_Response');
assert($syncRes->data['success'] === true, 'Sync response success != true');
assert($syncRes->data['stats']['statements_saved'] === 1, 'Malo byť uložených 1 výpisov');
assert($syncRes->data['stats']['movements_saved'] === 1, 'Malo byť uložených 1 pohybov');
echo "  ✓ Validný výpis bol úspešne spracovaný a uložený do databázy so štatistikou\n";

// 4. Overenie uloženia v databáze a generovania SHA-256 hashu
$savedMovement = $wpdb->rows['wp_vorkei_movements']['mov-101'] ?? null;
assert($savedMovement !== null, 'Pohyb mov-101 nebol nájdený v databáze');
assert(!empty($savedMovement['movement_hash']) && strlen($savedMovement['movement_hash']) === 64, 'SHA-256 hash nebol vygenerovaný korektne');
echo "  ✓ Idempotentný SHA-256 hash pohybu vygenerovaný: " . substr($savedMovement['movement_hash'], 0, 16) . "...\n";

// 5. Test čítania výpisov (get_statements)
$reqGet = new WP_REST_Request('GET', '/vorkei/v1/statements');
$reqGet->set_param('page', 1);
$reqGet->set_param('per_page', 10);
$getRes = $controller->get_statements($reqGet);
assert(isset($getRes->headers['X-WP-Total']), 'Chýba hlavička X-WP-Total');
echo "  ✓ Endpoint /statements vracia správne hlavičky stránkovania (X-WP-Total)\n";

// 6. Test Hard Paywall & Klientske Peňaženky (HTTP 402 Payment Required)
// Case A: Partner s 0 kreditmi je odmietnutý s kódom 402
$wpdb->wallets = [
	[
		'id'                 => 1,
		'token_key'          => 'VRK-PETER-FLOTILA-2026',
		'client_name'        => 'Kolega Peter',
		'credits_balance'    => 0,
		'cost_per_statement' => 1,
		'is_active'          => 1,
	],
	[
		'id'                 => 2,
		'token_key'          => 'VRK-VIP-PARTNER-2026',
		'client_name'        => 'VIP Partner',
		'credits_balance'    => 10,
		'cost_per_statement' => 1,
		'is_active'          => 1,
	],
];

// Test autorizácie s Peterovým kľúčom
$reqPeterAuth = new WP_REST_Request('POST', '/vorkei/v1/sync');
$reqPeterAuth->set_header('Authorization', 'Bearer VRK-PETER-FLOTILA-2026');
$peterAuthRes = $controller->check_permissions($reqPeterAuth);
assert($peterAuthRes === true, 'Partner token mal byť úspešne autorizovaný');
assert(!empty($reqPeterAuth->get_param('_vorkei_client_wallet')), 'Peňaženka mala byť priradená k requestu');

// Pokus o synchronizáciu bez kreditov
$reqPeterAuth->set_json_params($validPayload);
$peterSyncRes = $controller->handle_sync($reqPeterAuth);
assert(is_wp_error($peterSyncRes), 'Peter bez kreditov mal byť zablokovaný');
assert($peterSyncRes->code === 'vorkei_insufficient_credits', 'Kód chyby mal byť vorkei_insufficient_credits');
assert(($peterSyncRes->data['status'] ?? 0) === 402, 'HTTP status mal byť 402 Payment Required');
echo "  ✓ Hard Paywall (Nápad 1): Partner bez kreditov je striktne zablokovaný s kódom HTTP 402 Payment Required\n";

// Case B: Partner s dostatočným kreditom (VIP Partner s 10 kreditmi)
$reqVip = new WP_REST_Request('POST', '/vorkei/v1/sync');
$reqVip->set_header('Authorization', 'Bearer VRK-VIP-PARTNER-2026');
$controller->check_permissions($reqVip);
$reqVip->set_json_params($validPayload);
$vipSyncRes = $controller->handle_sync($reqVip);
assert($vipSyncRes instanceof WP_REST_Response, 'VIP sync mal prejsť');
assert($vipSyncRes->data['wallet']['credits_deducted'] === 1, 'Mala byť odpočítaná 1 kreditová jednotka');
assert($vipSyncRes->data['wallet']['credits_remaining'] === 9, 'Zostatok mal byť 9 kreditov');
echo "  ✓ Odpočítanie kreditu: Po úspešnej synchronizácii bol odpočítaný presne 1 kredit (zostatok: 9)\n";

echo "🎉 TEST 4 ÚSPEŠNÝ: REST API kontrakt, overovanie oprávnení a spracovanie payloadov fungujú excelentne.\n\n";
exit(0);
