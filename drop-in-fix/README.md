# GWT Auto-Render Fix – ohne Bricks-Templates

Die Bricks-Template-JSONs haben bei dir nicht funktioniert. Dieses Drop-In
macht den Umweg über Bricks-Templates komplett überflüssig:

- **Mitarbeiter-Einzelseiten** werden automatisch im GWT-Design gerendert
- **Startseite** bekommt einen neuen Shortcode `[gwt_startseite]`, der alles
  auf einmal rendert (Begrüßung, Wetter, News, Welcome on Board, Schwarzes Brett)

Keine Bricks-Templates. Keine Conditions. Nichts.

---

## Installation – 3 Schritte

### Schritt 1: Datei hochladen

`gwt-auto-render.php` herunterladen und per FTP / Raidboxes-Dateimanager nach:

```
wp-content/themes/gwt-intranet-child/inc/gwt-auto-render.php
```

### Schritt 2: In functions.php einbinden

In der Datei `wp-content/themes/gwt-intranet-child/functions.php` ganz unten
**eine Zeile** hinzufügen:

```php
require_once get_stylesheet_directory() . '/inc/gwt-auto-render.php';
```

### Schritt 3: Startseite anlegen

1. WP Admin → **Seiten** → **Erstellen**
2. Titel: `Startseite`
3. Im Editor: einen **Custom HTML / Shortcode-Block** einfügen mit:
   ```
   [gwt_startseite]
   ```
4. **Veröffentlichen**
5. WP Admin → **Einstellungen → Lesen** → "Eine statische Seite" → als Startseite wählen

## Mitarbeiter-Seiten testen

- Einfach eine beliebige Mitarbeiter-Seite aufrufen (z.B. `/mitarbeiter/max-mustermann/`)
- Sollte sofort die komplette Profil-Card in GWT-Design anzeigen
- Kein Bricks-Template nötig

## Troubleshooting

**"Startseite zeigt nur den Shortcode-Text [gwt_startseite]"**
→ Der `require_once` wurde nicht in `functions.php` ergänzt oder die Datei ist
nicht am richtigen Ort. Prüfen: Seite `/?p=X` aufrufen → wenn immer noch Text
statt Rendering, Pfad kontrollieren.

**"Mitarbeiter-Seite zeigt nichts / leer"**
→ In WP Admin → **Einstellungen → Permalinks** einmal "Speichern" klicken
(ohne etwas zu ändern). Das spült die Rewrite-Rules.

**"Wetter-Widget fehlt"**
→ Der Shortcode `[wetter_widget]` kommt aus dem Child-Theme
(`inc/weather-widget.php`). Dieses muss aktiv sein.

**Kann ich das Design anpassen?**
→ Ja, alle CSS-Regeln sind oben in der Datei (`wp_head` Hook). Farben und
Schriftgrößen dort ändern.

## Bricks-Templates löschen (optional)

Die bereits importierten Templates (GWT Header, Footer, Mitarbeiter, usw.)
kannst du in **Bricks → Templates** einfach löschen. Sie werden nicht mehr
gebraucht.

Oder stehen lassen – sie stören nicht, solange keine Conditions gesetzt sind.
