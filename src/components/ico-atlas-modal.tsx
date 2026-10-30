import { useState } from "react";
import { Search, ShieldAlert, CheckCircle2, AlertTriangle, Building2, X, Loader2, Calendar, MapPin, Hash, ExternalLink } from "lucide-react";
import { lookupCompanyByIco, type CompanyLookupResult } from "@/lib/company-lookup";

interface IcoAtlasModalProps {
  isOpen: boolean;
  onClose: () => void;
}

export function IcoAtlasModal({ isOpen, onClose }: IcoAtlasModalProps) {
  const [ico, setIco] = useState("");
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<CompanyLookupResult | null>(null);

  if (!isOpen) return null;

  async function handleSearch(searchIco?: string) {
    const target = (searchIco ?? ico).replace(/\s+/g, "");
    if (!target) return;
    setLoading(true);
    try {
      const res = await lookupCompanyByIco({ data: target });
      setResult(res);
    } catch (err) {
      setResult({
        found: false,
        ico: target,
        name: null,
        status: null,
        active: false,
        address: { street: null, city: null, postal_code: null, country: null },
        legal_form: null,
        founded_on: null,
        dissolved_on: null,
        is_risky: false,
        error: (err as Error).message,
      });
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
      <div className="relative w-full max-w-lg rounded-2xl bg-[#0f1117] border border-white/10 p-6 shadow-2xl text-white">
        <div className="flex items-center justify-between pb-4 border-b border-white/10">
          <div className="flex items-center gap-2">
            <Building2 className="w-5 h-5 text-blue-400" />
            <h2 className="text-lg font-semibold tracking-wide">ICO-ATLAS · Register SR & Risk Radar</h2>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="p-1 rounded-lg text-white/50 hover:text-white hover:bg-white/10 transition"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        <div className="mt-4 space-y-4">
          <p className="text-xs text-white/70">
            Overenie subjektov v reálnom čase voči registru SR s automatickou detekciou konkurzov, likvidácií a zániku.
          </p>

          <div>
            <div className="flex gap-2">
              <input
                type="text"
                placeholder="Zadajte 8-miestne IČO..."
                value={ico}
                onChange={(e) => setIco(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === "Enter") {
                    e.preventDefault();
                    void handleSearch();
                  }
                }}
                className="flex-1 px-3 py-2 bg-black/40 border border-white/15 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-blue-500"
              />
              <button
                type="button"
                disabled={loading || !ico.trim()}
                onClick={() => void handleSearch()}
                className="px-4 py-2 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white font-medium rounded-lg flex items-center gap-2 transition"
              >
                {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <Search className="w-4 h-4" />}
                Overiť
              </button>
            </div>

            <div className="flex items-center gap-2 mt-2 text-xs text-white/50">
              <span>Rýchly test:</span>
              <button
                type="button"
                onClick={() => {
                  setIco("31333532");
                  void handleSearch("31333532");
                }}
                className="hover:text-blue-400 underline underline-offset-2"
              >
                ESET (31333532)
              </button>
              <span>·</span>
              <button
                type="button"
                onClick={() => {
                  setIco("00686930");
                  void handleSearch("00686930");
                }}
                className="hover:text-blue-400 underline underline-offset-2"
              >
                Tatra banka (00686930)
              </button>
              <span>·</span>
              <button
                type="button"
                onClick={() => {
                  setIco("31707921");
                  void handleSearch("31707921");
                }}
                className="text-red-400 hover:text-red-300 underline underline-offset-2"
              >
                Konkurz (31707921)
              </button>
            </div>
          </div>

          {result ? (
            <div className="mt-4 rounded-xl border border-white/10 bg-white/5 p-4 space-y-3">
              {result.found && result.name ? (
                <>
                  {result.is_risky ? (
                    <div className="p-3 bg-red-950/80 border border-red-500/80 rounded-lg text-red-200 text-xs flex gap-2">
                      <ShieldAlert className="w-5 h-5 text-red-400 shrink-0 mt-0.5" />
                      <div>
                        <strong className="block text-red-300 font-semibold mb-0.5">🛡️ RISK RADAR: RIZIKOVÝ SUBJEKT!</strong>
                        <span>{result.risk_reason}</span>
                      </div>
                    </div>
                  ) : (
                    <div className="p-2.5 bg-emerald-950/60 border border-emerald-500/50 rounded-lg text-emerald-200 text-xs flex items-center gap-2">
                      <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0" />
                      <span>Subjekt je <strong>aktívny a overený</strong> v úradnom registri SR.</span>
                    </div>
                  )}

                  <div className="space-y-2 pt-1 text-sm">
                    <div>
                      <span className="text-xs text-white/50 block">Úradný názov:</span>
                      <strong className="text-white text-base">{result.name}</strong>
                    </div>

                    <div className="grid grid-cols-2 gap-2 text-xs">
                      <div>
                        <span className="text-white/50 block">IČO:</span>
                        <span className="font-mono text-white/90">{result.ico}</span>
                      </div>
                      <div>
                        <span className="text-white/50 block">Právna forma:</span>
                        <span className="text-white/90">{result.legal_form || "Nezadaná"}</span>
                      </div>
                      <div>
                        <span className="text-white/50 block">Stav:</span>
                        <span className={result.is_risky ? "text-red-400 font-semibold" : "text-emerald-400 font-semibold"}>
                          {result.status}
                        </span>
                      </div>
                      <div>
                        <span className="text-white/50 block">Vznik / Zánik:</span>
                        <span className="text-white/90">
                          {result.founded_on || "—"} {result.dissolved_on ? `(† ${result.dissolved_on})` : ""}
                        </span>
                      </div>
                    </div>

                    {result.formatted_address ? (
                      <div className="text-xs pt-1 border-t border-white/10">
                        <span className="text-white/50 block">Sídlo:</span>
                        <span className="text-white/80 whitespace-pre-line">{result.formatted_address}</span>
                      </div>
                    ) : null}
                  </div>
                </>
              ) : (
                <div className="p-3 bg-amber-950/40 border border-amber-500/40 rounded-lg text-amber-200 text-xs flex items-center gap-2">
                  <AlertTriangle className="w-4 h-4 text-amber-400 shrink-0" />
                  <span>{result.error || "Subjekt s týmto IČO nebol nájdený."}</span>
                </div>
              )}
            </div>
          ) : null}
        </div>

        <div className="mt-6 flex justify-end">
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg text-sm transition"
          >
            Zavrieť
          </button>
        </div>
      </div>
    </div>
  );
}
