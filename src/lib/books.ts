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
      previous_on: "31.06.2026",
      opening: 211815.25,
      movements: [
        {
          id: "m1",
          kind: "tax",
          date: "01.07.2026",
          text: "Transakčná daň 30.06.2026",
          symbol: "",
          amount: 0.08,
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
          amount: 160,
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
          amount: 11070,
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
          amount: 40,
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
          amount: 13530,
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
          amount: 40,
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
          amount: 11685.6,
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
          amount: 40,
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
          amount: 19,
          valuta: "",
          rate: "",
          party_id: "",
          fee_note: "Tatra Business Premium",
        },
      ],
    },
  ],
};

export function cents(value: number): number {
  return Math.round(value * 100);
}

export function formatMoney(value: number): string {
  const sign = value < 0 ? "-" : "";
  const abs = Math.abs(cents(value));
  const whole = Math.floor(abs / 100);
  const frac = String(abs % 100).padStart(2, "0");
  const grouped = String(whole).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  return `${sign}${grouped}.${frac}`;
}

export function parseAmount(raw: string): number {
  const text = raw.trim().replace(/\s/g, "");
  if (!text) return Number.NaN;
  if (text.includes(",") && text.includes(".")) return Number(text.replace(/,/g, ""));
  if (text.includes(",")) return Number(text.replace(",", "."));
  return Number(text);
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
  let db = 0;
  let cr = 0;
  let dbN = 0;
  let crN = 0;
  for (const movement of movements) {
    const amount = cents(movement.amount);
    if (!Number.isFinite(amount)) return { ok: false, db: 0, cr: 0, dbN: 0, crN: 0, closing: 0 };
    if (movement.kind === "in") {
      cr += amount;
      crN += 1;
    } else {
      db += amount;
      dbN += 1;
    }
  }
  const open = cents(opening);
  if (!Number.isFinite(open)) return { ok: false, db: 0, cr: 0, dbN: 0, crN: 0, closing: 0 };
  return { ok: true, db, cr, dbN, crN, closing: open - db + cr };
}

export function moneyFromCents(value: number): string {
  return formatMoney(value / 100);
}
