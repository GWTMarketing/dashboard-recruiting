# GWT Intranet — Figma Design Preview

Pixelgenaue HTML-Previews aller 5 Intranet-Seiten, optimiert für Import in Figma über das **html.to.design**-Plugin.

## Enthaltene Seiten

| Nr | Datei | Beschreibung |
|----|-------|--------------|
| 01 | `01-startseite.html` | Dashboard mit Welcome-Hero, Wetter, News, Welcome on Board, Schwarzes Brett |
| 02 | `02-mitarbeiter-profil.html` | Einzelne Mitarbeiter-Seite mit Foto + Kontakt + Vor/Zurück-Navigation |
| 03 | `03-mitarbeiter-uebersicht.html` | Grid aller Mitarbeiter mit Filter-Chips |
| 04 | `04-schwarzes-brett.html` | Aushänge-Übersicht mit Kategorien + Ablaufdatum |
| 05 | `05-login.html` | Two-Column Login mit Hero + AD-SSO-Option |

Plus: `index.html` (Navigation zu allen Seiten) und `styles.css` (Design-System).

## Vorschau ansehen

### Lokal (empfohlen)
```bash
cd figma-preview
python3 -m http.server 8000
# http://localhost:8000 im Browser öffnen
```

### Oder: direkt per Doppelklick
`index.html` im Browser öffnen — funktioniert ebenfalls, Google Fonts wird online geladen.

## Nach Figma importieren

### Variante A — html.to.design Plugin (empfohlen)

1. In Figma: **Ressourcen (⇧I)** → **Plugins** → Suche "html.to.design"
2. Plugin installieren und öffnen
3. **"Import from URL"** wählen
4. Lokalen Server starten (siehe oben) und URL eingeben:
   `http://localhost:8000/01-startseite.html`
5. Plugin erzeugt ein Figma-Frame mit vollem Auto-Layout, Komponenten, Farben und Typografie
6. Für jede weitere Seite wiederholen

**Tipp:** Plugin-Option "Viewport Width" auf **1440px** stellen für die gewünschte Desktop-Breite.

### Variante B — Screenshots importieren

Falls das Plugin nicht klappt: Screenshots machen und als Bild in Figma einfügen (gut für Mood-Boards, aber nicht bearbeitbar).

## Design-System (in `styles.css`)

### Farben
```
--gwt-primary:   #004071   Sidebar, Buttons, Headlines
--gwt-secondary: #005e9e   Links, Akzente
--gwt-accent:    #c7eafb   Hintergründe, Active-State, Tags
--gwt-bg:        #f5f7fa   Page-Background
--gwt-text:      #0a2540   Body-Text
--gwt-muted:     #6b7a90   Sekundär-Text
```

### Typografie
- **Font:** Mulish (Google Fonts)
- **Weights:** 400, 500, 600, 700, 800, 900
- **Headlines:** 800, letter-spacing -0.03em (Apple-Style)
- **Body:** 15px, line-height 1.5

### Spacing / Radius
- Cards: 16px radius, 24px padding, `0 2px 8px rgba(0,64,113,0.06)` shadow
- Inputs / Buttons: 12px radius
- Sidebar-Items: 10px radius
- Tags / Chips: 999px (pill-shape)

## Anpassungen

Alle Seiten teilen sich `styles.css`. Änderungen in den CSS-Variablen (`:root` oben in der Datei) wirken sich auf alle Seiten aus.

Die Inhalte (Namen, Texte, Mitarbeiter) sind Platzhalter. Nach dem Import in Figma können sie dort bearbeitet werden.
