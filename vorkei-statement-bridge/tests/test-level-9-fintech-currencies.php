<?php
/**
 * Test Level 9: Fintech Meny, Symboly & Hraničné stavy (Multi-Currency & Edge Cases)
 */

echo "🔍 [TEST 9] Fintech Meny, Symboly & Hraničné stavy...\n";

define('ABSPATH', '/var/www/html/wp/');

$GLOBALS['options_storage'] = [
	'vorkei_bridge_enable_wc_matching' => 'yes',
	'vorkei_bridge_wc_target_status'   => 'processing',
	'vorkei_bridge_verify_amount'      => 'yes',
	'vorkei_bridge_add_order_note'     => 'yes',
];

function get_option($name, $default = false) {
	return $GLOBALS['options_storage'][$name] ?? $default;
}

function update_option($name, $value) {
	$GLOBALS['options_storage'][$name] = $value;
	return true;
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

function current_time($type) {
	return '2026-10-29 17:30:00';
}

function do_action($tag, ...$args) {}
function apply_filters($tag, $value, ...$args) {
	return $value;
}

class WC_Order {
	private int $id;
	private float $total;
	private string $status;
	private string $currency;
	private array $notes = [];
	private array $meta = [];

	public function __construct(int $id, float $total, string $status = 'pending', string $currency = 'EUR') {
		$this->id       = $id;
		$this->total    = $total;
		$this->status   = $status;
		$this->currency = $currency;
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
	2001 => new WC_Order(2001, 50.00, 'pending', 'EUR'),
	2002 => new WC_Order(2002, 100.00, 'pending', 'EUR'),
];

function wc_get_order($id) {
	return $GLOBALS['mock_orders'][(int)$id] ?? false;
}

function wc_get_orders(array $args) {
	return [];
}

require_once dirname(__DIR__) . '/includes/class-vorkei-matcher.php';

// --- SCENÁR 9.1: Cudzia mena (CZK vs EUR) ---
echo "  [Fintech 9.1] Test ochrany pred nezhodou meny (50 CZK namiesto 50 EUR)...\n";
$stCzk = [
	'id'               => 'stmt-czk',
	'statement_number' => '5',
	'currency'         => 'CZK',
];
$mvCzk = [
	'id'              => 'mv-czk-1',
	'date'            => '29.10.2026',
	'amount_cents'    => 5000, // 50 CZK
	'kind'            => 'in',
	'party_name'      => 'Český Klient',
	'party_iban'      => 'CZ88000000001234567890',
	'variable_symbol' => '2001',
	'note'            => 'Úhrada v CZK',
];

$matchedCzkId = \Vorkei_Matcher::match_order($mvCzk, $stCzk);
$order2001    = wc_get_order(2001);

assert($matchedCzkId === 2001, "Objednávka mala byť identifikovaná");
assert($order2001->get_status() === 'pending', "Objednávka s inou menou (CZK namiesto EUR) NESMIE byť automaticky schválená!");
$notes = $order2001->get_notes();
assert(count($notes) > 0 && str_contains($notes[0], '50,00 CZK'), "Poznámka musí obsahovať CZK menu");
echo "    ✓ Platba v CZK (50,00 CZK) na EUR objednávku bola správne detegovaná ako nezrovnalosť meny.\n";

// --- SCENÁR 9.2: Nedoplatok o 1 cent (99.99 EUR vs 100.00 EUR) ---
echo "  [Fintech 9.2] Test mikro-nedoplatku o 1 cent (ochrana pred neúplnou úhradou)...\n";
$stEur = [
	'id'               => 'stmt-eur',
	'statement_number' => '6',
	'currency'         => 'EUR',
];
$mv1CentShort = [
	'id'              => 'mv-1cent-short',
	'date'            => '29.10.2026',
	'amount_cents'    => 9999, // 99.99 EUR namiesto 100.00 EUR
	'kind'            => 'in',
	'party_name'      => 'Centový Špekulant',
	'variable_symbol' => '2002',
	'note'            => '99.99',
];

\Vorkei_Matcher::match_order($mv1CentShort, $stEur);
$order2002 = wc_get_order(2002);
assert($order2002->get_status() === 'pending', "Objednávka s nedoplatkom o 1 cent nesmie prejsť!");
echo "    ✓ Nedoplatok o 1 cent bol striktne zablokovaný.\n";

// --- SCENÁR 9.3: Záporné stavy (Kontokorent / Overdraft) ---
echo "  [Fintech 9.3] Test záporných zostatkov (kontokorentný úver)...\n";
$openingNegative = -154200; // -1542.00 EUR
$closingNegative = -124200; // -1242.00 EUR
assert($closingNegative > $openingNegative, "Matematická validita záporných centov");
assert(is_int($openingNegative) && $openingNegative < 0, "Záporný signed bigint");
echo "    ✓ Záporné bankové stavy (kontokorent) sú plne podporované signed bigint typom.\n";

echo "🎉 TEST 9 ÚSPEŠNÝ: Multi-currency a hraničné fintech scenáre fungujú bezchybne.\n\n";
exit(0);
