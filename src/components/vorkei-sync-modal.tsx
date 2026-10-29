import { useState, useEffect } from "react";
import { Check, Cloud, CloudUpload, CloudDownload, RefreshCw, X, AlertCircle } from "lucide-react";
import type { Books } from "@/lib/books";
import {
  DEFAULT_CONFIG,
  getBridgeConfig,
  saveBridgeConfig,
  testBridgeConnection,
  pushStatementsToWordPress,
  pullJetEngineData,
  type VorkeiBridgeConfig,
} from "@/lib/vorkei-sync";

interface VorkeiSyncModalProps {
  isOpen: boolean;
  onClose: () => void;
  books: Books;
  onUpdateBooks: (updater: (prev: Books) => Books) => void;
}

export function VorkeiSyncModal({
  isOpen,
  onClose,
  books,
  onUpdateBooks,
}: VorkeiSyncModalProps) {
  const [config, setConfig] = useState<VorkeiBridgeConfig>(DEFAULT_CONFIG);
  const [testing, setTesting] = useState(false);
  const [pushing, setPushing] = useState(false);
  const [pulling, setPulling] = useState(false);
  const [statusMessage, setStatusMessage] = useState<{
    text: string;
    type: "success" | "error" | "info";
  } | null>(null);

  useEffect(() => {
    if (isOpen) {
      setConfig(getBridgeConfig());
      setStatusMessage(null);
    }
  }, [isOpen]);

  if (!isOpen) return null;

  function handleSaveConfig() {
    saveBridgeConfig(config);
    setStatusMessage({ text: "Nastavenia boli úspešne uložené lokálne.", type: "info" });
  }

  async function handleTest() {
    setTesting(true);
    setStatusMessage(null);
    saveBridgeConfig(config);
    try {
      const res = await testBridgeConnection(config);
      setStatusMessage({
        text: res.message,
        type: res.ok ? "success" : "error",
      });
    } catch (err) {
      setStatusMessage({
        text: `Chyba spojenia: ${(err as Error).message}`,
        type: "error",
      });
    } finally {
      setTesting(false);
    }
  }

  async function handlePush() {
    setPushing(true);
    setStatusMessage(null);
    saveBridgeConfig(config);
    try {
      const res = await pushStatementsToWordPress(config, books);
      setStatusMessage({
        text: res.message || "Výpisy boli úspešne synchronizované do WordPressu!",
        type: "success",
      });
    } catch (err) {
      setStatusMessage({
        text: (err as Error).message,
        type: "error",
      });
    } finally {
      setPushing(false);
    }
  }

  async function handlePull() {
    setPulling(true);
    setStatusMessage(null);
    saveBridgeConfig(config);
    try {
      const data = await pullJetEngineData(config);
      let bCount = 0;
      let clCount = 0;
      let cuCount = 0;
      let coCount = 0;

      onUpdateBooks((prev) => {
        const next = { ...prev };

        if (data.banks && data.banks.length > 0) {
          const bankMap = new Map(next.banks.map((b) => [b.id, b]));
          for (const b of data.banks) {
            bankMap.set(b.id, b);
            bCount++;
          }
          next.banks = Array.from(bankMap.values());
        }

        if (data.clients && data.clients.length > 0) {
          const clientMap = new Map(next.clients.map((c) => [c.id, c]));
          for (const c of data.clients) {
            const cleanIban = c.iban?.replace(/\s+/g, "").toUpperCase();
            const existing = Array.from(clientMap.values()).find(
              (ex) => cleanIban && ex.iban?.replace(/\s+/g, "").toUpperCase() === cleanIban,
            );
            if (existing) {
              clientMap.set(existing.id, { ...existing, ...c, id: existing.id });
            } else {
              clientMap.set(c.id, c);
            }
            clCount++;
          }
          next.clients = Array.from(clientMap.values());
        }

        if (data.customers && data.customers.length > 0) {
          const custMap = new Map(next.customers.map((p) => [p.id, p]));
          for (const p of data.customers) {
            const cleanIban = p.iban?.replace(/\s+/g, "").toUpperCase();
            const existing = Array.from(custMap.values()).find(
              (ex) => cleanIban && ex.iban?.replace(/\s+/g, "").toUpperCase() === cleanIban,
            );
            if (existing) {
              custMap.set(existing.id, { ...existing, ...p, id: existing.id });
            } else {
              custMap.set(p.id, p);
            }
            cuCount++;
          }
          next.customers = Array.from(custMap.values());
        }

        if (data.contractors && data.contractors.length > 0) {
          const contMap = new Map(next.contractors.map((p) => [p.id, p]));
          for (const p of data.contractors) {
            const cleanIban = p.iban?.replace(/\s+/g, "").toUpperCase();
            const existing = Array.from(contMap.values()).find(
              (ex) => cleanIban && ex.iban?.replace(/\s+/g, "").toUpperCase() === cleanIban,
            );
            if (existing) {
              contMap.set(existing.id, { ...existing, ...p, id: existing.id });
            } else {
              contMap.set(p.id, p);
            }
            coCount++;
          }
          next.contractors = Array.from(contMap.values());
        }

        return next;
      });

      const totalCount = bCount + clCount + cuCount + coCount;
      if (totalCount > 0) {
        setStatusMessage({
          text: `Úspešne synchronizované: ${clCount} klientov, ${cuCount} odberateľov, ${coCount} dodávateľov, ${bCount} bánk.`,
          type: "success",
        });
      } else {
        setStatusMessage({
          text: "Žiadne nové dáta z JetEngine neboli nájdené alebo REST API vrátilo prázdne zoznamy.",
          type: "info",
        });
      }
    } catch (err) {
      setStatusMessage({
        text: `Chyba pri sťahovaní: ${(err as Error).message}`,
        type: "error",
      });
    } finally {
      setPulling(false);
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
      <div className="relative w-full max-w-lg rounded-2xl bg-[#0f1117] border border-white/10 p-6 shadow-2xl text-white">
        <div className="flex items-center justify-between pb-4 border-b border-white/10">
          <div className="flex items-center gap-2">
            <Cloud className="w-5 h-5 text-blue-400" />
            <h2 className="text-lg font-semibold tracking-wide">WordPress Bridge · vorkei.com</h2>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="p-1 rounded-lg text-white/50 hover:text-white hover:bg-white/10 transition"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        <div className="mt-4 space-y-4">
          <div>
            <label className="block text-xs font-medium text-white/70 mb-1">
              URL WordPressu
            </label>
            <input
              type="text"
              value={config.baseUrl}
              onChange={(e) => setConfig({ ...config, baseUrl: e.target.value })}
              placeholder="https://vorkei.com"
              className="w-full px-3 py-2 rounded-lg bg-black/40 border border-white/15 text-sm focus:outline-none focus:border-blue-500"
            />
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label className="block text-xs font-medium text-white/70 mb-1">
                Používateľ (Username)
              </label>
              <input
                type="text"
                value={config.username}
                onChange={(e) => setConfig({ ...config, username: e.target.value })}
                placeholder="vorkei"
                className="w-full px-3 py-2 rounded-lg bg-black/40 border border-white/15 text-sm focus:outline-none focus:border-blue-500"
              />
            </div>

            <div>
              <label className="block text-xs font-medium text-white/70 mb-1">
                Aplikačné heslo (App Password)
              </label>
              <input
                type="password"
                value={config.appPassword || ""}
                onChange={(e) => setConfig({ ...config, appPassword: e.target.value })}
                placeholder="xxxx xxxx xxxx xxxx"
                className="w-full px-3 py-2 rounded-lg bg-black/40 border border-white/15 text-sm focus:outline-none focus:border-blue-500"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-medium text-white/50 mb-1">
              Alebo Bearer API Token (voliteľné)
            </label>
            <input
              type="password"
              value={config.bearerToken || ""}
              onChange={(e) => setConfig({ ...config, bearerToken: e.target.value })}
              placeholder="Kľúč z administrácie vorkei pluginu"
              className="w-full px-3 py-2 rounded-lg bg-black/40 border border-white/10 text-xs focus:outline-none focus:border-blue-500"
            />
          </div>

          <div className="p-3 rounded-lg bg-white/5 border border-white/10 text-xs flex justify-between items-center text-white/70">
            <span>Lokálny stav aplikácie:</span>
            <span className="font-mono text-white/90">
              {books.statements.length} výpisov · {books.clients.length} účtov · {books.customers.length + books.contractors.length} partnerov
            </span>
          </div>

          {statusMessage && (
            <div
              className={`p-3 rounded-lg text-xs flex items-start gap-2 ${
                statusMessage.type === "success"
                  ? "bg-emerald-950/50 border border-emerald-500/30 text-emerald-300"
                  : statusMessage.type === "error"
                  ? "bg-rose-950/50 border border-rose-500/30 text-rose-300"
                  : "bg-blue-950/50 border border-blue-500/30 text-blue-300"
              }`}
            >
              {statusMessage.type === "error" ? (
                <AlertCircle className="w-4 h-4 shrink-0 mt-0.5" />
              ) : statusMessage.type === "success" ? (
                <Check className="w-4 h-4 shrink-0 mt-0.5" />
              ) : (
                <Cloud className="w-4 h-4 shrink-0 mt-0.5" />
              )}
              <span>{statusMessage.text}</span>
            </div>
          )}

          <div className="pt-2 flex flex-wrap gap-2">
            <button
              type="button"
              onClick={handleTest}
              disabled={testing || pushing || pulling}
              className="px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-medium flex items-center gap-1.5 transition disabled:opacity-50"
            >
              <RefreshCw className={`w-3.5 h-3.5 ${testing ? "animate-spin" : ""}`} />
              Otestovať spojenie
            </button>

            <button
              type="button"
              onClick={handlePull}
              disabled={testing || pushing || pulling}
              title="Stiahne banky, klientov, odberateľov a dodávateľov z JetEngine CCT"
              className="px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-medium flex items-center gap-1.5 transition disabled:opacity-50"
            >
              <CloudDownload className={`w-3.5 h-3.5 ${pulling ? "animate-bounce" : ""}`} />
              Stiahnuť subjekty z WP
            </button>

            <button
              type="button"
              onClick={handlePush}
              disabled={testing || pushing || pulling}
              title="Odošle výpisy a transakcie do CCT 13 a 14 a spáruje objednávky"
              className="ml-auto px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-xs font-medium text-white flex items-center gap-1.5 shadow-lg shadow-blue-500/20 transition disabled:opacity-50"
            >
              <CloudUpload className={`w-3.5 h-3.5 ${pushing ? "animate-bounce" : ""}`} />
              Odoslať výpisy do WP
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
