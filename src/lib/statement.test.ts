import assert from "node:assert/strict";
import { test } from "node:test";
import { SEED, type Books, type Movement } from "./books.ts";
import { renderStatement } from "./statement.ts";

const OPENING = 21_181_525;
const DB = 3_642_468;
const CR = 16_000;
const CLOSING = 17_555_057;

function movement(partial: Partial<Movement> & Pick<Movement, "id" | "kind">): Movement {
  return {
    date: "01.07.2026",
    text: "Platba",
    symbol: "VS1",
    amount: 10,
    valuta: "01.07.2026",
    rate: "1.00000000",
    party_id: "co1",
    fee_note: "",
    ...partial,
  };
}

function booksWith(movements: Movement[]): Books {
  const books = structuredClone(SEED);
  const statement = books.statements.find((item) => item.id === "s7");
  if (!statement) throw new Error("chýba s7");
  statement.movements = movements;
  return books;
}

test("seed s7 keeps the 93 by 84 grid and the closing identity", () => {
  const rendered = renderStatement(SEED, "s7");
  assert.equal(rendered.error, null);
  assert.equal(rendered.pageOne.length, 84);
  assert.equal(rendered.pageTwo.length, 84);
  for (const line of [...rendered.pageOne, ...rendered.pageTwo]) {
    assert.equal(line.length, 93);
  }
  const page = rendered.pageOne.join("\n");
  assert.ok(page.includes("211,815.25"));
  assert.ok(page.includes("MCL Group"));
  assert.ok(page.includes("175,550.57"));
  assert.ok(page.includes("SPOLU"));
  assert.equal(OPENING - DB + CR, CLOSING);
  assert.equal(rendered.dbLabel, "36,424.68");
  assert.equal(rendered.crLabel, "160.00");
  assert.equal(rendered.closingLabel, "175,550.57");
});

test("a movement that passes the totals returns an error and empty pages", () => {
  const movements = Array.from({ length: 8 }, (_, index) =>
    movement({ id: `x${index}`, kind: "out", amount: 1 }),
  );
  const rendered = renderStatement(booksWith(movements), "s7");
  assert.equal(rendered.error, "Pohyby sa nezmestia na jednu stranu. Tlač je zastavená.");
  assert.deepEqual(rendered.pageOne, []);
  assert.deepEqual(rendered.pageTwo, []);
});

test("a payment without a counterparty returns an error and empty pages", () => {
  const rendered = renderStatement(booksWith([movement({ id: "x", kind: "out", party_id: "" })]), "s7");
  assert.equal(rendered.error, "Pohyb nemá protistranu z customers alebo contractors.");
  assert.deepEqual(rendered.pageOne, []);
  assert.deepEqual(rendered.pageTwo, []);
});

test("tax and fee do not need a counterparty", () => {
  const rendered = renderStatement(
    booksWith([
      movement({ id: "t", kind: "tax", party_id: "", amount: 8, text: "Transakčná daň" }),
      movement({ id: "f", kind: "fee", party_id: "", amount: 1900, text: "Poplatok za balík" }),
    ]),
    "s7",
  );
  assert.equal(rendered.error, null);
  assert.equal(rendered.pageOne.length, 84);
});
