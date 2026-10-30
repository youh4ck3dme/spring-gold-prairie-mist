#!/usr/bin/env python3
"""
Deploy Ultra-Minimalist Dark (+150% Visual) SaaS Landing Page to WordPress CMS on https://pdf-t3.vorkei.com
Strict UI/UX rules:
1. Container: max-w-7xl mx-auto px-6. All nav links, logo & CTA: whitespace-nowrap.
2. Logo & Brand: grouped into single flex container (flex items-center gap-2.5) with icon "V", "Vorkei Statement Bridge" & "PRO".
3. Hero text fix: "Dobiť Kredity (od 5,00 €)".
4. Hero layout: H1 tracking-tight text-balance, subtitle max-w-2xl mx-auto text-slate-400, trust badges flex flex-wrap justify-center gap-6 mt-8.
5. Dark theme: #000000 background, bg-zinc-950/90 cards, border-zinc-800/80, #0A6A56 & emerald-500 accents.
"""
import json
import base64
import urllib.request
import urllib.error

WP_URL = "https://pdf-t3.vorkei.com"
WP_USER = "support@vorkei.c"
WP_APP_PASS = "7fv8 7JAG XPcc IpDk b6dn BgMk"
PLUGIN_ZIP_URL = "https://pdf-t3.vorkei.com/wp-content/uploads/2026/10/vorkei-statement-bridge.zip"
STRIPE_10_URL = "https://buy.stripe.com/test_00w5kE2y3eul8dWaaW6Vq02"
STRIPE_PALICOX_URL = "https://buy.stripe.com/test_cNieVegoTae5bq81Eq6Vq04"

auth_header = "Basic " + base64.b64encode(f"{WP_USER}:{WP_APP_PASS}".encode()).decode()

html_content = f"""<!-- wp:html -->
<style>
/* -------------------------------------------------------------
   ULTRA-MINIMALIST FINTECH DARK DESIGN SYSTEM (#000000 / #0A6A56)
------------------------------------------------------------- */
:root {{
  --v-bg: #000000;
  --v-bg-card: rgba(9, 9, 11, 0.92);
  --v-bg-card-subtle: rgba(18, 18, 22, 0.7);
  --v-border: rgba(39, 39, 42, 0.8);
  --v-border-hover: rgba(16, 185, 129, 0.45);
  --v-text-head: #ffffff;
  --v-text-body: #e4e4e7;
  --v-text-muted: #94a3b8;
  --v-text-subtle: #71717a;
  --v-fintech-dark: #0A6A56;
  --v-emerald: #10b981;
  --v-emerald-glow: rgba(16, 185, 129, 0.2);
  --v-emerald-subtle: rgba(10, 106, 86, 0.15);
  --v-emerald-border: rgba(16, 185, 129, 0.35);
  --v-radius-sm: 8px;
  --v-radius-md: 14px;
  --v-radius-lg: 20px;
  --v-shadow-glow: 0 0 35px -5px rgba(10, 106, 86, 0.35);
}}

.vorkei-wrap {{
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  color: var(--v-text-body);
  background: var(--v-bg);
  background-image: 
    radial-gradient(ellipse 80% 50% at 50% -12%, rgba(10, 106, 86, 0.28), transparent 70%),
    radial-gradient(ellipse 60% 40% at 90% 20%, rgba(16, 185, 129, 0.08), transparent 60%);
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;
  padding: 0;
  margin: 0;
  overflow-x: hidden;
}}

.v-mono {{
  font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}}

/* CONTAINER: max-w-7xl mx-auto px-6 */
.v-container {{
  max-width: 80rem; /* 1280px / max-w-7xl */
  margin-left: auto;
  margin-right: auto;
  padding-left: 1.5rem; /* px-6 */
  padding-right: 1.5rem; /* px-6 */
}}

/* TOP ANNOUNCEMENT BAR */
.v-topbar {{
  background: rgba(0, 0, 0, 0.9);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--v-border);
  font-size: 0.8125rem;
  padding: 0.55rem 0;
  color: var(--v-text-muted);
}}
.v-topbar-flex {{
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}}
.v-topbar-badge {{
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: #f4f4f5;
  white-space: nowrap !important;
}}
.v-dot-live {{
  width: 8px;
  height: 8px;
  background-color: var(--v-emerald);
  border-radius: 50%;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
  display: inline-block;
  animation: vPulse 2s infinite;
  flex-shrink: 0;
}}
@keyframes vPulse {{
  0% {{ box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.6); }}
  70% {{ box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }}
  100% {{ box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }}
}}

/* NAVIGATION */
.v-nav {{
  background: rgba(0, 0, 0, 0.88);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--v-border);
  position: sticky;
  top: 0;
  z-index: 50;
  padding: 0.875rem 0;
}}
.v-nav-flex {{
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
}}

/* RULE 2: LOGO & BRAND (flex items-center gap-2.5) */
.v-brand {{
  display: inline-flex !important;
  align-items: center !important;
  gap: 0.625rem !important; /* gap-2.5 */
  text-decoration: none !important;
  white-space: nowrap !important;
  flex-shrink: 0;
}}
.v-logo-box {{
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, #0A6A56 0%, #044b3c 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 1.15rem;
  box-shadow: 0 0 16px rgba(10, 106, 86, 0.5);
  border: 1px solid rgba(16, 185, 129, 0.3);
  flex-shrink: 0;
}}
.v-brand-title {{
  font-size: 1.1rem;
  font-weight: 700;
  color: #ffffff;
  letter-spacing: -0.4px;
  white-space: nowrap !important;
  margin: 0;
}}
.v-badge-pro {{
  font-size: 0.6875rem;
  font-weight: 700;
  background: rgba(10, 106, 86, 0.25);
  color: #10b981;
  border: 1px solid rgba(16, 185, 129, 0.4);
  padding: 0.15rem 0.5rem;
  border-radius: 6px;
  white-space: nowrap !important;
  letter-spacing: 0.5px;
}}

/* RULE 1: NAV LINKS & BUTTONS (whitespace-nowrap) */
.v-nav-links {{
  display: flex;
  gap: 1.75rem;
  align-items: center;
}}
.v-nav-links a {{
  color: #a1a1aa;
  text-decoration: none;
  font-size: 0.9375rem;
  font-weight: 500;
  white-space: nowrap !important;
  transition: color 0.15s ease;
}}
.v-nav-links a:hover {{
  color: #ffffff;
}}

/* BUTTONS */
.v-btn {{
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-weight: 600;
  border-radius: var(--v-radius-sm);
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  border: 1px solid transparent;
  font-size: 0.9375rem;
  white-space: nowrap !important;
}}
.v-btn-primary {{
  background: linear-gradient(135deg, #0A6A56 0%, #064e3b 100%);
  color: #ffffff !important;
  border: 1px solid rgba(16, 185, 129, 0.5);
  padding: 0.8rem 1.65rem;
  box-shadow: 0 0 25px rgba(10, 106, 86, 0.45);
}}
.v-btn-primary:hover {{
  background: linear-gradient(135deg, #0d836b 0%, #085a44 100%);
  border-color: #10b981;
  transform: translateY(-1px);
  box-shadow: 0 0 35px rgba(16, 185, 129, 0.6);
}}
.v-btn-emerald {{
  background: #0A6A56;
  color: #ffffff !important;
  border: 1px solid rgba(16, 185, 129, 0.4);
  padding: 0.55rem 1.15rem;
  box-shadow: 0 0 16px rgba(10, 106, 86, 0.3);
  white-space: nowrap !important;
}}
.v-btn-emerald:hover {{
  background: #0d836b;
  border-color: #10b981;
  transform: translateY(-1px);
}}
.v-btn-secondary {{
  background: rgba(18, 18, 22, 0.8);
  color: #e4e4e7 !important;
  border: 1px solid var(--v-border);
  padding: 0.8rem 1.65rem;
  white-space: nowrap !important;
}}
.v-btn-secondary:hover {{
  background: rgba(24, 24, 27, 0.95);
  border-color: #52525b;
  color: #ffffff !important;
}}

/* HERO SECTION */
.v-hero {{
  padding: 4.5rem 0 3.5rem;
  text-align: center;
}}
.v-kicker {{
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(10, 106, 86, 0.2);
  border: 1px solid rgba(16, 185, 129, 0.35);
  color: #34d399;
  font-size: 0.8125rem;
  font-weight: 600;
  padding: 0.35rem 0.95rem;
  border-radius: 9999px;
  margin-bottom: 1.75rem;
  white-space: nowrap;
}}

/* RULE 4: H1 tracking-tight text-balance */
.v-hero-title {{
  font-size: 3.35rem;
  font-weight: 800;
  color: #ffffff;
  letter-spacing: -0.025em; /* tracking-tight */
  text-wrap: balance; /* text-balance */
  line-height: 1.15;
  max-width: 860px;
  margin-left: auto;
  margin-right: auto;
  margin-bottom: 1.35rem;
}}
@media (max-width: 768px) {{
  .v-hero-title {{
    font-size: 2.25rem;
  }}
}}

/* RULE 4: SUBTITLE max-w-2xl mx-auto text-slate-400 */
.v-hero-desc {{
  font-size: 1.125rem;
  color: #94a3b8; /* text-slate-400 */
  max-width: 42rem; /* max-w-2xl */
  margin-left: auto; /* mx-auto */
  margin-right: auto; /* mx-auto */
  margin-bottom: 2.25rem;
  line-height: 1.65;
}}

.v-hero-cta {{
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}}

/* RULE 4: TRUST BADGES (flex flex-wrap justify-center gap-6 mt-8) */
.v-trust-badges {{
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 1.5rem; /* gap-6 */
  margin-top: 2rem; /* mt-8 */
  font-size: 0.875rem;
  color: #a1a1aa;
  font-weight: 500;
}}
.v-trust-item {{
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  white-space: nowrap !important;
}}
.v-trust-item svg {{
  color: #10b981;
}}

/* RULE 5: CARDS bg-zinc-950/90 border-zinc-800/80 */
.v-pipeline-card {{
  background: rgba(9, 9, 11, 0.92); /* bg-zinc-950/90 */
  border: 1px solid rgba(39, 39, 42, 0.8); /* border-zinc-800/80 */
  border-radius: var(--v-radius-lg);
  padding: 2.25rem;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.6);
  margin: 3.75rem auto 0;
  max-width: 960px;
  position: relative;
  overflow: hidden;
}}
.v-pipeline-card::before {{
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, #0A6A56, #10b981, #0A6A56);
  background-size: 200% 100%;
  animation: vGradientMove 4s linear infinite;
}}
@keyframes vGradientMove {{
  0% {{ background-position: 0% 0%; }}
  100% {{ background-position: 200% 0%; }}
}}

/* 1. HORIZONTAL PIPELINE SIMULATOR (11-COL GRID) & TAILWIND UTILITIES */
.grid {{
  display: grid;
}}
.grid-cols-1 {{
  grid-template-columns: repeat(1, minmax(0, 1fr));
}}
.gap-4 {{
  gap: 1rem;
}}
.gap-6 {{
  gap: 1.5rem;
}}
.items-center {{
  align-items: center;
}}
.flex {{
  display: flex;
}}
.flex-col {{
  flex-direction: column;
}}
.justify-center {{
  justify-content: center;
}}
.justify-between {{
  justify-content: space-between;
}}
.h-full {{
  height: 100%;
}}
.w-full {{
  width: 100%;
}}
.rounded-lg {{
  border-radius: 0.5rem;
}}
.rounded-xl {{
  border-radius: 0.75rem;
}}
.p-6 {{
  padding: 1.5rem;
}}
.mt-8 {{
  margin-top: 2rem;
}}
.bg-zinc-800 {{
  background-color: #27272a;
}}
.bg-zinc-900\\/50, [class*="bg-zinc-900/50"] {{
  background-color: rgba(24, 24, 27, 0.5) !important;
}}
.border {{
  border-width: 1px;
  border-style: solid;
}}
.border-zinc-800, [class*="border-zinc-800"] {{
  border-color: rgba(39, 39, 42, 0.8) !important;
}}
.border-emerald-500\\/40, [class*="border-emerald-500/40"] {{
  border-color: rgba(16, 185, 129, 0.4) !important;
}}
.bg-emerald-950\\/10, [class*="bg-emerald-950/10"] {{
  background-color: rgba(6, 78, 59, 0.15) !important;
}}
.accent-emerald-500 {{
  accent-color: #10b981;
}}
.h-2 {{
  height: 0.5rem;
}}

@media (min-width: 640px) {{
  .sm\\:flex-row, [class*="sm:flex-row"] {{
    flex-direction: row !important;
  }}
}}

@media (min-width: 768px) {{
  .md\\:grid-cols-2, [class*="md:grid-cols-2"] {{
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
  }}
  .md\\:grid-cols-3, [class*="md:grid-cols-3"] {{
    grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
  }}
}}

@media (min-width: 1024px) {{
  .lg\\:grid-cols-3, [class*="lg:grid-cols-3"] {{
    grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
  }}
  .lg\\:grid-cols-11, [class*="lg:grid-cols-11"] {{
    grid-template-columns: repeat(11, minmax(0, 1fr)) !important;
  }}
  .lg\\:grid-cols-12, [class*="lg:grid-cols-12"] {{
    grid-template-columns: repeat(12, minmax(0, 1fr)) !important;
  }}
  .lg\\:col-span-5, [class*="lg:col-span-5"] {{
    grid-column: span 5 / span 5 !important;
  }}
  .lg\\:col-span-4, [class*="lg:col-span-4"] {{
    grid-column: span 4 / span 4 !important;
  }}
  .lg\\:col-span-3, [class*="lg:col-span-3"] {{
    grid-column: span 3 / span 3 !important;
  }}
}}

/* RULES 1-4 UTILITIES (p-8, pt-6, gap-8, badge positioning, flex lists) */
.p-8, [class*="p-8"] {{
  padding: 2rem !important;
}}
.pt-6, [class*="pt-6"] {{
  padding-top: 1.5rem !important;
}}
.gap-8, [class*="gap-8"] {{
  gap: 2rem !important;
}}
.gap-3, [class*="gap-3"] {{
  gap: 0.75rem !important;
}}
.items-stretch, [class*="items-stretch"] {{
  align-items: stretch !important;
}}
.items-start, [class*="items-start"] {{
  align-items: flex-start !important;
}}
.rounded-2xl, [class*="rounded-2xl"] {{
  border-radius: 1rem !important;
}}
.relative, [class*="relative"] {{
  position: relative !important;
}}
.absolute, [class*="absolute"] {{
  position: absolute !important;
}}
.-top-3\\.5, [class*="-top-3.5"] {{
  top: -0.875rem !important;
}}
.left-1\\/2, [class*="left-1/2"] {{
  left: 50% !important;
}}
.-translate-x-1\\/2, [class*="-translate-x-1/2"] {{
  transform: translateX(-50%) !important;
}}
.px-3, [class*="px-3"] {{
  padding-left: 0.75rem !important;
  padding-right: 0.75rem !important;
}}
.py-1, [class*="py-1"] {{
  padding-top: 0.25rem !important;
  padding-bottom: 0.25rem !important;
}}
.py-3, [class*="py-3"] {{
  padding-top: 0.75rem !important;
  padding-bottom: 0.75rem !important;
}}
.text-xs, [class*="text-xs"] {{
  font-size: 0.75rem !important;
  line-height: 1rem !important;
}}
.text-sm, [class*="text-sm"] {{
  font-size: 0.875rem !important;
  line-height: 1.25rem !important;
}}
.font-semibold, [class*="font-semibold"] {{
  font-weight: 600 !important;
}}
.uppercase, [class*="uppercase"] {{
  text-transform: uppercase !important;
}}
.tracking-wider, [class*="tracking-wider"] {{
  letter-spacing: 0.05em !important;
}}
.z-10, [class*="z-10"] {{
  z-index: 10 !important;
}}
.mt-auto, [class*="mt-auto"] {{
  margin-top: auto !important;
}}
.text-left, [class*="text-left"] {{
  text-align: left !important;
}}
.text-center, [class*="text-center"] {{
  text-align: center !important;
}}
.shrink-0, [class*="shrink-0"] {{
  flex-shrink: 0 !important;
}}
.w-4, [class*="w-4"] {{
  width: 1rem !important;
}}
.h-4, [class*="h-4"] {{
  height: 1rem !important;
}}
.w-12, [class*="w-12"] {{
  width: 3rem !important;
}}
.h-12, [class*="h-12"] {{
  height: 3rem !important;
}}
.flex-1, [class*="flex-1"] {{
  flex: 1 1 0% !important;
}}
.space-y-3\\.5 > * + *, [class*="space-y-3.5"] > * + * {{
  margin-top: 0.875rem !important;
}}

/* FAQ & FOOTER UTILITIES */
.border-b, [class*="border-b"] {{
  border-bottom-width: 1px !important;
  border-bottom-style: solid !important;
}}
.border-t, [class*="border-t"] {{
  border-top-width: 1px !important;
  border-top-style: solid !important;
}}
.border-zinc-800\\/80, [class*="border-zinc-800/80"] {{
  border-color: rgba(39, 39, 42, 0.8) !important;
}}
.border-zinc-900, [class*="border-zinc-900"] {{
  border-color: #18181b !important;
}}
.border-zinc-900\\/60, [class*="border-zinc-900/60"] {{
  border-color: rgba(24, 24, 27, 0.6) !important;
}}
.py-5, [class*="py-5"] {{
  padding-top: 1.25rem !important;
  padding-bottom: 1.25rem !important;
}}
.py-12, [class*="py-12"] {{
  padding-top: 3rem !important;
  padding-bottom: 3rem !important;
}}
.pt-8, [class*="pt-8"] {{
  padding-top: 2rem !important;
}}
.gap-10, [class*="gap-10"] {{
  gap: 2.5rem !important;
}}
.font-medium, [class*="font-medium"] {{
  font-weight: 500 !important;
}}
.space-y-2\\.5 > * + *, [class*="space-y-2.5"] > * + * {{
  margin-top: 0.625rem !important;
}}
.text-zinc-500, [class*="text-zinc-500"] {{
  color: #71717a !important;
}}
.text-zinc-400, [class*="text-zinc-400"] {{
  color: #a1a1aa !important;
}}
.text-zinc-300, [class*="text-zinc-300"] {{
  color: #d4d4d8 !important;
}}

.v-pipe-box {{
  background: rgba(18, 18, 22, 0.7);
  border: 1px solid rgba(39, 39, 42, 0.7);
  border-radius: var(--v-radius-md);
  padding: 1.25rem;
  text-align: left;
}}
.v-pipe-tag {{
  font-size: 0.6875rem;
  text-transform: uppercase;
  font-weight: 700;
  letter-spacing: 0.5px;
  color: #71717a;
  margin-bottom: 0.5rem;
}}
.v-pipe-mid {{
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}}
.v-flow-arrows {{
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  color: #10b981;
}}
@keyframes vArrowPulse {{
  0%, 100% {{ opacity: 0.4; transform: translateX(-2px); }}
  50% {{ opacity: 1; transform: translateX(2px); }}
}}
.v-arrow-flow {{
  display: inline-block;
  font-size: 0.95rem;
  animation: vArrowPulse 1.6s ease-in-out infinite;
}}
.v-pipe-btn {{
  background: linear-gradient(135deg, #0A6A56 0%, #064e3b 100%);
  color: #ffffff;
  border: 1px solid rgba(16, 185, 129, 0.5);
  padding: 0.7rem 1.4rem;
  border-radius: 9999px;
  font-weight: 600;
  font-size: 0.8125rem;
  cursor: pointer;
  box-shadow: 0 0 20px rgba(10, 106, 86, 0.4);
  transition: all 0.2s ease;
  white-space: nowrap !important;
}}
.v-pipe-btn:hover {{
  transform: scale(1.04);
  box-shadow: 0 0 25px rgba(16, 185, 129, 0.6);
}}

/* ROI CALCULATOR SECTION */
.v-roi-section {{
  padding: 5rem 0;
  background: #000000;
  border-top: 1px solid var(--v-border);
  border-bottom: 1px solid var(--v-border);
}}
.v-section-head {{
  text-align: center;
  max-width: 650px;
  margin: 0 auto 3rem;
}}
.v-section-kicker {{
  font-size: 0.8125rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #10b981;
  margin-bottom: 0.5rem;
}}
.v-section-h2 {{
  font-size: 2.25rem;
  font-weight: 800;
  color: #ffffff;
  letter-spacing: -0.025em;
  text-wrap: balance;
  margin: 0 0 0.75rem;
}}
.v-roi-box {{
  background: rgba(9, 9, 11, 0.92);
  border: 1px solid rgba(39, 39, 42, 0.8);
  border-radius: var(--v-radius-lg);
  padding: 2.5rem;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.6);
  max-width: 880px;
  margin: 0 auto;
}}
.v-slider-row {{
  margin-bottom: 2.25rem;
}}
.v-slider-label {{
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}}
.v-slider-val {{
  font-size: 1.15rem;
  font-weight: 700;
  color: #34d399;
  background: rgba(10, 106, 86, 0.25);
  border: 1px solid rgba(16, 185, 129, 0.4);
  padding: 0.25rem 0.85rem;
  border-radius: 9999px;
  letter-spacing: 0.3px;
}}
/* RULE 2: CUSTOM RANGE SLIDER (accent-emerald-500 h-2 bg-zinc-800 rounded-lg w-full) */
.v-slider, input[type="range"].v-slider {{
  width: 100%;
  height: 0.5rem; /* h-2 */
  border-radius: 0.5rem; /* rounded-lg */
  background: #27272a; /* bg-zinc-800 */
  accent-color: #10b981; /* accent-emerald-500 */
  outline: none;
  cursor: pointer;
  -webkit-appearance: none;
}}
.v-slider::-webkit-slider-runnable-track {{
  height: 8px;
  background: #27272a;
  border-radius: 4px;
}}
.v-slider::-webkit-slider-thumb {{
  -webkit-appearance: none;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: #10b981;
  border: 2px solid #ffffff;
  cursor: pointer;
  box-shadow: 0 0 14px rgba(16, 185, 129, 0.7);
  margin-top: -7px;
  transition: transform 0.15s ease;
}}
.v-slider::-webkit-slider-thumb:hover {{
  transform: scale(1.18);
}}
.v-slider::-moz-range-track {{
  height: 8px;
  background: #27272a;
  border-radius: 4px;
}}
.v-slider::-moz-range-thumb {{
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: #10b981;
  border: 2px solid #ffffff;
  cursor: pointer;
  box-shadow: 0 0 14px rgba(16, 185, 129, 0.7);
}}
.v-roi-stats {{
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
  text-align: center;
}}
@media (max-width: 640px) {{
  .v-roi-stats {{
    grid-template-columns: 1fr;
  }}
}}
.v-roi-card {{
  padding: 1.25rem;
  border-radius: var(--v-radius-md);
  background: rgba(18, 18, 22, 0.7);
  border: 1px solid rgba(39, 39, 42, 0.7);
}}
.v-roi-card.highlight {{
  background: rgba(10, 106, 86, 0.2);
  border-color: rgba(16, 185, 129, 0.45);
}}
.v-roi-num {{
  font-size: 2rem;
  font-weight: 800;
  color: #ffffff;
  margin-bottom: 0.25rem;
}}
.v-roi-num.emerald {{
  color: #10b981;
}}
.v-roi-lbl {{
  font-size: 0.8125rem;
  font-weight: 600;
  color: #a1a1aa;
}}

/* 3 PILLARS SECTION */
.v-pillars-section {{
  padding: 5rem 0;
  background: #000000;
}}
.v-pillars-grid {{
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2rem;
}}
@media (max-width: 768px) {{
  .v-pillars-grid {{
    grid-template-columns: 1fr;
  }}
}}
.v-pillar {{
  background: rgba(9, 9, 11, 0.92);
  border: 1px solid rgba(39, 39, 42, 0.8);
  border-radius: var(--v-radius-md);
  padding: 2rem;
  transition: all 0.25s ease;
}}
.v-pillar:hover {{
  transform: translateY(-3px);
  border-color: var(--v-border-hover);
  box-shadow: 0 10px 30px rgba(10, 106, 86, 0.15);
}}
.v-pillar-icon {{
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  margin-bottom: 1.25rem;
  background: rgba(10, 106, 86, 0.25);
  border: 1px solid rgba(16, 185, 129, 0.3);
}}
.v-pillar-h3 {{
  font-size: 1.2rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0 0 0.75rem;
}}
.v-pillar-p {{
  font-size: 0.9375rem;
  color: #a1a1aa;
  line-height: 1.6;
  margin: 0;
}}

/* PRICING SECTION */
.v-pricing-section {{
  padding: 5rem 0;
  background: #000000;
  border-top: 1px solid var(--v-border);
  border-bottom: 1px solid var(--v-border);
}}
.v-pricing-grid {{
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2rem;
  align-items: stretch;
  max-width: 1040px;
  margin: 0 auto;
}}
@media (max-width: 860px) {{
  .v-pricing-grid {{
    grid-template-columns: 1fr;
  }}
}}
.v-price-card {{
  background: rgba(9, 9, 11, 0.92);
  border: 1px solid rgba(39, 39, 42, 0.8);
  border-radius: var(--v-radius-lg);
  padding: 2.25rem;
  display: flex;
  flex-direction: column;
  position: relative;
  transition: all 0.25s ease;
}}
.v-price-card:hover {{
  transform: translateY(-4px);
  border-color: rgba(16, 185, 129, 0.4);
}}
.v-price-card.featured {{
  border: 2px solid #10b981;
  box-shadow: 0 0 35px rgba(10, 106, 86, 0.35);
}}
.v-badge-pop {{
  position: absolute !important;
  top: -0.875rem !important; /* -top-3.5 */
  left: 50% !important;
  transform: translateX(-50%) !important;
  background: #10b981 !important;
  color: #000000 !important;
  font-size: 0.75rem !important; /* text-xs */
  font-weight: 600 !important; /* font-semibold */
  text-transform: uppercase !important;
  letter-spacing: 0.05em !important; /* tracking-wider */
  padding: 0.25rem 0.75rem !important; /* px-3 py-1 */
  border-radius: 9999px !important;
  box-shadow: 0 0 15px rgba(16, 185, 129, 0.6) !important;
  white-space: nowrap !important;
  z-index: 10 !important;
}}
.v-plan-name {{
  font-size: 1.15rem;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 0.5rem;
}}
.v-plan-desc {{
  font-size: 0.875rem;
  color: #94a3b8;
  margin-bottom: 1.5rem;
}}
.v-plan-price {{
  font-size: 2.75rem;
  font-weight: 800;
  color: #ffffff;
  letter-spacing: -1px;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: baseline;
  gap: 0.25rem;
}}
.v-plan-cur {{
  font-size: 1.25rem;
  font-weight: 600;
  color: #10b981;
}}
.v-plan-sub {{
  font-size: 0.875rem;
  font-weight: 500;
  color: #71717a;
}}
.v-plan-features {{
  list-style: none;
  padding: 0;
  margin: 0 0 2rem;
  font-size: 0.9375rem;
  color: #d4d4d8;
  flex-grow: 1;
}}
.v-plan-features li {{
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}}
.v-plan-features li svg {{
  color: #10b981;
  flex-shrink: 0;
}}
.v-pay-logos {{
  display: flex;
  justify-content: center;
  gap: 0.75rem;
  align-items: center;
  margin-top: 1rem;
  font-size: 0.75rem;
  color: #71717a;
  white-space: nowrap !important;
}}

/* FAQ SECTION */
.v-faq-section {{
  padding: 5rem 0;
  background: #000000;
}}
.v-faq-wrap {{
  max-width: 760px;
  margin: 0 auto;
}}
.v-faq-box {{
  border-bottom: 1px solid rgba(39, 39, 42, 0.8);
  padding: 1.25rem 0;
}}
.v-faq-q {{
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 1.05rem;
  font-weight: 500;
  color: #ffffff;
  cursor: pointer;
  user-select: none;
  background: transparent;
  border: none;
  padding: 0;
  width: 100%;
  text-align: left;
  gap: 1rem;
  transition: color 0.15s ease;
}}
.v-faq-q:hover {{
  color: #34d399;
}}
.v-faq-a {{
  margin-top: 0.75rem;
  font-size: 0.9375rem;
  color: #a1a1aa;
  line-height: 1.6;
  display: none;
}}
.v-faq-box.open .v-faq-a {{
  display: block;
}}
.v-faq-box.open .v-faq-toggle {{
  transform: rotate(45deg);
  color: #10b981;
}}
.v-faq-toggle {{
  font-size: 1.25rem;
  font-weight: 400;
  color: #71717a;
  transition: transform 0.2s ease, color 0.2s ease;
  line-height: 1;
  display: inline-block;
  flex-shrink: 0;
}}

/* FOOTER (STRIPE COMPLIANT, NO PHONE NUMBER) */
.v-footer {{
  background: #000000;
  padding: 1rem 0 3rem;
  font-size: 0.875rem;
  color: #a1a1aa;
}}
.v-footer-h4 {{
  font-size: 0.75rem;
  text-transform: uppercase;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: #ffffff;
  margin: 0 0 1rem;
}}
.v-footer-links {{
  list-style: none;
  padding: 0;
  margin: 0;
}}
.v-footer-links li {{
  margin-bottom: 0.5rem;
}}
.v-footer-links a {{
  color: #a1a1aa;
  text-decoration: none;
  transition: color 0.15s ease;
  white-space: nowrap !important;
}}
.v-footer-links a:hover {{
  color: #ffffff;
}}
.v-footer-bottom {{
  border-top: 1px solid rgba(24, 24, 27, 0.6);
  padding-top: 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  color: #71717a;
  font-size: 0.75rem;
}}
</style>

<div class="vorkei-wrap">
  <!-- TOP ANNOUNCEMENT BAR (max-w-7xl mx-auto px-6 & whitespace-nowrap) -->
  <div class="v-topbar">
    <div class="v-container v-topbar-flex">
      <div class="v-topbar-badge">
        <span class="v-dot-live"></span>
        <span style="white-space:nowrap;">Produkčný Mostík pre Tatra banka PDF &amp; WooCommerce HPOS</span>
      </div>
      <div style="white-space:nowrap;">
        <span>Jednotná sadzba: 5,00 € / výpis</span> • 
        <span>Stripe Payments Verified</span>
      </div>
    </div>
  </div>

  <!-- NAVIGATION (max-w-7xl mx-auto px-6 & whitespace-nowrap) -->
  <nav class="v-nav">
    <div class="v-container v-nav-flex">
      <!-- RULE 2: LOGO & BRAND (flex items-center gap-2.5) -->
      <a href="https://pdf-t3.vorkei.com" class="v-brand">
        <div class="v-logo-box">V</div>
        <span class="v-brand-title">Vorkei Statement Bridge</span>
        <span class="v-badge-pro">PRO</span>
      </a>

      <!-- RULE 1: NAV LINKS (whitespace-nowrap) -->
      <div class="v-nav-links">
        <a href="#simulator">Simulátor</a>
        <a href="#kalkulacka">ROI Kalkulačka</a>
        <a href="#vyhody">Výhody</a>
        <a href="#cennik">Cenník</a>
        <a href="#faq">FAQ</a>
      </div>

      <!-- RULE 1: CTA BUTTON (whitespace-nowrap strictly on single line) -->
      <a href="#cennik" class="v-btn v-btn-emerald">
        Kúpiť Kredity (5 €)
      </a>
    </div>
  </nav>

  <!-- HERO SECTION -->
  <header class="v-hero">
    <div class="v-container">
      <div class="v-kicker">
        <span>⚡</span> Okamžité párovanie platieb bez bankových poplatkov
      </div>

      <!-- RULE 4: H1 tracking-tight text-balance -->
      <h1 class="v-hero-title">
        Premeňte bankový výpis na vybavené objednávky za 3 sekundy.
      </h1>

      <!-- RULE 4: SUBTITLE max-w-2xl mx-auto text-slate-400 -->
      <p class="v-hero-desc">
        Automatizovaný fintech mostík pre Tatra banka PDF výpisy a WooCommerce HPOS. Žiadne mesačné bankové zmluvy, žiadny externý cloud – vaše finančné dáta ostávajú 100% on-premise na vašom vlastnom hostingu.
      </p>

      <!-- RULE 3: PRIMARY BUTTON TYPO FIX -> Dobiť Kredity (od 5,00 €) -->
      <div class="v-hero-cta">
        <a href="#cennik" class="v-btn v-btn-primary">
          Dobiť Kredity (od 5,00 €)
        </a>
        <a href="#simulator" class="v-btn v-btn-secondary">
          Vyskúšať Živý Simulátor
        </a>
      </div>

      <!-- RULE 4: TRUST BADGES (flex flex-wrap justify-center gap-6 mt-8) -->
      <div class="v-trust-badges">
        <div class="v-trust-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          100% On-Premise Bezpečnosť
        </div>
        <div class="v-trust-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          WooCommerce HPOS Certified
        </div>
        <div class="v-trust-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Stripe Instant Checkout
        </div>
      </div>

      <!-- RULE 5: CARDS bg-zinc-950/90 border-zinc-800/80 -->
      <!-- 1. ŽIVÝ SIMULÁTOR (HORIZONTAL PIPELINE 11-COL GRID) -->
      <div id="simulator" class="v-pipeline-card">
        <div class="grid grid-cols-1 lg:grid-cols-11 gap-4 items-center">
          <!-- ĽAVÝ STĹPEC (lg:col-span-4): Bankový Výpis -->
          <div class="lg:col-span-4 v-pipe-box">
            <div class="v-pipe-tag">1. BANKOVÝ VÝPIS (TATRA BANKA)</div>
            <div class="v-mono" style="font-size:1.15rem;font-weight:800;color:#ffffff;margin-bottom:0.25rem;">
              + 189,50 EUR
            </div>
            <div class="v-mono" style="font-size:0.8125rem;color:#a1a1aa;margin-bottom:0.5rem;">
              VS: 1042 | Účet: SK66 1100...
            </div>
            <div style="font-size:0.75rem;color:#10b981;font-weight:700;display:inline-flex;align-items:center;gap:0.35rem;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              <span>SHA-256 Validovaný</span>
            </div>
          </div>

          <!-- STREDNÝ STĹPEC (lg:col-span-3 flex flex-col items-center justify-center): Trigger & Flow -->
          <div class="lg:col-span-3 flex flex-col items-center justify-center v-pipe-mid" style="gap:0.65rem;">
            <!-- Šípky toku dát -->
            <div class="v-flow-arrows" title="Dátový tok z výpisu do WooCommerce">
              <span class="v-arrow-flow">⇥</span>
              <span style="font-size:0.6875rem;font-weight:700;letter-spacing:1px;color:#34d399;text-transform:uppercase;">Dátový Tok</span>
              <span class="v-arrow-flow">➔</span>
            </div>
            <button id="v-sim-trigger" class="v-pipe-btn" type="button" onclick="vRunSimulationPipeline()">
              Spustiť synchronizáciu
            </button>
            <span id="v-sim-time" class="v-mono" style="font-size:0.75rem;color:#71717a;letter-spacing:0.3px;">
              Latencia: 0,07 sek.
            </span>
          </div>

          <!-- PRAVÝ STĹPEC (lg:col-span-4): WooCommerce Objednávka -->
          <div class="lg:col-span-4 v-pipe-box" style="border-left:3px solid #10b981;">
            <div class="v-pipe-tag">2. WOOCOMMERCE OBJEDNÁVKA #1042</div>
            <div style="font-size:1rem;font-weight:700;color:#ffffff;margin-bottom:0.5rem;">
              Peter Novák (189,50 €)
            </div>
            <div id="v-sim-status" style="display:inline-flex;align-items:center;gap:0.35rem;padding:0.25rem 0.75rem;border-radius:12px;background:rgba(234, 179, 8, 0.15);color:#facc15;border:1px solid rgba(234, 179, 8, 0.3);font-size:0.75rem;font-weight:700;">
              <span>●</span> Čaká na platbu (Pending)
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- 2. ROI KALKULAČKA NÁVRATNOSTI -->
  <section id="kalkulacka" class="v-roi-section">
    <div class="v-container">
      <div class="v-section-head">
        <div class="v-section-kicker">Kalkulačka Návratnosti</div>
        <h2 class="v-section-h2">Koľko peňazí a hodín ušetríte každý mesiac?</h2>
        <p style="color:#94a3b8;font-size:1.05rem;margin:0;">
          Posuňte posuvník a pozrite si okamžitú úsporu pri vašom mesačnom objeme výpisov.
        </p>
      </div>

      <div class="v-roi-box">
        <div class="v-slider-row">
          <div class="v-slider-label">
            <span style="font-weight:700;color:#ffffff;font-size:1.05rem;">Mesačný počet bankových výpisov:</span>
            <span id="v-slider-display" class="v-slider-val v-mono">10 výpisov</span>
          </div>
          <input id="v-roi-slider" type="range" min="1" max="50" value="10" class="accent-emerald-500 h-2 bg-zinc-800 rounded-lg w-full v-slider" oninput="vUpdateRoi(this.value)">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
          <!-- KARTA 1: 18,5 h -->
          <div class="h-full flex flex-col justify-between p-6 rounded-xl bg-zinc-900/50 border border-zinc-800 v-roi-card">
            <div>
              <div class="v-roi-lbl" style="font-size:0.875rem;font-weight:600;color:#a1a1aa;margin-bottom:0.75rem;">
                Ušetrený čas v účtovníctve
              </div>
              <div id="v-stat-hours" class="v-roi-num v-mono" style="font-size:2.25rem;font-weight:800;color:#ffffff;">
                18,5 h
              </div>
            </div>
            <div style="font-size:0.75rem;color:#71717a;margin-top:0.75rem;">
              ~ 1,85 hodiny na 1 výpis
            </div>
          </div>

          <!-- KARTA 2: 50 € -->
          <div class="h-full flex flex-col justify-between p-6 rounded-xl bg-zinc-900/50 border border-zinc-800 v-roi-card">
            <div>
              <div class="v-roi-lbl" style="font-size:0.875rem;font-weight:600;color:#a1a1aa;margin-bottom:0.75rem;">
                Náklady na Vorkei Bridge <span class="whitespace-nowrap">(5 €/výpis)</span>
              </div>
              <div id="v-stat-cost" class="v-roi-num v-mono" style="font-size:2.25rem;font-weight:800;color:#ffffff;">
                50 €
              </div>
            </div>
            <div style="font-size:0.75rem;color:#71717a;margin-top:0.75rem;">
              Jednotná sadzba bez viazanosti
            </div>
          </div>

          <!-- KARTA 3: + 227 € (border-emerald-500/40 bg-emerald-950/10) -->
          <div class="h-full flex flex-col justify-between p-6 rounded-xl bg-zinc-900/50 border border-zinc-800 border-emerald-500/40 bg-emerald-950/10 v-roi-card highlight" style="box-shadow:0 0 25px rgba(10, 106, 86, 0.2);">
            <div>
              <div class="v-roi-lbl" style="font-size:0.875rem;font-weight:600;color:#34d399;margin-bottom:0.75rem;">
                Čistá mesačná úspora (ROI)
              </div>
              <div id="v-stat-savings" class="v-roi-num v-mono emerald" style="font-size:2.25rem;font-weight:800;color:#10b981;">
                + 227 €
              </div>
            </div>
            <div style="font-size:0.75rem;color:#10b981;font-weight:600;margin-top:0.75rem;">
              Okamžitá finančná návratnosť
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 1. PREČO VORKEI BRIDGE (3 BENEFITY) -->
  <section id="vyhody" class="v-pillars-section">
    <div class="v-container">
      <div class="v-section-head">
        <div class="v-section-kicker">Prečo Vorkei Bridge</div>
        <h2 class="v-section-h2">Architektúra stavaná pre maximálnu bezpečnosť</h2>
        <p style="color:#94a3b8;font-size:1.05rem;margin:0;">
          Žiadne kompromisy v bezpečnosti ani skryté bankové poplatky.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- BENEFIT 1 -->
        <div class="p-8 h-full flex flex-col justify-between text-left rounded-2xl bg-zinc-950/90 border border-zinc-800/80 transition hover:border-emerald-500/40">
          <div>
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl mb-5 bg-emerald-950/30 border border-emerald-500/30 text-emerald-400">
              ⚡
            </div>
            <h3 class="text-xl font-bold text-white mb-3">
              Okamžité párovanie do 3 sekúnd
            </h3>
            <p class="text-sm text-zinc-400 leading-relaxed">
              Namiesto hodín strávených čítaním výpisu riadok po riadku algoritmus overí variabilné symboly, IBAN a presné centové sumy za menej ako 0,07 sekundy.
            </p>
          </div>
          <div class="mt-6 pt-4 border-t border-zinc-800/60 text-xs font-semibold text-emerald-400 flex items-center gap-2">
            <span>✓</span> Bleskový spracovateľský cyklus
          </div>
        </div>

        <!-- BENEFIT 2 -->
        <div class="p-8 h-full flex flex-col justify-between text-left rounded-2xl bg-zinc-950/90 border border-zinc-800/80 transition hover:border-emerald-500/40">
          <div>
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl mb-5 bg-emerald-950/30 border border-emerald-500/30 text-emerald-400">
              🔒
            </div>
            <h3 class="text-xl font-bold text-white mb-3">
              100% On-Premise &amp; Bank Secret Safe
            </h3>
            <p class="text-sm text-zinc-400 leading-relaxed">
              Žiadne bankové pohyby neodchádzajú do externého cloudu. Všetko beží výhradne vo vašej vlastnej WordPress databáze pod vašou kontrolou.
            </p>
          </div>
          <div class="mt-6 pt-4 border-t border-zinc-800/60 text-xs font-semibold text-emerald-400 flex items-center gap-2">
            <span>✓</span> Finančné tajomstvo chránené
          </div>
        </div>

        <!-- BENEFIT 3 -->
        <div class="p-8 h-full flex flex-col justify-between text-left rounded-2xl bg-zinc-950/90 border border-zinc-800/80 transition hover:border-emerald-500/40">
          <div>
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl mb-5 bg-emerald-950/30 border border-emerald-500/30 text-emerald-400">
              💳
            </div>
            <h3 class="text-xl font-bold text-white mb-3">
              0 € Mesačných Poplatkov Banke
            </h3>
            <p class="text-sm text-zinc-400 leading-relaxed">
              Zabudnite na zložité PSD2 zmluvy s Tatra bankou za 100+ € mesačne. Nástroj funguje priamo s bežným PDF výpisom a platíte len férových 5 € za spracovaný výpis.
            </p>
          </div>
          <div class="mt-6 pt-4 border-t border-zinc-800/60 text-xs font-semibold text-emerald-400 flex items-center gap-2">
            <span>✓</span> Bez viazanosti a paušálov
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 2. FÉROVÝ MODEL (CENNÍK) -->
  <section id="cennik" class="v-pricing-section">
    <div class="v-container">
      <div class="v-section-head">
        <div class="v-section-kicker">Férový Model</div>
        <h2 class="v-section-h2">Jednotný cenník: 5,00 € za výpis</h2>
        <p style="color:#94a3b8;font-size:1.05rem;margin:0;">
          Žiadne mesačné paušály. Kredit si dobijete kartou a miniete, kedy potrebujete.
        </p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch pt-6">
        <!-- PLAN 1: ŠTART -->
        <div class="relative flex flex-col justify-between p-8 rounded-2xl border border-zinc-800/80 bg-zinc-950/90 transition hover:border-zinc-700/80">
          <div>
            <div class="text-xl font-bold text-white mb-2">Štart</div>
            <div class="text-xs text-zinc-400 mb-6">1 kredit na okamžité vyskúšanie</div>
            <div class="flex items-baseline gap-2 mb-6">
              <span class="text-4xl font-extrabold text-white">5 €</span>
              <span class="text-sm font-medium text-zinc-400">/ výpis</span>
            </div>
            <ul class="space-y-3.5 mb-8 flex-1">
              <li class="flex items-start gap-3 text-sm text-zinc-300">
                <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Spracovanie 1 kompletného výpisu</span>
              </li>
              <li class="flex items-start gap-3 text-sm text-zinc-300">
                <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Neobmedzený počet pohybov</span>
              </li>
              <li class="flex items-start gap-3 text-sm text-zinc-300">
                <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Automatické Woo HPOS párovanie</span>
              </li>
            </ul>
          </div>
          <div>
            <a href="{STRIPE_10_URL}" class="mt-auto w-full py-3 px-6 rounded-xl font-bold text-sm bg-zinc-900 hover:bg-zinc-800 text-zinc-200 border border-zinc-700/80 transition text-center whitespace-nowrap flex items-center justify-center">
              Kúpiť 1 výpis (5 €)
            </a>
            <div class="text-xs text-zinc-500 text-center mt-3 whitespace-nowrap">
              Karta · Apple Pay · Google Pay
            </div>
          </div>
        </div>

        <!-- PLAN 2: FLOTILA & E-SHOP (FEATURED) -->
        <div class="relative flex flex-col justify-between p-8 rounded-2xl border-2 border-emerald-500 bg-zinc-950/95 shadow-2xl shadow-emerald-950/50">
          <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider whitespace-nowrap z-10 v-badge-pop">
            🔥 NAJPOPULÁRNEJŠIE PRE FLOTILY
          </div>
          <div>
            <div class="text-xl font-bold text-white mb-2">Flotila &amp; E-shop</div>
            <div class="text-xs text-zinc-400 mb-6">Balíček 10 kreditov pre aktívnu prevádzku</div>
            <div class="flex items-baseline gap-2 mb-6">
              <span class="text-4xl font-extrabold text-emerald-400">50 €</span>
              <span class="text-sm font-medium text-zinc-400">/ 10 výpisov</span>
            </div>
            <ul class="space-y-3.5 mb-8 flex-1">
              <li class="flex items-start gap-3 text-sm text-zinc-300">
                <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                <span><strong>10 kompletných výpisov</strong></span>
              </li>
              <li class="flex items-start gap-3 text-sm text-zinc-300">
                <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Jednotná sadzba 5,00 € / výpis</span>
              </li>
              <li class="flex items-start gap-3 text-sm text-zinc-300">
                <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Prioritné REST API spracovanie</span>
              </li>
              <li class="flex items-start gap-3 text-sm text-zinc-300">
                <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Forenzný auditný protokol</span>
              </li>
            </ul>
          </div>
          <div>
            <a href="{STRIPE_10_URL}" class="mt-auto w-full py-3 px-6 rounded-xl font-bold text-sm bg-gradient-to-r from-[#0A6A56] via-emerald-600 to-[#0A6A56] hover:brightness-110 text-white shadow-lg shadow-emerald-950/60 border border-emerald-500/40 transition text-center whitespace-nowrap flex items-center justify-center">
              Kúpiť 10 výpisov (50 €)
            </a>
            <div class="text-xs text-zinc-500 text-center mt-3 whitespace-nowrap">
              Karta · Apple Pay · Google Pay
            </div>
          </div>
        </div>

        <!-- PLAN 3: PROFI PARTNER -->
        <div class="relative flex flex-col justify-between p-8 rounded-2xl border border-zinc-800/80 bg-zinc-950/90 transition hover:border-zinc-700/80">
          <div>
            <div class="text-xl font-bold text-white mb-2">Profi Partner</div>
            <div class="text-xs text-zinc-400 mb-6">Balíček 20 kreditov pre účtovníkov</div>
            <div class="flex items-baseline gap-2 mb-6">
              <span class="text-4xl font-extrabold text-white">100 €</span>
              <span class="text-sm font-medium text-zinc-400">/ 20 výpisov</span>
            </div>
            <ul class="space-y-3.5 mb-8 flex-1">
              <li class="flex items-start gap-3 text-sm text-zinc-300">
                <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                <span><strong>20 kompletných výpisov</strong></span>
              </li>
              <li class="flex items-start gap-3 text-sm text-zinc-300">
                <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Plná podpora viacerých firiem</span>
              </li>
              <li class="flex items-start gap-3 text-sm text-zinc-300">
                <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Kredity s neobmedzenou platnosťou</span>
              </li>
            </ul>
          </div>
          <div>
            <a href="{STRIPE_10_URL}" class="mt-auto w-full py-3 px-6 rounded-xl font-bold text-sm bg-zinc-900 hover:bg-zinc-800 text-zinc-200 border border-zinc-700/80 transition text-center whitespace-nowrap flex items-center justify-center">
              Kúpiť 20 výpisov (100 €)
            </a>
            <div class="text-xs text-zinc-500 text-center mt-3 whitespace-nowrap">
              Karta · Apple Pay · Google Pay
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FAQ SECTION -->
  <section id="faq" class="v-faq-section">
    <div class="v-container v-faq-wrap">
      <div class="v-section-head">
        <div class="v-section-kicker">Odpovede</div>
        <h2 class="v-section-h2">Často kladené otázky</h2>
      </div>

      <div class="border-b border-zinc-800/80 py-5 v-faq-box open">
        <button type="button" class="w-full flex items-center justify-between text-left font-medium gap-4 v-faq-q" onclick="this.parentElement.classList.toggle('open')">
          <span>Ako funguje kreditný systém?</span>
          <span class="v-faq-toggle shrink-0">+</span>
        </button>
        <div class="v-faq-a">
          Za každý spracovaný bankový výpis (bez ohľadu na počet pohybov či obrat) sa z vašej peňaženky odpočíta presne 1 kredit (5,00 €). Kredity nemajú žiadnu expiráciu a môžete si ich kedykoľvek dobiť cez Stripe platobnú bránu.
        </div>
      </div>

      <div class="border-b border-zinc-800/80 py-5 v-faq-box">
        <button type="button" class="w-full flex items-center justify-between text-left font-medium gap-4 v-faq-q" onclick="this.parentElement.classList.toggle('open')">
          <span>Čo ak zákazník zaplatí inú sumu než je objednávka?</span>
          <span class="v-faq-toggle shrink-0">+</span>
        </button>
        <div class="v-faq-a">
          Systém disponuje striktnou centovou ochranou. Pri nedoplatku (napr. zaplatených 40 € namiesto 50 €) sa stav objednávky nezmení na Processing a do administrácie e-shopu sa automaticky vloží varovná interná poznámka pre personál.
        </div>
      </div>

      <div class="border-b border-zinc-800/80 py-5 v-faq-box">
        <button type="button" class="w-full flex items-center justify-between text-left font-medium gap-4 v-faq-q" onclick="this.parentElement.classList.toggle('open')">
          <span>Kde sú uložené bankové dáta? Hrozí únik do cloudu?</span>
          <span class="v-faq-toggle shrink-0">+</span>
        </button>
        <div class="v-faq-a">
          Všetky výpisy, pohyby a variabilné symboly sa ukladajú výhradne do vašej lokálnej WordPress databázy. Žiadne citlivé finančné dáta nikdy neopúšťajú váš server.
        </div>
      </div>

      <div class="border-b border-zinc-800/80 py-5 v-faq-box">
        <button type="button" class="w-full flex items-center justify-between text-left font-medium gap-4 v-faq-q" onclick="this.parentElement.classList.toggle('open')">
          <span>Je plugin kompatibilný s WooCommerce HPOS?</span>
          <span class="v-faq-toggle shrink-0">+</span>
        </button>
        <div class="v-faq-a">
          Áno, na 100%. Plugin deklaruje oficiálnu kompatibilitu s High-Performance Order Storage a pracuje priamo s tabuľkou wp_wc_orders s indexovaným vyhľadávaním bez zaťaženia databázy.
        </div>
      </div>
    </div>
  </section>

  <!-- ENTERPRISE FOOTER (STRIPE COMPLIANT, NO PHONE NUMBER) -->
  <footer class="v-footer">
    <div class="v-container">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10 py-12 border-t border-zinc-900">
        <!-- STĹPEC 1 (lg:col-span-5) -->
        <div class="lg:col-span-5">
          <div class="v-brand-title" style="margin-bottom:0.75rem;">
            VORKEI STATEMENT BRIDGE
          </div>
          <p style="margin:0 0 1rem;line-height:1.6;color:#a1a1aa;font-size:0.875rem;max-width:380px;">
            Automatizovaný fintech synchronizačný mostík pre bankové výpisy a e-commerce systémy.
          </p>
          <div style="font-size:0.8125rem;line-height:1.7;color:#71717a;">
            <strong style="color:#d4d4d8;">Prevádzkovateľ:</strong> Eab-EU s.r.o.<br>
            <strong style="color:#d4d4d8;">Sídlo:</strong> Kukučínova 22, 974 01 Banská Bystrica, Slovenská republika<br>
            <strong style="color:#d4d4d8;">Zákaznícka podpora:</strong> <a href="mailto:support@vorkei.com" style="color:#10b981;text-decoration:none;font-weight:600;">support@vorkei.com</a>
          </div>
        </div>

        <!-- STĹPEC 2 (lg:col-span-3) -->
        <div class="lg:col-span-3">
          <div class="v-footer-h4">PRODUKT &amp; API</div>
          <ul class="flex flex-col space-y-2.5 text-sm" style="list-style:none;padding:0;margin:0;">
            <li><a href="{PLUGIN_ZIP_URL}" download style="color:#a1a1aa;text-decoration:none;white-space:nowrap;">Stiahnuť Plugin v1.0.0</a></li>
            <li><a href="#simulator" style="color:#a1a1aa;text-decoration:none;white-space:nowrap;">Živý Simulátor</a></li>
            <li><a href="#cennik" style="color:#a1a1aa;text-decoration:none;white-space:nowrap;">Cenník Kreditov</a></li>
            <li><a href="https://pdf-t3.vorkei.com/wp-json/vorkei/v1/statements" target="_blank" rel="noreferrer" style="color:#a1a1aa;text-decoration:none;white-space:nowrap;">REST API Endpoint</a></li>
          </ul>
        </div>

        <!-- STĹPEC 3 (lg:col-span-4) -->
        <div class="lg:col-span-4">
          <div class="v-footer-h4">PRÁVNE &amp; BEZPEČNOSŤ</div>
          <ul class="flex flex-col space-y-2.5 text-sm" style="list-style:none;padding:0;margin:0;">
            <li><a href="https://vorkei.com" target="_blank" rel="noreferrer" style="color:#a1a1aa;text-decoration:none;white-space:nowrap;">Obchodné podmienky (VOP)</a></li>
            <li><a href="https://vorkei.com" target="_blank" rel="noreferrer" style="color:#a1a1aa;text-decoration:none;white-space:nowrap;">Ochrana osobných údajov (GDPR)</a></li>
            <li><a href="https://vorkei.com" target="_blank" rel="noreferrer" style="color:#a1a1aa;text-decoration:none;white-space:nowrap;">Reklamačný poriadok</a></li>
            <li><span class="whitespace-nowrap" style="color:#10b981;font-weight:600;display:inline-flex;align-items:center;gap:0.4rem;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> 256-bit SSL Zabezpečené</span></li>
          </ul>
        </div>
      </div>

      <!-- 3. SPODNÁ LIŠTA (COPYRIGHT) -->
      <div class="pt-8 border-t border-zinc-900/60 flex flex-col sm:flex-row items-center justify-between text-xs text-zinc-500 gap-4">
        <div>
          © 2026 Eab-EU s.r.o.
        </div>
        <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
          <span>Platby zabezpečené cez <strong style="color:#ffffff;">Stripe Payments Europe</strong></span>
          <span>•</span>
          <span>Visa, Mastercard, Apple Pay, Google Pay</span>
        </div>
      </div>
    </div>
  </footer>
</div>

<script>
/* INTERACTIVE SIMULATOR JS */
function vRunSimulationPipeline() {{
  var btn = document.getElementById("v-sim-trigger");
  var statusBadge = document.getElementById("v-sim-status");
  var timeDisplay = document.getElementById("v-sim-time");
  if (!btn || !statusBadge) return;

  btn.disabled = true;
  btn.innerText = "⏳ Párujem...";
  if (timeDisplay) timeDisplay.innerText = "Spracovanie: 0,02s...";

  setTimeout(function() {{
    statusBadge.style.background = "rgba(16, 185, 129, 0.2)";
    statusBadge.style.color = "#34d399";
    statusBadge.style.borderColor = "rgba(16, 185, 129, 0.45)";
    statusBadge.innerHTML = "<span>●</span> Vybavené (Processing) · Spárované!";
    if (timeDisplay) timeDisplay.innerText = "Latencia: 0,07 sek.";
    btn.disabled = false;
    btn.innerText = "✓ Spárované úspešne";

    setTimeout(function() {{
      btn.innerText = "Spustiť synchronizáciu";
    }}, 2800);
  }}, 700);
}}

/* DYNAMIC ROI CALCULATOR JS */
function vUpdateRoi(val) {{
  var count = parseInt(val, 10) || 1;
  var display = document.getElementById("v-slider-display");
  if (display) display.innerText = count + (count === 1 ? " výpis" : (count < 5 ? " výpisy" : " výpisov"));

  // Approx 1.85 hours saved per statement
  var hoursSaved = (count * 1.85).toFixed(1);
  // Cost per statement = 5 EUR
  var toolCost = count * 5;
  // Accountant hourly cost approx 15 EUR/hr (at 10 statements: 277 - 50 = 227 EUR net)
  var grossSavings = Math.floor(count * 1.85 * 15);
  var netSavings = grossSavings - toolCost;

  var statHours = document.getElementById("v-stat-hours");
  var statCost = document.getElementById("v-stat-cost");
  var statSavings = document.getElementById("v-stat-savings");

  if (statHours) statHours.innerText = hoursSaved.replace(".", ",") + " h";
  if (statCost) statCost.innerText = toolCost + " €";
  if (statSavings) statSavings.innerText = "+ " + netSavings + " €";
}}
</script>
<!-- /wp:html -->"""

print("Deploying Ultra-Minimalist Dark Landing Page to WordPress via REST API...")

# Check if front page already exists
req = urllib.request.Request(
    f"{WP_URL}/wp-json/wp/v2/pages?slug=landing",
    headers={"Authorization": auth_header, "Accept": "application/json"}
)

try:
    with urllib.request.urlopen(req) as resp:
        existing = json.loads(resp.read().decode())
except Exception as e:
    existing = []

payload = {
    "title": "Vorkei Statement Bridge — Bank Statement PDF & WooCommerce Sync",
    "slug": "landing",
    "content": html_content,
    "status": "publish"
}

if existing:
    page_id = existing[0]["id"]
    print(f"Updating existing landing page ID: {page_id}")
    url = f"{WP_URL}/wp-json/wp/v2/pages/{page_id}"
else:
    print("Creating new landing page...")
    url = f"{WP_URL}/wp-json/wp/v2/pages"

req = urllib.request.Request(
    url,
    data=json.dumps(payload).encode("utf-8"),
    headers={
        "Authorization": auth_header,
        "Content-Type": "application/json",
        "Accept": "application/json"
    },
    method="POST"
)

with urllib.request.urlopen(req) as resp:
    res = json.loads(resp.read().decode())
    page_id = res["id"]
    print(f"✅ Landing page successfully updated! ID: {page_id}, URL: {res.get('link')}")

# Confirm static front page
settings_payload = {
    "show_on_front": "page",
    "page_on_front": page_id
}

req_settings = urllib.request.Request(
    f"{WP_URL}/wp-json/wp/v2/settings",
    data=json.dumps(settings_payload).encode("utf-8"),
    headers={
        "Authorization": auth_header,
        "Content-Type": "application/json",
        "Accept": "application/json"
    },
    method="POST"
)

with urllib.request.urlopen(req_settings) as resp:
    settings_res = json.loads(resp.read().decode())
    print(f"✅ WordPress front page confirmed! show_on_front: {settings_res.get('show_on_front')}, page_on_front: {settings_res.get('page_on_front')}")

print("\n🚀 ALL DONE! Ultra-minimalist dark landing page is live at: https://pdf-t3.vorkei.com/")
