"use client";

import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  ReferenceLine,
} from "recharts";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { UI_STRINGS } from "@/lib/constants";
import { formatCurrency } from "@/lib/format";
import type { MonthlySpend } from "@/types/meta";

interface BudgetChartProps {
  monthlyData: MonthlySpend[];
  targetMonthly: number;
}

function CustomTooltip({
  active,
  payload,
  label,
}: {
  active?: boolean;
  payload?: Array<{ value: number }>;
  label?: string;
}) {
  if (!active || !payload?.length) return null;
  return (
    <div className="rounded-lg bg-white border border-gray-200 p-3 shadow-lg">
      <p className="text-sm font-medium text-gray-900">{label}</p>
      <p className="text-sm text-blue-600">{formatCurrency(payload[0].value)}</p>
    </div>
  );
}

export function BudgetChart({ monthlyData, targetMonthly }: BudgetChartProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-lg">{UI_STRINGS.chart.title}</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="h-[300px] w-full">
          <ResponsiveContainer width="100%" height="100%">
            <BarChart data={monthlyData} margin={{ top: 5, right: 20, left: 20, bottom: 5 }}>
              <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
              <XAxis
                dataKey="month"
                tick={{ fontSize: 12 }}
                stroke="#9ca3af"
              />
              <YAxis
                tick={{ fontSize: 12 }}
                stroke="#9ca3af"
                tickFormatter={(v) => `${(v / 1000).toFixed(0)}k`}
              />
              <Tooltip content={<CustomTooltip />} />
              <ReferenceLine
                y={targetMonthly}
                stroke="#ef4444"
                strokeDasharray="5 5"
                label={{
                  value: UI_STRINGS.chart.targetLine,
                  position: "right",
                  fill: "#ef4444",
                  fontSize: 12,
                }}
              />
              <Bar
                dataKey="spend"
                name={UI_STRINGS.chart.spend}
                fill="#3b82f6"
                radius={[4, 4, 0, 0]}
              />
            </BarChart>
          </ResponsiveContainer>
        </div>
      </CardContent>
    </Card>
  );
}
