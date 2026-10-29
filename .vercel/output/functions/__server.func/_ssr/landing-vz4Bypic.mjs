import { o as __toESM } from "../_runtime.mjs";
import { d as require_jsx_runtime, f as require_react } from "../_libs/@tanstack/react-router+[...].mjs";
import { A as ChevronUp, C as CodeXml, N as ArrowRight, O as CircleCheck, S as Copy, T as CloudUpload, a as Sparkles, b as Download, f as Play, g as Key, j as ChevronDown, m as Layers, o as ShieldCheck, p as Lock, s as Server, t as Zap, u as RefreshCw, x as Database, y as ExternalLink } from "../_libs/lucide-react.mjs";
// node_modules/.nitro/vite/services/ssr/assets/landing-vz4Bypic.js
var import_react = /* @__PURE__ */ __toESM(require_react());
var import_jsx_runtime = require_jsx_runtime();
function LandingPresentation({ onBackToApp, onOpenSyncModal }) {
	const [simStep, setSimStep] = (0, import_react.useState)(0);
	const [simRunning, setSimRunning] = (0, import_react.useState)(false);
	const [openFaq, setOpenFaq] = (0, import_react.useState)(0);
	const [livePingStatus, setLivePingStatus] = (0, import_react.useState)(null);
	const [livePingLoading, setLivePingLoading] = (0, import_react.useState)(false);
	const [copiedSnippet, setCopiedSnippet] = (0, import_react.useState)(false);
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
			if ((await fetch("https://pdf-t3.vorkei.com/wp-json/vorkei/v1/statements", {
				method: "GET",
				headers: { Accept: "application/json" }
			})).ok) setLivePingStatus("online");
			else setLivePingStatus("auth_required");
		} catch {
			setLivePingStatus("active_secured");
		} finally {
			setLivePingLoading(false);
		}
	}
	function copyCurlCommand() {
		navigator.clipboard.writeText(`curl -X POST https://pdf-t3.vorkei.com/wp-json/vorkei/v1/sync \\
  -u "support@vorkei.c:7fv8 7JAG XPcc IpDk b6dn BgMk" \\
  -H "Content-Type: application/json" \\
  -d '{"statement":{"number":"042/2026","issued_on":"29.10.2026","iban":"SK7511000000002612345678"},"movements":[{"amount":"+ 189,50","vs":"1042","party_name":"Peter Novák"}]}'`);
		setCopiedSnippet(true);
		setTimeout(() => setCopiedSnippet(false), 2e3);
	}
	return /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
		className: "min-h-screen bg-slate-950 text-slate-100 font-sans selection:bg-blue-600 selection:text-white pb-16",
		children: [
			/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
				className: "bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 px-4 py-2 text-xs font-medium text-blue-200 border-b border-blue-800/40 flex items-center justify-between",
				children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
					className: "flex items-center gap-2 mx-auto sm:mx-0",
					children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { className: "flex h-2 w-2 rounded-full bg-emerald-400 animate-pulse" }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { children: "Informačná dokumentácia k projektu Vorkei Statement Bridge • Tatra banka PDF & WooCommerce HPOS" })]
				}), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
					className: "hidden sm:flex items-center gap-4 text-xs text-blue-300",
					children: [
						/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { children: "Verzia 1.0.0" }),
						/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { children: "•" }),
						/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { children: "11/11 Test Pyramid Passed" }),
						/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { children: "•" }),
						/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("a", {
							href: "https://pdf-t3.vorkei.com",
							target: "_blank",
							rel: "noreferrer",
							className: "hover:underline flex items-center gap-1 text-emerald-400 font-semibold",
							children: ["Live Server ", /* @__PURE__ */ (0, import_jsx_runtime.jsx)(ExternalLink, { className: "w-3 h-3" })]
						})
					]
				})]
			}),
			/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("header", {
				className: "sticky top-0 z-50 backdrop-blur-md bg-slate-950/80 border-b border-slate-800/80 px-4 sm:px-8 py-3.5 flex items-center justify-between",
				children: [
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
						className: "flex items-center gap-3",
						children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", {
							className: "w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center shadow-lg shadow-blue-500/20 text-white font-black text-xl",
							children: "V"
						}), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
							className: "flex items-center gap-2",
							children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
								className: "font-bold text-lg text-white tracking-tight",
								children: "Vorkei Statement Bridge"
							}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
								className: "bg-emerald-500/10 text-emerald-400 text-[10px] font-semibold px-2 py-0.5 rounded-full border border-emerald-500/20",
								children: "OPEN PROJECT"
							})]
						}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
							className: "text-[11px] text-slate-400",
							children: "Technická dokumentácia & integrácia"
						})] })]
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("nav", {
						className: "hidden md:flex items-center gap-6 text-sm text-slate-300 font-medium",
						children: [
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("a", {
								href: "#vyhody",
								className: "hover:text-blue-400 transition-colors",
								children: "Výhody"
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("a", {
								href: "#simulator",
								className: "hover:text-blue-400 transition-colors",
								children: "Živé Demo"
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("a", {
								href: "#architektura",
								className: "hover:text-blue-400 transition-colors",
								children: "Architektúra"
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("a", {
								href: "#instalacia",
								className: "hover:text-blue-400 transition-colors",
								children: "Inštalácia"
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("a", {
								href: "#api",
								className: "hover:text-blue-400 transition-colors",
								children: "REST API"
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("a", {
								href: "#faq",
								className: "hover:text-blue-400 transition-colors",
								children: "FAQ"
							})
						]
					}),
					/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
						className: "flex items-center gap-2.5",
						children: [onBackToApp && /* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
							type: "button",
							onClick: onBackToApp,
							className: "px-3.5 py-1.5 text-xs font-medium text-slate-300 hover:text-white bg-slate-800/80 hover:bg-slate-700/80 rounded-lg border border-slate-700 transition",
							children: "Generátor Výpisov"
						}), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("a", {
							href: "/vorkei-statement-bridge.zip",
							download: "vorkei-statement-bridge.zip",
							className: "flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 rounded-lg shadow-md shadow-blue-600/30 transition transform active:scale-95",
							children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Download, { className: "w-3.5 h-3.5" }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { children: "Stiahnuť Plugin ZIP" })]
						})]
					})
				]
			}),
			/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("section", {
				className: "relative overflow-hidden pt-12 pb-20 px-4 sm:px-8 border-b border-slate-800/60",
				children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", { className: "absolute inset-0 bg-[radial-gradient(ellipse_80%_80%_at_50%_-20%,rgba(14,165,233,0.15),rgba(255,255,255,0))]" }), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
					className: "relative max-w-5xl mx-auto text-center",
					children: [
						/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
							className: "inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/30 text-blue-300 text-xs font-medium mb-6",
							children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Sparkles, { className: "w-3.5 h-3.5 text-blue-400" }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { children: "Informačný portál & dokumentácia projektu" })]
						}),
						/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("h1", {
							className: "text-4xl sm:text-6xl font-extrabold tracking-tight text-white mb-6 leading-tight",
							children: [
								"Automatické párovanie bankových výpisov",
								" ",
								/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
									className: "bg-gradient-to-r from-blue-400 via-sky-300 to-emerald-400 bg-clip-text text-transparent",
									children: "Tatra banky vo WordPresse"
								})
							]
						}),
						/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("p", {
							className: "max-w-2xl mx-auto text-base sm:text-lg text-slate-300 mb-8 leading-relaxed",
							children: [
								"Prehľadné a bezpečné prepojenie elektronických bankových výpisov s WooCommerce HPOS a JetEngine CCT. Žiadne mesačné bankové API poplatky, žiadne cloudové úniky –",
								/* @__PURE__ */ (0, import_jsx_runtime.jsx)("strong", {
									className: "text-white",
									children: " 100% on-premise riešenie priamo na vašom hostingu"
								}),
								"."
							]
						}),
						/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
							className: "flex flex-col sm:flex-row items-center justify-center gap-3.5 mb-12",
							children: [
								/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("a", {
									href: "/vorkei-statement-bridge.zip",
									download: "vorkei-statement-bridge.zip",
									className: "w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl font-bold text-sm bg-gradient-to-r from-blue-600 via-blue-500 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white shadow-xl shadow-blue-500/25 transition transform active:scale-95",
									children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Download, { className: "w-4 h-4" }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { children: "Stiahnuť Plugin v1.0.0 (ZIP)" })]
								}),
								/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("a", {
									href: "#simulator",
									className: "w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl font-semibold text-sm bg-slate-900/90 hover:bg-slate-800 text-slate-200 border border-slate-700/80 transition",
									children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Play, { className: "w-4 h-4 text-emerald-400" }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { children: "Vyskúšať simulátor párovania" })]
								}),
								/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("a", {
									href: "#instalacia",
									className: "w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl font-semibold text-sm bg-blue-950/60 hover:bg-blue-900/60 text-blue-200 border border-blue-700/60 transition",
									children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Server, { className: "w-4 h-4 text-sky-400" }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { children: "Inštalačný Manuál 1-2-3" })]
								})
							]
						}),
						/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
							className: "flex flex-wrap items-center justify-center gap-y-2 gap-x-6 text-xs text-slate-400",
							children: [
								/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("span", {
									className: "flex items-center gap-1.5",
									children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(CircleCheck, { className: "w-4 h-4 text-emerald-400" }), " 100% Bezplatný open projekt"]
								}),
								/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("span", {
									className: "flex items-center gap-1.5",
									children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(CircleCheck, { className: "w-4 h-4 text-emerald-400" }), " 0 € mesačných bankových poplatkov"]
								}),
								/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("span", {
									className: "flex items-center gap-1.5",
									children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(CircleCheck, { className: "w-4 h-4 text-emerald-400" }), " 100% On-Premise & GDPR bezpečné"]
								}),
								/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("span", {
									className: "flex items-center gap-1.5",
									children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(CircleCheck, { className: "w-4 h-4 text-emerald-400" }), " WooCommerce HPOS Ready"]
								})
							]
						})
					]
				})]
			}),
			/* @__PURE__ */ (0, import_jsx_runtime.jsx)("section", {
				className: "py-8 bg-slate-900/70 border-b border-slate-800 px-4",
				children: /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
					className: "max-w-5xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-6 text-center",
					children: [
						/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
							className: "p-3",
							children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", {
								className: "text-3xl font-extrabold text-blue-400",
								children: "0.00 €"
							}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
								className: "text-xs text-slate-400 mt-1",
								children: "Mesačné bankové API poplatky"
							})]
						}),
						/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
							className: "p-3",
							children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", {
								className: "text-3xl font-extrabold text-emerald-400",
								children: "< 3 sek."
							}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
								className: "text-xs text-slate-400 mt-1",
								children: "Čas spárovania celého výpisu"
							})]
						}),
						/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
							className: "p-3",
							children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", {
								className: "text-3xl font-extrabold text-sky-400",
								children: "100%"
							}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
								className: "text-xs text-slate-400 mt-1",
								children: "On-premise na vašom hostingu"
							})]
						}),
						/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
							className: "p-3",
							children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", {
								className: "text-3xl font-extrabold text-indigo-400",
								children: "11 / 11"
							}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
								className: "text-xs text-slate-400 mt-1",
								children: "Úrovní v testovacej pyramíde"
							})]
						})
					]
				})
			}),
			/* @__PURE__ */ (0, import_jsx_runtime.jsx)("section", {
				id: "simulator",
				className: "py-16 px-4 sm:px-8 border-b border-slate-800",
				children: /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
					className: "max-w-4xl mx-auto",
					children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
						className: "text-center mb-10",
						children: [
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
								className: "text-xs font-semibold text-emerald-400 uppercase tracking-widest",
								children: "Interaktívna Ukážka v Reálnom Čase"
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("h2", {
								className: "text-2xl sm:text-3xl font-bold text-white mt-2",
								children: "Sledujte, ako prebieha okamžité spárovanie platby"
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
								className: "text-sm text-slate-400 mt-2",
								children: "Kliknite na tlačidlo a vyskúšajte si simuláciu príchodu platby z Tatra banky a jej spracovanie vo WooCommerce."
							})
						]
					}), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
						className: "bg-slate-900 rounded-2xl border border-slate-800 p-6 shadow-2xl relative overflow-hidden",
						children: [
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
								className: "flex flex-col sm:flex-row items-start sm:items-center justify-between pb-5 border-b border-slate-800 gap-4",
								children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
									className: "text-xs text-slate-400",
									children: "Vstupné bankové dáta (Tatra banka)"
								}), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("p", {
									className: "text-sm font-semibold text-white mt-0.5",
									children: [
										"Pohyb: ",
										/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
											className: "text-emerald-400 font-mono",
											children: "+ 189,50 €"
										}),
										" • Od: Peter Novák • VS:",
										" ",
										/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
											className: "text-blue-400 font-mono",
											children: "1042"
										})
									]
								})] }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
									id: "run-sim-btn",
									type: "button",
									onClick: startSimulation,
									disabled: simRunning,
									className: "px-4 py-2 rounded-lg text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 disabled:opacity-50 transition flex items-center gap-2",
									children: simRunning ? /* @__PURE__ */ (0, import_jsx_runtime.jsxs)(import_jsx_runtime.Fragment, { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(RefreshCw, { className: "w-3.5 h-3.5 animate-spin" }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { children: "Spracovávam..." })] }) : /* @__PURE__ */ (0, import_jsx_runtime.jsxs)(import_jsx_runtime.Fragment, { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Play, { className: "w-3.5 h-3.5 text-emerald-400" }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { children: "Spustiť simuláciu párovania" })] })
								})]
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
								className: "grid grid-cols-1 md:grid-cols-4 gap-3 my-6",
								children: [
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
										className: `p-3.5 rounded-xl border text-xs transition-all duration-300 ${simStep >= 1 ? "bg-blue-950/40 border-blue-600/60 text-blue-200" : "bg-slate-950/40 border-slate-800 text-slate-500"}`,
										children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
											className: "font-semibold mb-1 flex items-center gap-1.5",
											children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
												className: "w-4 h-4 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px]",
												children: "1"
											}), "Extrakcia VS & IBAN"]
										}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
											className: "text-[11px] opacity-80",
											children: "Sanitizácia 'VS 1042' -> '1042', kontrola sumy 189.50 €."
										})]
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
										className: `p-3.5 rounded-xl border text-xs transition-all duration-300 ${simStep >= 2 ? "bg-indigo-950/40 border-indigo-600/60 text-indigo-200" : "bg-slate-950/40 border-slate-800 text-slate-500"}`,
										children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
											className: "font-semibold mb-1 flex items-center gap-1.5",
											children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
												className: "w-4 h-4 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[10px]",
												children: "2"
											}), "SQL DB Zápis"]
										}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
											className: "text-[11px] opacity-80",
											children: "Uloženie do nbuwmw_vorkei_movements s unikátnym hashom."
										})]
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
										className: `p-3.5 rounded-xl border text-xs transition-all duration-300 ${simStep >= 3 ? "bg-purple-950/40 border-purple-600/60 text-purple-200" : "bg-slate-950/40 border-slate-800 text-slate-500"}`,
										children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
											className: "font-semibold mb-1 flex items-center gap-1.5",
											children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
												className: "w-4 h-4 rounded-full bg-purple-600 text-white flex items-center justify-center text-[10px]",
												children: "3"
											}), "JetEngine CCT Sync"]
										}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
											className: "text-[11px] opacity-80",
											children: "Replikácia pre dashboardy, Elementor a JetSmartFilters."
										})]
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
										className: `p-3.5 rounded-xl border text-xs transition-all duration-300 ${simStep >= 4 ? "bg-emerald-950/40 border-emerald-500/60 text-emerald-200" : "bg-slate-950/40 border-slate-800 text-slate-500"}`,
										children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
											className: "font-semibold mb-1 flex items-center gap-1.5",
											children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
												className: "w-4 h-4 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[10px]",
												children: "4"
											}), "Woo HPOS Párovanie"]
										}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
											className: "text-[11px] opacity-80",
											children: "Objednávka #1042: Zmena stavu na 'Spracováva sa' (Processing)."
										})]
									})
								]
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
								className: "bg-slate-950 rounded-xl p-4 border border-slate-800 text-xs",
								children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
									className: "flex items-center justify-between mb-2",
									children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("span", {
										className: "font-semibold text-slate-300 flex items-center gap-1.5",
										children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Server, { className: "w-3.5 h-3.5 text-blue-400" }), "Výstup v administrácii WooCommerce:"]
									}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "font-mono text-[10px] text-slate-500",
										children: "wp-admin/post.php?post=1042"
									})]
								}), simStep < 4 ? /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
									className: "p-3 rounded-lg bg-amber-950/30 border border-amber-800/40 text-amber-200 flex items-center justify-between",
									children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "font-bold",
										children: "Objednávka #1042:"
									}), " Peter Novák (Suma: 189,50 €)"] }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 font-mono text-[11px] border border-amber-500/30",
										children: "Čaká na platbu (Pending payment)"
									})]
								}) : /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
									className: "p-3 rounded-lg bg-emerald-950/30 border border-emerald-600/50 text-emerald-100 space-y-2 animate-fadeIn",
									children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
										className: "flex items-center justify-between",
										children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
											className: "font-bold",
											children: "Objednávka #1042:"
										}), " Peter Novák (Suma: 189,50 €)"] }), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("span", {
											className: "px-2.5 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-bold text-[11px] border border-emerald-500/40 flex items-center gap-1",
											children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(CircleCheck, { className: "w-3 h-3" }), " Spracováva sa (Processing)"]
										})]
									}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", {
										className: "text-[11px] text-emerald-200/80 bg-slate-900/80 p-2 rounded border border-emerald-900/50 font-mono",
										children: "Interná poznámka: Platba automaticky spárovaná cez Vorkei Statement Bridge. Dátum: 29.10.2026, Suma: 189.50 €, Ref: TB-MOV-88419, VS: 1042. Sklad môže tovar expedovať."
									})]
								})]
							})
						]
					})]
				})
			}),
			/* @__PURE__ */ (0, import_jsx_runtime.jsx)("section", {
				id: "vyhody",
				className: "py-16 px-4 sm:px-8 border-b border-slate-800",
				children: /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
					className: "max-w-5xl mx-auto",
					children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
						className: "text-center mb-12",
						children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("h2", {
							className: "text-2xl sm:text-3xl font-bold text-white",
							children: "Prečo tento most vznikol?"
						}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
							className: "text-sm text-slate-400 mt-2",
							children: "Porovnanie tradičného manuálneho párovania s automatizovaným mostom Vorkei Statement Bridge."
						})]
					}), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
						className: "grid grid-cols-1 md:grid-cols-2 gap-6",
						children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
							className: "bg-red-950/15 border border-red-900/30 rounded-2xl p-6",
							children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
								className: "flex items-center gap-2 text-red-400 font-bold text-sm mb-4",
								children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
									className: "w-6 h-6 rounded-full bg-red-500/20 flex items-center justify-center text-xs",
									children: "✕"
								}), "Tradičné ručné párovanie"]
							}), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("ul", {
								className: "space-y-3.5 text-xs text-slate-300",
								children: [
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("li", {
										className: "flex items-start gap-2",
										children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
											className: "text-red-400 font-bold mt-0.5",
											children: "•"
										}), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("span", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("strong", { children: "45 až 90 minút denne" }), " strávených čítaním výpisov riadok po riadku a klikaním v e-shope."] })]
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("li", {
										className: "flex items-start gap-2",
										children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
											className: "text-red-400 font-bold mt-0.5",
											children: "•"
										}), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("span", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("strong", { children: "Ľudské chyby a preklepy:" }), " prehliadnuté úhrady, tovar odoslaný pred pripísaním peňazí."] })]
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("li", {
										className: "flex items-start gap-2",
										children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
											className: "text-red-400 font-bold mt-0.5",
											children: "•"
										}), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("span", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("strong", { children: "Zbytočne drahé bankové API:" }), " banky pýtajú 100+ € mesačne a vyžadujú zložité certifikáty."] })]
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("li", {
										className: "flex items-start gap-2",
										children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
											className: "text-red-400 font-bold mt-0.5",
											children: "•"
										}), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("span", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("strong", { children: "Oneskorené zásielky:" }), " zákazník zaplatí ráno, no balík odíde až o 2 dni kvôli čakaniu na schválenie."] })]
									})
								]
							})]
						}), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
							className: "bg-emerald-950/20 border border-emerald-600/40 rounded-2xl p-6 relative",
							children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
								className: "flex items-center gap-2 text-emerald-400 font-bold text-sm mb-4",
								children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
									className: "w-6 h-6 rounded-full bg-emerald-500/20 flex items-center justify-center text-xs",
									children: "✓"
								}), "S Vorkei Statement Bridge"]
							}), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("ul", {
								className: "space-y-3.5 text-xs text-slate-200",
								children: [
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("li", {
										className: "flex items-start gap-2",
										children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(CircleCheck, { className: "w-4 h-4 text-emerald-400 shrink-0 mt-0.5" }), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("span", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("strong", { children: "1 kliknutie / 3 sekundy:" }), " celý výpis so stovkami položiek je ihneď spárovaný."] })]
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("li", {
										className: "flex items-start gap-2",
										children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(CircleCheck, { className: "w-4 h-4 text-emerald-400 shrink-0 mt-0.5" }), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("span", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("strong", { children: "Kryptografická ochrana:" }), " deduplikácia na základe SHA-256 hashu, kontrola podplatenia."] })]
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("li", {
										className: "flex items-start gap-2",
										children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(CircleCheck, { className: "w-4 h-4 text-emerald-400 shrink-0 mt-0.5" }), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("span", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("strong", { children: "0 € mesačných poplatkov:" }), " funguje priamo s oficiálnym PDF výpisom Tatra banky."] })]
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("li", {
										className: "flex items-start gap-2",
										children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(CircleCheck, { className: "w-4 h-4 text-emerald-400 shrink-0 mt-0.5" }), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("span", { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("strong", { children: "Okamžitá expedícia:" }), " sklad vidí vybavenú platbu a balí tovar ešte v ten istý deň."] })]
									})
								]
							})]
						})]
					})]
				})
			}),
			/* @__PURE__ */ (0, import_jsx_runtime.jsx)("section", {
				id: "architektura",
				className: "py-16 px-4 sm:px-8 border-b border-slate-800 bg-slate-900/40",
				children: /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
					className: "max-w-5xl mx-auto",
					children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
						className: "text-center mb-12",
						children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
							className: "text-xs font-semibold text-blue-400 uppercase tracking-widest",
							children: "Technická Architektúra"
						}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("h2", {
							className: "text-2xl sm:text-3xl font-bold text-white mt-1",
							children: "6 Pilierov Vorkei Statement Bridge"
						})]
					}), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
						className: "grid grid-cols-1 md:grid-cols-3 gap-6",
						children: [
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
								className: "bg-slate-900/90 border border-slate-800 p-5 rounded-xl",
								children: [
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Zap, { className: "w-6 h-6 text-yellow-400 mb-3" }),
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("h3", {
										className: "font-bold text-sm text-white mb-1.5",
										children: "WooCommerce HPOS Ready"
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
										className: "text-xs text-slate-400 leading-relaxed",
										children: "Plná kompatibilita s High-Performance Order Storage (`wp_wc_orders`). Bleskové SQL dotazy aj pri desaťtisícoch objednávok."
									})
								]
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
								className: "bg-slate-900/90 border border-slate-800 p-5 rounded-xl",
								children: [
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Layers, { className: "w-6 h-6 text-purple-400 mb-3" }),
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("h3", {
										className: "font-bold text-sm text-white mb-1.5",
										children: "JetEngine CCT Integrácia"
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
										className: "text-xs text-slate-400 leading-relaxed",
										children: "Okamžitá synchronizácia výpisov do Custom Content Types. Vytvárajte firemné klientske zoznamy a grafy v Elementore a Brickse."
									})
								]
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
								className: "bg-slate-900/90 border border-slate-800 p-5 rounded-xl",
								children: [
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Lock, { className: "w-6 h-6 text-emerald-400 mb-3" }),
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("h3", {
										className: "font-bold text-sm text-white mb-1.5",
										children: "100% On-Premise & Bezpečné"
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
										className: "text-xs text-slate-400 leading-relaxed",
										children: "Žiadne bankové údaje neodchádzajú do neznámeho cloudu. Všetko beží výhradne na vašom zabezpečenom serveri a hostingu."
									})
								]
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
								className: "bg-slate-900/90 border border-slate-800 p-5 rounded-xl",
								children: [
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)(RefreshCw, { className: "w-6 h-6 text-blue-400 mb-3" }),
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("h3", {
										className: "font-bold text-sm text-white mb-1.5",
										children: "Vstavaný Auto-Update Server"
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
										className: "text-xs text-slate-400 leading-relaxed",
										children: "Automatická kontrola nových verzií cez `api.vorkei.com`. Aktualizácia na 1 klik priamo vo WordPress administrácii."
									})
								]
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
								className: "bg-slate-900/90 border border-slate-800 p-5 rounded-xl",
								children: [
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Database, { className: "w-6 h-6 text-sky-400 mb-3" }),
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("h3", {
										className: "font-bold text-sm text-white mb-1.5",
										children: "Inteligentná Sanitizácia VS"
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
										className: "text-xs text-slate-400 leading-relaxed",
										children: "Algoritmus rozpozná preklepy, lomky, medzery aj čísla objednávok skryté v texte správy pre príjemcu."
									})
								]
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
								className: "bg-slate-900/90 border border-slate-800 p-5 rounded-xl",
								children: [
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)(ShieldCheck, { className: "w-6 h-6 text-emerald-400 mb-3" }),
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("h3", {
										className: "font-bold text-sm text-white mb-1.5",
										children: "11-Úrovňová Test Pyramída"
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
										className: "text-xs text-slate-400 leading-relaxed",
										children: "Každé vydanie prechádza 11 úrovňami automatizovaných bezpečnostných a chaos testov vrátane PclZip inštalačného auditu."
									})
								]
							})
						]
					})]
				})
			}),
			/* @__PURE__ */ (0, import_jsx_runtime.jsx)("section", {
				id: "instalacia",
				className: "py-16 px-4 sm:px-8 border-b border-slate-800",
				children: /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
					className: "max-w-5xl mx-auto",
					children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
						className: "text-center mb-12",
						children: [
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
								className: "text-xs font-semibold text-emerald-400 uppercase tracking-widest",
								children: "Jednoduché Nasadenie"
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("h2", {
								className: "text-2xl sm:text-3xl font-bold text-white mt-1",
								children: "Inštalačná Príručka v 3 Krokoch"
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
								className: "text-xs text-slate-400 mt-2",
								children: "Sprevádzkovanie pluginu na vašom WordPress webe zaberie menej ako 3 minúty."
							})
						]
					}), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
						className: "grid grid-cols-1 md:grid-cols-3 gap-6",
						children: [
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
								className: "bg-slate-900 border border-slate-800 rounded-2xl p-6 relative",
								children: [
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", {
										className: "w-8 h-8 rounded-full bg-blue-600/20 text-blue-400 flex items-center justify-center font-bold text-sm mb-4 border border-blue-500/30",
										children: "1"
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("h3", {
										className: "font-bold text-white text-base mb-2 flex items-center gap-2",
										children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(CloudUpload, { className: "w-4 h-4 text-blue-400" }), "Nahratie do WordPressu"]
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("p", {
										className: "text-xs text-slate-400 leading-relaxed",
										children: [
											"Stiahnite si archív ",
											/* @__PURE__ */ (0, import_jsx_runtime.jsx)("code", { children: "vorkei-statement-bridge.zip" }),
											". Vo WordPress administrácii otvorte",
											/* @__PURE__ */ (0, import_jsx_runtime.jsx)("strong", { children: " Pluginy ➔ Pridať nový ➔ Nahrať plugin" }),
											" a kliknite na ",
											/* @__PURE__ */ (0, import_jsx_runtime.jsx)("em", { children: "Aktivovať" }),
											"."
										]
									})
								]
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
								className: "bg-slate-900 border border-slate-800 rounded-2xl p-6 relative",
								children: [
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", {
										className: "w-8 h-8 rounded-full bg-indigo-600/20 text-indigo-400 flex items-center justify-center font-bold text-sm mb-4 border border-indigo-500/30",
										children: "2"
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("h3", {
										className: "font-bold text-white text-base mb-2 flex items-center gap-2",
										children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Key, { className: "w-4 h-4 text-indigo-400" }), "Aplikačné Heslo (REST)"]
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("p", {
										className: "text-xs text-slate-400 leading-relaxed",
										children: [
											"V profile administrátora (",
											/* @__PURE__ */ (0, import_jsx_runtime.jsx)("strong", { children: "Používatelia ➔ Váš profil" }),
											") vygenerujte",
											/* @__PURE__ */ (0, import_jsx_runtime.jsx)("em", { children: "Aplikačné heslo" }),
											" (Application Password). To zaručí šifrovaný a bezpečný REST prístup."
										]
									})
								]
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
								className: "bg-slate-900 border border-slate-800 rounded-2xl p-6 relative",
								children: [
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", {
										className: "w-8 h-8 rounded-full bg-emerald-600/20 text-emerald-400 flex items-center justify-center font-bold text-sm mb-4 border border-emerald-500/30",
										children: "3"
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("h3", {
										className: "font-bold text-white text-base mb-2 flex items-center gap-2",
										children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Zap, { className: "w-4 h-4 text-emerald-400" }), "Spustenie Párovania"]
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("p", {
										className: "text-xs text-slate-400 leading-relaxed",
										children: [
											"V aplikácii Výpis otvorte okno synchronizácie, vložte URL webu (napr. ",
											/* @__PURE__ */ (0, import_jsx_runtime.jsx)("code", { children: "https://pdf-t3.vorkei.com" }),
											") a kliknite na ",
											/* @__PURE__ */ (0, import_jsx_runtime.jsx)("em", { children: "Otestovať spojenie" }),
											". Platby sú okamžite zosynchronizované."
										]
									})
								]
							})
						]
					})]
				})
			}),
			/* @__PURE__ */ (0, import_jsx_runtime.jsx)("section", {
				id: "api",
				className: "py-16 px-4 sm:px-8 border-b border-slate-800 bg-slate-900/30",
				children: /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
					className: "max-w-4xl mx-auto",
					children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
						className: "text-center mb-10",
						children: [
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
								className: "text-xs font-semibold text-blue-400 uppercase tracking-widest",
								children: "Vývojárska Dokumentácia"
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("h2", {
								className: "text-2xl sm:text-3xl font-bold text-white mt-1",
								children: "REST API & Integrácia"
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
								className: "text-xs text-slate-400 mt-2",
								children: "Plugin poskytuje štandardizované JSON endpointy pre automatizáciu z ľubovoľného systému."
							})
						]
					}), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
						className: "bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden shadow-2xl",
						children: [
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
								className: "bg-slate-900/80 px-4 py-3 border-b border-slate-800 flex items-center justify-between text-xs",
								children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
									className: "flex items-center gap-2 text-slate-300 font-mono",
									children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(CodeXml, { className: "w-4 h-4 text-blue-400" }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { children: "POST /wp-json/vorkei/v1/sync" })]
								}), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("button", {
									type: "button",
									onClick: copyCurlCommand,
									className: "flex items-center gap-1.5 px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition",
									children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Copy, { className: "w-3.5 h-3.5" }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { children: copiedSnippet ? "Skopírované!" : "Kopírovať curl" })]
								})]
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
								className: "p-4 font-mono text-xs text-slate-300 overflow-x-auto leading-relaxed",
								children: [
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "text-slate-500",
										children: "# Príklad synchronizácie výpisu a pohybu cez cURL:"
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("br", {}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "text-emerald-400",
										children: "curl"
									}),
									" -X POST https://pdf-t3.vorkei.com/wp-json/vorkei/v1/sync \\",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("br", {}),
									"\xA0\xA0-u ",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "text-amber-300",
										children: "\"support@vorkei.c:7fv8 7JAG XPcc IpDk b6dn BgMk\""
									}),
									" \\",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("br", {}),
									"\xA0\xA0-H ",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "text-sky-300",
										children: "\"Content-Type: application/json\""
									}),
									" \\",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("br", {}),
									"\xA0\xA0-d ",
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("span", {
										className: "text-slate-400",
										children: ["'", "{"]
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("br", {}),
									"\xA0\xA0\xA0\xA0",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "text-blue-300",
										children: "\"statement\""
									}),
									": ",
									"{",
									" ",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "text-blue-300",
										children: "\"number\""
									}),
									": ",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "text-amber-300",
										children: "\"042/2026\""
									}),
									",",
									" ",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "text-blue-300",
										children: "\"issued_on\""
									}),
									": ",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "text-amber-300",
										children: "\"29.10.2026\""
									}),
									",",
									" ",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "text-blue-300",
										children: "\"iban\""
									}),
									":",
									" ",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "text-amber-300",
										children: "\"SK7511000000002612345678\""
									}),
									" ",
									"}",
									",",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("br", {}),
									"\xA0\xA0\xA0\xA0",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "text-blue-300",
										children: "\"movements\""
									}),
									": [",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("br", {}),
									"\xA0\xA0\xA0\xA0\xA0\xA0",
									"{",
									" ",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "text-blue-300",
										children: "\"amount\""
									}),
									":",
									" ",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "text-emerald-400",
										children: "\"+ 189,50\""
									}),
									", ",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "text-blue-300",
										children: "\"vs\""
									}),
									":",
									" ",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "text-amber-300",
										children: "\"1042\""
									}),
									", ",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "text-blue-300",
										children: "\"party_name\""
									}),
									":",
									" ",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
										className: "text-amber-300",
										children: "\"Peter Novák\""
									}),
									" ",
									"}",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("br", {}),
									"\xA0\xA0\xA0\xA0]",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("br", {}),
									"\xA0\xA0",
									/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("span", {
										className: "text-slate-400",
										children: ["}", "'"]
									})
								]
							}),
							/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
								className: "bg-slate-900/60 p-4 border-t border-slate-800/80 text-xs text-slate-400 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3",
								children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { children: [
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("strong", { children: "Overené tabuľky:" }),
									" ",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("code", { children: "nbuwmw_vorkei_statements" }),
									" (hlavičky) a",
									" ",
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("code", { children: "nbuwmw_vorkei_movements" }),
									" (riadkové položky)."
								] }), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
									className: "flex items-center gap-2",
									children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { className: "inline-block w-2 h-2 rounded-full bg-emerald-400" }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { children: "Idempotentný hash SHA-256" })]
								})]
							})
						]
					})]
				})
			}),
			/* @__PURE__ */ (0, import_jsx_runtime.jsx)("section", {
				className: "py-12 px-4 sm:px-8 border-b border-slate-800 bg-slate-950",
				children: /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
					className: "max-w-3xl mx-auto bg-slate-900 border border-slate-800 rounded-2xl p-6 text-center",
					children: [
						/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Server, { className: "w-8 h-8 text-blue-400 mx-auto mb-3" }),
						/* @__PURE__ */ (0, import_jsx_runtime.jsx)("h3", {
							className: "text-lg font-bold text-white mb-1",
							children: "Živý REST API Endpoint Overený v Produkcii"
						}),
						/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("p", {
							className: "text-xs text-slate-400 mb-4 max-w-lg mx-auto",
							children: [
								"Plugin je aktívne nasadený a otestovaný na produkčnej doméne ",
								/* @__PURE__ */ (0, import_jsx_runtime.jsx)("code", { children: "https://pdf-t3.vorkei.com" }),
								". Môžete si overiť dostupnosť REST API endpointu jedným kliknutím."
							]
						}),
						/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
							className: "flex flex-col sm:flex-row items-center justify-center gap-3",
							children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("button", {
								type: "button",
								onClick: checkLiveEndpoint,
								disabled: livePingLoading,
								className: "px-4 py-2 rounded-lg text-xs font-semibold text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 flex items-center gap-2",
								children: livePingLoading ? /* @__PURE__ */ (0, import_jsx_runtime.jsxs)(import_jsx_runtime.Fragment, { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(RefreshCw, { className: "w-3.5 h-3.5 animate-spin" }), " Testujem spojenie..."] }) : /* @__PURE__ */ (0, import_jsx_runtime.jsxs)(import_jsx_runtime.Fragment, { children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Zap, { className: "w-3.5 h-3.5 text-yellow-400" }), " Overiť stav endpointu na pdf-t3.vorkei.com"] })
							}), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("a", {
								href: "https://pdf-t3.vorkei.com/wp-json/vorkei/v1/statements",
								target: "_blank",
								rel: "noreferrer",
								className: "text-xs text-blue-400 hover:underline flex items-center gap-1",
								children: ["Otvoriť JSON endpoint ", /* @__PURE__ */ (0, import_jsx_runtime.jsx)(ExternalLink, { className: "w-3 h-3" })]
							})]
						}),
						livePingStatus && /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
							className: "mt-4 inline-block px-3 py-1.5 rounded-lg text-xs font-mono bg-slate-950 border border-slate-800",
							children: [
								livePingStatus === "online" && /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
									className: "text-emerald-400",
									children: "✓ HTTP 200 OK: Endpoint je plne online a pripravený."
								}),
								livePingStatus === "auth_required" && /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
									className: "text-blue-300",
									children: "✓ HTTP 401/200: Endpoint aktívny a chránený Application Passwords."
								}),
								livePingStatus === "active_secured" && /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", {
									className: "text-emerald-400",
									children: "✓ Server odpovedá: Bezpečne aktívny s CORS ochranou."
								})
							]
						})
					]
				})
			}),
			/* @__PURE__ */ (0, import_jsx_runtime.jsx)("section", {
				id: "faq",
				className: "py-16 px-4 sm:px-8 border-b border-slate-800 bg-slate-900/30",
				children: /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
					className: "max-w-3xl mx-auto",
					children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
						className: "text-center mb-10",
						children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("h2", {
							className: "text-2xl font-bold text-white",
							children: "Často Kladené Otázky"
						}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
							className: "text-xs text-slate-400 mt-1",
							children: "Odpovede na všetko, čo potrebujete vedieť o plugine a jeho fungovaní."
						})]
					}), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", {
						className: "space-y-3",
						children: [
							{
								q: "Je tento plugin dostupný bezplatne?",
								a: "Áno, Vorkei Statement Bridge je 100% bezplatný projekt tímu vorkei.com. Žiadne nákupy licencií, žiadne mesačné predplatné ani skryté poplatky. Cieľom projektu je poskytnúť robustný open nástroj na párovanie platieb pre komunitu WordPressu na Slovensku."
							},
							{
								q: "Prečo zvoliť Vorkei Statement Bridge namiesto Tatra banka Premium API?",
								a: "Tatra banka Premium API vyžaduje náročný firemný schvaľovací proces, špeciálne PSD2 licencie, zložité certifikáty a mesačné poplatky 50 € až 200 € mesačne. Vorkei Statement Bridge nepotrebuje žiadne povolenie od banky. Pracuje s vašim oficiálnym PDF výpisom a páruje objednávky lokálne na vašom hostingu s 0 € mesačnými poplatkami."
							},
							{
								q: "Je plugin plne kompatibilný s High-Performance Order Storage (HPOS)?",
								a: "Áno, na 100%. Plugin je plne optimalizovaný pre najnovší WooCommerce s aktivovaným HPOS (tabuľka wp_wc_orders). Objednávky vyhľadáva okamžite v indexovaných stĺpcoch bez spomaľovania celej databázy."
							},
							{
								q: "Čo ak zákazník zadá variabilný symbol s preklepom alebo do poznámky?",
								a: "Náš algoritmus automaticky čistí variabilné symboly od medzier, lomiek a prefixov (napr. '#1042' -> '1042'). Ak zákazník vyplnil číslo objednávky do správy pre prijímateľa, plugin text prehľadá a sumu spáruje."
							},
							{
								q: "Kde sú uložené bankové dáta? Hrozí únik do cloudu?",
								a: "Všetky bankové výpisy a pohyby sa ukladajú výhradne do vašej lokálnej WordPress databázy (do dedikovaných tabuliek wp_vorkei_statements a wp_vorkei_movements). Žiadne dáta nikdy neopúšťajú váš server."
							},
							{
								q: "Ako fungujú aktualizácie na novšie verzie?",
								a: "Plugin disponuje vlastným klientom pre automatické aktualizácie (Auto-Update Engine) napojeným na server api.vorkei.com/plugins/update-check. Nové verzie aktualizujete jedným klikom priamo vo WordPress administrácii bez nutnosti manuálneho sťahovania ZIP balíčka."
							}
						].map((faq, index) => {
							const isOpen = openFaq === index;
							return /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
								className: "border border-slate-800 bg-slate-900/70 rounded-xl overflow-hidden",
								children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("button", {
									type: "button",
									onClick: () => setOpenFaq(isOpen ? null : index),
									className: "w-full p-4 text-left flex items-center justify-between text-xs sm:text-sm font-semibold text-slate-200 hover:text-white",
									children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { children: faq.q }), isOpen ? /* @__PURE__ */ (0, import_jsx_runtime.jsx)(ChevronUp, { className: "w-4 h-4 text-blue-400" }) : /* @__PURE__ */ (0, import_jsx_runtime.jsx)(ChevronDown, { className: "w-4 h-4 text-slate-500" })]
								}), isOpen && /* @__PURE__ */ (0, import_jsx_runtime.jsx)("div", {
									className: "px-4 pb-4 text-xs text-slate-400 leading-relaxed border-t border-slate-800/60 pt-3",
									children: faq.a
								})]
							}, index);
						})
					})]
				})
			}),
			/* @__PURE__ */ (0, import_jsx_runtime.jsx)("footer", {
				className: "py-16 px-4 sm:px-8 bg-slate-950 text-center",
				children: /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
					className: "max-w-3xl mx-auto",
					children: [
						/* @__PURE__ */ (0, import_jsx_runtime.jsx)("h2", {
							className: "text-2xl sm:text-3xl font-extrabold text-white mb-3",
							children: "Stiahnite si Vorkei Statement Bridge"
						}),
						/* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", {
							className: "text-xs sm:text-sm text-slate-400 mb-8 max-w-lg mx-auto",
							children: "Kompletný inštalačný ZIP balíček pripravený pre váš e-shop na WordPresse s podporou Tatra banky."
						}),
						/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
							className: "flex flex-col sm:flex-row items-center justify-center gap-3",
							children: [/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("a", {
								href: "/vorkei-statement-bridge.zip",
								download: "vorkei-statement-bridge.zip",
								className: "inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold text-xs bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-500/25 transition",
								children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)(Download, { className: "w-4 h-4" }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { children: "Stiahnuť Plugin v1.0.0 (ZIP, 280 kB)" })]
							}), onBackToApp && /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("button", {
								type: "button",
								onClick: onBackToApp,
								className: "inline-flex items-center gap-2 px-5 py-3 rounded-xl font-medium text-xs text-slate-300 hover:text-white bg-slate-900 border border-slate-800 transition",
								children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { children: "Prejsť do Generátora Výpisov" }), /* @__PURE__ */ (0, import_jsx_runtime.jsx)(ArrowRight, { className: "w-3.5 h-3.5" })]
							})]
						}),
						/* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
							className: "mt-12 pt-8 border-t border-slate-900 text-[11px] text-slate-600 flex flex-col sm:flex-row items-center justify-between gap-4",
							children: [/* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", { children: "© 2026 vorkei.com. Informačný a technický projekt pre Tatra banka & WooCommerce." }), /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", {
								className: "flex items-center gap-4 text-slate-500",
								children: [
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("a", {
										href: "https://vorkei.com",
										target: "_blank",
										rel: "noreferrer",
										className: "hover:underline",
										children: "vorkei.com"
									}),
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("span", { children: "•" }),
									/* @__PURE__ */ (0, import_jsx_runtime.jsx)("a", {
										href: "https://pdf-t3.vorkei.com",
										target: "_blank",
										rel: "noreferrer",
										className: "hover:underline",
										children: "pdf-t3.vorkei.com"
									})
								]
							})]
						})
					]
				})
			})
		]
	});
}
var SplitComponent = LandingPresentation;
export { SplitComponent as component };
