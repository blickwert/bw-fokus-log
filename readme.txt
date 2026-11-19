=== Fokus-Log ===
Contributors: david
Tags: todo, timer, productivity, focus, tracker
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Tagesplan als Textliste, Timer pro To-do, feste Pausen, motivierender Coach und Leistungsverlauf.

== Description ==

Schreibe deinen Tagesplan als Liste („- To-do“). Jede Zeile wird zu einem Element: antippen = Timer starten/pausieren, Haken = erledigt.
Gespeichert wird als Text im Format „- To-do {start|ende|dauer}“, die Dauer als hh:mm:ss.

* Pause zwischen To-dos mit Countdown
* Coach-Sprüche während der Arbeit, Party-Einblendung beim Abhaken
* Verlauf der letzten 14 Tage
* Backend-Menü „Fokus-Log“ und Shortcode [fokus_log]

== Installation ==

1. ZIP unter Plugins → Installieren → Plugin hochladen installieren und aktivieren.
2. Menüpunkt „Fokus-Log“ öffnen oder den Shortcode [fokus_log] auf einer Seite einfügen.
3. Sprüche und Standard-Pause unter Einstellungen → Fokus-Log anpassen.

== Changelog ==

= 1.0.1 =
* Kein Datenverlust mehr zwischen Geräten: Revision je Speicherung, die lokale Kopie überschreibt keinen neueren Stand vom Server.
* Speicherungen laufen der Reihe nach, ein älterer Stand überholt keinen neueren mehr.
* Ein neuer Tag wird in einem Datenbankbefehl angelegt, gleichzeitige Speicherungen gehen nicht mehr verloren, Fehler der Datenbank werden gemeldet.
* Der Text bleibt, wie er geschrieben wurde („<“, „&“, Anführungszeichen, „%AB“, doppelte Leerzeichen).
* Dauer mit Sekunden (hh:mm:ss), beim Neuladen geht keine Zeit mehr verloren; Dauern auf 0 bis 24 Stunden begrenzt.
* Neuer Versuch alle 30 Sekunden, wenn der Server nicht erreichbar war; abgelaufene Anmeldung wird selbst erneuert.
* Wechsel auf den neuen Tag um Mitternacht, sobald kein Timer läuft.
* Keine Pause, wenn beim Abhaken noch ein anderes To-do läuft; „Weiter starten“ nimmt das nächste offene To-do.
* Der Shortcode erscheint nur einmal je Seite.

= 1.0.0 =
* Erste Version.
