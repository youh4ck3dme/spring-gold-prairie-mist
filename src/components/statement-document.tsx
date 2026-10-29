import { useState, type FormEvent } from "react";
import { Printer } from "lucide-react";
import { downloadStatementPdf, downloadStatementZip } from "@/lib/export-statement";
import { unlockStatement } from "@/lib/unlock-gate";

const RASTER = ["H4CK3D", "H4CK3D", "H4CK3D", "H4CK3D", "H4CK3D", "H4CK3D", "H4CK3D", "H4CK3D"];

function Watermark() {
  return (
    <div className="watermark" aria-hidden="true">
      <p className="watermark-side">H4CK3D</p>
      <div className="watermark-grid">
        {RASTER.map((word, index) => (
          <span key={index}>{word}</span>
        ))}
      </div>
    </div>
  );
}

function Sheet({ lines, logo, unlocked }: { lines: string[]; logo?: boolean; unlocked: boolean }) {
  return (
    <div className="fit">
      <div className="fit-spacer">
        <article className="sheet">
          {logo ? <img className="sheet-logo" src="/tb-logo.svg" alt="Tatra banka" /> : null}
          <pre className="sheet-pre">{lines.join("\n")}</pre>
          {!unlocked ? <Watermark /> : null}
        </article>
      </div>
    </div>
  );
}

export function StatementDocument({
  title,
  fileStem,
  pageOne,
  pageTwo,
  error,
  onBack,
}: {
  title: string;
  fileStem: string;
  pageOne: string[];
  pageTwo: string[];
  error: string | null;
  onBack: () => void;
}) {
  const [unlocked, setUnlocked] = useState(false);
  const [password, setPassword] = useState("");
  const [denied, setDenied] = useState(false);
  const [busy, setBusy] = useState(false);
  const [exportError, setExportError] = useState<string | null>(null);
  const blocked = Boolean(error) || busy;
  const canExport = unlocked && !blocked;
  const blockReason = !error
    ? null
    : error.includes("protistranu")
      ? "Chýba protistrana"
      : "Pretečenie stĺpca";

  async function unlock(event: FormEvent) {
    event.preventDefault();
    const ok = await unlockStatement({ data: password });
    if (ok) {
      setUnlocked(true);
      setDenied(false);
      setPassword("");
      return;
    }
    setDenied(true);
  }

  function sheets(): HTMLElement[] {
    return Array.from(document.querySelectorAll<HTMLElement>("article.sheet"));
  }

  async function save(kind: "pdf" | "zip") {
    if (!canExport) return;
    setBusy(true);
    setExportError(null);
    try {
      const pages = sheets();
      if (pages.length !== 2) throw new Error("pages");
      if (kind === "pdf") await downloadStatementPdf(pages, fileStem, unlocked);
      else await downloadStatementZip(pages, fileStem, unlocked);
    } catch {
      setExportError("Súbor sa nepodarilo vytvoriť.");
    } finally {
      setBusy(false);
    }
  }

  return (
    <>
      <header className="toolbar preview-bar">
        <div>
          <button type="button" className="text-btn" onClick={onBack}>
            Zoznam
          </button>
          <h1 className="toolbar-title">{title}</h1>
        </div>
        <div className="preview-tools">
          {unlocked ? null : (
            <form className="gate" onSubmit={(event) => void unlock(event)}>
              <div className="gate-row">
                <input
                  id="vypis-gate"
                  className="gate-input"
                  type="password"
                  name="vypis-gate"
                  autoComplete="off"
                  value={password}
                  aria-label="Heslo"
                  aria-invalid={denied || undefined}
                  aria-describedby={denied ? "vypis-gate-error" : undefined}
                  onChange={(event) => {
                    setPassword(event.target.value);
                    setDenied(false);
                  }}
                />
                <button type="submit" className="print-btn">
                  Odomknúť stiahnutie
                </button>
              </div>
              {denied ? (
                <p id="vypis-gate-error" className="gate-error">
                  Nesprávne heslo. Súbor ostáva zamknutý.
                </p>
              ) : null}
            </form>
          )}
          <button
            type="button"
            className="print-btn"
            disabled={blocked}
            aria-describedby={blockReason ? "export-block-reason" : undefined}
            onClick={() => window.print()}
          >
            <Printer aria-hidden="true" />
            Tlačiť
          </button>
          <button
            type="button"
            className="print-btn"
            disabled={!canExport}
            aria-describedby={blockReason ? "export-block-reason" : undefined}
            onClick={() => void save("pdf")}
          >
            PDF
          </button>
          <button
            type="button"
            className="print-btn"
            disabled={!canExport}
            aria-describedby={blockReason ? "export-block-reason" : undefined}
            onClick={() => void save("zip")}
          >
            ZIP
          </button>
          {blockReason ? (
            <p id="export-block-reason" className="gate-error">
              {blockReason}
            </p>
          ) : null}
        </div>
      </header>
      {error ? <p className="banner">{error}</p> : null}
      {exportError ? <p className="banner">{exportError}</p> : null}
      <Sheet lines={pageOne} logo unlocked={unlocked} />
      <Sheet lines={pageTwo} unlocked={unlocked} />
    </>
  );
}
