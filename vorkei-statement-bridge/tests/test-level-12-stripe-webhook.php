<?php
/**
 * Test Level 12: Stripe Webhook, Transakčná Idempotencia & Reconciliácia
 * 
 * Verifikuje:
 * 12.1: Overenie platného Stripe podpisu (HMAC-SHA256, hash_equals)
 * 12.2: Odmietnutie neplatného / falšovaného podpisu (HTTP 400)
 * 12.3: Ochrana pred Replay Attackom (> 300s tolerancia)
 * 12.4: Úspešná platba (checkout.session.completed + paid) -> vytvorenie peňaženky a pripísanie kreditov
 * 12.5: Idempotencia a ochrana pred duplicitami pri opakovanom doručení webhooku
 * 12.6: Oneskorená platba (unpaid -> pending; async_payment_succeeded -> completed)
 * 12.7: Zlyhaná platba (async_payment_failed -> failed, 0 kreditov)
 * 12.8: Mechanizmus vyrovnania platieb (Fallback Reconciliation cez Stripe API)
 * 12.9: Admin audit endpoint pre kreditné transakcie
 */

namespace {
	echo "🔍 [TEST 12] Stripe Webhook, Transakčná Idempotencia & Reconciliácia...\n";

	define('ABSPATH', '/var/www/html/wp/');
	defined('ARRAY_A') || define('ARRAY_A', 'ARRAY_A');

	// Globálne úložisko pre WP options
	$GLOBALS['options_storage'] = [
		'vorkei_stripe_webhook_secret' => 'whsec_test_secret_key_1234567890abcdef',
		'vorkei_stripe_secret_key'     => 'sk_test_fake_stripe_api_key_987654321',
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

	function esc_html__($text, $domain = 'default') {
		return $text;
	}

	function sanitize_text_field($str) {
		return is_scalar($str) ? trim(strip_tags((string)$str)) : '';
	}

	function sanitize_textarea_field($str) {
		return is_scalar($str) ? trim(strip_tags((string)$str)) : '';
	}

	function sanitize_email($email) {
		return filter_var(trim((string)$email), FILTER_SANITIZE_EMAIL);
	}

	function wp_unslash($val) {
		return is_string($val) ? stripslashes($val) : $val;
	}

	function current_time($type) {
		return date('Y-m-d H:i:s');
	}

	$GLOBALS['current_user_caps'] = [ 'manage_woocommerce', 'manage_options' ];

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
		public string $body = '';
		public array $headers = [];
		public array $json_params = [];
		public array $params = [];

		public function __construct(string $method = 'GET', string $route = '') {
			$this->method = $method;
			$this->route  = $route;
		}

		public function get_body(): string {
			return $this->body;
		}

		public function set_body(string $body): void {
			$this->body = $body;
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

		public function get_params(): array {
			return $this->params;
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

		public function get_data() {
			return $this->data;
		}

		public function get_status(): int {
			return $this->status;
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

		public function get_error_code(): string {
			return $this->code;
		}

		public function get_error_message(): string {
			return $this->message;
		}

		public function get_error_data(): array {
			return $this->data;
		}
	}

	function is_wp_error($thing): bool {
		return $thing instanceof WP_Error;
	}

	function rest_ensure_response($response) {
		if ($response instanceof WP_REST_Response) {
			return $response;
		}
		if ($response instanceof WP_Error) {
			return $response;
		}
		return new WP_REST_Response($response);
	}

	$GLOBALS['registered_routes'] = [];
	function register_rest_route($namespace, $route, $args) {
		$GLOBALS['registered_routes'][$namespace][$route] = $args;
	}

	// Mock HTTP Remote API responses
	$GLOBALS['mock_remote_get_handler'] = null;

	function wp_remote_get($url, $args = []) {
		if (is_callable($GLOBALS['mock_remote_get_handler'])) {
			return call_user_func($GLOBALS['mock_remote_get_handler'], $url, $args);
		}
		return [
			'response' => [ 'code' => 200 ],
			'body'     => json_encode( [ 'id' => 'cs_test_mock' ] ),
		];
	}

	function wp_remote_retrieve_response_code($response) {
		if (is_wp_error($response)) {
			return 500;
		}
		return $response['response']['code'] ?? 200;
	}

	function wp_remote_retrieve_body($response) {
		if (is_wp_error($response)) {
			return '';
		}
		return $response['body'] ?? '';
	}

	// Mock WPDB pre Transakcie a Peňaženky
	class MockWPDB12 {
		public string $prefix = 'wp_';
		public array $transactions = [];
		public array $tokens = [];
		public int $insert_id = 0;

		public function prepare($query, ...$args) {
			$pattern = str_replace(['%s', '%d', '%f'], ["'%s'", "%d", "%F"], $query);
			$pattern = preg_replace("/''(%[sdfF])''/", "'$1'", $pattern);
			return vsprintf($pattern, $args);
		}

		public function insert($table, $data) {
			$this->insert_id++;
			$data['id'] = $this->insert_id;
			$data['created_at'] = current_time('mysql');
			$data['updated_at'] = current_time('mysql');

			if (strpos($table, 'credit_transactions') !== false) {
				$this->transactions[$this->insert_id] = $data;
			} elseif (strpos($table, 'client_tokens') !== false) {
				$this->tokens[$this->insert_id] = $data;
			}
			return 1;
		}

		public function update($table, $data, $where) {
			if (strpos($table, 'credit_transactions') !== false) {
				foreach ($this->transactions as $id => &$tx) {
					$match = true;
					foreach ($where as $k => $v) {
						if ((string)($tx[$k] ?? '') !== (string)$v) {
							$match = false;
							break;
						}
					}
					if ($match) {
						foreach ($data as $k => $v) {
							$tx[$k] = $v;
						}
						$tx['updated_at'] = current_time('mysql');
						return 1;
					}
				}
			} elseif (strpos($table, 'client_tokens') !== false) {
				foreach ($this->tokens as $id => &$tok) {
					$match = true;
					foreach ($where as $k => $v) {
						if ((string)($tok[$k] ?? '') !== (string)$v) {
							$match = false;
							break;
						}
					}
					if ($match) {
						foreach ($data as $k => $v) {
							$tok[$k] = $v;
						}
						$tok['updated_at'] = current_time('mysql');
						return 1;
					}
				}
			}
			return 1;
		}

		public function query($sql) {
			// Update credits_balance (increment)
			if (preg_match('/UPDATE.*vorkei_client_tokens.*SET credits_balance = credits_balance \+ (\d+).*total_spent_cents = total_spent_cents \+ (\d+).*WHERE id = (\d+)/i', $sql, $m)) {
				$credits = (int)$m[1];
				$spent   = (int)$m[2];
				$id      = (int)$m[3];
				if (isset($this->tokens[$id])) {
					$this->tokens[$id]['credits_balance'] = (int)($this->tokens[$id]['credits_balance'] ?? 0) + $credits;
					$this->tokens[$id]['total_spent_cents'] = (int)($this->tokens[$id]['total_spent_cents'] ?? 0) + $spent;
				}
				return 1;
			}
			// Update credits_balance (refund deduction)
			if (preg_match('/UPDATE.*vorkei_client_tokens.*SET credits_balance = GREATEST\(0,\s*credits_balance\s*-\s*(\d+)\).*WHERE id = (\d+)/i', $sql, $m)) {
				$deduct = (int)$m[1];
				$id     = (int)$m[2];
				if (isset($this->tokens[$id])) {
					$cur = (int)($this->tokens[$id]['credits_balance'] ?? 0);
					$this->tokens[$id]['credits_balance'] = max(0, $cur - $deduct);
				}
				return 1;
			}
			// Update is_active (dispute suspend / restore)
			if (preg_match('/UPDATE.*vorkei_client_tokens.*SET is_active = (\d+).*WHERE id = (\d+)/i', $sql, $m)) {
				$active = (int)$m[1];
				$id     = (int)$m[2];
				if (isset($this->tokens[$id])) {
					$this->tokens[$id]['is_active'] = $active;
				}
				return 1;
			}
			return 1;
		}

		public function get_row($sql, $output = ARRAY_A) {
			if (strpos($sql, 'vorkei_credit_transactions') !== false) {
				if (preg_match("/stripe_payment_intent_id = '([^']+)'/", $sql, $m)) {
					$val = $m[1];
					foreach ($this->transactions as $tx) {
						if (($tx['stripe_payment_intent_id'] ?? '') === $val) {
							return $tx;
						}
					}
				}
				if (preg_match("/stripe_event_id = '([^']+)'/", $sql, $m)) {
					$val = $m[1];
					foreach ($this->transactions as $tx) {
						if (($tx['stripe_event_id'] ?? '') === $val) {
							return $tx;
						}
					}
				}
				if (preg_match("/stripe_session_id = '([^']+)'/", $sql, $m)) {
					$val = $m[1];
					foreach ($this->transactions as $tx) {
						if (($tx['stripe_session_id'] ?? '') === $val) {
							return $tx;
						}
					}
				}
			} elseif (strpos($sql, 'vorkei_client_tokens') !== false) {
				if (preg_match("/client_email = '([^']+)'/", $sql, $m)) {
					$val = $m[1];
					foreach ($this->tokens as $tok) {
						if (($tok['client_email'] ?? '') === $val) {
							return $tok;
						}
					}
				}
				if (preg_match("/token_key = '([^']+)'/", $sql, $m)) {
					$val = $m[1];
					foreach ($this->tokens as $tok) {
						if (($tok['token_key'] ?? '') === $val) {
							return $tok;
						}
					}
				}
			}
			return null;
		}

		public function get_results($sql, $output = ARRAY_A) {
			if (strpos($sql, 'vorkei_credit_transactions') !== false) {
				return array_values($this->transactions);
			}
			return [];
		}

		public function get_var($sql) {
			return 0;
		}
	}

	global $wpdb;
	$wpdb = new MockWPDB12();

	abstract class WP_REST_Controller {
		protected $namespace;
		protected $rest_base;
	}

	require_once dirname(__DIR__) . '/includes/class-vorkei-matcher.php';
	require_once dirname(__DIR__) . '/includes/class-vorkei-rest-controller.php';

	$controller = new \Vorkei_REST_Controller();
	$secret     = get_option('vorkei_stripe_webhook_secret');

	// =========================================================================
	// Scenár 12.1: Overenie platného Stripe podpisu (HMAC-SHA256, hash_equals)
	// =========================================================================
	echo "  [Scenár 12.1] Overenie platného Stripe podpisu HMAC-SHA256...\n";
	$payload_12_1   = json_encode(['id' => 'evt_test_1', 'type' => 'checkout.session.completed']);
	$timestamp_12_1 = time();
	$sig_12_1       = hash_hmac('sha256', $timestamp_12_1 . '.' . $payload_12_1, $secret);
	$header_12_1    = "t={$timestamp_12_1},v1={$sig_12_1}";

	$is_valid = $controller->verify_stripe_signature($payload_12_1, $header_12_1, $secret);
	assert($is_valid === true, 'Validácia platného HMAC-SHA256 podpisu zlyhala!');
	echo "     ✅ Podpis korektne overený cez HMAC-SHA256 a hash_equals().\n";

	// =========================================================================
	// Scenár 12.2: Odmietnutie neplatného / falšovaného podpisu (HTTP 400)
	// =========================================================================
	echo "  [Scenár 12.2] Odmietnutie falšovaného alebo zmanipulovaného podpisu...\n";
	$forged_header = "t={$timestamp_12_1},v1=deadbeefcafebabe0123456789abcdef";
	$req_forged    = new WP_REST_Request('POST', '/vorkei/v1/stripe-webhook');
	$req_forged->set_body($payload_12_1);
	$req_forged->set_header('stripe-signature', $forged_header);

	$res_forged = $controller->handle_stripe_webhook($req_forged);
	assert(is_wp_error($res_forged), 'Falšovaný podpis mal vrátiť WP_Error!');
	assert($res_forged->get_error_code() === 'stripe_invalid_signature', 'Nesprávny kód chyby pre falšovaný podpis!');
	assert($res_forged->get_error_data()['status'] === 400, 'HTTP status mal byť 400!');
	echo "     ✅ Falšovaný podpis bol úspešne zachytený a zamietnutý (HTTP 400).\n";

	// =========================================================================
	// Scenár 12.3: Ochrana pred Replay Attackom (expirovaný timestamp > 300s)
	// =========================================================================
	echo "  [Scenár 12.3] Ochrana pred Replay Attackom (expirovaný timestamp > 300 sekúnd)...\n";
	$expired_timestamp = time() - 360; // 6 minút dozadu
	$expired_sig       = hash_hmac('sha256', $expired_timestamp . '.' . $payload_12_1, $secret);
	$expired_header    = "t={$expired_timestamp},v1={$expired_sig}";

	$req_replay = new WP_REST_Request('POST', '/vorkei/v1/stripe-webhook');
	$req_replay->set_body($payload_12_1);
	$req_replay->set_header('stripe-signature', $expired_header);

	$res_replay = $controller->handle_stripe_webhook($req_replay);
	assert(is_wp_error($res_replay), 'Replay attack s expirovaným timestampom mal vrátiť WP_Error!');
	assert($res_replay->get_error_code() === 'stripe_invalid_signature', 'Nesprávny kód chyby pre replay attack!');
	echo "     ✅ Replay attack bol odmietnutý na základe 300-sekundovej tolerancie.\n";

	// =========================================================================
	// Scenár 12.4: Úspešná platba (checkout.session.completed + paid)
	// =========================================================================
	echo "  [Scenár 12.4] Spracovanie potvrdenej platby -> auto-vytvorenie peňaženky a kreditov...\n";
	$session_paid = [
		'id'             => 'cs_test_session_paid_100',
		'payment_status' => 'paid',
		'payment_intent' => 'pi_test_intent_paid_100',
		'amount_total'   => 5000, // 50.00 EUR -> 10 kreditov
		'currency'       => 'eur',
		'customer_email' => 'novy.klient@flotila-vorkei.sk',
		'customer_details' => [
			'name'  => 'Miroslav Novák',
			'email' => 'novy.klient@flotila-vorkei.sk',
		],
		'metadata'       => [
			'credits' => '10',
		],
	];
	$payload_paid   = json_encode([
		'id'   => 'evt_test_paid_100',
		'type' => 'checkout.session.completed',
		'data' => [ 'object' => $session_paid ],
	]);
	$ts_paid  = time();
	$sig_paid = hash_hmac('sha256', $ts_paid . '.' . $payload_paid, $secret);

	$req_paid = new WP_REST_Request('POST', '/vorkei/v1/stripe-webhook');
	$req_paid->set_body($payload_paid);
	$req_paid->set_header('stripe-signature', "t={$ts_paid},v1={$sig_paid}");

	$res_paid = $controller->handle_stripe_webhook($req_paid);
	assert(!is_wp_error($res_paid), 'Spracovanie úspešnej platby zlyhalo!');
	$data_paid = $res_paid->get_data();
	assert($data_paid['received'] === true, 'Received flag chýba!');
	assert($data_paid['result']['status'] === 'completed', 'Stav transakcie mal byť completed!');
	assert($data_paid['result']['credits'] === 10, 'Kredity mali byť 10!');

	// Overenie v mock databáze
	assert(count($wpdb->tokens) === 1, 'Peňaženka mala byť vytvorená v wp_vorkei_client_tokens!');
	$wallet = reset($wpdb->tokens);
	assert($wallet['client_email'] === 'novy.klient@flotila-vorkei.sk', 'Email peňaženky nesedí!');
	assert($wallet['credits_balance'] === 10, 'Zostatok peňaženky mal byť presne 10 kreditov!');
	assert(strpos($wallet['token_key'], 'VRK-') === 0, 'API token peňaženky musí mať prefix VRK-!');

	assert(count($wpdb->transactions) === 1, 'Transakcia mala byť zapísaná v wp_vorkei_credit_transactions!');
	$tx = reset($wpdb->transactions);
	assert($tx['stripe_event_id'] === 'evt_test_paid_100', 'Stripe Event ID nesedí!');
	assert($tx['payment_status'] === 'completed', 'Stav transakcie v DB mal byť completed!');
	echo "     ✅ Peňaženka zákazníka automaticky vytvorená ({$wallet['token_key']}), pripísaných 10 kreditov.\n";

	// =========================================================================
	// Scenár 12.5: Idempotencia a ochrana proti duplicitám (opakovaný webhook)
	// =========================================================================
	echo "  [Scenár 12.5] Test idempotencie pri opakovanom doručení identického webhooku...\n";
	$res_duplicate = $controller->handle_stripe_webhook($req_paid);
	assert(!is_wp_error($res_duplicate), 'Opakovaný webhook spôsobil chybu!');
	$data_duplicate = $res_duplicate->get_data();
	assert($data_duplicate['result']['status'] === 'already_processed', 'Duplikát mal vrátiť status already_processed!');

	// DÔLEŽITÉ: Peňaženka nesmie mať 20 kreditov, ale stále len pôvodných 10!
	$wallet_after = reset($wpdb->tokens);
	assert($wallet_after['credits_balance'] === 10, 'Chyba idempotencie! Kredity boli pripísané viackrát!');
	assert(count($wpdb->transactions) === 1, 'Nesmie vzniknúť duplicitný záznam transakcie!');
	echo "     ✅ Idempotencia 100% potvrdená: opakované volanie vráti already_processed bez zmeny kreditu.\n";

	// =========================================================================
	// Scenár 12.6: Oneskorená platba (Async SEPA / Bank Transfer)
	// =========================================================================
	echo "  [Scenár 12.6] Oneskorená platba (unpaid -> pending; async_payment_succeeded -> completed)...\n";
	$session_async = [
		'id'             => 'cs_test_session_async_200',
		'payment_status' => 'unpaid', // Zatiaľ nezaplatené (napr. čaká sa na SEPA prevod)
		'payment_intent' => 'pi_test_async_200',
		'amount_total'   => 10000, // 100.00 EUR -> 20 kreditov
		'currency'       => 'eur',
		'customer_email' => 'sepa.zakaznik@autopozicovna.sk',
		'customer_details' => [
			'name'  => 'Ing. Juraj Kováč',
			'email' => 'sepa.zakaznik@autopozicovna.sk',
		],
		'metadata'       => [
			'credits' => '20',
		],
	];

	$payload_async   = json_encode([
		'id'   => 'evt_test_async_200',
		'type' => 'checkout.session.completed',
		'data' => [ 'object' => $session_async ],
	]);
	$ts_async  = time();
	$sig_async = hash_hmac('sha256', $ts_async . '.' . $payload_async, $secret);

	$req_async = new WP_REST_Request('POST', '/vorkei/v1/stripe-webhook');
	$req_async->set_body($payload_async);
	$req_async->set_header('stripe-signature', "t={$ts_async},v1={$sig_async}");

	$res_async = $controller->handle_stripe_webhook($req_async);
	assert(!is_wp_error($res_async), 'Spracovanie unpaid async platby zlyhalo!');
	$data_async = $res_async->get_data();
	assert($data_async['result']['status'] === 'pending', 'Stav transakcie mal byť pending!');
	assert($data_async['result']['credits'] === 0, 'Kredity nesmú byť pripísané pri unpaid!');

	// Overenie, že peňaženka zatiaľ NEBOLA dobitá
	$sepa_wallet = null;
	foreach ($wpdb->tokens as $tok) {
		if ($tok['client_email'] === 'sepa.zakaznik@autopozicovna.sk') {
			$sepa_wallet = $tok;
			break;
		}
	}
	assert($sepa_wallet === null, 'Pre unpaid platbu nemala vzniknúť aktívna peňaženka s kreditmi!');

	// Následne o 2 dni dorazí: checkout.session.async_payment_succeeded
	echo "     ⏳ Simulácia príchodu potvrdenia: checkout.session.async_payment_succeeded...\n";
	$session_async_success = $session_async;
	$session_async_success['payment_status'] = 'paid';

	$payload_success = json_encode([
		'id'   => 'evt_test_async_success_201',
		'type' => 'checkout.session.async_payment_succeeded',
		'data' => [ 'object' => $session_async_success ],
	]);
	$ts_succ  = time();
	$sig_succ = hash_hmac('sha256', $ts_succ . '.' . $payload_success, $secret);

	$req_succ = new WP_REST_Request('POST', '/vorkei/v1/stripe-webhook');
	$req_succ->set_body($payload_success);
	$req_succ->set_header('stripe-signature', "t={$ts_succ},v1={$sig_succ}");

	$res_succ = $controller->handle_stripe_webhook($req_succ);
	assert(!is_wp_error($res_succ), 'Spracovanie async_payment_succeeded zlyhalo!');
	$data_succ = $res_succ->get_data();
	assert($data_succ['result']['status'] === 'completed', 'Stav po úhrade mal byť completed!');
	assert($data_succ['result']['credits'] === 20, 'Kredity po úhrade mali byť 20!');

	// Overenie, že peňaženka bola vytvorená a má 20 kreditov
	foreach ($wpdb->tokens as $tok) {
		if ($tok['client_email'] === 'sepa.zakaznik@autopozicovna.sk') {
			$sepa_wallet = $tok;
			break;
		}
	}
	assert($sepa_wallet !== null, 'Peňaženka pre SEPA zákazníka nebola nájdená!');
	assert($sepa_wallet['credits_balance'] === 20, 'SEPA zákazník mal dostať presne 20 kreditov!');
	echo "     ✅ Oneskorená platba úspešne doriešená: stav prešiel z pending do completed, 20 kreditov pripísaných.\n";

	// =========================================================================
	// Scenár 12.7: Zlyhaná platba (async_payment_failed)
	// =========================================================================
	echo "  [Scenár 12.7] Zlyhaná platba (checkout.session.async_payment_failed)...\n";
	// Najprv vytvoríme pending transakciu pre zlyhanú session
	$session_failed = [
		'id'             => 'cs_test_session_failed_300',
		'payment_status' => 'unpaid',
		'payment_intent' => 'pi_test_failed_300',
		'amount_total'   => 500,
		'currency'       => 'eur',
		'customer_email' => 'odmietnuty@klient.sk',
	];
	$payload_fail_init = json_encode([
		'id'   => 'evt_test_failed_init_300',
		'type' => 'checkout.session.completed',
		'data' => [ 'object' => $session_failed ],
	]);
	$ts_fi  = time();
	$sig_fi = hash_hmac('sha256', $ts_fi . '.' . $payload_fail_init, $secret);
	$req_fi = new WP_REST_Request('POST', '/vorkei/v1/stripe-webhook');
	$req_fi->set_body($payload_fail_init);
	$req_fi->set_header('stripe-signature', "t={$ts_fi},v1={$sig_fi}");
	$controller->handle_stripe_webhook($req_fi);

	// Dorazí async_payment_failed
	$payload_fail = json_encode([
		'id'   => 'evt_test_failed_event_301',
		'type' => 'checkout.session.async_payment_failed',
		'data' => [ 'object' => [ 'id' => 'cs_test_session_failed_300' ] ],
	]);
	$ts_f  = time();
	$sig_f = hash_hmac('sha256', $ts_f . '.' . $payload_fail, $secret);
	$req_f = new WP_REST_Request('POST', '/vorkei/v1/stripe-webhook');
	$req_f->set_body($payload_fail);
	$req_f->set_header('stripe-signature', "t={$ts_f},v1={$sig_f}");

	$res_fail = $controller->handle_stripe_webhook($req_f);
	assert(!is_wp_error($res_fail), 'Spracovanie async_payment_failed zlyhalo!');
	$data_fail = $res_fail->get_data();
	assert($data_fail['status'] === 'failed', 'Status mal byť failed!');

	// Overenie v DB
	$failed_tx = null;
	foreach ($wpdb->transactions as $tx) {
		if ($tx['stripe_session_id'] === 'cs_test_session_failed_300') {
			$failed_tx = $tx;
			break;
		}
	}
	assert($failed_tx !== null, 'Transakcia pre zlyhanú session sa nenašla!');
	assert($failed_tx['payment_status'] === 'failed', 'Stav transakcie v DB mal byť failed!');
	echo "     ✅ Zlyhaná platba korektne označená ako failed, žiadne kredity nepripísané.\n";

	// =========================================================================
	// Scenár 12.8: Mechanizmus manuálneho vyrovnania (Fallback Reconciliation)
	// =========================================================================
	echo "  [Scenár 12.8] Vyrovnanie platieb cez Stripe API (reconcile_stripe_session)...\n";
	$GLOBALS['mock_remote_get_handler'] = function($url, $args) {
		assert(strpos($url, 'https://api.stripe.com/v1/checkout/sessions/cs_reconcile_400') !== false, 'URL požiadavky na Stripe nesedí!');
		assert($args['headers']['Authorization'] === 'Bearer sk_test_fake_stripe_api_key_987654321', 'Chýba platný Stripe Bearer kľúč!');
		return [
			'response' => [ 'code' => 200 ],
			'body'     => json_encode([
				'id'             => 'cs_reconcile_400',
				'payment_status' => 'paid',
				'payment_intent' => 'pi_reconcile_400',
				'amount_total'   => 5000,
				'currency'       => 'eur',
				'customer_email' => 'stratena.platba@firma.sk',
				'customer_details' => [
					'name'  => 'Ing. Peter Stratený',
					'email' => 'stratena.platba@firma.sk',
				],
				'metadata'       => [
					'credits' => '10',
				],
			]),
		];
	};

	$reconcile_res = $controller->reconcile_stripe_session('cs_reconcile_400');
	assert($reconcile_res['success'] === true, 'Reconciliácia mala uspieť!');
	assert($reconcile_res['status'] === 'reconciled', 'Status mal byť reconciled!');
	assert($reconcile_res['credits'] === 10, 'Pripísaných malo byť 10 kreditov!');
	assert($reconcile_res['email'] === 'stratena.platba@firma.sk', 'Email zákazníka nesedí!');

	// Overenie idempotencie reconciliácie
	$reconcile_res2 = $controller->reconcile_stripe_session('cs_reconcile_400');
	assert($reconcile_res2['success'] === true, 'Opakovaná reconciliácia mala uspieť!');
	assert($reconcile_res2['status'] === 'already_completed', 'Opakovaná reconciliácia mala vrátiť already_completed!');
	echo "     ✅ Manuálne vyrovnanie úspešne overilo Stripe API a bezpečne pripísalo kredity.\n";

	// =========================================================================
	// Scenár 12.9: Admin endpoint pre zoznam kreditných transakcií
	// =========================================================================
	echo "  [Scenár 12.9] Audit transakcií cez REST API get_credit_transactions()...\n";
	$req_tx_list = new WP_REST_Request('GET', '/vorkei/v1/credit-transactions');
	$res_tx_list = $controller->get_credit_transactions($req_tx_list);
	assert($res_tx_list instanceof WP_REST_Response, 'Mal vrátiť WP_REST_Response!');
	$data_list = $res_tx_list->get_data();
	assert(isset($data_list['count']) && $data_list['count'] > 0, 'Počet transakcií musí byť > 0!');
	assert(count($data_list['transactions']) === $data_list['count'], 'Nezrovnalosť v počte záznamov transakcií!');
	echo "     ✅ Zoznam transakcií vrátil {$data_list['count']} auditovaných transakcií so všetkými náležitosťami.\n";

	// =========================================================================
	// Scenár 12.10: Spracovanie Vratky (charge.refunded -> clawback kreditov)
	// =========================================================================
	echo "  [Scenár 12.10] Vratka platby (charge.refunded) -> odpočet kreditov a pozastavenie pri 0...\n";
	$token_before_refund = reset($wpdb->tokens);
	$token_id_target     = $token_before_refund['id'];
	$balance_before      = (int)$token_before_refund['credits_balance'];

	$refund_payload = [
		'id'   => 'evt_refund_test_001',
		'type' => 'charge.refunded',
		'data' => [
			'object' => [
				'id'             => 'ch_refund_test_001',
				'payment_intent' => 'pi_test_intent_paid_100',
				'amount'         => 5000,
				'currency'       => 'eur',
				'status'         => 'succeeded',
			],
		],
	];

	$raw_refund_body = json_encode($refund_payload);
	$t_refund        = time();
	$sig_refund      = hash_hmac('sha256', $t_refund . '.' . $raw_refund_body, $secret);

	$req_refund = new WP_REST_Request('POST', '/vorkei/v1/stripe-webhook');
	$req_refund->set_body($raw_refund_body);
	$req_refund->set_header('stripe-signature', "t={$t_refund},v1={$sig_refund}");

	$res_refund = $controller->handle_stripe_webhook($req_refund);
	assert(!is_wp_error($res_refund), 'Webhook charge.refunded zlyhal s chybou: ' . (is_wp_error($res_refund) ? $res_refund->get_error_message() : ''));
	assert($res_refund instanceof WP_REST_Response, 'Webhook charge.refunded mal vrátiť WP_REST_Response!');
	assert($res_refund->get_status() === 200, 'Webhook charge.refunded mal vrátiť HTTP 200!');
	$refund_data = $res_refund->get_data();
	assert(($refund_data['status'] ?? '') === 'refunded', "Stav refundu mal byť 'refunded'!");

	$token_after_refund = $wpdb->tokens[$token_id_target];
	$balance_after      = (int)$token_after_refund['credits_balance'];
	assert($balance_after < $balance_before, "Zostatok kreditov mal po refunde klesnúť z {$balance_before}!");
	echo "     ✅ Vratka charge.refunded úspešne odpočítala kredity (z {$balance_before} na {$balance_after}).\n";

	// =========================================================================
	// Scenár 12.11: Platobný Spor (charge.dispute.created -> freeze, closed -> unfreeze)
	// =========================================================================
	echo "  [Scenár 12.11] Platobný spor (charge.dispute.created & charge.dispute.closed)...\n";
	$dispute_created_payload = [
		'id'   => 'evt_dispute_test_001',
		'type' => 'charge.dispute.created',
		'data' => [
			'object' => [
				'id'             => 'dp_test_001',
				'payment_intent' => 'pi_test_intent_paid_100',
				'amount'         => 5000,
				'currency'       => 'eur',
				'status'         => 'needs_response',
			],
		],
	];

	$raw_dispute_body = json_encode($dispute_created_payload);
	$t_dispute        = time();
	$sig_dispute      = hash_hmac('sha256', $t_dispute . '.' . $raw_dispute_body, $secret);

	$req_dispute = new WP_REST_Request('POST', '/vorkei/v1/stripe-webhook');
	$req_dispute->set_body($raw_dispute_body);
	$req_dispute->set_header('stripe-signature', "t={$t_dispute},v1={$sig_dispute}");

	$res_dispute = $controller->handle_stripe_webhook($req_dispute);
	assert(!is_wp_error($res_dispute), 'Dispute webhook zlyhal s chybou: ' . (is_wp_error($res_dispute) ? $res_dispute->get_error_message() : ''));
	assert(($res_dispute->get_data()['status'] ?? '') === 'disputed', "Stav po spore mal byť 'disputed'!");

	$token_disputed = $wpdb->tokens[$token_id_target];
	assert((int)$token_disputed['is_active'] === 0, 'Pri charge.dispute.created musí byť token dočasne zablokovaný (is_active = 0)!');
	echo "     ✅ Platobný spor úspešne zablokoval peňaženku zákazníka (is_active = 0).\n";

	// Simulácia vyhratého sporu: charge.dispute.closed s status: won
	$dispute_won_payload = [
		'id'   => 'evt_dispute_won_001',
		'type' => 'charge.dispute.closed',
		'data' => [
			'object' => [
				'id'             => 'dp_test_001',
				'payment_intent' => 'pi_test_intent_paid_100',
				'status'         => 'won',
			],
		],
	];

	$raw_won_body = json_encode($dispute_won_payload);
	$t_won        = time();
	$sig_won      = hash_hmac('sha256', $t_won . '.' . $raw_won_body, $secret);

	$req_won = new WP_REST_Request('POST', '/vorkei/v1/stripe-webhook');
	$req_won->set_body($raw_won_body);
	$req_won->set_header('stripe-signature', "t={$t_won},v1={$sig_won}");

	$res_won = $controller->handle_stripe_webhook($req_won);
	assert(!is_wp_error($res_won), 'Dispute won webhook zlyhal s chybou: ' . (is_wp_error($res_won) ? $res_won->get_error_message() : ''));
	assert(($res_won->get_data()['outcome'] ?? '') === 'won', "Výsledok sporu mal byť 'won'!");

	$token_restored = $wpdb->tokens[$token_id_target];
	assert((int)$token_restored['is_active'] === 1, 'Vyhratý spor musí automaticky odblokovať peňaženku (is_active = 1)!');
	echo "     ✅ Vyhratý spor (charge.dispute.closed: won) úspešne odblokoval peňaženku zákazníka (is_active = 1).\n";

	echo "\n=============================================================\n";
	echo "🏆 VŠETKÝCH 11 SCENÁROV ÚROVNE 12 BOLO ÚSPEŠNE OVERENÝCH NA 100%!\n";
	echo "=============================================================\n";
	exit(0);
}
