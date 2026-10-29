# 🧠 SOURCE OF TRUTH: Vorkei Statement Bridge & PDF-T3 Ecosystem

Tento dokument je **jediným zdrojom pravdy (Single Source of Truth)** pre prepojenie webovej aplikácie Výpis (PDF-T3) s WordPress / WooCommerce eshopom a JetEngine CCT.

---

## 🌐 1. Produkčné Prostredie & Prístupové Údaje

- **Hlavná doména WordPress / API:** `https://pdf-t3.vorkei.com`
- **WordPress REST API Root:** `https://pdf-t3.vorkei.com/wp-json/`
- **Autorizovaný Admin Používateľ:** `support@vorkei.c` *(alias support@vorkei.com, User ID: 1)*
- **WordPress Application Password:** `7fv8 7JAG XPcc IpDk b6dn BgMk`
- **Aktualizačný server (Auto-Updates):** `https://api.vorkei.com/plugins/update-check`

---

## 📦 2. WordPress Plugin (Vorkei Statement Bridge)

- **Oficiálny názov:** `Vorkei Statement Bridge – Bank Statement PDF & WooCommerce Sync`
- **Slug / Basename:** `vorkei-statement-bridge/vorkei-statement-bridge.php`
- **Verzia:** `1.0.0` (PHP 8.1 - 8.4, WP 6.2 - 6.7+)
- **Kompatibilita:** WooCommerce HPOS (`custom_order_tables`), JetEngine CCT (ID 13 & 14)
- **Cesta k inštalačnému ZIP balíčku:**
  - Hlavné umiestnenie: `/home/vorkei/Documents/pdf-t3-vorkei/vorkei-statement-bridge.zip`
  - Záložné umiestnenie: `/home/vorkei/Documents/pdf-t3-vorkei/docs/vorkei-statement-bridge.zip`
- **Zdrojové kódy:** `/home/vorkei/Documents/pdf-t3-vorkei/vorkei-statement-bridge/`
- **Baliaci & validačný skript (PclZip & ZipArchive safe):**
  - `/home/vorkei/Documents/pdf-t3-vorkei/build-distribution-zip.py`

---

## 🔌 3. REST API Kontrakt (Menový priestor: `vorkei/v1`)

| Metóda | Endpoint | Účel | Parametre / Telo |
| :--- | :--- | :--- | :--- |
| `POST` | `/wp-json/vorkei/v1/sync` | Hromadná synchronizácia výpisov, pohybov a CCT | JSON payload `{ account, statements: [...] }` |
| `GET` | `/wp-json/vorkei/v1/statements` | Zoznam výpisov s pagination hlavičkami | `page`, `per_page` |
| `GET` | `/wp-json/vorkei/v1/statements/{id}` | Detail jedného výpisu vrátane pohybov | ID výpisu v URL |
| `GET` | `/wp-json/vorkei/v1/movements` | Filtrovateľný zoznam bankových pohybov | `variable_symbol`, `statement_id`, `page` |

---

## 🗄️ 4. Databázová Architektúra & JetEngine CCT

### A. Vstavané tabuľky pluginu:
1. `wp_vorkei_statements`: Ukladá čísla výpisov, IBAN, začiatočný a konečný stav v centoch, menu a dátum vydania.
2. `wp_vorkei_movements`: Ukladá bankové pohyby, SHA-256 idempotentný hash, VS, SS, KS, sumu v centoch, spárované ID objednávky (`matched_order_id`) a stav (`matched`/`unmatched`).

### B. JetEngine CCT Mapovanie (ak je nainštalovaný JetEngine):
- **CCT 13 (`wp_jet_cct_statements`):** slug, title, bank_name, bank_data, client_id, client_name, client_email, account_iban, account_swift, account_currency.
- **CCT 14 (`wp_jet_cct_transfers`):** statement_id, payment_type (Prijatá/Odoslaná/Transakčná daň), customer_id, contractor_id, date, amount, detail.

---

## 🧪 5. Automatizovaná 11-Stupňová Testovacia Pyramída

Spustenie všetkých testov:
```bash
./vorkei-statement-bridge/tests/run-all-tests.sh
# alebo
npx -y @php-wasm/cli ./vorkei-statement-bridge/tests/run-all-tests.php
```

1. **Úroveň 1 (`test-level-1-syntax.php`):** Lint & PHP syntax tokeny pre všetkých 16 súborov a assetov.
2. **Úroveň 2 (`test-level-2-bootstrap.php`):** WordPress mock bootstrapping, HPOS features, Vorkei_Updater.
3. **Úroveň 3 (`test-level-3-database.php`):** dbDelta štruktúra (lowercase typy, 2 medzery za PRIMARY KEY), bezpečný uninstall.
4. **Úroveň 4 (`test-level-4-rest-api.php`):** REST API trasy, Bearer auth, overenie oprávnení, sanitizácia.
5. **Úroveň 5 (`test-level-5-woocommerce.php`):** Párovanie podľa VS, orezanie predradených núl, status processing, interné poznámky.
6. **Úroveň 6 (`test-level-6-stress-concurrency.php`):** 1000+ transakcií za 0.2s, limit pamäte pod 64 MB, SHA-256 deduplikácia.
7. **Úroveň 7 (`test-level-7-security-pentest.php`):** Pen-test proti SQLi, XSS a Timing Attacks cez `hash_equals()`.
8. **Úroveň 8 (`test-level-8-chaos-resilience.php`):** Odolnosť pri vypnutom WooCommerce a výpadku MySQL databázy.
9. **Úroveň 9 (`test-level-9-fintech-currencies.php`):** Ochrana pred menovou nezhodou (CZK vs EUR), 1-centový nedoplatok, kontokorent.
10. **Úroveň 10 (`test-level-10-e2e-app-contract.php`):** End-to-End kontrakt medzi webovou aplikáciou Výpis a WooCommerce.
11. **Úroveň 11 (`test-level-11-auto-updates.php`):** WordPress Update Transient injekcia, changelog modal, server failover, cache purge.

---

## 💻 6. Frontend Synchronizácia (`src/lib/vorkei-sync.ts`)

- Konfigurácia je prednastavená na novú produkčnú doménu `https://pdf-t3.vorkei.com`.
- Používa používateľa `support@vorkei.c` s aplikačným heslom `7fv8 7JAG XPcc IpDk b6dn BgMk`.
- Úložisko v prehliadači: kľúč `vorkei-bridge-config-v2`.
