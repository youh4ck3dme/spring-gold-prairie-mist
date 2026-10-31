export interface CompanyAddress {
  street: string | null;
  city: string | null;
  postal_code: string | null;
  country: string | null;
}

export type VerificationStatus = "verified_active" | "verified_risky" | "unconfirmed" | "not_found";

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
  verified: boolean;
  verified_at?: string;
  source?: string;
  verification_status: VerificationStatus;
}

// Bounded LRU Cache (max 500 entries, 30 min TTL)
const MAX_CACHE_SIZE = 500;
const CACHE_TTL_MS = 30 * 60 * 1000;

interface CacheEntry {
  timestamp: number;
  data: CompanyLookupResult;
}

class BoundedLruCache {
  private store = new Map<string, CacheEntry>();

  get(key: string): CompanyLookupResult | null {
    const entry = this.store.get(key);
    if (!entry) return null;
    if (Date.now() - entry.timestamp > CACHE_TTL_MS) {
      this.store.delete(key);
      return null;
    }
    // Refresh LRU order (delete & re-set puts it at the end)
    this.store.delete(key);
    this.store.set(key, entry);
    return entry.data;
  }

  set(key: string, data: CompanyLookupResult): void {
    if (this.store.has(key)) {
      this.store.delete(key);
    } else if (this.store.size >= MAX_CACHE_SIZE) {
      const oldestKey = this.store.keys().next().value;
      if (oldestKey) this.store.delete(oldestKey);
    }
    this.store.set(key, { timestamp: Date.now(), data });
  }

  clear(): void {
    this.store.clear();
  }
}

const lookupCache = new BoundedLruCache();

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
      verified: false,
      verification_status: "not_found",
      error: "IČO musí obsahovať 6 až 8 číslic.",
    };
  }

  // Check cache
  const cached = lookupCache.get(cleanIco);
  if (cached) {
    return cached;
  }

  const endpoint = process.env.COMPANY_API_URL || `https://ico-api.vorkei.com/api/company/proxy-search?ico=${encodeURIComponent(cleanIco)}`;

  try {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 6000);

    const res = await fetch(endpoint, {
      signal: controller.signal,
      headers: {
        Accept: "application/json",
        "X-ICO-LOOKUP-CONTRACT": "js_default",
      },
    });
    clearTimeout(timer);

    if (!res.ok) {
      throw new Error(`Register vrátil kód ${res.status}`);
    }

    const json = (await res.json()) as Record<string, unknown>;

    // Strict response schema validation
    if (!json || typeof json !== "object" || !json.found || typeof json.name !== "string" || !json.name.trim()) {
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
        verified: false,
        verification_status: "not_found",
        error: "Subjekt s týmto IČO nebol nájdený v registri SR.",
      };
      lookupCache.set(cleanIco, notFoundResult);
      return notFoundResult;
    }

    const rawName = String(json.name).trim();
    const rawStatus = typeof json.status === "string" ? json.status.trim() : "";
    const statusLower = rawStatus.toLowerCase();
    const nameLower = rawName.toLowerCase();
    const dissolvedOn = typeof json.dissolved_on === "string" && json.dissolved_on.trim() ? json.dissolved_on.trim() : null;
    const isDissolved = Boolean(dissolvedOn);
    const isExplicitlyInactive = json.active === false;

    const hasLiquidationStatus =
      statusLower.includes("likvid") ||
      statusLower.includes("konkurz") ||
      statusLower.includes("zanik") ||
      statusLower.includes("zrušen") ||
      nameLower.includes("v likvidácii") ||
      nameLower.includes("v konkurze");

    const isRisky = isDissolved || isExplicitlyInactive || hasLiquidationStatus;

    // A company is confirmed active ONLY if NOT risky AND status explicitly confirms activity
    const hasExplicitActiveStatus = json.active === true || statusLower.includes("aktívny") || statusLower.includes("aktivny");
    const isConfirmedActive = !isRisky && hasExplicitActiveStatus;

    let verificationStatus: VerificationStatus = "unconfirmed";
    let verified = false;
    let displayStatus = rawStatus;

    if (isRisky) {
      verificationStatus = "verified_risky";
      verified = true;
      displayStatus = rawStatus || (isDissolved ? `Zaniknutý (${dissolvedOn})` : "Neaktívny");
    } else if (isConfirmedActive) {
      verificationStatus = "verified_active";
      verified = true;
      displayStatus = rawStatus || "Aktívny subjekt";
    } else {
      // Missing or unconfirmed legal state — cannot be marked green/active!
      verificationStatus = "unconfirmed";
      verified = false;
      displayStatus = rawStatus || "Nezistený právny stav";
    }

    let riskReason: string | undefined = undefined;
    if (isRisky) {
      const details = displayStatus || (isDissolved ? `zaniknutý dňa ${dissolvedOn}` : "neaktívny");
      riskReason = `⚠️ Pozor! Tento subjekt je zrušený / v likvidácii (${details}). Platba môže predstavovať daňové alebo právne riziko!`;
    }

    const addr = (typeof json.address === "object" && json.address !== null ? json.address : {}) as Record<string, unknown>;
    const street = typeof addr.street === "string" ? addr.street.trim() : "";
    const city = typeof addr.city === "string" ? addr.city.trim() : "";
    const zip = typeof addr.postal_code === "string" ? addr.postal_code.trim() : "";
    const country = typeof addr.country === "string" ? addr.country.trim() : "Slovensko";

    const addressParts = [street, `${zip} ${city}`.trim(), country].filter(Boolean);
    const formattedAddress = addressParts.join("\n");

    const result: CompanyLookupResult = {
      found: true,
      ico: cleanIco,
      name: rawName,
      status: displayStatus,
      active: isConfirmedActive,
      address: {
        street: street || null,
        city: city || null,
        postal_code: zip || null,
        country: country || "Slovensko",
      },
      legal_form: typeof json.legal_form === "string" ? json.legal_form.trim() : null,
      founded_on: typeof json.founded_on === "string" ? json.founded_on.trim() : null,
      dissolved_on: dissolvedOn,
      is_risky: isRisky,
      risk_reason: riskReason,
      formatted_address: formattedAddress,
      verified,
      verified_at: new Date().toISOString(),
      source: "Register účtovných závierok / Obchodný register SR",
      verification_status: verificationStatus,
    };

    lookupCache.set(cleanIco, result);
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
      verified: false,
      verification_status: "not_found",
      error: errorMsg,
    };
  }
}
