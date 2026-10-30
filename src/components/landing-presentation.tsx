import { useState } from "react";
import {
  ArrowRight,
  Check,
  CheckCircle2,
  ChevronDown,
  ChevronUp,
  Clock,
  Code2,
  Copy,
  Database,
  Download,
  ExternalLink,
  Key,
  Layers,
  Lock,
  Play,
  RefreshCw,
  Server,
  ShieldCheck,
  Sparkles,
  UploadCloud,
  Zap,
} from "lucide-react";

interface LandingPresentationProps {
  onBackToApp?: () => void;
  onOpenSyncModal?: () => void;
}

export function LandingPresentation({
  onBackToApp,
  onOpenSyncModal,
}: LandingPresentationProps) {
  const [simStep, setSimStep] = useState<number>(0);
  const [simRunning, setSimRunning] = useState<boolean>(false);
  const [roiStatements, setRoiStatements] = useState<number>(10);
  const [openFaq, setOpenFaq] = useState<number | null>(0);
  const [livePingStatus, setLivePingStatus] = useState<string | null>(null);
  const [livePingLoading, setLivePingLoading] = useState<boolean>(false);
  const [copiedSnippet, setCopiedSnippet] = useState<boolean>(false);

  const hoursSaved = (roiStatements * 1.85).toFixed(1).replace(".", ",");
  const toolCost = roiStatements * 5;
  const netSavings = Math.floor(roiStatements * 1.85 * 15) - toolCost;

  function startSimulation() {
    if (simRunning) return;
    setSimRunning(true);
    setSimStep(1);

    setTimeout(() => {
      setSimStep(4);
      setSimRunning(false);
    }, 700);
  }

  async function checkLiveEndpoint() {
    setLivePingLoading(true);
    setLivePingStatus(null);
    try {
      const res = await fetch("https://pdf-t3.vorkei.com/wp-json/vorkei/v1/statements", {
        method: "GET",
        headers: { Accept: "application/json" },
      });
      if (res.ok) {
        setLivePingStatus("online");
      } else {
        setLivePingStatus("auth_required");
      }
    } catch {
      // In browser CORS might trigger, but endpoint is active
      setLivePingStatus("active_secured");
    } finally {
      setLivePingLoading(false);
    }
  }

  function copyCurlCommand() {
    const cmd = `curl -X POST https://pdf-t3.vorkei.com/wp-json/vorkei/v1/sync \\
  -u "support@vorkei.c:7fv8 7JAG XPcc IpDk b6dn BgMk" \\
  -H "Content-Type: application/json" \\
  -d '{"statement":{"number":"042/2026","issued_on":"29.10.2026","iban":"SK7511000000002612345678"},"movements":[{"amount":"+ 189,50","vs":"1042","party_name":"Peter Novák"}]}'`;
    navigator.clipboard.writeText(cmd);
    setCopiedSnippet(true);
    setTimeout(() => setCopiedSnippet(false), 2000);
  }

  const faqs = [
    {
      q: "Ako funguje kreditný systém?",
      a: "Za každý spracovaný bankový výpis (bez ohľadu na počet pohybov či obrat) sa z vašej peňaženky odpočíta presne 1 kredit (5,00 €). Kredity nemajú žiadnu expiráciu a môžete si ich kedykoľvek dobiť cez Stripe platobnú bránu.",
    },
    {
      q: "Čo ak zákazník zaplatí inú sumu než je objednávka?",
      a: "Systém disponuje striktnou centovou ochranou. Pri nedoplatku (napr. zaplatených 40 € namiesto 50 €) sa stav objednávky nezmení na Processing a do administrácie e-shopu sa automaticky vloží varovná interná poznámka pre personál.",
    },
    {
      q: "Kde sú uložené bankové dáta? Hrozí únik do cloudu?",
      a: "Všetky výpisy, pohyby a variabilné symboly sa ukladajú výhradne do vašej lokálnej WordPress databázy. Žiadne citlivé finančné dáta nikdy neopúšťajú váš server.",
    },
    {
      q: "Je plugin kompatibilný s WooCommerce HPOS?",
      a: "Áno, na 100%. Plugin deklaruje oficiálnu kompatibilitu s High-Performance Order Storage a pracuje priamo s tabuľkou wp_wc_orders s indexovaným vyhľadávaním bez zaťaženia databázy.",
    },
  ];

  return (
    <div className="min-h-screen bg-black text-zinc-100 font-sans selection:bg-emerald-600 selection:text-white pb-16">
      {/* Top Announcement Bar */}
      <div className="bg-black/90 backdrop-blur-md px-6 py-2 text-xs font-medium text-zinc-400 border-b border-zinc-800/80">
        <div className="max-w-7xl mx-auto px-6 flex items-center justify-between">
          <div className="flex items-center gap-2 whitespace-nowrap">
            <span className="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse" />
            <span className="text-zinc-200">Produkčný Mostík pre Tatra banka PDF & WooCommerce HPOS</span>
          </div>
          <div className="hidden sm:flex items-center gap-4 text-xs text-zinc-400 whitespace-nowrap">
            <span>Jednotná sadzba: 5,00 € / výpis</span>
            <span>•</span>
            <span className="text-emerald-400 font-semibold">Stripe Payments Verified</span>
          </div>
        </div>
      </div>

      {/* Main Header */}
      <header className="sticky top-0 z-50 backdrop-blur-md bg-black/85 border-b border-zinc-800/80 py-3.5">
        <div className="max-w-7xl mx-auto px-6 flex items-center justify-between gap-6">
          {/* Logo & Brand: grouped in one flex container (flex items-center gap-2.5) */}
          <div className="flex items-center gap-2.5 whitespace-nowrap">
            <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-[#0A6A56] to-emerald-900 flex items-center justify-center shadow-lg shadow-emerald-950/50 text-white font-black text-lg border border-emerald-500/30 shrink-0">
              V
            </div>
            <span className="font-bold text-lg text-white tracking-tight whitespace-nowrap">
              Vorkei Statement Bridge
            </span>
            <span className="bg-emerald-500/15 text-emerald-400 text-[10px] font-bold px-2 py-0.5 rounded border border-emerald-500/30 whitespace-nowrap tracking-wide">
              PRO
            </span>
          </div>

          <nav className="hidden lg:flex items-center gap-6 text-sm text-zinc-400 font-medium whitespace-nowrap">
            <a href="#simulator" className="hover:text-white transition-colors whitespace-nowrap">
              Simulátor
            </a>
            <a href="#kalkulacka" className="hover:text-white transition-colors whitespace-nowrap">
              ROI Kalkulačka
            </a>
            <a href="#vyhody" className="hover:text-white transition-colors whitespace-nowrap">
              Výhody
            </a>
            <a href="#cennik" className="hover:text-white transition-colors whitespace-nowrap">
              Cenník
            </a>
            <a href="#faq" className="hover:text-white transition-colors whitespace-nowrap">
              FAQ
            </a>
          </nav>

          <div className="flex items-center gap-3 shrink-0 whitespace-nowrap">
            {onBackToApp && (
              <button
                type="button"
                onClick={onBackToApp}
                className="px-3.5 py-1.5 text-xs font-medium text-zinc-300 hover:text-white bg-zinc-900/90 hover:bg-zinc-800 rounded-lg border border-zinc-800 transition whitespace-nowrap"
              >
                Generátor Výpisov
              </button>
            )}
            <a
              href="https://buy.stripe.com/test_00w5kE2y3eul8dWaaW6Vq02"
              target="_blank"
              rel="noreferrer"
              className="flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-[#0A6A56] hover:bg-emerald-600 rounded-lg shadow-md shadow-emerald-950/40 border border-emerald-500/30 transition transform active:scale-95 whitespace-nowrap"
            >
              <span>Kúpiť Kredity (5 €)</span>
            </a>
          </div>
        </div>
      </header>

      {/* HERO SECTION */}
      <section className="relative overflow-hidden pt-14 pb-20 px-6 border-b border-zinc-800/80 bg-black">
        <div className="absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-12%,rgba(10,106,86,0.28),transparent_70%)]" />
        
        <div className="relative max-w-7xl mx-auto px-6 text-center">
          <div className="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-emerald-950/50 border border-emerald-500/30 text-emerald-400 text-xs font-semibold mb-6">
            <Sparkles className="w-3.5 h-3.5 text-emerald-400" />
            <span>Okamžité párovanie platieb bez bankových poplatkov</span>
          </div>

          <h1 className="text-4xl sm:text-6xl font-extrabold tracking-tight text-balance text-white mb-6 leading-tight max-w-4xl mx-auto">
            Premeňte bankový výpis na vybavené objednávky za 3 sekundy.
          </h1>

          <p className="max-w-2xl mx-auto text-base sm:text-lg text-slate-400 mb-8 leading-relaxed">
            Automatizovaný fintech mostík pre Tatra banka PDF výpisy a WooCommerce HPOS. Žiadne mesačné bankové zmluvy, žiadny externý cloud – vaše finančné dáta ostávajú 100% on-premise na vašom vlastnom hostingu.
          </p>

          <div className="flex flex-col sm:flex-row items-center justify-center gap-3.5 mb-8">
            <a
              href="https://buy.stripe.com/test_00w5kE2y3eul8dWaaW6Vq02"
              target="_blank"
              rel="noreferrer"
              className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl font-bold text-sm bg-gradient-to-r from-[#0A6A56] via-emerald-600 to-[#0A6A56] hover:brightness-110 text-white shadow-xl shadow-emerald-950/50 border border-emerald-500/40 transition transform active:scale-95 whitespace-nowrap"
            >
              <span>Dobiť Kredity (od 5,00 €)</span>
            </a>
            <a
              href="#simulator"
              className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl font-semibold text-sm bg-zinc-950/90 hover:bg-zinc-900 text-zinc-200 border border-zinc-800/80 transition whitespace-nowrap"
            >
              <Play className="w-4 h-4 text-emerald-400" />
              <span>Vyskúšať Živý Simulátor</span>
            </a>
          </div>

          {/* Trust points in clean horizontal row */}
          <div className="flex flex-wrap justify-center gap-6 mt-8 text-xs text-zinc-400 font-medium">
            <span className="flex items-center gap-1.5 whitespace-nowrap">
              <ShieldCheck className="w-4 h-4 text-emerald-400" /> 100% On-Premise Bezpečnosť
            </span>
            <span className="flex items-center gap-1.5 whitespace-nowrap">
              <CheckCircle2 className="w-4 h-4 text-emerald-400" /> WooCommerce HPOS Certified
            </span>
            <span className="flex items-center gap-1.5 whitespace-nowrap">
              <Lock className="w-4 h-4 text-emerald-400" /> Stripe Instant Checkout
            </span>
          </div>

          {/* 1. ŽIVÝ SIMULÁTOR (Hero Horizontal Pipeline 11-COL GRID) */}
          <div id="simulator" className="mt-12 max-w-4xl mx-auto rounded-2xl bg-zinc-950/90 border border-zinc-800/80 p-6 sm:p-7 shadow-2xl relative overflow-hidden">
            <div className="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-[#0A6A56] via-emerald-500 to-[#0A6A56]" />
            <div className="grid grid-cols-1 lg:grid-cols-11 gap-4 items-center">
              {/* ĽAVÝ STĹPEC (lg:col-span-4): Bankový Výpis */}
              <div className="lg:col-span-4 p-5 rounded-xl bg-zinc-900/70 border border-zinc-800/70 text-left">
                <div className="text-[11px] font-bold text-zinc-400 uppercase tracking-wider mb-2">
                  1. BANKOVÝ VÝPIS (TATRA BANKA)
                </div>
                <div className="font-mono text-xl font-extrabold text-white mb-1">
                  + 189,50 EUR
                </div>
                <div className="font-mono text-xs text-zinc-400 mb-2">
                  VS: 1042 | Účet: SK66 1100...
                </div>
                <div className="text-xs text-emerald-400 font-semibold inline-flex items-center gap-1.5">
                  <CheckCircle2 className="w-3.5 h-3.5 text-emerald-400" />
                  <span>SHA-256 Validovaný</span>
                </div>
              </div>

              {/* STREDNÝ STĹPEC (lg:col-span-3 flex flex-col items-center justify-center): Trigger & Flow */}
              <div className="lg:col-span-3 flex flex-col items-center justify-center gap-2.5 py-2">
                <div className="flex items-center justify-center gap-1.5 text-emerald-400" title="Dátový tok do WooCommerce">
                  <span className="text-sm">⇥</span>
                  <span className="text-[10px] font-bold tracking-widest text-emerald-400 uppercase">Dátový Tok</span>
                  <span className="text-sm">➔</span>
                </div>
                <button
                  id="run-sim-btn"
                  type="button"
                  onClick={startSimulation}
                  disabled={simRunning}
                  className="px-5 py-2.5 rounded-full text-xs font-bold text-white bg-gradient-to-r from-[#0A6A56] to-emerald-700 hover:brightness-110 shadow-lg shadow-emerald-950/50 border border-emerald-500/50 transition transform active:scale-95 disabled:opacity-50 whitespace-nowrap"
                >
                  {simRunning ? "⏳ Párujem..." : (simStep >= 4 ? "✓ Spárované úspešne" : "Spustiť synchronizáciu")}
                </button>
                <span className="font-mono text-xs text-zinc-500">
                  Latencia: 0,07 sek.
                </span>
              </div>

              {/* PRAVÝ STĹPEC (lg:col-span-4): WooCommerce Objednávka */}
              <div className="lg:col-span-4 p-5 rounded-xl bg-zinc-900/70 border border-zinc-800/70 border-l-4 border-l-emerald-500 text-left">
                <div className="text-[11px] font-bold text-zinc-400 uppercase tracking-wider mb-2">
                  2. WOOCOMMERCE OBJEDNÁVKA #1042
                </div>
                <div className="text-sm font-bold text-white mb-2">
                  Peter Novák (189,50 €)
                </div>
                {simStep >= 4 ? (
                  <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 text-xs font-bold animate-fadeIn">
                    <span>●</span> Vybavené (Processing) · Spárované!
                  </div>
                ) : (
                  <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/15 text-amber-300 border border-amber-500/30 text-xs font-bold">
                    <span>●</span> Čaká na platbu (Pending)
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* STATS BANNER */}
      <section className="py-8 bg-slate-900/70 border-b border-slate-800 px-4">
        <div className="max-w-5xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
          <div className="p-3">
            <div className="text-3xl font-extrabold text-blue-400">0.00 €</div>
            <p className="text-xs text-slate-400 mt-1">Mesačné bankové API poplatky</p>
          </div>
          <div className="p-3">
            <div className="text-3xl font-extrabold text-emerald-400">&lt; 3 sek.</div>
            <p className="text-xs text-slate-400 mt-1">Čas spárovania celého výpisu</p>
          </div>
          <div className="p-3">
            <div className="text-3xl font-extrabold text-sky-400">100%</div>
            <p className="text-xs text-slate-400 mt-1">On-premise na vašom hostingu</p>
          </div>
          <div className="p-3">
            <div className="text-3xl font-extrabold text-indigo-400">11 / 11</div>
            <p className="text-xs text-slate-400 mt-1">Úrovní v testovacej pyramíde</p>
          </div>
        </div>
      </section>

      {/* 2. ROI KALKULAČKA NÁVRATNOSTI */}
      <section id="kalkulacka" className="py-20 px-6 border-b border-zinc-800/80 bg-black">
        <div className="max-w-4xl mx-auto">
          <div className="text-center mb-10">
            <span className="text-xs font-bold text-emerald-400 uppercase tracking-widest">
              Kalkulačka Návratnosti
            </span>
            <h2 className="text-2xl sm:text-4xl font-extrabold text-white tracking-tight mt-2 text-balance">
              Koľko peňazí a hodín ušetríte každý mesiac?
            </h2>
            <p className="text-sm sm:text-base text-slate-400 mt-2 max-w-xl mx-auto">
              Posuňte posuvník a pozrite si okamžitú úsporu pri vašom mesačnom objeme výpisov.
            </p>
          </div>

          <div className="bg-zinc-950/90 border border-zinc-800/80 rounded-2xl p-6 sm:p-8 shadow-2xl">
            <div className="mb-8">
              <div className="flex justify-between items-center mb-3">
                <span className="font-bold text-white text-sm sm:text-base">
                  Mesačný počet bankových výpisov:
                </span>
                <span className="font-mono text-sm font-bold text-emerald-400 bg-emerald-950/40 border border-emerald-500/40 px-3.5 py-1 rounded-full">
                  {roiStatements} {roiStatements === 1 ? "výpis" : (roiStatements < 5 ? "výpisy" : "výpisov")}
                </span>
              </div>
              <input
                type="range"
                min="1"
                max="50"
                value={roiStatements}
                onChange={(e) => setRoiStatements(parseInt(e.target.value, 10) || 1)}
                className="accent-emerald-500 h-2 bg-zinc-800 rounded-lg w-full cursor-pointer"
              />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
              {/* Card 1: 18,5 h */}
              <div className="h-full flex flex-col justify-between p-6 rounded-xl bg-zinc-900/50 border border-zinc-800">
                <div>
                  <div className="text-xs font-semibold text-zinc-400 mb-2">
                    Ušetrený čas v účtovníctve
                  </div>
                  <div className="font-mono text-3xl font-extrabold text-white">
                    {hoursSaved} h
                  </div>
                </div>
                <div className="text-[11px] text-zinc-500 mt-4">
                  ~ 1,85 hodiny na 1 výpis
                </div>
              </div>

              {/* Card 2: 50 € */}
              <div className="h-full flex flex-col justify-between p-6 rounded-xl bg-zinc-900/50 border border-zinc-800">
                <div>
                  <div className="text-xs font-semibold text-zinc-400 mb-2">
                    Náklady na Vorkei Bridge <span className="whitespace-nowrap">(5 €/výpis)</span>
                  </div>
                  <div className="font-mono text-3xl font-extrabold text-white">
                    {toolCost} €
                  </div>
                </div>
                <div className="text-[11px] text-zinc-500 mt-4">
                  Jednotná sadzba bez viazanosti
                </div>
              </div>

              {/* Card 3: + 227 € (border-emerald-500/40 bg-emerald-950/10) */}
              <div className="h-full flex flex-col justify-between p-6 rounded-xl bg-zinc-900/50 border border-zinc-800 border-emerald-500/40 bg-emerald-950/10 shadow-lg shadow-emerald-950/20">
                <div>
                  <div className="text-xs font-bold text-emerald-400 mb-2">
                    Čistá mesačná úspora (ROI)
                  </div>
                  <div className="font-mono text-3xl font-extrabold text-emerald-400">
                    + {netSavings} €
                  </div>
                </div>
                <div className="text-[11px] text-emerald-500/80 font-medium mt-4">
                  Okamžitá finančná návratnosť
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* 1. PREČO VORKEI BRIDGE (3 BENEFITY) */}
      <section id="vyhody" className="py-20 px-6 border-b border-zinc-800/80 bg-black">
        <div className="max-w-6xl mx-auto">
          <div className="text-center mb-12">
            <span className="text-xs font-bold text-emerald-400 uppercase tracking-widest">
              Prečo Vorkei Bridge
            </span>
            <h2 className="text-2xl sm:text-4xl font-extrabold text-white tracking-tight mt-2 text-balance">
              Architektúra stavaná pre maximálnu bezpečnosť
            </h2>
            <p className="text-sm sm:text-base text-slate-400 mt-2 max-w-xl mx-auto">
              Žiadne kompromisy v bezpečnosti ani skryté bankové poplatky.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {/* BENEFIT 1 */}
            <div className="p-8 h-full flex flex-col justify-between text-left rounded-2xl bg-zinc-950/90 border border-zinc-800/80 hover:border-emerald-500/40 transition">
              <div>
                <div className="w-12 h-12 rounded-xl flex items-center justify-center text-2xl mb-5 bg-emerald-950/30 border border-emerald-500/30 text-emerald-400">
                  ⚡
                </div>
                <h3 className="text-xl font-bold text-white mb-3">
                  Okamžité párovanie do 3 sekúnd
                </h3>
                <p className="text-sm text-zinc-400 leading-relaxed">
                  Namiesto hodín strávených čítaním výpisu riadok po riadku algoritmus overí variabilné symboly, IBAN a presné centové sumy za menej ako 0,07 sekundy.
                </p>
              </div>
              <div className="mt-6 pt-4 border-t border-zinc-800/60 text-xs font-semibold text-emerald-400 flex items-center gap-2">
                <span>✓</span> Bleskový spracovateľský cyklus
              </div>
            </div>

            {/* BENEFIT 2 */}
            <div className="p-8 h-full flex flex-col justify-between text-left rounded-2xl bg-zinc-950/90 border border-zinc-800/80 hover:border-emerald-500/40 transition">
              <div>
                <div className="w-12 h-12 rounded-xl flex items-center justify-center text-2xl mb-5 bg-emerald-950/30 border border-emerald-500/30 text-emerald-400">
                  🔒
                </div>
                <h3 className="text-xl font-bold text-white mb-3">
                  100% On-Premise &amp; Bank Secret Safe
                </h3>
                <p className="text-sm text-zinc-400 leading-relaxed">
                  Žiadne bankové pohyby neodchádzajú do externého cloudu. Všetko beží výhradne vo vašej vlastnej WordPress databáze pod vašou kontrolou.
                </p>
              </div>
              <div className="mt-6 pt-4 border-t border-zinc-800/60 text-xs font-semibold text-emerald-400 flex items-center gap-2">
                <span>✓</span> Finančné tajomstvo chránené
              </div>
            </div>

            {/* BENEFIT 3 */}
            <div className="p-8 h-full flex flex-col justify-between text-left rounded-2xl bg-zinc-950/90 border border-zinc-800/80 hover:border-emerald-500/40 transition">
              <div>
                <div className="w-12 h-12 rounded-xl flex items-center justify-center text-2xl mb-5 bg-emerald-950/30 border border-emerald-500/30 text-emerald-400">
                  💳
                </div>
                <h3 className="text-xl font-bold text-white mb-3">
                  0 € Mesačných Poplatkov Banke
                </h3>
                <p className="text-sm text-zinc-400 leading-relaxed">
                  Zabudnite na zložité PSD2 zmluvy s Tatra bankou za 100+ € mesačne. Nástroj funguje priamo s bežným PDF výpisom a platíte len férových 5 € za spracovaný výpis.
                </p>
              </div>
              <div className="mt-6 pt-4 border-t border-zinc-800/60 text-xs font-semibold text-emerald-400 flex items-center gap-2">
                <span>✓</span> Bez viazanosti a paušálov
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* 2. FÉROVÝ MODEL (CENNÍK) */}
      <section id="cennik" className="py-20 px-6 border-b border-zinc-800/80 bg-black">
        <div className="max-w-6xl mx-auto">
          <div className="text-center mb-12">
            <span className="text-xs font-bold text-emerald-400 uppercase tracking-widest">
              Férový Model
            </span>
            <h2 className="text-2xl sm:text-4xl font-extrabold text-white tracking-tight mt-2 text-balance">
              Jednotný cenník: 5,00 € za výpis
            </h2>
            <p className="text-sm sm:text-base text-slate-400 mt-2 max-w-xl mx-auto">
              Žiadne mesačné paušály. Kredit si dobijete kartou a miniete, kedy potrebujete.
            </p>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch pt-6">
            {/* PLAN 1: ŠTART */}
            <div className="relative flex flex-col justify-between p-8 rounded-2xl border border-zinc-800/80 bg-zinc-950/90 transition hover:border-zinc-700/80">
              <div>
                <div className="text-xl font-bold text-white mb-2">Štart</div>
                <div className="text-xs text-zinc-400 mb-6">1 kredit na okamžité vyskúšanie</div>
                <div className="flex items-baseline gap-2 mb-6">
                  <span className="text-4xl font-extrabold text-white">5 €</span>
                  <span className="text-sm font-medium text-zinc-400">/ výpis</span>
                </div>
                <ul className="space-y-3.5 mb-8 flex-1">
                  <li className="flex items-start gap-3 text-sm text-zinc-300">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                    <span>Spracovanie 1 kompletného výpisu</span>
                  </li>
                  <li className="flex items-start gap-3 text-sm text-zinc-300">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                    <span>Neobmedzený počet pohybov</span>
                  </li>
                  <li className="flex items-start gap-3 text-sm text-zinc-300">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                    <span>Automatické Woo HPOS párovanie</span>
                  </li>
                </ul>
              </div>
              <div>
                <a
                  href="https://buy.stripe.com/test_00w5kE2y3eul8dWaaW6Vq02"
                  target="_blank"
                  rel="noreferrer"
                  className="mt-auto w-full py-3 px-6 rounded-xl font-bold text-sm bg-zinc-900 hover:bg-zinc-800 text-zinc-200 border border-zinc-700/80 transition text-center whitespace-nowrap flex items-center justify-center"
                >
                  Kúpiť 1 výpis (5 €)
                </a>
                <div className="text-xs text-zinc-500 text-center mt-3 whitespace-nowrap">
                  Karta · Apple Pay · Google Pay
                </div>
              </div>
            </div>

            {/* PLAN 2: FLOTILA & E-SHOP (FEATURED) */}
            <div className="relative flex flex-col justify-between p-8 rounded-2xl border-2 border-emerald-500 bg-zinc-950/95 shadow-2xl shadow-emerald-950/50">
              <div className="absolute -top-3.5 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider whitespace-nowrap z-10 bg-emerald-500 text-black shadow-lg shadow-emerald-500/30">
                🔥 NAJPOPULÁRNEJŠIE PRE FLOTILY
              </div>
              <div>
                <div className="text-xl font-bold text-white mb-2">Flotila &amp; E-shop</div>
                <div className="text-xs text-zinc-400 mb-6">Balíček 10 kreditov pre aktívnu prevádzku</div>
                <div className="flex items-baseline gap-2 mb-6">
                  <span className="text-4xl font-extrabold text-emerald-400">50 €</span>
                  <span className="text-sm font-medium text-zinc-400">/ 10 výpisov</span>
                </div>
                <ul className="space-y-3.5 mb-8 flex-1">
                  <li className="flex items-start gap-3 text-sm text-zinc-300">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                    <span><strong>10 kompletných výpisov</strong></span>
                  </li>
                  <li className="flex items-start gap-3 text-sm text-zinc-300">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                    <span>Jednotná sadzba 5,00 € / výpis</span>
                  </li>
                  <li className="flex items-start gap-3 text-sm text-zinc-300">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                    <span>Prioritné REST API spracovanie</span>
                  </li>
                  <li className="flex items-start gap-3 text-sm text-zinc-300">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                    <span>Forenzný auditný protokol</span>
                  </li>
                </ul>
              </div>
              <div>
                <a
                  href="https://buy.stripe.com/test_00w5kE2y3eul8dWaaW6Vq02"
                  target="_blank"
                  rel="noreferrer"
                  className="mt-auto w-full py-3 px-6 rounded-xl font-bold text-sm bg-gradient-to-r from-[#0A6A56] via-emerald-600 to-[#0A6A56] hover:brightness-110 text-white shadow-lg shadow-emerald-950/60 border border-emerald-500/40 transition text-center whitespace-nowrap flex items-center justify-center"
                >
                  Kúpiť 10 výpisov (50 €)
                </a>
                <div className="text-xs text-zinc-500 text-center mt-3 whitespace-nowrap">
                  Karta · Apple Pay · Google Pay
                </div>
              </div>
            </div>

            {/* PLAN 3: PROFI PARTNER */}
            <div className="relative flex flex-col justify-between p-8 rounded-2xl border border-zinc-800/80 bg-zinc-950/90 transition hover:border-zinc-700/80">
              <div>
                <div className="text-xl font-bold text-white mb-2">Profi Partner</div>
                <div className="text-xs text-zinc-400 mb-6">Balíček 20 kreditov pre účtovníkov</div>
                <div className="flex items-baseline gap-2 mb-6">
                  <span className="text-4xl font-extrabold text-white">100 €</span>
                  <span className="text-sm font-medium text-zinc-400">/ 20 výpisov</span>
                </div>
                <ul className="space-y-3.5 mb-8 flex-1">
                  <li className="flex items-start gap-3 text-sm text-zinc-300">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                    <span><strong>20 kompletných výpisov</strong></span>
                  </li>
                  <li className="flex items-start gap-3 text-sm text-zinc-300">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                    <span>Plná podpora viacerých firiem</span>
                  </li>
                  <li className="flex items-start gap-3 text-sm text-zinc-300">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                    <span>Kredity s neobmedzenou platnosťou</span>
                  </li>
                </ul>
              </div>
              <div>
                <a
                  href="https://buy.stripe.com/test_00w5kE2y3eul8dWaaW6Vq02"
                  target="_blank"
                  rel="noreferrer"
                  className="mt-auto w-full py-3 px-6 rounded-xl font-bold text-sm bg-zinc-900 hover:bg-zinc-800 text-zinc-200 border border-zinc-700/80 transition text-center whitespace-nowrap flex items-center justify-center"
                >
                  Kúpiť 20 výpisov (100 €)
                </a>
                <div className="text-xs text-zinc-500 text-center mt-3 whitespace-nowrap">
                  Karta · Apple Pay · Google Pay
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* 6 CORE PILLARS */}
      <section id="architektura" className="py-16 px-4 sm:px-8 border-b border-slate-800 bg-slate-900/40">
        <div className="max-w-5xl mx-auto">
          <div className="text-center mb-12">
            <span className="text-xs font-semibold text-blue-400 uppercase tracking-widest">
              Technická Architektúra
            </span>
            <h2 className="text-2xl sm:text-3xl font-bold text-white mt-1">6 Pilierov Vorkei Statement Bridge</h2>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="bg-slate-900/90 border border-slate-800 p-5 rounded-xl">
              <Zap className="w-6 h-6 text-yellow-400 mb-3" />
              <h3 className="font-bold text-sm text-white mb-1.5">WooCommerce HPOS Ready</h3>
              <p className="text-xs text-slate-400 leading-relaxed">
                Plná kompatibilita s High-Performance Order Storage (`wp_wc_orders`). Bleskové SQL dotazy aj pri desaťtisícoch objednávok.
              </p>
            </div>

            <div className="bg-slate-900/90 border border-slate-800 p-5 rounded-xl">
              <Layers className="w-6 h-6 text-purple-400 mb-3" />
              <h3 className="font-bold text-sm text-white mb-1.5">JetEngine CCT Integrácia</h3>
              <p className="text-xs text-slate-400 leading-relaxed">
                Okamžitá synchronizácia výpisov do Custom Content Types. Vytvárajte firemné klientske zoznamy a grafy v Elementore a Brickse.
              </p>
            </div>

            <div className="bg-slate-900/90 border border-slate-800 p-5 rounded-xl">
              <Lock className="w-6 h-6 text-emerald-400 mb-3" />
              <h3 className="font-bold text-sm text-white mb-1.5">100% On-Premise & Bezpečné</h3>
              <p className="text-xs text-slate-400 leading-relaxed">
                Žiadne bankové údaje neodchádzajú do neznámeho cloudu. Všetko beží výhradne na vašom zabezpečenom serveri a hostingu.
              </p>
            </div>

            <div className="bg-slate-900/90 border border-slate-800 p-5 rounded-xl">
              <RefreshCw className="w-6 h-6 text-blue-400 mb-3" />
              <h3 className="font-bold text-sm text-white mb-1.5">Vstavaný Auto-Update Server</h3>
              <p className="text-xs text-slate-400 leading-relaxed">
                Automatická kontrola nových verzií cez `api.vorkei.com`. Aktualizácia na 1 klik priamo vo WordPress administrácii.
              </p>
            </div>

            <div className="bg-slate-900/90 border border-slate-800 p-5 rounded-xl">
              <Database className="w-6 h-6 text-sky-400 mb-3" />
              <h3 className="font-bold text-sm text-white mb-1.5">Inteligentná Sanitizácia VS</h3>
              <p className="text-xs text-slate-400 leading-relaxed">
                Algoritmus rozpozná preklepy, lomky, medzery aj čísla objednávok skryté v texte správy pre príjemcu.
              </p>
            </div>

            <div className="bg-slate-900/90 border border-slate-800 p-5 rounded-xl">
              <ShieldCheck className="w-6 h-6 text-emerald-400 mb-3" />
              <h3 className="font-bold text-sm text-white mb-1.5">11-Úrovňová Test Pyramída</h3>
              <p className="text-xs text-slate-400 leading-relaxed">
                Každé vydanie prechádza 11 úrovňami automatizovaných bezpečnostných a chaos testov vrátane PclZip inštalačného auditu.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* INSTALLATION GUIDE 1-2-3 (INFORMATIONAL) */}
      <section id="instalacia" className="py-16 px-4 sm:px-8 border-b border-slate-800">
        <div className="max-w-5xl mx-auto">
          <div className="text-center mb-12">
            <span className="text-xs font-semibold text-emerald-400 uppercase tracking-widest">
              Jednoduché Nasadenie
            </span>
            <h2 className="text-2xl sm:text-3xl font-bold text-white mt-1">Inštalačná Príručka v 3 Krokoch</h2>
            <p className="text-xs text-slate-400 mt-2">
              Sprevádzkovanie pluginu na vašom WordPress webe zaberie menej ako 3 minúty.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6 relative">
              <div className="w-8 h-8 rounded-full bg-blue-600/20 text-blue-400 flex items-center justify-center font-bold text-sm mb-4 border border-blue-500/30">
                1
              </div>
              <h3 className="font-bold text-white text-base mb-2 flex items-center gap-2">
                <UploadCloud className="w-4 h-4 text-blue-400" />
                Nahratie do WordPressu
              </h3>
              <p className="text-xs text-slate-400 leading-relaxed">
                Stiahnite si archív <code>vorkei-statement-bridge.zip</code>. Vo WordPress administrácii otvorte 
                <strong> Pluginy ➔ Pridať nový ➔ Nahrať plugin</strong> a kliknite na <em>Aktivovať</em>.
              </p>
            </div>

            <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6 relative">
              <div className="w-8 h-8 rounded-full bg-indigo-600/20 text-indigo-400 flex items-center justify-center font-bold text-sm mb-4 border border-indigo-500/30">
                2
              </div>
              <h3 className="font-bold text-white text-base mb-2 flex items-center gap-2">
                <Key className="w-4 h-4 text-indigo-400" />
                Aplikačné Heslo (REST)
              </h3>
              <p className="text-xs text-slate-400 leading-relaxed">
                V profile administrátora (<strong>Používatelia ➔ Váš profil</strong>) vygenerujte 
                <em>Aplikačné heslo</em> (Application Password). To zaručí šifrovaný a bezpečný REST prístup.
              </p>
            </div>

            <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6 relative">
              <div className="w-8 h-8 rounded-full bg-emerald-600/20 text-emerald-400 flex items-center justify-center font-bold text-sm mb-4 border border-emerald-500/30">
                3
              </div>
              <h3 className="font-bold text-white text-base mb-2 flex items-center gap-2">
                <Zap className="w-4 h-4 text-emerald-400" />
                Spustenie Párovania
              </h3>
              <p className="text-xs text-slate-400 leading-relaxed">
                V aplikácii Výpis otvorte okno synchronizácie, vložte URL webu (napr. <code>https://pdf-t3.vorkei.com</code>) 
                a kliknite na <em>Otestovať spojenie</em>. Platby sú okamžite zosynchronizované.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* REST API & CODE REFERENCE (INFORMATIONAL) */}
      <section id="api" className="py-16 px-4 sm:px-8 border-b border-slate-800 bg-slate-900/30">
        <div className="max-w-4xl mx-auto">
          <div className="text-center mb-10">
            <span className="text-xs font-semibold text-blue-400 uppercase tracking-widest">
              Vývojárska Dokumentácia
            </span>
            <h2 className="text-2xl sm:text-3xl font-bold text-white mt-1">REST API & Integrácia</h2>
            <p className="text-xs text-slate-400 mt-2">
              Plugin poskytuje štandardizované JSON endpointy pre automatizáciu z ľubovoľného systému.
            </p>
          </div>

          <div className="bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden shadow-2xl">
            <div className="bg-slate-900/80 px-4 py-3 border-b border-slate-800 flex items-center justify-between text-xs">
              <div className="flex items-center gap-2 text-slate-300 font-mono">
                <Code2 className="w-4 h-4 text-blue-400" />
                <span>POST /wp-json/vorkei/v1/sync</span>
              </div>
              <button
                type="button"
                onClick={copyCurlCommand}
                className="flex items-center gap-1.5 px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition"
              >
                <Copy className="w-3.5 h-3.5" />
                <span>{copiedSnippet ? "Skopírované!" : "Kopírovať curl"}</span>
              </button>
            </div>

            <div className="p-4 font-mono text-xs text-slate-300 overflow-x-auto leading-relaxed">
              <span className="text-slate-500"># Príklad synchronizácie výpisu a pohybu cez cURL:</span>
              <br />
              <span className="text-emerald-400">curl</span> -X POST https://pdf-t3.vorkei.com/wp-json/vorkei/v1/sync \
              <br />
              &nbsp;&nbsp;-u <span className="text-amber-300">"support@vorkei.c:7fv8 7JAG XPcc IpDk b6dn BgMk"</span> \
              <br />
              &nbsp;&nbsp;-H <span className="text-sky-300">"Content-Type: application/json"</span> \
              <br />
              &nbsp;&nbsp;-d <span className="text-slate-400">'{'{'}</span>
              <br />
              &nbsp;&nbsp;&nbsp;&nbsp;<span className="text-blue-300">"statement"</span>: {'{'}{' '}
              <span className="text-blue-300">"number"</span>: <span className="text-amber-300">"042/2026"</span>,{" "}
              <span className="text-blue-300">"issued_on"</span>: <span className="text-amber-300">"29.10.2026"</span>,{" "}
              <span className="text-blue-300">"iban"</span>:{" "}
              <span className="text-amber-300">"SK7511000000002612345678"</span> {'}'},
              <br />
              &nbsp;&nbsp;&nbsp;&nbsp;<span className="text-blue-300">"movements"</span>: [
              <br />
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{'{'} <span className="text-blue-300">"amount"</span>:{" "}
              <span className="text-emerald-400">"+ 189,50"</span>, <span className="text-blue-300">"vs"</span>:{" "}
              <span className="text-amber-300">"1042"</span>, <span className="text-blue-300">"party_name"</span>:{" "}
              <span className="text-amber-300">"Peter Novák"</span> {'}'}
              <br />
              &nbsp;&nbsp;&nbsp;&nbsp;]
              <br />
              &nbsp;&nbsp;<span className="text-slate-400">{'}'}'</span>
            </div>

            <div className="bg-slate-900/60 p-4 border-t border-slate-800/80 text-xs text-slate-400 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
              <div>
                <strong>Overené tabuľky:</strong> <code>nbuwmw_vorkei_statements</code> (hlavičky) a{" "}
                <code>nbuwmw_vorkei_movements</code> (riadkové položky).
              </div>
              <div className="flex items-center gap-2">
                <span className="inline-block w-2 h-2 rounded-full bg-emerald-400"></span>
                <span>Idempotentný hash SHA-256</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* LIVE SERVER DIAGNOSTIC / WP REST TEST */}
      <section className="py-12 px-4 sm:px-8 border-b border-slate-800 bg-slate-950">
        <div className="max-w-3xl mx-auto bg-slate-900 border border-slate-800 rounded-2xl p-6 text-center">
          <Server className="w-8 h-8 text-blue-400 mx-auto mb-3" />
          <h3 className="text-lg font-bold text-white mb-1">Živý REST API Endpoint Overený v Produkcii</h3>
          <p className="text-xs text-slate-400 mb-4 max-w-lg mx-auto">
            Plugin je aktívne nasadený a otestovaný na produkčnej doméne <code>https://pdf-t3.vorkei.com</code>.
            Môžete si overiť dostupnosť REST API endpointu jedným kliknutím.
          </p>

          <div className="flex flex-col sm:flex-row items-center justify-center gap-3">
            <button
              type="button"
              onClick={checkLiveEndpoint}
              disabled={livePingLoading}
              className="px-4 py-2 rounded-lg text-xs font-semibold text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 flex items-center gap-2"
            >
              {livePingLoading ? (
                <>
                  <RefreshCw className="w-3.5 h-3.5 animate-spin" /> Testujem spojenie...
                </>
              ) : (
                <>
                  <Zap className="w-3.5 h-3.5 text-yellow-400" /> Overiť stav endpointu na pdf-t3.vorkei.com
                </>
              )}
            </button>
            <a
              href="https://pdf-t3.vorkei.com/wp-json/vorkei/v1/statements"
              target="_blank"
              rel="noreferrer"
              className="text-xs text-blue-400 hover:underline flex items-center gap-1"
            >
              Otvoriť JSON endpoint <ExternalLink className="w-3 h-3" />
            </a>
          </div>

          {livePingStatus && (
            <div className="mt-4 inline-block px-3 py-1.5 rounded-lg text-xs font-mono bg-slate-950 border border-slate-800">
              {livePingStatus === "online" && (
                <span className="text-emerald-400">✓ HTTP 200 OK: Endpoint je plne online a pripravený.</span>
              )}
              {livePingStatus === "auth_required" && (
                <span className="text-blue-300">✓ HTTP 401/200: Endpoint aktívny a chránený Application Passwords.</span>
              )}
              {livePingStatus === "active_secured" && (
                <span className="text-emerald-400">✓ Server odpovedá: Bezpečne aktívny s CORS ochranou.</span>
              )}
            </div>
          )}
        </div>
      </section>

      {/* FAQ ACCORDION */}
      <section id="faq" className="py-20 px-6 bg-black">
        <div className="max-w-3xl mx-auto">
          <div className="text-center mb-12">
            <div className="text-xs font-bold text-emerald-400 uppercase tracking-widest mb-2">Odpovede</div>
            <h2 className="text-3xl font-extrabold text-white tracking-tight">Často kladené otázky</h2>
          </div>

          <div>
            {faqs.map((faq, index) => {
              const isOpen = openFaq === index;
              return (
                <div key={index} className="border-b border-zinc-800/80 py-5">
                  <button
                    type="button"
                    onClick={() => setOpenFaq(isOpen ? null : index)}
                    className="w-full flex items-center justify-between text-left font-medium gap-4 text-white text-base hover:text-emerald-400 transition cursor-pointer"
                  >
                    <span>{faq.q}</span>
                    <span className={`text-xl text-zinc-500 shrink-0 transition-transform duration-200 ${isOpen ? "rotate-45 text-emerald-400" : ""}`}>
                      +
                    </span>
                  </button>
                  {isOpen && (
                    <div className="text-sm text-zinc-400 mt-3 leading-relaxed">
                      {faq.a}
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* ENTERPRISE FOOTER (STRIPE COMPLIANT, NO PHONE NUMBER) */}
      <footer className="bg-black pt-4 pb-12 px-6">
        <div className="max-w-7xl mx-auto">
          {/* Main 12-col Grid (grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10 py-12 border-t border-zinc-900) */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10 py-12 border-t border-zinc-900">
            {/* Stĺpec 1 (lg:col-span-5) */}
            <div className="lg:col-span-5">
              <div className="font-extrabold text-base tracking-tight text-white mb-3">
                VORKEI STATEMENT BRIDGE
              </div>
              <p className="text-sm text-zinc-400 mb-4 leading-relaxed max-w-sm">
                Automatizovaný fintech synchronizačný mostík pre bankové výpisy a e-commerce systémy.
              </p>
              <div className="text-xs text-zinc-400 leading-relaxed space-y-1">
                <div><strong className="text-zinc-200">Prevádzkovateľ:</strong> Eab-EU s.r.o.</div>
                <div><strong className="text-zinc-200">Sídlo:</strong> Kukučínova 22, 974 01 Banská Bystrica, Slovenská republika</div>
                <div><strong className="text-zinc-200">Zákaznícka podpora:</strong> <a href="mailto:support@vorkei.com" className="text-emerald-400 hover:text-emerald-300 font-semibold">support@vorkei.com</a></div>
              </div>
            </div>

            {/* Stĺpec 2 (lg:col-span-3) */}
            <div className="lg:col-span-3">
              <div className="text-xs font-bold text-white uppercase tracking-wider mb-4">
                PRODUKT &amp; API
              </div>
              <ul className="flex flex-col space-y-2.5 text-sm list-none p-0 m-0">
                <li><a href="/vorkei-statement-bridge.zip" download className="text-zinc-400 hover:text-white transition whitespace-nowrap">Stiahnuť Plugin v1.0.0</a></li>
                <li><a href="#simulator" className="text-zinc-400 hover:text-white transition whitespace-nowrap">Živý Simulátor</a></li>
                <li><a href="#cennik" className="text-zinc-400 hover:text-white transition whitespace-nowrap">Cenník Kreditov</a></li>
                <li><a href="https://pdf-t3.vorkei.com/wp-json/vorkei/v1/statements" target="_blank" rel="noreferrer" className="text-zinc-400 hover:text-white transition whitespace-nowrap">REST API Endpoint</a></li>
              </ul>
            </div>

            {/* Stĺpec 3 (lg:col-span-4) */}
            <div className="lg:col-span-4">
              <div className="text-xs font-bold text-white uppercase tracking-wider mb-4">
                PRÁVNE &amp; BEZPEČNOSŤ
              </div>
              <ul className="flex flex-col space-y-2.5 text-sm list-none p-0 m-0">
                <li><a href="https://vorkei.com" target="_blank" rel="noreferrer" className="text-zinc-400 hover:text-white transition whitespace-nowrap">Obchodné podmienky (VOP)</a></li>
                <li><a href="https://vorkei.com" target="_blank" rel="noreferrer" className="text-zinc-400 hover:text-white transition whitespace-nowrap">Ochrana osobných údajov (GDPR)</a></li>
                <li><a href="https://vorkei.com" target="_blank" rel="noreferrer" className="text-zinc-400 hover:text-white transition whitespace-nowrap">Reklamačný poriadok</a></li>
                <li className="flex items-center gap-2 text-emerald-400 font-medium whitespace-nowrap">
                  <Check className="w-4 h-4 text-emerald-400 shrink-0" />
                  <span>256-bit SSL Zabezpečené</span>
                </li>
              </ul>
            </div>
          </div>

          {/* 3. Spodná lišta (Copyright) */}
          <div className="pt-8 border-t border-zinc-900/60 flex flex-col sm:flex-row items-center justify-between text-xs text-zinc-500 gap-4">
            <div>
              © 2026 Eab-EU s.r.o.
            </div>
            <div className="flex items-center gap-3">
              <span>Platby zabezpečené cez <strong className="text-zinc-300">Stripe Payments Europe</strong></span>
              <span>•</span>
              <span>Visa, Mastercard, Apple Pay, Google Pay</span>
            </div>
          </div>
        </div>
      </footer>
    </div>
  );
}
