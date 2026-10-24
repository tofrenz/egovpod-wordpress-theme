# WordPress-Theme des eGovernment Podcast

WordPress-Theme für den [eGovernment Podcast](https://egovernment-podcast.com) — ein
Child-Theme von [KERN-UX](https://gitlab.opencode.de/sgemlichheim/kern-ux-theme-for-wordpress)
mit Podcast-Funktionen für den [Podlove Podcast Publisher](https://podlove.org/podlove-publisher/).

## Der Podcast

Der eGovernment Podcast begleitet seit 2014 die Digitalisierung der öffentlichen
Verwaltung in Deutschland: Verwaltungsdigitalisierung, Open Government, Open Data,
Smart City und digitale Souveränität. Inzwischen sind über 270 Folgen erschienen.
Moderation: [Torsten Frenzel](https://www.linkedin.com/in/tfrenzel).

## KERN UX

[KERN](https://www.kern-ux.de) ist ein offener UX-Standard für die deutsche Verwaltung —
ein Design-System aus Komponenten, Farben, Schriften und Gestaltungsregeln, das aus einer
bundesweiten Fachcommunity heraus entsteht. Das erklärte Ziel ist der digital zugängliche
Staat: barrierefrei, transparent und intuitiv nutzbar. KERN steht unter der EUPL-1.2.

Dieses Theme nutzt KERN über das Eltern-Theme KERN-UX, das den Kit unverändert mitbringt.
Dass ein Podcast über die Verwaltungsdigitalisierung im Design-System dieser Verwaltung
steckt, ist Absicht.

## Installation

1. **Eltern-Theme** [KERN-UX](https://gitlab.opencode.de/sgemlichheim/kern-ux-theme-for-wordpress)
   herunterladen und unter *Design → Themes → Theme hochladen* installieren.
   Es muss im Ordner `wp-content/themes/kern-ux` liegen.
2. **Dieses Theme** als ZIP herunterladen (*Code → Download ZIP*) und genauso hochladen.
3. **Aktivieren** unter *Design → Themes*.
4. **Podlove Podcast Publisher** installieren, falls noch nicht geschehen. Ohne ihn
   läuft das Theme als normales Blog-Theme.

## Konfiguration

Alles Folgende ist optional — das Theme läuft ohne eine einzige Einstellung.

### Seiten-Templates

Drei Seiten-Templates ersetzen lange Podlove-Tabellen und von Hand gepflegte Listen
durch etwas Übersichtlicheres. Zuzuweisen unter *Seiten → bearbeiten →
Seiten-Attribute → Template*. An Podlove und am gespeicherten Seiteninhalt ändert
sich dabei nichts: Zurück auf *Standard-Template* gestellt, steht die ursprüngliche
Tabelle beziehungsweise Liste unverändert wieder da.

| Template | macht daraus |
|---|---|
| **Episodenarchiv** | Karten oder Liste mit Suche, Jahresleiste, Sortierung nach Folgennummer und Blätterung |
| **Transkripte** | dasselbe, eingeschränkt auf Folgen mit Transkript, mit Download-Knöpfen (HTML, Text, PDF) je Folge |
| **Teilnehmer:innen (Karten)** | Karten mit Avatar, Anzahl der Folgen und Diensten, dazu ein mitlaufendes A–Z-Register |

Die Liste der Transkripte entsteht bei jedem Aufruf neu aus den Episoden — eine neue
Folge steht sofort drin, sobald ihr Transkript in Podlove liegt. Nichts zu pflegen.

### Customizer → eGovPod (Podcast)

**Podlove-Integration**

| Einstellung | Standard | wofür |
|---|---|---|
| Episoden-Bausteine ausgeben durch … | Automatisch | Theme-Layout oder Podlove-Templates. „Automatisch“ erkennt eine automatische Template-Zuweisung in Podlove und verhindert so doppelte Player. |
| Darstellung der Mitwirkenden | Liste | Liste, Tabelle oder kommagetrennt |
| Download-Buttons auf Episodenseiten | an | |
| Transkript anzeigen | an | falls in Podlove hinterlegt |
| Verwandte Episoden anzeigen | an | |
| Strukturierte Daten (schema.org) | an | `PodcastEpisode` für Suchmaschinen |

**Doppelte Bausteine** — für Podcasts, deren Episodentexte Zusammenfassung, Player
und Mitwirkende schon selbst enthalten. Genau das ist beim eGovernment Podcast der
Fall, deshalb sind die ersten beiden ab Werk aus.

| Einstellung | Standard | wofür |
|---|---|---|
| Zusammenfassung als Vorspann anzeigen | aus | nur einschalten, wenn der Episodentext sie nicht selbst enthält |
| Mitwirkende als eigenen Abschnitt anzeigen | aus | dito |
| Zusätzlichen Web Player aus dem Episodentext entfernen | an | lässt den Player oben den einzigen sein; der gespeicherte Text bleibt unverändert |

**Startseite & Archiv**

| Einstellung | Standard |
|---|---|
| Überschrift | leer = Podcast-Titel aus Podlove |
| Einleitungstext | leer = Podcast-Beschreibung aus Podlove |
| Anzahl weiterer Episoden auf der Startseite | 6 |
| Neueste Blogbeiträge auf der Startseite | an |
| Episoden pro Archivseite | 24 |

Farben und Breiten gehören dem Eltern-Theme und stehen im Customizer unter
**KERN-UX**. Das Child-Theme setzt dort lediglich die Akzentfarbe vor
(hell `#A64F00`, dunkel `#FF9400`) — überschreibbar wie jede andere Einstellung.

Den Hell/Dunkel-Umschalter oben rechts stellt ebenfalls das Eltern-Theme; das
Child-Theme gestaltet ihn nur um — aus Symbol plus Beschriftung wird ein Schalter
mit Sonne und Mond. Reine CSS-Sache: derselbe Knopf, dieselbe Funktion, kein
eigenes JavaScript. Ein Update des Eltern-Themes kann also höchstens die
Gestaltung zurückholen, nie die Funktion brechen.

### Filter

Für alles, wofür ein Schalter im Customizer zu viel wäre.

| Filter | Datei | Standard |
|---|---|---|
| `egovpod_contributor_bands` | `inc/contributors.php` | Banderolen auf den Teilnehmerkarten, `Name => Aufschrift` |
| `egovpod_player_only_templates` | `inc/podlove.php` | `array( 'default' )` — Podlove-Templates, die auf Episodenseiten übersprungen werden |
| `egovpod_subscribe_color` | `inc/podlove.php` | `#A64F00` — Farbe im Abo-Fenster |
| `egovpod_subscribe_language` | `inc/podlove.php` | `de` — Sprache des Abo-Fensters |
| `egovpod_transcript_formats` | `inc/transcript.php` | `html`, `txt`, `druck` — angebotene Transkript-Downloads, `Kürzel => Beschriftung` |

Eigene `add_filter()`-Aufrufe gehören **nicht** in die `functions.php` des Themes —
die wird beim nächsten Update überschrieben. Besser in ein Code-Snippets-Plugin oder
in eine eigene Datei unter `wp-content/mu-plugins/`, die WordPress automatisch lädt
und die jedes Theme-Update überlebt:

```php
<?php
// wp-content/mu-plugins/egovpod-eigenes.php

add_filter( 'egovpod_contributor_bands', function ( $banderolen ) {
	$banderolen['Sandy Jahn'] = 'Co-Host';
	return $banderolen;
} );
```

Schlüssel ist der Name, wie Podlove ihn ausgibt. Wird eine Person dort umbenannt,
fällt ihre Banderole stillschweigend weg — dann den Schlüssel nachziehen.

### Transkripte

Unter jeder Folge mit Transkript und auf der Seite mit dem Template *Transkripte*
steht eine Knopfreihe:

| Knopf | was passiert |
|---|---|
| **HTML** | lädt ein eigenständiges HTML-Dokument herunter — eine Datei, kein Theme nötig |
| **Text** | dasselbe als reiner Text, auf 80 Zeichen umgebrochen |
| **PDF** | öffnet eine Druckansicht in einem neuen Tab und startet den Druckdialog; dort *Als PDF speichern* wählen |

Die Druckansicht ist bewusst kein echtes PDF: ein PDF im Theme zu erzeugen hieße, eine
Bibliothek wie Dompdf mitzuschleppen — mehrere Megabyte und eine weitere Abhängigkeit,
die gepflegt werden muss. Browser und Betriebssystem können das längst selbst, und das
Ergebnis ist ein durchsuchbares PDF mit echten Umbrüchen.

Die Daten kommen aus dem Podlove-Modul *Transcripts* über dessen eigene Methoden.
Fehlt Podlove oder das Modul, verschwinden die Knöpfe und die Seite bleibt leer, statt
einen Fehler zu werfen.

### Außerhalb des Themes

Ob der Web Player zu- oder aufgeklappt startet, entscheidet Podlove, nicht das Theme:
*Podlove → Web Player → Configuration → Active Tab*. `none` startet zugeklappt.

## Abhängigkeiten

| | Rolle | Lizenz |
|---|---|---|
| [WordPress](https://wordpress.org) 6.4+, PHP 7.4+ | Grundlage | GPLv2+ |
| [KERN-UX WordPress Theme](https://gitlab.opencode.de/sgemlichheim/kern-ux-theme-for-wordpress) | Eltern-Theme, erforderlich | GPLv2+ |
| [KERN Design-System](https://gitlab.opencode.de/kern-ux/kern-ux-plain) | kommt mit dem Eltern-Theme | EUPL-1.2 |
| [Podlove Podcast Publisher](https://podlove.org/podlove-publisher/) | Episoden, Web Player, Feeds | MIT |
| [Podlove Subscribe Button](https://podlove.org/podlove-subscribe-button/) | Abo-Button, optional | MIT |
| [Fira Sans](https://github.com/carrois/Fira), [Noto Sans](https://fonts.google.com/noto) | Schriften, kommen mit dem Eltern-Theme | SIL OFL 1.1 |

Maschinenlesbar als Software-Stückliste in zwei Formaten:
[`bom.json`](bom.json) (CycloneDX 1.6) und [`egovpod.spdx.json`](egovpod.spdx.json)
(SPDX 2.3). Beide listen das Theme, seine Abhängigkeiten und jede Datei mit Prüfsumme.

## Lizenz

Doppellizenz — wähle eine der beiden:

* **GNU General Public License v2 oder später**, siehe [LICENSE](LICENSE)
* **European Union Public Licence 1.2**, siehe [LICENSE.EUPL-1.2.txt](LICENSE.EUPL-1.2.txt)

`SPDX-License-Identifier: GPL-2.0-or-later OR EUPL-1.2`

Die EUPL ist die Lizenz der öffentlichen Verwaltung in Europa und auch die Lizenz des
KERN-Design-Systems; die GPL ist die Lizenz von WordPress. Wer das Theme in einer
WordPress-Installation betreibt, kombiniert es mit GPL-Code — für diese Kombination
gelten die Bedingungen der GPL.

Marke und Logo des eGovernment Podcast sind von beiden Lizenzen ausgenommen — tausche
`assets/images/logo.png` gegen dein eigenes Logo.

Einzelheiten zu allen Bestandteilen stehen im Abschnitt *Copyright* der
[readme.txt](readme.txt).
