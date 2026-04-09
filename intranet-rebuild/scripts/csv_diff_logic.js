/**
 * SAGE DPW CSV-Vergleich fuer n8n Function Node (v1.x)
 *
 * Anleitung:
 *   1. In n8n einen "Function"-Node erstellen.
 *   2. Den gesamten Inhalt dieser Datei in das Code-Feld des Function-Nodes einfuegen.
 *   3. Der vorherige Node muss ein JSON-Objekt mit zwei Feldern liefern:
 *        - current_csv  : Array von Mitarbeiter-Objekten (aktueller Monat)
 *        - previous_csv : Array von Mitarbeiter-Objekten (vorheriger Monat)
 *      Die Felder stammen aus einem semikolon-getrennten SAGE-DPW-Export mit folgenden Spalten:
 *        Personalnummer, Vorname, Nachname, Email, Telefon, Mobil,
 *        Position, Standort, Abteilung, Eintrittsdatum, Status
 *
 * Vergleichslogik:
 *   - Neue Mitarbeiter:        Personalnummer nur im aktuellen Monat vorhanden
 *   - Geaenderte Mitarbeiter:  Personalnummer in beiden, aber Felder unterschiedlich
 *   - Deaktivierte Mitarbeiter: Personalnummer nur im vorherigen Monat vorhanden
 *
 * Fuer neue Mitarbeiter wird ein kryptographisch sicheres 24-Zeichen-Passwort erzeugt.
 */

// ── Hilfsfunktionen ──────────────────────────────────────────────────────────

/** Alle zu vergleichenden Felder (ohne Personalnummer, da diese der Schluessel ist). */
const VERGLEICHSFELDER = [
  'Vorname',
  'Nachname',
  'Email',
  'Telefon',
  'Mobil',
  'Position',
  'Standort',
  'Abteilung',
  'Eintrittsdatum',
  'Status',
];

/**
 * Entfernt einen eventuell vorhandenen UTF-8 BOM (Byte Order Mark)
 * aus einem String-Wert.
 */
function bomEntfernen(wert) {
  if (typeof wert !== 'string') return wert;
  return wert.replace(/^\uFEFF/, '');
}

/**
 * Normalisiert einen Feldwert:
 *   - BOM entfernen
 *   - Whitespace trimmen
 *   - null / undefined → leerer String
 */
function feldNormalisieren(wert) {
  if (wert === null || wert === undefined) return '';
  if (typeof wert !== 'string') return String(wert).trim();
  return bomEntfernen(wert).trim();
}

/**
 * Normalisiert alle relevanten Felder eines Mitarbeiter-Objekts.
 * Gibt ein neues Objekt zurueck (Original bleibt unveraendert).
 */
function mitarbeiterNormalisieren(datensatz) {
  const normalisiert = {};
  const alleFeldnamen = ['Personalnummer', ...VERGLEICHSFELDER];

  for (const feld of alleFeldnamen) {
    normalisiert[feld] = feldNormalisieren(datensatz[feld]);
  }

  return normalisiert;
}

/**
 * Erstellt eine Map (Personalnummer → normalisiertes Objekt) aus einem Array.
 * BOM-bereinigte Schluessel werden als Map-Key verwendet.
 */
function mitarbeiterMapErstellen(liste) {
  const map = new Map();

  for (const eintrag of liste) {
    const normalisiert = mitarbeiterNormalisieren(eintrag);
    const schluessel = normalisiert.Personalnummer;

    // Leere Personalnummer ueberspringen – ungueltige Datensaetze ignorieren
    if (schluessel === '') continue;

    map.set(schluessel, normalisiert);
  }

  return map;
}

/**
 * Vergleicht zwei Feldwerte. Bei E-Mail-Feldern wird
 * gross-/kleinschreibungsunabhaengig verglichen.
 */
function felderGleich(feldname, wertA, wertB) {
  if (feldname === 'Email') {
    return wertA.toLowerCase() === wertB.toLowerCase();
  }
  return wertA === wertB;
}

/**
 * Erzeugt ein kryptographisch sicheres Passwort mit 24 Zeichen.
 * Zeichenvorrat: a-z, A-Z, 0-9, !@#$%&*
 *
 * Verwendet crypto.randomBytes, das in der n8n-Node.js-Laufzeitumgebung
 * verfuegbar ist.
 */
function passwortGenerieren() {
  const crypto = require('crypto');
  const zeichenvorrat =
    'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
  const laenge = 24;

  // Genuegend zufaellige Bytes erzeugen, um Modulo-Verzerrung zu minimieren.
  // Wir verwenden Rejection-Sampling: nur Bytes akzeptieren, die kleiner als
  // das groesste Vielfache der Zeichenvorrat-Laenge sind.
  const maxGueltig = 256 - (256 % zeichenvorrat.length);
  let passwort = '';

  while (passwort.length < laenge) {
    // Pro Durchlauf genuegend Bytes holen (Puffer fuer abgelehnte Werte)
    const bytes = crypto.randomBytes(laenge * 2);

    for (let i = 0; i < bytes.length && passwort.length < laenge; i++) {
      // Rejection-Sampling – Bytes >= maxGueltig verwerfen
      if (bytes[i] < maxGueltig) {
        passwort += zeichenvorrat[bytes[i] % zeichenvorrat.length];
      }
    }
  }

  return passwort;
}

// ── Hauptlogik ───────────────────────────────────────────────────────────────

// Eingabedaten aus dem vorherigen n8n-Node lesen
const eingabe = $input.first().json;
const aktuelleCSV = eingabe.current_csv || [];
const vorherigeCSV = eingabe.previous_csv || [];

// Maps nach Personalnummer aufbauen
const aktuellMap = mitarbeiterMapErstellen(aktuelleCSV);
const vorherigeMap = mitarbeiterMapErstellen(vorherigeCSV);

// ── Neue Mitarbeiter ermitteln ───────────────────────────────────────────────
const neueMitarbeiter = [];

for (const [pnr, mitarbeiter] of aktuellMap) {
  if (!vorherigeMap.has(pnr)) {
    // Neuer Mitarbeiter – Passwort erzeugen und anfuegen
    neueMitarbeiter.push({
      ...mitarbeiter,
      generiertes_passwort: passwortGenerieren(),
    });
  }
}

// ── Geaenderte Mitarbeiter ermitteln ─────────────────────────────────────────
const geaenderteMitarbeiter = [];

for (const [pnr, aktuell] of aktuellMap) {
  if (!vorherigeMap.has(pnr)) continue; // Neue werden oben behandelt

  const vorherig = vorherigeMap.get(pnr);
  const aenderungen = {};
  let hatAenderungen = false;

  for (const feld of VERGLEICHSFELDER) {
    if (!felderGleich(feld, aktuell[feld], vorherig[feld])) {
      aenderungen[feld] = {
        alt: vorherig[feld],
        neu: aktuell[feld],
      };
      hatAenderungen = true;
    }
  }

  if (hatAenderungen) {
    geaenderteMitarbeiter.push({
      ...aktuell,
      _aenderungen: aenderungen,
    });
  }
}

// ── Deaktivierte Mitarbeiter ermitteln ───────────────────────────────────────
const deaktivierteMitarbeiter = [];

for (const [pnr, mitarbeiter] of vorherigeMap) {
  if (!aktuellMap.has(pnr)) {
    deaktivierteMitarbeiter.push({ ...mitarbeiter });
  }
}

// ── Zusammenfassung erstellen ────────────────────────────────────────────────
const zusammenfassung = {
  gesamt_vorher: vorherigeMap.size,
  gesamt_nachher: aktuellMap.size,
  neue: neueMitarbeiter.length,
  geaenderte: geaenderteMitarbeiter.length,
  deaktivierte: deaktivierteMitarbeiter.length,
  zeitstempel: new Date().toISOString(),
};

// ── Ergebnis im n8n-kompatiblen Format zurueckgeben ──────────────────────────
return [
  {
    json: {
      neue_mitarbeiter: neueMitarbeiter,
      geaenderte_mitarbeiter: geaenderteMitarbeiter,
      deaktivierte_mitarbeiter: deaktivierteMitarbeiter,
      zusammenfassung: zusammenfassung,
    },
  },
];
