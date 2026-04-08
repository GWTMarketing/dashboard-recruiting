import { KpiCards } from "@/components/dashboard/kpi-cards";
import { BudgetChart } from "@/components/dashboard/budget-chart";
import { TrendComparisonSection } from "@/components/dashboard/trend-comparison";
import { CampaignsTable } from "@/components/dashboard/campaigns-table";
import { PerspectiveSection } from "@/components/dashboard/perspective-section";
import { RefreshButton } from "@/components/dashboard/refresh-button";
import { fetchAccountInsights, fetchAdLevelInsights, fetchWeeklyInsights } from "@/lib/meta-api";
import { calculateBudgetMetrics, getFiscalYearConfig } from "@/lib/budget-calculator";
import { parseTrendData, calculateTrendComparisons } from "@/lib/trend-calculator";
import { fetchAllFunnelKPIs, isPerspectiveConfigured } from "@/lib/perspective-api";
import { formatDate } from "@/lib/format";
import { UI_STRINGS } from "@/lib/constants";
import type { BudgetMetrics, AdPerformance, TrendComparison } from "@/types/meta";
import type { PerspectiveFunnelKPIs } from "@/types/perspective";

export const dynamic = "force-dynamic";
export const revalidate = 900;

interface DashboardData {
  metrics: BudgetMetrics | null;
  ads: AdPerformance[];
  wow: TrendComparison[] | null;
  mom: TrendComparison[] | null;
  funnels: PerspectiveFunnelKPIs[];
  error: string | null;
}

async function getDashboardData(): Promise<DashboardData> {
  const result: DashboardData = {
    metrics: null,
    ads: [],
    wow: null,
    mom: null,
    funnels: [],
    error: null,
  };

  try {
    const config = getFiscalYearConfig();
    const today = new Date().toISOString().split("T")[0];

    const [monthlyRows, ads, weeklyRows, funnels] = await Promise.all([
      fetchAccountInsights(config.start, config.end),
      fetchAdLevelInsights(config.start, today),
      fetchWeeklyInsights(config.start, today),
      isPerspectiveConfigured() ? fetchAllFunnelKPIs() : Promise.resolve([]),
    ]);

    result.metrics = calculateBudgetMetrics(monthlyRows);
    result.ads = ads;
    result.funnels = funnels;

    const weeklyData = parseTrendData(weeklyRows);
    const monthlyData = parseTrendData(monthlyRows);

    if (weeklyData.length >= 2) {
      result.wow = calculateTrendComparisons(
        weeklyData[weeklyData.length - 1],
        weeklyData[weeklyData.length - 2]
      );
    }
    if (monthlyData.length >= 2) {
      result.mom = calculateTrendComparisons(
        monthlyData[monthlyData.length - 1],
        monthlyData[monthlyData.length - 2]
      );
    }
  } catch (error) {
    console.error("Dashboard data error:", error);
    result.error =
      error instanceof Error ? error.message : UI_STRINGS.error;
  }

  return result;
}

export default async function DashboardPage() {
  const { metrics, ads, wow, mom, funnels, error } = await getDashboardData();
  const config = getFiscalYearConfig();
  const now = formatDate(new Date());

  return (
    <div className="min-h-screen bg-[#f8fafc]">
      {/* Header */}
      <header className="bg-[#004071] sticky top-0 z-10">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex items-center justify-between">
          <div className="flex items-center gap-4">
            <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-white/10 backdrop-blur-sm">
              <span className="text-sm font-black text-white tracking-tight">
                GWT
              </span>
            </div>
            <div>
              <h1 className="text-base font-extrabold text-white tracking-tight">
                {UI_STRINGS.title}
              </h1>
              <p className="text-xs text-[#c7eafb] font-medium">
                {UI_STRINGS.subtitle}
              </p>
            </div>
          </div>
          <RefreshButton />
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {error && (
          <div className="rounded-2xl bg-[#fef2f2] border border-[#dc2626]/10 px-5 py-4 text-sm font-medium text-[#dc2626]">
            {error}
          </div>
        )}

        {metrics && (
          <>
            <KpiCards
              metrics={metrics}
              fiscalStart={config.start}
              fiscalEnd={config.end}
            />
            <BudgetChart
              monthlyData={metrics.monthlyData}
              targetMonthly={config.annualBudget / 12}
            />
          </>
        )}

        <TrendComparisonSection wow={wow} mom={mom} />

        <CampaignsTable ads={ads} />

        <PerspectiveSection funnels={funnels} />
      </main>

      {/* Footer */}
      <footer className="border-t border-[rgba(0,64,113,0.06)] mt-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex items-center justify-between">
          <p className="text-[10px] font-bold uppercase tracking-widest text-[#94a3b8]">
            {UI_STRINGS.lastUpdated}: {now}
          </p>
          <p className="text-[10px] font-bold uppercase tracking-widest text-[#c7eafb]">
            GWT Group
          </p>
        </div>
      </footer>
    </div>
  );
}
