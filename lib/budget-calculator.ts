import type { BudgetMetrics, MonthlySpend, MetaInsightRow } from "@/types/meta";
import { PACE_THRESHOLDS, GERMAN_MONTHS, UI_STRINGS } from "./constants";

function getFiscalYearStart(): Date {
  const start = process.env.FISCAL_YEAR_START;
  if (!start) throw new Error("FISCAL_YEAR_START not configured");
  return new Date(start + "T00:00:00");
}

function getFiscalYearEnd(): Date {
  const end = process.env.FISCAL_YEAR_END;
  if (!end) throw new Error("FISCAL_YEAR_END not configured");
  return new Date(end + "T23:59:59");
}

function getAnnualBudget(): number {
  const budget = process.env.ANNUAL_BUDGET;
  if (!budget) throw new Error("ANNUAL_BUDGET not configured");
  return parseFloat(budget);
}

function getMonthsElapsed(start: Date, now: Date): number {
  const fullMonths =
    (now.getFullYear() - start.getFullYear()) * 12 +
    (now.getMonth() - start.getMonth());
  const dayFraction = (now.getDate() - 1) / 30;
  return Math.max(fullMonths + dayFraction, 0.1);
}

function getTotalMonths(start: Date, end: Date): number {
  return (
    (end.getFullYear() - start.getFullYear()) * 12 +
    (end.getMonth() - start.getMonth()) +
    1
  );
}

export function calculateBudgetMetrics(
  insightRows: MetaInsightRow[]
): BudgetMetrics {
  const annualBudget = getAnnualBudget();
  const fiscalStart = getFiscalYearStart();
  const fiscalEnd = getFiscalYearEnd();
  const now = new Date();

  const monthlyData: MonthlySpend[] = insightRows.map((row) => {
    const date = new Date(row.date_start);
    const monthIndex = date.getMonth();
    return {
      month: GERMAN_MONTHS[monthIndex],
      spend: parseFloat(row.spend) || 0,
    };
  });

  const spent = monthlyData.reduce((sum, m) => sum + m.spend, 0);
  const remaining = Math.max(annualBudget - spent, 0);

  const monthsElapsed = getMonthsElapsed(fiscalStart, now);
  const totalMonths = getTotalMonths(fiscalStart, fiscalEnd);
  const averageMonthly = spent / monthsElapsed;

  const expectedToDate = (annualBudget / totalMonths) * monthsElapsed;
  const paceRatio = expectedToDate > 0 ? spent / expectedToDate : 0;

  const projectedAnnual = averageMonthly * totalMonths;

  let status: BudgetMetrics["status"];
  let statusLabel: string;

  if (paceRatio >= PACE_THRESHOLDS.onTrackMin && paceRatio <= PACE_THRESHOLDS.onTrackMax) {
    status = "on-track";
    statusLabel = UI_STRINGS.kpi.onTrack;
  } else if (paceRatio > PACE_THRESHOLDS.onTrackMax) {
    status = "over";
    statusLabel = UI_STRINGS.kpi.overBudget;
  } else {
    status = "under";
    statusLabel = UI_STRINGS.kpi.underBudget;
  }

  return {
    spent,
    remaining,
    averageMonthly,
    projectedAnnual,
    paceRatio,
    status,
    statusLabel,
    monthlyData,
  };
}

export function getFiscalYearConfig() {
  return {
    start: process.env.FISCAL_YEAR_START || "2026-03-01",
    end: process.env.FISCAL_YEAR_END || "2027-02-28",
    annualBudget: getAnnualBudget(),
  };
}
