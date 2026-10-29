import assert from "node:assert/strict";
import { test } from "node:test";
import {
  SEED,
  isSkIban,
  isRealDate,
  parseAmountInput,
  readStoredBooks,
  statementFileStem,
} from "./books.ts";

test("invalid storage payload falls back to the seed and a notice", () => {
  const broken = readStoredBooks('{"clients":[{"id":"c"}],"statements":[{"id":"s"}]}');
  assert.equal(broken.notice?.includes("poškodené"), true);
  assert.equal(broken.books.statements[0]?.id, SEED.statements[0]?.id);
  assert.equal(broken.books.statements[0]?.opening, 21181525);
  const empty = readStoredBooks(null);
  assert.equal(empty.notice, null);
  assert.equal(empty.books.statements[0]?.opening, 21181525);
});

test("amount parsing keeps cents and rejects unfinished or negative input", () => {
  const ok = parseAmountInput("10.5", false);
  assert.equal(ok.ok && ok.cents, 1050);
  const comma = parseAmountInput("10,50", false);
  assert.equal(comma.ok && comma.cents, 1050);
  assert.equal(parseAmountInput("10.", false).ok, false);
  assert.equal(parseAmountInput("-1", false).ok, false);
  assert.equal(parseAmountInput("", false).ok, false);
});

test("dates accept only a real calendar day", () => {
  assert.equal(isRealDate("31.07.2026"), true);
  assert.equal(isRealDate("29.02.2024"), true);
  assert.equal(isRealDate("32.13.2026"), false);
  assert.equal(isRealDate("29.02.2027"), false);
  assert.equal(isRealDate("31.06.2026"), false);
});

test("slovak iban must be 24 characters and pass mod-97", () => {
  assert.equal(isSkIban("SK6611000000002942170336"), true);
  assert.equal(isSkIban("SK66 1100 0000 0029 4217 0336"), true);
  assert.equal(isSkIban("SK0011000000002942170336"), false);
  assert.equal(isSkIban("DE89370400440532013000"), false);
  assert.equal(isSkIban(""), false);
});

test("filename sanitizing turns slashes into dashes", () => {
  assert.equal(statementFileStem("a/b"), "vypis-a-b");
  assert.equal(statementFileStem("a\\b"), "vypis-a-b");
  assert.equal(statementFileStem("7"), "vypis-7");
});
