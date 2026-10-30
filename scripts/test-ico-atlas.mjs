import { chromium } from "playwright";
import fs from "node:fs";
import path from "node:path";

const TARGET_URL = process.env.TEST_URL || "http://127.0.0.1:8080/";
const SCREENSHOT_DIR = path.resolve(process.cwd(), "screenshots");

async function run() {
  console.log("\n==================================================================");
  console.log("  🛡️ PLAYWRIGHT E2E TEST: ICO-ATLAS & RISK RADAR (PWA)");
  console.log(`  Target: ${TARGET_URL}`);
  console.log("==================================================================\n");

  const browser = await chromium.launch({
    executablePath: "/usr/bin/google-chrome",
    headless: true,
    args: ["--no-sandbox", "--disable-setuid-sandbox", "--ignore-https-errors"]
  });

  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  
  const consoleErrors = [];
  page.on("console", (msg) => {
    if (msg.type() === "error") consoleErrors.push(msg.text());
  });
  page.on("pageerror", (err) => consoleErrors.push(err.message));

  await page.goto(TARGET_URL, { waitUntil: "networkidle" });
  await page.waitForTimeout(1000);

  // 1. Prechod do zoznamu výpisov (klik na "Všetky výpisy")
  console.log("1. Navigating to Statement List...");
  await page.click("button.home-secondary:has-text('Všetky výpisy')");
  await page.waitForTimeout(500);

  // 2. Overenie prítomnosti tlačidla ICO-ATLAS Radar v toolbare
  console.log("2. Checking ICO-ATLAS Radar button in toolbar...");
  const atlasBtn = page.locator("button:has-text('ICO-ATLAS Radar')");
  if (!(await atlasBtn.isVisible())) {
    throw new Error("Tlačidlo ICO-ATLAS Radar sa nenašlo v toolbare!");
  }
  console.log("   ✅ ICO-ATLAS Radar button visible");

  // 3. Otvorenie ICO-ATLAS modálu a test ESET IČO
  console.log("3. Testing ICO-ATLAS Modal with ESET (31333532)...");
  await atlasBtn.click();
  await page.waitForTimeout(400);

  const modalTitle = page.locator("text=ICO-ATLAS · Register SR & Risk Radar");
  if (!(await modalTitle.isVisible())) {
    throw new Error("Modál ICO-ATLAS sa neotvoril!");
  }

  // Klikneme na rýchly test pill "ESET (31333532)"
  await page.click("button:has-text('ESET (31333532)')");
  await page.waitForTimeout(1200);

  const esetFound = await page.locator("strong:has-text('ESET, spol. s r.o.')").isVisible();
  console.log(`   ✅ ESET search result: ${esetFound ? "FOUND" : "NOT FOUND"}`);
  if (!esetFound) throw new Error("ESET nebol úspešne nájdený!");

  // 4. Test Risk Radar v modále (Zaniknutá firma 31707921)
  console.log("4. Testing Risk Radar with Dissolved entity (31707921)...");
  await page.click("button:has-text('Konkurz (31707921)')");
  await page.waitForTimeout(1200);

  const riskRadarAlert = await page.locator("text=RISK RADAR: RIZIKOVÝ SUBJEKT!").isVisible();
  console.log(`   ✅ Risk Radar detection: ${riskRadarAlert ? "TRIGGERED (PASS)" : "FAILED"}`);
  if (!riskRadarAlert) throw new Error("Risk Radar nezachytil subjekt v konkurze!");

  // Screenshot modálu
  const modalScreenshot = path.join(SCREENSHOT_DIR, "ico-atlas-modal.png");
  await page.screenshot({ path: modalScreenshot });
  console.log(`   📸 Modal screenshot saved to: ${modalScreenshot}`);

  // Zavrieme modál
  await page.click("div[role='dialog'] button:has-text('Zavrieť'), div.fixed button:has-text('Zavrieť')");
  await page.waitForTimeout(400);

  // 5. Prechod do editora výpisu (klik na Upraviť)
  console.log("5. Navigating to Statement Editor to test Smart 1-Click Onboarding...");
  await page.click("li.card:first-child button:has-text('Upraviť')");
  await page.waitForTimeout(600);

  // 6. Test Smart Onboarding formulára:
  // Výber 'Nová protistrana' je predvolený ak nie je vybraná existujúca
  console.log("6. Testing 1-Click Auto-Enrichment in New Movement form...");
  const icoInput = page.locator("input[placeholder='napr. 31333532']");
  if (!(await icoInput.isVisible())) {
    throw new Error("IČO Smart Input sa nenašiel vo formulári nového pohybu!");
  }

  // Zadáme ESET IČO: 31333532
  await icoInput.fill("31333532");
  await page.waitForTimeout(1500);

  // Overíme, že Názov sa automaticky vyplnil na "ESET, spol. s r.o."
  const nameInput = page.locator("input[placeholder='Úradný názov firmy']");
  const autoFilledName = await nameInput.inputValue();
  console.log(`   ✅ Auto-filled company name: "${autoFilledName}"`);
  if (!autoFilledName.includes("ESET")) {
    throw new Error(`Očakával sa názov obsahujúci ESET, ale prišlo: "${autoFilledName}"`);
  }

  // 7. Test auto-SWIFT derivácie pri zadaní IBANu
  const ibanInput = page.locator("input[placeholder='SK00 0000 0000 0000 0000 0000']");
  await ibanInput.fill("SK66 1100 0000 0029 4112 6907");
  await page.waitForTimeout(300);

  const swiftInput = page.locator("input[placeholder='napr. TATRSKBX']");
  const autoSwift = await swiftInput.inputValue();
  console.log(`   ✅ Auto-suggested SWIFT from SK1100 IBAN: "${autoSwift}"`);
  if (autoSwift !== "TATRSKBX") {
    throw new Error(`Očakával sa SWIFT TATRSKBX, ale prišlo: "${autoSwift}"`);
  }

  // 8. Test zmeny na rizikové IČO v samotnom formulári (31707921)
  console.log("8. Testing Risk Radar banner inside movement form...");
  await icoInput.fill("31707921");
  await page.click("button:has-text('Overiť IČO')");
  await page.waitForTimeout(1500);

  const formRiskAlert = await page.locator("text=RISK RADAR: RIZIKOVÝ SUBJEKT!").isVisible();
  console.log(`   ✅ In-Form Risk Radar detection: ${formRiskAlert ? "TRIGGERED (PASS)" : "FAILED"}`);
  if (!formRiskAlert) throw new Error("In-Form Risk Radar nezachytil rizikový subjekt!");

  // Screenshot editora so Smart Onboarding a Risk Radar
  const formScreenshot = path.join(SCREENSHOT_DIR, "ico-atlas-risk-radar-form.png");
  await page.screenshot({ path: formScreenshot });
  console.log(`   📸 Form screenshot saved to: ${formScreenshot}`);

  await browser.close();

  console.log("\n==================================================================");
  console.log("  🎉 VŠETKY TESTY SMART 1-CLICK ONBOARDING & RISK RADAR PREŠLI!");
  console.log("==================================================================\n");
}

run().catch((err) => {
  console.error("Test failed:", err);
  process.exit(1);
});
