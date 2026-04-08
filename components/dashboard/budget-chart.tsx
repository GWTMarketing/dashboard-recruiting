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
    <div className="rounded-xl bg-[#004071] px-4 py-3 shadow-xl">
      <p className="text-xs font-bold text-[#c7eafb] uppercase tracking-wider">
        {label}
      </p>
      <p className="text-base font-extrabold text-white mt-0.5">
        {formatCurrency(payload[0].value)}
      </p>
    </div>
  );
}

export function BudgetChart({ monthlyData, targetMonthly }: BudgetChartProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>{UI_STRINGS.chart.title}</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="h-[320px] w-full">
          <ResponsiveContainer width="100%" height="100%">
            <BarChart
              data={monthlyData}
              margin={{ top: 8, right: 8, left: 8, bottom: 4 }}
            >
              <CartesianGrid
                strokeDasharray="3 3"
                stroke="rgba(0,64,113,0.06)"
                vertical={false}
              />
              <XAxis
                dataKey="month"
                tick={{ fontSize: 11, fontWeight: 600, fill: "#94a3b8" }}
                stroke="transparent"
                tickLine={false}
              />
              <YAxis
                tick={{ fontSize: 11, fontWeight: 600, fill: "#94a3b8" }}
                stroke="transparent"
                tickLine={false}
                tickFormatter={(v) => `${(v / 1000).toFixed(0)}k`}
                width={40}
              />
              <Tooltip
                content={<CustomTooltip />}
                cursor={{ fill: "rgba(0,64,113,0.04)", radius: 8 }}
              />
              <ReferenceLine
                y={targetMonthly}
                stroke="#dc2626"
                strokeDasharray="6 4"
                strokeWidth={1.5}
                label={{
                  value: UI_STRINGS.chart.targetLine,
                  position: "right",
                  fill: "#dc2626",
                  fontSize: 11,
                  fontWeight: 600,
                }}
              />
              <Bar
                dataKey="spend"
                name={UI_STRINGS.chart.spend}
                fill="#004071"
                radius={[6, 6, 0, 0]}
                maxBarSize={48}
              />
            </BarChart>
          </ResponsiveContainer>
        </div>
      </CardContent>
    </Card>
  );
}
