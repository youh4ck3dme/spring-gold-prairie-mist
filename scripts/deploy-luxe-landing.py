#!/usr/bin/env python3
"""
Deploy Luxe +150% Visual SaaS Landing Page to WordPress CMS on https://pdf-t3.vorkei.com
Stripe-ready, ROI Calculator, Fintech Pipeline Visualizer, Eab-EU s.r.o. Footer (no phone)
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
   FINTECH LUXE DESIGN TOKENS (Linear / Stripe inspired)
------------------------------------------------------------- */
:root {{
  --v-bg: #ffffff;
  --v-bg-subtle: #f8fafc;
  --v-bg-card: #ffffff;
  --v-border: rgba(15, 23, 42, 0.08);
  --v-border-hover: rgba(16, 185, 129, 0.35);
  --v-border-strong: #cbd5e1;
  --v-text-head: #0f172a;
  --v-text-body: #334155;
  --v-text-muted: #64748b;
  --v-emerald: #059669;
  --v-emerald-light: #ecfdf5;
  --v-emerald-border: #a7f3d0;
  --v-emerald-glow: rgba(5, 150, 105, 0.15);
  --v-blue: #2563eb;
  --v-blue-light: #eff6ff;
  --v-radius-sm: 8px;
  --v-radius-md: 14px;
  --v-radius-lg: 20px;
  --v-shadow-card: 0 4px 20px -2px rgba(15, 23, 42, 0.05), 0 1px 3px 0 rgba(15, 23, 42, 0.03);
  --v-shadow-hover: 0 20px 35px -5px rgba(15, 23, 42, 0.08), 0 10px 15px -5px rgba(16, 185, 129, 0.06);
}}

.vorkei-wrap {{
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  color: var(--v-text-body);
  background: var(--v-bg);
  background-image: 
    radial-gradient(ellipse 80% 50% at 50% -10%, rgba(16, 185, 129, 0.09), transparent),
    radial-gradient(ellipse 60% 40% at 90% 15%, rgba(37, 99, 235, 0.05), transparent);
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;
  padding: 0;
  margin: 0;
  overflow-x: hidden;
}}

.v-mono {{
  font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}}

.v-container {{
  max-width: 1140px;
  margin: 0 auto;
  padding: 0 1.5rem;
}}

/* TOP ANNOUNCEMENT BAR */
.v-topbar {{
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(8px);
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
  color: var(--v-text-head);
}}
.v-dot-live {{
  width: 8px;
  height: 8px;
  background-color: var(--v-emerald);
  border-radius: 50%;
  box-shadow: 0 0 0 3px var(--v-emerald-light);
  display: inline-block;
  animation: vPulse 2s infinite;
}}
@keyframes vPulse {{
  0% {{ box-shadow: 0 0 0 0 rgba(5, 150, 105, 0.4); }}
  70% {{ box-shadow: 0 0 0 6px rgba(5, 150, 105, 0); }}
  100% {{ box-shadow: 0 0 0 0 rgba(5, 150, 105, 0); }}
}}

/* NAVIGATION */
.v-nav {{
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(12px);
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
}}
.v-brand {{
  display: flex;
  align-items: center;
  gap: 0.75rem;
  text-decoration: none;
}}
.v-logo-box {{
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 1.1rem;
  box-shadow: 0 2px 6px rgba(15, 23, 42, 0.15);
}}
.v-brand-title {{
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--v-text-head);
  margin: 0;
  letter-spacing: -0.3px;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}}
.v-badge-pro {{
  font-size: 0.6875rem;
  font-weight: 700;
  background: #0f172a;
  color: #fff;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
}}
.v-nav-links {{
  display: flex;
  gap: 1.75rem;
  align-items: center;
}}
.v-nav-links a {{
  color: var(--v-text-muted);
  text-decoration: none;
  font-size: 0.9375rem;
  font-weight: 500;
  transition: color 0.15s ease;
}}
.v-nav-links a:hover {{
  color: var(--v-text-head);
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
}}
.v-btn-primary {{
  background: #0f172a;
  color: #ffffff !important;
  padding: 0.75rem 1.5rem;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
}}
.v-btn-primary:hover {{
  background: #1e293b;
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(15, 23, 42, 0.2);
}}
.v-btn-emerald {{
  background: var(--v-emerald);
  color: #ffffff !important;
  padding: 0.75rem 1.5rem;
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25);
}}
.v-btn-emerald:hover {{
  background: #047857;
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(5, 150, 105, 0.35);
}}
.v-btn-secondary {{
  background: #ffffff;
  color: var(--v-text-head) !important;
  border-color: var(--v-border-strong);
  padding: 0.75rem 1.5rem;
}}
.v-btn-secondary:hover {{
  background: var(--v-bg-subtle);
  border-color: #94a3b8;
}}

/* HERO SECTION */
.v-hero {{
  padding: 4.5rem 0 3rem;
  text-align: center;
}}
.v-kicker {{
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: var(--v-emerald-light);
  border: 1px solid var(--v-emerald-border);
  color: #065f46;
  font-size: 0.8125rem;
  font-weight: 600;
  padding: 0.35rem 0.85rem;
  border-radius: 9999px;
  margin-bottom: 1.5rem;
}}
.v-hero-title {{
  font-size: 3.25rem;
  font-weight: 800;
  color: var(--v-text-head);
  letter-spacing: -1.5px;
  line-height: 1.15;
  max-width: 820px;
  margin: 0 auto 1.25rem;
}}
.v-hero-desc {{
  font-size: 1.2rem;
  color: var(--v-text-muted);
  max-width: 660px;
  margin: 0 auto 2.25rem;
  line-height: 1.6;
}}
.v-hero-cta {{
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
  margin-bottom: 2.5rem;
}}
.v-trust-badges {{
  display: flex;
  justify-content: center;
  gap: 2rem;
  flex-wrap: wrap;
  font-size: 0.875rem;
  color: var(--v-text-muted);
  font-weight: 500;
}}
.v-trust-item {{
  display: flex;
  align-items: center;
  gap: 0.4rem;
}}
.v-trust-item svg {{
  color: var(--v-emerald);
}}

/* INTERACTIVE PIPELINE VISUALIZER */
.v-pipeline-card {{
  background: #ffffff;
  border: 1px solid var(--v-border);
  border-radius: var(--v-radius-lg);
  padding: 2rem;
  box-shadow: var(--v-shadow-card);
  margin: 3.5rem auto 0;
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
  height: 3px;
  background: linear-gradient(90deg, #059669, #2563eb, #059669);
  background-size: 200% 100%;
  animation: vGradientMove 4s linear infinite;
}}
@keyframes vGradientMove {{
  0% {{ background-position: 0% 0%; }}
  100% {{ background-position: 200% 0%; }}
}}
.v-pipeline-grid {{
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 1.5rem;
}}
@media (max-width: 768px) {{
  .v-pipeline-grid {{
    grid-template-columns: 1fr;
    gap: 1rem;
  }}
}}
.v-pipe-box {{
  background: var(--v-bg-subtle);
  border: 1px solid var(--v-border);
  border-radius: var(--v-radius-md);
  padding: 1.25rem;
  text-align: left;
}}
.v-pipe-tag {{
  font-size: 0.6875rem;
  text-transform: uppercase;
  font-weight: 700;
  letter-spacing: 0.5px;
  color: var(--v-text-muted);
  margin-bottom: 0.5rem;
}}
.v-pipe-mid {{
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}}
.v-pipe-btn {{
  background: #0f172a;
  color: #fff;
  border: none;
  padding: 0.65rem 1.25rem;
  border-radius: 9999px;
  font-weight: 600;
  font-size: 0.8125rem;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
  transition: all 0.2s ease;
  white-space: nowrap;
}}
.v-pipe-btn:hover {{
  transform: scale(1.03);
}}

/* ROI CALCULATOR SECTION */
.v-roi-section {{
  padding: 5rem 0;
  background: var(--v-bg-subtle);
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
  color: var(--v-emerald);
  margin-bottom: 0.5rem;
}}
.v-section-h2 {{
  font-size: 2.25rem;
  font-weight: 800;
  color: var(--v-text-head);
  letter-spacing: -0.8px;
  margin: 0 0 0.75rem;
}}
.v-roi-box {{
  background: #ffffff;
  border: 1px solid var(--v-border);
  border-radius: var(--v-radius-lg);
  padding: 2.5rem;
  box-shadow: var(--v-shadow-card);
  max-width: 880px;
  margin: 0 auto;
}}
.v-slider-row {{
  margin-bottom: 2.5rem;
}}
.v-slider-label {{
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}}
.v-slider-val {{
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--v-emerald);
}}
.v-slider {{
  width: 100%;
  height: 8px;
  border-radius: 4px;
  background: #e2e8f0;
  outline: none;
  -webkit-appearance: none;
}}
.v-slider::-webkit-slider-thumb {{
  -webkit-appearance: none;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: var(--v-emerald);
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.4);
  transition: transform 0.15s ease;
}}
.v-slider::-webkit-slider-thumb:hover {{
  transform: scale(1.15);
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
  background: var(--v-bg-subtle);
  border: 1px solid var(--v-border);
}}
.v-roi-card.highlight {{
  background: var(--v-emerald-light);
  border-color: var(--v-emerald-border);
}}
.v-roi-num {{
  font-size: 2rem;
  font-weight: 800;
  color: var(--v-text-head);
  margin-bottom: 0.25rem;
}}
.v-roi-num.emerald {{
  color: var(--v-emerald);
}}
.v-roi-lbl {{
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--v-text-muted);
}}

/* 3 PILLARS SECTION */
.v-pillars-section {{
  padding: 5rem 0;
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
  background: #ffffff;
  border: 1px solid var(--v-border);
  border-radius: var(--v-radius-md);
  padding: 2rem;
  box-shadow: var(--v-shadow-card);
  transition: all 0.25s ease;
}}
.v-pillar:hover {{
  transform: translateY(-3px);
  border-color: var(--v-border-hover);
  box-shadow: var(--v-shadow-hover);
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
  background: var(--v-emerald-light);
}}
.v-pillar-h3 {{
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--v-text-head);
  margin: 0 0 0.75rem;
}}
.v-pillar-p {{
  font-size: 0.9375rem;
  color: var(--v-text-muted);
  line-height: 1.6;
  margin: 0;
}}

/* PRICING SECTION */
.v-pricing-section {{
  padding: 5rem 0;
  background: var(--v-bg-subtle);
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
  background: #ffffff;
  border: 1px solid var(--v-border);
  border-radius: var(--v-radius-lg);
  padding: 2.25rem;
  display: flex;
  flex-direction: column;
  position: relative;
  transition: all 0.25s ease;
  box-shadow: var(--v-shadow-card);
}}
.v-price-card:hover {{
  transform: translateY(-4px);
  box-shadow: var(--v-shadow-hover);
}}
.v-price-card.featured {{
  border: 2px solid var(--v-emerald);
  box-shadow: 0 12px 30px -5px rgba(5, 150, 105, 0.15);
}}
.v-badge-pop {{
  position: absolute;
  top: -12px;
  left: 50%;
  transform: translateX(-50%);
  background: var(--v-emerald);
  color: #fff;
  font-size: 0.6875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  padding: 0.3rem 0.8rem;
  border-radius: 9999px;
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.3);
}}
.v-plan-name {{
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--v-text-head);
  margin-bottom: 0.5rem;
}}
.v-plan-desc {{
  font-size: 0.875rem;
  color: var(--v-text-muted);
  margin-bottom: 1.5rem;
}}
.v-plan-price {{
  font-size: 2.75rem;
  font-weight: 800;
  color: var(--v-text-head);
  letter-spacing: -1px;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: baseline;
  gap: 0.25rem;
}}
.v-plan-cur {{
  font-size: 1.25rem;
  font-weight: 600;
}}
.v-plan-sub {{
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--v-text-muted);
}}
.v-plan-features {{
  list-style: none;
  padding: 0;
  margin: 0 0 2rem;
  font-size: 0.9375rem;
  color: var(--v-text-body);
  flex-grow: 1;
}}
.v-plan-features li {{
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}}
.v-plan-features li svg {{
  color: var(--v-emerald);
  flex-shrink: 0;
}}
.v-pay-logos {{
  display: flex;
  justify-content: center;
  gap: 0.75rem;
  align-items: center;
  margin-top: 1rem;
  font-size: 0.75rem;
  color: var(--v-text-muted);
}}

/* FAQ SECTION */
.v-faq-section {{
  padding: 5rem 0;
}}
.v-faq-wrap {{
  max-width: 760px;
  margin: 0 auto;
}}
.v-faq-box {{
  border-bottom: 1px solid var(--v-border);
  padding: 1.25rem 0;
}}
.v-faq-q {{
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--v-text-head);
  cursor: pointer;
  user-select: none;
}}
.v-faq-a {{
  margin-top: 0.75rem;
  font-size: 0.9375rem;
  color: var(--v-text-muted);
  line-height: 1.6;
  display: none;
}}
.v-faq-box.open .v-faq-a {{
  display: block;
}}
.v-faq-box.open .v-faq-toggle {{
  transform: rotate(45deg);
}}
.v-faq-toggle {{
  font-size: 1.25rem;
  font-weight: 400;
  color: var(--v-text-muted);
  transition: transform 0.2s ease;
}}

/* COMPLIANT FOOTER (NO PHONE NUMBER) */
.v-footer {{
  background: #ffffff;
  border-top: 1px solid var(--v-border);
  padding: 3.5rem 0 2rem;
  font-size: 0.875rem;
  color: var(--v-text-muted);
}}
.v-footer-grid {{
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: 3rem;
  margin-bottom: 3rem;
}}
@media (max-width: 768px) {{
  .v-footer-grid {{
    grid-template-columns: 1fr;
    gap: 2rem;
  }}
}}
.v-footer-h4 {{
  font-size: 0.8125rem;
  text-transform: uppercase;
  font-weight: 700;
  letter-spacing: 0.8px;
  color: var(--v-text-head);
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
  color: var(--v-text-muted);
  text-decoration: none;
  transition: color 0.15s ease;
}}
.v-footer-links a:hover {{
  color: var(--v-text-head);
}}
.v-footer-bottom {{
  border-top: 1px solid var(--v-border);
  padding-top: 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}}
</style>

<div class="vorkei-wrap">
  <!-- ANNOUNCEMENT BAR -->
  <div class="v-topbar">
    <div class="v-container v-topbar-flex">
      <div class="v-topbar-badge">
        <span class="v-dot-live"></span>
        <span>Produkčný Mostík pre Tatra banka PDF &amp; WooCommerce HPOS</span>
      </div>
      <div>
        <span>Jednotná sadzba: 5,00 € / výpis</span> • 
        <span>Stripe Payments Verified</span>
      </div>
    </div>
  </div>

  <!-- NAVIGATION -->
  <nav class="v-nav">
    <div class="v-container v-nav-flex">
      <a href="https://pdf-t3.vorkei.com" class="v-brand">
        <div class="v-logo-box">V</div>
        <div class="v-brand-title">
          Vorkei Statement Bridge <span class="v-badge-pro">PRO</span>
        </div>
      </a>
      <div class="v-nav-links">
        <a href="#simulator">Simulátor</a>
        <a href="#kalkulacka">ROI Kalkulačka</a>
        <a href="#vyhody">Výhody</a>
        <a href="#cennik">Cenník</a>
        <a href="#faq">FAQ</a>
      </div>
      <a href="#cennik" class="v-btn v-btn-emerald" style="padding:0.5rem 1rem;font-size:0.875rem;">
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
      <h1 class="v-hero-title">
        Premeňte bankový výpis na vybavené objednávky za 3 sekundy.
      </h1>
      <p class="v-hero-desc">
        Automatizovaný fintech mostík pre Tatra banka PDF výpisy a WooCommerce HPOS. Žiadne mesačné bankové zmluvy, žiadny externý cloud – vaše finančné dáta ostávajú 100% on-premise na vašom vlastnom hostingu.
      </p>
      <div class="v-hero-cta">
        <a href="#cennik" class="v-btn v-btn-primary" style="padding:0.85rem 1.75rem;font-size:1rem;">
          Dobíť Kredity (od 5,00 €)
        </a>
        <a href="#simulator" class="v-btn v-btn-secondary" style="padding:0.85rem 1.5rem;font-size:1rem;">
          Vyskúšať Živý Simulátor
        </a>
      </div>
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

      <!-- LIVE PIPELINE VISUALIZER -->
      <div id="simulator" class="v-pipeline-card">
        <div class="v-pipeline-grid">
          <!-- INPUT BOX -->
          <div class="v-pipe-box">
            <div class="v-pipe-tag">1. Bankový Výpis (Tatra banka)</div>
            <div class="v-mono" style="font-size:0.875rem;font-weight:600;color:var(--v-text-head);margin-bottom:0.25rem;">
              + 189,50 EUR
            </div>
            <div class="v-mono" style="font-size:0.8125rem;color:var(--v-text-muted);">
              VS: 1042 • Účet: SK66 1100...
            </div>
            <div style="font-size:0.75rem;color:#059669;margin-top:0.4rem;font-weight:600;">
              ✓ SHA-256 Validovaný
            </div>
          </div>

          <!-- MID TRIGGER -->
          <div class="v-pipe-mid">
            <button id="v-sim-trigger" class="v-pipe-btn" type="button" onclick="vRunSimulationPipeline()">
              ▶ Spustiť synchronizáciu
            </button>
            <span id="v-sim-time" class="v-mono" style="font-size:0.6875rem;color:var(--v-text-muted);">
              Latencia: 0,07 sek.
            </span>
          </div>

          <!-- OUTPUT BOX -->
          <div class="v-pipe-box" style="border-left:3px solid var(--v-emerald);">
            <div class="v-pipe-tag">2. WooCommerce Objednávka #1042</div>
            <div style="font-size:0.875rem;font-weight:600;color:var(--v-text-head);margin-bottom:0.25rem;">
              Zákazník: Peter Novák (189,50 €)
            </div>
            <div id="v-sim-status" style="display:inline-flex;align-items:center;gap:0.35rem;padding:0.2rem 0.6rem;border-radius:12px;background:#fef3c7;color:#b45309;font-size:0.75rem;font-weight:700;">
              <span>●</span> Čaká na platbu (Pending)
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- ROI CALCULATOR SECTION -->
  <section id="kalkulacka" class="v-roi-section">
    <div class="v-container">
      <div class="v-section-head">
        <div class="v-section-kicker">Kalkulačka Návratnosti</div>
        <h2 class="v-section-h2">Koľko peňazí a hodín ušetríte každý mesiac?</h2>
        <p style="color:var(--v-text-muted);font-size:1.05rem;margin:0;">
          Posuňte posuvník a pozrite si okamžitú úsporu pri vašom mesačnom objeme výpisov.
        </p>
      </div>

      <div class="v-roi-box">
        <div class="v-slider-row">
          <div class="v-slider-label">
            <span style="font-weight:700;color:var(--v-text-head);font-size:1.05rem;">Mesačný počet bankových výpisov:</span>
            <span id="v-slider-display" class="v-slider-val v-mono">10 výpisov</span>
          </div>
          <input id="v-roi-slider" type="range" min="1" max="50" value="10" class="v-slider" oninput="vUpdateRoi(this.value)">
        </div>

        <div class="v-roi-stats">
          <div class="v-roi-card">
            <div id="v-stat-hours" class="v-roi-num v-mono">18,5 h</div>
            <div class="v-roi-lbl">Ušetrený čas v účtovníctve</div>
          </div>
          <div class="v-roi-card">
            <div id="v-stat-cost" class="v-roi-num v-mono">50 €</div>
            <div class="v-roi-lbl">Náklady na Vorkei Bridge (5 €/výpis)</div>
          </div>
          <div class="v-roi-card highlight">
            <div id="v-stat-savings" class="v-roi-num v-mono emerald">+ 227 €</div>
            <div class="v-roi-lbl" style="color:#065f46;">Čistá mesačná úspora (ROI)</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 3 KEY PILLARS -->
  <section id="vyhody" class="v-pillars-section">
    <div class="v-container">
      <div class="v-section-head">
        <div class="v-section-kicker">Prečo Vorkei Bridge</div>
        <h2 class="v-section-h2">Architektúra stavaná pre maximálnu bezpečnosť</h2>
        <p style="color:var(--v-text-muted);font-size:1.05rem;margin:0;">
          Žiadne kompromisy v bezpečnosti ani skryté bankové poplatky.
        </p>
      </div>

      <div class="v-pillars-grid">
        <div class="v-pillar">
          <div class="v-pillar-icon">⚡</div>
          <h3 class="v-pillar-h3">Okamžité párovanie do 3 sekúnd</h3>
          <p class="v-pillar-p">
            Namiesto hodín strávených čítaním výpisu riadok po riadku algoritmus overí variabilné symboly, IBAN a presné centové sumy za menej ako 0,07 sekundy.
          </p>
        </div>
        <div class="v-pillar">
          <div class="v-pillar-icon">🔒</div>
          <h3 class="v-pillar-h3">100% On-Premise &amp; Bank Secret Safe</h3>
          <p class="v-pillar-p">
            Žiadne bankové pohyby neodchádzajú do externého cloudu. Všetko beží výhradne vo vašej vlastnej WordPress databáze pod vašou kontrolou.
          </p>
        </div>
        <div class="v-pillar">
          <div class="v-pillar-icon">💳</div>
          <h3 class="v-pillar-h3">0 € Mesačných Poplatkov Banke</h3>
          <p class="v-pillar-p">
            Zabudnite na zložité PSD2 zmluvy s Tatra bankou za 100+ € mesačne. Nástroj funguje priamo s bežným PDF výpisom a platíte len férových 5 € za spracovaný výpis.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- PRICING SECTION -->
  <section id="cennik" class="v-pricing-section">
    <div class="v-container">
      <div class="v-section-head">
        <div class="v-section-kicker">Férový Model</div>
        <h2 class="v-section-h2">Jednotný cenník: 5,00 € za výpis</h2>
        <p style="color:var(--v-text-muted);font-size:1.05rem;margin:0;">
          Žiadne mesačné paušály. Kredit si dobijete kartou a miniete, kedy potrebujete.
        </p>
      </div>

      <div class="v-pricing-grid">
        <!-- PLAN 1 -->
        <div class="v-price-card">
          <div class="v-plan-name">Štart</div>
          <div class="v-plan-desc">1 kredit na okamžité vyskúšanie</div>
          <div class="v-plan-price">
            <span class="v-plan-cur">€</span>5<span class="v-plan-sub"> / výpis</span>
          </div>
          <ul class="v-plan-features">
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              Spracovanie 1 kompletného výpisu
            </li>
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              Neobmedzený počet pohybov
            </li>
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              Automatické Woo párovanie
            </li>
          </ul>
          <a href="{STRIPE_10_URL}" class="v-btn v-btn-secondary" style="width:100%;">
            Kúpiť 1 výpis (5 €)
          </a>
          <div class="v-pay-logos">Karta • Apple Pay • Google Pay</div>
        </div>

        <!-- PLAN 2 (FEATURED) -->
        <div class="v-price-card featured">
          <div class="v-badge-pop">🔥 NAJPOPULÁRNEJŠIE PRE FLOTILY</div>
          <div class="v-plan-name">Flotila &amp; E-shop</div>
          <div class="v-plan-desc">Balíček 10 kreditov pre aktívnu prevádzku</div>
          <div class="v-plan-price">
            <span class="v-plan-cur">€</span>50<span class="v-plan-sub"> / 10 výpisov</span>
          </div>
          <ul class="v-plan-features">
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              <strong>10 kompletných výpisov</strong>
            </li>
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              Jednotná sadzba 5,00 € / výpis
            </li>
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              Prioritné REST API spracovanie
            </li>
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              Forenzný auditný protokol
            </li>
          </ul>
          <a href="{STRIPE_10_URL}" class="v-btn v-btn-emerald" style="width:100%;">
            Kúpiť 10 výpisov (50 €)
          </a>
          <div class="v-pay-logos">Okamžité pripísanie kreditov cez Stripe</div>
        </div>

        <!-- PLAN 3 -->
        <div class="v-price-card">
          <div class="v-plan-name">Profi Partner</div>
          <div class="v-plan-desc">Balíček 20 kreditov pre účtovníkov</div>
          <div class="v-plan-price">
            <span class="v-plan-cur">€</span>100<span class="v-plan-sub"> / 20 výpisov</span>
          </div>
          <ul class="v-plan-features">
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              <strong>20 kompletných výpisov</strong>
            </li>
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              Plná podpora viacerých firiem
            </li>
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              Kredity s neobmedzenou platnosťou
            </li>
          </ul>
          <a href="{STRIPE_10_URL}" class="v-btn v-btn-secondary" style="width:100%;">
            Kúpiť 20 výpisov (100 €)
          </a>
          <div class="v-pay-logos">Karta • Apple Pay • Google Pay</div>
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

      <div class="v-faq-box open">
        <div class="v-faq-q" onclick="this.parentElement.classList.toggle('open')">
          <span>Ako funguje kreditný systém?</span>
          <span class="v-faq-toggle">+</span>
        </div>
        <div class="v-faq-a">
          Za každý spracovaný bankový výpis (bez ohľadu na počet pohybov či obrat) sa z vašej peňaženky odpočíta presne 1 kredit (5,00 €). Kredity nemajú žiadnu expiráciu a môžete si ich kedykoľvek dobiť cez Stripe platobnú bránu.
        </div>
      </div>

      <div class="v-faq-box">
        <div class="v-faq-q" onclick="this.parentElement.classList.toggle('open')">
          <span>Čo ak zákazník zaplatí inú sumu než je objednávka?</span>
          <span class="v-faq-toggle">+</span>
        </div>
        <div class="v-faq-a">
          Systém disponuje striktnou centovou ochranou. Pri nedoplatku (napr. zaplatených 40 € namiesto 50 €) sa stav objednávky nezmení na Processing a do administrácie e-shopu sa automaticky vloží varovná interná poznámka pre personál.
        </div>
      </div>

      <div class="v-faq-box">
        <div class="v-faq-q" onclick="this.parentElement.classList.toggle('open')">
          <span>Kde sú uložené bankové dáta? Hrozí únik do cloudu?</span>
          <span class="v-faq-toggle">+</span>
        </div>
        <div class="v-faq-a">
          Všetky výpisy, pohyby a variabilné symboly sa ukladajú výhradne do vašej lokálnej WordPress databázy. Žiadne citlivé finančné dáta nikdy neopúšťajú váš server.
        </div>
      </div>

      <div class="v-faq-box">
        <div class="v-faq-q" onclick="this.parentElement.classList.toggle('open')">
          <span>Je plugin kompatibilný s WooCommerce HPOS?</span>
          <span class="v-faq-toggle">+</span>
        </div>
        <div class="v-faq-a">
          Áno, na 100%. Plugin deklaruje oficiálnu kompatibilitu s High-Performance Order Storage a pracuje priamo s tabuľkou wp_wc_orders s indexovaným vyhľadávaním bez zaťaženia databázy.
        </div>
      </div>
    </div>
  </section>

  <!-- ENTERPRISE FOOTER (STRIPE COMPLIANT, NO PHONE NUMBER) -->
  <footer class="v-footer">
    <div class="v-container">
      <div class="v-footer-grid">
        <div>
          <div class="v-brand-title" style="margin-bottom:0.75rem;">
            VORKEI STATEMENT BRIDGE
          </div>
          <p style="margin:0 0 1rem;line-height:1.6;">
            Automatizovaný fintech synchronizačný mostík pre bankové výpisy a e-commerce systémy.
          </p>
          <div style="font-size:0.8125rem;line-height:1.6;color:var(--v-text-muted);">
            <strong>Prevádzkovateľ:</strong> Eab-EU s.r.o.<br>
            <strong>Sídlo:</strong> Kukučínova 22, 974 01 Banská Bystrica, Slovenská republika<br>
            <strong>Zákaznícka podpora:</strong> <a href="mailto:support@vorkei.com" style="color:var(--v-emerald);text-decoration:none;font-weight:600;">support@vorkei.com</a>
          </div>
        </div>

        <div>
          <div class="v-footer-h4">Produkt &amp; API</div>
          <ul class="v-footer-links">
            <li><a href="{PLUGIN_ZIP_URL}" download>Stiahnuť Plugin v1.0.0</a></li>
            <li><a href="#simulator">Živý Simulátor</a></li>
            <li><a href="#cennik">Cenník Kreditov</a></li>
            <li><a href="https://pdf-t3.vorkei.com/wp-json/vorkei/v1/statements" target="_blank" rel="noreferrer">REST API Endpoint</a></li>
          </ul>
        </div>

        <div>
          <div class="v-footer-h4">Právne &amp; Bezpečnosť</div>
          <ul class="v-footer-links">
            <li><a href="https://vorkei.com" target="_blank" rel="noreferrer">Obchodné podmienky (VOP)</a></li>
            <li><a href="https://vorkei.com" target="_blank" rel="noreferrer">Ochrana osobných údajov (GDPR)</a></li>
            <li><a href="https://vorkei.com" target="_blank" rel="noreferrer">Reklamačný poriadok</a></li>
            <li><span style="color:#059669;font-weight:600;">✓ 256-bit SSL Zabezpečené</span></li>
          </ul>
        </div>
      </div>

      <div class="v-footer-bottom">
        <div>
          © 2026 Eab-EU s.r.o. Všetky práva vyhradené. Vytvorené pre Tatra banka &amp; WooCommerce.
        </div>
        <div style="display:flex;gap:1rem;align-items:center;">
          <span>Platby zabezpečené cez <strong>Stripe Payments Europe</strong></span>
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
  timeDisplay.innerText = "Spracovanie: 0,02s...";

  setTimeout(function() {{
    statusBadge.style.background = "#dcfce7";
    statusBadge.style.color = "#15803d";
    statusBadge.innerHTML = "<span>●</span> Vybavené (Processing) · Spárované!";
    timeDisplay.innerText = "Dokončené za 0,07 sek. ✓";
    btn.disabled = false;
    btn.innerText = "✓ Spárované úspešne";
  }}, 700);
}}

/* DYNAMIC ROI CALCULATOR JS */
function vUpdateRoi(val) {{
  var count = parseInt(val, 10) || 1;
  var display = document.getElementById("v-slider-display");
  if (display) display.innerText = count + (count === 1 ? " výpis" : (count < 5 ? " výpisy" : " výpisov"));

  // Approx 1.85 hours saved per statement (manual matching, checking VS, calls)
  var hoursSaved = (count * 1.85).toFixed(1);
  // Cost per statement = 5 EUR
  var toolCost = count * 5;
  // Accountant hourly cost approx 15 EUR/hr
  var grossSavings = Math.round(count * 1.85 * 15);
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

print("Deploying Luxe +150% Landing Page to WordPress via REST API...")

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

# Set as static front page
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

print("\n🚀 ALL DONE! Luxe landing page is live at: https://pdf-t3.vorkei.com/")
