import { useEffect, useRef, useState } from "react";
import {
  DAMAGE_NOTICE,
  SEED,
  type Books,
  type Movement,
  type MovementKind,
  type Party,
  formatMoney,
  isBooks,
  isRealDate,
  isSkIban,
  parseAmountInput,
  plainCents,
  readStoredBooks,
  statementFileStem,
} from "@/lib/books";
import { draftExcess, renderStatement } from "@/lib/statement";
import { StatementDocument } from "@/components/statement-document";
import { WelcomeScreen } from "@/components/welcome-screen";
import { VorkeiSyncModal } from "@/components/vorkei-sync-modal";
import { IcoAtlasModal } from "@/components/ico-atlas-modal";
import { lookupCompanyByIco, deriveSwiftFromSkIban, type VerificationStatus } from "@/lib/company-lookup";
import { Cloud, Download, Upload, RotateCcw, Search, ShieldAlert, CheckCircle2, AlertTriangle, Loader2 } from "lucide-react";

const KEY = "vypis-books-v1";

function uid(prefix: string): string {
  return `${prefix}-${crypto.randomUUID()}`;
}

function today(): string {
  const now = new Date();
  const d = String(now.getDate()).padStart(2, "0");
  const m = String(now.getMonth() + 1).padStart(2, "0");
  return `${d}.${m}.${now.getFullYear()}`;
}

export function VypisApp() {
  const [books, setBooks] = useState<Books>(SEED);
  const [ready, setReady] = useState(false);
  const [notice, setNotice] = useState<string | null>(null);
  const [view, setView] = useState<"home" | "list" | "edit" | "preview">("home");
  const [activeId, setActiveId] = useState(SEED.statements[0].id);
  const [syncOpen, setSyncOpen] = useState(false);
  const [icoAtlasOpen, setIcoAtlasOpen] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  function downloadJson(data: unknown, filename: string) {
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: "application/json" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
  }

  function downloadLatestBackup() {
    let latestKey: string | null = null;
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

  function handleRestoreFile(event: React.ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (e) => {
      try {
        const parsed = JSON.parse(e.target?.result as string);
        if (isBooks(parsed)) {
          setBooks(parsed);
          setNotice("Dáta boli úspešne obnovené zo záložného súboru.");
        } else {
          alert("Súbor neobsahuje platnú štruktúru databázy výpisov.");
        }
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

  useEffect(() => {
    let raw: string | null = null;
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

  useEffect(() => {
    if (!ready) return;
    localStorage.setItem(KEY, JSON.stringify(books));
  }, [books, ready]);

  const statement = books.statements.find((item) => item.id === activeId) ?? books.statements[0] ?? SEED.statements[0];
  const rendered = renderStatement(books, statement.id);

  function patchStatement(partial: Partial<typeof statement>) {
    setBooks((current) => ({
      ...current,
      statements: current.statements.map((item) =>
        item.id === statement.id ? { ...item, ...partial } : item,
      ),
    }));
  }

  function patchMovement(id: string, partial: Partial<Movement>) {
    patchStatement({
      movements: statement.movements.map((item) => (item.id === id ? { ...item, ...partial } : item)),
    });
  }

  function addStatement() {
    const id = uid("s");
    const next: Books["statements"][number] = {
      id,
      client_id: books.clients[0]?.id || "",
      number: String(books.statements.length + 1),
      issued_on: today(),
      previous_on: "",
      opening: 0,
      movements: [],
    };
    setBooks((current) => ({ ...current, statements: [...current.statements, next] }));
    setActiveId(id);
    setView("edit");
  }

  function addParty(kind: "in" | "out", party: Omit<Party, "id" | "client_id">): string {
    const list = kind === "in" ? books.customers : books.contractors;
    const cleanIban = party.iban.replace(/\s+/g, "").toUpperCase();
    const cleanName = party.name.trim().toLowerCase();
    const existing = list.find(
      (p) =>
        p.client_id === statement.client_id &&
        p.iban.replace(/\s+/g, "").toUpperCase() === cleanIban &&
        p.name.trim().toLowerCase() === cleanName,
    );
    if (existing) {
      return existing.id;
    }
    const id = uid(kind === "in" ? "cu" : "co");
    const row: Party = {
      ...party,
      name: party.name.trim(),
      iban: cleanIban,
      swift: party.swift.trim(),
      id,
      client_id: statement.client_id,
    };
    setBooks((current) => ({
      ...current,
      customers: kind === "in" ? [...current.customers, row] : current.customers,
      contractors: kind === "out" ? [...current.contractors, row] : current.contractors,
    }));
    return id;
  }

  return (
    <div className={view === "home" ? "desk desk-home" : "desk"}>
      {notice ? (
        <div className="banner" style={{ display: "flex", justifyContent: "space-between", alignItems: "center", flexWrap: "wrap", gap: "0.5rem" }}>
          <span>{notice}</span>
          <div style={{ display: "flex", gap: "0.5rem", alignItems: "center" }}>
            <button
              type="button"
              className="text-btn"
              style={{ fontSize: "0.8rem", padding: "0.2rem 0.6rem", background: "rgba(255,255,255,0.15)", borderRadius: "0.35rem", display: "inline-flex", alignItems: "center", gap: "0.25rem" }}
              onClick={downloadLatestBackup}
            >
              <Download style={{ width: "0.85rem", height: "0.85rem" }} />
              Stiahnuť zálohu
            </button>
            <button
              type="button"
              className="text-btn"
              style={{ fontSize: "0.85rem", padding: "0.2rem 0.4rem" }}
              title="Zavrieť upozornenie"
              onClick={() => setNotice(null)}
            >
              ✕
            </button>
          </div>
        </div>
      ) : null}
      {view === "home" ? (
        <WelcomeScreen
          books={books}
          statementId={statement.id}
          rendered={rendered}
          onStatements={() => setView("list")}
          onEdit={() => setView("edit")}
          onPreview={() => setView("preview")}
        />
      ) : null}

      {view === "list" ? (
        <section className="screen-ui">
          <header className="toolbar">
            <div>
              <p className="toolbar-kicker">Účty a výpisy</p>
              <h1 className="toolbar-title">Výpis</h1>
              <button type="button" className="text-btn" onClick={() => setView("home")}>
                Úvod
              </button>
            </div>
            <div className="row-actions" style={{ flexWrap: "wrap", gap: "0.4rem" }}>
              <input
                ref={fileInputRef}
                type="file"
                accept=".json"
                style={{ display: "none" }}
                onChange={handleRestoreFile}
              />
              <button
                type="button"
                className="text-btn"
                title="Stiahnuť kompletnú databázu v JSON formáte"
                style={{ display: "inline-flex", alignItems: "center", gap: "0.35rem" }}
                onClick={handleExportDatabase}
              >
                <Download aria-hidden="true" style={{ width: "0.95rem", height: "0.95rem" }} />
                Záloha JSON
              </button>
              <button
                type="button"
                className="text-btn"
                title="Nahrať databázu zo záložného súboru JSON"
                style={{ display: "inline-flex", alignItems: "center", gap: "0.35rem" }}
                onClick={() => fileInputRef.current?.click()}
              >
                <Upload aria-hidden="true" style={{ width: "0.95rem", height: "0.95rem" }} />
                Obnoviť
              </button>
              <button
                type="button"
                className="text-btn"
                title="Obnoviť predvolené dáta (SEED)"
                style={{ display: "inline-flex", alignItems: "center", gap: "0.35rem", color: "#f87171" }}
                onClick={handleResetSeed}
              >
                <RotateCcw aria-hidden="true" style={{ width: "0.95rem", height: "0.95rem" }} />
                Reset
              </button>
              <button
                type="button"
                className="text-btn"
                title="Rýchle overenie firmy v registri SR a Risk Radar"
                style={{ display: "inline-flex", alignItems: "center", gap: "0.35rem", color: "#60a5fa" }}
                onClick={() => setIcoAtlasOpen(true)}
              >
                <Search aria-hidden="true" style={{ width: "0.95rem", height: "0.95rem" }} />
                ICO-ATLAS Radar
              </button>
              <button
                type="button"
                className="text-btn"
                style={{ display: "inline-flex", alignItems: "center", gap: "0.35rem" }}
                onClick={() => setSyncOpen(true)}
              >
                <Cloud aria-hidden="true" style={{ width: "1rem", height: "1rem" }} />
                vorkei.com Bridge
              </button>
              <button type="button" className="print-btn" onClick={addStatement}>
                Nový výpis
              </button>
            </div>
          </header>
          <ul className="cards">
            {books.statements.map((item) => {
              const viewModel = renderStatement(books, item.id);
              const client = books.clients.find((row) => row.id === item.client_id);
              return (
                <li key={item.id} className="card">
                  <div>
                    <strong>Výpis {item.number}</strong>
                    <p>
                      {client?.name} · {item.issued_on}
                    </p>
                    {item.movements.length === 0 ? (
                      <p>Tento výpis zatiaľ nemá žiadne pohyby.</p>
                    ) : (
                      <p className="amount">{viewModel.closingLabel} {client?.currency}</p>
                    )}
                  </div>
                  <div className="row-actions">
                    <button type="button" className="text-btn" onClick={() => { setActiveId(item.id); setView("edit"); }}>
                      Upraviť
                    </button>
                    <button type="button" className="print-btn" onClick={() => { setActiveId(item.id); setView("preview"); }}>
                      Náhľad
                    </button>
                  </div>
                </li>
              );
            })}
          </ul>
        </section>
      ) : null}

      {view === "edit" ? (
        <Editor
          books={books}
          statementId={statement.id}
          onBack={() => setView("list")}
          onPreview={() => setView("preview")}
          onStatement={patchStatement}
          onMovement={patchMovement}
          onAddParty={addParty}
          summary={rendered}
        />
      ) : null}

      {view === "preview" ? (
        <StatementDocument
          title={`Výpis číslo ${statement.number}`}
          fileStem={statementFileStem(statement.number)}
          pages={rendered.pages}
          pageOne={rendered.pageOne}
          pageTwo={rendered.pageTwo}
          error={rendered.error}
          onBack={() => setView("list")}
        />
      ) : null}

      <VorkeiSyncModal
        isOpen={syncOpen}
        onClose={() => setSyncOpen(false)}
        books={books}
        onUpdateBooks={setBooks}
      />

      <IcoAtlasModal
        isOpen={icoAtlasOpen}
        onClose={() => setIcoAtlasOpen(false)}
      />
    </div>
  );
}

function Editor({
  books,
  statementId,
  onBack,
  onPreview,
  onStatement,
  onMovement,
  onAddParty,
  summary,
}: {
  books: Books;
  statementId: string;
  onBack: () => void;
  onPreview: () => void;
  onStatement: (partial: Partial<Books["statements"][number]>) => void;
  onMovement: (id: string, partial: Partial<Movement>) => void;
  onAddParty: (kind: "in" | "out", party: Omit<Party, "id" | "client_id">) => string;
  summary: ReturnType<typeof renderStatement>;
}) {
  const statement = books.statements.find((item) => item.id === statementId)!;
  const client = books.clients.find((item) => item.id === statement.client_id) ?? books.clients[0];
  const [draft, setDraft] = useState({
    kind: "in" as MovementKind,
    date: statement.issued_on,
    text: "",
    symbol: "",
    amount: "",
    valuta: statement.issued_on,
    rate: "1.00000000",
    party_id: "",
    fee_note: "Tatra Business Premium",
    newIco: "",
    newName: "",
    newIban: "",
    newSwift: "",
    newAddress: "",
    newStatus: "",
    newActive: false,
    newLegalForm: "",
    riskWarning: "",
    newVerified: false,
    newVerifiedAt: undefined as string | undefined,
    newVerificationSource: undefined as string | undefined,
    newVerificationStatus: "unverified" as VerificationStatus | "unverified",
    isNameAutofilled: false,
    isAddressAutofilled: false,
  });
  const [icoLoading, setIcoLoading] = useState(false);
  const [icoStatus, setIcoStatus] = useState<{
    type: "success" | "warning" | "error";
    text: string;
    details?: string;
  } | null>(null);
  const lookupSeqRef = useRef(0);
  const [numberText, setNumberText] = useState(statement.number);
  const [issuedText, setIssuedText] = useState(statement.issued_on);
  const [previousText, setPreviousText] = useState(statement.previous_on);
  const [openingText, setOpeningText] = useState(plainCents(statement.opening));
  const [fieldError, setFieldError] = useState<Record<string, string>>({});
  const [draftError, setDraftError] = useState<Record<string, string>>({});
  const [amountText, setAmountText] = useState<Record<string, string>>({});
  const [pendingDelete, setPendingDelete] = useState<string | null>(null);

  useEffect(() => {
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

  async function handleIcoLookup(targetIco?: string) {
    const icoToSearch = (targetIco ?? draft.newIco).replace(/\s+/g, "");
    if (!icoToSearch || icoToSearch.length < 6) {
      setIcoStatus({ type: "warning", text: "Zadajte 6 až 8-miestne IČO." });
      return;
    }
    const thisSeq = ++lookupSeqRef.current;
    setIcoLoading(true);
    setIcoStatus(null);
    try {
      const res = await lookupCompanyByIco({ data: icoToSearch });
      if (thisSeq !== lookupSeqRef.current) {
        // Discard stale out-of-order response
        return;
      }
      if (!res.found || !res.name) {
        setDraft((current) => ({
          ...current,
          newStatus: "",
          newActive: false,
          newLegalForm: "",
          riskWarning: "",
          newVerified: false,
          newVerifiedAt: undefined,
          newVerificationSource: undefined,
          newVerificationStatus: "unverified",
          newName: current.isNameAutofilled ? "" : current.newName,
          newAddress: current.isAddressAutofilled ? "" : current.newAddress,
          isNameAutofilled: false,
          isAddressAutofilled: false,
        }));
        setIcoStatus({
          type: "warning",
          text: res.error || "Subjekt s týmto IČO nebol nájdený v registri SR.",
          details: "Môžete pokračovať s manuálnym vyplnením.",
        });
        return;
      }

      setDraft((current) => ({
        ...current,
        newIco: res.ico,
        newName: res.name || current.newName,
        newAddress: res.formatted_address || current.newAddress,
        newStatus: res.status || "",
        newActive: res.active,
        newLegalForm: res.legal_form || "",
        riskWarning: res.risk_reason || "",
        newVerified: res.verified,
        newVerifiedAt: res.verified_at,
        newVerificationSource: res.source,
        newVerificationStatus: res.verification_status,
        isNameAutofilled: Boolean(res.name),
        isAddressAutofilled: Boolean(res.formatted_address),
      }));

      setDraftError((current) => {
        const next = { ...current };
        delete next.name;
        return next;
      });

      if (res.is_risky) {
        setIcoStatus({
          type: "error",
          text: "🛡️ RISK RADAR: RIZIKOVÝ SUBJEKT!",
          details: res.risk_reason,
        });
      } else if (res.verification_status === "verified_active") {
        setIcoStatus({
          type: "success",
          text: `✅ Overený subjekt: ${res.name}`,
          details: `${res.legal_form || "Firma"} · ${res.address.city || "SR"} · Aktívny v registri SR`,
        });
      } else {
        setIcoStatus({
          type: "warning",
          text: `⚠️ Subjekt nájdený: ${res.name}`,
          details: `Stav v registri: ${res.status || "Nezistený"} (vyžaduje overenie)`,
        });
      }
    } catch (err) {
      if (thisSeq !== lookupSeqRef.current) return;
      setDraft((current) => ({
        ...current,
        newVerified: false,
        newVerificationStatus: "unverified",
      }));
      setIcoStatus({
        type: "error",
        text: "Nepodarilo sa overiť IČO v registri.",
        details: (err as Error).message,
      });
    } finally {
      if (thisSeq === lookupSeqRef.current) {
        setIcoLoading(false);
      }
    }
  }

  function addMovement() {
    const nextError: Record<string, string> = {};
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
    if (amount.ok && draftExcess(draft.kind, draft.text, draft.fee_note, amount.cents) > textOver) {
      const excess = draftExcess(draft.kind, draft.text, draft.fee_note, amount.cents);
      nextError.text = `Text je o ${excess} znakov dlhší než je povolené.`;
    }
    if (Object.keys(nextError).length > 0) {
      setDraftError(nextError);
      return;
    }
    if (!amount.ok) return;
    let partyId = draft.party_id;
    if (needsParty && !partyId) {
      const vStatus = draft.newVerificationStatus === "unconfirmed" ? "unverified" : draft.newVerificationStatus;
      partyId = onAddParty(draft.kind as "in" | "out", {
        name: draft.newName.trim(),
        iban: draft.newIban.replace(/\s+/g, "").toUpperCase(),
        swift: draft.newSwift.trim(),
        ico: draft.newIco.trim(),
        address: draft.newAddress.trim(),
        status: draft.newStatus.trim(),
        active: draft.newActive,
        legal_form: draft.newLegalForm.trim(),
        risk_warning: draft.riskWarning.trim(),
        verified: draft.newVerified,
        verified_at: draft.newVerifiedAt,
        verification_source: draft.newVerificationSource,
        verification_status: vStatus,
      });
    }
    const movement: Movement = {
      id: uid("m"),
      kind: draft.kind,
      date: draft.date,
      text: draft.text.trim(),
      symbol: draft.symbol,
      amount: amount.cents,
      valuta: draft.valuta,
      rate: draft.rate || "1.00000000",
      party_id: needsParty ? partyId : "",
      fee_note: draft.kind === "fee" ? draft.fee_note : "",
    };
    onStatement({ movements: [...statement.movements, movement] });
    setDraftError({});
    setDraft((current) => ({
      ...current,
      text: "",
      symbol: "",
      amount: "",
      party_id: partyId,
      newIco: "",
      newName: "",
      newIban: "",
      newSwift: "",
      newAddress: "",
      newStatus: "",
      newActive: false,
      newLegalForm: "",
      riskWarning: "",
      newVerified: false,
      newVerifiedAt: undefined,
      newVerificationSource: undefined,
      newVerificationStatus: "unverified",
      isNameAutofilled: false,
      isAddressAutofilled: false,
    }));
    setIcoStatus(null);
  }

  const parties = draft.kind === "in"
    ? books.customers.filter((item) => !item.client_id || item.client_id === statement.client_id)
    : books.contractors.filter((item) => !item.client_id || item.client_id === statement.client_id);
  const selectedParty = parties.find((p) => p.id === draft.party_id);

  return (
    <section className="screen-ui">
      <header className="toolbar">
        <div>
          <button type="button" className="text-btn" onClick={onBack}>Zoznam</button>
          <h1 className="toolbar-title">Výpis {statement.number}</h1>
        </div>
        <button
          type="button"
          className="print-btn"
          disabled={hasErrors}
          title={hasErrors ? "Formulár obsahuje neplatné údaje" : "Zobraziť náhľad výpisu"}
          onClick={() => {
            if (hasErrors) return;
            onPreview();
          }}
        >
          Náhľad
        </button>
      </header>

      <div className="panel">
        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-start", flexWrap: "wrap", gap: "0.75rem" }}>
          <div>
            <p className="toolbar-kicker">Majiteľ účtu / Klient</p>
            {books.clients.length > 1 ? (
              <div style={{ marginTop: "0.25rem", marginBottom: "0.35rem" }}>
                <select
                  value={client?.id || ""}
                  onChange={(e) => onStatement({ client_id: e.target.value })}
                  aria-label="Výber bankového účtu"
                  style={{
                    padding: "0.4rem 0.65rem",
                    fontSize: "0.95rem",
                    fontWeight: 600,
                    borderRadius: "0.5rem",
                    border: "1px solid rgba(255, 255, 255, 0.2)",
                    background: "rgba(0, 0, 0, 0.45)",
                    color: "inherit",
                    cursor: "pointer",
                  }}
                >
                  {books.clients.map((c) => (
                    <option key={c.id} value={c.id} style={{ background: "#111827", color: "#fff" }}>
                      {c.name} ({c.iban})
                    </option>
                  ))}
                </select>
              </div>
            ) : (
              <p style={{ fontWeight: 600, fontSize: "1.05rem" }}>{client?.name}</p>
            )}
            <p className="meta" style={{ marginTop: "0.25rem" }}>
              {client?.type || "Podnikateľský účet"} {client?.number} · {client?.iban} · {client?.switft} · {client?.currency || "EUR"}
            </p>
          </div>
          {client?.address ? (
            <div style={{ textAlign: "right", fontSize: "0.85rem", opacity: 0.85 }}>
              <p>{client.address}</p>
              {client.email ? <p style={{ opacity: 0.75 }}>{client.email}</p> : null}
            </div>
          ) : null}
        </div>
      </div>

      <div className="form-grid two">
        <label className="field">Číslo výpisu
          <input
            value={numberText}
            aria-invalid={Boolean(fieldError.number) || undefined}
            onChange={(event) => {
              const value = event.target.value;
              setNumberText(value);
              const trimmed = value.trim();
              if (!trimmed) {
                setFieldError((current) => ({ ...current, number: "Číslo výpisu je povinné." }));
                return;
              }
              if (!/^[a-zA-Z0-9\-_/]+$/.test(trimmed)) {
                setFieldError((current) => ({
                  ...current,
                  number: "Číslo výpisu môže obsahovať len čísla, písmená, pomlčku alebo lomku.",
                }));
                return;
              }
              setFieldError((current) => ({ ...current, number: "" }));
              onStatement({ number: trimmed });
            }}
          />
          {fieldError.number ? <span className="field-error">{fieldError.number}</span> : null}
        </label>
        <label className="field">Dátum vystavenia
          <input
            value={issuedText}
            inputMode="numeric"
            aria-invalid={Boolean(fieldError.issued) || undefined}
            onChange={(event) => {
              const value = event.target.value;
              setIssuedText(value);
              if (!isRealDate(value)) {
                setFieldError((current) => ({ ...current, issued: "Dátum musí byť skutočný deň vo formáte dd.mm.rrrr." }));
                return;
              }
              setFieldError((current) => ({ ...current, issued: "" }));
              onStatement({ issued_on: value });
            }}
          />
          {fieldError.issued ? <span className="field-error">{fieldError.issued}</span> : null}
        </label>
        <label className="field">Posledný výpis
          <input
            value={previousText}
            inputMode="numeric"
            aria-invalid={Boolean(fieldError.previous) || undefined}
            onChange={(event) => {
              const value = event.target.value;
              setPreviousText(value);
              if (value !== "" && !isRealDate(value)) {
                setFieldError((current) => ({ ...current, previous: "Dátum musí byť skutočný deň vo formáte dd.mm.rrrr." }));
                return;
              }
              setFieldError((current) => ({ ...current, previous: "" }));
              onStatement({ previous_on: value });
            }}
          />
          {fieldError.previous ? <span className="field-error">{fieldError.previous}</span> : null}
        </label>
        <label className="field">Počiatočný zostatok
          <input
            inputMode="decimal"
            value={openingText}
            aria-invalid={Boolean(fieldError.opening) || undefined}
            onChange={(event) => {
              const value = event.target.value;
              setOpeningText(value);
              const parsed = parseAmountInput(value, false);
              if (!parsed.ok) {
                setFieldError((current) => ({ ...current, opening: parsed.error }));
                return;
              }
              setFieldError((current) => ({ ...current, opening: "" }));
              onStatement({ opening: parsed.cents });
            }}
          />
          {fieldError.opening ? <span className="field-error">{fieldError.opening}</span> : null}
        </label>
      </div>

      {statement.movements.length === 0 ? (
        <p>Tento výpis zatiaľ nemá žiadne pohyby.</p>
      ) : (
        <div className="totals">
          <span>DB {summary.dbN} · {summary.dbLabel}</span>
          <span>CR {summary.crN} · {summary.crLabel}</span>
          <strong>{summary.closingLabel}</strong>
        </div>
      )}
      {summary.error ? <p className="banner">{summary.error}</p> : null}

      <ul className="moves">
        {statement.movements.map((movement, index) => {
          const moveParty = movement.party_id
            ? (movement.kind === "in" ? books.customers : books.contractors).find((p) => p.id === movement.party_id)
            : null;
          return (
            <li key={movement.id} className="move">
              <div>
                <div style={{ display: "flex", alignItems: "center", gap: "0.5rem", flexWrap: "wrap" }}>
                  <strong>{labelKind(movement.kind)}</strong>
                  {moveParty?.risk_warning || moveParty?.verification_status === "verified_risky" ? (
                    <span
                      title={`Risk Radar: ${moveParty.risk_warning || "Subjekt je označený ako rizikový v registri SR"}`}
                      style={{
                        background: "#dc2626",
                        color: "#fff",
                        fontSize: "0.7rem",
                        padding: "0.1rem 0.4rem",
                        borderRadius: "0.25rem",
                        display: "inline-flex",
                        alignItems: "center",
                        gap: "0.2rem",
                        fontWeight: 600,
                      }}
                    >
                      <ShieldAlert style={{ width: "0.75rem", height: "0.75rem" }} />
                      Rizikový partner
                    </span>
                  ) : moveParty?.verified && moveParty?.active && moveParty?.verification_status === "verified_active" ? (
                    <span
                      title={`Overené v registri SR (${moveParty.status || "Aktívny subjekt"})${moveParty.verified_at ? ` · ${new Date(moveParty.verified_at).toLocaleDateString("sk-SK")}` : ""}`}
                      style={{
                        background: "rgba(16, 185, 129, 0.2)",
                        color: "#059669",
                        fontSize: "0.7rem",
                        padding: "0.1rem 0.4rem",
                        borderRadius: "0.25rem",
                        fontWeight: 600,
                        display: "inline-flex",
                        alignItems: "center",
                        gap: "0.2rem",
                      }}
                    >
                      ✓ IČO {moveParty.ico}
                    </span>
                  ) : moveParty?.ico ? (
                    <span
                      title="IČO zatiaľ nebolo overené v úradnom registri"
                      style={{
                        background: "#f3f4f6",
                        color: "#4b5563",
                        border: "1px solid #d1d5db",
                        fontSize: "0.7rem",
                        padding: "0.1rem 0.4rem",
                        borderRadius: "0.25rem",
                        fontWeight: 500,
                        display: "inline-flex",
                        alignItems: "center",
                        gap: "0.2rem",
                      }}
                    >
                      IČO {moveParty.ico}
                    </span>
                  ) : null}
                </div>
                <p>
                  {movement.date} · {movement.text || movement.symbol}
                  {moveParty ? ` (${moveParty.name})` : ""}
                </p>
                <p className="amount">{formatMoney(movement.amount)}</p>
              </div>
              <div className="row-actions">
                <button type="button" className="text-btn" disabled={index === 0} onClick={() => move(statement.movements, index, -1, onStatement)}>Hore</button>
                <button type="button" className="text-btn" disabled={index === statement.movements.length - 1} onClick={() => move(statement.movements, index, 1, onStatement)}>Dole</button>
                <button
                  type="button"
                  className="text-btn"
                  onClick={() => setPendingDelete(movement.id)}
                >
                  Zmazať
                </button>
              </div>
            <label className="field">Suma
              <input
                inputMode="decimal"
                value={amountText[movement.id] ?? plainCents(movement.amount)}
                aria-invalid={Boolean(fieldError[movement.id]) || undefined}
                onChange={(event) => {
                  const value = event.target.value;
                  setAmountText((current) => ({ ...current, [movement.id]: value }));
                  const parsed = parseAmountInput(value, false);
                  if (!parsed.ok) {
                    setFieldError((current) => ({ ...current, [movement.id]: parsed.error }));
                    return;
                  }
                  setFieldError((current) => ({ ...current, [movement.id]: "" }));
                  onMovement(movement.id, { amount: parsed.cents });
                }}
              />
              {fieldError[movement.id] ? <span className="field-error">{fieldError[movement.id]}</span> : null}
            </label>
          </li>
        );
      })}
      </ul>

      {pendingDelete ? (
        <div className="confirm" role="dialog" aria-modal="true" aria-labelledby="delete-title">
          <p id="delete-title">Naozaj zmazať tento pohyb?</p>
          <button type="button" className="text-btn" onClick={() => setPendingDelete(null)}>Zrušiť</button>
          <button
            type="button"
            className="print-btn"
            onClick={() => {
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
            }}
          >
            Zmazať
          </button>
        </div>
      ) : null}

      <form
        className="panel"
        onSubmit={(event) => {
          event.preventDefault();
          addMovement();
        }}
      >
        <p className="toolbar-kicker">Nový pohyb</p>
        <div className="form-grid two">
          <label className="field">Typ
            <select value={draft.kind} onChange={(event) => setDraft({ ...draft, kind: event.target.value as MovementKind, party_id: "" })}>
              <option value="in">Prijatá platba</option>
              <option value="out">Odoslaná platba</option>
              <option value="tax">Transakčná daň</option>
              <option value="fee">Poplatok</option>
            </select>
          </label>
          <label className="field">Dátum
            <input
              value={draft.date}
              inputMode="numeric"
              aria-invalid={Boolean(draftError.date) || undefined}
              onChange={(event) => setDraft({ ...draft, date: event.target.value })}
            />
            {draftError.date ? <span className="field-error">{draftError.date}</span> : null}
          </label>
          <label className="field">Suma
            <input
              inputMode="decimal"
              value={draft.amount}
              aria-invalid={Boolean(draftError.amount) || undefined}
              onChange={(event) => setDraft({ ...draft, amount: event.target.value })}
            />
            {draftError.amount ? <span className="field-error">{draftError.amount}</span> : null}
          </label>
          {draft.kind === "in" || draft.kind === "out" ? (
            <>
              <label className="field">Referencia
                <input
                  value={draft.text}
                  aria-invalid={Boolean(draftError.text) || undefined}
                  aria-describedby="draft-text-count"
                  onChange={(event) => setDraft({ ...draft, text: event.target.value })}
                />
                <span id="draft-text-count" className="meta">{textUsed}/{textLimit}</span>
                {draftError.text ? <span className="field-error">{draftError.text}</span> : null}
              </label>
              <label className="field">Symbol
                <input value={draft.symbol} onChange={(event) => setDraft({ ...draft, symbol: event.target.value })} />
              </label>
              <label className="field">Valuta
                <input value={draft.valuta} onChange={(event) => setDraft({ ...draft, valuta: event.target.value })} />
              </label>
              <label className="field">{draft.kind === "in" ? "Platiteľ" : "Príjemca"}
                <select value={draft.party_id} onChange={(event) => setDraft({ ...draft, party_id: event.target.value })}>
                  <option value="">Nová protistrana</option>
                  {parties.map((party) => (
                    <option key={party.id} value={party.id}>
                      {party.name} {party.risk_warning ? "⚠️ (Rizikový)" : party.ico ? `(IČO: ${party.ico})` : ""}
                    </option>
                  ))}
                </select>
              </label>
              {draft.party_id ? (
                selectedParty?.risk_warning ? (
                  <div style={{
                    gridColumn: "1 / -1",
                    background: "#450a0a",
                    border: "1px solid #ef4444",
                    borderRadius: "0.5rem",
                    padding: "0.6rem 0.85rem",
                    color: "#fecaca",
                    fontSize: "0.825rem",
                    display: "flex",
                    alignItems: "center",
                    gap: "0.5rem"
                  }}>
                    <ShieldAlert style={{ width: "1.2rem", height: "1.2rem", color: "#f87171", flexShrink: 0 }} />
                    <div>
                      <strong style={{ color: "#fca5a5" }}>🛡️ Risk Radar:</strong> {selectedParty.risk_warning}
                    </div>
                  </div>
                ) : selectedParty?.ico ? (
                  <div style={{
                    gridColumn: "1 / -1",
                    background: "rgba(16, 185, 129, 0.1)",
                    border: "1px solid rgba(16, 185, 129, 0.3)",
                    borderRadius: "0.5rem",
                    padding: "0.5rem 0.75rem",
                    color: "#a7f3d0",
                    fontSize: "0.825rem",
                    display: "flex",
                    alignItems: "center",
                    gap: "0.45rem"
                  }}>
                    <CheckCircle2 style={{ width: "1rem", height: "1rem", color: "#34d399", flexShrink: 0 }} />
                    <span>Overený partner (IČO: <strong>{selectedParty.ico}</strong> · {selectedParty.legal_form || "Firma"})</span>
                  </div>
                ) : null
              ) : (
                <>
                  <div style={{ gridColumn: "1 / -1", marginTop: "0.25rem" }}>
                    <label className="field">
                      <span style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
                        <span>IČO (Smart 1-Click Onboarding)</span>
                        <span style={{ fontSize: "0.75rem", color: "#2563eb" }}>Register SR (RÚZ / ORSR)</span>
                      </span>
                      <div style={{ display: "flex", gap: "0.5rem" }}>
                        <input
                          placeholder="napr. 31333532"
                          maxLength={10}
                          value={draft.newIco}
                          onChange={(e) => {
                            const val = e.target.value.replace(/\s+/g, "");
                            lookupSeqRef.current++;
                            setDraft((curr) => {
                              const icoChanged = val !== curr.newIco;
                              return {
                                ...curr,
                                newIco: val,
                                ...(icoChanged ? {
                                  newName: curr.isNameAutofilled ? "" : curr.newName,
                                  newAddress: curr.isAddressAutofilled ? "" : curr.newAddress,
                                  newStatus: "",
                                  newActive: false,
                                  newLegalForm: "",
                                  riskWarning: "",
                                  newVerified: false,
                                  newVerifiedAt: undefined,
                                  newVerificationSource: undefined,
                                  newVerificationStatus: "unverified",
                                  isNameAutofilled: false,
                                  isAddressAutofilled: false,
                                } : {}),
                              };
                            });
                            setIcoStatus(null);
                            if (val.length === 8) {
                              void handleIcoLookup(val);
                            }
                          }}
                          onKeyDown={(e) => {
                            if (e.key === "Enter") {
                              e.preventDefault();
                              void handleIcoLookup();
                            }
                          }}
                        />
                        <button
                          type="button"
                          className="text-btn"
                          disabled={icoLoading || !draft.newIco}
                          onClick={() => void handleIcoLookup()}
                          style={{
                            minWidth: "6.5rem",
                            background: "rgba(59, 130, 246, 0.18)",
                            border: "1px solid rgba(59, 130, 246, 0.4)",
                            color: "#1d4ed8",
                            borderRadius: "0.45rem",
                            display: "inline-flex",
                            alignItems: "center",
                            justifyContent: "center",
                            gap: "0.35rem",
                            fontSize: "0.85rem",
                          }}
                        >
                          {icoLoading ? (
                            <>
                              <Loader2 style={{ width: "0.95rem", height: "0.95rem", animation: "spin 1s linear infinite" }} />
                              Hľadám...
                            </>
                          ) : (
                            <>
                              <Search style={{ width: "0.95rem", height: "0.95rem" }} />
                              Overiť IČO
                            </>
                          )}
                        </button>
                      </div>
                    </label>

                    {icoStatus ? (
                      <div
                        style={{
                          marginTop: "0.5rem",
                          padding: "0.65rem 0.85rem",
                          borderRadius: "0.5rem",
                          fontSize: "0.825rem",
                          lineHeight: "1.35",
                          background:
                            icoStatus.type === "error"
                              ? "#450a0a"
                              : icoStatus.type === "warning"
                                ? "rgba(217, 119, 6, 0.15)"
                                : "rgba(16, 185, 129, 0.12)",
                          border:
                            icoStatus.type === "error"
                              ? "1px solid #dc2626"
                              : icoStatus.type === "warning"
                                ? "1px solid rgba(217, 119, 6, 0.4)"
                                : "1px solid rgba(16, 185, 129, 0.35)",
                          color:
                            icoStatus.type === "error"
                              ? "#fecaca"
                              : icoStatus.type === "warning"
                                ? "#92400e"
                                : "#065f46",
                        }}
                      >
                        <div style={{ display: "flex", alignItems: "center", gap: "0.45rem", fontWeight: 700 }}>
                          {icoStatus.type === "error" ? (
                            <ShieldAlert style={{ width: "1.1rem", height: "1.1rem", color: "#f87171", flexShrink: 0 }} />
                          ) : icoStatus.type === "warning" ? (
                            <AlertTriangle style={{ width: "1.1rem", height: "1.1rem", color: "#d97706", flexShrink: 0 }} />
                          ) : (
                            <CheckCircle2 style={{ width: "1.1rem", height: "1.1rem", color: "#10b981", flexShrink: 0 }} />
                          )}
                          <span>{icoStatus.text}</span>
                        </div>
                        {icoStatus.details ? (
                          <div style={{ marginTop: "0.25rem", opacity: 0.9 }}>
                            {icoStatus.details}
                          </div>
                        ) : null}
                      </div>
                    ) : null}
                  </div>

                  <label className="field">Názov
                    <input
                      value={draft.newName}
                      placeholder="Úradný názov firmy"
                      aria-invalid={Boolean(draftError.name) || undefined}
                      onChange={(event) => setDraft((curr) => ({ ...curr, newName: event.target.value, isNameAutofilled: false }))}
                    />
                    {draftError.name ? <span className="field-error">{draftError.name}</span> : null}
                  </label>
                  <label className="field">IBAN
                    <input
                      value={draft.newIban}
                      placeholder="SK00 0000 0000 0000 0000 0000"
                      aria-invalid={Boolean(draftError.iban) || undefined}
                      onChange={(event) => {
                        const val = event.target.value;
                        const cleanIban = val.replace(/\s+/g, "").toUpperCase();
                        const suggestedSwift = deriveSwiftFromSkIban(cleanIban);
                        setDraft({
                          ...draft,
                          newIban: val,
                          newSwift: !draft.newSwift && suggestedSwift ? suggestedSwift : draft.newSwift,
                        });
                      }}
                    />
                    {draftError.iban ? <span className="field-error">{draftError.iban}</span> : null}
                  </label>
                  <label className="field">SWIFT
                    <input
                      value={draft.newSwift}
                      placeholder="napr. TATRSKBX"
                      onChange={(event) => setDraft({ ...draft, newSwift: event.target.value })}
                    />
                  </label>
                </>
              )}
            </>
          ) : (
            <label className="field">Text
              <input
                value={draft.text}
                aria-invalid={Boolean(draftError.text) || undefined}
                aria-describedby="draft-text-count"
                onChange={(event) => setDraft({ ...draft, text: event.target.value })}
              />
              <span id="draft-text-count" className="meta">{textUsed}/{textLimit}</span>
              {draftError.text ? <span className="field-error">{draftError.text}</span> : null}
            </label>
          )}
          {draft.kind === "fee" ? (
            <label className="field">Balík
              <input value={draft.fee_note} onChange={(event) => setDraft({ ...draft, fee_note: event.target.value })} />
            </label>
          ) : null}
        </div>
        <button type="submit" className="print-btn">Pridať pohyb</button>
      </form>
    </section>
  );
}

function labelKind(kind: MovementKind): string {
  if (kind === "in") return "Prijatá";
  if (kind === "out") return "Odoslaná";
  if (kind === "tax") return "Daň";
  return "Poplatok";
}

function move(
  movements: Movement[],
  index: number,
  delta: number,
  onStatement: (partial: { movements: Movement[] }) => void,
) {
  const next = movements.slice();
  const target = index + delta;
  const [item] = next.splice(index, 1);
  next.splice(target, 0, item);
  onStatement({ movements: next });
}

