import { createServerFn } from "@tanstack/react-start";
import type { CompanyLookupResult } from "./company-lookup.server";

export type { CompanyLookupResult, CompanyAddress } from "./company-lookup.server";

export const lookupCompanyByIco = createServerFn({ method: "POST" })
  .validator((ico: string) => ico)
  .handler(async ({ data: ico }): Promise<CompanyLookupResult> => {
    const { fetchCompanyFromRegistry } = await import("./company-lookup.server");
    return fetchCompanyFromRegistry(ico);
  });

/**
 * Odhad bankového SWIFT kódu podľa slovenského IBANu (prefix SK.. BBBB ....)
 */
export function deriveSwiftFromSkIban(iban: string): string {
  const clean = iban.replace(/\s+/g, "").toUpperCase();
  if (!clean.startsWith("SK") || clean.length < 8) return "";
  const bankCode = clean.slice(4, 8);
  const map: Record<string, string> = {
    "1100": "TATRSKBX",
    "0200": "SUBASKBX",
    "0900": "GIBASKBX",
    "7500": "CEKOSKBX",
    "8330": "FIOZSKBA",
    "5600": "KOMASK2X",
    "3100": "LUBASKBX",
    "6500": "POBNSKBA",
    "8130": "CITISKBA",
    "8170": "INGBSKBX",
    "8410": "MPBSSKBA",
  };
  return map[bankCode] || "";
}
