import { useState, useEffect } from "react";
import { 
  Check, 
  Copy, 
  Download, 
  ShieldCheck, 
  ArrowRight, 
  RefreshCw, 
  AlertCircle,
  Key,
  Clock,
  XCircle,
  Lock
} from "lucide-react";
import { saveBridgeConfig, getBridgeConfig } from "@/lib/vorkei-sync";

interface OrderData {
  success: boolean;
  session_id: string;
  payment_status: "completed" | "pending" | "failed";
  credits: number;
  credits_balance: number;
  amount_cents: number;
  currency: string;
  customer_email: string;
  customer_name: string;
  token_key?: string;
  redacted_token: string;
  token_claimed?: boolean;
  download_url: string;
  plugin_version: string;
  checksum_sha256: string;
  support_email: string;
}

export function OrderSuccessView() {
  const [sessionId, setSessionId] = useState<string>("");
  const [claimCode, setClaimCode] = useState<string>("");
  const [loading, setLoading] = useState<boolean>(true);
  const [claiming, setClaiming] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);
  const [copied, setCopied] = useState<boolean>(false);
  const [connected, setConnected] = useState<boolean>(false);
  const [order, setOrder] = useState<OrderData | null>(null);

  useEffect(() => {
    if (typeof window === "undefined") return;

    const params = new URLSearchParams(window.location.search);
    const sid = params.get("session_id") || params.get("sid") || "";
    const code = params.get("claim_code") || params.get("code") || "";

    if (!sid) {
      setLoading(false);
      setError("Chýba identifikátor objednávky (session_id). Prejdite na stránku cenníka a zvoľte si kreditný balíček.");
      return;
    }

    setSessionId(sid);
    setClaimCode(code);

    async function fetchStatus() {
      try {
        setLoading(true);
        setError(null);

        const res = await fetch(
          `https://pdf-t3.vorkei.com/wp-json/vorkei/v1/order-status?session_id=${encodeURIComponent(sid)}`
        );

        if (res.status === 404) {
          setError("Objednávka s týmto Session ID nebola nájdená (NOT_FOUND). Ak ste platbu práve odoslali, počkajte niekoľko sekúnd a obnovte stránku.");
          setOrder(null);
          return;
        }

        if (!res.ok) {
          setError(`Server vrátil chybu pri overovaní platby (HTTP ${res.status}). Kontaktujte podporu na support@vorkei.com.`);
          setOrder(null);
          return;
        }

        const data: OrderData = await res.json();
        setOrder(data);

        // If payment is completed and we have a claim code but no full token yet, claim it securely
        if (data.payment_status === "completed" && !data.token_key && code) {
          try {
            setClaiming(true);
            const claimRes = await fetch("https://pdf-t3.vorkei.com/wp-json/vorkei/v1/order/claim-token", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({
                session_id: sid,
                claim_code: code,
              }),
            });

            if (claimRes.ok) {
              const claimData = await claimRes.json();
              if (claimData.token_key) {
                setOrder((prev) => prev ? { ...prev, token_key: claimData.token_key, token_claimed: true } : prev);
              }
            }
          } catch {
            // Non-fatal: user can still see redacted token and request email delivery
          } finally {
            setClaiming(false);
          }
        }
      } catch (err: unknown) {
        const msg = err instanceof Error ? err.message : "Chyba sieťového spojenia";
        setError(`Nepodarilo sa spojiť s overovacím serverom (${msg}). Skontrolujte internetové pripojenie.`);
        setOrder(null);
      } finally {
        setLoading(false);
      }
    }

    fetchStatus();
  }, []);

  function handleCopyToken() {
    const token = order?.token_key || order?.redacted_token;
    if (!token) return;
    navigator.clipboard.writeText(token);
    setCopied(true);
    setTimeout(() => setCopied(false), 2500);
  }

  function handleConnectBrowser() {
    if (!order?.token_key) return;
    const current = getBridgeConfig();
    saveBridgeConfig({
      ...current,
      bearerToken: order.token_key,
    });
    setConnected(true);
  }

  return (
    <div className="min-h-screen bg-black text-zinc-100 font-sans selection:bg-emerald-600 selection:text-white pb-20">
      {/* Top Bar */}
      <div className="bg-black/90 backdrop-blur-md px-6 py-2.5 text-xs font-medium text-zinc-400 border-b border-zinc-800/80">
        <div className="max-w-4xl mx-auto flex items-center justify-between">
          <div className="flex items-center gap-2">
            <span className="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse" />
            <span className="text-zinc-200">Vorkei Statement Bridge · Overenie & Doručenie</span>
          </div>
          <div className="flex items-center gap-4 text-xs text-zinc-400">
            <span className="text-emerald-400 font-semibold flex items-center gap-1">
              <ShieldCheck className="w-3.5 h-3.5" /> Stripe TLS Verified
            </span>
          </div>
        </div>
      </div>

      <div className="max-w-4xl mx-auto px-6 pt-10">
        {/* Header Branding */}
        <div className="flex items-center justify-between pb-8 mb-8 border-b border-zinc-800">
          <a href="/" className="flex items-center gap-2.5 text-white no-underline">
            <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-[#0A6A56] to-emerald-900 flex items-center justify-center shadow-lg text-white font-black text-lg border border-emerald-500/30">
              V
            </div>
            <div>
              <div className="font-bold text-base tracking-tight">Vorkei Statement Bridge</div>
              <div className="text-[11px] text-zinc-500">Tatra banka PDF & WooCommerce Sync</div>
            </div>
          </a>
          <a
            href="/"
            className="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-medium text-zinc-300 hover:text-white bg-zinc-900 rounded-lg border border-zinc-800 transition"
          >
            <span>Návrat na web</span>
            <ArrowRight className="w-3.5 h-3.5" />
          </a>
        </div>

        {loading ? (
          <div className="p-12 text-center rounded-2xl bg-zinc-950 border border-zinc-800">
            <RefreshCw className="w-8 h-8 text-emerald-400 animate-spin mx-auto mb-4" />
            <h2 className="text-lg font-bold text-white mb-1">Overujem autorizovaný stav platby...</h2>
            <p className="text-xs text-zinc-400">Zabezpečené overenie kryptografického záznamu transakcie na serveri.</p>
          </div>
        ) : error ? (
          <div className="p-8 rounded-2xl bg-rose-950/40 border border-rose-500/30 text-center">
            <AlertCircle className="w-8 h-8 text-rose-400 mx-auto mb-3" />
            <h2 className="text-lg font-bold text-white mb-1">Stav objednávky sa nepodarilo overiť</h2>
            <p className="text-xs text-rose-300 mb-4 max-w-lg mx-auto">{error}</p>
            <div className="flex flex-wrap items-center justify-center gap-3">
              <button
                type="button"
                onClick={() => window.location.reload()}
                className="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-zinc-900 hover:bg-zinc-800 text-xs font-semibold text-zinc-200 border border-zinc-700 transition"
              >
                <RefreshCw className="w-3.5 h-3.5" />
                <span>Skúsiť znova</span>
              </button>
              <a
                href="mailto:support@vorkei.com"
                className="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-700 hover:bg-emerald-600 text-xs font-semibold text-white transition"
              >
                <span>Kontaktovať podporu: support@vorkei.com</span>
              </a>
            </div>
          </div>
        ) : order && order.payment_status === "pending" ? (
          /* Pending Payment Screen */
          <div className="p-8 rounded-2xl bg-amber-950/30 border border-amber-500/30 text-center space-y-4">
            <Clock className="w-10 h-10 text-amber-400 animate-pulse mx-auto" />
            <h2 className="text-xl font-bold text-white">Platba sa spracováva (PENDING)</h2>
            <p className="text-xs text-amber-200/90 max-w-md mx-auto">
              Vaša platba bola prijatá platobnou bránou a čaká na definitívne bankové zúčtovanie (SEPA / oneskorená autorizácia).
              Kredity budú pripísané okamžite po doručení potvrdenia.
            </p>
            <div className="p-3 bg-black/60 rounded-xl border border-zinc-800 inline-block font-mono text-xs text-zinc-400">
              Session ID: {order.session_id}
            </div>
            <div>
              <button
                type="button"
                onClick={() => window.location.reload()}
                className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs transition"
              >
                <RefreshCw className="w-3.5 h-3.5" />
                <span>Skontrolovať stav platby</span>
              </button>
            </div>
          </div>
        ) : order && order.payment_status === "failed" ? (
          /* Failed Payment Screen */
          <div className="p-8 rounded-2xl bg-rose-950/30 border border-rose-500/30 text-center space-y-4">
            <XCircle className="w-10 h-10 text-rose-400 mx-auto" />
            <h2 className="text-xl font-bold text-white">Platba nebola úspešná (FAILED)</h2>
            <p className="text-xs text-rose-200/90 max-w-md mx-auto">
              Platobná transakcia bola zamietnutá bankou alebo vypršal jej časový limit. Z vášho účtu neboli odpočítané žiadne prostriedky.
            </p>
            <a
              href="/#cennik"
              className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-white font-bold text-xs border border-zinc-700 transition"
            >
              <span>Vrátiť sa k výberu balíčka</span>
            </a>
          </div>
        ) : order && order.payment_status === "completed" && (
          <div className="space-y-6">
            {/* Payment Confirmed Banner */}
            <div className="p-6 rounded-2xl bg-gradient-to-r from-emerald-950/70 via-zinc-900/90 to-zinc-950 border border-emerald-500/40 shadow-xl shadow-emerald-950/30 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div className="flex items-start gap-4">
                <div className="w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 flex items-center justify-center shrink-0">
                  <Check className="w-6 h-6" />
                </div>
                <div>
                  <div className="flex items-center gap-2 mb-1">
                    <span className="text-xs font-bold uppercase tracking-wider text-emerald-400">
                      Platba Potvrdená
                    </span>
                    <span className="text-xs text-zinc-500">•</span>
                    <span className="text-xs text-zinc-400 font-mono">ID: {order.session_id.slice(0, 18)}...</span>
                  </div>
                  <h1 className="text-2xl font-black text-white tracking-tight">
                    Úspešne pripísaných {order.credits} kreditov
                  </h1>
                  <p className="text-xs text-zinc-400 mt-1">
                    Aktuálny zostatok vašej peňaženky je{" "}
                    <strong className="text-emerald-400 font-semibold">{order.credits_balance} kreditov</strong>.
                    Kredity majú neobmedzenú časovú platnosť.
                  </p>
                </div>
              </div>

              <div className="text-right shrink-0">
                <div className="text-2xl font-extrabold text-white">
                  {(order.amount_cents / 100).toFixed(2)} {order.currency.toUpperCase()}
                </div>
                <div className="text-xs text-emerald-400 font-medium flex items-center justify-end gap-1 mt-0.5">
                  <ShieldCheck className="w-3.5 h-3.5" /> 256-bit Stripe SSL
                </div>
              </div>
            </div>

            {/* Credential Card: API Token */}
            <div className="p-6 rounded-2xl bg-zinc-950 border border-zinc-800">
              <div className="flex items-center justify-between mb-3">
                <div className="flex items-center gap-2">
                  <Key className="w-4 h-4 text-emerald-400" />
                  <h2 className="text-sm font-bold text-white uppercase tracking-wider">
                    Váš Zákaznícky API Token
                  </h2>
                </div>
                <span className="text-[11px] text-zinc-500 flex items-center gap-1">
                  <Lock className="w-3 h-3" /> Chránené jednorazové doručenie
                </span>
              </div>

              <div className="p-3.5 rounded-xl bg-black border border-zinc-800 flex flex-col sm:flex-row items-center justify-between gap-3">
                <code className="font-mono text-emerald-400 text-sm tracking-wider font-bold select-all break-all">
                  {order.token_key || order.redacted_token}
                </code>

                <div className="flex items-center gap-2 w-full sm:w-auto shrink-0">
                  <button
                    type="button"
                    onClick={handleCopyToken}
                    className="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-lg bg-zinc-900 hover:bg-zinc-800 text-xs font-semibold text-zinc-200 border border-zinc-700 transition"
                  >
                    {copied ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
                    <span>{copied ? "Skopírované!" : "Kopírovať"}</span>
                  </button>

                  {order.token_key && (
                    <button
                      type="button"
                      onClick={handleConnectBrowser}
                      disabled={connected}
                      className="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-lg bg-[#0A6A56] hover:bg-emerald-600 text-xs font-semibold text-white transition disabled:opacity-50"
                    >
                      {connected ? <Check className="w-3.5 h-3.5" /> : <ShieldCheck className="w-3.5 h-3.5" />}
                      <span>{connected ? "Prepojené s aplikáciou" : "Prepojiť prehliadač"}</span>
                    </button>
                  )}
                </div>
              </div>

              {!order.token_key && order.redacted_token && (
                <div className="mt-3 text-[11px] text-zinc-400 bg-zinc-900/60 p-3 rounded-lg border border-zinc-800 flex items-start gap-2">
                  <Lock className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                  <span>
                    Z bezpečnostných dôvodov je zobrazený redigovaný token. Ak potrebujete token vygenerovať nanovo, môžete ho kedykoľvek revokovať vo vašej WordPress administrácii alebo kontaktujte podporu na {order.support_email}.
                  </span>
                </div>
              )}

              {connected && (
                <div className="mt-3 text-xs text-emerald-400 bg-emerald-950/40 p-2.5 rounded-lg border border-emerald-500/20 flex items-center gap-2">
                  <Check className="w-4 h-4 shrink-0" />
                  <span>Token bol bezpečne uložený do pamäte prehliadača. Môžete ihneď generovať a synchronizovať výpisy!</span>
                </div>
              )}
            </div>

            {/* Plugin Download Card */}
            <div className="p-6 rounded-2xl bg-zinc-950 border border-zinc-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
              <div className="flex items-start gap-3.5">
                <div className="w-10 h-10 rounded-xl bg-zinc-900 text-zinc-300 border border-zinc-800 flex items-center justify-center shrink-0">
                  <Download className="w-5 h-5 text-emerald-400" />
                </div>
                <div>
                  <h3 className="text-sm font-bold text-white">
                    WordPress Plugin: vorkei-statement-bridge.zip
                  </h3>
                  <div className="text-xs text-zinc-400 mt-0.5">
                    Verzia <strong>{order.plugin_version}</strong> · HPOS Kompatibilný · 100% PclZip Safe
                  </div>
                  <div className="text-[11px] font-mono text-zinc-500 mt-1">
                    SHA-256: {order.checksum_sha256}
                  </div>
                </div>
              </div>

              <a
                href={order.download_url}
                download
                className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#0A6A56] via-emerald-600 to-[#0A6A56] hover:brightness-110 text-white font-bold text-xs shadow-lg transition"
              >
                <Download className="w-4 h-4" />
                <span>Stiahnuť Plugin ZIP</span>
              </a>
            </div>

            {/* 3-Step Onboarding Guide */}
            <div className="p-6 rounded-2xl bg-zinc-950 border border-zinc-800">
              <h3 className="text-xs font-bold uppercase tracking-wider text-emerald-400 mb-4">
                Jednoduchý Sprievodca Aktiváciou (3 Kroky)
              </h3>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="p-4 rounded-xl bg-zinc-900/60 border border-zinc-800/80">
                  <div className="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-xs mb-2.5 border border-emerald-500/30">
                    1
                  </div>
                  <div className="text-xs font-bold text-white mb-1">Nainštalujte Plugin</div>
                  <p className="text-[11px] text-zinc-400 leading-relaxed">
                    Vo vašom WordPress administrátorskom menu otvorte <strong>Pluginy ➔ Pridať nový ➔ Nahrať plugin</strong> a nahrajte stiahnutý ZIP.
                  </p>
                </div>

                <div className="p-4 rounded-xl bg-zinc-900/60 border border-zinc-800/80">
                  <div className="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-xs mb-2.5 border border-emerald-500/30">
                    2
                  </div>
                  <div className="text-xs font-bold text-white mb-1">Zadajte Váš API Token</div>
                  <p className="text-[11px] text-zinc-400 leading-relaxed">
                    V administrácii v záložke <strong>Vorkei Bridge ➔ Nastavenia</strong> vložte váš API Token pre automatickú autorizáciu.
                  </p>
                </div>

                <div className="p-4 rounded-xl bg-zinc-900/60 border border-zinc-800/80">
                  <div className="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-xs mb-2.5 border border-emerald-500/30">
                    3
                  </div>
                  <div className="text-xs font-bold text-white mb-1">Importujte Výpisy</div>
                  <p className="text-[11px] text-zinc-400 leading-relaxed">
                    V aplikácii kliknite na <strong>Synchronizovať do WordPressu</strong>. Objednávky WooCommerce sa spárujú automaticky!
                  </p>
                </div>
              </div>
            </div>

            {/* Bottom Actions */}
            <div className="pt-4 flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-zinc-900">
              <a
                href="/"
                className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm shadow-lg shadow-emerald-950/40 transition"
              >
                <span>Prejsť do Generátora Výpisov</span>
                <ArrowRight className="w-4 h-4" />
              </a>

              <div className="text-xs text-zinc-500 text-center sm:text-right">
                Potrebujete pomôcť? Napíšte nám na{" "}
                <a href="mailto:support@vorkei.com" className="text-zinc-300 underline hover:text-white">
                  support@vorkei.com
                </a>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
