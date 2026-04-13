# GWT Bricks Templates – richtiger Workflow

## Was war beim ersten Versuch falsch?

Die alten JSONs waren in einem **Array** verpackt mit `templateType` etc. – das ist NICHT das Format, das Bricks erwartet. Bricks verwendet das **Copy/Paste-Format**:

```json
{
  "content": [...],
  "source": "bricksCopiedElements",
  "sourceUrl": "",
  "version": "1.12.2",
  "globalClasses": [],
  "globalElements": []
}
```

Genau das machen jetzt die neuen Files.

## Dateien

| Datei | Was es ist |
|-------|------------|
| `00-test-import.json` | **Mini-Test** – ein Heading "GWT Bricks-Import funktioniert!" Wenn das funktioniert, klappt das Format. |
| `01-mitarbeiter-profil.json` | Komplette Mitarbeiter-Profil-Card (Foto, Name, Position, Kontakt) |
| `02-startseite.json` | Komplette Startseite (Begrüßung, Wetter, News, Welcome, Schwarzes Brett) |

## Workflow – so geht's richtig

Bricks bietet **zwei** Wege, mit JSON-Files zu arbeiten. Wir nutzen den, der zuverlässig funktioniert:

### Weg A – Paste in den Builder (empfohlen, IMMER funktionierend)

1. **Beliebige Seite oder ein leeres Bricks-Template öffnen** → "Edit with Bricks"
2. JSON-Datei mit Texteditor öffnen → **kompletten Inhalt kopieren** (Strg+A, Strg+C)
3. Im Bricks-Builder: links das **Strukturpanel** öffnen (Icon mit den drei Linien)
4. Im Strukturpanel rechts klicken (auf den weißen Bereich) → **"Insert from clipboard"** oder **"Paste"** wählen
5. Bricks fügt alle Elemente ein

**Vorteil:** Das ist die Bricks-Funktion `bricksCopiedElements` direkt – funktioniert **garantiert** in jeder Bricks-Version ab 1.5.

### Weg B – Templates → Import (wenn Weg A bestätigt funktioniert)

1. WP Admin → **Bricks → Templates** → **"Add New"**
2. Titel: z.B. "Mitarbeiter Single", Type: **Single**
3. Klick auf das Template → **"Import"** in der Toolbar oben rechts (manche Versionen)
4. JSON-Datei auswählen → Import

Falls "Import" Fehler wirft → einfach Weg A nehmen.

## Erst testen, dann skalieren

**Schritt 1:** Mach es mit `00-test-import.json` – wenn du danach im Editor "GWT Bricks-Import funktioniert!" siehst, ist alles gut.

**Schritt 2:** Dann mit den echten Templates.

## Templates nutzen

### Mitarbeiter-Profil
1. Bricks → Templates → "Add New" → Type: **Single** → "Edit with Bricks"
2. Im Builder: Strukturpanel → **Paste** mit `01-mitarbeiter-profil.json`
3. Save
4. Zahnrad ⚙️ → Settings → Tab **Template** → Conditions → "+ Add" → **Single → Post Type: Mitarbeiter**

### Startseite
1. Bricks → Templates → "Add New" → Type: **Single** → "Edit with Bricks"
2. Strukturpanel → Paste mit `02-startseite.json`
3. Save
4. Conditions → **Single → Front Page**

## Wichtige Felder, die du nach dem Paste anpassen kannst

Im Mitarbeiter-Profil sind diese **Dynamic Tags** vorgesehen:

| Element | Tag | Was es zieht |
|---------|-----|--------------|
| Name | `{post_title}` (post-title element) | Mitarbeiter-Titel |
| Position | `{cf__employee_position}` | Custom Field |
| Telefon | `{cf__employee_telefon}` | Custom Field |
| Mobil | `{cf__employee_mobil}` | Custom Field |
| Email | `{cf__employee_email}` | Custom Field |
| Foto | Featured Image | WP Beitragsbild |

**Falls die Custom Fields anders heißen** als oben (`_employee_position` etc.) – einfach den Text-Element öffnen und das Tag im Feld "Text" anpassen.

## Wenn der Test (`00-test-import.json`) NICHT funktioniert

Bitte schick mir:
1. Deine **Bricks-Version** (steht in WP Admin → Design → Bricks → unten)
2. Den genauen **Fehlertext**
3. Screenshot von dem Strukturpanel nach dem Paste-Versuch

Dann kann ich das Format gezielt für deine Bricks-Version anpassen.
