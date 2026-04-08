import { NextResponse } from "next/server";
import { fetchAdLevelInsights, fetchActiveCampaigns } from "@/lib/meta-api";
import { getFiscalYearConfig } from "@/lib/budget-calculator";

export async function GET() {
  try {
    const config = getFiscalYearConfig();
    const today = new Date().toISOString().split("T")[0];

    const [campaigns, adInsights] = await Promise.all([
      fetchActiveCampaigns(),
      fetchAdLevelInsights(config.start, today),
    ]);

    return NextResponse.json({ campaigns, adInsights });
  } catch (error) {
    console.error("Error fetching campaigns:", error);
    return NextResponse.json(
      { error: "Fehler beim Laden der Kampagnen" },
      { status: 500 }
    );
  }
}
