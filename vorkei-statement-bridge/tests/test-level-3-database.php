<?php
/**
 * Test Level 3: Databázová štruktúra, dbDelta pravidlá & Migrácie
 */

echo "🔍 [TEST 3] Databázová štruktúra, dbDelta pravidlá & Migrácie...\n";

define('ABSPATH', '/var/www/html/wp/');

// Mock $wpdb
class MockWPDB {
	public string $prefix = 'wp_';
	public array $executed_queries = [];
	public array $options = [];

	public function get_charset_collate(): string {
		return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
	}

	public function query(string $query) {
		$this->executed_queries[] = $query;
		return true;
	}
}

global $wpdb;
$wpdb = new MockWPDB();

$GLOBALS['dbdelta_queries'] = [];
$GLOBALS['options_storage'] = [];

function dbDelta($queries) {
	if (is_array($queries)) {
		$GLOBALS['dbdelta_queries'] = array_merge($GLOBALS['dbdelta_queries'], $queries);
	} else {
		$GLOBALS['dbdelta_queries'][] = $queries;
	}
	return [];
}

function update_option($name, $value) {
	$GLOBALS['options_storage'][$name] = $value;
	return true;
}

function get_option($name, $default = false) {
	return $GLOBALS['options_storage'][$name] ?? $default;
}

function delete_option($name) {
	unset($GLOBALS['options_storage'][$name]);
	return true;
}

function delete_transient($name) {
	return true;
}

// Načítanie triedy Activator
require_once dirname(__DIR__) . '/includes/class-vorkei-activator.php';

// Spustenie aktivácie
\Vorkei_Activator::activate();

// 1. Overenie, že dbDelta dostala presne 3 CREATE TABLE SQL príkazy
assert(count($GLOBALS['dbdelta_queries']) === 3, "dbDelta mala dostať 3 SQL dotazy, dostala: " . count($GLOBALS['dbdelta_queries']));
echo "  ✓ dbDelta prijala SQL dotazy pre hlavičky výpisov, pohyby aj forenzný audit log\n";

$sql_statements = $GLOBALS['dbdelta_queries'][0];
$sql_movements  = $GLOBALS['dbdelta_queries'][1];
$sql_audit      = $GLOBALS['dbdelta_queries'][2];

// 2. Striktná kontrola pravidiel dbDelta:
// Pravidlo A: Dve medzery za PRIMARY KEY
assert(str_contains($sql_statements, 'PRIMARY KEY  (id)'), "Statements tabuľka porušuje dbDelta pravidlo: musí mať dve medzery za PRIMARY KEY");
assert(str_contains($sql_movements, 'PRIMARY KEY  (id)'), "Movements tabuľka porušuje dbDelta pravidlo: musí mať dve medzery za PRIMARY KEY");
assert(str_contains($sql_audit, 'PRIMARY KEY  (id)'), "Audit log tabuľka porušuje dbDelta pravidlo: musí mať dve medzery za PRIMARY KEY");
echo "  ✓ Pravidlo dbDelta: Dve medzery za PRIMARY KEY dodržané (PRIMARY KEY  (id))\n";

// Pravidlo B: Lowercase dátové typy
$uppercaseTypes = ['VARCHAR', 'BIGINT', 'DATETIME', 'TEXT', 'INT'];
foreach ($uppercaseTypes as $type) {
	assert(!str_contains($sql_statements, $type), "Statements SQL obsahuje veľké písmená dátového typu: $type");
	assert(!str_contains($sql_movements, $type), "Movements SQL obsahuje veľké písmená dátového typu: $type");
	assert(!str_contains($sql_audit, $type), "Audit log SQL obsahuje veľké písmená dátového typu: $type");
}
echo "  ✓ Pravidlo dbDelta: Dátové typy sú výhradne lowercase (varchar, bigint, datetime, text)\n";

// Pravidlo C: Indexy a kľúče
assert(str_contains($sql_statements, 'KEY iban_idx'), "Chýba index KEY iban_idx");
assert(str_contains($sql_movements, 'UNIQUE KEY hash_idx (movement_hash)'), "Chýba unikátny kľúč UNIQUE KEY hash_idx");
assert(str_contains($sql_movements, 'KEY statement_idx (statement_id)'), "Chýba index KEY statement_idx");
assert(str_contains($sql_movements, 'KEY vs_idx (variable_symbol)'), "Chýba index KEY vs_idx");
assert(str_contains($sql_movements, 'KEY matched_order_idx (matched_order_id)'), "Chýba index KEY matched_order_idx");
assert(str_contains($sql_audit, 'KEY statement_idx (statement_id)'), "Chýba index KEY statement_idx v audit logu");
assert(str_contains($sql_audit, 'KEY ip_idx (ip_address)'), "Chýba index KEY ip_idx v audit logu");
assert(str_contains($sql_audit, 'KEY fee_status_idx (fee_status)'), "Chýba index KEY fee_status_idx v audit logu");
echo "  ✓ Všetky výkonnostné a relačné indexy sú korektne definované\n";

// 3. Overenie predvolených nastavení
assert(get_option('vorkei_bridge_db_version') === '1.1.0', 'db_version nesedí');
assert(get_option('vorkei_bridge_enable_wc_matching') === 'yes', 'enable_wc_matching nesedí');
assert(get_option('vorkei_bridge_wc_target_status') === 'processing', 'wc_target_status nesedí');
assert(get_option('vorkei_bridge_verify_amount') === 'yes', 'verify_amount nesedí');
assert(get_option('vorkei_bridge_add_order_note') === 'yes', 'add_order_note nesedí');
assert(get_option('vorkei_bridge_delete_on_uninstall') === 'no', 'delete_on_uninstall predvolene nesedí');
echo "  ✓ Inicializácia predvolených volieb v wp_options prebehla úspešne\n";

// 4. Test odinštalovania (uninstall.php)
// Test A: Bezpečný režim (delete_on_uninstall = no) -> tabuľky NESMÚ byť zmazané
define('WP_UNINSTALL_PLUGIN', true);
$wpdb->executed_queries = [];
require dirname(__DIR__) . '/uninstall.php';
assert(empty($wpdb->executed_queries), 'Tabuľky boli zmazané, hoci nastavenie bolo delete_on_uninstall=no!');
echo "  ✓ uninstall.php v bezpečnom režime správne zachováva používateľské dáta\n";

// Test B: Mazací režim (delete_on_uninstall = yes) -> tabuľky sa zmažú
update_option('vorkei_bridge_delete_on_uninstall', 'yes');
require dirname(__DIR__) . '/uninstall.php';
assert(count($wpdb->executed_queries) === 3, 'Tabuľky mali byť zmazané cez DROP TABLE');
assert(!isset($GLOBALS['options_storage']['vorkei_bridge_db_version']), 'Voľby mali byť vyčistené');
echo "  ✓ uninstall.php pri delete_on_uninstall=yes úspešne a čisto odstránil všetky tabuľky aj options\n";

echo "🎉 TEST 3 ÚSPEŠNÝ: Databázové schéma, dbDelta a migrácie sú stopercentné.\n\n";
exit(0);
