import { useState } from "react";
import { CircleHelp, Eye, FileText, Landmark, Printer, Rows3 } from "lucide-react";
import type { Books } from "@/lib/books";
import { formatIban } from "@/lib/books";
import type { Rendered } from "@/lib/statement";

export function WelcomeScreen({
  books,
  statementId,
  rendered,
  onStatements,
  onEdit,
  onPreview,
}: {
  books: Books;
  statementId: string;
  rendered: Rendered;
  onStatements: () => void;
  onEdit: () => void;
  onPreview: () => void;
}) {
  const statement = books.statements.find((item) => item.id === statementId) ?? books.statements[0];
  const client = books.clients.find((item) => item.id === statement.client_id);
  const bank = books.banks?.find((item) => item.id === client?.bank_id);
  const [help, setHelp] = useState(false);
  const street = bank?.addess.split(/\r?\n/)[0] ?? "";

  return (
    <section className="home">
      <button type="button" className="home-help" onClick={() => setHelp(true)} aria-label="Nápoveda">
        <CircleHelp aria-hidden="true" />
      </button>
      <div className="home-stage">
        <div className="home-art" aria-hidden="true">
          <span className="home-shard home-shard-a" />
          <span className="home-shard home-shard-b" />
          <span className="home-shard home-shard-c" />
        </div>
        <div className="home-mark" aria-hidden="true">
          <span />
        </div>
      </div>

      <div className="home-body">
        <p className="home-kicker">{client?.name}</p>
        <h1 className="home-title">
          Papierový výpis <strong>z uložených pohybov</strong>
        </h1>
        {statement.movements?.length === 0 ? (
          <p className="home-balance">Tento výpis zatiaľ nemá žiadne pohyby.</p>
        ) : (
          <p className="home-balance">
            {rendered.closingLabel} {client?.currency}
          </p>
        )}
        <dl className="home-facts">
          <div>
            <dt>Účet</dt>
            <dd>
              {client?.type} {client?.number}
            </dd>
          </div>
          <div>
            <dt>IBAN</dt>
            <dd>{client ? formatIban(client.iban) : ""}</dd>
          </div>
          <div>
            <dt>Banka</dt>
            <dd>
              {bank?.name}
              {street ? `, ${street}` : ""}
            </dd>
          </div>
          <div>
            <dt>Klient</dt>
            <dd>{client?.client}</dd>
          </div>
        </dl>
        <button type="button" className="home-primary" onClick={onPreview}>
          Otvoriť výpis
        </button>
        <button type="button" className="home-secondary" onClick={onStatements}>
          Všetky výpisy
        </button>
        <p className="home-meta">
          Výpis {statement.number}
          <br />
          Deň výpisu: {statement.issued_on}
        </p>
      </div>

      <nav className="home-nav" aria-label="Ponuka">
        <button type="button" onClick={onStatements}>
          <Rows3 aria-hidden="true" />
          Výpisy
        </button>
        <button type="button" onClick={() => setHelp(true)}>
          <Landmark aria-hidden="true" />
          Účet
        </button>
        <button type="button" onClick={onEdit}>
          <FileText aria-hidden="true" />
          Pohyby
        </button>
        <button type="button" onClick={onPreview}>
          <Eye aria-hidden="true" />
          Náhľad
        </button>
        <button type="button" onClick={onPreview}>
          <Printer aria-hidden="true" />
          Tlač
        </button>
      </nav>

      {help ? (
        <div className="home-sheet" role="dialog" aria-labelledby="home-help-title">
          <h2 id="home-help-title">Údaje účtu</h2>
          <p>Toto nie je aplikácia banky. Nižšie sú polia z výpisu, nie prihlásenie.</p>
          <dl className="home-facts">
            <div>
              <dt>Majiteľ</dt>
              <dd>{client?.name}</dd>
            </div>
            <div>
              <dt>Adresa</dt>
              <dd>{client?.address}</dd>
            </div>
            <div>
              <dt>E-mail</dt>
              <dd>{client?.email}</dd>
            </div>
            <div>
              <dt>SWIFT účtu</dt>
              <dd>{client?.switft}</dd>
            </div>
            <div>
              <dt>Banka</dt>
              <dd>{bank?.addess}</dd>
            </div>
          </dl>
          <button type="button" className="home-primary" onClick={() => setHelp(false)}>
            Zavrieť
          </button>
        </div>
      ) : null}
    </section>
  );
}
