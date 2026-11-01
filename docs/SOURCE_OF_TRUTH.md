# 🧠 SOURCE OF TRUTH: Vorkei Statement Bridge & PDF-T3

Dokument slúži ako centrálna technická dokumentácia a referenčná príručka projektu prepojenia bankových výpisov Tatra banky s WordPressom.

---

## 📌 Rýchly Prehľad Kľúčových Parametrov

| Parameter | Hodnota |
| :--- | :--- |
| **Produkčná URL webu** | `https://pdf-t3.vorkei.com` |
| **WordPress REST API** | `https://pdf-t3.vorkei.com/wp-json/` |
| **API Používateľ** | `support@vorkei.c` *(ID 1, Administrator)* |
| **Autentifikácia** | Bearer Token (Zákaznícke peňaženky) / Server-side premenné prostredia |
| **Aktualizačný server (Auto-Updates)** | `https://pdf-t3.vorkei.com/wp-json/vorkei/v1/update-check` *(fallback: `https://api.vorkei.com/plugins/update-check`)* |
| **Inštalačný ZIP pluginu** | `vorkei-statement-bridge.zip` (280 kB, 100% PclZip safe) |
| **Testovacia pyramída** | 11/11 Úrovní úspešných (PASS) |

---

## 🛠️ Architektúra Pluginu (`vorkei-statement-bridge`)

```text
vorkei-statement-bridge/
├── vorkei-statement-bridge.php      # Hlavný zavádzač, HPOS deklarácia, Singleton
├── uninstall.php                    # Čistenie databázy (s ochranou pri delete_on_uninstall=no)
├── readme.txt                       # Oficiálny WordPress formát pre katalóg a modály
├── README.md                        # Technická dokumentácia v slovenskom jazyku
├── assets/                          # Oficiálny grafický branding
│   ├── banner-772x250.jpg           # Vysoké rozlíšenie pre WordPress hlavičku
│   ├── icon-128x128.png             # Ikona 1x
│   ├── icon-256x256.png             # Ikona 2x retina
│   └── icon.svg                     # Vektorový zdroj ikony
├── includes/
│   ├── class-vorkei-activator.php   # Vytváranie tabuliek cez dbDelta, migrácie schémy
│   ├── class-vorkei-deactivator.php # Bezpečná deaktivácia
│   ├── class-vorkei-matcher.php     # Párovanie WooCommerce platieb, auditné poznámky
│   ├── class-vorkei-rest-controller.php # REST API (/sync, /statements, /movements, /update-check, CCT 13 & 14)
│   └── class-vorkei-updater.php     # Auto-update klient pre update-check server
├── admin/
│   ├── class-vorkei-admin.php       # Administračné menu, prehľad výpisov, nastavenia
│   └── css/vorkei-admin.css         # Špeciálne štýly administračného rozhrania
└── languages/
    └── vorkei-bridge.pot            # Šablóna pre preklady
```

---

## 🔄 Synchronizačný Cyklus (Dátový Tok)

```mermaid
sequenceDiagram
    autonumber
    participant App as Web App (pdf-t3.vorkei.com)
    participant REST as WP REST API (/vorkei/v1/sync)
    participant DB as Vorkei DB Tabuľky
    participant CCT as JetEngine CCT (13 & 14)
    participant WC as WooCommerce HPOS

    App->>REST: POST /sync (Basic Auth: support@vorkei.c)
    REST->>REST: Overenie Application Password (HTTP 200)
    REST->>DB: Uloženie hlavičky výpisu (wp_vorkei_statements)
    opt Ak existuje JetEngine CCT 13
        REST->>CCT: Zápis do wp_jet_cct_statements
    end
    loop Pre každý pohyb vo výpise
        REST->>DB: Generovanie SHA-256 hashu a uloženie (wp_vorkei_movements)
        opt Ak existuje JetEngine CCT 14
            REST->>CCT: Zápis do wp_jet_cct_transfers
        end
        opt Ak je zapnuté párovanie WooCommerce
            REST->>WC: Hľadanie objednávky podľa Variabilného Symbolu
            WC-->>REST: Nájdená objednávka (ID & Suma)
            REST->>WC: Zmena stavu na 'processing' + Interná poznámka
            REST->>DB: Aktualizácia matched_order_id
        end
    end
    REST-->>App: Odpoveď so štatistikou (uložené výpisy, pohyby, spárované objednávky)
```

---

## 🚀 Postup Inštalácie a Prvého Spustenia

1. Otvorte administráciu WordPress na **`https://pdf-t3.vorkei.com/wp-admin/`**.
2. Prejdite do **Pluginy ➔ Pridať nový ➔ Nahrať plugin**.
3. Nahrajte súbor [`vorkei-statement-bridge.zip`](file:///home/vorkei/Documents/pdf-t3-vorkei/vorkei-statement-bridge.zip) a kliknite na **Aktivovať**.
4. V bočnom menu pribudne položka **Vorkei Bridge**:
   - **Prehľad a Výpisy**: Zobrazuje štatistiky a posledné importované bankové transakcie.
   - **Nastavenia**: Pravidlá párovania platieb, cieľový stav objednávky, nastavenie Auto-Updates.
5. V aplikácii Výpis (frontend) otvorte okno synchronizácie a kliknite na **"Otestovať spojenie"** – spojenie s `https://pdf-t3.vorkei.com` prebehne okamžite a bezpečne.

---

## 🌐 Landing Page & Prezentácia Pluginu na WordPress CMS

Prezentácia a komerčný predaj pluginu `Vorkei Statement Bridge` sú nasadené priamo vo **WordPress CMS** na produkčnej doméne **`https://pdf-t3.vorkei.com/`**:

1. **Umiestnenie a Dizajn:**
   - URL: **`https://pdf-t3.vorkei.com/`** (oficiálna úvodná stránka webu v CMS, Page ID: 10, template `landing-canvas`).
   - Štýl: **Enterprise Minimal Design Tokens na čistom bielom pozadí** (bez tmavomodrého pozadia, s dôrazom na čistú typografiu, jemné sivé orámovania a prehľadnosť).
   - Očista aplikácie (Prísny zákaz): Všetky marketingové a propagačné prvky, tlačidlá "Prezentácia Pluginu", "Landing Page" a príslušné navigačné odkazy boli striktne a trvalo odstránené z bežiacej aplikácie (WelcomeScreen a VypisApp). Bežiaca aplikácia slúži výhradne na správu a editáciu výpisov Tatra banky a je 100% čistá.

2. **Funkcionalita na Live Webe:**
   - **Živý interaktívny simulátor párovania** platby s WooCommerce objednávkou #1042 (stav `Pending payment` ➔ `Processing`).
   - **Diagnostický test živého REST API endpointu** `https://pdf-t3.vorkei.com/wp-json/vorkei/v1/statements`.
   - **Priame stiahnutie inštalačného balíčka pluginu** z WordPress Media Library: `https://pdf-t3.vorkei.com/wp-content/uploads/2026/10/vorkei-statement-bridge.zip`.
   - **Porovnanie riešení**, 6 pilierov technickej architektúry, cenové balíčky (Single, Agency, Enterprise) a interaktívny FAQ akordeón.
   - **Nasadzovací skript:** [`scripts/deploy-landing-wp.py`](file:///home/vorkei/Documents/pdf-t3-vorkei/scripts/deploy-landing-wp.py) – automatizuje správu a aktualizáciu stránky cez WordPress REST API.

---

## 🏛️ VORKEI IČO ATLAS v2.0.0 (Architektúra & Integrácia)

Samostatný WordPress modulárny plugin pre automatické overovanie firiem, účtovných závierok a daňovej integrity bez platených sprostredkovateľov.

### 1. Kľúčové komponenty a zdroje dát (Trojuholník dôvery):
1. **Registračné API (Hetzner VPS microservice):**
   - Verejná HTTPS adresa: `https://ico-api.vorkei.com/api/company/proxy-search?ico=`
   - IP: `2.29.52.59` (Nginx reverzné proxy s certifikátom Let's Encrypt)
   - Interný backend: Docker kontajner `company-api-api-1` na porte `3010`
   - Povinná hlavička: `X-ICO-LOOKUP-CONTRACT: js_default`
   - Dáta: Overenie právnej existencie, úradný názov, adresa, štatutári, likvidácia, konkurz.
2. **Konektor RÚZ (Register účtovných závierok):**
   - Oficiálne štátne API MF SR: `https://www.registeruz.sk/cruz-public/api`
   - Dáta: Hospodárske výsledky, tržby, zisk pred/po zdanení, aktíva, vlastné imanie, súvaha podľa šablóny 687 (Úč MUJ).
   - Náklady: **0 € (ZADARMO)**, nevyžaduje kľúč.
3. **API Finančnej správy SR (OpenData):**
   - Portál: `https://opendata.financnasprava.sk/page/openapi`
   - API Endpoint: `https://iz.opendata.financnasprava.sk/api`
   - Overený token: `vvRk2abkHEP2jE9l9ZF7x4t71Cd9do0TITdjmNu9zzToWs12m9Y5kvKmzuXyQCOwX5y2ZqFBMjjL2r43X5PMAckHriCXhXigX3oPGkvZd1SW8mvA7xhmNGq4VXlSUdRYgeBdxV6XymjWEHfYXKnL8xgW1Wl0p8CKBJsSzFGiSEngQoGBtJpeqitjiwdYdbW8qC9w8FP3MYMjFaWMzagioW72eh4rMO9w9YxoUQiyAfkfFjnOJqI9jJTAOp`
   - Slugs v systéme:
     - DIČ (Daň z príjmov): `ds_dsrdp`
     - IČ DPH (Platitelia DPH): `ds_dphs`
     - Čierna listina DPH: `ds_dphz`
     - Nahlásené firemné bankové účty: `ds_dph_iban`
     - Index daňovej spoľahlivosti (IDS): `ds_iz_ran`
   - Náklady: **0 € (ZADARMO)**, limit 1 000 volaní / hod.

### 2. Dátový tok do JetEngine CCT:
- Tabuľky v databáze: `wp_jet_cct_customers`, `wp_jet_cct_contractors`, `wp_jet_cct_clients`.
- Automatický audit spúšťaný po dokončení synchronizácie výpisov z Vorkei Bridge.
- Okamžité vyhodnotenie Risk Radaru (🔴 Rizikový subjekt / 🟡 Upozornenie / 🟢 Overený zápis).

---

## 🔐 MASTER BACKUP & DISASTER RECOVERY VAULT (2026-11-01)

Kompletná nezávislá záloha celého ekosystému je uložená a zabezpečená na lokálnom stroji:

- **Archív zálohy:** `/home/vorkei/Documents/BACKUP_VORKEI_MASTER_2026-11-01.tar.gz` (15 MB, oprávnenia `chmod 600`)
- **Priečinok zálohy:** `/home/vorkei/Documents/BACKUP_VORKEI_MASTER_2026-11-01/` (oprávnenia `chmod 700`)
- **Obsah:**
  - `ENV_AND_KEYS_VAULT.env`: Všetky kľúče (FS SR, Stripe live, GitHub PAT, GPG kľúč, Vercel token, Mistral).
  - `vorkei-ico-atlas.zip`: 100% PclZip-safe distribučný balíček pluginu.
  - `vorkei-ico-atlas-source/`: Kompletné zdrojové kódy WordPress pluginu.
  - `pdf-t3-vorkei.bundle`: Nezávislý Git bundle repozitára PWA Výpisov so všetkými vetvami.
  - `pwa-agent-workspace.bundle`: Nezávislý Git bundle repozitára PWA AI Agenta.
  - `HETZNER_VPS_CONFIGS_AND_CONTRACTS.md`: Nginx konfigurácie a reverzné proxy pre Hetzner VPS.
  - `DISASTER_RECOVERY_MANUAL.md`: Presný manuál na obnovu v prípade havárie alebo výpadku.


