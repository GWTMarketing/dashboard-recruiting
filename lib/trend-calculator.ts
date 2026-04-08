import type { TrendData, TrendComparison } from "@/types/meta";
import { UI_STRINGS } from "./constants";

function calcChange(current: number, previous: number): number {
  if (previous === 0) return current > 0 ? 100 : 0;
  return ((current - previous) / previous) * 100;
}

export function calculateTrendComparisons(
  current: TrendData,
  previous: TrendData
): TrendComparison[] {
  return [
    {
      metric: UI_STRINGS.trends.reach,
      current: current.reach,
      previous: previous.reach,
      changePercent: calcChange(current.reach, previous.reach),
    },
    {
      metric: UI_STRINGS.trends.impressions,
      current: current.impressions,
      previous: previous.impressions,
      changePercent: calcChange(current.impressions, previous.impressions),
    },
    {
      metric: UI_STRINGS.trends.cpc,
      current: current.cpc,
      previous: previous.cpc,
      changePercent: calcChange(current.cpc, previous.cpc),
    },
    {
      metric: UI_STRINGS.trends.costPerResult,
      current: current.costPerResult,
      previous: previous.costPerResult,
      changePercent: calcChange(current.costPerResult, previous.costPerResult),
    },
    {
      metric: UI_STRINGS.trends.spend,
      current: current.spend,
      previous: previous.spend,
      changePercent: calcChange(current.spend, previous.spend),
    },
  ];
}

export function parseTrendData(
  rows: Array<{
    date_start: string;
    spend: string;
    impressions: string;
    reach: string;
    cpc?: string;
    cost_per_action_type?: Array<{ action_type: string; value: string }>;
    actions?: Array<{ action_type: string; value: string }>;
  }>
): TrendData[] {
  return rows.map((row) => {
    const results =
      row.actions?.reduce((sum, a) => sum + (parseFloat(a.value) || 0), 0) || 0;
    const spend = parseFloat(row.spend) || 0;

    return {
      period: row.date_start,
      spend,
      impressions: parseInt(row.impressions) || 0,
      reach: parseInt(row.reach) || 0,
      cpc: parseFloat(row.cpc || "0") || 0,
      costPerResult: results > 0 ? spend / results : 0,
    };
  });
}
