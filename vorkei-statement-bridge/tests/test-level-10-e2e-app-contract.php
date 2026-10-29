<?php
/**
 * Test Level 10: E2E Kontrakt aplikácie Výpis (pdf-t3.vorkei) <-> WordPress WooCommerce Bridge
 */

echo "🔍 [TEST 10] E2E Kontrakt: Aplikácia Výpis (pdf-t3.vorkei) ➔ WordPress Bridge...\n";

define('ABSPATH', '/var/www/html/wp/');
defined('ARRAY_A') || define('ARRAY_A', 'ARRAY_A');

$GLOBALS['options_storage'] = [
	'vorkei_bridge_enable_wc_matching' => 'yes',
	'vorkei_bridge_wc_target_status'   => 'processing',
	'vorkei_bridge_verify_amount'      => 'yes',
	'vorkei_bridge_add_order_note'     => 'yes',
	'vorkei_bridge_api_token'          => 'vorkei-app-secret-key-2026',
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
	return '2026-10-29 17:35:00';
}

function current_user_can($cap) {
	return true;
}

function do_action($tag, ...$args) {}
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

	public function __construct(string $method = 'POST', string $route = '/vorkei/v1/sync') {
		$this->method = $method;
		$this->route  = $route;
	}

	public function get_header(string $header): ?string {
		return $this->headers[strtolower($header)] ?? null;
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

class WC_Order {
	private int $id;
	private float $total;
	private string $status;
	private string $currency = 'EUR';
	private array $notes = [];
	private array $meta = [];

	public function __construct(int $id, float $total, string $status = 'pending') {
		$this->id     = $id;
		$this->total  = $total;
		$this->status = $status;
	}

	public function get_id(): int {
		return $this->id;
	}

	public function get_total(): float {
		return $this->total;
	}

	public function get_currency(): string {
		return $this->currency;
	}

	public function get_status(): string {
		return $this->status;
	}

	public function update_status(string $status, string $note = ''): void {
		$this->status = $status;
		if (!empty($note)) {
			$this->notes[] = $note;
		}
	}

	public function add_order_note(string $note, bool $is_customer_note = false): void {
		$this->notes[] = $note;
	}

	public function get_notes(): array {
		return $this->notes;
	}

	public function update_meta_data(string $key, $value): void {
		$this->meta[$key] = $value;
	}

	public function get_meta(string $key) {
		return $this->meta[$key] ?? '';
	}

	public function save(): void {}
}

$GLOBALS['mock_orders'] = [
	3050 => new WC_Order(3050, 249.90, 'pending'),
];

function wc_get_order($id) {
	return $GLOBALS['mock_orders'][(int)$id] ?? false;
}

function wc_get_orders(array $args) {
	return [];
}

class MockWPDBE2E {
	public string $prefix = 'wp_';
	public array $statements = [];
	public array $movements = [];

	public function replace($table, $data) {
		if (str_ends_with($table, 'vorkei_statements')) {
			$this->statements[$data['id']] = $data;
		} else {
			$this->movements[$data['id']] = $data;
		}
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
$wpdb = new MockWPDBE2E();

abstract class WP_REST_Controller {
	protected $namespace;
	protected $rest_base;
}

require_once dirname(__DIR__) . '/includes/class-vorkei-matcher.php';
require_once dirname(__DIR__) . '/includes/class-vorkei-rest-controller.php';

$controller = new \Vorkei_REST_Controller();

// 1. Simulácia reálnej dátovej štruktúry z aplikácie Výpis (Books SEED)
echo "  [E2E 10.1] Konverzia dátového modelu z aplikácie Výpis (pdf-t3.vorkei)...\n";

$vypisAppPayload = [
	'account' => [
		'iban' => 'SK8811000000002941234567',
	],
	'statements' => [
		[
			'id'           => 's-oct-2026-001',
			'number'       => '10',
			'openingCents' => 150000, // 1 500.00 EUR
			'closingCents' => 174990, // 1 749.90 EUR (+249.90 EUR)
			'currency'     => 'EUR',
			'issuedOn'     => '29.10.2026',
			'movements'    => [
				[
					'id'             => 'm-client-vorkei-3050',
					'date'           => '29.10.2026',
					'amountCents'    => 24990, // 249.90 EUR
					'kind'           => 'in',
					'partyName'      => 'Vorkei Spokojný Zákazník',
					'partyIban'      => 'SK5511000000009876543210',
					'variableSymbol' => '3050',
					'note'           => 'Faktúra č. 3050 - nákup v eshope vorkei.com',
				],
			],
		],
	],
];

// 2. Simulácia HTTP odoslania s Bearer tokenom
$request = new WP_REST_Request('POST', '/vorkei/v1/sync');
$request->set_header('Authorization', 'Bearer vorkei-app-secret-key-2026');
$request->set_json_params($vypisAppPayload);

// Overenie autentifikácie
assert($controller->check_permissions($request) === true, 'Autentifikácia aplikácie Výpis musí uspieť');
echo "    ✓ Bearer token aplikácie Výpis bol úspešne overený.\n";

// 3. Spracovanie a synchronizácia cez mostík
$response = $controller->handle_sync($request);
assert($response instanceof WP_REST_Response, 'Sync musí vrátiť response');
assert($response->data['success'] === true, 'Sync response success != true');
assert($response->data['stats']['orders_matched'] === 1, 'Objednávka #3050 musela byť automaticky spárovaná!');
echo "    ✓ Výpis a transakcia boli prijaté, objednávka #3050 automaticky identifikovaná.\n";

// 4. Overenie výsledného stavu WooCommerce objednávky
$order3050 = wc_get_order(3050);
assert($order3050->get_status() === 'processing', "Objednávka #3050 mala prejsť do 'processing', stav: " . $order3050->get_status());
assert($order3050->get_meta('_vorkei_matched_movement_id') === 'm-client-vorkei-3050', "Meta dáta transakcie neboli uložené");

$orderNotes = $order3050->get_notes();
assert(count($orderNotes) >= 1, "K objednávke nebola pridaná poznámka");
assert(str_contains($orderNotes[0], '249,90 EUR'), "Poznámka musí obsahovať prijatú sumu 249,90 EUR");
assert(str_contains($orderNotes[0], 'Vorkei Spokojný Zákazník'), "Poznámka musí obsahovať meno odosielateľa");

echo "    ✓ Objednávka #3050 bola prepnutá do stavu 'Spracováva sa' (processing).\n";
echo "    ✓ Interná účtovná poznámka bola do objednávky vložená s auditom (suma 249,90 EUR, IBAN a meno platiteľa).\n";

echo "🎉 TEST 10 ÚSPEŠNÝ: Kompletný End-to-End cyklus z aplikácie Výpis do WooCommerce eshopu je 100% funkčný.\n\n";
exit(0);
