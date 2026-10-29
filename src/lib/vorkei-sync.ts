import type { Bank, Books, Client, Movement, Party, Statement } from "@/lib/books";

export interface VorkeiBridgeConfig {
  baseUrl: string;
  username: string;
  appPassword?: string;
  bearerToken?: string;
  autoSync?: boolean;
}

const STORAGE_KEY = "vorkei-bridge-config-v1";

export const DEFAULT_CONFIG: VorkeiBridgeConfig = {
  baseUrl: "https://vorkei.com",
  username: "vorkei",
  appPassword: "",
  bearerToken: "",
  autoSync: false,
};

export function getBridgeConfig(): VorkeiBridgeConfig {
  if (typeof window === "undefined") return DEFAULT_CONFIG;
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return DEFAULT_CONFIG;
    return { ...DEFAULT_CONFIG, ...JSON.parse(raw) };
  } catch {
    return DEFAULT_CONFIG;
  }
}

export function saveBridgeConfig(config: VorkeiBridgeConfig): void {
  if (typeof window === "undefined") return;
  localStorage.setItem(STORAGE_KEY, JSON.stringify(config));
}

function getAuthHeader(config: VorkeiBridgeConfig): Record<string, string> {
  const headers: Record<string, string> = {
    "Content-Type": "application/json",
    Accept: "application/json",
  };

  if (config.bearerToken && config.bearerToken.trim().length > 0) {
    headers["Authorization"] = `Bearer ${config.bearerToken.trim()}`;
  } else if (config.username && config.appPassword) {
    // WordPress Application Passwords use standard Basic Auth
    // Spaces in Application Passwords should be stripped or kept, both are supported by WP
    const cleanPass = config.appPassword.replace(/\s+/g, "");
    const token = btoa(`${config.username.trim()}:${cleanPass}`);
    headers["Authorization"] = `Basic ${token}`;
  }

  return headers;
}

export async function testBridgeConnection(config: VorkeiBridgeConfig): Promise<{
  ok: boolean;
  message: string;
  status: number;
}> {
  const base = config.baseUrl.replace(/\/+$/, "");
  const endpoint = `${base}/wp-json/vorkei/v1/statements`;

  try {
    const res = await fetch(endpoint, {
      method: "GET",
      headers: getAuthHeader(config),
    });

    if (res.ok) {
      return {
        ok: true,
        message: "Spojenie s WordPress REST API na vorkei.com je aktívne a overené!",
        status: res.status,
      };
    }

    if (res.status === 401 || res.status === 403) {
      return {
        ok: false,
        message: "Chyba autentifikácie (401/403): Skontrolujte používateľské meno a aplikačné heslo.",
        status: res.status,
      };
    }

    if (res.status === 404) {
      // Vorkei Bridge endpoint not registered yet, test general WP REST API
      const rootRes = await fetch(`${base}/wp-json/`, { method: "GET" });
      if (rootRes.ok) {
        return {
          ok: false,
          message: "WordPress REST API odpovedá, ale plugin Vorkei Statement Bridge ešte nie je na webe aktivovaný.",
          status: 404,
        };
      }
    }

    return {
      ok: false,
      message: `Server vrátil stav ${res.status}: ${res.statusText}`,
      status: res.status,
    };
  } catch (err) {
    return {
      ok: false,
      message: `Chyba siete: ${(err as Error).message || "Nedá sa pripojiť k serveru vorkei.com"}`,
      status: 0,
    };
  }
}

/**
 * Synchronize local Books (Statements & Movements) to WordPress Vorkei Statement Bridge
 */
export async function pushStatementsToWordPress(
  config: VorkeiBridgeConfig,
  books: Books,
): Promise<{
  success: boolean;
  message: string;
  stats?: {
    statements_saved: number;
    movements_saved: number;
    orders_matched: number;
  };
}> {
  const base = config.baseUrl.replace(/\/+$/, "");
  const endpoint = `${base}/wp-json/vorkei/v1/sync`;

  const defaultClient = books.clients[0];

  const payload = {
    account: {
      iban: defaultClient ? defaultClient.iban : "",
      name: defaultClient ? defaultClient.name : "",
    },
    statements: books.statements.map((st) => {
      const client = books.clients.find((c) => c.id === st.client_id) ?? defaultClient;
      const bank = (client?.bank_id ? books.banks.find((b) => b.id === client.bank_id) : null) ?? books.banks[0];
      const accountType = client?.type || "Podnikateľský účet";

      let balance = st.opening;
      for (const m of st.movements) {
        if (m.kind === "in") balance += m.amount;
        else balance -= m.amount;
      }

      return {
        id: st.id,
        number: st.number,
        title: `Výpis číslo ${st.number}`,
        slug: `vypis-${st.number}`,
        openingCents: st.opening,
        closingCents: balance,
        currency: client ? client.currency : "EUR",
        issuedOn: st.issued_on,
        bank_name: bank ? bank.name : "Tatra banka, a.s.",
        bank_data: bank ? bank.addess : "",
        client_id: client ? client.client : "",
        client_name: client ? client.name : "",
        client_data: client ? client.address : "",
        client_email: client ? client.email : "",
        account_type: accountType,
        account_number: client ? String(client.number) : "",
        account_iban: client ? client.iban : "",
        account_swift: client ? client.switft : "TATRSKBXXXX",
        movements: st.movements.map((m) => {
          const party =
            m.kind === "in"
              ? books.customers.find((p) => p.id === m.party_id)
              : books.contractors.find((p) => p.id === m.party_id);

          return {
            id: m.id,
            statement_id: Number(st.number) || 0,
            date: m.date,
            amountCents: m.amount,
            amountFormatted: (m.amount / 100).toFixed(2),
            kind: m.kind,
            payment_type:
              m.kind === "in"
                ? "Prijatá platba"
                : m.kind === "out"
                  ? "Odoslaná platba"
                  : "Transakčná daň",
            customer_id: m.kind === "in" && party ? party.id : "",
            contractor_id: m.kind === "out" && party ? party.id : "",
            partyName: party ? party.name : "",
            partyIban: party ? party.iban : "",
            variableSymbol: m.symbol || "",
            note: m.text || "",
            detail: m.text || (m.symbol ? `VS: ${m.symbol}` : "") || m.fee_note || "",
            feeNote: m.fee_note || "",
          };
        }),
      };
    }),
  };

  const res = await fetch(endpoint, {
    method: "POST",
    headers: getAuthHeader(config),
    body: JSON.stringify(payload),
  });

  if (!res.ok) {
    const errorText = await res.text();
    throw new Error(`Chyba synchronizácie (${res.status}): ${errorText}`);
  }

  const result = await res.json();
  return result;
}

/**
 * Fetch Clients, Customers, Contractors, and Banks from JetEngine CCT
 */
export async function pullJetEngineData(
  config: VorkeiBridgeConfig,
): Promise<{
  banks?: Bank[];
  clients?: Client[];
  customers?: Party[];
  contractors?: Party[];
}> {
  const base = config.baseUrl.replace(/\/+$/, "");
  const headers = getAuthHeader(config);

  const fetchCct = async (slug: string) => {
    try {
      const res = await fetch(`${base}/wp-json/jet-cct/${slug}`, { headers });
      if (res.ok) return await res.json();
    } catch {
      // ignore
    }
    return null;
  };

  const [banksData, clientsData, customersData, contractorsData] = await Promise.all([
    fetchCct("banks"),
    fetchCct("clients"),
    fetchCct("customers"),
    fetchCct("contractors"),
  ]);

  const output: {
    banks?: Bank[];
    clients?: Client[];
    customers?: Party[];
    contractors?: Party[];
  } = {};

  if (Array.isArray(banksData)) {
    output.banks = banksData.map((b: any) => ({
      id: String(b._ID || b.id),
      name: b.name || "",
      addess: b.addess || b.address || "",
      logo: b.logo || null,
    }));
  }

  if (Array.isArray(clientsData)) {
    output.clients = clientsData.map((c: any) => ({
      id: String(c._ID || c.id),
      bank_id: String(c.bank_id || ""),
      client: c.client || "",
      name: c.name || "",
      address: c.address || "",
      email: c.email || "",
      type: c.type || "Podnikateľský účet",
      number: String(c.number || ""),
      iban: c.iban || "",
      switft: c.switft || c.swift || "",
      currency: c.currency || "EUR",
    }));
  }

  if (Array.isArray(customersData)) {
    output.customers = customersData.map((cu: any) => ({
      id: String(cu._ID || cu.id),
      client_id: String(cu.client_id || ""),
      name: cu.name || "",
      iban: cu.iban || "",
      swift: cu.swift || "",
    }));
  }

  if (Array.isArray(contractorsData)) {
    output.contractors = contractorsData.map((co: any) => ({
      id: String(co._ID || co.id),
      client_id: String(co.client_id || ""),
      name: co.name || "",
      iban: co.iban || "",
      swift: co.swift || "",
    }));
  }

  return output;
}
