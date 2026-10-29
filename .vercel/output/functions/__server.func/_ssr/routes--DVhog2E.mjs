import { o as __toESM } from "../_runtime.mjs";
import { d as require_jsx_runtime, f as require_react } from "../_libs/@tanstack/react-router+[...].mjs";
import { D as CircleHelp, E as CloudDownload, M as Check, T as CloudUpload, _ as FileText, b as Download, c as Rows3, d as Printer, h as Landmark, k as CircleAlert, l as RotateCcw, n as X, r as Upload, u as RefreshCw, v as Eye, w as Cloud } from "../_libs/lucide-react.mjs";
import { n as TSS_SERVER_FUNCTION, r as getServerFnById, t as createServerFn } from "./ssr.mjs";
import { r as init_html2canvas_esm, t as html2canvas } from "../_libs/html2canvas.mjs";
import { t as require_jspdf_node_min } from "../_libs/jspdf.mjs";
import { t as require_lib } from "../_libs/jszip+[...].mjs";
// node_modules/.nitro/vite/services/ssr/assets/routes--DVhog2E.js
var import_react = /* @__PURE__ */ __toESM(require_react());
var import_jsx_runtime = require_jsx_runtime();
init_html2canvas_esm();
var import_jspdf_node_min = require_jspdf_node_min();
var import_lib = /* @__PURE__ */ __toESM(require_lib());
var SEED = {
	banks: [{
		id: "b1",
		name: "Tatra banka, a.s.",
		addess: [
			"Hodžovo nám. 3",
			"811 06 Bratislava",
			"IČO: 00686930",
			"DIČ: 2020408522",
			"Obchodný reg. Mestského súdu Bratislava III,",
			"Oddiel Sa, vložka č. 71/B"
		].join("\n"),
		logo: null
	}],
	clients: [{
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
		currency: "EUR"
	}],
	customers: [{
		id: "cu1",
		client_id: "c1",
		name: "KRUPPA s.r.o.",
		iban: "SK6611000000002941126907",
		swift: "TATRSKBXXXX"
	}],
	contractors: [{
		id: "co1",
		client_id: "c1",
		name: "Flotilových partner s.r.o.",
		iban: "SK911100000000294190387",
		swift: "TATRSKBXXXX"
	}, {
		id: "co2",
		client_id: "c1",
		name: "Revino s.r.o.",
		iban: "LT703250097758116083",
		swift: "REVOLT21XXX"
	}],
	statements: [{
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
				fee_note: ""
			},
			{
				id: "m2",
				kind: "in",
				date: "13.07.2026",
				text: "1100/000000-2941126907",
				symbol: "VP26061542533722",
				amount: 16e3,
				valuta: "09.07.2026",
				rate: "1.00000000",
				party_id: "cu1",
				fee_note: ""
			},
			{
				id: "m3",
				kind: "out",
				date: "13.07.2026",
				text: "VS26085171223",
				symbol: "VS26085171223",
				amount: 1107e3,
				valuta: "13.07.2026",
				rate: "1.00000000",
				party_id: "co1",
				fee_note: ""
			},
			{
				id: "m4",
				kind: "tax",
				date: "14.07.2026",
				text: "Transakčná daň 14.07.2026",
				symbol: "",
				amount: 4e3,
				valuta: "",
				rate: "",
				party_id: "",
				fee_note: ""
			},
			{
				id: "m5",
				kind: "out",
				date: "16.07.2026",
				text: "1100/000000-02941087370",
				symbol: "VS26085171223",
				amount: 1353e3,
				valuta: "16.07.2026",
				rate: "1.00000000",
				party_id: "co1",
				fee_note: ""
			},
			{
				id: "m6",
				kind: "tax",
				date: "17.07.2026",
				text: "Transakčná daň 17.07.2026",
				symbol: "",
				amount: 4e3,
				valuta: "",
				rate: "",
				party_id: "",
				fee_note: ""
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
				fee_note: ""
			},
			{
				id: "m8",
				kind: "tax",
				date: "22.07.2026",
				text: "Transakčná daň 22.07.2026",
				symbol: "",
				amount: 4e3,
				valuta: "",
				rate: "",
				party_id: "",
				fee_note: ""
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
				fee_note: "Tatra Business Premium"
			}
		]
	}]
};
var DAMAGE_NOTICE = "Uložené dáta boli poškodené, obnovil som predvolené dáta. Pôvodné sú v zálohe.";
function formatMoney(centsValue) {
	const sign = centsValue < 0 ? "-" : "";
	const abs = Math.abs(Math.trunc(centsValue));
	const whole = Math.floor(abs / 100);
	const frac = String(abs % 100).padStart(2, "0");
	return `${sign}${String(whole).replace(/\B(?=(\d{3})+(?!\d))/g, ",")}.${frac}`;
}
function plainCents(centsValue) {
	const sign = centsValue < 0 ? "-" : "";
	const abs = Math.abs(Math.trunc(centsValue));
	return `${sign}${Math.floor(abs / 100)}.${String(abs % 100).padStart(2, "0")}`;
}
function parseAmountInput(raw, allowNegative) {
	const text = raw.trim().replace(/\s/g, "");
	if (!text) return {
		ok: false,
		error: "Suma je prázdna."
	};
	let normalized = text;
	if (text.includes(",") && text.includes(".")) normalized = text.replace(/,/g, "");
	else if (text.includes(",")) normalized = text.replace(",", ".");
	if (normalized.endsWith(".")) return {
		ok: false,
		error: "Suma nie je dokončená."
	};
	const negative = normalized.startsWith("-");
	if (negative && !allowNegative) return {
		ok: false,
		error: "Záporná suma nie je povolená."
	};
	const body = negative ? normalized.slice(1) : normalized;
	if (!/^\d+(\.\d{1,2})?$/.test(body)) return {
		ok: false,
		error: "Suma má mať najviac dve desatinné miesta."
	};
	const [whole, frac = ""] = body.split(".");
	const value = Number(whole) * 100 + Number((frac + "00").slice(0, 2));
	if (!Number.isSafeInteger(value)) return {
		ok: false,
		error: "Suma je príliš veľká."
	};
	return {
		ok: true,
		cents: negative ? -value : value
	};
}
function isRealDate(value) {
	const match = /^(\d{2})\.(\d{2})\.(\d{4})$/.exec(value);
	if (!match) return false;
	const day = Number(match[1]);
	const month = Number(match[2]);
	const year = Number(match[3]);
	const date = new Date(Date.UTC(year, month - 1, day));
	return date.getUTCFullYear() === year && date.getUTCMonth() === month - 1 && date.getUTCDate() === day;
}
function isSkIban(raw) {
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
function statementFileStem(number) {
	return `vypis-${number.replace(/[\\/]/g, "-").replace(/[<>:"|?*\u0000-\u001f]/g, "")}`;
}
function isString(value) {
	return typeof value === "string";
}
function isCents(value) {
	return typeof value === "number" && Number.isSafeInteger(value);
}
function isBank(value) {
	if (!value || typeof value !== "object") return false;
	const row = value;
	return isString(row.id) && isString(row.name) && isString(row.addess) && (row.logo === null || isString(row.logo));
}
function isClient(value) {
	if (!value || typeof value !== "object") return false;
	const row = value;
	return isString(row.id) && (!("bank_id" in row) || isString(row.bank_id)) && isString(row.client) && isString(row.name) && isString(row.address) && isString(row.email) && (!("type" in row) || isString(row.type)) && (isString(row.number) || typeof row.number === "number") && isString(row.iban) && isString(row.switft) && isString(row.currency);
}
function isParty(value) {
	if (!value || typeof value !== "object") return false;
	const row = value;
	return isString(row.id) && isString(row.client_id) && isString(row.name) && isString(row.iban) && isString(row.swift);
}
function isMovement(value) {
	if (!value || typeof value !== "object") return false;
	const row = value;
	return isString(row.id) && (row.kind === "in" || row.kind === "out" || row.kind === "tax" || row.kind === "fee") && isString(row.date) && isString(row.text) && isString(row.symbol) && isCents(row.amount) && isString(row.valuta) && isString(row.rate) && isString(row.party_id) && isString(row.fee_note);
}
function isBooks(value) {
	if (!value || typeof value !== "object") return false;
	const books = value;
	if (!Array.isArray(books.banks) || books.banks.length === 0 || !books.banks.every(isBank)) return false;
	if (!Array.isArray(books.clients) || books.clients.length === 0 || !books.clients.every(isClient)) return false;
	if (!Array.isArray(books.customers) || !books.customers.every(isParty)) return false;
	if (!Array.isArray(books.contractors) || !books.contractors.every(isParty)) return false;
	if (!Array.isArray(books.statements)) return false;
	return books.statements.every((item) => !!item && typeof item === "object" && isString(item.id) && isString(item.client_id) && isString(item.number) && isString(item.issued_on) && isString(item.previous_on) && isCents(item.opening) && Array.isArray(item.movements) && item.movements.every(isMovement));
}
function readStoredBooks(raw) {
	if (raw === null) return {
		books: SEED,
		notice: null
	};
	try {
		const parsed = JSON.parse(raw);
		if (isBooks(parsed)) return {
			books: parsed,
			notice: null
		};
	} catch {}
	return {
		books: structuredClone(SEED),
		notice: DAMAGE_NOTICE
	};
}
function formatIban(raw) {
	return raw.replace(/\s+/g, "").replace(/(.{4})/g, "$1 ").trim();
}
function ledger(opening, movements) {
	const rows = Array.isArray(movements) ? movements : [];
	let db = 0;
	let cr = 0;
	let dbN = 0;
	let crN = 0;
	for (const movement of rows) {
		if (!isCents(movement?.amount)) return {
			ok: false,
			db: 0,
			cr: 0,
			dbN: 0,
			crN: 0,
			closing: 0
		};
		if (movement.kind === "in") {
			cr += movement.amount;
			crN += 1;
		} else {
			db += movement.amount;
			dbN += 1;
		}
	}
	if (!isCents(opening)) return {
		ok: false,
		db: 0,
		cr: 0,
		dbN: 0,
		crN: 0,
		closing: 0
	};
	return {
		ok: true,
		db,
		cr,
		dbN,
		crN,
		closing: opening - db + cr
	};
}
function moneyFromCents(value) {
	return formatMoney(value);
}
var LINES = 84;
function placedExcess(column, text) {
	return Math.max(0, column + Array.from(text).length - 93);
}
function wrapPaymentText(text, maxLine1 = 52, maxLine2 = 78) {
	const trimmed = text.trim();
	if (Array.from(trimmed).length <= maxLine1) return [trimmed, null];
	const lastSpace = trimmed.slice(0, maxLine1 + 1).lastIndexOf(" ");
	if (lastSpace > 15) return [trimmed.slice(0, lastSpace).trim(), trimmed.slice(lastSpace + 1).trim().slice(0, maxLine2)];
	return [trimmed.slice(0, maxLine1), trimmed.slice(maxLine1).trim().slice(0, maxLine2)];
}
function draftExcess(kind, text, feeNote, amountCents) {
	if (kind === "in" || kind === "out") {
		const len = Array.from(text.trim()).length;
		return Math.max(0, len - 140);
	}
	let excess = placedExcess(13, text);
	if (kind === "fee") {
		const second = `${feeNote || "Tatra Business Premium"}  ${".".repeat(42)}  ${formatMoney(amountCents)}EUR`;
		excess = Math.max(excess, placedExcess(13, second));
	}
	return excess;
}
function row(spans) {
	const chars = Array.from({ length: 93 }, () => " ");
	for (const [col, text] of spans) {
		const glyphs = Array.from(text);
		for (let i = 0; i < glyphs.length; i++) {
			const at = col + i;
			if (at >= 0 && at < 93) chars[at] = glyphs[i];
		}
	}
	return chars.join("");
}
function rule() {
	return "-".repeat(93);
}
function money(text, kind) {
	if (kind === "debit") {
		const value = text.endsWith("-") ? text : `${text}-`;
		return [93 - value.length, value];
	}
	if (kind === "credit") return [92 - text.length, text];
	return [93 - text.length, text];
}
function sheet() {
	return Array.from({ length: LINES }, () => row([]));
}
function footer(number, page, currency) {
	return row([
		[0, "Mena"],
		[10, currency],
		[49, "Výpis číslo:"],
		[65, number],
		[72, "Strana:"],
		[80, page]
	]);
}
function partyOf(books, movement) {
	return (movement.kind === "in" ? books.customers : books.contractors).find((party) => party.id === movement.party_id);
}
function blocks(movement, party) {
	const amount = formatMoney(movement.amount);
	const date = movement.date;
	if (movement.kind === "tax") return [[
		[0, date],
		[13, movement.text],
		money(amount, "debit")
	]];
	if (movement.kind === "fee") {
		const second = `${movement.fee_note || "Tatra Business Premium"}  ${".".repeat(42)}  ${amount}EUR`;
		return [[
			[0, date],
			[13, movement.text || "Poplatok za balík"],
			money(amount, "debit")
		], [[13, second]]];
	}
	const rate = movement.rate || "1.00000000";
	if (movement.kind === "in") {
		const [t1, t2] = wrapPaymentText(movement.text);
		const rows = [[
			[0, date],
			[13, `Platba ${t1}`],
			money(amount, "credit")
		]];
		if (t2) rows.push([[13, t2]]);
		rows.push([[13, "Prijatá  platba:"], [45, movement.symbol]], [
			[13, `Suma:${amount}EUR`],
			[45, `Kurz:${rate}`],
			[71, `Valuta:${movement.valuta}`]
		], [[13, `Banka platiteľa:${party?.swift ?? ""}`]], [[13, "Platiteľ:"], [29, party?.iban ?? ""]], [[29, party?.name ?? ""]]);
		return rows;
	}
	const [t1, t2] = wrapPaymentText(movement.text);
	const rows = [[
		[0, date],
		[13, `Platba ${t1}`],
		money(amount, "debit")
	]];
	if (t2) rows.push([[13, t2]]);
	rows.push([[13, "Odoslaná platba:"], [45, movement.symbol]], [
		[13, `Suma:${amount}EUR`],
		[45, `Kurz:${rate}`],
		[71, `Valuta:${movement.valuta}`]
	], [[13, `Banka príjemcu: ${party?.swift ?? ""}`]], [[13, "Príjemca:"], [29, party?.iban ?? ""]], [[29, party?.name ?? ""]]);
	return rows;
}
function addFondOchranyVkladov(page, startLine = 3) {
	page[startLine] = row([
		[6, "Týmto potvrdzujeme, že"],
		[30, "Váš"],
		[35, "vklad je chránený v zmysle Zákona o ochrane"]
	]);
	page[startLine + 1] = row([[6, "vkladov. Bližšie informácie sú uvedené v Informačnom formulári pre vklada-"]]);
	page[startLine + 2] = row([[6, "teľa, ktorý od nás dostanete raz ročne, na našej internetovej stránke"]]);
	page[startLine + 3] = row([[6, "www.tatrabanka.sk, v našich obchodných priestoroch a tiež na internetovej"]]);
	page[startLine + 4] = row([[6, "stránke Fondu ochrany vkladov www.fovsr.sk."]]);
}
function addTotals(page, sums) {
	page[78] = row([
		[13, "SPOLU"],
		[45, "DB"],
		[59, String(sums.dbN)],
		money(moneyFromCents(sums.db), "total")
	]);
	page[79] = row([
		[45, "CR"],
		[59, String(sums.crN)],
		money(moneyFromCents(sums.cr), "total")
	]);
	page[81] = row([[13, "Zostatok na účte ku dňu vystavenia výpisu:"], money(moneyFromCents(sums.closing), "total")]);
}
function renderStatement(books, statementId) {
	const statement = books.statements.find((item) => item.id === statementId);
	const client = books.clients.find((item) => item.id === statement?.client_id);
	const bank = (client?.bank_id ? books.banks.find((item) => item.id === client.bank_id) : null) ?? books.banks[0];
	const fail = (error) => ({
		pages: [],
		pageOne: [],
		pageTwo: [],
		error,
		dbLabel: "0.00",
		crLabel: "0.00",
		dbN: 0,
		crN: 0,
		closingLabel: "0.00"
	});
	if (!statement || !client || !bank) return fail("Chýba účet alebo banka.");
	const movements = Array.isArray(statement.movements) ? statement.movements : [];
	const sums = ledger(statement.opening, movements);
	if (!sums.ok) return fail("Suma pohybu nie je číslo.");
	const currency = client.currency || "EUR";
	const issued = statement.issued_on;
	const accountType = client.type || "Podnikateľský účet";
	let textOver = 0;
	for (const movement of movements) for (const spans of blocks(movement, partyOf(books, movement))) for (const [col, text] of spans) textOver = Math.max(textOver, placedExcess(col, text));
	if (textOver > 0) return fail(`Text je o ${textOver} znakov dlhší než je povolené.`);
	if (movements.some((movement) => (movement.kind === "in" || movement.kind === "out") && !partyOf(books, movement))) return fail("Pohyb nemá protistranu z customers alebo contractors.");
	const subheader = row([
		[0, `${accountType} ${client.number}`],
		[33, `Majiteľ ${client.name}`],
		[77, "Dátum"],
		[83, issued]
	]);
	const movementBlocks = movements.map((m) => blocks(m, partyOf(books, m)).map((spans) => row(spans)));
	let totalLinesP1 = 0;
	for (let i = 0; i < movementBlocks.length; i++) totalLinesP1 += (i === 0 ? 0 : 1) + movementBlocks[i].length;
	const pages = [];
	const createPageOneHeader = () => {
		const lines = sheet();
		lines[0] = row([
			[0, `${accountType} ${client.number}`],
			[44, "Mena"],
			[50, currency],
			[77, "Dátum"],
			[83, issued]
		]);
		lines[1] = row([
			[0, `IBAN ${formatIban(client.iban)}`],
			[71, "BIC (SWIFT)"],
			[85, client.switft]
		]);
		lines[2] = rule();
		lines[4] = row([[0, `Číslo klienta: ${client.client}`]]);
		lines[5] = row([[0, "Majiteľ účtu:"], [15, client.name]]);
		const address = client.address.split(/\r?\n/);
		lines[6] = row([[15, address[0] ?? ""]]);
		lines[7] = row([[15, address[1] ?? ""]]);
		lines[8] = row([[15, address[2] ?? ""]]);
		lines[9] = row([[0, "Názov účtu:"], [15, client.name]]);
		lines[10] = row([[0, "E-mail:"], [15, client.email]]);
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
			[91, statement.number]
		]);
		lines[30] = subheader;
		lines[31] = rule();
		lines[32] = row([
			[0, "Dátum sprac. Popis"],
			[48, "Dátum zúčt."],
			[87, "Suma"]
		]);
		lines[33] = rule();
		lines[34] = row([
			[13, "Posledný výpis"],
			[29, statement.previous_on],
			money(formatMoney(statement.opening), "debit")
		]);
		return lines;
	};
	const createContinuationPageHeader = () => {
		const lines = sheet();
		lines[0] = subheader;
		lines[1] = rule();
		lines[2] = row([
			[0, "Dátum sprac. Popis"],
			[48, "Dátum zúčt."],
			[87, "Suma"]
		]);
		lines[3] = rule();
		return lines;
	};
	if (35 + totalLinesP1 <= 78) {
		const p1 = createPageOneHeader();
		let cursor = 35;
		movementBlocks.forEach((block, index) => {
			if (index > 0) p1[cursor++] = rule();
			for (const line of block) p1[cursor++] = line;
		});
		addTotals(p1, sums);
		pages.push(p1);
		const p2 = sheet();
		p2[0] = subheader;
		p2[1] = rule();
		addFondOchranyVkladov(p2, 3);
		pages.push(p2);
	} else {
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
			} else break;
		}
		pages.push(p1);
		while (mIdx < movementBlocks.length) {
			const pn = createContinuationPageHeader();
			cursor = 4;
			let remLines = 0;
			for (let i = mIdx; i < movementBlocks.length; i++) remLines += (i === mIdx ? 0 : 1) + movementBlocks[i].length;
			if (4 + remLines <= 71) {
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
				while (mIdx < movementBlocks.length) {
					const block = movementBlocks[mIdx];
					const needed = (cursor === 4 ? 0 : 1) + block.length;
					if (cursor + needed <= 78) {
						if (cursor > 4) pn[cursor++] = rule();
						for (const line of block) pn[cursor++] = line;
						mIdx++;
					} else break;
				}
				pages.push(pn);
			}
		}
		if (!pages[pages.length - 1][78].trim()) {
			const finalPage = sheet();
			finalPage[0] = subheader;
			finalPage[1] = rule();
			addFondOchranyVkladov(finalPage, 3);
			addTotals(finalPage, sums);
			pages.push(finalPage);
		}
	}
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
		closingLabel: moneyFromCents(sums.closing)
	};
}
var PAGE = [595.28, 841.89];
function downloadBlob(blob, name) {
	const url = URL.createObjectURL(blob);
	const link = document.createElement("a");
	link.href = url;
	link.download = name;
	link.click();
	URL.revokeObjectURL(url);
}
async function paintSheet(sheet) {
	const host = document.createElement("div");
	host.style.cssText = "position:fixed;left:-2400px;top:0;width:595.28pt;height:841.89pt;overflow:hidden;pointer-events:none;";
	const clone = sheet.cloneNode(true);
	clone.style.transform = "none";
	clone.style.position = "relative";
	clone.style.left = "0";
	clone.style.top = "0";
	clone.style.boxShadow = "none";
	host.appendChild(clone);
	document.body.appendChild(host);
	try {
		return await html2canvas(clone, {
			scale: 3,
			backgroundColor: "#ffffff",
			width: clone.offsetWidth,
			height: clone.offsetHeight,
			logging: false,
			useCORS: true
		});
	} finally {
		host.remove();
	}
}
function isAuthorized(options) {
	if (typeof options === "boolean") return options;
	return Boolean(options?.authorized);
}
async function buildPdf(sheets, stem, options) {
	const authorized = isAuthorized(options);
	if (sheets.some((sheet) => sheet.querySelector(".watermark") !== null) && !authorized) throw new Error("Dokument je zamknutý. Export bez autorizácie nie je povolený.");
	const pdf = new import_jspdf_node_min.jsPDF({
		unit: "pt",
		format: PAGE,
		compress: true
	});
	pdf.setProperties({
		title: stem || "Výpis z účtu",
		subject: "Výpis z bankového účtu",
		author: "Tatra banka, a.s. / vorkei",
		creator: "pdf-t3.vorkei"
	});
	for (let index = 0; index < sheets.length; index += 1) {
		const image = (await paintSheet(sheets[index])).toDataURL("image/jpeg", .95);
		if (index > 0) pdf.addPage(PAGE);
		pdf.addImage(image, "JPEG", 0, 0, PAGE[0], PAGE[1]);
	}
	return pdf.output("blob");
}
async function downloadStatementPdf(sheets, stem, options) {
	downloadBlob(await buildPdf(sheets, stem, options), `${stem}.pdf`);
}
async function downloadStatementZip(sheets, stem, options) {
	const zip = new import_lib.default();
	zip.file(`${stem}.pdf`, await buildPdf(sheets, stem, options));
	downloadBlob(await zip.generateAsync({ type: "blob" }), `${stem}.zip`);
}
var createSsrRpc = (functionId) => {
	const url = "/_serverFn/" + functionId;
	const serverFnMeta = { id: functionId };
	const fn = async (...args) => {
		return (await getServerFnById(functionId, { origin: "server" }))(...args);
	};
	return Object.assign(fn, {
		url,
		serverFnMeta,
		[TSS_SERVER_FUNCTION]: true
	});
};
var unlockStatement = createServerFn({ method: "POST" }).validator((data) => data).handler(createSsrRpc("2b8e76eaec1f3f4278ec65fc4c18198b2bc26bbddf6a668700aacaca0ed8c0ea"));
var RASTER = [
	"H4CK3D",
	"H4CK3D",
	"H4CK3D",
	"H4CK3D",
	"H4CK3D",
	"H4CK3D",
	"H4CK3D",
	"H4CK3D"
];
function Watermark() {
	return /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
		className: "watermark",
		"aria-hidden": "true",
		children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
			className: "watermark-side",
			children: "H4CK3D"
		}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", {
			className: "watermark-grid",
			children: RASTER.map((word, index) => /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { children: word }, index))
		})]
	});
}
function Sheet({ lines, logo, unlocked }) {
	return /* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", {
		className: "fit",
		children: /* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", {
			className: "fit-spacer",
			children: /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("article", {
				className: "sheet",
				children: [
					logo ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("img", {
						className: "sheet-logo",
						src: "/tb-logo.svg",
						alt: "Tatra banka"
					}) : null,
					/* @__PURE__ */ (0, import_jsx_runtime.jsx)("pre", {
						className: "sheet-pre",
						children: lines.join("\n")
					}),
					!unlocked ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)(Watermark, {}) : null
				]
			})
		})
	});
}
function StatementDocument({ title, fileStem, pages, pageOne, pageTwo, error, onBack }) {
	const [unlocked, setUnlocked] = (0, import_react.useState)(false);
	const [password, setPassword] = (0, import_react.useState)("");
	const [denied, setDenied] = (0, import_react.useState)(false);
	const [busy, setBusy] = (0, import_react.useState)(false);
	const [exportError, setExportError] = (0, import_react.useState)(null);
	const blocked = Boolean(error) || busy;
	const canExport = unlocked && !blocked;
	const blockReason = !error ? null : error.includes("protistranu") ? "Chýba protistrana" : "Pretečenie stĺpca";
	const allPages = pages && pages.length > 0 ? pages : pageOne && pageTwo ? [pageOne, pageTwo] : [];
	async function unlock(event) {
		event.preventDefault();
		if (await unlockStatement({ data: password })) {
			setUnlocked(true);
			setDenied(false);
			setPassword("");
			return;
		}
		setDenied(true);
	}
	function sheets() {
		return Array.from(document.querySelectorAll("article.sheet"));
	}
	async function save(kind) {
		if (!canExport) return;
		setBusy(true);
		setExportError(null);
		try {
			const pageElements = sheets();
			if (pageElements.length === 0 || pageElements.length !== allPages.length) throw new Error("pages");
			if (kind === "pdf") await downloadStatementPdf(pageElements, fileStem, unlocked);
			else await downloadStatementZip(pageElements, fileStem, unlocked);
		} catch {
			setExportError("Súbor sa nepodarilo vytvoriť.");
		} finally {
			setBusy(false);
		}
	}
	return /* @__PURE__ */ (0, import_jsx_runtime.jsxs)(import_jsx_runtime.Fragment, { children: [
		/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("header", {
			className: "toolbar preview-bar",
			children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
				type: "button",
				className: "text-btn",
				onClick: onBack,
				children: "Zoznam"
			}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("h1", {
				className: "toolbar-title",
				children: title
			})] }), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
				className: "preview-tools",
				children: [
					unlocked ? null : /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("form", {
						className: "gate",
						onSubmit: (event) => void unlock(event),
						children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
							className: "gate-row",
							children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
								id: "vypis-gate",
								className: "gate-input",
								type: "password",
								name: "vypis-gate",
								autoComplete: "off",
								value: password,
								"aria-label": "Heslo",
								"aria-invalid": denied || void 0,
								"aria-describedby": denied ? "vypis-gate-error" : void 0,
								onChange: (event) => {
									setPassword(event.target.value);
									setDenied(false);
								}
							}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
								type: "submit",
								className: "print-btn",
								children: "Odomknúť stiahnutie"
							})]
						}), denied ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
							id: "vypis-gate-error",
							className: "gate-error",
							children: "Nesprávne heslo. Súbor ostáva zamknutý."
						}) : null]
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("button", {
						type: "button",
						className: "print-btn",
						disabled: blocked,
						"aria-describedby": blockReason ? "export-block-reason" : void 0,
						onClick: () => window.print(),
						children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Printer, { "aria-hidden": "true" }), "Tlačiť"]
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
						type: "button",
						className: "print-btn",
						disabled: !canExport,
						"aria-describedby": blockReason ? "export-block-reason" : void 0,
						onClick: () => void save("pdf"),
						children: "PDF"
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
						type: "button",
						className: "print-btn",
						disabled: !canExport,
						"aria-describedby": blockReason ? "export-block-reason" : void 0,
						onClick: () => void save("zip"),
						children: "ZIP"
					}),
					blockReason ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
						id: "export-block-reason",
						className: "gate-error",
						children: blockReason
					}) : null
				]
			})]
		}),
		error ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
			className: "banner",
			children: error
		}) : null,
		exportError ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
			className: "banner",
			children: exportError
		}) : null,
		allPages.map((lines, idx) => /* @__PURE__ */ (0, import_jsx_runtime.jsx)(Sheet, {
			lines,
			logo: idx === 0,
			unlocked
		}, idx))
	] });
}
function WelcomeScreen({ books, statementId, rendered, onStatements, onEdit, onPreview }) {
	const statement = books.statements.find((item) => item.id === statementId) ?? books.statements[0];
	const client = books.clients.find((item) => item.id === statement.client_id);
	const bank = (client?.bank_id ? books.banks?.find((item) => item.id === client.bank_id) : null) ?? books.banks?.[0];
	const [help, setHelp] = (0, import_react.useState)(false);
	const street = bank?.addess.split(/\r?\n/)[0] ?? "";
	return /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("section", {
		className: "home",
		children: [
			/* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
				type: "button",
				className: "home-help",
				onClick: () => setHelp(true),
				"aria-label": "Nápoveda",
				children: /* @__PURE__ */ (0, import_jsx_runtime.jsx)(CircleHelp, { "aria-hidden": "true" })
			}),
			/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
				className: "home-stage",
				children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
					className: "home-art",
					"aria-hidden": "true",
					children: [
						/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { className: "home-shard home-shard-a" }),
						/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { className: "home-shard home-shard-b" }),
						/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { className: "home-shard home-shard-c" })
					]
				}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", {
					className: "home-mark",
					"aria-hidden": "true",
					children: /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {})
				})]
			}),
			/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
				className: "home-body",
				children: [
					/* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
						className: "home-kicker",
						children: client?.name
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("h1", {
						className: "home-title",
						children: ["Papierový výpis ", /* @__PURE__ */ (0, import_jsx_runtime.jsx)("strong", { children: "z uložených pohybov" })]
					}),
					statement.movements?.length === 0 ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
						className: "home-balance",
						children: "Tento výpis zatiaľ nemá žiadne pohyby."
					}) : /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("p", {
						className: "home-balance",
						children: [
							rendered.closingLabel,
							" ",
							client?.currency
						]
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("dl", {
						className: "home-facts",
						children: [
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("dt", { children: "Účet" }), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("dd", { children: [
								client?.type,
								" ",
								client?.number
							] })] }),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("dt", { children: "IBAN" }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("dd", { children: client ? formatIban(client.iban) : "" })] }),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("dt", { children: "Banka" }), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("dd", { children: [bank?.name, street ? `, ${street}` : ""] })] }),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("dt", { children: "Klient" }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("dd", { children: client?.client })] })
						]
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
						type: "button",
						className: "home-primary",
						onClick: onPreview,
						children: "Otvoriť výpis"
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
						type: "button",
						className: "home-secondary",
						onClick: onStatements,
						children: "Všetky výpisy"
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("p", {
						className: "home-meta",
						children: [
							"Výpis ",
							statement.number,
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("br", {}),
							"Deň výpisu: ",
							statement.issued_on
						]
					})
				]
			}),
			/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("nav", {
				className: "home-nav",
				"aria-label": "Ponuka",
				children: [
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("button", {
						type: "button",
						onClick: onStatements,
						children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Rows3, { "aria-hidden": "true" }), "Výpisy"]
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("button", {
						type: "button",
						onClick: () => setHelp(true),
						children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Landmark, { "aria-hidden": "true" }), "Účet"]
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("button", {
						type: "button",
						onClick: onEdit,
						children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(FileText, { "aria-hidden": "true" }), "Pohyby"]
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("button", {
						type: "button",
						onClick: onPreview,
						children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Eye, { "aria-hidden": "true" }), "Náhľad"]
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("button", {
						type: "button",
						onClick: onPreview,
						children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Printer, { "aria-hidden": "true" }), "Tlač"]
					})
				]
			}),
			help ? /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
				className: "home-sheet",
				role: "dialog",
				"aria-labelledby": "home-help-title",
				children: [
					/* @__PURE__ */ (0, import_jsx_runtime.jsx)("h2", {
						id: "home-help-title",
						children: "Údaje účtu"
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", { children: "Toto nie je aplikácia banky. Nižšie sú polia z výpisu, nie prihlásenie." }),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("dl", {
						className: "home-facts",
						children: [
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("dt", { children: "Majiteľ" }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("dd", { children: client?.name })] }),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("dt", { children: "Adresa" }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("dd", { children: client?.address })] }),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("dt", { children: "E-mail" }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("dd", { children: client?.email })] }),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("dt", { children: "SWIFT účtu" }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("dd", { children: client?.switft })] }),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("dt", { children: "Banka" }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("dd", { children: bank?.addess })] })
						]
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
						type: "button",
						className: "home-primary",
						onClick: () => setHelp(false),
						children: "Zavrieť"
					})
				]
			}) : null
		]
	});
}
var STORAGE_KEY = "vorkei-bridge-config-v2";
var DEFAULT_CONFIG = {
	baseUrl: "https://pdf-t3.vorkei.com",
	username: "support@vorkei.c",
	appPassword: "7fv8 7JAG XPcc IpDk b6dn BgMk",
	bearerToken: "",
	autoSync: false
};
function getBridgeConfig() {
	if (typeof window === "undefined") return DEFAULT_CONFIG;
	try {
		const raw = localStorage.getItem(STORAGE_KEY);
		if (!raw) return DEFAULT_CONFIG;
		return {
			...DEFAULT_CONFIG,
			...JSON.parse(raw)
		};
	} catch {
		return DEFAULT_CONFIG;
	}
}
function saveBridgeConfig(config) {
	if (typeof window === "undefined") return;
	localStorage.setItem(STORAGE_KEY, JSON.stringify(config));
}
function getAuthHeader(config) {
	const headers = {
		"Content-Type": "application/json",
		Accept: "application/json"
	};
	if (config.bearerToken && config.bearerToken.trim().length > 0) headers["Authorization"] = `Bearer ${config.bearerToken.trim()}`;
	else if (config.username && config.appPassword) {
		const cleanPass = config.appPassword.replace(/\s+/g, "");
		headers["Authorization"] = `Basic ${btoa(`${config.username.trim()}:${cleanPass}`)}`;
	}
	return headers;
}
async function testBridgeConnection(config) {
	const base = config.baseUrl.replace(/\/+$/, "");
	const endpoint = `${base}/wp-json/vorkei/v1/statements`;
	try {
		const res = await fetch(endpoint, {
			method: "GET",
			headers: getAuthHeader(config)
		});
		if (res.ok) return {
			ok: true,
			message: "Spojenie s WordPress REST API na vorkei.com je aktívne a overené!",
			status: res.status
		};
		if (res.status === 401 || res.status === 403) return {
			ok: false,
			message: "Chyba autentifikácie (401/403): Skontrolujte používateľské meno a aplikačné heslo.",
			status: res.status
		};
		if (res.status === 404) {
			if ((await fetch(`${base}/wp-json/`, { method: "GET" })).ok) return {
				ok: false,
				message: "WordPress REST API odpovedá, ale plugin Vorkei Statement Bridge ešte nie je na webe aktivovaný.",
				status: 404
			};
		}
		return {
			ok: false,
			message: `Server vrátil stav ${res.status}: ${res.statusText}`,
			status: res.status
		};
	} catch (err) {
		return {
			ok: false,
			message: `Chyba siete: ${err.message || "Nedá sa pripojiť k serveru vorkei.com"}`,
			status: 0
		};
	}
}
/**
* Synchronize local Books (Statements & Movements) to WordPress Vorkei Statement Bridge
*/
async function pushStatementsToWordPress(config, books) {
	const endpoint = `${config.baseUrl.replace(/\/+$/, "")}/wp-json/vorkei/v1/sync`;
	const defaultClient = books.clients[0];
	const payload = {
		account: {
			iban: defaultClient ? defaultClient.iban : "",
			name: defaultClient ? defaultClient.name : ""
		},
		statements: books.statements.map((st) => {
			const client = books.clients.find((c) => c.id === st.client_id) ?? defaultClient;
			const bank = (client?.bank_id ? books.banks.find((b) => b.id === client.bank_id) : null) ?? books.banks[0];
			const accountType = client?.type || "Podnikateľský účet";
			let balance = st.opening;
			for (const m of st.movements) if (m.kind === "in") balance += m.amount;
			else balance -= m.amount;
			return {
				id: st.id,
				number: st.number,
				title: `Výpis číslo ${st.number}`,
				slug: `vypis-${st.number}`,
				openingCents: st.opening,
				closingCents: balance,
				currency: client ? client.currency : "EUR",
				issuedOn: st.issued_on,
				bank_cct_id: bank && /^\d+$/.test(bank.id) ? Number(bank.id) : 0,
				bank_name: bank ? bank.name : "Tatra banka, a.s.",
				bank_data: bank ? bank.addess : "",
				client_cct_id: client && /^\d+$/.test(client.id) ? Number(client.id) : 0,
				client_id: client ? client.client : "",
				client_name: client ? client.name : "",
				client_data: client ? client.address : "",
				client_email: client ? client.email : "",
				account_type: accountType,
				account_number: client ? String(client.number) : "",
				account_iban: client ? client.iban : "",
				account_swift: client ? client.switft : "TATRSKBXXXX",
				movements: st.movements.map((m) => {
					const party = m.kind === "in" ? books.customers.find((p) => p.id === m.party_id) : books.contractors.find((p) => p.id === m.party_id);
					return {
						id: m.id,
						statement_id: Number(st.number) || 0,
						date: m.date,
						amountCents: m.amount,
						amountFormatted: (m.amount / 100).toFixed(2),
						kind: m.kind,
						payment_type: m.kind === "in" ? "Prijatá platba" : m.kind === "out" ? "Odoslaná platba" : "Transakčná daň",
						customer_cct_id: m.kind === "in" && party && /^\d+$/.test(party.id) ? Number(party.id) : 0,
						contractor_cct_id: m.kind === "out" && party && /^\d+$/.test(party.id) ? Number(party.id) : 0,
						customer_id: m.kind === "in" && party ? party.id : "",
						contractor_id: m.kind === "out" && party ? party.id : "",
						partyName: party ? party.name : "",
						partyIban: party ? party.iban : "",
						variableSymbol: m.symbol || "",
						note: m.text || "",
						detail: m.text || (m.symbol ? `VS: ${m.symbol}` : "") || m.fee_note || "",
						feeNote: m.fee_note || ""
					};
				})
			};
		})
	};
	const res = await fetch(endpoint, {
		method: "POST",
		headers: getAuthHeader(config),
		body: JSON.stringify(payload)
	});
	if (!res.ok) {
		const errorText = await res.text();
		throw new Error(`Chyba synchronizácie (${res.status}): ${errorText}`);
	}
	return await res.json();
}
/**
* Fetch Clients, Customers, Contractors, and Banks from JetEngine CCT
*/
async function pullJetEngineData(config) {
	const base = config.baseUrl.replace(/\/+$/, "");
	const headers = getAuthHeader(config);
	const fetchCct = async (slug) => {
		try {
			const res = await fetch(`${base}/wp-json/jet-cct/${slug}`, { headers });
			if (res.ok) return await res.json();
		} catch {}
		return null;
	};
	const [banksData, clientsData, customersData, contractorsData] = await Promise.all([
		fetchCct("banks"),
		fetchCct("clients"),
		fetchCct("customers"),
		fetchCct("contractors")
	]);
	const output = {};
	if (Array.isArray(banksData)) output.banks = banksData.map((b) => ({
		id: String(b._ID || b.id),
		name: b.name || "",
		addess: b.data || b.addess || b.address || "",
		logo: b.logo || null
	}));
	if (Array.isArray(clientsData)) output.clients = clientsData.map((c) => ({
		id: String(c._ID || c.id),
		bank_id: String(c.bank || c.bank_id || ""),
		client: c.client || "",
		name: c.name || "",
		address: c.address || "",
		email: c.email || "",
		type: c.type || "Podnikateľský účet",
		number: String(c.number || ""),
		iban: c.iban || "",
		switft: c.swift || c.switft || "",
		currency: c.currency || "EUR"
	}));
	if (Array.isArray(customersData)) output.customers = customersData.map((cu) => ({
		id: String(cu._ID || cu.id),
		client_id: String(cu.client || cu.client_id || ""),
		name: cu.name || "",
		iban: cu.iban || "",
		swift: cu.swift || ""
	}));
	if (Array.isArray(contractorsData)) output.contractors = contractorsData.map((co) => ({
		id: String(co._ID || co.id),
		client_id: String(co.client || co.client_id || ""),
		name: co.name || "",
		iban: co.iban || "",
		swift: co.swift || ""
	}));
	return output;
}
function VorkeiSyncModal({ isOpen, onClose, books, onUpdateBooks }) {
	const [config, setConfig] = (0, import_react.useState)(DEFAULT_CONFIG);
	const [testing, setTesting] = (0, import_react.useState)(false);
	const [pushing, setPushing] = (0, import_react.useState)(false);
	const [pulling, setPulling] = (0, import_react.useState)(false);
	const [statusMessage, setStatusMessage] = (0, import_react.useState)(null);
	(0, import_react.useEffect)(() => {
		if (isOpen) {
			setConfig(getBridgeConfig());
			setStatusMessage(null);
		}
	}, [isOpen]);
	if (!isOpen) return null;
	async function handleTest() {
		setTesting(true);
		setStatusMessage(null);
		saveBridgeConfig(config);
		try {
			const res = await testBridgeConnection(config);
			setStatusMessage({
				text: res.message,
				type: res.ok ? "success" : "error"
			});
		} catch (err) {
			setStatusMessage({
				text: `Chyba spojenia: ${err.message}`,
				type: "error"
			});
		} finally {
			setTesting(false);
		}
	}
	async function handlePush() {
		setPushing(true);
		setStatusMessage(null);
		saveBridgeConfig(config);
		try {
			const res = await pushStatementsToWordPress(config, books);
			setStatusMessage({
				text: res.message || "Výpisy boli úspešne synchronizované do WordPressu!",
				type: "success"
			});
		} catch (err) {
			setStatusMessage({
				text: err.message,
				type: "error"
			});
		} finally {
			setPushing(false);
		}
	}
	async function handlePull() {
		setPulling(true);
		setStatusMessage(null);
		saveBridgeConfig(config);
		try {
			const data = await pullJetEngineData(config);
			let bCount = 0;
			let clCount = 0;
			let cuCount = 0;
			let coCount = 0;
			onUpdateBooks((prev) => {
				const next = { ...prev };
				if (data.banks && data.banks.length > 0) {
					const bankMap = new Map(next.banks.map((b) => [b.id, b]));
					for (const b of data.banks) {
						bankMap.set(b.id, b);
						bCount++;
					}
					next.banks = Array.from(bankMap.values());
				}
				if (data.clients && data.clients.length > 0) {
					const clientMap = new Map(next.clients.map((c) => [c.id, c]));
					for (const c of data.clients) {
						const cleanIban = c.iban?.replace(/\s+/g, "").toUpperCase();
						const existing = Array.from(clientMap.values()).find((ex) => cleanIban && ex.iban?.replace(/\s+/g, "").toUpperCase() === cleanIban);
						if (existing) clientMap.set(existing.id, {
							...existing,
							...c,
							id: existing.id
						});
						else clientMap.set(c.id, c);
						clCount++;
					}
					next.clients = Array.from(clientMap.values());
				}
				if (data.customers && data.customers.length > 0) {
					const custMap = new Map(next.customers.map((p) => [p.id, p]));
					for (const p of data.customers) {
						const cleanIban = p.iban?.replace(/\s+/g, "").toUpperCase();
						const existing = Array.from(custMap.values()).find((ex) => cleanIban && ex.iban?.replace(/\s+/g, "").toUpperCase() === cleanIban);
						if (existing) custMap.set(existing.id, {
							...existing,
							...p,
							id: existing.id
						});
						else custMap.set(p.id, p);
						cuCount++;
					}
					next.customers = Array.from(custMap.values());
				}
				if (data.contractors && data.contractors.length > 0) {
					const contMap = new Map(next.contractors.map((p) => [p.id, p]));
					for (const p of data.contractors) {
						const cleanIban = p.iban?.replace(/\s+/g, "").toUpperCase();
						const existing = Array.from(contMap.values()).find((ex) => cleanIban && ex.iban?.replace(/\s+/g, "").toUpperCase() === cleanIban);
						if (existing) contMap.set(existing.id, {
							...existing,
							...p,
							id: existing.id
						});
						else contMap.set(p.id, p);
						coCount++;
					}
					next.contractors = Array.from(contMap.values());
				}
				return next;
			});
			if (bCount + clCount + cuCount + coCount > 0) setStatusMessage({
				text: `Úspešne synchronizované: ${clCount} klientov, ${cuCount} odberateľov, ${coCount} dodávateľov, ${bCount} bánk.`,
				type: "success"
			});
			else setStatusMessage({
				text: "Žiadne nové dáta z JetEngine neboli nájdené alebo REST API vrátilo prázdne zoznamy.",
				type: "info"
			});
		} catch (err) {
			setStatusMessage({
				text: `Chyba pri sťahovaní: ${err.message}`,
				type: "error"
			});
		} finally {
			setPulling(false);
		}
	}
	return /* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", {
		className: "fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4",
		children: /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
			className: "relative w-full max-w-lg rounded-2xl bg-[#0f1117] border border-white/10 p-6 shadow-2xl text-white",
			children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
				className: "flex items-center justify-between pb-4 border-b border-white/10",
				children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
					className: "flex items-center gap-2",
					children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Cloud, { className: "w-5 h-5 text-blue-400" }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("h2", {
						className: "text-lg font-semibold tracking-wide",
						children: "WordPress Bridge · pdf-t3.vorkei.com"
					})]
				}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
					type: "button",
					onClick: onClose,
					className: "p-1 rounded-lg text-white/50 hover:text-white hover:bg-white/10 transition",
					children: /* @__PURE__ */ (0, import_jsx_runtime.jsx)(X, { className: "w-5 h-5" })
				})]
			}), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
				className: "mt-4 space-y-4",
				children: [
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("label", {
						className: "block text-xs font-medium text-white/70 mb-1",
						children: "URL WordPressu"
					}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
						type: "text",
						value: config.baseUrl,
						onChange: (e) => setConfig({
							...config,
							baseUrl: e.target.value
						}),
						placeholder: "https://pdf-t3.vorkei.com",
						className: "w-full px-3 py-2 rounded-lg bg-black/40 border border-white/15 text-sm focus:outline-none focus:border-blue-500"
					})] }),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
						className: "grid grid-cols-1 sm:grid-cols-2 gap-3",
						children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("label", {
							className: "block text-xs font-medium text-white/70 mb-1",
							children: "Používateľ (Username)"
						}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
							type: "text",
							value: config.username,
							onChange: (e) => setConfig({
								...config,
								username: e.target.value
							}),
							placeholder: "support@vorkei.c",
							className: "w-full px-3 py-2 rounded-lg bg-black/40 border border-white/15 text-sm focus:outline-none focus:border-blue-500"
						})] }), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("label", {
							className: "block text-xs font-medium text-white/70 mb-1",
							children: "Aplikačné heslo (App Password)"
						}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
							type: "password",
							value: config.appPassword || "",
							onChange: (e) => setConfig({
								...config,
								appPassword: e.target.value
							}),
							placeholder: "xxxx xxxx xxxx xxxx",
							className: "w-full px-3 py-2 rounded-lg bg-black/40 border border-white/15 text-sm focus:outline-none focus:border-blue-500"
						})] })]
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("label", {
						className: "block text-xs font-medium text-white/50 mb-1",
						children: "Alebo Bearer API Token (voliteľné)"
					}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
						type: "password",
						value: config.bearerToken || "",
						onChange: (e) => setConfig({
							...config,
							bearerToken: e.target.value
						}),
						placeholder: "Kľúč z administrácie vorkei pluginu",
						className: "w-full px-3 py-2 rounded-lg bg-black/40 border border-white/10 text-xs focus:outline-none focus:border-blue-500"
					})] }),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
						className: "p-3 rounded-lg bg-white/5 border border-white/10 text-xs flex justify-between items-center text-white/70",
						children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { children: "Lokálny stav aplikácie:" }), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("span", {
							className: "font-mono text-white/90",
							children: [
								books.statements.length,
								" výpisov · ",
								books.clients.length,
								" účtov · ",
								books.customers.length + books.contractors.length,
								" partnerov"
							]
						})]
					}),
					statusMessage && /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
						className: `p-3 rounded-lg text-xs flex items-start gap-2 ${statusMessage.type === "success" ? "bg-emerald-950/50 border border-emerald-500/30 text-emerald-300" : statusMessage.type === "error" ? "bg-rose-950/50 border border-rose-500/30 text-rose-300" : "bg-blue-950/50 border border-blue-500/30 text-blue-300"}`,
						children: [statusMessage.type === "error" ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)(CircleAlert, { className: "w-4 h-4 shrink-0 mt-0.5" }) : statusMessage.type === "success" ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)(Check, { className: "w-4 h-4 shrink-0 mt-0.5" }) : /* @__PURE__ */ (0, import_jsx_runtime.jsx)(Cloud, { className: "w-4 h-4 shrink-0 mt-0.5" }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { children: statusMessage.text })]
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
						className: "pt-2 flex flex-wrap gap-2",
						children: [
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("button", {
								type: "button",
								onClick: handleTest,
								disabled: testing || pushing || pulling,
								className: "px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-medium flex items-center gap-1.5 transition disabled:opacity-50",
								children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(RefreshCw, { className: `w-3.5 h-3.5 ${testing ? "animate-spin" : ""}` }), "Otestovať spojenie"]
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("button", {
								type: "button",
								onClick: handlePull,
								disabled: testing || pushing || pulling,
								title: "Stiahne banky, klientov, odberateľov a dodávateľov z JetEngine CCT",
								className: "px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-medium flex items-center gap-1.5 transition disabled:opacity-50",
								children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(CloudDownload, { className: `w-3.5 h-3.5 ${pulling ? "animate-bounce" : ""}` }), "Stiahnuť subjekty z WP"]
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("button", {
								type: "button",
								onClick: handlePush,
								disabled: testing || pushing || pulling,
								title: "Odošle výpisy a transakcie do CCT 13 a 14 a spáruje objednávky",
								className: "ml-auto px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-xs font-medium text-white flex items-center gap-1.5 shadow-lg shadow-blue-500/20 transition disabled:opacity-50",
								children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(CloudUpload, { className: `w-3.5 h-3.5 ${pushing ? "animate-bounce" : ""}` }), "Odoslať výpisy do WP"]
							})
						]
					})
				]
			})]
		})
	});
}
var KEY = "vypis-books-v1";
function uid(prefix) {
	return `${prefix}-${crypto.randomUUID()}`;
}
function today() {
	const now = /* @__PURE__ */ new Date();
	return `${String(now.getDate()).padStart(2, "0")}.${String(now.getMonth() + 1).padStart(2, "0")}.${now.getFullYear()}`;
}
function VypisApp() {
	const [books, setBooks] = (0, import_react.useState)(SEED);
	const [ready, setReady] = (0, import_react.useState)(false);
	const [notice, setNotice] = (0, import_react.useState)(null);
	const [view, setView] = (0, import_react.useState)("home");
	const [activeId, setActiveId] = (0, import_react.useState)(SEED.statements[0].id);
	const [syncOpen, setSyncOpen] = (0, import_react.useState)(false);
	const fileInputRef = (0, import_react.useRef)(null);
	function downloadJson(data, filename) {
		const blob = new Blob([JSON.stringify(data, null, 2)], { type: "application/json" });
		const url = URL.createObjectURL(blob);
		const a = document.createElement("a");
		a.href = url;
		a.download = filename;
		a.click();
		URL.revokeObjectURL(url);
	}
	function downloadLatestBackup() {
		let latestKey = null;
		let latestTime = 0;
		for (let i = 0; i < localStorage.length; i++) {
			const k = localStorage.key(i);
			if (k && k.startsWith(`${KEY}-backup-`)) {
				const time = Number(k.replace(`${KEY}-backup-`, "")) || 0;
				if (time > latestTime) {
					latestTime = time;
					latestKey = k;
				}
			}
		}
		if (latestKey) {
			const content = localStorage.getItem(latestKey);
			if (content) {
				const blob = new Blob([content], { type: "application/json" });
				const url = URL.createObjectURL(blob);
				const a = document.createElement("a");
				a.href = url;
				a.download = `${latestKey}.json`;
				a.click();
				URL.revokeObjectURL(url);
				return;
			}
		}
		downloadJson(books, `vypis-databaza-zaloha-${today().replace(/\./g, "-")}.json`);
	}
	function handleExportDatabase() {
		downloadJson(books, `vypis-databaza-${today().replace(/\./g, "-")}.json`);
	}
	function handleRestoreFile(event) {
		const file = event.target.files?.[0];
		if (!file) return;
		const reader = new FileReader();
		reader.onload = (e) => {
			try {
				const parsed = JSON.parse(e.target?.result);
				if (isBooks(parsed)) {
					setBooks(parsed);
					setNotice("Dáta boli úspešne obnovené zo záložného súboru.");
				} else alert("Súbor neobsahuje platnú štruktúru databázy výpisov.");
			} catch {
				alert("Súbor nie je platný JSON formát.");
			}
		};
		reader.readAsText(file);
		event.target.value = "";
	}
	function handleResetSeed() {
		if (window.confirm("Naozaj chcete resetovať databázu do predvoleného stavu SEED? Všetky zmeny budú archivované do zálohy.")) {
			localStorage.setItem(`${KEY}-backup-${Date.now()}`, JSON.stringify(books));
			setBooks(structuredClone(SEED));
			setNotice("Databáza bola úspešne obnovená do predvoleného stavu SEED. Pôvodné dáta boli uložené do zálohy.");
		}
	}
	(0, import_react.useEffect)(() => {
		let raw = null;
		try {
			raw = localStorage.getItem(KEY);
			const loaded = readStoredBooks(raw);
			if (loaded.notice && raw !== null) {
				localStorage.setItem(`${KEY}-backup-${Date.now()}`, raw);
				setNotice(loaded.notice);
			}
			setBooks(loaded.books);
		} catch {
			if (raw !== null) localStorage.setItem(`${KEY}-backup-${Date.now()}`, raw);
			setBooks(structuredClone(SEED));
			setNotice(DAMAGE_NOTICE);
		}
		setReady(true);
	}, []);
	(0, import_react.useEffect)(() => {
		if (!ready) return;
		localStorage.setItem(KEY, JSON.stringify(books));
	}, [books, ready]);
	const statement = books.statements.find((item) => item.id === activeId) ?? books.statements[0] ?? SEED.statements[0];
	const rendered = renderStatement(books, statement.id);
	function patchStatement(partial) {
		setBooks((current) => ({
			...current,
			statements: current.statements.map((item) => item.id === statement.id ? {
				...item,
				...partial
			} : item)
		}));
	}
	function patchMovement(id, partial) {
		patchStatement({ movements: statement.movements.map((item) => item.id === id ? {
			...item,
			...partial
		} : item) });
	}
	function addStatement() {
		const id = uid("s");
		const next = {
			id,
			client_id: books.clients[0]?.id || "",
			number: String(books.statements.length + 1),
			issued_on: today(),
			previous_on: "",
			opening: 0,
			movements: []
		};
		setBooks((current) => ({
			...current,
			statements: [...current.statements, next]
		}));
		setActiveId(id);
		setView("edit");
	}
	function addParty(kind, party) {
		const list = kind === "in" ? books.customers : books.contractors;
		const cleanIban = party.iban.replace(/\s+/g, "").toUpperCase();
		const cleanName = party.name.trim().toLowerCase();
		const existing = list.find((p) => p.client_id === statement.client_id && p.iban.replace(/\s+/g, "").toUpperCase() === cleanIban && p.name.trim().toLowerCase() === cleanName);
		if (existing) return existing.id;
		const id = uid(kind === "in" ? "cu" : "co");
		const row = {
			...party,
			name: party.name.trim(),
			iban: cleanIban,
			swift: party.swift.trim(),
			id,
			client_id: statement.client_id
		};
		setBooks((current) => ({
			...current,
			customers: kind === "in" ? [...current.customers, row] : current.customers,
			contractors: kind === "out" ? [...current.contractors, row] : current.contractors
		}));
		return id;
	}
	return /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
		className: view === "home" ? "desk desk-home" : "desk",
		children: [
			notice ? /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
				className: "banner",
				style: {
					display: "flex",
					justifyContent: "space-between",
					alignItems: "center",
					flexWrap: "wrap",
					gap: "0.5rem"
				},
				children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { children: notice }), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
					style: {
						display: "flex",
						gap: "0.5rem",
						alignItems: "center"
					},
					children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("button", {
						type: "button",
						className: "text-btn",
						style: {
							fontSize: "0.8rem",
							padding: "0.2rem 0.6rem",
							background: "rgba(255,255,255,0.15)",
							borderRadius: "0.35rem",
							display: "inline-flex",
							alignItems: "center",
							gap: "0.25rem"
						},
						onClick: downloadLatestBackup,
						children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Download, { style: {
							width: "0.85rem",
							height: "0.85rem"
						} }), "Stiahnuť zálohu"]
					}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
						type: "button",
						className: "text-btn",
						style: {
							fontSize: "0.85rem",
							padding: "0.2rem 0.4rem"
						},
						title: "Zavrieť upozornenie",
						onClick: () => setNotice(null),
						children: "✕"
					})]
				})]
			}) : null,
			view === "home" ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)(WelcomeScreen, {
				books,
				statementId: statement.id,
				rendered,
				onStatements: () => setView("list"),
				onEdit: () => setView("edit"),
				onPreview: () => setView("preview")
			}) : null,
			view === "list" ? /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("section", {
				className: "screen-ui",
				children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("header", {
					className: "toolbar",
					children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [
						/* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
							className: "toolbar-kicker",
							children: "Účty a výpisy"
						}),
						/* @__PURE__ */ (0, import_jsx_runtime.jsx)("h1", {
							className: "toolbar-title",
							children: "Výpis"
						}),
						/* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
							type: "button",
							className: "text-btn",
							onClick: () => setView("home"),
							children: "Úvod"
						})
					] }), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
						className: "row-actions",
						style: {
							flexWrap: "wrap",
							gap: "0.4rem"
						},
						children: [
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
								ref: fileInputRef,
								type: "file",
								accept: ".json",
								style: { display: "none" },
								onChange: handleRestoreFile
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("button", {
								type: "button",
								className: "text-btn",
								title: "Stiahnuť kompletnú databázu v JSON formáte",
								style: {
									display: "inline-flex",
									alignItems: "center",
									gap: "0.35rem"
								},
								onClick: handleExportDatabase,
								children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Download, {
									"aria-hidden": "true",
									style: {
										width: "0.95rem",
										height: "0.95rem"
									}
								}), "Záloha JSON"]
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("button", {
								type: "button",
								className: "text-btn",
								title: "Nahrať databázu zo záložného súboru JSON",
								style: {
									display: "inline-flex",
									alignItems: "center",
									gap: "0.35rem"
								},
								onClick: () => fileInputRef.current?.click(),
								children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Upload, {
									"aria-hidden": "true",
									style: {
										width: "0.95rem",
										height: "0.95rem"
									}
								}), "Obnoviť"]
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("button", {
								type: "button",
								className: "text-btn",
								title: "Obnoviť predvolené dáta (SEED)",
								style: {
									display: "inline-flex",
									alignItems: "center",
									gap: "0.35rem",
									color: "#f87171"
								},
								onClick: handleResetSeed,
								children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(RotateCcw, {
									"aria-hidden": "true",
									style: {
										width: "0.95rem",
										height: "0.95rem"
									}
								}), "Reset"]
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("button", {
								type: "button",
								className: "text-btn",
								style: {
									display: "inline-flex",
									alignItems: "center",
									gap: "0.35rem"
								},
								onClick: () => setSyncOpen(true),
								children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Cloud, {
									"aria-hidden": "true",
									style: {
										width: "1rem",
										height: "1rem"
									}
								}), "vorkei.com Bridge"]
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
								type: "button",
								className: "print-btn",
								onClick: addStatement,
								children: "Nový výpis"
							})
						]
					})]
				}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("ul", {
					className: "cards",
					children: books.statements.map((item) => {
						const viewModel = renderStatement(books, item.id);
						const client = books.clients.find((row) => row.id === item.client_id);
						return /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("li", {
							className: "card",
							children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [
								/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("strong", { children: ["Výpis ", item.number] }),
								/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("p", { children: [
									client?.name,
									" · ",
									item.issued_on
								] }),
								item.movements.length === 0 ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", { children: "Tento výpis zatiaľ nemá žiadne pohyby." }) : /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("p", {
									className: "amount",
									children: [
										viewModel.closingLabel,
										" ",
										client?.currency
									]
								})
							] }), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
								className: "row-actions",
								children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
									type: "button",
									className: "text-btn",
									onClick: () => {
										setActiveId(item.id);
										setView("edit");
									},
									children: "Upraviť"
								}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
									type: "button",
									className: "print-btn",
									onClick: () => {
										setActiveId(item.id);
										setView("preview");
									},
									children: "Náhľad"
								})]
							})]
						}, item.id);
					})
				})]
			}) : null,
			view === "edit" ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)(Editor, {
				books,
				statementId: statement.id,
				onBack: () => setView("list"),
				onPreview: () => setView("preview"),
				onStatement: patchStatement,
				onMovement: patchMovement,
				onAddParty: addParty,
				summary: rendered
			}) : null,
			view === "preview" ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)(StatementDocument, {
				title: `Výpis číslo ${statement.number}`,
				fileStem: statementFileStem(statement.number),
				pages: rendered.pages,
				pageOne: rendered.pageOne,
				pageTwo: rendered.pageTwo,
				error: rendered.error,
				onBack: () => setView("list")
			}) : null,
			/* @__PURE__ */ (0, import_jsx_runtime.jsx)(VorkeiSyncModal, {
				isOpen: syncOpen,
				onClose: () => setSyncOpen(false),
				books,
				onUpdateBooks: setBooks
			})
		]
	});
}
function Editor({ books, statementId, onBack, onPreview, onStatement, onMovement, onAddParty, summary }) {
	const statement = books.statements.find((item) => item.id === statementId);
	const client = books.clients.find((item) => item.id === statement.client_id) ?? books.clients[0];
	const [draft, setDraft] = (0, import_react.useState)({
		kind: "in",
		date: statement.issued_on,
		text: "",
		symbol: "",
		amount: "",
		valuta: statement.issued_on,
		rate: "1.00000000",
		party_id: "",
		fee_note: "Tatra Business Premium",
		newName: "",
		newIban: "",
		newSwift: ""
	});
	const [numberText, setNumberText] = (0, import_react.useState)(statement.number);
	const [issuedText, setIssuedText] = (0, import_react.useState)(statement.issued_on);
	const [previousText, setPreviousText] = (0, import_react.useState)(statement.previous_on);
	const [openingText, setOpeningText] = (0, import_react.useState)(plainCents(statement.opening));
	const [fieldError, setFieldError] = (0, import_react.useState)({});
	const [draftError, setDraftError] = (0, import_react.useState)({});
	const [amountText, setAmountText] = (0, import_react.useState)({});
	const [pendingDelete, setPendingDelete] = (0, import_react.useState)(null);
	(0, import_react.useEffect)(() => {
		setNumberText(statement.number);
		setIssuedText(statement.issued_on);
		setPreviousText(statement.previous_on);
		setOpeningText(plainCents(statement.opening));
		setFieldError({});
		setDraftError({});
		setAmountText({});
		setPendingDelete(null);
	}, [statement.id]);
	const textLimit = draft.kind === "in" || draft.kind === "out" ? 140 : 80;
	const textUsed = Array.from(draft.text).length;
	const textOver = Math.max(0, textUsed - textLimit);
	const hasErrors = Object.values(fieldError).some((err) => Boolean(err && err.trim().length > 0));
	function addMovement() {
		const nextError = {};
		if (!isRealDate(draft.date)) nextError.date = "Dátum musí byť skutočný deň vo formáte dd.mm.rrrr.";
		const amount = parseAmountInput(draft.amount, false);
		if (!amount.ok) nextError.amount = amount.error;
		if (!draft.text.trim()) nextError.text = "Text je prázdny.";
		if (textOver > 0) nextError.text = `Text je o ${textOver} znakov dlhší než je povolené.`;
		const needsParty = draft.kind === "in" || draft.kind === "out";
		if (needsParty && !draft.party_id) {
			if (!draft.newName.trim()) nextError.name = "Názov je prázdny.";
			if (!isSkIban(draft.newIban)) nextError.iban = "IBAN musí mať 24 znakov, začínať na SK a prejsť kontrolou.";
		}
		if (amount.ok && draftExcess(draft.kind, draft.text, draft.fee_note, amount.cents) > textOver) nextError.text = `Text je o ${draftExcess(draft.kind, draft.text, draft.fee_note, amount.cents)} znakov dlhší než je povolené.`;
		if (Object.keys(nextError).length > 0) {
			setDraftError(nextError);
			return;
		}
		if (!amount.ok) return;
		let partyId = draft.party_id;
		if (needsParty && !partyId) partyId = onAddParty(draft.kind, {
			name: draft.newName.trim(),
			iban: draft.newIban.replace(/\s+/g, "").toUpperCase(),
			swift: draft.newSwift.trim()
		});
		const movement = {
			id: uid("m"),
			kind: draft.kind,
			date: draft.date,
			text: draft.text.trim(),
			symbol: draft.symbol,
			amount: amount.cents,
			valuta: draft.valuta,
			rate: draft.rate || "1.00000000",
			party_id: needsParty ? partyId : "",
			fee_note: draft.kind === "fee" ? draft.fee_note : ""
		};
		onStatement({ movements: [...statement.movements, movement] });
		setDraftError({});
		setDraft((current) => ({
			...current,
			text: "",
			symbol: "",
			amount: "",
			party_id: partyId
		}));
	}
	const parties = draft.kind === "in" ? books.customers.filter((item) => !item.client_id || item.client_id === statement.client_id) : books.contractors.filter((item) => !item.client_id || item.client_id === statement.client_id);
	return /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("section", {
		className: "screen-ui",
		children: [
			/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("header", {
				className: "toolbar",
				children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
					type: "button",
					className: "text-btn",
					onClick: onBack,
					children: "Zoznam"
				}), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("h1", {
					className: "toolbar-title",
					children: ["Výpis ", statement.number]
				})] }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
					type: "button",
					className: "print-btn",
					disabled: hasErrors,
					title: hasErrors ? "Formulár obsahuje neplatné údaje" : "Zobraziť náhľad výpisu",
					onClick: () => {
						if (hasErrors) return;
						onPreview();
					},
					children: "Náhľad"
				})]
			}),
			/* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", {
				className: "panel",
				children: /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
					style: {
						display: "flex",
						justifyContent: "space-between",
						alignItems: "flex-start",
						flexWrap: "wrap",
						gap: "0.75rem"
					},
					children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [
						/* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
							className: "toolbar-kicker",
							children: "Majiteľ účtu / Klient"
						}),
						books.clients.length > 1 ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", {
							style: {
								marginTop: "0.25rem",
								marginBottom: "0.35rem"
							},
							children: /* @__PURE__ */ (0, import_jsx_runtime.jsx)("select", {
								value: client?.id || "",
								onChange: (e) => onStatement({ client_id: e.target.value }),
								"aria-label": "Výber bankového účtu",
								style: {
									padding: "0.4rem 0.65rem",
									fontSize: "0.95rem",
									fontWeight: 600,
									borderRadius: "0.5rem",
									border: "1px solid rgba(255, 255, 255, 0.2)",
									background: "rgba(0, 0, 0, 0.45)",
									color: "inherit",
									cursor: "pointer"
								},
								children: books.clients.map((c) => /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("option", {
									value: c.id,
									style: {
										background: "#111827",
										color: "#fff"
									},
									children: [
										c.name,
										" (",
										c.iban,
										")"
									]
								}, c.id))
							})
						}) : /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
							style: {
								fontWeight: 600,
								fontSize: "1.05rem"
							},
							children: client?.name
						}),
						/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("p", {
							className: "meta",
							style: { marginTop: "0.25rem" },
							children: [
								client?.type || "Podnikateľský účet",
								" ",
								client?.number,
								" · ",
								client?.iban,
								" · ",
								client?.switft,
								" · ",
								client?.currency || "EUR"
							]
						})
					] }), client?.address ? /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
						style: {
							textAlign: "right",
							fontSize: "0.85rem",
							opacity: .85
						},
						children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", { children: client.address }), client.email ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
							style: { opacity: .75 },
							children: client.email
						}) : null]
					}) : null]
				})
			}),
			/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
				className: "form-grid two",
				children: [
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
						className: "field",
						children: [
							"Číslo výpisu",
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
								value: numberText,
								"aria-invalid": Boolean(fieldError.number) || void 0,
								onChange: (event) => {
									const value = event.target.value;
									setNumberText(value);
									const trimmed = value.trim();
									if (!trimmed) {
										setFieldError((current) => ({
											...current,
											number: "Číslo výpisu je povinné."
										}));
										return;
									}
									if (!/^[a-zA-Z0-9\-_/]+$/.test(trimmed)) {
										setFieldError((current) => ({
											...current,
											number: "Číslo výpisu môže obsahovať len čísla, písmená, pomlčku alebo lomku."
										}));
										return;
									}
									setFieldError((current) => ({
										...current,
										number: ""
									}));
									onStatement({ number: trimmed });
								}
							}),
							fieldError.number ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
								className: "field-error",
								children: fieldError.number
							}) : null
						]
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
						className: "field",
						children: [
							"Dátum vystavenia",
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
								value: issuedText,
								inputMode: "numeric",
								"aria-invalid": Boolean(fieldError.issued) || void 0,
								onChange: (event) => {
									const value = event.target.value;
									setIssuedText(value);
									if (!isRealDate(value)) {
										setFieldError((current) => ({
											...current,
											issued: "Dátum musí byť skutočný deň vo formáte dd.mm.rrrr."
										}));
										return;
									}
									setFieldError((current) => ({
										...current,
										issued: ""
									}));
									onStatement({ issued_on: value });
								}
							}),
							fieldError.issued ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
								className: "field-error",
								children: fieldError.issued
							}) : null
						]
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
						className: "field",
						children: [
							"Posledný výpis",
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
								value: previousText,
								inputMode: "numeric",
								"aria-invalid": Boolean(fieldError.previous) || void 0,
								onChange: (event) => {
									const value = event.target.value;
									setPreviousText(value);
									if (value !== "" && !isRealDate(value)) {
										setFieldError((current) => ({
											...current,
											previous: "Dátum musí byť skutočný deň vo formáte dd.mm.rrrr."
										}));
										return;
									}
									setFieldError((current) => ({
										...current,
										previous: ""
									}));
									onStatement({ previous_on: value });
								}
							}),
							fieldError.previous ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
								className: "field-error",
								children: fieldError.previous
							}) : null
						]
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
						className: "field",
						children: [
							"Počiatočný zostatok",
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
								inputMode: "decimal",
								value: openingText,
								"aria-invalid": Boolean(fieldError.opening) || void 0,
								onChange: (event) => {
									const value = event.target.value;
									setOpeningText(value);
									const parsed = parseAmountInput(value, false);
									if (!parsed.ok) {
										setFieldError((current) => ({
											...current,
											opening: parsed.error
										}));
										return;
									}
									setFieldError((current) => ({
										...current,
										opening: ""
									}));
									onStatement({ opening: parsed.cents });
								}
							}),
							fieldError.opening ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
								className: "field-error",
								children: fieldError.opening
							}) : null
						]
					})
				]
			}),
			statement.movements.length === 0 ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", { children: "Tento výpis zatiaľ nemá žiadne pohyby." }) : /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
				className: "totals",
				children: [
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("span", { children: [
						"DB ",
						summary.dbN,
						" · ",
						summary.dbLabel
					] }),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("span", { children: [
						"CR ",
						summary.crN,
						" · ",
						summary.crLabel
					] }),
					/* @__PURE__ */ (0, import_jsx_runtime.jsx)("strong", { children: summary.closingLabel })
				]
			}),
			summary.error ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
				className: "banner",
				children: summary.error
			}) : null,
			/* @__PURE__ */ (0, import_jsx_runtime.jsx)("ul", {
				className: "moves",
				children: statement.movements.map((movement, index) => /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("li", {
					className: "move",
					children: [
						/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("strong", { children: labelKind(movement.kind) }),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("p", { children: [
								movement.date,
								" · ",
								movement.text || movement.symbol
							] }),
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
								className: "amount",
								children: formatMoney(movement.amount)
							})
						] }),
						/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
							className: "row-actions",
							children: [
								/* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
									type: "button",
									className: "text-btn",
									disabled: index === 0,
									onClick: () => move(statement.movements, index, -1, onStatement),
									children: "Hore"
								}),
								/* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
									type: "button",
									className: "text-btn",
									disabled: index === statement.movements.length - 1,
									onClick: () => move(statement.movements, index, 1, onStatement),
									children: "Dole"
								}),
								/* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
									type: "button",
									className: "text-btn",
									onClick: () => setPendingDelete(movement.id),
									children: "Zmazať"
								})
							]
						}),
						/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
							className: "field",
							children: [
								"Suma",
								/* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
									inputMode: "decimal",
									value: amountText[movement.id] ?? plainCents(movement.amount),
									"aria-invalid": Boolean(fieldError[movement.id]) || void 0,
									onChange: (event) => {
										const value = event.target.value;
										setAmountText((current) => ({
											...current,
											[movement.id]: value
										}));
										const parsed = parseAmountInput(value, false);
										if (!parsed.ok) {
											setFieldError((current) => ({
												...current,
												[movement.id]: parsed.error
											}));
											return;
										}
										setFieldError((current) => ({
											...current,
											[movement.id]: ""
										}));
										onMovement(movement.id, { amount: parsed.cents });
									}
								}),
								fieldError[movement.id] ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
									className: "field-error",
									children: fieldError[movement.id]
								}) : null
							]
						})
					]
				}, movement.id))
			}),
			pendingDelete ? /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
				className: "confirm",
				role: "dialog",
				"aria-modal": "true",
				"aria-labelledby": "delete-title",
				children: [
					/* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
						id: "delete-title",
						children: "Naozaj zmazať tento pohyb?"
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
						type: "button",
						className: "text-btn",
						onClick: () => setPendingDelete(null),
						children: "Zrušiť"
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
						type: "button",
						className: "print-btn",
						onClick: () => {
							const idToDelete = pendingDelete;
							onStatement({ movements: statement.movements.filter((item) => item.id !== idToDelete) });
							setFieldError((current) => {
								const next = { ...current };
								if (idToDelete) delete next[idToDelete];
								return next;
							});
							setAmountText((current) => {
								const next = { ...current };
								if (idToDelete) delete next[idToDelete];
								return next;
							});
							setPendingDelete(null);
						},
						children: "Zmazať"
					})
				]
			}) : null,
			/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("form", {
				className: "panel",
				onSubmit: (event) => {
					event.preventDefault();
					addMovement();
				},
				children: [
					/* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
						className: "toolbar-kicker",
						children: "Nový pohyb"
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
						className: "form-grid two",
						children: [
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
								className: "field",
								children: ["Typ", /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("select", {
									value: draft.kind,
									onChange: (event) => setDraft({
										...draft,
										kind: event.target.value,
										party_id: ""
									}),
									children: [
										/* @__PURE__ */ (0, import_jsx_runtime.jsx)("option", {
											value: "in",
											children: "Prijatá platba"
										}),
										/* @__PURE__ */ (0, import_jsx_runtime.jsx)("option", {
											value: "out",
											children: "Odoslaná platba"
										}),
										/* @__PURE__ */ (0, import_jsx_runtime.jsx)("option", {
											value: "tax",
											children: "Transakčná daň"
										}),
										/* @__PURE__ */ (0, import_jsx_runtime.jsx)("option", {
											value: "fee",
											children: "Poplatok"
										})
									]
								})]
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
								className: "field",
								children: [
									"Dátum",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
										value: draft.date,
										inputMode: "numeric",
										"aria-invalid": Boolean(draftError.date) || void 0,
										onChange: (event) => setDraft({
											...draft,
											date: event.target.value
										})
									}),
									draftError.date ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "field-error",
										children: draftError.date
									}) : null
								]
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
								className: "field",
								children: [
									"Suma",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
										inputMode: "decimal",
										value: draft.amount,
										"aria-invalid": Boolean(draftError.amount) || void 0,
										onChange: (event) => setDraft({
											...draft,
											amount: event.target.value
										})
									}),
									draftError.amount ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "field-error",
										children: draftError.amount
									}) : null
								]
							}),
							draft.kind === "in" || draft.kind === "out" ? /* @__PURE__ */ (0, import_jsx_runtime.jsxs)(import_jsx_runtime.Fragment, { children: [
								/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
									className: "field",
									children: [
										"Referencia",
										/* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
											value: draft.text,
											"aria-invalid": Boolean(draftError.text) || void 0,
											"aria-describedby": "draft-text-count",
											onChange: (event) => setDraft({
												...draft,
												text: event.target.value
											})
										}),
										/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("span", {
											id: "draft-text-count",
											className: "meta",
											children: [
												textUsed,
												"/",
												textLimit
											]
										}),
										draftError.text ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
											className: "field-error",
											children: draftError.text
										}) : null
									]
								}),
								/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
									className: "field",
									children: ["Symbol", /* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
										value: draft.symbol,
										onChange: (event) => setDraft({
											...draft,
											symbol: event.target.value
										})
									})]
								}),
								/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
									className: "field",
									children: ["Valuta", /* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
										value: draft.valuta,
										onChange: (event) => setDraft({
											...draft,
											valuta: event.target.value
										})
									})]
								}),
								/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
									className: "field",
									children: [draft.kind === "in" ? "Platiteľ" : "Príjemca", /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("select", {
										value: draft.party_id,
										onChange: (event) => setDraft({
											...draft,
											party_id: event.target.value
										}),
										children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("option", {
											value: "",
											children: "Nová protistrana"
										}), parties.map((party) => /* @__PURE__ */ (0, import_jsx_runtime.jsx)("option", {
											value: party.id,
											children: party.name
										}, party.id))]
									})]
								}),
								draft.party_id ? null : /* @__PURE__ */ (0, import_jsx_runtime.jsxs)(import_jsx_runtime.Fragment, { children: [
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
										className: "field",
										children: [
											"Názov",
											/* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
												value: draft.newName,
												"aria-invalid": Boolean(draftError.name) || void 0,
												onChange: (event) => setDraft({
													...draft,
													newName: event.target.value
												})
											}),
											draftError.name ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
												className: "field-error",
												children: draftError.name
											}) : null
										]
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
										className: "field",
										children: [
											"IBAN",
											/* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
												value: draft.newIban,
												"aria-invalid": Boolean(draftError.iban) || void 0,
												onChange: (event) => setDraft({
													...draft,
													newIban: event.target.value
												})
											}),
											draftError.iban ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
												className: "field-error",
												children: draftError.iban
											}) : null
										]
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
										className: "field",
										children: ["SWIFT", /* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
											value: draft.newSwift,
											onChange: (event) => setDraft({
												...draft,
												newSwift: event.target.value
											})
										})]
									})
								] })
							] }) : /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
								className: "field",
								children: [
									"Text",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
										value: draft.text,
										"aria-invalid": Boolean(draftError.text) || void 0,
										"aria-describedby": "draft-text-count",
										onChange: (event) => setDraft({
											...draft,
											text: event.target.value
										})
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("span", {
										id: "draft-text-count",
										className: "meta",
										children: [
											textUsed,
											"/",
											textLimit
										]
									}),
									draftError.text ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "field-error",
										children: draftError.text
									}) : null
								]
							}),
							draft.kind === "fee" ? /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
								className: "field",
								children: ["Balík", /* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
									value: draft.fee_note,
									onChange: (event) => setDraft({
										...draft,
										fee_note: event.target.value
									})
								})]
							}) : null
						]
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
						type: "submit",
						className: "print-btn",
						children: "Pridať pohyb"
					})
				]
			})
		]
	});
}
function labelKind(kind) {
	if (kind === "in") return "Prijatá";
	if (kind === "out") return "Odoslaná";
	if (kind === "tax") return "Daň";
	return "Poplatok";
}
function move(movements, index, delta, onStatement) {
	const next = movements.slice();
	const target = index + delta;
	const [item] = next.splice(index, 1);
	next.splice(target, 0, item);
	onStatement({ movements: next });
}
var SplitComponent = VypisApp;
export { SplitComponent as component };
