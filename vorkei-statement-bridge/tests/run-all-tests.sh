#!/usr/bin/env bash
# ==============================================================================
# Vorkei Statement Bridge — Kompletný Test Runner (12-stupňová testovacia pyramída)
# ==============================================================================

set -e

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Detekcia PHP behového prostredia (systémové PHP alebo WebAssembly cez npx)
if command -v php >/dev/null 2>&1; then
    PHP_EXEC="php"
    echo "⚙️  Používam systémové PHP: $(php -v | head -n 1)"
else
    PHP_EXEC="npx -y @php-wasm/cli"
    echo "⚙️  Používam izolované PHP WASM runtime (Node.js engine)"
fi

echo "============================================================="
echo "🧪 SPUSTENIE 13-STUPŇOVEJ TESTOVACEJ PYRAMÍDY VORKEI BRIDGE"
echo "   Úrovne 1 (Základ / syntax) až 13 (Hranica Dôvery & Concurrency)"
echo "============================================================="
echo ""

echo "▶️ [1/12] Úroveň 1: Syntaktická a štrukturálna integrita..."
$PHP_EXEC "$DIR/test-level-1-syntax.php"

echo "▶️ [2/12] Úroveň 2: WordPress Sandbox & Hook Registrácia..."
$PHP_EXEC "$DIR/test-level-2-bootstrap.php"

echo "▶️ [3/12] Úroveň 3: Databázová Štruktúra & dbDelta Pravidlá..."
$PHP_EXEC "$DIR/test-level-3-database.php"

echo "▶️ [4/12] Úroveň 4: REST API Kontrakt & Payload Validácia..."
$PHP_EXEC "$DIR/test-level-4-rest-api.php"

echo "▶️ [5/12] Úroveň 5: WooCommerce Párovací Engine & Biznis Logika..."
$PHP_EXEC "$DIR/test-level-5-woocommerce.php"

echo "▶️ [6/12] Úroveň 6: Záťažový test & Concurrency (1000+ transakcií)..."
$PHP_EXEC "$DIR/test-level-6-stress-concurrency.php"

echo "▶️ [7/12] Úroveň 7: Bezpečnostný Pen-test (SQLi, XSS, Privileges)..."
$PHP_EXEC "$DIR/test-level-7-security-pentest.php"

echo "▶️ [8/12] Úroveň 8: Chaos Engineering & Výpadok WooCommerce..."
$PHP_EXEC "$DIR/test-level-8-chaos-resilience.php"

echo "▶️ [9/12] Úroveň 9: Fintech Meny, Symboly & Hraničné stavy..."
$PHP_EXEC "$DIR/test-level-9-fintech-currencies.php"

echo "▶️ [10/12] Úroveň 10: E2E Kontrakt Aplikácia Výpis ➔ WooCommerce..."
$PHP_EXEC "$DIR/test-level-10-e2e-app-contract.php"

echo "▶️ [11/12] Úroveň 11: Auto-Updater Client & WordPress Update Transient..."
$PHP_EXEC "$DIR/test-level-11-auto-updates.php"

echo "▶️ [12/13] Úroveň 12: Stripe Webhook, Transakčná Idempotencia & Reconciliácia..."
$PHP_EXEC "$DIR/test-level-12-stripe-webhook.php"

echo "▶️ [13/13] Úroveň 13: Hranica Dôvery, Súbežnosť & Deduplikácia..."
$PHP_EXEC "$DIR/test-level-13-trust-boundary-concurrency.php"

echo "============================================================="
echo "📊 SÚHRNNÝ REPORT TESTOVACIEHO BEHU (1 až 13):"
echo "============================================================="
echo "  [Úroveň 1]  Lint, Súbory & PHP Syntaktická Integrita : ✅ PASS"
echo "  [Úroveň 2]  WordPress Sandbox & Hook Registrácia     : ✅ PASS"
echo "  [Úroveň 3]  Databázová Štruktúra & dbDelta Pravidlá : ✅ PASS"
echo "  [Úroveň 4]  REST API Kontrakt & Payload Validácia    : ✅ PASS"
echo "  [Úroveň 5]  WooCommerce Párovací Engine (VS Základ)  : ✅ PASS"
echo "  [Úroveň 6]  Záťažový test & 1000+ pohybov Concurrency: ✅ PASS"
echo "  [Úroveň 7]  Bezpečnostný Pen-test (SQLi, XSS, Auth)  : ✅ PASS"
echo "  [Úroveň 8]  Chaos Engineering & Odolnosť voči pádu  : ✅ PASS"
echo "  [Úroveň 9]  Fintech Meny (CZK/EUR) & Centové stavy   : ✅ PASS"
echo "  [Úroveň 10] E2E Kontrakt: Aplikácia Výpis ➔ WC Eshop : ✅ PASS"
echo "  [Úroveň 11] Auto-Updater: api.vorkei.com Aktualizácie : ✅ PASS"
echo "  [Úroveň 12] Stripe Webhook, Idempotencia & Wallet Sync: ✅ PASS"
echo "  [Úroveň 13] Hranica Dôvery, Súbežnosť & Deduplikácia  : ✅ PASS"
echo "============================================================="
echo "🏆 VŠETKÝCH 13 ÚROVNÍ BOLO ÚSPEŠNE OVERENÝCH NA 100%!"
echo "============================================================="
