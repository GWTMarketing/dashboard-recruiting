# Redaktions-Handbuch: GWT Intranet

## Ueberblick

Das Intranet besteht aus folgenden Hauptbereichen:

| Bereich | Was passiert automatisch? | Was muss manuell gemacht werden? |
|---------|--------------------------|----------------------------------|
| **Mitarbeiter** | Werden monatlich aus SAGE CSV importiert | Fotos hochladen |
| **Schwarzes Brett** | Abgelaufene Aushaenge werden deaktiviert | Neue Aushaenge erstellen |
| **News** | Artikel werden aus E-Mails erstellt | Freigabe per Klick in der E-Mail |
| **Welcome-Bereich** | Neue Mitarbeiter erscheinen automatisch | Nichts – laeuft vollautomatisch |
| **Wetter-Widget** | Aktualisiert sich selbst | Nichts – laeuft vollautomatisch |

---

## 1. Mitarbeiter verwalten

### Automatischer Import (ueber n8n)

Jeden Monat wird die aktuelle SAGE-Exportdatei automatisch verarbeitet:
- **Neue Mitarbeiter** werden angelegt (WordPress-Account + Profilseite)
- **Geaenderte Daten** werden aktualisiert (Name, E-Mail, Abteilung etc.)
- **Ausgeschiedene Mitarbeiter** werden deaktiviert

**Du musst nichts tun** – der Import laeuft automatisch.

### Mitarbeiter-Foto hochladen

1. Im WordPress-Admin auf **Mitarbeiter** klicken
2. Den gewuenschten Mitarbeiter oeffnen
3. Rechts auf **Beitragsbild festlegen** klicken
4. Foto hochladen (idealerweise quadratisch, mindestens 300x300 Pixel)
5. **Aktualisieren** klicken

**Tipp:** Wenn kein Foto hochgeladen ist, werden die Initialen des Mitarbeiters als Platzhalter angezeigt.

### Mitarbeiter manuell bearbeiten

Normalerweise nicht noetig, da alles ueber den CSV-Import laeuft. Falls doch:

1. **Mitarbeiter** → gewuenschten Eintrag oeffnen
2. Felder bearbeiten (Custom Fields im Editor)
3. **Aktualisieren** klicken

**Wichtig:** Aenderungen an Name, E-Mail, Standort oder Abteilung werden beim naechsten CSV-Import ueberschrieben!

---

## 2. Schwarzes Brett (Aushaenge)

### Neuen Aushang erstellen

1. Im WordPress-Admin auf **Schwarzes Brett** → **Neuen Aushang erstellen**
2. **Titel** eingeben (kurz und praegnant)
3. **Inhalt** schreiben (kann im visuellen Editor formatiert werden)
4. Rechts die **Aushang-Einstellungen** ausfuellen:
   - **Ablaufdatum**: Wann soll der Aushang automatisch verschwinden?
   - **Kategorie**: Allgemein, HR/Personal, Events, oder Sonstiges
   - **Prioritaet**: Normal oder Wichtig (wichtige Aushaenge werden hervorgehoben)
5. Optional: **Beitragsbild** hinzufuegen
6. **Veroeffentlichen** klicken

### Was passiert mit abgelaufenen Aushaengen?

- Abgelaufene Aushaenge werden **automatisch 2x taeglich** auf "Entwurf" gesetzt
- Sie sind dann **nicht mehr sichtbar** fuer Mitarbeiter
- Sie werden **NICHT geloescht** – du kannst sie jederzeit reaktivieren
- Der Autor bekommt eine **E-Mail-Benachrichtigung** wenn sein Aushang ablaeuft
- Der Admin bekommt jeden **Montag eine Zusammenfassung** aller abgelaufenen Aushaenge

### Aushang verlaengern

1. **Schwarzes Brett** → den abgelaufenen Aushang oeffnen
2. Neues **Ablaufdatum** setzen
3. Status von "Entwurf" auf **"Veroeffentlicht"** aendern
4. **Aktualisieren** klicken

---

## 3. News-Artikel

### Automatische Erstellung (ueber E-Mail)

1. Eine E-Mail an **news@intranet.firma.at** schreiben
   - **Betreff** = grobe Ueberschrift / Thema
   - **Inhalt** = Stichpunkte oder kurzer Text
2. Der Artikel wird **automatisch von der KI formuliert** und als Entwurf gespeichert
3. Die Redaktion bekommt eine **E-Mail mit Vorschau**
4. In der E-Mail auf **"Freigeben"** oder **"Ablehnen"** klicken
5. Freigegebene Artikel werden **sofort veroeffentlicht**

### Artikel manuell erstellen

1. Im WordPress-Admin auf **Beitraege** → **Erstellen**
2. Titel und Inhalt eingeben
3. Kategorie waehlen
4. **Veroeffentlichen** klicken

### Artikel bearbeiten

1. **Beitraege** → gewuenschten Artikel oeffnen
2. Aenderungen vornehmen
3. **Aktualisieren** klicken

---

## 4. Seiten gestalten (Bricks Builder)

### Bricks Builder oeffnen

1. Die gewuenschte Seite im Admin oeffnen
2. Oben auf **"Mit Bricks bearbeiten"** klicken
3. Die Seite oeffnet sich im visuellen Editor

### Wichtige Bricks-Elemente

- **Shortcodes** koennen ueberall eingefuegt werden:
  - `[welcome_bereich]` – Neue Mitarbeiter (Welcome on Board)
  - `[welcome_bereich anzahl="8" monate="3"]` – mit Parametern
  - `[wetter_widget]` – 3-Tages-Wettervorschau
  - `[mitarbeiter_navigation]` – Vor/Zurueck in der Abteilung

- **Query Loops** fuer dynamische Listen:
  - Mitarbeiter nach Standort/Abteilung
  - Schwarzes Brett Cards
  - News-Artikel

### Seiten-Struktur

Die wichtigsten Seiten:
- **Startseite**: Welcome, Wetter, News, Welcome on Board, Schwarzes Brett
- **/mitarbeiter/**: Alle Mitarbeiter (Archiv)
- **/standort/wien/**: Mitarbeiter am Standort Wien
- **/abteilung/marketing/**: Mitarbeiter in der Marketing-Abteilung
- **/aushang/**: Schwarzes Brett (Archiv)

---

## 5. Haeufige Fragen

### "Ein neuer Mitarbeiter taucht nicht im Welcome-Bereich auf"

- Pruefen ob der Mitarbeiter einen **Status "aktiv"** hat
- Pruefen ob das **Eintrittsdatum** innerhalb der letzten 2 Monate liegt
- Der Welcome-Bereich aktualisiert sich automatisch – ggf. den **WP Rocket Cache leeren** (Dashboard → WP Rocket → Cache leeren)

### "Das Wetter-Widget zeigt den falschen Standort"

- Das Widget versucht zuerst den **Browser-Standort** zu verwenden
- Falls der User die Standort-Abfrage abgelehnt hat, wird der **Unternehmens-Standort** aus dem Profil verwendet
- Neuer Standort? In der IT melden – muss einmalig in der Konfiguration ergaenzt werden

### "Ein Mitarbeiter hat das Unternehmen verlassen, ist aber noch sichtbar"

- Wird beim naechsten **monatlichen CSV-Import** automatisch deaktiviert
- Fuer sofortige Deaktivierung: Mitarbeiter im Admin oeffnen → Status auf **"Entwurf"** setzen

### "Ich moechte einen Aushang ohne Ablaufdatum"

- Das Ablaufdatum-Feld einfach **leer lassen**
- Der Aushang bleibt dann dauerhaft sichtbar bis er manuell entfernt wird

### "Die News-KI hat einen Artikel falsch formuliert"

- Auf **"Ablehnen"** in der Freigabe-Mail klicken
- Alternativ: Den Entwurf im WordPress-Admin manuell bearbeiten und dann veroeffentlichen

---

## 6. Kontakt bei technischen Problemen

- **IT-Support:** it@firma.at
- **WordPress-Hosting (Raidboxes):** Zugang ueber das Raidboxes-Dashboard
- **n8n-Workflows:** Zugang ueber das n8n-Dashboard auf dem Hetzner-Server

---

*Stand: April 2026 | Version 1.0*
