import { NextResponse } from "next/server";
import { fetchAllFunnelKPIs, isPerspectiveConfigured } from "@/lib/perspective-api";

export async function GET() {
  if (!isPerspectiveConfigured()) {
    return NextResponse.json({ configured: false, funnels: [] });
  }

  try {
    const funnels = await fetchAllFunnelKPIs();
    return NextResponse.json({ configured: true, funnels });
  } catch (error) {
    console.error("Error fetching Perspective data:", error);
    return NextResponse.json(
      { error: "Fehler beim Laden der Perspective-Daten" },
      { status: 500 }
    );
  }
}
