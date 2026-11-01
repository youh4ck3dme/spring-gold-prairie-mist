#!/usr/bin/env python3
"""
Deploy Stitch Enterprise Dark Landing Page to WordPress CMS on https://pdf-t3.vorkei.com
Source: /home/vorkei/Desktop/stitch-pdf-t3-vorkei-landing.html
Target: Front Page (Page ID 10) via WordPress REST API
"""

import os
import sys
import json
import base64
import re
import urllib.request
import urllib.error

WP_URL = os.environ.get("WP_URL", "https://pdf-t3.vorkei.com")
WP_USER = os.environ.get("WP_USER", "")
WP_APP_PASS = os.environ.get("WP_APP_PASS", "")
PAGE_ID = int(os.environ.get("PAGE_ID", "10"))
SOURCE_FILE = os.environ.get("SOURCE_FILE", "/home/vorkei/Desktop/stitch-pdf-t3-vorkei-landing.html")

STRIPE_TEST_CHECKOUT = "https://buy.stripe.com/test_00w5kE2y3eul8dWaaW6Vq02"
PLUGIN_DOWNLOAD_URL = "https://pdf-t3.vorkei.com/wp-content/uploads/2026/10/vorkei-statement-bridge.zip"

print(f"=== VORKEI STITCH LANDING DEPLOYMENT ===")
print(f"Target site: {WP_URL}")
print(f"Target page ID: {PAGE_ID}")
print(f"Source file: {SOURCE_FILE}")

if not os.path.exists(SOURCE_FILE):
    print(f"❌ CHYBA: Zdrojový súbor neexistuje: {SOURCE_FILE}")
    sys.exit(1)

with open(SOURCE_FILE, "r", encoding="utf-8") as f:
    source_html = f.read()

print(f"✅ Načítaný zdrojový súbor ({len(source_html)} bajtov).")

# Extract tailwind configuration
tw_config_match = re.search(r'<script id="tailwind-config">(.*?)</script>', source_html, re.DOTALL)
tailwind_config = tw_config_match.group(1).strip() if tw_config_match else ""

# Extract style block from head
style_match = re.search(r'<style>(.*?)</style>', source_html, re.DOTALL)
head_style = style_match.group(1).strip() if style_match else ""

# Extract body contents (header, main, footer, and scripts)
body_match = re.search(r'<body[^>]*>(.*?)</body>', source_html, re.DOTALL)
if body_match:
    body_content = body_match.group(1).strip()
else:
    print("❌ CHYBA: Nepodarilo sa nájsť <body> tag v zdrojovom súbore.")
    sys.exit(1)

# Ensure dark theme compatibility and full-bleed layout overrides
custom_overrides_css = """
/* WordPress Theme Full-bleed Overrides */
html, body {
  background-color: #131315 !important;
  color: #e5e1e4 !important;
  margin: 0 !important;
  padding: 0 !important;
  overflow-x: hidden !important;
  min-height: 100vh !important;
}
.wp-site-blocks, .entry-content, .wp-block-post-content {
  max-width: 100% !important;
  padding: 0 !important;
  margin: 0 !important;
  background-color: #131315 !important;
  width: 100% !important;
}
.vorkei-stitch-wrap {
  min-height: 100vh;
  background-color: #131315;
  color: #e5e1e4;
  position: relative;
  overflow-x: hidden;
}
/* Smooth scrolling */
html {
  scroll-behavior: smooth;
}
"""

# Enhance the body content with dynamic checkout handlers
interactive_script = f"""
<script>
  // Force dark class on document root for Tailwind dark variants
  document.documentElement.classList.add('dark');

  // Package Purchase Handler (Single 5 EUR, Starter 50 EUR, Business 100 EUR)
  window.handleBuyPackage = async function(packageKey) {{
    const buttons = document.querySelectorAll(`[data-package="${{packageKey}}"]`);
    buttons.forEach(btn => {{
      btn.dataset.original = btn.innerHTML;
      btn.innerHTML = `
        <span class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
        <span>Pripravujem Stripe Checkout...</span>
      `;
      btn.disabled = true;
    }});

    try {{
      const resp = await fetch('https://pdf-t3.vorkei.com/wp-json/vorkei/v1/checkout/create-session', {{
        method: 'POST',
        headers: {{ 'Content-Type': 'application/json' }},
        body: JSON.stringify({{
          package: packageKey,
          success_url: window.location.origin + '/order-success?session_id={{CHECKOUT_SESSION_ID}}',
          cancel_url: window.location.origin + '/#cennik'
        }})
      }});

      const data = await resp.json();
      if (data && data.checkout_url) {{
        window.location.href = data.checkout_url;
        return;
      }}
    }} catch (err) {{
      console.warn('Checkout API fallback:', err);
    }}

    // Fallback to verified direct Stripe checkout link
    window.location.href = "{STRIPE_TEST_CHECKOUT}";
  }};

  document.addEventListener('DOMContentLoaded', () => {{
    // Wire up the 3 pricing buttons
    const btn1 = document.querySelector('button:has-text("Kúpiť 1 kredit")') || 
                 Array.from(document.querySelectorAll('button')).find(el => el.textContent.includes('Kúpiť 1 kredit'));
    if (btn1) {{
      btn1.setAttribute('data-package', 'single');
      btn1.setAttribute('onclick', "handleBuyPackage('single')");
      btn1.style.cursor = 'pointer';
    }}

    const btn10 = Array.from(document.querySelectorAll('button')).find(el => el.textContent.includes('Dobiť 10 kreditov'));
    if (btn10) {{
      btn10.setAttribute('data-package', 'starter');
      btn10.setAttribute('onclick', "handleBuyPackage('starter')");
      btn10.style.cursor = 'pointer';
    }}

    const btn20 = Array.from(document.querySelectorAll('button')).find(el => el.textContent.includes('Kúpiť 20 kreditov'));
    if (btn20) {{
      btn20.setAttribute('data-package', 'business');
      btn20.setAttribute('onclick', "handleBuyPackage('business')");
      btn20.style.cursor = 'pointer';
    }}

    // Wire up waterfall filter buttons in the terminal simulator
    const filterBtns = document.querySelectorAll('#simulator .flex.items-center.gap-1\\\\.5 button');
    filterBtns.forEach(btn => {{
      btn.addEventListener('click', (e) => {{
        filterBtns.forEach(b => {{
          b.classList.remove('bg-surface-container-high', 'text-primary');
          b.classList.add('text-on-surface-variant');
        }});
        btn.classList.add('bg-surface-container-high', 'text-primary');
        btn.classList.remove('text-on-surface-variant');
      }});
    }});
  }});
</script>
"""

# Construct the integrated HTML
final_html = f"""<!-- wp:html -->
<!DOCTYPE html>
<!-- Google Fonts: Inter & JetBrains Mono -->
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="" />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet" />
<!-- Material Symbols Outlined -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
<!-- Tailwind CSS v3 CDN -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
{tailwind_config}
</script>
<style>
{head_style}
{custom_overrides_css}
</style>

<div class="vorkei-stitch-wrap dark bg-background text-on-surface antialiased selection:bg-primary-container selection:text-on-primary-container min-h-screen flex flex-col justify-between overflow-x-hidden">
{body_content}
</div>

{interactive_script}
<!-- /wp:html -->"""

print(f"✅ Transformovaný Gutenberg HTML payload zostavený ({len(final_html)} bajtov).")

# Save a local preview artifact
preview_path = "/home/vorkei/Desktop/stitch-landing-preview.html"
with open(preview_path, "w", encoding="utf-8") as f:
    f.write(final_html)
print(f"📄 Lokálny preview súbor uložený: {preview_path}")

# Check authentication
if not WP_USER or not WP_APP_PASS:
    print("\n⚠️  UPOZORNENIE: Chýbajú premenné prostredia WP_USER a WP_APP_PASS.")
    print("Na odoslanie cez WordPress REST API spustite tento skript s platnými údajmi:")
    print("  WP_USER='admin' WP_APP_PASS='xxxx xxxx xxxx xxxx' python3 scripts/deploy-stitch-landing.py")
    sys.exit(2)

auth_header = "Basic " + base64.b64encode(f"{WP_USER}:{WP_APP_PASS}".encode()).decode()

payload = {
    "title": "PDF-T3 · Vorkei Statement Bridge | Tatra banka PDF automatizácia pre WooCommerce HPOS",
    "slug": "landing",
    "content": final_html,
    "status": "publish",
    "template": "landing-canvas"
}

target_url = f"{WP_URL}/wp-json/wp/v2/pages/{PAGE_ID}"
print(f"\n🚀 Odosielam požiadavku na {target_url}...")

req = urllib.request.Request(
    target_url,
    data=json.dumps(payload).encode("utf-8"),
    headers={
        "Authorization": auth_header,
        "Content-Type": "application/json",
        "Accept": "application/json"
    },
    method="POST"
)

try:
    with urllib.request.urlopen(req) as resp:
        res = json.loads(resp.read().decode("utf-8"))
        print(f"✅ Stránka úspešne aktualizovaná! ID: {res.get('id')}, URL: {res.get('link')}")
except urllib.error.HTTPError as e:
    err_body = e.read().decode("utf-8", errors="replace")
    print(f"❌ HTTP CHYBA {e.code}: {err_body}")
    sys.exit(1)
except Exception as e:
    print(f"❌ CHYBA pripojenia: {e}")
    sys.exit(1)

# Ensure Front Page settings
print("⚙️  Konfigurujem statickú úvodnú stránku v nastaveniach WordPress...")
settings_payload = {
    "show_on_front": "page",
    "page_on_front": PAGE_ID
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

try:
    with urllib.request.urlopen(req_settings) as resp:
        settings_res = json.loads(resp.read().decode("utf-8"))
        print(f"✅ Nastavenia úspešne uložené! show_on_front: {settings_res.get('show_on_front')}, page_on_front: {settings_res.get('page_on_front')}")
except Exception as e:
    print(f"⚠️  Upozornenie pri ukladaní nastavení: {e}")

print(f"\n🎉 HOTOVO! Nová Stitch landing page je publikovaná na: {WP_URL}/")
