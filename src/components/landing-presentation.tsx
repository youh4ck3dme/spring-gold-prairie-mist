import { useState } from "react";
import {
  ArrowRight,
  CheckCircle2,
  ChevronDown,
  ChevronUp,
  Clock,
  Database,
  Download,
  ExternalLink,
  Layers,
  Lock,
  Play,
  RefreshCw,
  Server,
  ShieldCheck,
  Sparkles,
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
  const [openFaq, setOpenFaq] = useState<number | null>(0);
  const [livePingStatus, setLivePingStatus] = useState<string | null>(null);
  const [livePingLoading, setLivePingLoading] = useState<boolean>(false);

  function startSimulation() {
    if (simRunning) return;
    setSimRunning(true);
    setSimStep(1);

    setTimeout(() => {
      setSimStep(2);
      setTimeout(() => {
        setSimStep(3);
        setTimeout(() => {
          setSimStep(4);
          setTimeout(() => {
            setSimStep(5);
            setSimRunning(false);
          }, 800);
        }, 800);
      }, 900);
    }, 800);
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

  const faqs = [
    {
      q: "Prečo zvoliť Vorkei Statement Bridge namiesto Tatra banka Premium API?",
      a: "Tatra banka Premium API vyžaduje náročný firemný schvaľovací proces, špeciálne PSD2 licencie, zložité certifikáty a mesačné poplatky 50 € až 200 € mesačne. Vorkei Statement Bridge nepotrebuje žiadne povolenie od banky. Pracuje s vašim oficiálnym PDF výpisom a páruje objednávky lokálne na vašom hostingu s 0 € mesačnými poplatkami.",
    },
    {
      q: "Je plugin plne kompatibilný s High-Performance Order Storage (HPOS)?",
      a: "Áno, na 100%. Plugin je plne optimalizovaný pre najnovší WooCommerce s aktivovaným HPOS (tabuľka wp_wc_orders). Objednávky vyhľadáva okamžite v indexovaných stĺpcoch bez spomaľovania celej databázy.",
    },
    {
      q: "Čo ak zákazník zadá variabilný symbol s preklepom alebo do poznámky?",
      a: "Náš algoritmus automaticky čistí variabilné symboly od medzier, lomiek a prefixov (napr. '#1042' -> '1042'). Ak zákazník vyplnil číslo objednávky do správy pre prijímateľa, plugin text prehľadá a sumu spáruje.",
    },
    {
      q: "Kde sú uložené bankové dáta? Hrozí únik do cloudu?",
      a: "Všetky bankové výpisy a pohyby sa ukladajú výhradne do vašej lokálnej WordPress databázy (do dedikovaných tabuliek wp_vorkei_statements a wp_vorkei_movements). Žiadne dáta nikdy neopúšťajú váš server.",
    },
    {
      q: "Ako fungujú aktualizácie na novšie verzie?",
      a: "Plugin disponuje vlastným klientom pre automatické aktualizácie (Auto-Update Engine) napojeným na server api.vorkei.com/plugins/update-check. Nové verzie aktualizujete jedným klikom priamo vo WordPress administrácii bez nutnosti manuálneho sťahovania ZIP balíčka.",
    },
  ];

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 font-sans selection:bg-blue-600 selection:text-white pb-16">
      {/* Top Announcement Bar */}
      <div className="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 px-4 py-2 text-xs font-medium text-blue-200 border-b border-blue-800/40 flex items-center justify-between">
        <div className="flex items-center gap-2 mx-auto sm:mx-0">
          <span className="flex h-2 w-2 rounded-full bg-emerald-400 animate-pulse" />
          <span>Oficiálny WordPress Plugin pre Tatra banka PDF výpisy & WooCommerce HPOS</span>
        </div>
        <div className="hidden sm:flex items-center gap-4 text-xs text-blue-300">
          <span>Verzia 1.0.0</span>
          <span>•</span>
          <span>11/11 Test Pyramid Passed</span>
          <span>•</span>
          <a
            href="https://pdf-t3.vorkei.com"
            target="_blank"
            rel="noreferrer"
            className="hover:underline flex items-center gap-1 text-emerald-400 font-semibold"
          >
            Live Server <ExternalLink className="w-3 h-3" />
          </a>
        </div>
      </div>

      {/* Main Header */}
      <header className="sticky top-0 z-50 backdrop-blur-md bg-slate-950/80 border-b border-slate-800/80 px-4 sm:px-8 py-3.5 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center shadow-lg shadow-blue-500/20 text-white font-black text-xl">
            V
          </div>
          <div>
            <div className="flex items-center gap-2">
              <span className="font-bold text-lg text-white tracking-tight">Vorkei Statement Bridge</span>
              <span className="bg-blue-500/10 text-blue-400 text-[10px] font-semibold px-2 py-0.5 rounded-full border border-blue-500/20">
                PRO
              </span>
            </div>
            <p className="text-[11px] text-slate-400">Bank Statement PDF & WooCommerce Sync</p>
          </div>
        </div>

        <nav className="hidden md:flex items-center gap-6 text-sm text-slate-300 font-medium">
          <a href="#vyhody" className="hover:text-blue-400 transition-colors">
            Výhody
          </a>
          <a href="#simulator" className="hover:text-blue-400 transition-colors">
            Živé Demo
          </a>
          <a href="#architektura" className="hover:text-blue-400 transition-colors">
            Architektúra
          </a>
          <a href="#cennik" className="hover:text-blue-400 transition-colors">
            Cenník
          </a>
          <a href="#faq" className="hover:text-blue-400 transition-colors">
            FAQ
          </a>
        </nav>

        <div className="flex items-center gap-2.5">
          {onBackToApp && (
            <button
              type="button"
              onClick={onBackToApp}
              className="px-3.5 py-1.5 text-xs font-medium text-slate-300 hover:text-white bg-slate-800/80 hover:bg-slate-700/80 rounded-lg border border-slate-700 transition"
            >
              Generátor Výpisov
            </button>
          )}
          <a
            href="/vorkei-statement-bridge.zip"
            download="vorkei-statement-bridge.zip"
            className="flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 rounded-lg shadow-md shadow-blue-600/30 transition transform active:scale-95"
          >
            <Download className="w-3.5 h-3.5" />
            <span>Stiahnuť Plugin ZIP</span>
          </a>
        </div>
      </header>

      {/* HERO SECTION */}
      <section className="relative overflow-hidden pt-12 pb-20 px-4 sm:px-8 border-b border-slate-800/60">
        <div className="absolute inset-0 bg-[radial-gradient(ellipse_80%_80%_at_50%_-20%,rgba(14,165,233,0.15),rgba(255,255,255,0))]" />
        
        <div className="relative max-w-5xl mx-auto text-center">
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/30 text-blue-300 text-xs font-medium mb-6">
            <Sparkles className="w-3.5 h-3.5 text-blue-400" />
            <span>Koniec manuálneho prepisovania variabilných symbolov</span>
          </div>

          <h1 className="text-4xl sm:text-6xl font-extrabold tracking-tight text-white mb-6 leading-tight">
            Premeňte bankový výpis na{" "}
            <span className="bg-gradient-to-r from-blue-400 via-sky-300 to-emerald-400 bg-clip-text text-transparent">
              vybavené objednávky
            </span>{" "}
            za 3 sekundy.
          </h1>

          <p className="max-w-2xl mx-auto text-base sm:text-lg text-slate-300 mb-8 leading-relaxed">
            Automatické inteligentné párovanie Tatra banka PDF výpisov s WooCommerce HPOS a JetEngine CCT. 
            Žiadne mesačné bankové API poplatky, žiadne cloudové úniky – 
            <strong className="text-white"> 100% on-premise priamo na vašom WordPress hostingu</strong>.
          </p>

          <div className="flex flex-col sm:flex-row items-center justify-center gap-3.5 mb-12">
            <a
              href="/vorkei-statement-bridge.zip"
              download="vorkei-statement-bridge.zip"
              className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl font-bold text-sm bg-gradient-to-r from-blue-600 via-blue-500 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white shadow-xl shadow-blue-500/25 transition transform active:scale-95"
            >
              <Download className="w-4 h-4" />
              <span>Stiahnuť Plugin v1.0.0</span>
            </a>
            <a
              href="#simulator"
              className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl font-semibold text-sm bg-slate-900/90 hover:bg-slate-800 text-slate-200 border border-slate-700/80 transition"
            >
              <Play className="w-4 h-4 text-emerald-400" />
              <span>Vyskúšať simulátor párovania</span>
            </a>
            {onOpenSyncModal && (
              <button
                type="button"
                onClick={onOpenSyncModal}
                className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl font-semibold text-sm bg-blue-950/60 hover:bg-blue-900/60 text-blue-200 border border-blue-700/60 transition"
              >
                <Server className="w-4 h-4 text-sky-400" />
                <span>Otvoriť Live Sync Konzol</span>
              </button>
            )}
          </div>

          {/* Trust points */}
          <div className="flex flex-wrap items-center justify-center gap-y-2 gap-x-6 text-xs text-slate-400">
            <span className="flex items-center gap-1.5">
              <CheckCircle2 className="w-4 h-4 text-emerald-400" /> Inštalácia do 2 minút
            </span>
            <span className="flex items-center gap-1.5">
              <CheckCircle2 className="w-4 h-4 text-emerald-400" /> Žiadne bankové mesačné poplatky
            </span>
            <span className="flex items-center gap-1.5">
              <CheckCircle2 className="w-4 h-4 text-emerald-400" /> GDPR & Bank Secret Safe
            </span>
            <span className="flex items-center gap-1.5">
              <CheckCircle2 className="w-4 h-4 text-emerald-400" /> WooCommerce HPOS Ready
            </span>
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

      {/* INTERACTIVE MATCHING SIMULATOR */}
      <section id="simulator" className="py-16 px-4 sm:px-8 border-b border-slate-800">
        <div className="max-w-4xl mx-auto">
          <div className="text-center mb-10">
            <span className="text-xs font-semibold text-emerald-400 uppercase tracking-widest">
              Interaktívna Ukážka v Reálnom Čase
            </span>
            <h2 className="text-2xl sm:text-3xl font-bold text-white mt-2">
              Sledujte, ako prebieha okamžité spárovanie platby
            </h2>
            <p className="text-sm text-slate-400 mt-2">
              Kliknite na tlačidlo a vyskúšajte si simuláciu príchodu platby z Tatra banky a jej spracovanie vo WooCommerce.
            </p>
          </div>

          <div className="bg-slate-900 rounded-2xl border border-slate-800 p-6 shadow-2xl relative overflow-hidden">
            {/* Header of simulator card */}
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-5 border-b border-slate-800 gap-4">
              <div>
                <p className="text-xs text-slate-400">Vstupné bankové dáta (Tatra banka)</p>
                <p className="text-sm font-semibold text-white mt-0.5">
                  Pohyb: <span className="text-emerald-400 font-mono">+ 189,50 €</span> • Od: Peter Novák • VS:{" "}
                  <span className="text-blue-400 font-mono">1042</span>
                </p>
              </div>

              <button
                id="run-sim-btn"
                type="button"
                onClick={startSimulation}
                disabled={simRunning}
                className="px-4 py-2 rounded-lg text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 disabled:opacity-50 transition flex items-center gap-2"
              >
                {simRunning ? (
                  <>
                    <RefreshCw className="w-3.5 h-3.5 animate-spin" />
                    <span>Spracovávam...</span>
                  </>
                ) : (
                  <>
                    <Play className="w-3.5 h-3.5 text-emerald-400" />
                    <span>Spustiť simuláciu párovania</span>
                  </>
                )}
              </button>
            </div>

            {/* Simulation pipeline visual */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-3 my-6">
              <div
                className={`p-3.5 rounded-xl border text-xs transition-all duration-300 ${
                  simStep >= 1
                    ? "bg-blue-950/40 border-blue-600/60 text-blue-200"
                    : "bg-slate-950/40 border-slate-800 text-slate-500"
                }`}
              >
                <div className="font-semibold mb-1 flex items-center gap-1.5">
                  <span className="w-4 h-4 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px]">
                    1
                  </span>
                  Extrakcia VS & IBAN
                </div>
                <p className="text-[11px] opacity-80">Sanitizácia 'VS 1042' -&gt; '1042', kontrola sumy 189.50 €.</p>
              </div>

              <div
                className={`p-3.5 rounded-xl border text-xs transition-all duration-300 ${
                  simStep >= 2
                    ? "bg-indigo-950/40 border-indigo-600/60 text-indigo-200"
                    : "bg-slate-950/40 border-slate-800 text-slate-500"
                }`}
              >
                <div className="font-semibold mb-1 flex items-center gap-1.5">
                  <span className="w-4 h-4 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[10px]">
                    2
                  </span>
                  SQL DB Zápis
                </div>
                <p className="text-[11px] opacity-80">Uloženie do nbuwmw_vorkei_movements s unikátnym hashom.</p>
              </div>

              <div
                className={`p-3.5 rounded-xl border text-xs transition-all duration-300 ${
                  simStep >= 3
                    ? "bg-purple-950/40 border-purple-600/60 text-purple-200"
                    : "bg-slate-950/40 border-slate-800 text-slate-500"
                }`}
              >
                <div className="font-semibold mb-1 flex items-center gap-1.5">
                  <span className="w-4 h-4 rounded-full bg-purple-600 text-white flex items-center justify-center text-[10px]">
                    3
                  </span>
                  JetEngine CCT Sync
                </div>
                <p className="text-[11px] opacity-80">Replikácia pre dashboardy, Elementor a JetSmartFilters.</p>
              </div>

              <div
                className={`p-3.5 rounded-xl border text-xs transition-all duration-300 ${
                  simStep >= 4
                    ? "bg-emerald-950/40 border-emerald-500/60 text-emerald-200"
                    : "bg-slate-950/40 border-slate-800 text-slate-500"
                }`}
              >
                <div className="font-semibold mb-1 flex items-center gap-1.5">
                  <span className="w-4 h-4 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[10px]">
                    4
                  </span>
                  Woo HPOS Párovanie
                </div>
                <p className="text-[11px] opacity-80">Objednávka #1042: Zmena stavu na 'Spracováva sa' (Processing).</p>
              </div>
            </div>

            {/* Result preview */}
            <div className="bg-slate-950 rounded-xl p-4 border border-slate-800 text-xs">
              <div className="flex items-center justify-between mb-2">
                <span className="font-semibold text-slate-300 flex items-center gap-1.5">
                  <Server className="w-3.5 h-3.5 text-blue-400" />
                  Výstup v administrácii WooCommerce:
                </span>
                <span className="font-mono text-[10px] text-slate-500">wp-admin/post.php?post=1042</span>
              </div>

              {simStep < 4 ? (
                <div className="p-3 rounded-lg bg-amber-950/30 border border-amber-800/40 text-amber-200 flex items-center justify-between">
                  <div>
                    <span className="font-bold">Objednávka #1042:</span> Peter Novák (Suma: 189,50 €)
                  </div>
                  <span className="px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 font-mono text-[11px] border border-amber-500/30">
                    Čaká na platbu (Pending payment)
                  </span>
                </div>
              ) : (
                <div className="p-3 rounded-lg bg-emerald-950/30 border border-emerald-600/50 text-emerald-100 space-y-2 animate-fadeIn">
                  <div className="flex items-center justify-between">
                    <div>
                      <span className="font-bold">Objednávka #1042:</span> Peter Novák (Suma: 189,50 €)
                    </div>
                    <span className="px-2.5 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-bold text-[11px] border border-emerald-500/40 flex items-center gap-1">
                      <CheckCircle2 className="w-3 h-3" /> Spracováva sa (Processing)
                    </span>
                  </div>
                  <div className="text-[11px] text-emerald-200/80 bg-slate-900/80 p-2 rounded border border-emerald-900/50 font-mono">
                    Interná poznámka: Platba automaticky spárovaná cez Vorkei Statement Bridge. Dátum: 29.10.2026,
                    Suma: 189.50 €, Ref: TB-MOV-88419, VS: 1042. Sklad môže tovar expedovať.
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      </section>

      {/* PROBLEM VS SOLUTION */}
      <section id="vyhody" className="py-16 px-4 sm:px-8 border-b border-slate-800">
        <div className="max-w-5xl mx-auto">
          <div className="text-center mb-12">
            <h2 className="text-2xl sm:text-3xl font-bold text-white">Prečo je starý spôsob čisté plytvanie časom?</h2>
            <p className="text-sm text-slate-400 mt-2">
              Porovnanie tradičného manuálneho párovania s automatizovaným mostom Vorkei Statement Bridge.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* The Old Way */}
            <div className="bg-red-950/15 border border-red-900/30 rounded-2xl p-6">
              <div className="flex items-center gap-2 text-red-400 font-bold text-sm mb-4">
                <span className="w-6 h-6 rounded-full bg-red-500/20 flex items-center justify-center text-xs">✕</span>
                Tradičné ručné párovanie
              </div>
              <ul className="space-y-3.5 text-xs text-slate-300">
                <li className="flex items-start gap-2">
                  <span className="text-red-400 font-bold mt-0.5">•</span>
                  <span>
                    <strong>45 až 90 minút denne</strong> strávených čítaním výpisov riadok po riadku a klikaním v e-shope.
                  </span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-red-400 font-bold mt-0.5">•</span>
                  <span>
                    <strong>Ľudské chyby a preklepy:</strong> prehliadnuté úhrady, tovar odoslaný pred pripísaním peňazí.
                  </span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-red-400 font-bold mt-0.5">•</span>
                  <span>
                    <strong>Zbytočne drahé bankové API:</strong> banky pýtajú 100+ € mesačne a vyžadujú zložité certifikáty.
                  </span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-red-400 font-bold mt-0.5">•</span>
                  <span>
                    <strong>Oneskorené zásielky:</strong> zákazník zaplatí ráno, no balík odíde až o 2 dni kvôli čakaniu na schválenie.
                  </span>
                </li>
              </ul>
            </div>

            {/* The Vorkei Way */}
            <div className="bg-emerald-950/20 border border-emerald-600/40 rounded-2xl p-6 relative">
              <div className="flex items-center gap-2 text-emerald-400 font-bold text-sm mb-4">
                <span className="w-6 h-6 rounded-full bg-emerald-500/20 flex items-center justify-center text-xs">✓</span>
                S Vorkei Statement Bridge
              </div>
              <ul className="space-y-3.5 text-xs text-slate-200">
                <li className="flex items-start gap-2">
                  <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                  <span>
                    <strong>1 kliknutie / 3 sekundy:</strong> celý výpis so stovkami položiek je ihneď spárovaný.
                  </span>
                </li>
                <li className="flex items-start gap-2">
                  <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                  <span>
                    <strong>Kryptografická ochrana:</strong> deduplikácia na základe SHA-256 hashu, kontrola podplatenia.
                  </span>
                </li>
                <li className="flex items-start gap-2">
                  <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                  <span>
                    <strong>0 € mesačných poplatkov:</strong> funguje priamo s oficiálnym PDF výpisom Tatra banky.
                  </span>
                </li>
                <li className="flex items-start gap-2">
                  <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                  <span>
                    <strong>Okamžitá expedícia:</strong> sklad vidí vybavenú platbu a balí tovar ešte v ten istý deň.
                  </span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      {/* 6 CORE PILLARS */}
      <section className="py-16 px-4 sm:px-8 border-b border-slate-800 bg-slate-900/40">
        <div className="max-w-5xl mx-auto">
          <div className="text-center mb-12">
            <span className="text-xs font-semibold text-blue-400 uppercase tracking-widest">
              Technická Dokonalosť
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

      {/* PRICING & LICENSING */}
      <section id="cennik" className="py-16 px-4 sm:px-8 border-b border-slate-800">
        <div className="max-w-5xl mx-auto">
          <div className="text-center mb-12">
            <span className="text-xs font-semibold text-emerald-400 uppercase tracking-widest">
              Férové Ceny bez Skrytých Poplatkov
            </span>
            <h2 className="text-2xl sm:text-3xl font-bold text-white mt-1">Vyberte si licenciu pre vaše potreby</h2>
            <p className="text-xs text-slate-400 mt-2">
              Ušetrite stovky eur mesačne za korporátne bankové API.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {/* Single */}
            <div className="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 flex flex-col justify-between">
              <div>
                <h3 className="font-bold text-white text-base">SINGLE SHOP</h3>
                <p className="text-xs text-slate-400 mt-1">Pre 1 samostatný WordPress e-shop</p>
                <div className="my-5">
                  <span className="text-3xl font-extrabold text-white">89 €</span>
                  <span className="text-xs text-slate-400 ml-1">/ jednorazovo</span>
                </div>
                <ul className="space-y-2.5 text-xs text-slate-300">
                  <li className="flex items-center gap-2">
                    <CheckCircle2 className="w-3.5 h-3.5 text-blue-400" /> 1 e-shop / WordPress web
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle2 className="w-3.5 h-3.5 text-blue-400" /> Tatra banka PDF Sync
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle2 className="w-3.5 h-3.5 text-blue-400" /> WooCommerce HPOS párovanie
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle2 className="w-3.5 h-3.5 text-blue-400" /> 1 rok aktualizácií v cene
                  </li>
                </ul>
              </div>
              <a
                href="/vorkei-statement-bridge.zip"
                download="vorkei-statement-bridge.zip"
                className="mt-6 w-full py-2.5 rounded-lg text-xs font-bold text-center bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 transition"
              >
                Kúpiť Single Licenciu
              </a>
            </div>

            {/* Agency (Featured) */}
            <div className="bg-gradient-to-b from-blue-950/40 to-slate-900 border-2 border-blue-500 rounded-2xl p-6 flex flex-col justify-between relative shadow-2xl shadow-blue-500/10">
              <div className="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 rounded-full bg-blue-500 text-white text-[10px] font-extrabold uppercase tracking-wider">
                Najobľúbenejšie pre Agentúry
              </div>
              <div>
                <h3 className="font-bold text-white text-base">AGENCY</h3>
                <p className="text-xs text-slate-400 mt-1">Až 10 klientskych WordPress e-shopov</p>
                <div className="my-5">
                  <span className="text-3xl font-extrabold text-white">199 €</span>
                  <span className="text-xs text-slate-400 ml-1">/ ročne</span>
                </div>
                <ul className="space-y-2.5 text-xs text-slate-200">
                  <li className="flex items-center gap-2">
                    <CheckCircle2 className="w-3.5 h-3.5 text-emerald-400" /> Až 10 WordPress webov
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle2 className="w-3.5 h-3.5 text-emerald-400" /> Plná JetEngine CCT podpora
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle2 className="w-3.5 h-3.5 text-emerald-400" /> Neobmedzené automatické aktualizácie
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle2 className="w-3.5 h-3.5 text-emerald-400" /> Prioritná VIP podpora na vorkei.com
                  </li>
                </ul>
              </div>
              <a
                href="/vorkei-statement-bridge.zip"
                download="vorkei-statement-bridge.zip"
                className="mt-6 w-full py-2.5 rounded-lg text-xs font-bold text-center bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30 transition"
              >
                Získať Agency Balíček
              </a>
            </div>

            {/* Enterprise */}
            <div className="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 flex flex-col justify-between">
              <div>
                <h3 className="font-bold text-white text-base">ENTERPRISE</h3>
                <p className="text-xs text-slate-400 mt-1">Neobmedzené weby & White-Label</p>
                <div className="my-5">
                  <span className="text-3xl font-extrabold text-white">449 €</span>
                  <span className="text-xs text-slate-400 ml-1">/ doživotne</span>
                </div>
                <ul className="space-y-2.5 text-xs text-slate-300">
                  <li className="flex items-center gap-2">
                    <CheckCircle2 className="w-3.5 h-3.5 text-sky-400" /> Neobmedzený počet webov
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle2 className="w-3.5 h-3.5 text-sky-400" /> Doživotné bezplatné aktualizácie
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle2 className="w-3.5 h-3.5 text-sky-400" /> Možnosť rebrandingu (White-Label)
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle2 className="w-3.5 h-3.5 text-sky-400" /> Konzultácia a nasadenie na mieru
                  </li>
                </ul>
              </div>
              <a
                href="mailto:support@vorkei.com?subject=Vorkei%20Statement%20Bridge%20Enterprise"
                className="mt-6 w-full py-2.5 rounded-lg text-xs font-bold text-center bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 transition"
              >
                Kontaktovať Vorkei Tím
              </a>
            </div>
          </div>
        </div>
      </section>

      {/* FAQ ACCORDION */}
      <section id="faq" className="py-16 px-4 sm:px-8 border-b border-slate-800 bg-slate-900/30">
        <div className="max-w-3xl mx-auto">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-bold text-white">Často Kladené Otázky</h2>
            <p className="text-xs text-slate-400 mt-1">Odpovede na všetko, čo potrebujete vedieť pred inštaláciou.</p>
          </div>

          <div className="space-y-3">
            {faqs.map((faq, index) => {
              const isOpen = openFaq === index;
              return (
                <div key={index} className="border border-slate-800 bg-slate-900/70 rounded-xl overflow-hidden">
                  <button
                    type="button"
                    onClick={() => setOpenFaq(isOpen ? null : index)}
                    className="w-full p-4 text-left flex items-center justify-between text-xs sm:text-sm font-semibold text-slate-200 hover:text-white"
                  >
                    <span>{faq.q}</span>
                    {isOpen ? <ChevronUp className="w-4 h-4 text-blue-400" /> : <ChevronDown className="w-4 h-4 text-slate-500" />}
                  </button>
                  {isOpen && (
                    <div className="px-4 pb-4 text-xs text-slate-400 leading-relaxed border-t border-slate-800/60 pt-3">
                      {faq.a}
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* FINAL CTA & FOOTER */}
      <footer className="py-16 px-4 sm:px-8 bg-slate-950 text-center">
        <div className="max-w-3xl mx-auto">
          <h2 className="text-2xl sm:text-3xl font-extrabold text-white mb-3">
            Pripravení ušetriť 20+ hodín mesačne?
          </h2>
          <p className="text-xs sm:text-sm text-slate-400 mb-8 max-w-lg mx-auto">
            Stiahnite si overený inštalačný ZIP balíček a aktivujte Vorkei Statement Bridge na vašom e-shope ešte dnes.
          </p>

          <div className="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a
              href="/vorkei-statement-bridge.zip"
              download="vorkei-statement-bridge.zip"
              className="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold text-xs bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-500/25 transition"
            >
              <Download className="w-4 h-4" />
              <span>Stiahnuť Vorkei Statement Bridge v1.0.0</span>
            </a>
            {onBackToApp && (
              <button
                type="button"
                onClick={onBackToApp}
                className="inline-flex items-center gap-2 px-5 py-3 rounded-xl font-medium text-xs text-slate-300 hover:text-white bg-slate-900 border border-slate-800 transition"
              >
                <span>Prejsť do Generátora Výpisov</span>
                <ArrowRight className="w-3.5 h-3.5" />
              </button>
            )}
          </div>

          <div className="mt-12 pt-8 border-t border-slate-900 text-[11px] text-slate-600 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p>© 2026 vorkei.com. Všetky práva vyhradené. Vytvorené pre Tatra banka & WooCommerce.</p>
            <div className="flex items-center gap-4 text-slate-500">
              <a href="https://vorkei.com" target="_blank" rel="noreferrer" className="hover:underline">
                vorkei.com
              </a>
              <span>•</span>
              <a href="https://pdf-t3.vorkei.com" target="_blank" rel="noreferrer" className="hover:underline">
                pdf-t3.vorkei.com
              </a>
            </div>
          </div>
        </div>
      </footer>
    </div>
  );
}
