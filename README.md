# Fokus-Log – WordPress-Plugin

Ein einfacher Tagesplaner gegen Aufschieben: Du schreibst den Plan als Textliste, jede Zeile wird zu einem antippbaren Element mit Timer. Zwischen den To-dos gibt es Pausen mit festem Ende, ein Coach motiviert dich, und der Verlauf zeigt, wie du über die Wochen mehr schaffst.

## Funktionen

- **Tagesplan als Text**: eine Zeile pro To-do, beginnend mit `- `
- **Antippen** startet bzw. pausiert den Timer. Startest du ein anderes To-do, pausiert das laufende automatisch.
- **Haken** (erscheint beim Drüberfahren, auf dem Handy immer sichtbar): hakt ein To-do ab, auch ohne Timer. Ein Klick auf den Haken eines erledigten To-dos öffnet es wieder.
- **Pause zwischen To-dos** mit Countdown, Ton am Ende und Knopf „Weiter starten“. Die Länge stellst du in der App ein.
- **Coach**: Unter dem laufenden To-do steht bei jedem Start ein neuer Spruch. Beim Abhaken kommt eine Party-Einblendung mit Konfetti.
- **Speicherformat** wie eine Textdatei, automatisch gespeichert (zum Kopieren oder als `.txt` herunterladen):
  ```
  - Spazieren gehen {08:30|09:05|00:35}
  - Bett machen {09:10|09:16|00:06}
  - Briefe öffnen {09:20||00:04}
  - Staubsaugen
  ```
  `{start|ende|dauer}`: Die Dauer ist die reine Timerzeit (hh:mm), ohne Pausen. Ist die Endzeit leer, wurde das To-do angefangen, aber noch nicht abgeschlossen.
- **Offene von gestern holen**: übernimmt alles, was gestern offen geblieben ist, in den neuen Plan.
- **Verlauf**: erledigte To-dos und Fokuszeit der letzten 14 Tage, mit Vergleich zur Vorwoche.
- Der Tab-Titel zeigt den laufenden Timer bzw. die Pause, so siehst du ihn auch in einem anderen Tab.
- Jeder angemeldete Benutzer sieht nur seine eigenen Daten.

## Installation

**Variante A – ZIP (am einfachsten)**
1. `fokus-log-1.0.0.zip` herunterladen.
2. WordPress-Backend → *Plugins → Installieren → Plugin hochladen* → ZIP auswählen → *Jetzt installieren* → *Aktivieren*.

**Variante B – aus dem Repo**
```bash
cd wp-content/plugins
git clone <dein-repo-url> fokus-log
```
Dann im Backend unter *Plugins* aktivieren.

Beim Aktivieren wird die Tabelle `wp_fokus_log_days` angelegt.

## Benutzung

- **Backend**: Menüpunkt **Fokus-Log** (Uhr-Symbol).
- **Frontend**: eine (am besten private) Seite anlegen und den Shortcode einfügen:
  ```
  [fokus_log]
  ```
  Wer nicht angemeldet ist, sieht nur einen Login-Link.
- **Sprüche anpassen**: *Einstellungen → Fokus-Log*. Ein Spruch pro Zeile. Platzhalter: `{t}` = To-do, `{d}` = Dauer, `{n}` = erledigt, `{total}` = gesamt.
- Tastenkürzel im Plan-Editor: **Strg/Cmd + Enter** = Plan übernehmen.

## Repo als Git-Repository veröffentlichen

```bash
cd fokus-log
git init
git add .
git commit -m "Fokus-Log 1.0.0"
git branch -M main
git remote add origin git@github.com:<dein-name>/fokus-log.git
git push -u origin main
```

Neue ZIP-Datei bauen (lässt README, bin/ und .git weg):
```bash
bash bin/build-zip.sh
```

## Aufbau

```
fokus-log/
├── fokus-log.php                      Plugin-Header, lädt alles
├── uninstall.php                      Beim Löschen: Tabelle + Optionen entfernen
├── includes/
│   ├── class-fokus-log-store.php      Tabelle, Text-Parser, Speichern, Verlauf
│   ├── class-fokus-log-rest.php       REST-API /wp-json/fokus-log/v1/…
│   ├── class-fokus-log-settings.php   Einstellungen → Fokus-Log (Pause, Sprüche)
│   └── class-fokus-log-app.php        Menüpunkt + Shortcode, lädt die App
├── assets/
│   ├── app.js                         Die App (reines JavaScript, kein Build)
│   └── app.css                        Styles (Systemschriften, keine externen Fonts)
├── bin/build-zip.sh                   Baut die installierbare ZIP-Datei
├── readme.txt                         WordPress-Readme
└── README.md
```

### REST-Endpunkte (nur angemeldet, Nonce `wp_rest`)

| Methode | Pfad | Inhalt |
|---|---|---|
| GET | `/day?date=YYYY-MM-DD` | Text des Tages |
| POST | `/day` `{date, content}` | Speichert den Text und berechnet daraus erledigte To-dos und Fokuszeit |
| GET | `/history?today=YYYY-MM-DD&days=14` | Ein Eintrag pro Tag (fehlende Tage = 0) |
| POST | `/settings` `{pause}` | Pause in Minuten (pro Benutzer) |

### Wie gespeichert wird

- Bei jeder Aktion (Start, Pause, Haken, Plan übernehmen), während ein Timer läuft jede Minute, und beim Schließen des Tabs.
- Zusätzlich gibt es eine lokale Kopie im Browser (`localStorage`). Ist der Server kurz weg, geht nichts verloren, und ein laufender Timer überlebt ein Neuladen der Seite.
- Das Datum ist das lokale Datum des Browsers. Lässt du die Seite über Mitternacht offen, lade sie am neuen Tag neu.

## Anforderungen

WordPress 6.0+, PHP 7.4+. Getestet wurde die Logik, nicht jede Theme-Kombination. Falls ein Theme die Knöpfe überschreibt, in `assets/app.css` nachschärfen.

## Lizenz

GPL-2.0-or-later
