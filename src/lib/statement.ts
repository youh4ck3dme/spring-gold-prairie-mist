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
const FOOTER_AT = 78;

type Span = [number, string];

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

function footer(number: string, page: "1" | "2", currency: string): string {
  return row([
    [0, "Mena"],
    [10, currency],
    [49, "Výpis číslo:"],
    [65, number],
    [72, "Strana:"],
    [86, page],
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

export type Rendered = {
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
  const empty = sheet();
  const fail = (error: string): Rendered => ({
    pageOne: empty,
    pageTwo: empty,
    error,
    dbLabel: "0.00",
    crLabel: "0.00",
    dbN: 0,
    crN: 0,
    closingLabel: "0.00",
  });
  if (!statement || !client || !bank) return fail("Chýba účet alebo banka.");

  const sums = ledger(statement.opening, statement.movements);
  if (!sums.ok) return fail("Suma pohybu nie je číslo.");

  const lines = sheet();
  const currency = client.currency || "EUR";
  const issued = statement.issued_on;
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
  const subheader = row([
    [0, `${client.type} ${client.number}`],
    [33, `Majiteľ ${client.name}`],
    [77, "Dátum"],
    [83, issued],
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

  let cursor = 35;
  let overflow = false;
  statement.movements.forEach((movement, index) => {
    if (index > 0) {
      if (cursor < FOOTER_AT) lines[cursor] = rule();
      else overflow = true;
      cursor += 1;
    }
    for (const spans of blocks(movement, partyOf(books, movement))) {
      if (cursor < FOOTER_AT) lines[cursor] = row(spans);
      else overflow = true;
      cursor += 1;
    }
  });

  lines[78] = row([
    [13, "SPOLU"],
    [45, "DB"],
    [59, String(sums.dbN)],
    money(moneyFromCents(sums.db), "total"),
  ]);
  lines[79] = row([
    [45, "CR"],
    [59, String(sums.crN)],
    money(moneyFromCents(sums.cr), "total"),
  ]);
  lines[81] = row([
    [13, "Zostatok na účte ku dňu vystavenia výpisu:"],
    money(moneyFromCents(sums.closing), "total"),
  ]);
  lines[82] = rule();
  lines[83] = footer(statement.number, "1", currency);

  const pageTwo = sheet();
  pageTwo[0] = subheader;
  pageTwo[1] = rule();
  pageTwo[3] = row([
    [6, "Týmto potvrdzujeme, že"],
    [30, "Váš"],
    [35, "vklad je chránený v zmysle Zákona o ochrane"],
  ]);
  pageTwo[4] = row([
    [6, "vkladov. Bližšie informácie sú uvedené v Informačnom formulári pre vklada-"],
  ]);
  pageTwo[5] = row([
    [6, "teľa, ktorý od nás dostanete raz ročne, na našej internetovej stránke"],
  ]);
  pageTwo[6] = row([
    [6, "www.tatrabanka.sk, v našich obchodných priestoroch a tiež na internetovej"],
  ]);
  pageTwo[7] = row([[6, "stránke Fondu ochrany vkladov www.fovsr.sk."]]);
  pageTwo[82] = rule();
  pageTwo[83] = footer(statement.number, "2", currency);

  let error: string | null = null;
  if (overflow || cursor > FOOTER_AT) error = "Pohyby sa nezmestia na jednu stranu. Tlač je zastavená.";
  if (statement.movements.some((movement) => (movement.kind === "in" || movement.kind === "out") && !partyOf(books, movement))) {
    error = "Pohyb nemá protistranu z customers alebo contractors.";
  }

  return {
    pageOne: lines,
    pageTwo,
    error,
    dbLabel: moneyFromCents(sums.db),
    crLabel: moneyFromCents(sums.cr),
    dbN: sums.dbN,
    crN: sums.crN,
    closingLabel: moneyFromCents(sums.closing),
  };
}
