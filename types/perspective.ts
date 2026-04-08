export interface PerspectiveFunnelKPIs {
  funnelId: string;
  funnelName: string;
  conversionRate: number;
  completions: number;
  visitors: number;
  bounceRate: number;
}

export interface PerspectiveMetricsResponse {
  data: {
    visitors?: number;
    completions?: number;
    conversionRate?: number;
    bounceRate?: number;
  };
}
