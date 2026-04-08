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
  if (metric === UI_STRINGS.trends.cpc || metric === UI_STRINGS.trends.costPerResult || metric === UI_STRINGS.trends.spend) {
    return formatCurrency(value);
  }
  return formatNumber(value);
}

function TrendIcon({ change, metric }: { change: number; metric: string }) {
  // For cost metrics, going down is good
  const isCostMetric =
    metric === UI_STRINGS.trends.cpc ||
    metric === UI_STRINGS.trends.costPerResult ||
    metric === UI_STRINGS.trends.spend;

  const isPositive = isCostMetric ? change < 0 : change > 0;
  const isNeutral = Math.abs(change) < 1;

  if (isNeutral) return <Minus className="h-4 w-4 text-gray-400" />;
  if (isPositive) return <TrendingUp className="h-4 w-4 text-green-600" />;
  return <TrendingDown className="h-4 w-4 text-red-600" />;
}

function TrendTable({ data }: { data: TrendComparison[] }) {
  return (
    <div className="overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b border-gray-200">
            <th className="text-left py-3 px-2 font-medium text-gray-500">
              Metrik
            </th>
            <th className="text-right py-3 px-2 font-medium text-gray-500">
              {UI_STRINGS.trends.current}
            </th>
            <th className="text-right py-3 px-2 font-medium text-gray-500">
              {UI_STRINGS.trends.previous}
            </th>
            <th className="text-right py-3 px-2 font-medium text-gray-500">
              {UI_STRINGS.trends.change}
            </th>
          </tr>
        </thead>
        <tbody>
          {data.map((row) => {
            const isCostMetric =
              row.metric === UI_STRINGS.trends.cpc ||
              row.metric === UI_STRINGS.trends.costPerResult ||
              row.metric === UI_STRINGS.trends.spend;
            const isPositive = isCostMetric
              ? row.changePercent < 0
              : row.changePercent > 0;
            const colorClass =
              Math.abs(row.changePercent) < 1
                ? "text-gray-500"
                : isPositive
                  ? "text-green-600"
                  : "text-red-600";

            return (
              <tr key={row.metric} className="border-b border-gray-100">
                <td className="py-3 px-2 font-medium text-gray-700">
                  {row.metric}
                </td>
                <td className="text-right py-3 px-2 text-gray-900">
                  {formatMetricValue(row.metric, row.current)}
                </td>
                <td className="text-right py-3 px-2 text-gray-500">
                  {formatMetricValue(row.metric, row.previous)}
                </td>
                <td className={`text-right py-3 px-2 ${colorClass}`}>
                  <span className="inline-flex items-center gap-1">
                    <TrendIcon change={row.changePercent} metric={row.metric} />
                    {formatPercent(row.changePercent)}
                  </span>
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}

export function TrendComparisonSection({ wow, mom }: TrendComparisonProps) {
  const [activeTab, setActiveTab] = useState<"wow" | "mom">("wow");

  if (!wow && !mom) {
    return null;
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-lg">{UI_STRINGS.trends.title}</CardTitle>
        <div className="flex gap-2 mt-2">
          <button
            onClick={() => setActiveTab("wow")}
            className={`px-3 py-1.5 text-sm rounded-lg transition-colors ${
              activeTab === "wow"
                ? "bg-blue-600 text-white"
                : "bg-gray-100 text-gray-600 hover:bg-gray-200"
            }`}
          >
            {UI_STRINGS.trends.wow}
          </button>
          <button
            onClick={() => setActiveTab("mom")}
            className={`px-3 py-1.5 text-sm rounded-lg transition-colors ${
              activeTab === "mom"
                ? "bg-blue-600 text-white"
                : "bg-gray-100 text-gray-600 hover:bg-gray-200"
            }`}
          >
            {UI_STRINGS.trends.mom}
          </button>
        </div>
      </CardHeader>
      <CardContent>
        {activeTab === "wow" && wow ? (
          <TrendTable data={wow} />
        ) : activeTab === "mom" && mom ? (
          <TrendTable data={mom} />
        ) : (
          <p className="text-sm text-gray-500">{UI_STRINGS.noData}</p>
        )}
      </CardContent>
    </Card>
  );
}
