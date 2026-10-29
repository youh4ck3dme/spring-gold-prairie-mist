<?php
/**
 * Master Test Runner: Vorkei Statement Bridge (Úrovne 1 až 5)
 */

$testFiles = [
	1 => 'test-level-1-syntax.php',
	2 => 'test-level-2-bootstrap.php',
	3 => 'test-level-3-database.php',
	4 => 'test-level-4-rest-api.php',
	5 => 'test-level-5-woocommerce.php',
];

echo "=============================================================\n";
echo "🧪 SPUSTENIE TESTOVACEJ PYRAMÍDY VORKEI STATEMENT BRIDGE\n";
echo "   Úrovne 1 (najslabšia / syntax) až 5 (najsilnejšia / E2E)\n";
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
echo "📊 SÚHRNNÝ REPORT TESTOVACIEHO BEHU (1 až 5):\n";
echo "=============================================================\n";
echo "  [Úroveň 1] Lint, Súbory & PHP Syntaktická Integrita : ✅ PASS\n";
echo "  [Úroveň 2] WordPress Sandbox & Hook Registrácia     : ✅ PASS\n";
echo "  [Úroveň 3] Databázová Štruktúra & dbDelta Pravidlá : ✅ PASS\n";
echo "  [Úroveň 4] REST API Kontrakt & Payload Validácia    : ✅ PASS\n";
echo "  [Úroveň 5] WooCommerce Párovací Engine & Bezpečnosť : ✅ PASS\n";
echo "=============================================================\n";
echo "🏆 VŠETKÝCH 5 ÚROVNÍ BOLO ÚSPEŠNE OVERENÝCH!\n";
echo "=============================================================\n";
exit(0);
