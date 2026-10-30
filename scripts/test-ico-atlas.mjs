import { chromium } from "playwright";
import fs from "node:fs";
import path from "node:path";

const TARGET_URL = process.env.TEST_URL || "http://127.0.0.1:8080/";
const SCREENSHOT_DIR = path.resolve(process.cwd(), "screenshots");

async function run() {
  console.log("\n==================================================================");
  console.log("  🛡️ PLAYWRIGHT E2E TEST: ICO-ATLAS & RISK RADAR (P0 VERIFICATION)");
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
  await page.waitForTimeout(1500);

  const esetFound = await page.locator("strong:has-text('ESET, spol. s r.o.')").isVisible();
  console.log(`   ✅ ESET search result: ${esetFound ? "FOUND" : "NOT FOUND"}`);
  if (!esetFound) throw new Error("ESET nebol úspešne nájdený!");

  // 4. Test Risk Radar v modále (Zaniknutá firma 31707921)
  console.log("4. Testing Risk Radar with Dissolved entity (31707921)...");
  await page.click("button:has-text('Konkurz (31707921)')");
  await page.waitForTimeout(1500);

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

  // 7. P0 TEST: Modifikácia IČO okamžite resetuje staré údaje predchádzajúcej firmy
  console.log("7. P0 TEST: Modifying IČO clears auto-filled company data immediately...");
  await icoInput.fill("12345");
  await page.waitForTimeout(300);
  const clearedName = await nameInput.inputValue();
  console.log(`   ✅ Cleared company name on IČO change: "${clearedName}" (is empty: ${clearedName === ""})`);
  if (clearedName !== "") {
    throw new Error(`P0 FAIL: Starý názov firmy '${clearedName}' zostal visieť po zmene IČO!`);
  }

  // Znovu zadáme ESET a overíme
  await icoInput.fill("31333532");
  await page.click("button:has-text('Overiť IČO')");
  await page.waitForTimeout(1500);

  // 8. Test auto-SWIFT derivácie pri zadaní IBANu
  console.log("8. Testing Auto-SWIFT derivation from SK IBAN...");
  const ibanInput = page.locator("input[placeholder='SK00 0000 0000 0000 0000 0000']");
  await ibanInput.fill("SK66 1100 0000 0029 4112 6907");
  await page.waitForTimeout(300);

  const swiftInput = page.locator("input[placeholder='napr. TATRSKBX']");
  const autoSwift = await swiftInput.inputValue();
  console.log(`   ✅ Auto-suggested SWIFT from SK1100 IBAN: "${autoSwift}"`);
  if (autoSwift !== "TATRSKBX") {
    throw new Error(`Očakával sa SWIFT TATRSKBX, ale prišlo: "${autoSwift}"`);
  }

  // 9. Pridanie overeného pohybu a overenie zeleného odznaku ✓ IČO
  console.log("9. P0 TEST: Adding verified movement and verifying Green '✓ IČO' badge...");
  const refInput = page.locator("form.panel label:has-text('Referencia') input");
  await refInput.fill("Úhrada faktúry ESET");
  const amountInput = page.locator("form.panel label:has-text('Suma') input");
  await amountInput.fill("1500.00");
  const addMoveBtn = page.locator("form.panel button[type='submit']:has-text('Pridať pohyb')");
  await addMoveBtn.click();
  await page.waitForTimeout(800);

  // Overíme, že v zozname pohybov sa objavil zelený odznak ✓ IČO 31333532
  const verifiedBadge = page.locator("span:has-text('✓ IČO 31333532')");
  const isVerifiedBadgeVisible = await verifiedBadge.isVisible();
  console.log(`   ✅ Verified Active Badge '✓ IČO 31333532': ${isVerifiedBadgeVisible ? "VISIBLE (PASS)" : "MISSING (FAIL)"}`);
  if (!isVerifiedBadgeVisible) {
    throw new Error("P0 FAIL: Zelený odznak ✓ IČO 31333532 sa nezobrazil pri overenej aktívnej firme!");
  }

  // 10. Test rizikového subjektu (Konkurz 31707921)
  console.log("10. Testing Risk Radar movement with Red Badge...");
  await page.selectOption("select:has(option:has-text('Nová protistrana'))", "");
  await page.waitForTimeout(300);

  await icoInput.fill("31707921");
  await page.click("button:has-text('Overiť IČO')");
  await page.waitForTimeout(1500);

  const formRiskAlert = await page.locator("text=RISK RADAR: RIZIKOVÝ SUBJEKT!").isVisible();
  console.log(`   ✅ In-Form Risk Radar detection: ${formRiskAlert ? "TRIGGERED (PASS)" : "FAILED"}`);
  if (!formRiskAlert) throw new Error("In-Form Risk Radar nezachytil rizikový subjekt!");

  await refInput.fill("Riziková platba");
  await ibanInput.fill("SK66 1100 0000 0029 4217 0336");
  await amountInput.fill("250.00");
  await addMoveBtn.click();
  await page.waitForTimeout(800);

  // Overíme prítomnosť červeného odznaku "Rizikový partner"
  const riskBadge = page.locator("span:has-text('Rizikový partner')");
  const isRiskBadgeVisible = await riskBadge.first().isVisible();
  console.log(`   ✅ Risk Badge 'Rizikový partner': ${isRiskBadgeVisible ? "VISIBLE (PASS)" : "MISSING (FAIL)"}`);
  if (!isRiskBadgeVisible) {
    throw new Error("P0 FAIL: Červený odznak Rizikový partner sa nezobrazil pri rizikovom subjekte!");
  }

  // 11. P0 TEST: Neoverené IČO nesmie mať zelený checkmark!
  console.log("11. P0 TEST: Unverified IČO must NOT have green checkmark...");
  await page.selectOption("select:has(option:has-text('Nová protistrana'))", "");
  await page.waitForTimeout(300);

  // Vyplníme ručne neoverené IČO a partnera (bez kliknutia na overenie v registri)
  await icoInput.fill("99887766");
  // Nevoláme overenie, zadáme rovno názov a IBAN
  await nameInput.fill("Neoverený Subjekt s.r.o.");
  await ibanInput.fill("SK71 1100 0000 0012 3456 7890");
  await refInput.fill("Neoverená transakcia");
  await amountInput.fill("50.00");
  await addMoveBtn.click();
  await page.waitForTimeout(800);

  // Overíme neutrálny odznak: "IČO 99887766" a NIE "✓ IČO 99887766"
  const unverifiedBadge = page.locator("span:has-text('IČO 99887766')");
  const unverifiedText = await unverifiedBadge.innerText();
  console.log(`   ✅ Unverified badge text: "${unverifiedText}" (contains checkmark: ${unverifiedText.includes("✓")})`);
  if (unverifiedText.includes("✓")) {
    throw new Error("P0 FAIL: Neoverené IČO nesmie obsahovať zelený checkmark ✓!");
  }

  // Screenshot editora so všetkými 3 typmi odznakov
  const formScreenshot = path.join(SCREENSHOT_DIR, "ico-atlas-risk-radar-form.png");
  await page.screenshot({ path: formScreenshot });
  console.log(`   📸 All badges screenshot saved to: ${formScreenshot}`);

  await browser.close();

  console.log("\n==================================================================");
  console.log("  🎉 VŠETKY P0 TESTY ICO-ATLAS, SMART ONBOARDING & RADAR PREŠLI!");
  console.log("==================================================================\n");
}

run().catch((err) => {
  console.error("Test failed:", err);
  process.exit(1);
});
