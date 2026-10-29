import { useEffect, useState } from "react";
import {
  SEED,
  type Books,
  type Movement,
  type MovementKind,
  type Party,
  formatMoney,
  parseAmount,
} from "@/lib/books";
import { renderStatement } from "@/lib/statement";
import { StatementDocument } from "@/components/statement-document";
import { WelcomeScreen } from "@/components/welcome-screen";

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
  const [view, setView] = useState<"home" | "list" | "edit" | "preview">("home");
  const [activeId, setActiveId] = useState(SEED.statements[0].id);

  useEffect(() => {
    try {
      const raw = localStorage.getItem(KEY);
      if (raw) {
        const parsed = JSON.parse(raw) as Books;
        if (parsed.clients?.length && parsed.statements?.length) setBooks(parsed);
      }
    } catch {
      /* seed ostáva */
    }
    setReady(true);
  }, []);

  useEffect(() => {
    if (!ready) return;
    localStorage.setItem(KEY, JSON.stringify(books));
  }, [books, ready]);

  const statement = books.statements.find((item) => item.id === activeId) ?? books.statements[0];
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
      client_id: books.clients[0].id,
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
    const id = uid(kind === "in" ? "cu" : "co");
    const row: Party = { ...party, id, client_id: statement.client_id };
    setBooks((current) => ({
      ...current,
      customers: kind === "in" ? [...current.customers, row] : current.customers,
      contractors: kind === "out" ? [...current.contractors, row] : current.contractors,
    }));
    return id;
  }

  return (
    <div className={view === "home" ? "desk desk-home" : "desk"}>
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
            <button type="button" className="print-btn" onClick={addStatement}>
              Nový výpis
            </button>
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
                    <p className="amount">{viewModel.closingLabel} {client?.currency}</p>
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
          pageOne={rendered.pageOne}
          pageTwo={rendered.pageTwo}
          error={rendered.error}
          onBack={() => setView("list")}
        />
      ) : null}
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
  const client = books.clients.find((item) => item.id === statement.client_id);
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
    newName: "",
    newIban: "",
    newSwift: "",
  });

  function addMovement() {
    const amount = parseAmount(draft.amount);
    if (!Number.isFinite(amount) || amount < 0) return;
    let partyId = draft.party_id;
    if ((draft.kind === "in" || draft.kind === "out") && !partyId && draft.newName.trim()) {
      partyId = onAddParty(draft.kind, {
        name: draft.newName.trim(),
        iban: draft.newIban.replace(/\s+/g, ""),
        swift: draft.newSwift.trim(),
      });
    }
    const movement: Movement = {
      id: uid("m"),
      kind: draft.kind,
      date: draft.date,
      text: draft.text || (draft.kind === "tax" ? `Transakčná daň ${draft.date}` : draft.kind === "fee" ? "Poplatok za balík" : ""),
      symbol: draft.symbol,
      amount,
      valuta: draft.valuta,
      rate: draft.rate || "1.00000000",
      party_id: draft.kind === "in" || draft.kind === "out" ? partyId : "",
      fee_note: draft.kind === "fee" ? draft.fee_note : "",
    };
    onStatement({ movements: [...statement.movements, movement] });
    setDraft((current) => ({ ...current, text: "", symbol: "", amount: "", party_id: partyId }));
  }

  const parties = draft.kind === "in"
    ? books.customers.filter((item) => item.client_id === statement.client_id)
    : books.contractors.filter((item) => item.client_id === statement.client_id);

  return (
    <section className="screen-ui">
      <header className="toolbar">
        <div>
          <button type="button" className="text-btn" onClick={onBack}>Zoznam</button>
          <h1 className="toolbar-title">Výpis {statement.number}</h1>
        </div>
        <button type="button" className="print-btn" onClick={onPreview}>Náhľad</button>
      </header>

      <div className="panel">
        <p className="toolbar-kicker">Účet {client?.client}</p>
        <p>{client?.name}</p>
        <p className="meta">{client?.type} {client?.number} · {client?.switft} · {client?.currency}</p>
      </div>

      <div className="form-grid two">
        <label className="field">Číslo výpisu
          <input value={statement.number} onChange={(event) => onStatement({ number: event.target.value })} />
        </label>
        <label className="field">Dátum vystavenia
          <input value={statement.issued_on} onChange={(event) => onStatement({ issued_on: event.target.value })} />
        </label>
        <label className="field">Posledný výpis
          <input value={statement.previous_on} onChange={(event) => onStatement({ previous_on: event.target.value })} />
        </label>
        <label className="field">Počiatočný zostatok
          <input
            inputMode="decimal"
            value={String(statement.opening)}
            onChange={(event) => {
              const value = parseAmount(event.target.value);
              if (Number.isFinite(value)) onStatement({ opening: value });
            }}
          />
        </label>
      </div>

      <div className="totals">
        <span>DB {summary.dbN} · {summary.dbLabel}</span>
        <span>CR {summary.crN} · {summary.crLabel}</span>
        <strong>{summary.closingLabel}</strong>
      </div>
      {summary.error ? <p className="banner">{summary.error}</p> : null}

      <ul className="moves">
        {statement.movements.map((movement, index) => (
          <li key={movement.id} className="move">
            <div>
              <strong>{labelKind(movement.kind)}</strong>
              <p>{movement.date} · {movement.text || movement.symbol}</p>
              <p className="amount">{formatMoney(movement.amount)}</p>
            </div>
            <div className="row-actions">
              <button type="button" className="text-btn" disabled={index === 0} onClick={() => move(statement.movements, index, -1, onStatement)}>Hore</button>
              <button type="button" className="text-btn" disabled={index === statement.movements.length - 1} onClick={() => move(statement.movements, index, 1, onStatement)}>Dole</button>
              <button
                type="button"
                className="text-btn"
                onClick={() => onStatement({ movements: statement.movements.filter((item) => item.id !== movement.id) })}
              >
                Zmazať
              </button>
            </div>
            <label className="field">Suma
              <input
                inputMode="decimal"
                value={String(movement.amount)}
                onChange={(event) => {
                  const value = parseAmount(event.target.value);
                  if (Number.isFinite(value)) onMovement(movement.id, { amount: value });
                }}
              />
            </label>
          </li>
        ))}
      </ul>

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
            <input value={draft.date} onChange={(event) => setDraft({ ...draft, date: event.target.value })} />
          </label>
          <label className="field">Suma
            <input inputMode="decimal" value={draft.amount} onChange={(event) => setDraft({ ...draft, amount: event.target.value })} />
          </label>
          {draft.kind === "in" || draft.kind === "out" ? (
            <>
              <label className="field">Referencia
                <input value={draft.text} onChange={(event) => setDraft({ ...draft, text: event.target.value })} />
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
                    <option key={party.id} value={party.id}>{party.name}</option>
                  ))}
                </select>
              </label>
              {draft.party_id ? null : (
                <>
                  <label className="field">Názov
                    <input value={draft.newName} onChange={(event) => setDraft({ ...draft, newName: event.target.value })} />
                  </label>
                  <label className="field">IBAN
                    <input value={draft.newIban} onChange={(event) => setDraft({ ...draft, newIban: event.target.value })} />
                  </label>
                  <label className="field">SWIFT
                    <input value={draft.newSwift} onChange={(event) => setDraft({ ...draft, newSwift: event.target.value })} />
                  </label>
                </>
              )}
            </>
          ) : (
            <label className="field">Text
              <input value={draft.text} onChange={(event) => setDraft({ ...draft, text: event.target.value })} />
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

