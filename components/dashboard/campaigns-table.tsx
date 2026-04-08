"use client";

import { useState, useMemo } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { UI_STRINGS } from "@/lib/constants";
import { formatCurrency, formatNumber } from "@/lib/format";
import { Search, Filter } from "lucide-react";
import type { AdPerformance } from "@/types/meta";

interface CampaignsTableProps {
  ads: AdPerformance[];
}

export function CampaignsTable({ ads }: CampaignsTableProps) {
  const [search, setSearch] = useState("");
  const [selectedCampaign, setSelectedCampaign] = useState<string>("all");

  const campaignNames = useMemo(() => {
    const names = [...new Set(ads.map((a) => a.campaignName))];
    return names.sort();
  }, [ads]);

  const filtered = useMemo(() => {
    return ads.filter((ad) => {
      const matchesCampaign =
        selectedCampaign === "all" || ad.campaignName === selectedCampaign;
      const matchesSearch =
        !search ||
        ad.adName.toLowerCase().includes(search.toLowerCase()) ||
        ad.campaignName.toLowerCase().includes(search.toLowerCase());
      return matchesCampaign && matchesSearch;
    });
  }, [ads, selectedCampaign, search]);

  if (ads.length === 0) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>{UI_STRINGS.campaigns.title}</CardTitle>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-[#94a3b8]">{UI_STRINGS.noData}</p>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <CardTitle>{UI_STRINGS.campaigns.title}</CardTitle>
            <Badge variant="primary">{ads.length}</Badge>
          </div>

          {/* Filters */}
          <div className="flex flex-col sm:flex-row gap-2">
            {/* Search */}
            <div className="relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-[#94a3b8]" />
              <input
                type="text"
                placeholder={UI_STRINGS.campaigns.search}
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="pl-9 pr-3 py-2 text-xs font-medium rounded-xl border border-[rgba(0,64,113,0.12)] bg-white text-[#1a1a2e] placeholder:text-[#94a3b8] focus:outline-none focus:ring-2 focus:ring-[#004071]/20 focus:border-[#004071]/30 w-full sm:w-52 transition-all"
              />
            </div>

            {/* Campaign filter */}
            {campaignNames.length > 1 && (
              <div className="relative">
                <Filter className="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-[#94a3b8]" />
                <select
                  value={selectedCampaign}
                  onChange={(e) => setSelectedCampaign(e.target.value)}
                  className="pl-9 pr-8 py-2 text-xs font-medium rounded-xl border border-[rgba(0,64,113,0.12)] bg-white text-[#1a1a2e] focus:outline-none focus:ring-2 focus:ring-[#004071]/20 focus:border-[#004071]/30 appearance-none cursor-pointer w-full sm:w-auto transition-all"
                >
                  <option value="all">{UI_STRINGS.campaigns.allCampaigns}</option>
                  {campaignNames.map((name) => (
                    <option key={name} value={name}>
                      {name}
                    </option>
                  ))}
                </select>
              </div>
            )}
          </div>
        </div>
      </CardHeader>
      <CardContent>
        <div className="overflow-x-auto -mx-6">
          <table className="w-full text-sm min-w-[700px]">
            <thead>
              <tr className="border-b border-[rgba(0,64,113,0.08)]">
                <th className="text-left py-3 px-6 text-[10px] font-bold uppercase tracking-widest text-[#94a3b8]">
                  {UI_STRINGS.campaigns.campaign}
                </th>
                <th className="text-left py-3 px-3 text-[10px] font-bold uppercase tracking-widest text-[#94a3b8]">
                  {UI_STRINGS.campaigns.ad}
                </th>
                <th className="text-right py-3 px-3 text-[10px] font-bold uppercase tracking-widest text-[#94a3b8]">
                  {UI_STRINGS.campaigns.reach}
                </th>
                <th className="text-right py-3 px-3 text-[10px] font-bold uppercase tracking-widest text-[#94a3b8]">
                  {UI_STRINGS.campaigns.impressions}
                </th>
                <th className="text-right py-3 px-3 text-[10px] font-bold uppercase tracking-widest text-[#94a3b8]">
                  {UI_STRINGS.campaigns.cpc}
                </th>
                <th className="text-right py-3 px-3 text-[10px] font-bold uppercase tracking-widest text-[#94a3b8]">
                  {UI_STRINGS.campaigns.costPerResult}
                </th>
                <th className="text-right py-3 px-3 text-[10px] font-bold uppercase tracking-widest text-[#94a3b8]">
                  {UI_STRINGS.campaigns.results}
                </th>
                <th className="text-right py-3 px-6 text-[10px] font-bold uppercase tracking-widest text-[#94a3b8]">
                  {UI_STRINGS.campaigns.spend}
                </th>
              </tr>
            </thead>
            <tbody>
              {filtered.map((ad) => (
                <tr
                  key={ad.adId}
                  className="border-b border-[rgba(0,64,113,0.04)] hover:bg-[#c7eafb]/10 transition-colors"
                >
                  <td className="py-3.5 px-6 text-[#64748b] max-w-[180px] truncate text-xs font-medium">
                    {ad.campaignName}
                  </td>
                  <td className="py-3.5 px-3 text-[#1a1a2e] font-bold max-w-[180px] truncate text-xs">
                    {ad.adName}
                  </td>
                  <td className="text-right py-3.5 px-3 text-[#1a1a2e] tabular-nums text-xs font-medium">
                    {formatNumber(ad.reach)}
                  </td>
                  <td className="text-right py-3.5 px-3 text-[#1a1a2e] tabular-nums text-xs font-medium">
                    {formatNumber(ad.impressions)}
                  </td>
                  <td className="text-right py-3.5 px-3 text-[#1a1a2e] tabular-nums text-xs font-medium">
                    {formatCurrency(ad.cpc)}
                  </td>
                  <td className="text-right py-3.5 px-3 text-[#1a1a2e] tabular-nums text-xs font-medium">
                    {formatCurrency(ad.costPerResult)}
                  </td>
                  <td className="text-right py-3.5 px-3 text-[#1a1a2e] tabular-nums text-xs font-medium">
                    {formatNumber(ad.results)}
                  </td>
                  <td className="text-right py-3.5 px-6 font-extrabold text-[#004071] tabular-nums text-xs">
                    {formatCurrency(ad.spend)}
                  </td>
                </tr>
              ))}
              {filtered.length === 0 && (
                <tr>
                  <td
                    colSpan={8}
                    className="py-8 text-center text-sm text-[#94a3b8]"
                  >
                    Keine Anzeigen gefunden
                  </td>
                </tr>
              )}
            </tbody>
            {filtered.length > 0 && (
              <tfoot>
                <tr className="border-t-2 border-[#004071]/10">
                  <td
                    className="py-3.5 px-6 text-xs font-extrabold text-[#004071] uppercase tracking-wider"
                    colSpan={2}
                  >
                    Gesamt
                  </td>
                  <td className="text-right py-3.5 px-3 text-xs font-extrabold text-[#004071] tabular-nums">
                    {formatNumber(filtered.reduce((s, a) => s + a.reach, 0))}
                  </td>
                  <td className="text-right py-3.5 px-3 text-xs font-extrabold text-[#004071] tabular-nums">
                    {formatNumber(
                      filtered.reduce((s, a) => s + a.impressions, 0)
                    )}
                  </td>
                  <td className="text-right py-3.5 px-3 text-xs font-extrabold text-[#004071]">
                    &mdash;
                  </td>
                  <td className="text-right py-3.5 px-3 text-xs font-extrabold text-[#004071]">
                    &mdash;
                  </td>
                  <td className="text-right py-3.5 px-3 text-xs font-extrabold text-[#004071] tabular-nums">
                    {formatNumber(filtered.reduce((s, a) => s + a.results, 0))}
                  </td>
                  <td className="text-right py-3.5 px-6 text-xs font-extrabold text-[#004071] tabular-nums">
                    {formatCurrency(filtered.reduce((s, a) => s + a.spend, 0))}
                  </td>
                </tr>
              </tfoot>
            )}
          </table>
        </div>
      </CardContent>
    </Card>
  );
}
