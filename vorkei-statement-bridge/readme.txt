=== Vorkei Statement Bridge – Bank Statement PDF & WooCommerce Sync ===
Contributors: vorkei
Tags: rest-api, bank-statements, woocommerce, payment-matching, tatra-banka, jetengine
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bezpečné REST API prepojenie bankových výpisov Tatra banky s WordPress databázou a JetEngine CCT. Automatické inteligentné párovanie platieb s WooCommerce objednávkami.

== Description ==

Vorkei Statement Bridge je vysoko optimalizovaný a bezpečný WordPress plugin, ktorý poskytuje oficiálne REST API rozhranie pre príjem bankových výpisov a finančných pohybov priamo z aplikácie **Výpis (pdf-t3.vorkei)**.

### Hlavné funkcie:
* **Kompatibilita s moderným WordPress (6.2 až 6.7+) a PHP 8.1 - 8.3+**.
* **Plná podpora WooCommerce HPOS (High-Performance Order Storage)**.
* **Automatické inteligentné párovanie objednávok**:
  - Vyhľadávanie objednávky podľa Variabilného symbolu (VS).
  - Podpora štandardných ID objednávok aj zákazkových čísiel (Custom Order Numbers).
  - Striktná validácia sumy v centoch (ochrana pred neúplne zaplatenými objednávkami).
  - Automatická zmena stavu objednávky (napr. na "Spracováva sa" / "Vybavená").
  - Detailná privátna poznámka v objednávke s menom odosielateľa, IBANom a číslom výpisu.
* **Idempotentné ukladanie transakcií**:
  - SHA-256 hashovanie zabraňuje duplicitnému zaevidovaniu tej istej platby.
* **Zabezpečenie**:
  - Štandardná podpora WordPress Application Passwords (odporúčané).
  - Voliteľný tajný Bearer API kľúč.
  - Kontrola oprávnení administrátora/správcu obchodu (`manage_woocommerce`, `edit_shop_orders`).

== Installation ==

1. Nahrajte priečinok `vorkei-statement-bridge` do adresára `/wp-content/plugins/` alebo nahrajte archív `.zip` cez administračný panel: **Pluginy -> Pridať nový -> Nahrať plugin**.
2. Aktivujte plugin v zozname pluginov.
3. V administračnom menu pribudne položka **Vorkei Bridge**.
4. V menu **Vorkei Bridge -> Nastavenia** si prispôsobte pravidlá párovania objednávok a zabezpečenie.
5. Vytvorte si v profile používateľa (Používatelia -> Profil) **Aplikačné heslo** a zadajte ho do aplikácie Výpis spolu s URL adresou endpointu `https://vasadomena.sk/wp-json/vorkei/v1/sync`.

== Frequently Asked Questions ==

= Podporuje plugin HPOS v novom WooCommerce? =
Áno, plugin deklaruje plnú kompatibilitu s `custom_order_tables` a používa CRUD metódy WooCommerce.

= Čo sa stane, ak zákazník pošle inú sumu, než je na objednávke? =
Ak máte zapnutú "Striktnú kontrolu sumy", plugin neprestaví objednávku na zaplatenú, ale pridá varovnú poznámku pre personál eshopu.

== Changelog ==

= 1.0.0 =
* Prvá stabilná verzia s REST API synchronizáciou, HPOS kompatibilitou a automatickým párovaním WooCommerce.
