# 🚀 VORKEI STATEMENT BRIDGE — MISSION 08: PRODUCTION GO-LIVE & VERIFICATION REPORT

**Dátum a čas:** 2026-10-31 10:32 CEST  
**Prostredie:** Produkcia (`https://pdf-t3.vorkei.com` & `https://app.pdf-t3.vorkei.com`)  
**Git Repozitár:** `youh4ck3dme/spring-gold-prairie-mist`  
**Produkčná vetva:** `main` (Commit: `b0b2aef`)  
**Distribučný ZIP:** `vorkei-statement-bridge.zip`  
**SHA-256:** `53d91a98edcca0a681c1887deefeb6e24aa53566fc2b67a5e605c510e8a6026f`  
**Media ID na serveri:** 30 (`https://pdf-t3.vorkei.com/wp-content/uploads/2026/10/vorkei-statement-bridge.zip`)

---

## 1. VÝSLEDKY TESTOVACEJ PYRAMÍDY A OVERENIE INTEGRITY

### A. 13-stupňová testovacia pyramída (`run-all-tests.sh`)
- **[Úroveň 1]** Lint, PHP 8.1 - 8.5 Syntax & Assety: ✅ **PASS**
- **[Úroveň 2]** WordPress Sandbox & Hook Registrácia: ✅ **PASS**
- **[Úroveň 3]** Databázová Štruktúra & dbDelta Migrácie: ✅ **PASS**
- **[Úroveň 4]** REST API Kontrakt & Payload Validácia: ✅ **PASS**
- **[Úroveň 5]** WooCommerce Párovací Engine (VS Základ): ✅ **PASS**
- **[Úroveň 6]** Záťažový test & 1000+ pohybov Concurrency: ✅ **PASS**
- **[Úroveň 7]** Bezpečnostný Pen-test (SQLi, XSS, Auth): ✅ **PASS**
- **[Úroveň 8]** Chaos Engineering & Odolnosť voči pádu: ✅ **PASS**
- **[Úroveň 9]** Fintech Meny (CZK/EUR) & Centové stavy: ✅ **PASS**
- **[Úroveň 10]** E2E Kontrakt: Aplikácia Výpis ➔ WC Eshop: ✅ **PASS**
- **[Úroveň 11]** Auto-Updater: api.vorkei.com Aktualizácie: ✅ **PASS**
- **[Úroveň 12]** Stripe Webhook, Idempotencia & Wallet Sync (11 scenárov vrátane vratiek a sporov): ✅ **PASS**
- **[Úroveň 13]** Hranica Dôvery, Súbežnosť & Deduplikácia (5 scenárov vrátane jednorazového claim tokenu): ✅ **PASS**

### B. E2E 7-krokový životný cyklus platby (`verify_e2e_full_cycle.php`)
1. **Objednávka balíčka Single (5 € / 1 kredit):** Vytvorená relácia, vygenerovaný 48-znakový kryptografický `claim_token`. ✅ **PASS**
2. **Stripe Webhook (`checkout.session.completed`):** Validovaný HMAC-SHA256 podpis, pripísaný presne 1 kredit. ✅ **PASS**
3. **Doručenie tokenu na `/order-success`:** Bez tokenu redigované (`VRK-CE51...B1E7`), s platným claim tokenom jednorazovo vydaný Bearer kľúč. Opakovaný claim (Replay attack) striktne odmietnutý. ✅ **PASS**
4. **Spotreba kreditu cez `/sync`:** 1 výpis úspešne spracovaný, kredit atómovo znížený (1 ➔ 0). ✅ **PASS**
5. **Hard Paywall:** Pokus o ďalšiu synchronizáciu s 0 kreditmi striktne zablokovaný s kódom HTTP 402 Payment Required. ✅ **PASS**
6. **Platobný spor (`charge.dispute.created`):** Token zákazníka automaticky zmrazený (`is_active = 0`), API volania zablokované (HTTP 401/403). ✅ **PASS**
7. **Vyriešenie sporu v prospech obchodníka (`charge.dispute.closed: won`):** Token automaticky obnovený (`is_active = 1`). ✅ **PASS**

### C. Frontend & TypeScript Kontrola
- `pnpm typecheck` (`tsc --noEmit`): **0 chýb (PASS)**
- `pnpm build` (Nitro / Vercel Web Entry): **Úspešný build (PASS)**

---

## 2. STRIPE WEBHOOK & BIZNIS PRAVIDLÁ

### Konfigurácia Webhooku v Stripe Dashboarde
- **Webhook Endpoint URL:** `https://pdf-t3.vorkei.com/wp-json/vorkei/v1/stripe/webhook`
- **Prihlásené udalosti:**
  - `checkout.session.completed`
  - `checkout.session.async_payment_succeeded`
  - `checkout.session.async_payment_failed`
  - `charge.refunded`
  - `charge.dispute.created`
  - `charge.dispute.closed`

### Nastavenie serverových konštánt (`wp-config.php`)
```php
// Stripe Live/Test konfigurácia pre Vorkei Statement Bridge
define( 'VORKEI_STRIPE_SECRET_KEY', 'sk_live_...' ); // alebo sk_test_... pre testovací režim
define( 'VORKEI_STRIPE_WEBHOOK_SECRET', 'whsec_...' );
```

---

## 3. NASADENIE BALÍČKA NA PRODUKČNÝ WORDPRESS

Keďže dočasná konzolová relácia WebSupport SSH na porte 26749 expirovala, inštalácia overeného balíčka na produkčný server prebieha cez WP Administráciu alebo WebSupport WebFTP:

### Postup A: WordPress Admin (Odporúčané, 1 minúta)
1. Prihláste sa do administrácie: `https://pdf-t3.vorkei.com/wp-admin/plugin-install.php?tab=upload`
2. Stiahnite overený balíček:  
   👉 `https://pdf-t3.vorkei.com/wp-content/uploads/2026/10/vorkei-statement-bridge.zip` (SHA-256: `53d91a98...`)
3. Vyberte stiahnutý súbor a kliknite **Inštalovať teraz**.
4. WordPress automaticky deteguje existujúci plugin a zobrazí tlačidlo **Nahradiť existujúci nahraným (Replace current with uploaded)**.
5. Kliknite **Nahradiť**. Tým sa na serveri okamžite aktualizuje kód a aktivátor spustí databázové migrácie.

### Postup B: WebSupport WebAdmin / WebFTP
1. Otvorte správcu súborov WebFTP pre doménu `vorkei.com`.
2. Prejdite do `vorkei.com/sub/pdf-t3/wp-content/plugins/`.
3. Nahrajte a rozbaľte `vorkei-statement-bridge.zip`.

---

## 4. INFRAŠTRUKTÚRNE ZABEZPEČENIE

### A. Revokácia starého Application Password
1. V administrácii WordPressu prejdite do `https://pdf-t3.vorkei.com/wp-admin/profile.php`.
2. V sekcii **Heslá aplikácií (Application Passwords)** kliknite na **Odvolať (Revoke)** pri starom hesle pre `support@vorkei.c`.
3. Vytvorte si nové jedinečné heslo iba v prípade potreby externej správy cez REST API.

### B. SSL certifikát pre `app.pdf-t3.vorkei.com`
- Súčasný wildcard certifikát pokrýva iba prvú úroveň (`*.vorkei.com`). Subdoména druhej úrovne (`app.pdf-t3.vorkei.com`) vyžaduje:
  - V WebSupport WebAdmin pridať `app.pdf-t3.vorkei.com` do Let's Encrypt SAN zoznamu, ALEBO
  - Nasmérovať CNAME záznam `app.pdf-t3.vorkei.com` na Vercel (`cname.vercel-dns.com`), ktorý automaticky spravuje a obnovuje platný SSL certifikát.
