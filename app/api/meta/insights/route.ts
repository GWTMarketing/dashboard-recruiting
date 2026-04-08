import { NextResponse } from "next/server";
import { fetchAccountInsights } from "@/lib/meta-api";
import { calculateBudgetMetrics, getFiscalYearConfig } from "@/lib/budget-calculator";

export async function GET() {
  try {
    const config = getFiscalYearConfig();
    const rows = await fetchAccountInsights(config.start, config.end);
    const metrics = calculateBudgetMetrics(rows);
    return NextResponse.json(metrics);
  } catch (error) {
    console.error("Error fetching insights:", error);
    return NextResponse.json(
      { error: "Fehler beim Laden der Budget-Daten" },
      { status: 500 }
    );
  }
}
