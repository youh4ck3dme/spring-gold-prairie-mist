# 🚀 Vorkei Statement Bridge (WordPress & WooCommerce Plugin)

> Profesionálny a bezpečný WordPress plugin pre automatickú synchronizáciu bankových výpisov Tatra banky s **WooCommerce HPOS** a **JetEngine CCT**.

[![WordPress](https://img.shields.io/badge/WordPress-6.2%20to%206.7+-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.1%20|%208.2%20|%208.3%20|%208.4-purple.svg)](https://php.net)
[![WooCommerce HPOS](https://img.shields.io/badge/WooCommerce-HPOS%20Ready-green.svg)](https://woocommerce.com)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL%20v2+-orange.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Test Pyramid](https://img.shields.io/badge/Tests-11%2F11%20PASS-brightgreen.svg)](docs/DEVELOPER_GUIDE.md)

---

## 📖 Rýchla Navigácia

- 📘 **Kompletná vývojárska a integračná príručka**: Pozrite si [`docs/DEVELOPER_GUIDE.md`](../docs/DEVELOPER_GUIDE.md).
- 🌐 **Oficiálny Landing & Live Endpoint**: [https://pdf-t3.vorkei.com/](https://pdf-t3.vorkei.com/)
- 💻 **Webová aplikácia Výpis (PWA)**: [https://app.pdf-t3.vorkei.com/](https://app.pdf-t3.vorkei.com/)
- 📦 **Inštalačný ZIP balíček**: `vorkei-statement-bridge.zip` (PclZip safe, < 300 kB).

---

## 📋 Kľúčové Vlastnosti

- **Plná kompatibilita s WooCommerce HPOS**: Deklarovaná natívna podpora High-Performance Order Storage (`custom_order_tables`).
- **Inteligentné párovanie objednávok**:
  - Vyhľadávanie objednávky podľa Variabilného symbolu (VS) z údajov platby.
  - Podpora štandardných WooCommerce Order ID aj pluginov pre zákazkové číslovanie (Sequential / Custom Order Numbers).
  - Striktné porovnávanie centov zabraňujúce podvodom, podplateniu alebo čiastočným úhradám.
  - Nastaviteľný cieľový stav objednávky (*Spracováva sa*, *Vybavená*, alebo *Iba poznámka*).
  - Interná servisná poznámka s auditom (dátum, IBAN odosielateľa, číslo výpisu).
- **JetEngine CCT Integrácia**:
  - Replikácia výpisov do **CCT 13 (`wp_jet_cct_bank_statements`)**.
  - Replikácia prevodov do **CCT 14 (`wp_jet_cct_transfers`)**.
  - Automatické relácie v `wp_jet_rel_default` (Relácia 32: Klient -> Výpisy, Relácia 33: Banka -> Výpisy).
- **100% On-Premise & GDPR Bezpečnosť**:
  - Žiadne bankové údaje neodchádzajú do externého cloudu.
  - Všetko beží lokálne na vašom WordPress hostingu s nulovými bankovými poplatkami.
- **Kryptografická deduplikácia**:
  - Každý pohyb má unikátny SHA-256 hash `(date + amount + iban + vs + statement_id)`, čo bráni duplikácii pri opakovanom importe.
- **Vstavaný Auto-Update Server**:
  - Automatická kontrola nových verzií cez endpoint `/wp-json/vorkei/v1/update-check`.
  - Aktualizácia na jeden klik priamo v administračnom paneli WordPress.

---

## 🛠️ Inštalácia

1. Skopírujte priečinok `vorkei-statement-bridge` do `/wp-content/plugins/` (alebo nahrajte `vorkei-statement-bridge.zip` cez **Pluginy -> Pridať nový -> Nahrať plugin**).
2. Aktivujte plugin.
3. Otvorte novú položku menu **Vorkei Bridge** v bočnom paneli administrácie.
4. V záložke **Nastavenia** si overte požadované stavy párovania a zabezpečenie.

---

## 🔌 REST API Rýchly Prehľad

Všetky trasy sú registrované pod menným priestorom `/wp-json/vorkei/v1/`.

| Metóda | Endpoint | Popis | Autentifikácia |
| :--- | :--- | :--- | :--- |
| `POST` | `/wp-json/vorkei/v1/sync` | Príjem výpisov, pohybov a okamžité párovanie s Woo | Application Passwords / Bearer |
| `GET` | `/wp-json/vorkei/v1/statements` | Zoznam synchronizovaných výpisov | Application Passwords / Bearer |
| `GET` | `/wp-json/vorkei/v1/statements/{id}` | Detail konkrétneho výpisu | Application Passwords / Bearer |
| `GET` | `/wp-json/vorkei/v1/movements` | Zoznam a vyhľadávanie pohybov (filter `variable_symbol`) | Application Passwords / Bearer |
| `GET/POST` | `/wp-json/vorkei/v1/update-check` | Kontrola verzií pre automatické aktualizácie pluginu | Verejný |

Kompletné JSON schémy požiadaviek a odpovedí nájdete v [Developer Guide](../docs/DEVELOPER_GUIDE.md#5-rest-api-špecifikácia).

---

## 🗄️ Databázová štruktúra

Plugin vytvára 2 optimalizované tabuľky cez WordPress `dbDelta`:
- **`wp_vorkei_statements`** — Hlavičky výpisov s číslom, IBANom a počiatočným/konečným zostatkom.
- **`wp_vorkei_movements`** — Kreditné/debetné položky s unikátnym indexom `movement_hash` (SHA-256) a väzbou na `matched_order_id`.

---

## 🧪 11-Úrovňová Testovacia Pyramída

Každé vydanie pluginu prechádza prísnym testovacím auditom v priečinku `tests/`:
- **Level 1**: PHP Syntax & Coding Standards
- **Level 2**: Plugin Bootstrap & HPOS Declaration
- **Level 3**: SQL dbDelta Schema Integrity
- **Level 4**: REST API Routes, Auth & JSON Schema
- **Level 5**: WooCommerce HPOS Order Matching & Amount Check
- **Level 6**: Stress & High Concurrency deduplication
- **Level 7**: Pentesting, SQLi & XSS Sanitization
- **Level 8**: Chaos & Resilience (Missing data)
- **Level 9**: Fintech Multi-Currency & Cent Integrity
- **Level 10**: E2E PWA Contract Verification
- **Level 11**: Auto-Updates & PclZip Package Audit

Spustenie testov:
```bash
cd tests
bash run-all-tests.sh
```

---

© 2026 **Vorkei** (`vorkei.com`). Všetky práva vyhradené. Vytvorené pre Tatra banka & WooCommerce HPOS.
