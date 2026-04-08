"use client";

import { useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { UI_STRINGS } from "@/lib/constants";
import { formatCurrency, formatNumber, formatPercent } from "@/lib/format";
import { TrendingUp, TrendingDown, Minus } from "lucide-react";
import type { TrendComparison } from "@/types/meta";

interface TrendComparisonProps {
  wow: TrendComparison[] | null;
  mom: TrendComparison[] | null;
}

function formatMetricValue(metric: string, value: number): string {
  if (
    metric === UI_STRINGS.trends.cpc ||
    metric === UI_STRINGS.trends.costPerResult ||
    metric === UI_STRINGS.trends.spend
  ) {
    return formatCurrency(value);
  }
  return formatNumber(value);
}

function TrendIcon({ change, metric }: { change: number; metric: string }) {
  const isCostMetric =
    metric === UI_STRINGS.trends.cpc ||
    metric === UI_STRINGS.trends.costPerResult ||
    metric === UI_STRINGS.trends.spend;

  const isPositive = isCostMetric ? change < 0 : change > 0;
  const isNeutral = Math.abs(change) < 1;

  if (isNeutral)
    return (
      <div className="flex items-center justify-center w-7 h-7 rounded-lg bg-[#f1f5f9]">
        <Minus className="h-3.5 w-3.5 text-[#94a3b8]" />
      </div>
    );
  if (isPositive)
    return (
      <div className="flex items-center justify-center w-7 h-7 rounded-lg bg-[#ecfdf5]">
        <TrendingUp className="h-3.5 w-3.5 text-[#059669]" />
      </div>
    );
  return (
    <div className="flex items-center justify-center w-7 h-7 rounded-lg bg-[#fef2f2]">
      <TrendingDown className="h-3.5 w-3.5 text-[#dc2626]" />
    </div>
  );
}

function TrendTable({ data }: { data: TrendComparison[] }) {
  return (
    <div className="space-y-2">
      {data.map((row) => {
        const isCostMetric =
          row.metric === UI_STRINGS.trends.cpc ||
          row.metric === UI_STRINGS.trends.costPerResult ||
          row.metric === UI_STRINGS.trends.spend;
        const isPositive = isCostMetric
          ? row.changePercent < 0
          : row.changePercent > 0;
        const isNeutral = Math.abs(row.changePercent) < 1;
        const colorClass = isNeutral
          ? "text-[#94a3b8]"
          : isPositive
            ? "text-[#059669]"
            : "text-[#dc2626]";

        return (
          <div
            key={row.metric}
            className="flex items-center justify-between py-3 px-4 rounded-xl bg-[#f8fafc] hover:bg-[#c7eafb]/20 transition-colors"
          >
            <div className="flex items-center gap-3">
              <TrendIcon change={row.changePercent} metric={row.metric} />
              <div>
                <p className="text-sm font-bold text-[#1a1a2e]">{row.metric}</p>
                <p className="text-xs text-[#94a3b8] mt-0.5">
                  {formatMetricValue(row.metric, row.previous)} &rarr;{" "}
                  {formatMetricValue(row.metric, row.current)}
                </p>
              </div>
            </div>
            <span className={`text-sm font-extrabold tabular-nums ${colorClass}`}>
              {formatPercent(row.changePercent)}
            </span>
          </div>
        );
      })}
    </div>
  );
}

export function TrendComparisonSection({ wow, mom }: TrendComparisonProps) {
  const [activeTab, setActiveTab] = useState<"wow" | "mom">("wow");

  if (!wow && !mom) return null;

  return (
    <Card>
      <CardHeader>
        <div className="flex items-center justify-between">
          <CardTitle>{UI_STRINGS.trends.title}</CardTitle>
          <div className="flex rounded-xl bg-[#f1f5f9] p-1">
            <button
              onClick={() => setActiveTab("wow")}
              className={`px-4 py-1.5 text-xs font-bold rounded-lg transition-all ${
                activeTab === "wow"
                  ? "bg-[#004071] text-white shadow-sm"
                  : "text-[#64748b] hover:text-[#004071]"
              }`}
            >
              {UI_STRINGS.trends.wow}
            </button>
            <button
              onClick={() => setActiveTab("mom")}
              className={`px-4 py-1.5 text-xs font-bold rounded-lg transition-all ${
                activeTab === "mom"
                  ? "bg-[#004071] text-white shadow-sm"
                  : "text-[#64748b] hover:text-[#004071]"
              }`}
            >
              {UI_STRINGS.trends.mom}
            </button>
          </div>
        </div>
      </CardHeader>
      <CardContent>
        {activeTab === "wow" && wow ? (
          <TrendTable data={wow} />
        ) : activeTab === "mom" && mom ? (
          <TrendTable data={mom} />
        ) : (
          <p className="text-sm text-[#94a3b8]">{UI_STRINGS.noData}</p>
        )}
      </CardContent>
    </Card>
  );
}
