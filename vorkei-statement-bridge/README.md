# 🚀 Vorkei Statement Bridge (WordPress REST API Plugin)

Profesionálny a bezpečný WordPress / WooCommerce plugin pre synchronizáciu bankových výpisov a transakcií z aplikácie **Výpis (`pdf-t3.vorkei`)**.

---

## 📋 Vlastnosti a Štandardy

- **Najnovšie štandardy WordPress**: Kompatibilita s **WordPress 6.7+** a **PHP 8.1 / 8.2 / 8.3 / 8.4**.
- **WooCommerce HPOS Ready**: Deklarovaná natívna kompatibilita s High-Performance Order Storage (`custom_order_tables`).
- **Bezpečná architektúra**:
  - Dvojitá autorizácia: Natívne WordPress **Application Passwords** alebo vlastný **Bearer Token**.
  - Ochrana proti priamemu prístupu (`ABSPATH` check).
  - Sanitizácia a validácia vstupov pomocou WP REST API Schemas.
  - SQL ochrana cez `$wpdb->prepare` a `dbDelta`.
- **Inteligentné párovanie objednávok**:
  - Automatické dohľadanie objednávky podľa Variabilného symbolu (VS) z údajov platby.
  - Podpora pre štandardné ID objednávok aj pluginy generujúce vlastné čísla objednávok (Sequential / Custom Order Numbers).
  - Striktné overovanie sumy (porovnanie centov zabraňujúce podvodom alebo čiastočným úhradám).
  - Konfigurovateľný cieľový stav objednávky (napr. *Spracováva sa* / *Vybavená* / *Iba poznámka*).
  - Interná servisná poznámka s auditom (dátum, IBAN odosielateľa, číslo výpisu).
- **Idempotencia a Deduplikácia**: Každý pohyb má unikátny SHA-256 hash `(date + amount + iban + vs + statement_id)`, čo bráni duplikácii pri opakovanom odoslaní výpisu.

---

## 🛠️ Inštalácia

1. Skopírujte priečinok `vorkei-statement-bridge` do `/wp-content/plugins/` (alebo zbaľte do `.zip` a nahrajte cez administráciu WordPress: **Pluginy -> Pridať nový -> Nahrať plugin**).
2. Aktivujte plugin.
3. Otvorte novú položku menu **Vorkei Bridge** v bočnom paneli administrácie.
4. V záložke **Nastavenia** si overte požadované stavy párovania a zabezpečenie.

---

## 🔌 REST API Špecifikácia

### 1. Synchronizácia výpisov (Sync Endpoint)
- **Metóda:** `POST`
- **URL:** `/wp-json/vorkei/v1/sync`
- **Hlavičky:**
  - `Content-Type: application/json`
  - `Authorization: Basic <base64(username:app_password)>` **alebo** `Authorization: Bearer <tajny_token>`

#### Príklad JSON Payloadu:
```json
{
  "account": {
    "iban": "SK8811000000002941234567"
  },
  "statements": [
    {
      "id": "s-2026-10-01",
      "number": "10",
      "openingCents": 154200,
      "closingCents": 184200,
      "currency": "EUR",
      "issuedOn": "29.10.2026",
      "movements": [
        {
          "id": "mv-001",
          "date": "28.10.2026",
          "amountCents": 30000,
          "kind": "in",
          "partyName": "Ján Novák",
          "partyIban": "SK1234567890123456789012",
          "variableSymbol": "10245",
          "note": "Úhrada objednávky 10245"
        }
      ]
    }
  ]
}
```

#### Príklad odpovede:
```json
{
  "success": true,
  "message": "Úspešne synchronizované: 1 výpisov, 1 pohybov a 1 spárovaných objednávok.",
  "stats": {
    "statements_saved": 1,
    "movements_saved": 1,
    "orders_matched": 1,
    "duplicates_skipped": 0
  },
  "timestamp": "2026-10-29 16:45:00"
}
```

---

### 2. Získanie zoznamu výpisov
- **Metóda:** `GET`
- **URL:** `/wp-json/vorkei/v1/statements?page=1&per_page=20`

### 3. Získanie zoznamu pohybov / vyhľadávanie
- **Metóda:** `GET`
- **URL:** `/wp-json/vorkei/v1/movements?variable_symbol=10245`

---

## 🗄️ Databázová štruktúra (Custom Tables)

Plugin vytvára 2 optimalizované tabuľky spĺňajúce pravidlá `dbDelta`:
- `wp_vorkei_statements` — Hlavičky výpisov s číslom, IBANom a zostatkami.
- `wp_vorkei_movements` — Jednotlivé kreditné/debetné položky s hashovacím kľúčom a väzbou na `matched_order_id`.
