export interface CompanyAddress {
  street: string | null;
  city: string | null;
  postal_code: string | null;
  country: string | null;
}

export interface CompanyLookupResult {
  found: boolean;
  ico: string;
  name: string | null;
  status: string | null;
  active: boolean;
  address: CompanyAddress;
  legal_form: string | null;
  founded_on: string | null;
  dissolved_on: string | null;
  is_risky: boolean;
  risk_reason?: string;
  formatted_address?: string;
  error?: string;
}

// In-memory cache for fast repeated lookups (TTL 30 minutes)
const cache = new Map<string, { timestamp: number; data: CompanyLookupResult }>();
const CACHE_TTL_MS = 30 * 60 * 1000;

export async function fetchCompanyFromRegistry(rawIco: string): Promise<CompanyLookupResult> {
  const cleanIco = rawIco.replace(/\s+/g, "").padStart(8, "0");

  if (!/^\d{6,8}$/.test(rawIco.replace(/\s+/g, ""))) {
    return {
      found: false,
      ico: rawIco,
      name: null,
      status: null,
      active: false,
      address: { street: null, city: null, postal_code: null, country: null },
      legal_form: null,
      founded_on: null,
      dissolved_on: null,
      is_risky: false,
      error: "IČO musí obsahovať 6 až 8 číslic.",
    };
  }

  // Check cache
  const cached = cache.get(cleanIco);
  if (cached && Date.now() - cached.timestamp < CACHE_TTL_MS) {
    return cached.data;
  }

  const endpoint = `http://2.29.52.59:3010/api/company/proxy-search?ico=${encodeURIComponent(cleanIco)}`;

  try {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 6000);

    const res = await fetch(endpoint, {
      signal: controller.signal,
      headers: {
        Accept: "application/json",
      },
    });
    clearTimeout(timer);

    if (!res.ok) {
      throw new Error(`Register vrátil kód ${res.status}`);
    }

    const json = await res.json();

    if (!json || !json.found || !json.name) {
      const notFoundResult: CompanyLookupResult = {
        found: false,
        ico: cleanIco,
        name: null,
        status: null,
        active: false,
        address: { street: null, city: null, postal_code: null, country: null },
        legal_form: null,
        founded_on: null,
        dissolved_on: null,
        is_risky: false,
        error: "Subjekt s týmto IČO nebol nájdený v registri SR.",
      };
      cache.set(cleanIco, { timestamp: Date.now(), data: notFoundResult });
      return notFoundResult;
    }

    const statusLower = (json.status || "").toLowerCase();
    const nameLower = (json.name || "").toLowerCase();
    const isDissolved = Boolean(json.dissolved_on);
    const isInactive = json.active === false;
    const hasLiquidationStatus =
      statusLower.includes("likvid") ||
      statusLower.includes("konkurz") ||
      statusLower.includes("zanik") ||
      statusLower.includes("zrušen") ||
      nameLower.includes("v likvidácii") ||
      nameLower.includes("v konkurze");

    const isRisky = isDissolved || isInactive || hasLiquidationStatus;

    let riskReason: string | undefined = undefined;
    if (isRisky) {
      const details = json.status || (isDissolved ? `zaniknutý dňa ${json.dissolved_on}` : "neaktívny");
      riskReason = `⚠️ Pozor! Tento subjekt je zrušený / v likvidácii (${details}). Platba môže predstavovať daňové alebo právne riziko!`;
    }

    const addr = json.address || {};
    const street = addr.street || "";
    const city = addr.city || "";
    const zip = addr.postal_code || "";
    const country = addr.country || "Slovensko";

    const addressParts = [street, `${zip} ${city}`.trim(), country].filter(Boolean);
    const formattedAddress = addressParts.join("\n");

    const result: CompanyLookupResult = {
      found: true,
      ico: cleanIco,
      name: json.name.trim(),
      status: json.status || (isInactive ? "Neaktívny" : "Aktívny subjekt"),
      active: !isRisky,
      address: {
        street: addr.street || null,
        city: addr.city || null,
        postal_code: addr.postal_code || null,
        country: addr.country || "Slovensko",
      },
      legal_form: json.legal_form || null,
      founded_on: json.founded_on || null,
      dissolved_on: json.dissolved_on || null,
      is_risky: isRisky,
      risk_reason: riskReason,
      formatted_address: formattedAddress,
    };

    cache.set(cleanIco, { timestamp: Date.now(), data: result });
    return result;
  } catch (err) {
    const errorMsg = (err as Error).message.includes("abort")
      ? "Vypršal časový limit spojenia s registrom SR."
      : `Chyba pri overovaní: ${(err as Error).message}`;

    return {
      found: false,
      ico: cleanIco,
      name: null,
      status: null,
      active: false,
      address: { street: null, city: null, postal_code: null, country: null },
      legal_form: null,
      founded_on: null,
      dissolved_on: null,
      is_risky: false,
      error: errorMsg,
    };
  }
}
