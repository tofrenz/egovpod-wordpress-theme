# eGovPod

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

Eingestellt wird im Customizer unter **eGovPod (Podcast)**; Farben, Breiten und
Hell/Dunkel kommen aus dem Eltern-Theme unter **KERN-UX**.

## Abhängigkeiten

| | Rolle | Lizenz |
|---|---|---|
| [WordPress](https://wordpress.org) 6.4+, PHP 7.4+ | Grundlage | GPLv2+ |
| [KERN-UX WordPress Theme](https://gitlab.opencode.de/sgemlichheim/kern-ux-theme-for-wordpress) | Eltern-Theme, erforderlich | GPLv2+ |
| [KERN Design-System](https://gitlab.opencode.de/kern-ux/kern-ux-plain) | kommt mit dem Eltern-Theme | EUPL-1.2 |
| [Podlove Podcast Publisher](https://podlove.org/podlove-publisher/) | Episoden, Web Player, Feeds | MIT |
| [Podlove Subscribe Button](https://podlove.org/podlove-subscribe-button/) | Abo-Button, optional | MIT |
| [Fira Sans](https://github.com/carrois/Fira), [Noto Sans](https://fonts.google.com/noto) | Schriften, kommen mit dem Eltern-Theme | SIL OFL 1.1 |

## Lizenz

GNU General Public License v2 oder später, siehe [LICENSE](LICENSE).
Marke und Logo des eGovernment Podcast sind davon ausgenommen — tausche
`assets/images/logo.png` gegen dein eigenes Logo.

Einzelheiten zu allen Bestandteilen stehen im Abschnitt *Copyright* der
[readme.txt](readme.txt).
