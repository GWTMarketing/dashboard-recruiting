import { unstable_cache } from "next/cache";
import type {
  MetaApiResponse,
  MetaInsightRow,
  MetaAdInsightRow,
  MetaCampaign,
  AdPerformance,
} from "@/types/meta";
import { CACHE_TTL } from "./constants";

const API_VERSION = "v24.0";
const BASE_URL = `https://graph.facebook.com/${API_VERSION}`;

function getAccountId(): string {
  const id = process.env.META_AD_ACCOUNT_ID;
  if (!id) throw new Error("META_AD_ACCOUNT_ID not configured");
  return id;
}

function getAccessToken(): string {
  const token = process.env.META_ACCESS_TOKEN;
  if (!token) throw new Error("META_ACCESS_TOKEN not configured");
  return token;
}

async function metaFetch<T>(
  endpoint: string,
  params: Record<string, string>
): Promise<T> {
  const url = new URL(`${BASE_URL}/${endpoint}`);
  url.searchParams.set("access_token", getAccessToken());
  for (const [key, value] of Object.entries(params)) {
    url.searchParams.set(key, value);
  }

  const response = await fetch(url.toString(), {
    headers: { "Content-Type": "application/json" },
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({}));
    throw new Error(
      `Meta API error ${response.status}: ${JSON.stringify(error)}`
    );
  }

  return response.json();
}

async function fetchAllPages<T>(
  endpoint: string,
  params: Record<string, string>
): Promise<T[]> {
  const firstPage = await metaFetch<MetaApiResponse<T>>(endpoint, params);
  const allData = [...firstPage.data];

  let nextUrl = firstPage.paging?.next;
  while (nextUrl) {
    const response = await fetch(nextUrl);
    if (!response.ok) break;
    const page = (await response.json()) as MetaApiResponse<T>;
    allData.push(...page.data);
    nextUrl = page.paging?.next;
  }

  return allData;
}

export const fetchAccountInsights = unstable_cache(
  async (since: string, until: string): Promise<MetaInsightRow[]> => {
    const accountId = getAccountId();
    return fetchAllPages<MetaInsightRow>(`${accountId}/insights`, {
      fields:
        "spend,impressions,reach,cpc,cost_per_action_type,actions",
      time_range: JSON.stringify({ since, until }),
      time_increment: "monthly",
    });
  },
  ["meta-account-insights"],
  { revalidate: CACHE_TTL, tags: ["meta-insights"] }
);

export const fetchActiveCampaigns = unstable_cache(
  async (): Promise<MetaCampaign[]> => {
    const accountId = getAccountId();
    return fetchAllPages<MetaCampaign>(`${accountId}/campaigns`, {
      fields: "name,status,objective,daily_budget,lifetime_budget,start_time",
      filtering: JSON.stringify([
        {
          field: "effective_status",
          operator: "IN",
          value: ["ACTIVE"],
        },
      ]),
    });
  },
  ["meta-active-campaigns"],
  { revalidate: CACHE_TTL, tags: ["meta-insights"] }
);

export const fetchAdLevelInsights = unstable_cache(
  async (since: string, until: string): Promise<AdPerformance[]> => {
    const accountId = getAccountId();
    const rows = await fetchAllPages<MetaAdInsightRow>(
      `${accountId}/insights`,
      {
        fields:
          "ad_id,ad_name,campaign_name,spend,impressions,reach,cpc,cost_per_action_type,actions",
        level: "ad",
        filtering: JSON.stringify([
          {
            field: "ad.effective_status",
            operator: "IN",
            value: ["ACTIVE"],
          },
        ]),
        time_range: JSON.stringify({ since, until }),
      }
    );

    return rows.map((row) => {
      const results =
        row.actions?.reduce(
          (sum, a) => sum + (parseFloat(a.value) || 0),
          0
        ) || 0;
      const spend = parseFloat(row.spend) || 0;

      return {
        adId: row.ad_id,
        adName: row.ad_name,
        campaignName: row.campaign_name,
        spend,
        impressions: parseInt(row.impressions) || 0,
        reach: parseInt(row.reach) || 0,
        cpc: parseFloat(row.cpc || "0") || 0,
        costPerResult: results > 0 ? spend / results : 0,
        results,
      };
    });
  },
  ["meta-ad-insights"],
  { revalidate: CACHE_TTL, tags: ["meta-insights"] }
);

export const fetchWeeklyInsights = unstable_cache(
  async (since: string, until: string): Promise<MetaInsightRow[]> => {
    const accountId = getAccountId();
    return fetchAllPages<MetaInsightRow>(`${accountId}/insights`, {
      fields:
        "spend,impressions,reach,cpc,cost_per_action_type,actions",
      time_range: JSON.stringify({ since, until }),
      time_increment: "7",
    });
  },
  ["meta-weekly-insights"],
  { revalidate: CACHE_TTL, tags: ["meta-insights"] }
);
