import { unstable_cache } from "next/cache";
import type { PerspectiveFunnelKPIs } from "@/types/perspective";
import { CACHE_TTL } from "./constants";

function getApiKey(): string {
  const key = process.env.PERSPECTIVE_API_KEY;
  if (!key) throw new Error("PERSPECTIVE_API_KEY not configured");
  return key;
}

function getFunnelIds(): string[] {
  const ids = process.env.PERSPECTIVE_FUNNEL_IDS;
  if (!ids) return [];
  return ids.split(",").map((id) => id.trim()).filter(Boolean);
}

export function isPerspectiveConfigured(): boolean {
  return !!(process.env.PERSPECTIVE_API_KEY && process.env.PERSPECTIVE_FUNNEL_IDS);
}

async function fetchFunnelKPIs(funnelId: string): Promise<PerspectiveFunnelKPIs | null> {
  try {
    const apiKey = getApiKey();
    const response = await fetch(
      `https://api.perspective.co/api/v1/funnels/${funnelId}/metrics/kpi`,
      {
        headers: {
          Authorization: `Bearer ${apiKey}`,
          "Content-Type": "application/json",
        },
      }
    );

    if (!response.ok) {
      console.error(`Perspective API error for funnel ${funnelId}: ${response.status}`);
      return null;
    }

    const data = await response.json();
    return {
      funnelId,
      funnelName: data.funnelName || funnelId,
      conversionRate: data.data?.conversionRate || 0,
      completions: data.data?.completions || 0,
      visitors: data.data?.visitors || 0,
      bounceRate: data.data?.bounceRate || 0,
    };
  } catch (error) {
    console.error(`Error fetching Perspective data for funnel ${funnelId}:`, error);
    return null;
  }
}

export const fetchAllFunnelKPIs = unstable_cache(
  async (): Promise<PerspectiveFunnelKPIs[]> => {
    const funnelIds = getFunnelIds();
    if (funnelIds.length === 0) return [];

    const results = await Promise.all(funnelIds.map(fetchFunnelKPIs));
    return results.filter((r): r is PerspectiveFunnelKPIs => r !== null);
  },
  ["perspective-funnel-kpis"],
  { revalidate: CACHE_TTL, tags: ["perspective"] }
);
