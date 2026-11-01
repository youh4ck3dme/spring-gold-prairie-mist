# VORKEI IČO ATLAS 2.1

Aktualizácia rozširuje pôvodný plugin o priame RPO a ORSR, normalizované financie zo šablóny RÚZ 687 a pripravený konektor oficiálneho API Finančnej správy. Zachováva Vorkei Bridge, JetEngine mapovanie a uložené profily 2.0. Nepripojené registre sa naďalej označujú ako neoverené.

## Inštalácia a konfigurácia

1. Zálohujte WordPress databázu a pôvodný plugin. Aktualizačný ZIP najprv nahrajte na skúšobnú kópiu webu a nahraďte existujúci plugin rovnakého názvu.
2. Migrácia 2.1 je verziovaná a aditívna. Existujúci `profile_json` sa pri čítaní prevádza na nový dôkazový profil bez vymazania pôvodných pozorovaní. Fyzické stĺpce existujúcej tabuľky zostávajú kompatibilné.
3. RPO a ORSR sú pripojené priamo. Priame RÚZ sa zapína existujúcim nastavením. Voliteľné staré registračné API vyžaduje HTTPS adresu končiacu parametrom `ico=`.
4. Pre FS vložte vlastný API kľúč do poľa „API Finančnej správy“ a uložte nastavenia. Potom kliknite na „Overiť kľúč a načítať zoznamy“. Z overených metadát nastavte identifikátory zoznamov registrácie na daň z príjmov a registrácie DPH a vyhľadávací stĺpec IČO. Slug ani podporované stĺpce sa neodhadujú.
5. Bez kľúča, vhodného zoznamu alebo vyhľadávateľného stĺpca plugin uvedie konkrétne obmedzenie. Nevyvodí daňový status a nebude sťahovať celé verejné ZIP databázy. API kľúč zostáva na serveri, nevracia sa do REST odpovedí ani exportov. Prázdne heslové pole zachová uložený kľúč; jeho odstránenie má samostatnú voľbu.

## Dátový model a rozhodovanie

REST namespace zostáva `vorkei-ico/v1`, dátová schéma je `2.1`. `fields` obsahuje vybranú hodnotu a `observations` všetky získané verzie a pôvody. `groups` uchováva opakované záznamy, `checks` priebeh a dostupnosť zdrojov, `conflicts` rozdiely medzi podkladmi. Všetky endpointy vyžadujú `manage_options`; cookie autentifikácia potrebuje REST nonce.

Pri aktuálnych právnych údajoch má prednosť jednoznačný aktuálny ORSR, potom RPO. Účtovné údaje pochádzajú z RÚZ; daňové polia uprednostňujú FS. Prednosť nie je dôvodom na zmazanie alternatívneho pozorovania. Ukončená účinnosť alebo nedostupná kontrola znižuje aktuálnosť údaja. Rovnaká hodnota z dvoch zdrojov zachová oba pôvody. Rozdielne formátovanie názvu či adresy sa neoznačuje automaticky za vecný rozpor.

Nadpis, zoznam, detail a export používajú rovnakú projekciu. Stav `verified_registered` znamená overený zápis bez zobrazeného výmazu, nie hospodársku aktivitu alebo bezdlžnosť. Samotné `active=true` z proxy ani prázdne `statutaries=[]` takýto záver nevyvolajú. Staré polia active/status z proxy sú výslovne nepotvrdené tvrdenia (`candidate`, `unconfirmed`), nie úradné overenie. Deklarovaný pôvod z externého v2 API zostáva v `upstream_evidence`; nenahradí našu priamu kontrolu úradného zdroja.

Časy sú oddelené: `retrieved_at` je skutočné získanie odpovede, `source_updated_at` aktualizácia zdroja, `valid_from`/`valid_to` právna účinnosť a `period` účtovné obdobie. Staré RÚZ `valid_as_of` sa presúva do `source_updated_at`. Čítanie cache neposúva čas získania. Nové časy sú UTC.

## Získavanie, cache a chyby

`GET /lookup?ico=XXXXXXXX&refresh=true` začne nové získavanie. Odpoveď `pending=true` vyžaduje pokračovanie `continue=true&refresh=false`. Prvá fáza načíta RPO/ORSR (rozpočet 20 s), druhá proxy/FS (FS do 18 s), ďalšie načítavajú najviac tri RÚZ úlohy. Obrazovka aj dávkový proces pokračujú automaticky.

Bežné načítanie používa hodinovú cache. Explicitné obnovenie obíde registre a FS cache a pri prvom RÚZ kroku zruší predchádzajúci job aj jeho známe cache položky. Chyba HTTP, HTML namiesto JSON, nedokončené stránkovanie či aktualizujúci sa zoznam neznačia nenájdenú firmu. FS odpoveď 429 pozastaví ďalšie sieťové pokusy rovnakého kľúča na minútu.

ORSR spracúva Windows-1250 a presne overuje IČO. Presunutý spis nie je aktuálnym primárnym spisom. Viac než jeden aktuálny spis znamená konflikt, nepreskúmané ďalšie výsledky pri limite troch znamenajú čiastočný výsledok. V takom stave sa neudeľuje potvrdenie zo starého ORSR pozorovania.

## Financie a exporty

Šablóna 687, MF/18008/2014, má 23/22/38 riadkov a dva dátové stĺpce. Overuje sa jej identita, poradie a dĺžky dát. Hodnoty sa priraďujú cez pozíciu riadku v šablóne, nie všeobecne podľa čísla riadku. Jednotky sú celé eurá doložené pôvodným formulárom.

Výkazy uchovávajú pôvodný `value` a pridávajú `decoded_tables`, `normalized_facts`, `comparative_facts`, `report_identity`, `financial_issues`. Prázdna bunka zostáva null; reťazec „0“ je vykázaná nula. Porovnávacie obdobie sa použije len vtedy, keď ho titulná strana výslovne obsahuje. Pôvodné a neskoršie porovnávacie hodnoty sa navzájom neprepisujú. Staršie verzie rovnakého výkazu zostávajú označené ako nahradené.

Tri pomery majú presný vzorec a zdrojové vstupy: zisk po zdanení / hospodárske výnosy, vlastné imanie / aktíva, záväzky / aktíva. Počítajú sa len pri úplných zhodných obdobiach, jednotkách a konsolidácii a nenulovom menovateli. Iné účtovné šablóny sa zachovajú surové; nie sú automaticky normalizované.

- `format=json`: celý profil s pôvodom, históriou, stavmi a rozdielmi.
- `format=csv`: pôvodných deväť stĺpcov a doplnené `kind`, `match_method`, `source_updated_at`, `valid_from`, `valid_to`, `record_json`. Úplná obálka zahŕňa aj dekódované tabuľky a finančné fakty; samostatné riadky obsahujú stavy skupín, kontroly, rozdiely a súhrn.
- `format=financial_csv`: čitateľná tabuľka vybraných výkazov po obdobiach. Ak je viac verzií rovnakého obdobia, použije najnovší čas zdrojovej úpravy/získania; kompletná história ostáva v JSON. Nahradené verzie sa nesmú vydať za nové obdobie. Prázdno a nula ostávajú odlíšené.

Voliteľné `ico=XXXXXXXX` obmedzí export na jednu firmu. CSV používa UTF-8 BOM a bodkočiarku. Pri importe do Excelu označte identifikátory ako text; CSV samo neuchováva excelové typy buniek. Celé finančné čísla sa nezapisujú ako vzorce. Veľké JSON exporty zostávajú v pamäti; pri veľkých databázach treba samostatné výkonové overenie.

## Integrácie a skúšky

Vorkei Bridge hook a hodinový WP-Cron zostávajú zachované. WP-Cron závisí od návštevnosti alebo systémového spúšťania. Dávka spracúva jedinečné IČO a rozpracované získavanie pokračuje bez resetovania. JetEngine synchronizácia vypĺňa len prázdne namapované skalárne hodnoty. Definície CCT nie sú automaticky vytvorené v editore JetEngine; nastavte ich na konkrétnom webe. Filter `vorkei_ico_cct_field_map` zostáva kompatibilný.

Samostatné regresné testy:

```text
php tests/run.php
php tests/financials-test.php
php tests/fixtures/registers-check.php
php tests/fixtures/tax-regression.php
```

Testy používajú izolované WordPress/databázové dvojníky a zachytené úradné odpovede. Produkčný WordPress, skutočný MySQL/dbDelta, WP-Cron a konkrétna konfigurácia JetEngine neboli dostupné na reálnu integračnú skúšku. Balík preto najprv overte na skúšobnom webe. Živá autorizovaná FS kontrola sa dokončí po dodaní vlastného API kľúča a overení jeho katalógu.

Dlhy, insolvencie a ostatné pôvodne plánované zdroje nie sú súčasťou tejto opravy a zostávajú neoverené. Retencia historických údajov ani úplná relačná databáza dokumentov sa nemenia.
