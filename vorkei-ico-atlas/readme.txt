=== VORKEI IČO ATLAS ===
Contributors: vorkei
Requires at least: 6.2
Requires PHP: 8.1
Stable tag: 2.1.0
License: GPLv2 or later

Preukázateľné profily firiem s priamym konektorom RÚZ a existujúcim registračným API.

== Inštalácia ==
Najprv záloha a skúšobná kópia WordPressu. Nahrať ZIP a nahradiť pôvodný plugin.
Verziovaná migrácia zachová existujúcu tabuľku. Rozšírený lookup teraz vyžaduje administrátora.

== Rozsah ==
RÚZ: jednotky, závierky, výkazy, šablóny, správy a odkazy na prílohy.
Čitateľné finančné tabuľky: overená šablóna 687; ostatné dáta zachované v pôvodnej podobe.
Priame RPO a ORSR dopĺňajú osoby a vlastníctvo. Oficiálne FS API vyžaduje vlastný kľúč a konfiguráciu zoznamov. Ostatné nepripojené skupiny zostávajú Neoverené.
Zdroj, čas, dostupnosť a konflikty pri údajoch. Ukladanie profilu, dávky, cron, JSON a CSV.

Podrobnosti: docs/IMPLEMENTACIA.md, docs/ZDROJE.md, docs/CONTRACT.schema.json.
Testy: php tests/run.php (izolovaný harness, bez živého WordPressu/MySQL).

== Changelog ==
= 2.1.0 =
* Priame RPO/ORSR, aktuálne osoby a vlastníctvo, história a jednoznačný primárny spis.
* Oficiálny FS adaptér s vlastným kľúčom, discovery metadát a bezpečnými chybovými stavmi.
* Normalizované financie 687, pôvod a história hodnôt, čitateľný finančný CSV.
* Jednotná projekcia, oprava pokračovania bez resetu a zachovanie metadát pri exporte.

= 2.0.0 =
* Verziovaný dôkazový profil, priamy RÚZ a pokračujúce získavanie podkladov.
* Zachovanie vnorených polí, dát pri výpadku, transparentné stavy a zdroje.
* Oprava potvrdenia aktivity, duplicít, stránkovania a hromadného spracovania.
* Chránený lookup, HTTPS, bezpečné vykreslenie a úplný JSON export.
