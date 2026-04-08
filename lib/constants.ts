export const UI_STRINGS = {
  title: "Dashboard GWT Group",
  subtitle: "Recruiting Ads",
  login: {
    title: "Anmelden",
    password: "Passwort",
    submit: "Einloggen",
    error: "Falsches Passwort",
  },
  kpi: {
    spent: "Ausgegeben",
    spentSince: "seit",
    remaining: "Verbleibend",
    remainingUntil: "bis",
    avgMonthly: "Durchschn. / Monat",
    projection: "Hochrechnung (Jahr)",
    onTrack: "Im Plan",
    overBudget: "Ueber Budget",
    underBudget: "Unter Budget",
  },
  chart: {
    title: "Budget-Verlauf",
    targetLine: "Soll / Monat",
    spend: "Ausgaben",
  },
  trends: {
    title: "Trend-Vergleich",
    wow: "Woche zu Woche",
    mom: "Monat zu Monat",
    reach: "Reichweite",
    impressions: "Impressionen",
    cpc: "CPC",
    costPerResult: "Kosten / Ergebnis",
    spend: "Ausgaben",
    current: "Aktuell",
    previous: "Vorher",
    change: "Veraenderung",
  },
  campaigns: {
    title: "Aktive Anzeigen",
    campaign: "Kampagne",
    ad: "Anzeige",
    reach: "Reichweite",
    impressions: "Impressionen",
    cpc: "CPC",
    costPerResult: "Kosten / Ergebnis",
    spend: "Ausgaben",
    results: "Ergebnisse",
    filter: "Filtern",
    allCampaigns: "Alle Kampagnen",
    search: "Anzeige suchen...",
  },
  perspective: {
    title: "Perspective Funnels",
    funnel: "Funnel",
    conversionRate: "Konversionsrate",
    completions: "Abschluesse",
    visitors: "Besucher",
    bounceRate: "Absprungrate",
  },
  refresh: "Aktualisieren",
  lastUpdated: "Letzte Aktualisierung",
  loading: "Laden...",
  error: "Fehler beim Laden der Daten",
  noData: "Keine Daten vorhanden",
} as const;

export const PACE_THRESHOLDS = {
  onTrackMin: 0.9,
  onTrackMax: 1.1,
} as const;

export const GERMAN_MONTHS = [
  "Jan", "Feb", "Maer", "Apr", "Mai", "Jun",
  "Jul", "Aug", "Sep", "Okt", "Nov", "Dez",
] as const;

export const CACHE_TTL = 900; // 15 minutes in seconds
