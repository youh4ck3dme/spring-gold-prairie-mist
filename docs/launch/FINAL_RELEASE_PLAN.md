# 🚀 FINAL PRODUCTION RELEASE & ROLLBACK PLAN
## Vorkei Statement Bridge — Paid Launch Release Protocol (Mission 08)

**Dátum vydania:** 30. október 2026  
**Cieľová verzia:** `v1.0.0-paid-launch`  
**Hostiteľ:** `https://pdf-t3.vorkei.com` (WordPress 6.7.x / PHP 8.x / MySQL)  
**Frontend:** `https://app.pdf-t3.vorkei.com` (Next.js / Nitro on Vercel)  
**Release Artifact:** `vorkei-statement-bridge.zip` (SHA-256: `ed239613642bc4048b4fe768c2ecb81540ef900cc5d5289d56ac42d773e97fa8`)  

---

## 1. Predpoklady a Kontrolný Zoznam Pred Nasadením (Pre-Flight)

Pred začatím produkčného nasadenia musí byť splnené:
- [x] Všetkých 13 úrovní testovacej pyramídy úspešne vykonaných (100% PASS).
- [x] TypeScript typecheck a produkčný build dokončený bez chýb.
- [x] Distribučný ZIP balíček zostavený s overeným SHA-256 kontrolným súčtom.
- [x] Kód očistený od akýchkoľvek citlivých hesiel a pevných tajomstiev.
- [ ] **Výslovné schválenie používateľa na merge do `main` a produkčné nasadenie.**

---

## 2. Krok za Krokom: Postup Produkčného Nasadenia

### Krok 1: Zlúčenie do `main` a Git Tagging (Po Schválení)
```bash
# 1. Kontrola vetvy a stavu
git checkout main
git pull origin main

# 2. Zlúčenie pripravenej feature vetvy (fast-forward alebo merge commit)
git merge feat/statement-bridge-paid-launch --no-ff -m "chore(release): v1.0.0-paid-launch production reconciliation & P0 security hardening"

# 3. Označenie releasu git tagom
git tag -a v1.0.0-paid-launch -m "Release v1.0.0-paid-launch with hardened Stripe token claim & WooCommerce protection"

# 4. Push do vzdialeného repozitára
git push origin main --tags
```

---

### Krok 2: Nasadenie WordPress Pluginu na `pdf-t3.vorkei.com`

Nasadenie je možné vykonať dvomi bezpečnými cestami:

#### Možnosť A: Priamo cez SSH na server `37.9.175.196` (Odporúčané)
```bash
# 1. Záloha existujúceho pluginu na serveri
ssh user@37.9.175.196 "cp -r /var/www/html/wp-content/plugins/vorkei-statement-bridge /var/www/html/wp-content/plugins/vorkei-statement-bridge.bak-$(date +%Y%m%d%H%M%S)"

# 2. Nahratie nového release ZIPu na server
scp vorkei-statement-bridge.zip user@37.9.175.196:/tmp/vorkei-statement-bridge.zip

# 3. Rozbalenie balíčka do priečinka pluginov s korektnými oprávneniami
ssh user@37.9.175.196 "unzip -o /tmp/vorkei-statement-bridge.zip -d /var/www/html/wp-content/plugins/ && chown -R www-data:www-data /var/www/html/wp-content/plugins/vorkei-statement-bridge"

# 4. Vykonanie databázových migrácií (cez WP-CLI)
ssh user@37.9.175.196 "wp plugin activate vorkei-statement-bridge --allow-root"

# 5. Aktualizácia verejného ZIPu v Media Library pre zákazníkov
ssh user@37.9.175.196 "cp /tmp/vorkei-statement-bridge.zip /var/www/html/wp-content/uploads/2026/10/vorkei-statement-bridge.zip && chown www-data:www-data /var/www/html/wp-content/uploads/2026/10/vorkei-statement-bridge.zip"
```

#### Možnosť B: Cez WordPress Admin Rozhranie
1. Prihlásiť sa na `https://pdf-t3.vorkei.com/wp-admin/`.
2. Prejsť do **Pluginy ➔ Pridať nový plugin ➔ Nahrať plugin**.
3. Zvoliť lokálny súbor `vorkei-statement-bridge.zip` (311 213 bajtov).
4. Kliknúť na **Inštalovať teraz** a zvoliť **Nahradiť existujúci novou verziou**.
5. Preveriť stav aktivácie.

---

### Krok 3: Konfigurácia Stripe Webhooku

V Stripe Dashboarde (`https://dashboard.stripe.com/webhooks`) vytvoriť koncový bod:
- **URL adresa koncového bodu:**
  `https://pdf-t3.vorkei.com/wp-json/vorkei/v1/stripe-webhook`
- **Počúvané udalosti (Events to send):**
  1. `checkout.session.completed` (Vytvorenie peňaženky a udelenie kreditov)
  2. `checkout.session.async_payment_succeeded` (Oneskorené SEPA platby)
  3. `checkout.session.async_payment_failed` (Zlyhanie oneskorenej platby)
  4. `charge.refunded` (Automatické stiahnutie kreditov pri vratke)
  5. `charge.dispute.created` (Pozastavenie peňaženky pri platobnom spore)
- **Získanie podpisovacieho kľúča (Signing Secret):**
  - Kľúč `whsec_...` uložiť do nastavení WordPressu v:
    **Nastavenia ➔ Vorkei Bridge ➔ Stripe Webhook Secret**.

---

### Krok 4: Nasadenie Frontendu na Vercel

```bash
# Automatické nasadenie prebehne po pushnutí do main na Vercel:
git push origin main

# Alebo manuálne cez Vercel CLI:
npx nitro deploy --prebuilt
```

---

### Krok 5: Overovacie Dymové Testy (Smoke Tests)

Po nasadení vykonať verifikačný skript:
```bash
# 1. Overenie verejného kontrolného súčtu v Media Library:
curl -sL https://pdf-t3.vorkei.com/wp-content/uploads/2026/10/vorkei-statement-bridge.zip | sha256sum
# Očakávaný výsledok: ed239613642bc4048b4fe768c2ecb81540ef900cc5d5289d56ac42d773e97fa8

# 2. Overenie REST API trasy pre claim tokeny:
curl -s -X POST https://pdf-t3.vorkei.com/wp-json/vorkei/v1/order/claim-token -H "Content-Type: application/json" -d "{}"
# Očakávaný výsledok: HTTP 400 s kódom "missing_params" (potvrdzuje prítomnosť nového endpointu)

# 3. Overenie ochrany neautorizovaného prístupu:
curl -s -o /dev/null -w "%{http_code}\n" https://pdf-t3.vorkei.com/wp-json/vorkei/v1/wallets
# Očakávaný výsledok: 401
```

---

## 3. Plán Návratu Späť (Rollback Procedure)

V prípade akejkoľvek neočakávanej anomálie na produkcii je postup návratu okamžitý:

```bash
# 1. Obnova predchádzajúcej verzie pluginu zo zálohy:
ssh user@37.9.175.196 "rm -rf /var/www/html/wp-content/plugins/vorkei-statement-bridge && mv /var/www/html/wp-content/plugins/vorkei-statement-bridge.bak-* /var/www/html/wp-content/plugins/vorkei-statement-bridge"

# 2. Vyčistenie WordPress opcache a transientov:
ssh user@37.9.175.196 "wp transient delete --all --allow-root && wp cache flush --allow-root"

# 3. Rollback Git vetvy main (ak bol merge vykonaný):
git checkout main
git revert -m 1 HEAD --no-edit
git push origin main

# 4. Rollback Vercel nasadenia:
# V rozhraní Vercel Dashboard kliknúť na predchádzajúci úspešný deployment a zvoliť "Instant Rollback".
```
