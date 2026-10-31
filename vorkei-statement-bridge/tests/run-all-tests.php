<?php
/**
 * Master Test Runner: Vorkei Statement Bridge (Úrovne 1 až 12)
 */

$testFiles = [
	1  => 'test-level-1-syntax.php',
	2  => 'test-level-2-bootstrap.php',
	3  => 'test-level-3-database.php',
	4  => 'test-level-4-rest-api.php',
	5  => 'test-level-5-woocommerce.php',
	6  => 'test-level-6-stress-concurrency.php',
	7  => 'test-level-7-security-pentest.php',
	8  => 'test-level-8-chaos-resilience.php',
	9  => 'test-level-9-fintech-currencies.php',
	10 => 'test-level-10-e2e-app-contract.php',
	11 => 'test-level-11-auto-updates.php',
	12 => 'test-level-12-stripe-webhook.php',
	13 => 'test-level-13-trust-boundary-concurrency.php',
];

echo "=============================================================\n";
echo "🧪 SPUSTENIE 13-STUPŇOVEJ TESTOVACEJ PYRAMÍDY VORKEI BRIDGE\n";
echo "   Úrovne 1 (Základ / syntax) až 13 (Hranica Dôvery & Concurrency)\n";
echo "=============================================================\n\n";

$results = [];

foreach ($testFiles as $level => $file) {
	$path = __DIR__ . '/' . $file;
	echo "-------------------------------------------------------------\n";
	$cmd = "npx -y @php-wasm/cli " . escapeshellarg($path) . " 2>&1";
	$output = [];
	$exitCode = 0;
	exec($cmd, $output, $exitCode);
	
	echo implode("\n", $output) . "\n";
	
	if ($exitCode === 0) {
		$results[$level] = 'PASS';
	} else {
		$results[$level] = 'FAIL';
		echo "💥 Testovacia úroveň $level zlyhala! Prerušujem beh.\n";
		exit(1);
	}
}

echo "=============================================================\n";
echo "📊 SÚHRNNÝ REPORT TESTOVACIEHO BEHU (1 až 12):\n";
echo "=============================================================\n";
echo "  [Úroveň 1]  Lint, Súbory & PHP Syntaktická Integrita : ✅ PASS\n";
echo "  [Úroveň 2]  WordPress Sandbox & Hook Registrácia     : ✅ PASS\n";
echo "  [Úroveň 3]  Databázová Štruktúra & dbDelta Pravidlá : ✅ PASS\n";
echo "  [Úroveň 4]  REST API Kontrakt & Payload Validácia    : ✅ PASS\n";
echo "  [Úroveň 5]  WooCommerce Párovací Engine (VS Základ)  : ✅ PASS\n";
echo "  [Úroveň 6]  Záťažový test & 1000+ pohybov Concurrency: ✅ PASS\n";
echo "  [Úroveň 7]  Bezpečnostný Pen-test (SQLi, XSS, Auth)  : ✅ PASS\n";
echo "  [Úroveň 8]  Chaos Engineering & Odolnosť voči pádu  : ✅ PASS\n";
echo "  [Úroveň 9]  Fintech Meny (CZK/EUR) & Centové stavy   : ✅ PASS\n";
echo "  [Úroveň 10] E2E Kontrakt: Aplikácia Výpis ➔ WC Eshop : ✅ PASS\n";
echo "  [Úroveň 11] Auto-Updater: api.vorkei.com Aktualizácie : ✅ PASS\n";
echo "  [Úroveň 12] Stripe Webhook, Idempotencia & Wallet Sync: ✅ PASS\n";
echo "  [Úroveň 13] Hranica Dôvery, Súbežnosť & Deduplikácia  : ✅ PASS\n";
echo "=============================================================\n";
echo "🏆 VŠETKÝCH 13 ÚROVNÍ BOLO ÚSPEŠNE OVERENÝCH NA 100%!\n";
echo "=============================================================\n";
exit(0);
