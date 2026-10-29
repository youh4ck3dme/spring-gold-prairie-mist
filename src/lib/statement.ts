import {
  type Books,
  type Movement,
  type Party,
  formatIban,
  formatMoney,
  ledger,
  moneyFromCents,
} from "@/lib/books";

export const COLS = 93;
const LINES = 84;

type Span = [number, string];

export function placedExcess(column: number, text: string): number {
  return Math.max(0, column + Array.from(text).length - COLS);
}

export function draftExcess(kind: Movement["kind"], text: string, feeNote: string, amountCents: number): number {
  const line = kind === "in" || kind === "out" ? `Platba ${text}` : text;
  let excess = placedExcess(13, line);
  if (kind === "fee") {
    const note = feeNote || "Tatra Business Premium";
    const second = `${note}  ${".".repeat(42)}  ${formatMoney(amountCents)}EUR`;
    excess = Math.max(excess, placedExcess(13, second));
  }
  return excess;
}

function row(spans: Span[]): string {
  const chars = Array.from({ length: COLS }, () => " ");
  for (const [col, text] of spans) {
    const glyphs = Array.from(text);
    for (let i = 0; i < glyphs.length; i++) {
      const at = col + i;
      if (at >= 0 && at < COLS) chars[at] = glyphs[i];
    }
  }
  return chars.join("");
}

function rule(): string {
  return "-".repeat(COLS);
}

function money(text: string, kind: "debit" | "credit" | "total"): Span {
  if (kind === "debit") {
    const value = text.endsWith("-") ? text : `${text}-`;
    return [COLS - value.length, value];
  }
  if (kind === "credit") return [COLS - 1 - text.length, text];
  return [COLS - text.length, text];
}

function sheet(): string[] {
  return Array.from({ length: LINES }, () => row([]));
}

function footer(number: string, page: string, currency: string): string {
  return row([
    [0, "Mena"],
    [10, currency],
    [49, "Výpis číslo:"],
    [65, number],
    [72, "Strana:"],
    [80, page],
  ]);
}

function partyOf(books: Books, movement: Movement): Party | undefined {
  const list = movement.kind === "in" ? books.customers : books.contractors;
  return list.find((party) => party.id === movement.party_id);
}

function blocks(movement: Movement, party: Party | undefined): Span[][] {
  const amount = formatMoney(movement.amount);
  const date = movement.date;
  if (movement.kind === "tax") {
    return [[[0, date], [13, movement.text], money(amount, "debit")]];
  }
  if (movement.kind === "fee") {
    const note = movement.fee_note || "Tatra Business Premium";
    const second = `${note}  ${".".repeat(42)}  ${amount}EUR`;
    return [
      [[0, date], [13, movement.text || "Poplatok za balík"], money(amount, "debit")],
      [[13, second]],
    ];
  }
  const rate = movement.rate || "1.00000000";
  if (movement.kind === "in") {
    return [
      [[0, date], [13, `Platba ${movement.text}`], money(amount, "credit")],
      [[13, "Prijatá  platba:"], [45, movement.symbol]],
      [[13, `Suma:${amount}EUR`], [45, `Kurz:${rate}`], [71, `Valuta:${movement.valuta}`]],
      [[13, `Banka platiteľa:${party?.swift ?? ""}`]],
      [[13, "Platiteľ:"], [29, party?.iban ?? ""]],
      [[29, party?.name ?? ""]],
    ];
  }
  return [
    [[0, date], [13, `Platba ${movement.text}`], money(amount, "debit")],
    [[13, "Odoslaná platba:"], [45, movement.symbol]],
    [[13, `Suma:${amount}EUR`], [45, `Kurz:${rate}`], [71, `Valuta:${movement.valuta}`]],
    [[13, `Banka príjemcu: ${party?.swift ?? ""}`]],
    [[13, "Príjemca:"], [29, party?.iban ?? ""]],
    [[29, party?.name ?? ""]],
  ];
}

function addFondOchranyVkladov(page: string[], startLine = 3) {
  page[startLine] = row([
    [6, "Týmto potvrdzujeme, že"],
    [30, "Váš"],
    [35, "vklad je chránený v zmysle Zákona o ochrane"],
  ]);
  page[startLine + 1] = row([
    [6, "vkladov. Bližšie informácie sú uvedené v Informačnom formulári pre vklada-"],
  ]);
  page[startLine + 2] = row([
    [6, "teľa, ktorý od nás dostanete raz ročne, na našej internetovej stránke"],
  ]);
  page[startLine + 3] = row([
    [6, "www.tatrabanka.sk, v našich obchodných priestoroch a tiež na internetovej"],
  ]);
  page[startLine + 4] = row([[6, "stránke Fondu ochrany vkladov www.fovsr.sk."]]);
}

function addTotals(
  page: string[],
  sums: { db: number; cr: number; dbN: number; crN: number; closing: number },
) {
  page[78] = row([
    [13, "SPOLU"],
    [45, "DB"],
    [59, String(sums.dbN)],
    money(moneyFromCents(sums.db), "total"),
  ]);
  page[79] = row([
    [45, "CR"],
    [59, String(sums.crN)],
    money(moneyFromCents(sums.cr), "total"),
  ]);
  page[81] = row([
    [13, "Zostatok na účte ku dňu vystavenia výpisu:"],
    money(moneyFromCents(sums.closing), "total"),
  ]);
}

export type Rendered = {
  pages: string[][];
  pageOne: string[];
  pageTwo: string[];
  error: string | null;
  dbLabel: string;
  crLabel: string;
  dbN: number;
  crN: number;
  closingLabel: string;
};

export function renderStatement(books: Books, statementId: string): Rendered {
  const statement = books.statements.find((item) => item.id === statementId);
  const client = books.clients.find((item) => item.id === statement?.client_id);
  const bank = books.banks.find((item) => item.id === client?.bank_id);
  const fail = (error: string): Rendered => ({
    pages: [],
    pageOne: [],
    pageTwo: [],
    error,
    dbLabel: "0.00",
    crLabel: "0.00",
    dbN: 0,
    crN: 0,
    closingLabel: "0.00",
  });
  if (!statement || !client || !bank) return fail("Chýba účet alebo banka.");
  const movements = Array.isArray(statement.movements) ? statement.movements : [];

  const sums = ledger(statement.opening, movements);
  if (!sums.ok) return fail("Suma pohybu nie je číslo.");

  const currency = client.currency || "EUR";
  const issued = statement.issued_on;

  let textOver = 0;
  for (const movement of movements) {
    for (const spans of blocks(movement, partyOf(books, movement))) {
      for (const [col, text] of spans) textOver = Math.max(textOver, placedExcess(col, text));
    }
  }
  if (textOver > 0) return fail(`Text je o ${textOver} znakov dlhší než je povolené.`);

  const missingParty = movements.some(
    (movement) => (movement.kind === "in" || movement.kind === "out") && !partyOf(books, movement),
  );
  if (missingParty) return fail("Pohyb nemá protistranu z customers alebo contractors.");

  const subheader = row([
    [0, `${client.type} ${client.number}`],
    [33, `Majiteľ ${client.name}`],
    [77, "Dátum"],
    [83, issued],
  ]);

  // Pre-render movement line blocks
  const movementBlocks: string[][] = movements.map((m) =>
    blocks(m, partyOf(books, m)).map((spans) => row(spans)),
  );

  // Check if all movements fit on Page 1
  let totalLinesP1 = 0;
  for (let i = 0; i < movementBlocks.length; i++) {
    totalLinesP1 += (i === 0 ? 0 : 1) + movementBlocks[i].length;
  }

  const pages: string[][] = [];

  const createPageOneHeader = (): string[] => {
    const lines = sheet();
    lines[0] = row([
      [0, `${client.type} ${client.number}`],
      [44, "Mena"],
      [50, currency],
      [77, "Dátum"],
      [83, issued],
    ]);
    lines[1] = row([
      [0, `IBAN ${formatIban(client.iban)}`],
      [71, "BIC (SWIFT)"],
      [85, client.switft],
    ]);
    lines[2] = rule();
    lines[4] = row([[0, `Číslo klienta: ${client.client}`]]);
    lines[5] = row([
      [0, "Majiteľ účtu:"],
      [15, client.name],
    ]);
    const address = client.address.split(/\r?\n/);
    lines[6] = row([[15, address[0] ?? ""]]);
    lines[7] = row([[15, address[1] ?? ""]]);
    lines[8] = row([[15, address[2] ?? ""]]);
    lines[9] = row([
      [0, "Názov účtu:"],
      [15, client.name],
    ]);
    lines[10] = row([
      [0, "E-mail:"],
      [15, client.email],
    ]);
    lines[12] = row([[82, "001"]]);
    lines[15] = row([[54, client.name]]);
    lines[16] = row([[54, address[0] ?? ""]]);
    lines[17] = row([[54, address[1] ?? ""]]);
    lines[18] = row([[54, address[2] ?? ""]]);
    const bankLines = bank.addess.split(/\r?\n/);
    lines[20] = row([[0, `${bank.name}, ${bankLines[0] ?? ""}`.replace(/, $/, "")]]);
    for (let i = 1; i < 6; i++) lines[20 + i] = row([[0, bankLines[i] ?? ""]]);
    lines[28] = row([
      [0, "CENTRÁLA/HEAD OFFICE"],
      [41, "ID:"],
      [48, "00"],
      [71, "Výpis číslo:"],
      [91, statement.number],
    ]);
    lines[30] = subheader;
    lines[31] = rule();
    lines[32] = row([
      [0, "Dátum sprac. Popis"],
      [48, "Dátum zúčt."],
      [87, "Suma"],
    ]);
    lines[33] = rule();
    lines[34] = row([
      [13, "Posledný výpis"],
      [29, statement.previous_on],
      money(formatMoney(statement.opening), "debit"),
    ]);
    return lines;
  };

  const createContinuationPageHeader = (): string[] => {
    const lines = sheet();
    lines[0] = subheader;
    lines[1] = rule();
    lines[2] = row([
      [0, "Dátum sprac. Popis"],
      [48, "Dátum zúčt."],
      [87, "Suma"],
    ]);
    lines[3] = rule();
    return lines;
  };

  if (35 + totalLinesP1 <= 78) {
    // Single movement page: all movements fit on Page 1
    const p1 = createPageOneHeader();
    let cursor = 35;
    movementBlocks.forEach((block, index) => {
      if (index > 0) {
        p1[cursor++] = rule();
      }
      for (const line of block) {
        p1[cursor++] = line;
      }
    });
    addTotals(p1, sums);
    pages.push(p1);

    // Page 2: legal notice
    const p2 = sheet();
    p2[0] = subheader;
    p2[1] = rule();
    addFondOchranyVkladov(p2, 3);
    pages.push(p2);
  } else {
    // Multi-page statement: paginate movements across multiple sheets
    const p1 = createPageOneHeader();
    let cursor = 35;
    let mIdx = 0;

    while (mIdx < movementBlocks.length) {
      const block = movementBlocks[mIdx];
      const needed = (cursor === 35 ? 0 : 1) + block.length;
      if (cursor + needed <= 78) {
        if (cursor > 35) p1[cursor++] = rule();
        for (const line of block) p1[cursor++] = line;
        mIdx++;
      } else {
        break;
      }
    }
    pages.push(p1);

    // Continuation pages
    while (mIdx < movementBlocks.length) {
      const pn = createContinuationPageHeader();
      cursor = 4;

      // Calculate how many lines remaining movements would take
      let remLines = 0;
      for (let i = mIdx; i < movementBlocks.length; i++) {
        remLines += (i === mIdx ? 0 : 1) + movementBlocks[i].length;
      }

      if (4 + remLines <= 71) {
        // All remaining movements fit on this page alongside totals & legal notice!
        while (mIdx < movementBlocks.length) {
          const block = movementBlocks[mIdx];
          if (cursor > 4) pn[cursor++] = rule();
          for (const line of block) pn[cursor++] = line;
          mIdx++;
        }
        addFondOchranyVkladov(pn, 72);
        addTotals(pn, sums);
        pages.push(pn);
        break;
      } else {
        // Fill up to line 78
        while (mIdx < movementBlocks.length) {
          const block = movementBlocks[mIdx];
          const needed = (cursor === 4 ? 0 : 1) + block.length;
          if (cursor + needed <= 78) {
            if (cursor > 4) pn[cursor++] = rule();
            for (const line of block) pn[cursor++] = line;
            mIdx++;
          } else {
            break;
          }
        }
        pages.push(pn);
      }
    }

    // If movements finished on a full continuation page without space for totals & notice:
    const lastPage = pages[pages.length - 1];
    if (!lastPage[78].trim()) {
      const finalPage = sheet();
      finalPage[0] = subheader;
      finalPage[1] = rule();
      addFondOchranyVkladov(finalPage, 3);
      addTotals(finalPage, sums);
      pages.push(finalPage);
    }
  }

  // Stamp footer on all pages
  const totalPages = pages.length;
  for (let p = 0; p < totalPages; p++) {
    pages[p][82] = rule();
    pages[p][83] = footer(statement.number, `${p + 1} / ${totalPages}`, currency);
  }

  return {
    pages,
    pageOne: pages[0] ?? [],
    pageTwo: pages[1] ?? [],
    error: null,
    dbLabel: moneyFromCents(sums.db),
    crLabel: moneyFromCents(sums.cr),
    dbN: sums.dbN,
    crN: sums.crN,
    closingLabel: moneyFromCents(sums.closing),
  };
}
