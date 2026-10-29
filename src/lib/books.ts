/** Polia 1:1 s JetEngine CCT. `addess` a `switft` sú preklepy zo schémy, nemeníme ich. */

export type Bank = {
  id: string;
  name: string;
  addess: string;
  logo: string | null;
};

export type Client = {
  id: string;
  bank_id: string;
  client: string;
  name: string;
  address: string;
  email: string;
  type: string;
  number: string;
  iban: string;
  switft: string;
  currency: string;
};

export type Party = {
  id: string;
  client_id: string;
  name: string;
  iban: string;
  swift: string;
};

export type MovementKind = "in" | "out" | "tax" | "fee";

export type Movement = {
  id: string;
  kind: MovementKind;
  date: string;
  text: string;
  symbol: string;
  amount: number;
  valuta: string;
  rate: string;
  party_id: string;
  fee_note: string;
};

export type Statement = {
  id: string;
  client_id: string;
  number: string;
  issued_on: string;
  previous_on: string;
  opening: number;
  movements: Movement[];
};

export type Books = {
  banks: Bank[];
  clients: Client[];
  customers: Party[];
  contractors: Party[];
  statements: Statement[];
};

export const SEED: Books = {
  banks: [
    {
      id: "b1",
      name: "Tatra banka, a.s.",
      addess: [
        "Hodžovo nám. 3",
        "811 06 Bratislava",
        "IČO: 00686930",
        "DIČ: 2020408522",
        "Obchodný reg. Mestského súdu Bratislava III,",
        "Oddiel Sa, vložka č. 71/B",
      ].join("\n"),
      logo: null,
    },
  ],
  clients: [
    {
      id: "c1",
      bank_id: "b1",
      client: "G2GT08",
      name: "MCL Group s.r.o.",
      address: "Košická 52/a\n821 08 Bratislava\nSlovensko",
      email: "pkrucovcin@gmail.com",
      type: "Podnikateľský účet",
      number: "2942170336",
      iban: "SK6611000000002942170336",
      switft: "TATRSKBX",
      currency: "EUR",
    },
  ],
  customers: [
    {
      id: "cu1",
      client_id: "c1",
      name: "KRUPPA s.r.o.",
      iban: "SK6611000000002941126907",
      swift: "TATRSKBXXXX",
    },
  ],
  contractors: [
    {
      id: "co1",
      client_id: "c1",
      name: "Flotilových partner s.r.o.",
      iban: "SK911100000000294190387",
      swift: "TATRSKBXXXX",
    },
    {
      id: "co2",
      client_id: "c1",
      name: "Revino s.r.o.",
      iban: "LT703250097758116083",
      swift: "REVOLT21XXX",
    },
  ],
  statements: [
    {
      id: "s7",
      client_id: "c1",
      number: "7",
      issued_on: "31.07.2026",
      previous_on: "30.06.2026",
      opening: 21181525,
      movements: [
        {
          id: "m1",
          kind: "tax",
          date: "01.07.2026",
          text: "Transakčná daň 30.06.2026",
          symbol: "",
          amount: 8,
          valuta: "",
          rate: "",
          party_id: "",
          fee_note: "",
        },
        {
          id: "m2",
          kind: "in",
          date: "13.07.2026",
          text: "1100/000000-2941126907",
          symbol: "VP26061542533722",
          amount: 16000,
          valuta: "09.07.2026",
          rate: "1.00000000",
          party_id: "cu1",
          fee_note: "",
        },
        {
          id: "m3",
          kind: "out",
          date: "13.07.2026",
          text: "VS26085171223",
          symbol: "VS26085171223",
          amount: 1107000,
          valuta: "13.07.2026",
          rate: "1.00000000",
          party_id: "co1",
          fee_note: "",
        },
        {
          id: "m4",
          kind: "tax",
          date: "14.07.2026",
          text: "Transakčná daň 14.07.2026",
          symbol: "",
          amount: 4000,
          valuta: "",
          rate: "",
          party_id: "",
          fee_note: "",
        },
        {
          id: "m5",
          kind: "out",
          date: "16.07.2026",
          text: "1100/000000-02941087370",
          symbol: "VS26085171223",
          amount: 1353000,
          valuta: "16.07.2026",
          rate: "1.00000000",
          party_id: "co1",
          fee_note: "",
        },
        {
          id: "m6",
          kind: "tax",
          date: "17.07.2026",
          text: "Transakčná daň 17.07.2026",
          symbol: "",
          amount: 4000,
          valuta: "",
          rate: "",
          party_id: "",
          fee_note: "",
        },
        {
          id: "m7",
          kind: "out",
          date: "21.07.2026",
          text: "P026072012626832",
          symbol: "P026072012626832",
          amount: 1168560,
          valuta: "21.07.2026",
          rate: "1.00000000",
          party_id: "co2",
          fee_note: "",
        },
        {
          id: "m8",
          kind: "tax",
          date: "22.07.2026",
          text: "Transakčná daň 22.07.2026",
          symbol: "",
          amount: 4000,
          valuta: "",
          rate: "",
          party_id: "",
          fee_note: "",
        },
        {
          id: "m9",
          kind: "fee",
          date: "31.07.2026",
          text: "Poplatok za balík",
          symbol: "",
          amount: 1900,
          valuta: "",
          rate: "",
          party_id: "",
          fee_note: "Tatra Business Premium",
        },
      ],
    },
  ],
};

export const DAMAGE_NOTICE =
  "Uložené dáta boli poškodené, obnovil som predvolené dáta. Pôvodné sú v zálohe.";

export function cents(value: number): number {
  return Math.round(value * 100);
}

export function formatMoney(centsValue: number): string {
  const sign = centsValue < 0 ? "-" : "";
  const abs = Math.abs(Math.trunc(centsValue));
  const whole = Math.floor(abs / 100);
  const frac = String(abs % 100).padStart(2, "0");
  const grouped = String(whole).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  return `${sign}${grouped}.${frac}`;
}

export function plainCents(centsValue: number): string {
  const sign = centsValue < 0 ? "-" : "";
  const abs = Math.abs(Math.trunc(centsValue));
  return `${sign}${Math.floor(abs / 100)}.${String(abs % 100).padStart(2, "0")}`;
}

export type AmountParse = { ok: true; cents: number } | { ok: false; error: string };

export function parseAmountInput(raw: string, allowNegative: boolean): AmountParse {
  const text = raw.trim().replace(/\s/g, "");
  if (!text) return { ok: false, error: "Suma je prázdna." };
  let normalized = text;
  if (text.includes(",") && text.includes(".")) normalized = text.replace(/,/g, "");
  else if (text.includes(",")) normalized = text.replace(",", ".");
  if (normalized.endsWith(".")) return { ok: false, error: "Suma nie je dokončená." };
  const negative = normalized.startsWith("-");
  if (negative && !allowNegative) return { ok: false, error: "Záporná suma nie je povolená." };
  const body = negative ? normalized.slice(1) : normalized;
  if (!/^\d+(\.\d{1,2})?$/.test(body)) return { ok: false, error: "Suma má mať najviac dve desatinné miesta." };
  const [whole, frac = ""] = body.split(".");
  const value = Number(whole) * 100 + Number((frac + "00").slice(0, 2));
  if (!Number.isSafeInteger(value)) return { ok: false, error: "Suma je príliš veľká." };
  return { ok: true, cents: negative ? -value : value };
}

export function parseAmount(raw: string): number {
  const parsed = parseAmountInput(raw, true);
  return parsed.ok ? parsed.cents : Number.NaN;
}

export function isRealDate(value: string): boolean {
  const match = /^(\d{2})\.(\d{2})\.(\d{4})$/.exec(value);
  if (!match) return false;
  const day = Number(match[1]);
  const month = Number(match[2]);
  const year = Number(match[3]);
  const date = new Date(Date.UTC(year, month - 1, day));
  return date.getUTCFullYear() === year && date.getUTCMonth() === month - 1 && date.getUTCDate() === day;
}

export function isSkIban(raw: string): boolean {
  const iban = raw.replace(/\s+/g, "").toUpperCase();
  if (!/^SK\d{22}$/.test(iban)) return false;
  const moved = iban.slice(4) + iban.slice(0, 4);
  let expanded = "";
  for (const char of moved) {
    const code = char.charCodeAt(0);
    expanded += code >= 65 && code <= 90 ? String(code - 55) : char;
  }
  let rest = 0;
  for (const digit of expanded) rest = (rest * 10 + Number(digit)) % 97;
  return rest === 1;
}

export function statementFileStem(number: string): string {
  const clean = number.replace(/[\\/]/g, "-").replace(/[<>:"|?*\u0000-\u001f]/g, "");
  return `vypis-${clean}`;
}

function isString(value: unknown): value is string {
  return typeof value === "string";
}

function isCents(value: unknown): value is number {
  return typeof value === "number" && Number.isSafeInteger(value);
}

function isBank(value: unknown): value is Bank {
  if (!value || typeof value !== "object") return false;
  const row = value as Bank;
  return isString(row.id) && isString(row.name) && isString(row.addess) && (row.logo === null || isString(row.logo));
}

function isClient(value: unknown): value is Client {
  if (!value || typeof value !== "object") return false;
  const row = value as Client;
  return (
    isString(row.id) &&
    isString(row.bank_id) &&
    isString(row.client) &&
    isString(row.name) &&
    isString(row.address) &&
    isString(row.email) &&
    isString(row.type) &&
    isString(row.number) &&
    isString(row.iban) &&
    isString(row.switft) &&
    isString(row.currency)
  );
}

function isParty(value: unknown): value is Party {
  if (!value || typeof value !== "object") return false;
  const row = value as Party;
  return isString(row.id) && isString(row.client_id) && isString(row.name) && isString(row.iban) && isString(row.swift);
}

function isMovement(value: unknown): value is Movement {
  if (!value || typeof value !== "object") return false;
  const row = value as Movement;
  return (
    isString(row.id) &&
    (row.kind === "in" || row.kind === "out" || row.kind === "tax" || row.kind === "fee") &&
    isString(row.date) &&
    isString(row.text) &&
    isString(row.symbol) &&
    isCents(row.amount) &&
    isString(row.valuta) &&
    isString(row.rate) &&
    isString(row.party_id) &&
    isString(row.fee_note)
  );
}

export function isBooks(value: unknown): value is Books {
  if (!value || typeof value !== "object") return false;
  const books = value as Books;
  if (!Array.isArray(books.banks) || books.banks.length === 0 || !books.banks.every(isBank)) return false;
  if (!Array.isArray(books.clients) || books.clients.length === 0 || !books.clients.every(isClient)) return false;
  if (!Array.isArray(books.customers) || !books.customers.every(isParty)) return false;
  if (!Array.isArray(books.contractors) || !books.contractors.every(isParty)) return false;
  if (!Array.isArray(books.statements)) return false;
  return books.statements.every(
    (item) =>
      !!item &&
      typeof item === "object" &&
      isString(item.id) &&
      isString(item.client_id) &&
      isString(item.number) &&
      isString(item.issued_on) &&
      isString(item.previous_on) &&
      isCents(item.opening) &&
      Array.isArray(item.movements) &&
      item.movements.every(isMovement),
  );
}

export function readStoredBooks(raw: string | null): { books: Books; notice: string | null } {
  if (raw === null) return { books: SEED, notice: null };
  try {
    const parsed: unknown = JSON.parse(raw);
    if (isBooks(parsed)) return { books: parsed, notice: null };
  } catch {
    /* záloha a seed */
  }
  return { books: structuredClone(SEED), notice: DAMAGE_NOTICE };
}

export function formatIban(raw: string): string {
  return raw.replace(/\s+/g, "").replace(/(.{4})/g, "$1 ").trim();
}

export type Ledger = {
  ok: boolean;
  db: number;
  cr: number;
  dbN: number;
  crN: number;
  closing: number;
};

export function ledger(opening: number, movements: Movement[]): Ledger {
  const rows = Array.isArray(movements) ? movements : [];
  let db = 0;
  let cr = 0;
  let dbN = 0;
  let crN = 0;
  for (const movement of rows) {
    if (!isCents(movement?.amount)) return { ok: false, db: 0, cr: 0, dbN: 0, crN: 0, closing: 0 };
    if (movement.kind === "in") {
      cr += movement.amount;
      crN += 1;
    } else {
      db += movement.amount;
      dbN += 1;
    }
  }
  if (!isCents(opening)) return { ok: false, db: 0, cr: 0, dbN: 0, crN: 0, closing: 0 };
  return { ok: true, db, cr, dbN, crN, closing: opening - db + cr };
}

export function moneyFromCents(value: number): string {
  return formatMoney(value);
}
