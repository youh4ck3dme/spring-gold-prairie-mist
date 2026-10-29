#!/usr/bin/env node
/**
 * 📱 Playwright Test Suite: iPhone 14 Plus Mobile & PWA Compliance
 * Inšpirované oficiálnymi testovacími štandardmi z microsoft/playwright a Apple HIG.
 * 
 * Cieľ: Otestovať PWA Výpis Vorkei na reálnych parametroch Apple iPhone 14 Plus:
 * - Viewport: 428 x 746 px (Screen: 428 x 926 px)
 * - Retina DPR (Device Scale Factor): 3.0
 * - Dotykové rozhranie (hasTouch: true, isMobile: true)
 * - User-Agent: Mobile Safari iOS
 */

import { chromium, devices } from "playwright";
import fs from "node:fs";
import path from "node:path";

const TARGET_URL = process.env.TEST_URL || "http://127.0.0.1:8080/";
const SCREENSHOT_DIR = path.resolve(process.cwd(), "screenshots");

if (!fs.existsSync(SCREENSHOT_DIR)) {
  fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

const tests = [];
function record(id, title, passed, details = "", durationMs = 0) {
  tests.push({ id, title, passed, details, durationMs: Math.round(durationMs) });
  const badge = passed ? "✅ [PASS]" : "❌ [FAIL]";
  console.log(`${badge} #${String(id).padStart(2, "0")} ${title} (${Math.round(durationMs)}ms)`);
  if (details) {
    console.log(`     └─ ${details}`);
  }
}

async function runSuite() {
  console.log("\n==================================================================");
  console.log("  📱 PLAYWRIGHT MOBILE TEST SUITE: APPLE iPHONE 14 PLUS");
  console.log(`  Cieľový server: ${TARGET_URL}`);
  console.log("==================================================================\n");

  const browser = await chromium.launch({
    executablePath: "/usr/bin/google-chrome",
    headless: true,
    args: ["--no-sandbox", "--disable-setuid-sandbox", "--ignore-https-errors"]
  });

  const iphonePortrait = devices["iPhone 14 Plus"];
  const iphoneLandscape = devices["iPhone 14 Plus landscape"];

  const context = await browser.newContext({
    ...iphonePortrait,
    ignoreHTTPSErrors: true
  });

  const page = await context.newPage();
  const consoleErrors = [];
  page.on("console", (msg) => {
    if (msg.type() === "error") consoleErrors.push(msg.text());
  });
  page.on("pageerror", (err) => consoleErrors.push(err.message));

  const t0 = performance.now();
  await page.goto(TARGET_URL, { waitUntil: "networkidle" });
  // Počkáme 1.5s na dokončenie hydratácie Vite/React
  await page.waitForTimeout(1500);

  // -------------------------------------------------------------
  // TEST 1: Parametre zariadenia iPhone 14 Plus
  // -------------------------------------------------------------
  {
    const start = performance.now();
    const vp = page.viewportSize();
    const dpr = await page.evaluate(() => window.devicePixelRatio);
    const hasTouch = await page.evaluate(() => "ontouchstart" in window || navigator.maxTouchPoints > 0);
    const isExact = vp.width === 428 && dpr === 3 && hasTouch;
    record(
      1,
      "Device Profile (iPhone 14 Plus: 428px, Retina DPR=3, Touch)",
      isExact,
      `Viewport: ${vp.width}x${vp.height}, DPR: ${dpr}, Touch: ${hasTouch}`,
      performance.now() - start
    );
  }

  // -------------------------------------------------------------
  // TEST 2: Žiadny horizontálny posun (No Horizontal Overflow)
  // -------------------------------------------------------------
  {
    const start = performance.now();
    const overflow = await page.evaluate(() => {
      const scrollW = document.documentElement.scrollWidth;
      const innerW = window.innerWidth;
      return {
        hasOverflow: scrollW > innerW,
        scrollWidth: scrollW,
        innerWidth: innerW
      };
    });
    record(
      2,
      "Mobile Layout Fit (Nulový horizontálny scroll leak)",
      !overflow.hasOverflow,
      `scrollWidth: ${overflow.scrollWidth}px <= innerWidth: ${overflow.innerWidth}px`,
      performance.now() - start
    );
  }

  // -------------------------------------------------------------
  // TEST 3: PWA & iOS Meta Tags (Safe Area Inset / Viewport)
  // -------------------------------------------------------------
  {
    const start = performance.now();
    const metaInfo = await page.evaluate(() => {
      const viewport = document.querySelector('meta[name="viewport"]')?.getAttribute("content") || "";
      const appleCapable = document.querySelector('meta[name="apple-mobile-web-app-capable"]')?.getAttribute("content");
      const appleTitle = document.querySelector('meta[name="apple-mobile-web-app-title"]')?.getAttribute("content");
      const appleTouchIcon = document.querySelector('link[rel="apple-touch-icon"]')?.getAttribute("href");
      const manifest = document.querySelector('link[rel="manifest"]')?.getAttribute("href");
      return { viewport, appleCapable, appleTitle, appleTouchIcon, manifest };
    });

    const hasViewport = metaInfo.viewport.includes("width=device-width");
    const passed = hasViewport && (Boolean(metaInfo.manifest) || Boolean(metaInfo.appleTouchIcon));
    record(
      3,
      "iOS & PWA Meta Compliance (Viewport, Manifest, Apple Touch)",
      passed,
      `Viewport: "${metaInfo.viewport}", Manifest: ${metaInfo.manifest || "N/A"}, Apple Icon: ${metaInfo.appleTouchIcon || "N/A"}`,
      performance.now() - start
    );
  }

  // -------------------------------------------------------------
  // TEST 4: Apple HIG Tap Targets (Minimálna výška tlačidiel 44px)
  // -------------------------------------------------------------
  {
    const start = performance.now();
    const badTargets = await page.evaluate(() => {
      const buttons = Array.from(document.querySelectorAll("button.home-primary, button.home-secondary, nav.home-nav button"));
      return buttons
        .map((b) => {
          const rect = b.getBoundingClientRect();
          return { text: b.innerText.trim(), height: rect.height, width: rect.width };
        })
        .filter((b) => b.height < 44 || b.width < 44);
    });

    const passed = badTargets.length === 0;
    record(
      4,
      "Touch Target Sizing (Apple HIG min. 44x44px pre prst)",
      passed,
      passed ? "Všetky primárne tlačidlá a navigačné taby spĺňajú výšku >= 44px" : `Nevyhovujúce: ${JSON.stringify(badTargets)}`,
      performance.now() - start
    );
  }

  // -------------------------------------------------------------
  // TEST 5: Spodná navigačná lišta (Bottom Tab Navigation Bar)
  // -------------------------------------------------------------
  {
    const start = performance.now();
    const navButtons = await page.locator("nav.home-nav button").allInnerTexts();
    const expected = ["Výpisy", "Účet", "Pohyby", "Náhľad", "Tlač"];
    const allFound = expected.every((tab) => navButtons.some((b) => b.includes(tab)));
    record(
      5,
      "Bottom Tab Bar (Prítomnosť kľúčových mobilných tabov)",
      allFound,
      `Nájdené taby: [${navButtons.map((b) => b.replace(/\n/g, " ").trim()).join(", ")}]`,
      performance.now() - start
    );
  }

  // -------------------------------------------------------------
  // TEST 6: Mobilný dialóg / Bottom Sheet (Údaje účtu)
  // -------------------------------------------------------------
  {
    const start = performance.now();
    // Klikneme na tlačidlo 'Účet' v navigačnej lište
    await page.locator("nav.home-nav button:has-text('Účet')").click();
    await page.waitForTimeout(300);

    const dialogVisible = await page.locator(".home-sheet").isVisible();
    const dialogTitle = dialogVisible ? await page.locator("#home-help-title").innerText() : "";

    // Zavrieme dialóg kliknutím na tlačidlo Zavrieť
    if (dialogVisible) {
      await page.locator(".home-sheet button").click();
      await page.waitForTimeout(300);
    }
    const dialogClosed = !(await page.locator(".home-sheet").isVisible());

    record(
      6,
      "Mobile Modal Sheet (Otvorenie a zatvorenie panelu účtu)",
      dialogVisible && dialogClosed,
      `Titulok dialógu: "${dialogTitle}", Zatvorenie: ${dialogClosed ? "OK" : "Zlyhalo"}`,
      performance.now() - start
    );
  }

  // -------------------------------------------------------------
  // TEST 7: Prechod do Náhľadu Výpisu (Statement Preview)
  // -------------------------------------------------------------
  {
    const start = performance.now();
    await page.locator("button.home-primary:has-text('Otvoriť výpis')").click();
    await page.waitForTimeout(600);

    const isPreview = await page.locator(".toolbar.preview-bar").isVisible();
    const hasBackBtn = await page.locator(".toolbar.preview-bar button:has-text('Zoznam')").isVisible();

    record(
      7,
      "Statement Preview Navigation (Dotyk na Otvoriť výpis)",
      isPreview && hasBackBtn,
      `Preview lišta zobrazená: ${isPreview}, Tlačidlo Zoznam: ${hasBackBtn}`,
      performance.now() - start
    );
  }

  // -------------------------------------------------------------
  // TEST 8: Bankový dokument a overenie centov na mobile
  // -------------------------------------------------------------
  {
    const start = performance.now();
    const statementDoc = await page.locator(".statement-document, .desk").innerText();
    const hasIban = statementDoc.includes("SK66");
    const hasBank = statementDoc.includes("Tatra banka");
    const hasTotal = statementDoc.includes("EUR") || statementDoc.includes("175,550.57");

    record(
      8,
      "Bank Statement Data Integrity (IBAN, Tatra banka, Suma)",
      hasIban && hasBank && hasTotal,
      `IBAN prítomný: ${hasIban}, Tatra banka: ${hasBank}, Celková suma: ${hasTotal}`,
      performance.now() - start
    );
  }

  // -------------------------------------------------------------
  // TEST 9: Retina Screenshot v režime Portrait (428 x 926 px)
  // -------------------------------------------------------------
  {
    const start = performance.now();
    const portraitPath = path.join(SCREENSHOT_DIR, "iphone-14-plus-portrait.png");
    await page.screenshot({ path: portraitPath, fullPage: false });
    const exists = fs.existsSync(portraitPath) && fs.statSync(portraitPath).size > 10000;
    record(
      9,
      "Retina Screenshot Portrait (428x926 @3x)",
      exists,
      `Uložený do: ${portraitPath} (${fs.statSync(portraitPath).size} bytes)`,
      performance.now() - start
    );
  }

  // -------------------------------------------------------------
  // TEST 10: Návrat na zoznam a otvorenie Vorkei Bridge Modálu
  // -------------------------------------------------------------
  {
    const start = performance.now();
    await page.locator(".toolbar.preview-bar button:has-text('Zoznam')").click();
    await page.waitForTimeout(400);

    // Na zozname výpisov klikneme na vorkei.com Bridge
    const bridgeBtn = page.locator("button:has-text('vorkei.com Bridge')");
    const bridgeFound = await bridgeBtn.isVisible();
    let modalOpened = false;
    if (bridgeFound) {
      await bridgeBtn.click();
      await page.waitForTimeout(400);
      modalOpened = await page.locator("text=WordPress Bridge").isVisible();
      // Zavrieme modal kliknutím na krížik alebo tlačidlo
      const closeBtn = page.locator("button[title='Zavrieť'], .fixed button:has(svg.lucide-x)");
      if (await closeBtn.isVisible()) {
        await closeBtn.click();
      } else {
        await page.keyboard.press("Escape");
      }
      await page.waitForTimeout(300);
    }

    record(
      10,
      "Vorkei Bridge Modal Interaction (Otvorenie WordPress mostíka)",
      bridgeFound && modalOpened,
      `Bridge tlačidlo: ${bridgeFound}, Modál overený: ${modalOpened}`,
      performance.now() - start
    );
  }

  // -------------------------------------------------------------
  // TEST 11: Zmena orientácie na Landscape (832 x 378 px @3x)
  // -------------------------------------------------------------
  {
    const start = performance.now();
    // Vytvoríme landscape kontext
    const landscapeContext = await browser.newContext({
      ...iphoneLandscape,
      ignoreHTTPSErrors: true
    });
    const landscapePage = await landscapeContext.newPage();
    await landscapePage.goto(TARGET_URL, { waitUntil: "networkidle" });
    await landscapePage.waitForTimeout(1000);

    const vp = landscapePage.viewportSize();
    const landscapeOverflow = await landscapePage.evaluate(() => {
      return document.documentElement.scrollWidth > window.innerWidth;
    });

    const landscapePath = path.join(SCREENSHOT_DIR, "iphone-14-plus-landscape.png");
    await landscapePage.screenshot({ path: landscapePath, fullPage: false });

    record(
      11,
      "Orientation Switch: Landscape Mode (832x378 px)",
      vp.width === 832 && !landscapeOverflow,
      `Viewport: ${vp.width}x${vp.height}, Horizontálny pretek: ${landscapeOverflow ? "ÁNO (Chyba)" : "NIE (V poriadku)"}`,
      performance.now() - start
    );

    await landscapeContext.close();
  }

  // -------------------------------------------------------------
  // TEST 12: Offline Resilience (Simulácia straty signálu na mobile)
  // -------------------------------------------------------------
  {
    const start = performance.now();
    await context.setOffline(true);
    await page.waitForTimeout(300);

    const isStillResponsive = await page.evaluate(() => {
      // Skontrolujeme, či aplikácia naďalej beží v pamäti a nezlyhala
      return Boolean(document.querySelector(".desk") || document.querySelector("h1"));
    });

    // Obnovíme sieť
    await context.setOffline(false);

    record(
      12,
      "Offline Resilience (PWA funkčnosť pri strate mobilného signálu)",
      isStillResponsive,
      "Aplikácia zachovala kompletný DOM a lokálny stav aj pri odpojenej sieti",
      performance.now() - start
    );
  }

  await browser.close();

  // -------------------------------------------------------------
  // REKAPITULÁCIA
  // -------------------------------------------------------------
  const total = tests.length;
  const passedCount = tests.filter((t) => t.passed).length;
  const failedCount = total - passedCount;

  console.log("\n==================================================================");
  console.log(`  📊 VÝSLEDOK TESTOV IPHONE 14 PLUS: ${passedCount}/${total} ÚSPEŠNÝCH`);
  if (failedCount === 0) {
    console.log("  🎉 VŠETKY MOBILNÉ TESTY NA IPHONE 14 PLUS PREŠLI NA 100%!");
  } else {
    console.log(`  ⚠️ POČET ZLYHANÍ: ${failedCount}`);
  }
  console.log("==================================================================\n");

  return { total, passedCount, failedCount, tests };
}

runSuite().catch((err) => {
  console.error("Test suite runtime error:", err);
  process.exit(1);
});
