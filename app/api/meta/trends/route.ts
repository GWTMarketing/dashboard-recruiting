import { NextResponse } from "next/server";
import { fetchWeeklyInsights, fetchAccountInsights } from "@/lib/meta-api";
import { getFiscalYearConfig } from "@/lib/budget-calculator";
import { parseTrendData, calculateTrendComparisons } from "@/lib/trend-calculator";

export async function GET() {
  try {
    const config = getFiscalYearConfig();
    const today = new Date().toISOString().split("T")[0];

    const [weeklyRows, monthlyRows] = await Promise.all([
      fetchWeeklyInsights(config.start, today),
      fetchAccountInsights(config.start, config.end),
    ]);

    const weeklyData = parseTrendData(weeklyRows);
    const monthlyData = parseTrendData(monthlyRows);

    let wow = null;
    if (weeklyData.length >= 2) {
      wow = calculateTrendComparisons(
        weeklyData[weeklyData.length - 1],
        weeklyData[weeklyData.length - 2]
      );
    }

    let mom = null;
    if (monthlyData.length >= 2) {
      mom = calculateTrendComparisons(
        monthlyData[monthlyData.length - 1],
        monthlyData[monthlyData.length - 2]
      );
    }

    return NextResponse.json({ wow, mom, weeklyData, monthlyData });
  } catch (error) {
    console.error("Error fetching trends:", error);
    return NextResponse.json(
      { error: "Fehler beim Laden der Trends" },
      { status: 500 }
    );
  }
}
