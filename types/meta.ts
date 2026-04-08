export interface MetaInsightRow {
  date_start: string;
  date_stop: string;
  spend: string;
  impressions: string;
  reach: string;
  cpc?: string;
  cost_per_action_type?: Array<{
    action_type: string;
    value: string;
  }>;
  actions?: Array<{
    action_type: string;
    value: string;
  }>;
}

export interface MetaAdInsightRow extends MetaInsightRow {
  ad_id: string;
  ad_name: string;
  campaign_name: string;
}

export interface MetaCampaign {
  id: string;
  name: string;
  status: string;
  objective: string;
  daily_budget?: string;
  lifetime_budget?: string;
  start_time: string;
  stop_time?: string;
}

export interface MetaApiResponse<T> {
  data: T[];
  paging?: {
    cursors: {
      before: string;
      after: string;
    };
    next?: string;
  };
}

export interface BudgetMetrics {
  spent: number;
  remaining: number;
  averageMonthly: number;
  projectedAnnual: number;
  paceRatio: number;
  status: "on-track" | "over" | "under";
  statusLabel: string;
  monthlyData: MonthlySpend[];
}

export interface MonthlySpend {
  month: string;
  spend: number;
}

export interface AdPerformance {
  adId: string;
  adName: string;
  campaignName: string;
  spend: number;
  impressions: number;
  reach: number;
  cpc: number;
  costPerResult: number;
  results: number;
}

export interface TrendData {
  period: string;
  spend: number;
  impressions: number;
  reach: number;
  cpc: number;
  costPerResult: number;
}

export interface TrendComparison {
  metric: string;
  current: number;
  previous: number;
  changePercent: number;
}
