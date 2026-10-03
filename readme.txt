=== eGovPod ===
Contributors: tfrenzel
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 7.4
License: GPLv2 or later OR EUPL-1.2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Child-Theme von KERN-UX für den eGovernment Podcast mit dem Podlove Podcast Publisher.

== Beschreibung ==

Eltern-Theme: KERN-UX der Samtgemeinde Emlichheim
https://gitlab.opencode.de/sgemlichheim/kern-ux-theme-for-wordpress

Vom Eltern-Theme kommen: KERN-Kit (CSS, Fira Sans), Kopf, Fuß, Navigation,
Brotkrume, Hell/Dunkel-Umschalter, Blog-/Seiten-/Such-Templates, Block-Brücke,
Akzentfarben-System. Updates des Eltern-Themes wirken direkt.

Dieses Child-Theme ergänzt:

* Episodenseite (single-podcast.php) mit Podlove Web Player, Shownotes,
  Mitwirkenden, Transkript (Akkordeon), Downloads, Abo-Button, Vor/Zurück
* Episodenarchiv mit Episodensuche (archive-podcast.php bzw. Seiten-Template
  „Episodenarchiv“)
* Podcast-Startseite (front-page.php): Vorstellung, neueste Folge mit Player,
  weitere Episoden, Blogbeiträge
* Markenfarbe Orange: setzt den Akzent des Eltern-Themes voreingestellt auf
  „Wunschfarbe, exakt“ – hell #A64F00 (5,6:1), dunkel #FF9400 (9,5:1).
  Im Customizer unter KERN-UX jederzeit änderbar.
* Logo des eGovernment Podcast: wird bei Aktivierung einmalig in die
  Mediathek übernommen und als Logo gesetzt, falls noch keins gesetzt ist.
* Strukturierte Daten (schema.org/PodcastEpisode)

== Installation ==

1. Eltern-Theme KERN-UX nach wp-content/themes/kern-ux installieren
   (nicht aktivieren nötig).
2. Dieses Theme (Ordner egovpod) hochladen und aktivieren.
3. Podlove → Einstellungen → Website: „Episodenarchiv“ aktivieren
   (oder Seite mit Template „Episodenarchiv“ anlegen).
4. Customizer → „eGovPod (Podcast)“: Player-Modus, Abo-Button, Startseite.
   „Automatisch“ verhindert doppelte Player, wenn in Podlove unter
   Templates eine automatische Template-Zuweisung aktiv ist.

== Hinweise ==

* Die KERN-Kopfzeile („Offizielle Website …“) des Eltern-Themes ist
  standardmäßig aus und sollte für den Podcast aus bleiben.
* Der Abo-Button steht auf der Startseite und in der Episoden-Seitenleiste;
  der Kopf gehört dem Eltern-Theme und wird nicht überschrieben.

== Copyright ==

eGovPod, Copyright 2026 Torsten Frenzel

eGovPod steht wahlweise unter der GNU GPL v2 (oder später) oder unter der
European Union Public Licence 1.2. Wer das Theme nutzt, weitergibt oder
verändert, wählt eine der beiden Lizenzen und hält sich an deren Bedingungen.
SPDX-License-Identifier: GPL-2.0-or-later OR EUPL-1.2

Zum Hintergrund: Die EUPL ist die Lizenz der öffentlichen Verwaltung in Europa
und auch die Lizenz des KERN-Design-Systems. Die GPL ist die Lizenz von
WordPress. Wer das Theme in einer WordPress-Installation betreibt, kombiniert
es mit GPL-Code; für diese Kombination gelten die Bedingungen der GPL.

Dieses Child-Theme enthält ausschließlich eigenen Code. Es bündelt keine
fremden Bibliotheken, keine Schriften und keine Teile des KERN-Design-Systems;
es verweist nur auf deren Klassen und Token.

Die folgenden Bestandteile kommen aus dem Eltern-Theme und behalten dort ihre
eigenen Lizenzen:

* KERN-UX WordPress Theme, Copyright 2026 Samtgemeinde Emlichheim, Michi91,
  Lizenz: GPL v2 oder später,
  https://gitlab.opencode.de/sgemlichheim/kern-ux-theme-for-wordpress
* KERN Design System (@kern-ux/native), unverändert unter
  assets/vendor/kern des Eltern-Themes, Copyright KERN-Projekt
  (Hamburg / Schleswig-Holstein), Lizenz: EUPL-1.2,
  https://gitlab.opencode.de/kern-ux/kern-ux-plain
* Fira Sans, Copyright Carrois Type Design / Mozilla, Lizenz: SIL OFL 1.1
* Noto Sans, Copyright Google, Lizenz: SIL OFL 1.1

Die EUPL-1.2 nennt in ihrem Anhang die GPL v2 und v3 als kompatible Lizenzen
(Artikel 5 der EUPL). Das KERN-Kit wird unverändert und mitsamt seinem
Lizenztext weitergegeben.

Nicht von der Theme-Lizenz erfasst sind die Marke und das Logo des
eGovernment Podcast (assets/images/logo.png sowie der Schriftzug im
screenshot.png). Alle Rechte daran bleiben bei Torsten Frenzel; eine
Weiterverwendung des Themes schließt keine Rechte an der Marke ein.

Bundesadler und Bildwortmarke des Bundes werden von diesem Theme bewusst
nicht verwendet. Sie sind Hoheitszeichen, stehen nicht unter der EUPL und
sind Stellen des Bundes, der Länder und der Kommunen vorbehalten.

== Changelog ==

= 2.4.0 =
* Doppellizenz: Das Theme steht nun wahlweise unter der GNU GPL v2 (oder
  später) oder unter der European Union Public Licence 1.2. Der vollständige
  EUPL-Text liegt als LICENSE.EUPL-1.2.txt bei.

= 2.3.3 =
* LICENSE mit dem vollständigen Text der GNU GPL v2 ergänzt.
* readme.txt: Abschnitt "Copyright" mit allen Bestandteilen und ihren Lizenzen.
  Marke und Logo des Podcast sind ausdrücklich von der Theme-Lizenz ausgenommen.

= 2.3.2 =
* Kartentitel: Der Link darin hat die Überschrift auf Fließtextgröße
  heruntergezogen – der Folgentitel war kleiner als die Folgennummer.

= 2.3.1 =
* „Neueste Folge“ auf der Startseite zeigt ebenfalls das Beitragsbild als
  Banner statt der quadratischen Cover-Art.

= 2.3.0 =
* Im Kopfbereich der Episodenseite steht jetzt das Beitragsbild der Folge
  (Banner 3:1) statt der quadratischen Podlove-Cover-Art. Folgen ohne
  Beitragsbild behalten das Cover neben dem Titel.

= 2.2.3 =
* Folgenbeschreibung (Zitat im Episodentext) auf Fließtextgröße statt 2rem.
* Absätze, die komplett fett gesetzt sind, laufen in normaler Stärke; Fett
  innerhalb eines Satzes bleibt fett.

= 2.2.2 =
* Oranger Rahmen um die Coverbilder auf Startseite und Episodenseite entfernt.

= 2.2.1 =
* Der zweite Web Player kam über [podlove-template id="default"] aus dem
  Episodentext – dieses Template besteht aus nichts als dem Player und wird
  auf Episodenseiten nun übersprungen. Betrifft alle Folgen ab eGov002.

= 2.2.0 =
* Keine doppelten Bausteine mehr auf der Episodenseite. Die Episodentexte des
  eGovernment Podcast enthalten Zusammenfassung, Web Player und einen eigenen
  Block mit den Beteiligten bereits selbst; das Theme hat sie bis 2.1.2 ein
  zweites Mal ausgegeben.
* Zusammenfassung als Vorspann und Mitwirkende als eigener Abschnitt sind nun
  standardmäßig aus, im Customizer unter "eGovPod → Doppelte Bausteine"
  einzeln wieder einschaltbar.
* Ein zusätzlicher Web Player im Episodentext wird bei der Ausgabe entfernt,
  damit der Player oben neben Cover und Titel der einzige bleibt. Der
  gespeicherte Episodentext bleibt unverändert.

= 2.1.2 =
* Abo-Fenster des Podlove-Subscribe-Widgets in Markenorange und auf Deutsch.

= 2.1.1 =
* Dunkelmodus: Mitwirkenden-Karten von Podlove und das Veranstaltungs-Widget
  waren unlesbar (helle Schrift auf weißer Fläche). Beides läuft jetzt über
  die KERN-Token.

= 2.1.0 =
* Cover auf schmalen Bildschirmen kleiner (Startseite 200px, Episode 150px), damit die neueste Folge früher sichtbar ist.
* Feinschliff Episodenseite: Lead mit Markenlinie, ruhigere Abschnittsabstände, dichtere Shownotes, abgesetztes Transkript, klarere Trennung vor Vor/Zurück.

= 2.0.1 =
* Abo-Button als KERN-Button (Podlove hide + buttonid): gleiche Höhe und Linie wie die übrigen Buttons.

= 2.0.0 =
* Umbau zum Child-Theme von KERN-UX. Eigenes KERN-Kit, Kopf, Fuß, Blog-Templates
  und Hell/Dunkel-Logik entfernt – kommen jetzt aus dem Eltern-Theme.

= 1.1.0 =
* Markenfarbe Orange, eGovPod-Logo.

= 1.0.0 =
* Erste Version (eigenständiges Theme).
