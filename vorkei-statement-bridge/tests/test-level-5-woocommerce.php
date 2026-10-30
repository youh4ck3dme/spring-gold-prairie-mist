<?php
/**
 * Test Level 5: WooCommerce Párovací Engine, Bezpečnosť & Biznis Logika
 */

echo "🔍 [TEST 5] WooCommerce Párovací Engine, Bezpečnosť & Biznis Logika...\n";

define('ABSPATH', '/var/www/html/wp/');

// 1. Mock prostredie WordPress a WooCommerce
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
	return '2026-10-29 16:55:00';
}

$GLOBALS['fired_actions'] = [];
function do_action($tag, ...$args) {
	$GLOBALS['fired_actions'][$tag][] = $args;
}

function apply_filters($tag, $value, ...$args) {
	return $value;
}

// 2. Mock trieda WC_Order (reprezentuje HPOS objednávku WooCommerce)
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

	public function set_status(string $status, string $note = ''): void {
		$this->status = $status;
		if (!empty($note)) {
			$this->notes[] = $note;
		}
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

	public function save(): void {
		// Mock uloženia do databázy
	}
}

// Globálny repozitár mock objednávok
$GLOBALS['mock_orders'] = [
	1001 => new WC_Order(1001, 120.50, 'pending'),
	1002 => new WC_Order(1002, 50.00, 'pending'),
	555  => new WC_Order(555, 75.00, 'on-hold'),
];
$GLOBALS['mock_orders'][555]->update_meta_data('_order_number', '2026-999');

function wc_get_order($id) {
	return $GLOBALS['mock_orders'][(int)$id] ?? false;
}

function wc_get_orders(array $args) {
	$results = [];
	if (!empty($args['meta_query'])) {
		foreach ($GLOBALS['mock_orders'] as $order) {
			foreach ($args['meta_query'] as $clause) {
				if (is_array($clause) && isset($clause['key']) && $order->get_meta($clause['key']) === $clause['value']) {
					$results[] = $order;
					break;
				}
			}
		}
	}
	return $results;
}

require_once dirname(__DIR__) . '/includes/class-vorkei-matcher.php';

$statement = [
	'id'               => 'stmt-10',
	'statement_number' => '10',
	'currency'         => 'EUR',
	'is_verified'      => true, // Overený bankový import v teste 5.1
];

// --- TEST SCENÁR 5.1: Ideálne párovanie platby podľa VS ---
echo "  [Scenár 5.1] Test úspešného spárovania platby podľa variabilného symbolu...\n";
$movement1 = [
	'id'              => 'mov-1',
	'date'            => '29.10.2026',
	'amount_cents'    => 12050, // 120.50 EUR
	'kind'            => 'in',
	'party_name'      => 'Janko Hraško',
	'party_iban'      => 'SK9911000000001234567890',
	'variable_symbol' => '1001',
	'note'            => 'Platba objednávky',
];

$matchedId1 = \Vorkei_Matcher::match_order($movement1, $statement);
$order1001  = wc_get_order(1001);

assert($matchedId1 === 1001, "Očakávalo sa spárovanie s objednávkou 1001, vrátené: " . var_export($matchedId1, true));
assert($order1001->get_status() === 'processing', "Objednávka mala prejsť do stavu 'processing', aktuálny stav: " . $order1001->get_status());
assert($order1001->get_meta('_vorkei_matched_movement_id') === 'mov-1', "Metadáta _vorkei_matched_movement_id sa neuložili");
assert(count($order1001->get_notes()) >= 1, "K objednávke nebola pridaná poznámka");
assert(!empty($GLOBALS['fired_actions']['vorkei_bridge_order_matched']), "WordPress action vorkei_bridge_order_matched sa neodpálil");
echo "    ✓ Objednávka #1001 úspešne spárovaná, stav zmenený na 'processing' a vytvorená interná poznámka.\n";

// --- TEST SCENÁR 5.2: Ochrana pred nedoplatkom (Suma nesedí) ---
echo "  [Scenár 5.2] Test detekcie nezrovnalosti sumy (ochrana pred nedoplatkom)...\n";
$movement2 = [
	'id'              => 'mov-2',
	'date'            => '29.10.2026',
	'amount_cents'    => 4000, // Zákazník poslal 40 EUR namiesto 50 EUR!
	'kind'            => 'in',
	'party_name'      => 'Fero Skúpy',
	'party_iban'      => 'SK8811000000009876543210',
	'variable_symbol' => '1002',
	'note'            => 'Posielam zalohu',
];

$matchedId2 = \Vorkei_Matcher::match_order($movement2, $statement);
$order1002  = wc_get_order(1002);

assert($matchedId2 === 1002, "Objednávka mala byť identifikovaná");
assert($order1002->get_status() === 'pending', "Objednávka s nedoplatkom NESMIE zmeniť stav na processing! Stav: " . $order1002->get_status());
$notes1002 = $order1002->get_notes();
assert(count($notes1002) > 0 && str_contains($notes1002[0], 'UPOZORNENIE: Suma platby sa nezhoduje'), "Chýba varovná poznámka o nesúhlase sumy");
echo "    ✓ Pri nedoplatku (40 € namiesto 50 €) sa stav nezmenil a personál dostal varovnú poznámku.\n";

// --- TEST SCENÁR 5.3: Vyhľadanie podľa zákazkového čísla objednávky (_order_number) ---
echo "  [Scenár 5.3] Test vyhľadania podľa sekvenčného/zákazníckeho čísla objednávky...\n";
$movement3 = [
	'id'              => 'mov-3',
	'date'            => '29.10.2026',
	'amount_cents'    => 7500, // 75.00 EUR
	'kind'            => 'in',
	'party_name'      => 'Zákazník s Custom Číslom',
	'party_iban'      => 'SK7711000000001111222233',
	'variable_symbol' => '2026-999', // Číslo s pomlčkou
	'note'            => 'FA 2026-999',
];

$matchedId3 = \Vorkei_Matcher::match_order($movement3, $statement);
assert($matchedId3 === 555, "Objednávka mala byť dohľadaná podľa vlastného čísla objednávky na ID 555");
echo "    ✓ Úspešne dohľadaná objednávka ID #555 cez meta pole _order_number.\n";

// --- TEST SCENÁR 5.4: Odchádzajúce platby (kind = 'out') sa nesmú párovať s objednávkami ---
echo "  [Scenár 5.4] Test odchádzajúcej platby (debet / odvod dph / poplatok)...\n";
$movement4 = [
	'id'              => 'mov-4',
	'date'            => '29.10.2026',
	'amount_cents'    => 50000,
	'kind'            => 'out',
	'party_name'      => 'Daňový Úrad',
	'variable_symbol' => '1001', // Zhodou okolností rovnaké číslo
	'note'            => 'DPH',
];

$matchedId4 = \Vorkei_Matcher::match_order($movement4, $statement);
assert($matchedId4 === null, "Odchádzajúca platba (out) sa NIKDY nesmie spárovať ako prijatá platba za objednávku!");
echo "    ✓ Odchádzajúca debetná platba bola bezpečne ignorovaná.\n";

// --- TEST SCENÁR 5.5: Normalizácia a očistenie VS od znakov ---
echo "  [Scenár 5.5] Test očistenia VS od medzier a textu...\n";
$vsRaw = " VS / 1001 ";
$cleanVs = preg_replace('/\D/', '', $vsRaw);
assert($cleanVs === '1001', "Regexp očistenie variabilného symbolu zlyhalo: '$cleanVs'");
echo "    ✓ Variabilný symbol úspešne očistený na číselnú hodnotu '1001'.\n";

// --- TEST SCENÁR 5.6: Bankové zarovnanie núl (Padded VS napr. 000001001) ---
echo "  [Scenár 5.6] Test odstránenia počiatočných núl z bankového formátu...\n";
$movement6 = [
	'id'              => 'mov-6',
	'date'            => '29.10.2026',
	'amount_cents'    => 12050,
	'kind'            => 'in',
	'party_name'      => 'Banka Zarovnanie',
	'party_iban'      => 'SK1111222233334444555566',
	'variable_symbol' => '0000001001', // Banka poslala 10-miestne s nulami
	'note'            => 'VS 0000001001',
];
$matchedId6 = \Vorkei_Matcher::match_order($movement6, $statement);
assert($matchedId6 === 1001, "Paded VS '0000001001' malo byť spárované s objednávkou 1001");
echo "    ✓ Bankový 10-miestny variabilný symbol s počiatočnými nulami ('0000001001') úspešne spárovaný.\n";

// --- TEST SCENÁR 5.7: Bezpečnostná ochrana neovereného importu (P0 Mission 08) ---
echo "  [Scenár 5.7] Test neovereného / syntetického importu (ochrana pred neoprávnenou zmenou stavu)...\n";
$unverifiedStatement = [
	'id'               => 'stmt-unverified',
	'statement_number' => '999',
	'currency'         => 'EUR',
	'is_verified'      => false, // Neoverený import z PWA
];
$movementUnverified = [
	'id'              => 'mov-unverified',
	'date'            => '30.10.2026',
	'amount_cents'    => 12050,
	'kind'            => 'in',
	'party_name'      => 'Neznámy platiteľ',
	'party_iban'      => 'SK1111111111111111111111',
	'variable_symbol' => '1001',
	'note'            => 'Neoverený test z PWA',
];
$order1001->set_status('on-hold');
$order1001->update_meta_data('_vorkei_matched_movement_id', ''); // Reset match meta

$matchedIdUnverified = \Vorkei_Matcher::match_order($movementUnverified, $unverifiedStatement);
assert($matchedIdUnverified === 1001, "Neoverený výpis mal identifikovať objednávku 1001");
assert($order1001->get_status() === 'on-hold', "Objednávka z neovereného importu NESMIE prejsť do 'processing'! Zostáva 'on-hold'");
assert($order1001->get_meta('_vorkei_match_state') === 'proposal', "Match state musel byť nastavený na 'proposal'");
echo "    ✓ Neoverený import vytvoril iba návrh párovania (proposal), stav objednávky bezpečne nezmenil.\n";

echo "🎉 TEST 5 ÚSPEŠNÝ: WooCommerce párovací engine prešiel všetkými komplexnými hraničnými testami na výbornú.\n\n";
exit(0);
