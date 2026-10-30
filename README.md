\# Pokémon TCG Shop – Inventarverwaltung



Webanwendung zur Verwaltung eines Pokémon-Karten-Inventars: Karten erfassen,

Bestand nach Zustand und Sprache führen, Verkäufe buchen und Gewinne auswerten.



\## Funktionen

\- Kartensuche über die TCGdex-API mit Live-Vorschlägen

\- Bestand pro Variante (Zustand, Sprache) mit Einkaufspreis und Marge

\- Verkäufe mit Plattform-Zuordnung, Transaktion und Zeilensperre gegen Überverkauf

\- Statistiken: Umsatz, Gewinn pro Karte und Plattform, Verläufe als Diagramme



\## Tech-Stack

PHP 8.3, MySQL 8, PDO, JavaScript (Fetch API, Chart.js), Docker



\## Starten

1\. `.env.example` nach `.env` kopieren und Passwörter setzen

2\. `docker compose up -d`

3\. http://localhost:8080 öffnen



Das Datenbankschema wird beim ersten Start automatisch aus `schema.sql` angelegt.



\## Bekannte Einschränkungen / nächste Schritte

\- Kein Login und kein CSRF-Schutz (aktuell nur für lokale Nutzung gedacht)

\- Einkäufe werden noch nicht als eigene Tabelle erfasst

\- Keine automatisierten Tests

