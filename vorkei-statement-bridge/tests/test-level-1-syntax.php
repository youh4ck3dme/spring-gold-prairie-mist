<?php
/**
 * Test Level 1: Syntaktická a štrukturálna integrita
 */

$rootDir = dirname(__DIR__);
echo "🔍 [TEST 1] Kontrola súborov a PHP syntaxe...\n";

$files = [
	'vorkei-statement-bridge.php',
	'uninstall.php',
	'includes/class-vorkei-activator.php',
	'includes/class-vorkei-deactivator.php',
	'includes/class-vorkei-matcher.php',
	'includes/class-vorkei-rest-controller.php',
	'includes/class-vorkei-updater.php',
	'admin/class-vorkei-admin.php',
];

$allPassed = true;

foreach ($files as $file) {
	$path = $rootDir . '/' . $file;
	if (!file_exists($path)) {
		echo "  ❌ Súbor neexistuje: $file\n";
		$allPassed = false;
		continue;
	}

	$code = file_get_contents($path);
	
	// Kontrola otváracieho tagu
	if (!str_starts_with(trim($code), '<?php')) {
		echo "  ❌ Chýba <?php na začiatku: $file\n";
		$allPassed = false;
		continue;
	}

	// Kontrola tokenov a syntaktickej platnosti
	try {
		$tokens = token_get_all($code, TOKEN_PARSE);
		echo "  ✓ $file OK (Syntax platná, tokeny bez chýb)\n";
	} catch (\ParseError $e) {
		echo "  ❌ Syntaktická chyba v $file: " . $e->getMessage() . " na riadku " . $e->getLine() . "\n";
		$allPassed = false;
	}

	// Kontrola ABSPATH ochrany
	if (!str_contains($code, 'ABSPATH') && !str_contains($code, 'WP_UNINSTALL_PLUGIN')) {
		echo "  ⚠️ Varovanie: Chýba ABSPATH check v $file\n";
	}
}

// Kontrola manifestov a assetov
$requiredMeta = ['readme.txt', 'README.md', 'admin/css/vorkei-admin.css', 'languages/vorkei-bridge.pot', 'assets/icon.svg', 'assets/icon-256x256.png', 'assets/icon-128x128.png'];
foreach ($requiredMeta as $metaFile) {
	if (file_exists($rootDir . '/' . $metaFile)) {
		echo "  ✓ Asset existuje: $metaFile\n";
	} else {
		echo "  ❌ Chýba asset: $metaFile\n";
		$allPassed = false;
	}
}

if ($allPassed) {
	echo "🎉 TEST 1 ÚSPEŠNÝ: Všetky súbory a syntax sú stopercentné.\n\n";
	exit(0);
} else {
	echo "💥 TEST 1 ZLYHAL!\n\n";
	exit(1);
}
