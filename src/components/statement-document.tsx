import { Printer } from "lucide-react";

function Sheet({ lines, logo }: { lines: string[]; logo?: boolean }) {
  return (
    <div className="fit">
      <div className="fit-spacer">
        <article className="sheet">
          {logo ? <img className="sheet-logo" src="/tb-logo.png" alt="" /> : null}
          <pre className="sheet-pre">{lines.join("\n")}</pre>
        </article>
      </div>
    </div>
  );
}

export function StatementDocument({
  title,
  pageOne,
  pageTwo,
  error,
  onBack,
}: {
  title: string;
  pageOne: string[];
  pageTwo: string[];
  error: string | null;
  onBack: () => void;
}) {
  return (
    <>
      <header className="toolbar">
        <div>
          <button type="button" className="text-btn" onClick={onBack}>
            Zoznam
          </button>
          <h1 className="toolbar-title">{title}</h1>
        </div>
        <button
          type="button"
          className="print-btn"
          disabled={Boolean(error)}
          onClick={() => window.print()}
        >
          <Printer aria-hidden="true" />
          Tlačiť
        </button>
      </header>
      {error ? <p className="banner">{error}</p> : null}
      <Sheet lines={pageOne} logo />
      <Sheet lines={pageTwo} />
    </>
  );
}
