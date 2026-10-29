import { i as __toESM } from "../_runtime.mjs";
import { K as require_react, b as require_jsx_runtime } from "../_libs/@tanstack/react-router+[...].mjs";
import { a as FileText, i as Landmark, n as Rows3, o as Eye, r as Printer, s as CircleHelp } from "../_libs/lucide-react.mjs";
//#region node_modules/.nitro/vite/services/ssr/assets/routes-CDDRc6Bg.js
var import_react = /* @__PURE__ */ __toESM(require_react());
var import_jsx_runtime = require_jsx_runtime();
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
		previous_on: "31.06.2026",
		opening: 211815.25,
		movements: [
			{
				id: "m1",
				kind: "tax",
				date: "01.07.2026",
				text: "Transakčná daň 30.06.2026",
				symbol: "",
				amount: .08,
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
				amount: 160,
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
				amount: 11070,
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
				amount: 40,
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
				amount: 13530,
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
				amount: 40,
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
				amount: 11685.6,
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
				amount: 40,
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
				amount: 19,
				valuta: "",
				rate: "",
				party_id: "",
				fee_note: "Tatra Business Premium"
			}
		]
	}]
};
function cents(value) {
	return Math.round(value * 100);
}
function formatMoney(value) {
	const sign = value < 0 ? "-" : "";
	const abs = Math.abs(cents(value));
	const whole = Math.floor(abs / 100);
	const frac = String(abs % 100).padStart(2, "0");
	return `${sign}${String(whole).replace(/\B(?=(\d{3})+(?!\d))/g, ",")}.${frac}`;
}
function parseAmount(raw) {
	const text = raw.trim().replace(/\s/g, "");
	if (!text) return NaN;
	if (text.includes(",") && text.includes(".")) return Number(text.replace(/,/g, ""));
	if (text.includes(",")) return Number(text.replace(",", "."));
	return Number(text);
}
function formatIban(raw) {
	return raw.replace(/\s+/g, "").replace(/(.{4})/g, "$1 ").trim();
}
function ledger(opening, movements) {
	let db = 0;
	let cr = 0;
	let dbN = 0;
	let crN = 0;
	for (const movement of movements) {
		const amount = cents(movement.amount);
		if (!Number.isFinite(amount)) return {
			ok: false,
			db: 0,
			cr: 0,
			dbN: 0,
			crN: 0,
			closing: 0
		};
		if (movement.kind === "in") {
			cr += amount;
			crN += 1;
		} else {
			db += amount;
			dbN += 1;
		}
	}
	const open = cents(opening);
	if (!Number.isFinite(open)) return {
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
		closing: open - db + cr
	};
}
function moneyFromCents(value) {
	return formatMoney(value / 100);
}
var LINES = 84;
var FOOTER_AT = 78;
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
		[86, page]
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
	if (movement.kind === "in") return [
		[
			[0, date],
			[13, `Platba ${movement.text}`],
			money(amount, "credit")
		],
		[[13, "Prijatá  platba:"], [45, movement.symbol]],
		[
			[13, `Suma:${amount}EUR`],
			[45, `Kurz:${rate}`],
			[71, `Valuta:${movement.valuta}`]
		],
		[[13, `Banka platiteľa:${party?.swift ?? ""}`]],
		[[13, "Platiteľ:"], [29, party?.iban ?? ""]],
		[[29, party?.name ?? ""]]
	];
	return [
		[
			[0, date],
			[13, `Platba ${movement.text}`],
			money(amount, "debit")
		],
		[[13, "Odoslaná platba:"], [45, movement.symbol]],
		[
			[13, `Suma:${amount}EUR`],
			[45, `Kurz:${rate}`],
			[71, `Valuta:${movement.valuta}`]
		],
		[[13, `Banka príjemcu: ${party?.swift ?? ""}`]],
		[[13, "Príjemca:"], [29, party?.iban ?? ""]],
		[[29, party?.name ?? ""]]
	];
}
function renderStatement(books, statementId) {
	const statement = books.statements.find((item) => item.id === statementId);
	const client = books.clients.find((item) => item.id === statement?.client_id);
	const bank = books.banks.find((item) => item.id === client?.bank_id);
	const empty = sheet();
	const fail = (error) => ({
		pageOne: empty,
		pageTwo: empty,
		error,
		dbLabel: "0.00",
		crLabel: "0.00",
		dbN: 0,
		crN: 0,
		closingLabel: "0.00"
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
	const subheader = row([
		[0, `${client.type} ${client.number}`],
		[33, `Majiteľ ${client.name}`],
		[77, "Dátum"],
		[83, issued]
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
		money(moneyFromCents(sums.db), "total")
	]);
	lines[79] = row([
		[45, "CR"],
		[59, String(sums.crN)],
		money(moneyFromCents(sums.cr), "total")
	]);
	lines[81] = row([[13, "Zostatok na účte ku dňu vystavenia výpisu:"], money(moneyFromCents(sums.closing), "total")]);
	lines[82] = rule();
	lines[83] = footer(statement.number, "1", currency);
	const pageTwo = sheet();
	pageTwo[0] = subheader;
	pageTwo[1] = rule();
	pageTwo[3] = row([
		[6, "Týmto potvrdzujeme, že"],
		[30, "Váš"],
		[35, "vklad je chránený v zmysle Zákona o ochrane"]
	]);
	pageTwo[4] = row([[6, "vkladov. Bližšie informácie sú uvedené v Informačnom formulári pre vklada-"]]);
	pageTwo[5] = row([[6, "teľa, ktorý od nás dostanete raz ročne, na našej internetovej stránke"]]);
	pageTwo[6] = row([[6, "www.tatrabanka.sk, v našich obchodných priestoroch a tiež na internetovej"]]);
	pageTwo[7] = row([[6, "stránke Fondu ochrany vkladov www.fovsr.sk."]]);
	pageTwo[82] = rule();
	pageTwo[83] = footer(statement.number, "2", currency);
	let error = null;
	if (overflow || cursor > FOOTER_AT) error = "Pohyby sa nezmestia na jednu stranu. Tlač je zastavená.";
	if (statement.movements.some((movement) => (movement.kind === "in" || movement.kind === "out") && !partyOf(books, movement))) error = "Pohyb nemá protistranu z customers alebo contractors.";
	return {
		pageOne: lines,
		pageTwo,
		error,
		dbLabel: moneyFromCents(sums.db),
		crLabel: moneyFromCents(sums.cr),
		dbN: sums.dbN,
		crN: sums.crN,
		closingLabel: moneyFromCents(sums.closing)
	};
}
function Sheet({ lines, logo }) {
	return /* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", {
		className: "fit",
		children: /* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", {
			className: "fit-spacer",
			children: /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("article", {
				className: "sheet",
				children: [logo ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("img", {
					className: "sheet-logo",
					src: "/tb-logo.png",
					alt: ""
				}) : null, /* @__PURE__ */ (0, import_jsx_runtime.jsx)("pre", {
					className: "sheet-pre",
					children: lines.join("\n")
				})]
			})
		})
	});
}
function StatementDocument({ title, pageOne, pageTwo, error, onBack }) {
	return /* @__PURE__ */ (0, import_jsx_runtime.jsxs)(import_jsx_runtime.Fragment, { children: [
		/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("header", {
			className: "toolbar",
			children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
				type: "button",
				className: "text-btn",
				onClick: onBack,
				children: "Zoznam"
			}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("h1", {
				className: "toolbar-title",
				children: title
			})] }), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("button", {
				type: "button",
				className: "print-btn",
				disabled: Boolean(error),
				onClick: () => window.print(),
				children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Printer, { "aria-hidden": "true" }), "Tlačiť"]
			})]
		}),
		error ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
			className: "banner",
			children: error
		}) : null,
		/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Sheet, {
			lines: pageOne,
			logo: true
		}),
		/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Sheet, { lines: pageTwo })
	] });
}
function WelcomeScreen({ books, statementId, rendered, onStatements, onEdit, onPreview }) {
	const statement = books.statements.find((item) => item.id === statementId) ?? books.statements[0];
	const client = books.clients.find((item) => item.id === statement.client_id);
	const bank = books.banks.find((item) => item.id === client?.bank_id);
	const [help, setHelp] = (0, import_react.useState)(false);
	const street = bank?.addess.split(/\r?\n/)[0] ?? "";
	return /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("section", {
		className: "home",
		children: [
			/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
				className: "home-art",
				"aria-hidden": "true",
				children: [
					/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { className: "home-shard home-shard-a" }),
					/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { className: "home-shard home-shard-b" }),
					/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { className: "home-shard home-shard-c" })
				]
			}),
			/* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
				type: "button",
				className: "home-help",
				onClick: () => setHelp(true),
				"aria-label": "Nápoveda",
				children: /* @__PURE__ */ (0, import_jsx_runtime.jsx)(CircleHelp, { "aria-hidden": "true" })
			}),
			/* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", {
				className: "home-mark",
				"aria-hidden": "true",
				children: /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {})
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
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("p", {
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
	const [view, setView] = (0, import_react.useState)("home");
	const [activeId, setActiveId] = (0, import_react.useState)(SEED.statements[0].id);
	(0, import_react.useEffect)(() => {
		try {
			const raw = localStorage.getItem(KEY);
			if (raw) {
				const parsed = JSON.parse(raw);
				if (parsed.clients?.length && parsed.statements?.length) setBooks(parsed);
			}
		} catch {}
		setReady(true);
	}, []);
	(0, import_react.useEffect)(() => {
		if (!ready) return;
		localStorage.setItem(KEY, JSON.stringify(books));
	}, [books, ready]);
	const statement = books.statements.find((item) => item.id === activeId) ?? books.statements[0];
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
			client_id: books.clients[0].id,
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
		const id = uid(kind === "in" ? "cu" : "co");
		const row = {
			...party,
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
					] }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
						type: "button",
						className: "print-btn",
						onClick: addStatement,
						children: "Nový výpis"
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
								/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("p", {
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
				pageOne: rendered.pageOne,
				pageTwo: rendered.pageTwo,
				error: rendered.error,
				onBack: () => setView("list")
			}) : null
		]
	});
}
function Editor({ books, statementId, onBack, onPreview, onStatement, onMovement, onAddParty, summary }) {
	const statement = books.statements.find((item) => item.id === statementId);
	const client = books.clients.find((item) => item.id === statement.client_id);
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
	function addMovement() {
		const amount = parseAmount(draft.amount);
		if (!Number.isFinite(amount) || amount < 0) return;
		let partyId = draft.party_id;
		if ((draft.kind === "in" || draft.kind === "out") && !partyId && draft.newName.trim()) partyId = onAddParty(draft.kind, {
			name: draft.newName.trim(),
			iban: draft.newIban.replace(/\s+/g, ""),
			swift: draft.newSwift.trim()
		});
		const movement = {
			id: uid("m"),
			kind: draft.kind,
			date: draft.date,
			text: draft.text || (draft.kind === "tax" ? `Transakčná daň ${draft.date}` : draft.kind === "fee" ? "Poplatok za balík" : ""),
			symbol: draft.symbol,
			amount,
			valuta: draft.valuta,
			rate: draft.rate || "1.00000000",
			party_id: draft.kind === "in" || draft.kind === "out" ? partyId : "",
			fee_note: draft.kind === "fee" ? draft.fee_note : ""
		};
		onStatement({ movements: [...statement.movements, movement] });
		setDraft((current) => ({
			...current,
			text: "",
			symbol: "",
			amount: "",
			party_id: partyId
		}));
	}
	const parties = draft.kind === "in" ? books.customers.filter((item) => item.client_id === statement.client_id) : books.contractors.filter((item) => item.client_id === statement.client_id);
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
					onClick: onPreview,
					children: "Náhľad"
				})]
			}),
			/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
				className: "panel",
				children: [
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("p", {
						className: "toolbar-kicker",
						children: ["Účet ", client?.client]
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", { children: client?.name }),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("p", {
						className: "meta",
						children: [
							client?.type,
							" ",
							client?.number,
							" · ",
							client?.switft,
							" · ",
							client?.currency
						]
					})
				]
			}),
			/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
				className: "form-grid two",
				children: [
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
						className: "field",
						children: ["Číslo výpisu", /* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
							value: statement.number,
							onChange: (event) => onStatement({ number: event.target.value })
						})]
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
						className: "field",
						children: ["Dátum vystavenia", /* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
							value: statement.issued_on,
							onChange: (event) => onStatement({ issued_on: event.target.value })
						})]
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
						className: "field",
						children: ["Posledný výpis", /* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
							value: statement.previous_on,
							onChange: (event) => onStatement({ previous_on: event.target.value })
						})]
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
						className: "field",
						children: ["Počiatočný zostatok", /* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
							inputMode: "decimal",
							value: String(statement.opening),
							onChange: (event) => {
								const value = parseAmount(event.target.value);
								if (Number.isFinite(value)) onStatement({ opening: value });
							}
						})]
					})
				]
			}),
			/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
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
									onClick: () => onStatement({ movements: statement.movements.filter((item) => item.id !== movement.id) }),
									children: "Zmazať"
								})
							]
						}),
						/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
							className: "field",
							children: ["Suma", /* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
								inputMode: "decimal",
								value: String(movement.amount),
								onChange: (event) => {
									const value = parseAmount(event.target.value);
									if (Number.isFinite(value)) onMovement(movement.id, { amount: value });
								}
							})]
						})
					]
				}, movement.id))
			}),
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
								children: ["Dátum", /* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
									value: draft.date,
									onChange: (event) => setDraft({
										...draft,
										date: event.target.value
									})
								})]
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
								className: "field",
								children: ["Suma", /* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
									inputMode: "decimal",
									value: draft.amount,
									onChange: (event) => setDraft({
										...draft,
										amount: event.target.value
									})
								})]
							}),
							draft.kind === "in" || draft.kind === "out" ? /* @__PURE__ */ (0, import_jsx_runtime.jsxs)(import_jsx_runtime.Fragment, { children: [
								/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
									className: "field",
									children: ["Referencia", /* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
										value: draft.text,
										onChange: (event) => setDraft({
											...draft,
											text: event.target.value
										})
									})]
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
										children: ["Názov", /* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
											value: draft.newName,
											onChange: (event) => setDraft({
												...draft,
												newName: event.target.value
											})
										})]
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("label", {
										className: "field",
										children: ["IBAN", /* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
											value: draft.newIban,
											onChange: (event) => setDraft({
												...draft,
												newIban: event.target.value
											})
										})]
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
								children: ["Text", /* @__PURE__ */ (0, import_jsx_runtime.jsx)("input", {
									value: draft.text,
									onChange: (event) => setDraft({
										...draft,
										text: event.target.value
									})
								})]
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
//#endregion
export { SplitComponent as component };
