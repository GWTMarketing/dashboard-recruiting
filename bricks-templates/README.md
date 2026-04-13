# GWT Bricks Templates – Import-Anleitung

Fertige Bricks-Builder-Templates im GWT Design (Mulish, #004071 / #005e9e / #c7eafb).

## Was du hier findest

| Datei | Was es ist | Template-Typ |
|-------|------------|--------------|
| `01-header.json` | Dunkle Sidebar links mit Menü + Profil-Block unten | Header |
| `02-footer.json` | Schmale Fußzeile in GWT-Blau | Footer |
| `03-mitarbeiter-single.json` | Mitarbeiter-Detailseite (Foto, Name, Position, Kontakt, Vor/Zurück) | Single |
| `04-schwarzes-brett-archive.json` | Aushang-Übersicht als Card-Grid | Archive |
| `05-startseite.json` | Komplette Startseite: Wetter + News + Welcome + Schwarzes Brett | Section |

## Voraussetzungen

- Bricks Theme + Child-Theme sind installiert und aktiv
- Das GWT Child-Theme (aus `interne-plattform-neu.zip`) ist aktiv
- Die Custom Post Types `employee` und `bulletin_board` sind sichtbar (WP Admin → Mitarbeiter / Schwarzes Brett)
- Shortcodes `[wetter_widget]`, `[welcome_bereich]`, `[mitarbeiter_navigation]` funktionieren (werden vom Child-Theme registriert)

## Import-Schritte (für jede der 5 Dateien)

1. Datei auf deinen Computer herunterladen (Rechtsklick → "Speichern unter")
2. WordPress Admin öffnen
3. **Bricks → Templates**
4. Oben auf **"Import"** klicken
5. JSON-Datei auswählen → **"Import"**
6. Template erscheint in der Liste – Titel prüfen

## Nach dem Import: Conditions setzen

Bricks Import übernimmt die Template-Bedingungen meist nicht automatisch. Setze sie manuell:

### Header (`01-header.json`)
- Template öffnen → **"Edit with Bricks"**
- Zahnrad ⚙️ (Toolbar) → **Settings** → Tab **"Template"** → **Conditions**
- **"+ Add Condition"** → **Entire website**
- Save

### Footer (`02-footer.json`)
- Gleich wie Header, aber im Footer-Template
- Condition: **Entire website**

### Mitarbeiter-Einzelseite (`03-mitarbeiter-single.json`)
- Template öffnen → Settings → Template → Conditions
- **"+ Add Condition"** → **Single** → **Post Type: Mitarbeiter**
- Save

### Schwarzes Brett Archiv (`04-schwarzes-brett-archive.json`)
- Template öffnen → Settings → Template → Conditions
- **"+ Add Condition"** → **Archive** → **Post Type Archive: Aushang**
- Save

### Startseite (`05-startseite.json`)
Diese ist als **Section-Template** gebaut, weil du sie flexibel auf die bestehende Startseite setzen willst.

**Variante A (empfohlen):**
- WP Admin → **Seiten** → "Startseite" (oder eine neue erstellen)
- **Einstellungen → Lesen** → "Eine statische Seite" → Startseite auswählen
- Die Seite öffnen → **"Edit with Bricks"**
- Links im Panel **"+"** → Suche "Template" → Element **"Template"** einfügen
- Rechts: dropdown **"GWT Startseite"** wählen

**Variante B:**
- Template-Typ nach Import auf "Single" umstellen und Condition **Front Page** setzen

## Shortcode-Alternative (falls Import hakt)

Jede Seite funktioniert auch ohne Bricks-Templates, wenn du in normale WP-Seiten diese Shortcodes einfügst:

```
[welcome_bereich anzahl="6" monate="2"]
[wetter_widget]
[mitarbeiter_navigation]
```

## Falls der Import fehlschlägt

Bricks-Versionen < 1.9 haben ein leicht anderes JSON-Format. Falls der Import mit Fehler abbricht:

1. Update Bricks auf ≥ 1.10 (WP Admin → Design → Bricks Lizenz)
2. Alternativ: Template manuell nachbauen und den **Inhalt** der Datei per Copy-Paste über **Strukturansicht → Paste** einfügen
3. Oder: die Shortcodes nutzen (siehe oben) – das funktioniert ohne Templates

## Nach dem Import testen

1. Frontend öffnen als eingeloggter User → Sidebar sollte links sein
2. Mitarbeiter-Einzelseite aufrufen (`/mitarbeiter/max-mustermann/`) → Karte mit Foto
3. Startseite → Welcome-Bereich, News, Schwarzes Brett sichtbar
4. `/aushang/` aufrufen → Card-Grid mit Aushängen

## Farben/Design anpassen

Alle Farben sind im Child-Theme als CSS-Variablen definiert (`style.css`):

```css
--gwt-primary: #004071;
--gwt-secondary: #005e9e;
--gwt-accent: #c7eafb;
```

Willst du Farben ändern → diese 3 Werte in der `style.css` anpassen, fertig.

---

Bei Problemen: Screenshot an Claude schicken, dann bauen wir das Template passend um.
