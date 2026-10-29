import assert from "node:assert/strict";
import { test } from "node:test";
import { cents, formatMoney, parseAmount } from "./books.ts";

test("formatMoney groups thousands and keeps two decimals", () => {
  assert.equal(formatMoney(21181525), "211,815.25");
  assert.equal(formatMoney(0), "0.00");
  assert.equal(formatMoney(1168560), "11,685.60");
});

test("parseAmount returns integer cents without a float step", () => {
  assert.equal(parseAmount("211,815.25"), 21_181_525);
  assert.equal(parseAmount("211815.25"), 21_181_525);
  assert.equal(parseAmount("11,685.60"), 1_168_560);
  assert.equal(parseAmount("0.08"), 8);
});

test("cents adds ten euros twice as two thousand cents", () => {
  assert.equal(cents(10) + cents(10), 2000);
});
