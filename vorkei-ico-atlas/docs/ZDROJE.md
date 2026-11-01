# Zdroje pripojené vo verzii 2.1

| Zdroj | Údaje | Prístup a podmienky | Stav |
|---|---|---|---|
| [RPO](https://susrrpo.docs.apiary.io/) | Identita, názvy/sídla s históriou, osoby, činnosti, vklady, štatistické kódy | Verejné JSON API, IČO → interné ID; ŠÚ SR, CC BY 4.0 | Implementované, odpovede EB-EU priamo overené |
| [ORSR](https://www.orsr.sk/) | Aktuálny zápis, sídlo, osoby, vklady, činnosti, spôsob konania | Verejný informatívny výpis, presná kontrola IČO; HTML, nie univerzálne deklarované API | Implementované; presun spisu, encoding a nejednoznačnosť testované |
| [RÚZ](https://www.registeruz.sk/cruz-public/home/api) | Jednotka, všetky dostupné závierky, výkazy, šablóny, správy a prílohy | JSON API 2.5, CC0; normalizovaná šablóna 687 | Implementované; financie EB-EU z dodaného exportu, 2017–2023 |
| [FS OpenAPI](https://iz.opendata.financnasprava.sk/openapi.yaml) | DIČ, DPH, druh registrácie, dátumy, postavenie platiteľa | Vlastný kľúč, overené slugs/searchable, presné IČO, všetky stránky; podmienky konkrétneho zoznamu | Konektor implementovaný a testovaný na syntetických odpovediach; živá autorizovaná prevádzka vyžaduje kľúč |
| Konfigurované proxy | Dostupné dodatočné údaje | HTTPS; pôvod iba podľa reálneho transportu, deklarované úradné zdroje sa archivujú | Zachovaná kompatibilita; nie náhrada našej priamej úradnej kontroly |

Obnova: hodinová cache, explicitný refresh ju obíde; RÚZ pokračuje po dávkach. Aktuálny úradný zápis nie je dôkaz bezdlžnosti. DIČ sa nemení na IČ DPH pridaním prefixu SK. Chýbajúci kľúč nie je negatívny nález.

## Širší katalóg

Nasledujúca matica zostáva podkladom pre ďalšie rozšírenia. Ak pri zdroji zostal starší text o nepripojení, rozhodujúci je konkrétny stav vyššie; ostatné konektory v tejto oprave nevznikli.

| Skupina / polia | Primárny zdroj a kľúč | Prístup a podmienky | Realizácia / obmedzenia |
|---|---|---|---|
| Identita, názvy, adresy, dátumy, forma, NACE, veľkosť | [RÚZ](https://www.registeruz.sk/cruz-public/home/api), IČO → interné ID | JSON API 2.5, CC0; údaje pribúdajú počas dňa | Priamy konektor implementovaný; živá jednotka overená. Kódy sa nevydávajú za opisné názvy. |
| Registračný súd/vložka, história názvov a adries, pobočky, činnosti | [RPO](https://susrrpo.docs.apiary.io/), [ORSR](https://sluzby.orsr.sk/), IČO | RPO REST/CC BY 4.0; ORSR aktuálne rozhranie a podmienky overiť | Priame RPO a ORSR implementované; ďalšie zdroje podľa rozsahu vyššie. |
| Živnosti, prevádzkarne, začiatok/pozastavenie/koniec | [ZRSR](https://zrsr.minv.sk/), IČO | Verejný vyhľadávač; strojové rozhranie a licencia na overenie | Nie je pripojené. |
| Štatutári, funkcie, spôsob konania, spoločníci, vklady, história | ORSR/RPO, IČO a identifikátor osoby | Rozsah podľa formy a registra; ďalšie použitie overiť | Priame RPO/ORSR implementované. Rovnaké meno nepotvrdzuje identitu osôb naprieč firmami. |
| KÚV, oprávnená osoba, verifikačné dokumenty a dátum overenia | [RPVS](https://www.justice.gov.sk/sluzby/register-partnerov-verejneho-sektora/open-data/), IČO → partner ID | Oficiálne REST/OData v2; licenciu a režim aktualizácií overiť | Nie je pripojené; nepokrýva všetky firmy. |
| Závierky, správy, výkazy, šablóny, prílohy, obdobia, dátumy, všetky bunky | RÚZ, ID účtovnej jednotky → ID závierok/výkazov | JSON; prílohy mimo API cez oficiálne URL; neverejné údaje nemusia existovať v odpovedi | Implementované pokračujúce získavanie; živá závierka, výkaz a šablóna overené. |
| Čitateľné finančné riadky | RÚZ, idSablony + riadok + stĺpec | Oficiálna šablóna a jej hlavičky | Overená šablóna 687. Ostatné zostávajú raw, čísla sa neodhadujú. |
| Marža, podiel vlastného imania, zadlženosť, likvidita | Výpočet z normalized_facts v2 backendu | Vzorec a identické obdobie, mena, jednotka, konsolidácia | Šablóna 687 má pomenované fakty a tri doložené pomery. Rast/EBITDA a ostatné šablóny zostávajú ďalšou úlohou. |
| DIČ, DPH, druh/dátum registrácie, index spoľahlivosti, zverejnená daň | [FS zoznamy](https://www.financnasprava.sk/sk/elektronicke-sluzby/verejne-sluzby/zoznamy), IČO/DIČ/IČ DPH | Web a XML exporty podľa zoznamu; väčšina uvedených zoznamov CC0; vlastný publikačný rytmus | DIČ z RÚZ a oficiálny FS adaptér implementované; FS potrebuje vlastný kľúč a overené metadáta. Registrácia nie je automaticky platiteľstvo. |
| IBAN a účet správcu dane | FS bankové zoznamy, IČ DPH | Stránka FS uvádza CC BY-NC-ND; pred komerčným použitím vyriešiť podmienky | requires_access/licenčná závislosť; priamy konektor nie je implementovaný. |
| Zverejnené daňové a poistné dlhy, chýbajúce výkazy | FS, [SP](https://www.socpoist.sk/nastroje-sluzby/zoznam-dlznikov), [VšZP](https://www.vszp.sk/platitelia/platenie-poistneho/zoznam-dlznikov.html), [Dôvera](https://www.dovera.sk/overenia/dlznici/zoznam-dlznikov), [Union](https://www.union.sk/zoznam-dlznikov/), IČO | SP má hromadný súbor, 4× mesačne. Pri poisťovniach overiť strojový prístup a podmienky | Nie je pripojené; žiadne tvrdenie o bezdlžnosti. |
| Insolvencie, likvidácie, udalosti a dokumenty | [REPLIK](https://www.justice.gov.sk/sluzby/register-predinsolvencnych-likvidacnych-a-insolvencnych-konani/), IČO/ID konania; OV historické oznamy | Verejné štruktúrované údaje; konkrétny kontrakt/licenciu overiť | Nie je pripojené. Starý Register úpadcov bol nahradený 1.10.2025. |
| Exekúcie, diskvalifikácie relevantných osôb | [CRE](https://www.cre.sk/), IČO; [diskvalifikácie](https://www.justice.gov.sk/registre/registerDiskvalifikacii/), identita osoby | CRE platený registrovaný prístup, webové služby s certifikátom; diskvalifikácie ďalšie overenie | Vyžaduje osobitné prístupy; bez automatických negatívnych záverov. |
| Verejné zmluvy, sumy, termíny, dodatky a dokumenty | [CRZ](https://www.crz.gov.sk/stahovanie-udajov-z-crz/), IČO strany | Denné rozdielové XML ZIP, CC BY 4.0, limity sťahovania | Nie je pripojené. Súčet zmlúv nie je zaplatený príjem. |
| Zákazky/CPV, ZHS, referencie, zákazy | [ÚVO](https://www.uvo.gov.sk/vestnik-a-registre/sluzby-ecertis), IČO | eCertis SOAP/WSDL pre ZHS; ostatné registre vlastné rozhrania; licencie overiť | Nie je pripojené. |
| Projekty, pomoc, sumy schválené/čerpané/vrátené | [SEMP](https://www.antimon.gov.sk/centralny-register-is-semp), [ITMS21+](https://eurofondy.gov.sk/dokumenty-a-publikacie/metodicke-dokumenty/dokumenty-itms21/), IČO/ID projektu | Verejné zóny, nie všetky údaje; otvorené API/licenciu overiť | Nie je pripojené. |
| Licencie, rozsah a platnosť | [NBS PSD2](https://nbs.sk/dohlad-nad-financnym-trhom/registre/register-podla-psd2/), IČO | Zdokumentované JSON API PSD2, ďalšie registre samostatne | Nie je pripojené. |
| Sankčné záznamy/aliasy/právny predpis | [EÚ dataset](https://data.europa.eu/data/datasets/consolidated-list-of-persons-groups-and-entities-subject-to-eu-financial-sanctions?locale=en), názov + dodatočné identifikátory | XML/CSV/PDF; prevzatie a obnovu overiť | Nie je pripojené; názvová zhoda je iba kandidát. |
| Známky, patenty, dizajny, majiteľ, stav | [ÚPV](https://www.indprop.gov.sk/databazy-registre-a-triedenia/databazy-a-registre), [EUIPO](https://www.euipo.europa.eu/search-ip), overený názov/adresa majiteľa | Verejná rešerš; univerzálne IČO API neoverené | Nie je pripojené; nadväzné párovanie musí potvrdiť subjekt. |
| LEI, stav, zverejnené materské väzby | [GLEIF](https://www.gleif.org/en/lei-data/gleif-api/), registrujúca autorita + registračné číslo | Verejné API; konkrétny filter a aktualizáciu overiť | Nie je pripojené; LEI nemá každá firma. |
| Web, firemný e-mail/telefón, produkty a certifikáty | Web firmy s doloženým IČO alebo vydavateľ certifikátu | Nadväzné vyhľadávanie; osobitné podmienky zdrojov | Nie je pripojené. Žiadne odhadnuté kontakty. |
