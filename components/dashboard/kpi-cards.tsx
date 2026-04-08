import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { formatCurrency } from "@/lib/format";
import { UI_STRINGS } from "@/lib/constants";
import type { BudgetMetrics } from "@/types/meta";
import {
  TrendingUp,
  TrendingDown,
  Minus,
  ArrowUpRight,
  ArrowDownRight,
} from "lucide-react";

interface KpiCardsProps {
  metrics: BudgetMetrics;
  fiscalStart: string;
  fiscalEnd: string;
}

function formatGermanDate(dateStr: string): string {
  const [year, month, day] = dateStr.split("-");
  return `${day}.${month}.${year}`;
}

export function KpiCards({ metrics, fiscalStart, fiscalEnd }: KpiCardsProps) {
  const pacePercent = Math.round(metrics.paceRatio * 100);

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
      {/* Ausgegeben */}
      <Card className="relative overflow-hidden">
        <div className="absolute top-0 left-0 right-0 h-1 bg-[#004071]" />
        <CardContent className="p-6">
          <p className="text-xs font-bold uppercase tracking-widest text-[#64748b] mb-3">
            {UI_STRINGS.kpi.spent}
          </p>
          <p className="text-3xl font-extrabold text-[#004071] tracking-tight">
            {formatCurrency(metrics.spent)}
          </p>
          <p className="text-xs text-[#94a3b8] mt-2 font-medium">
            {UI_STRINGS.kpi.spentSince} {formatGermanDate(fiscalStart)}
          </p>
        </CardContent>
      </Card>

      {/* Verbleibend */}
      <Card className="relative overflow-hidden">
        <div className="absolute top-0 left-0 right-0 h-1 bg-[#005e9e]" />
        <CardContent className="p-6">
          <p className="text-xs font-bold uppercase tracking-widest text-[#64748b] mb-3">
            {UI_STRINGS.kpi.remaining}
          </p>
          <p className="text-3xl font-extrabold text-[#004071] tracking-tight">
            {formatCurrency(metrics.remaining)}
          </p>
          <p className="text-xs text-[#94a3b8] mt-2 font-medium">
            {UI_STRINGS.kpi.remainingUntil} {formatGermanDate(fiscalEnd)}
          </p>
        </CardContent>
      </Card>

      {/* Durchschnitt / Monat */}
      <Card className="relative overflow-hidden">
        <div className="absolute top-0 left-0 right-0 h-1 bg-[#c7eafb]" />
        <CardContent className="p-6">
          <p className="text-xs font-bold uppercase tracking-widest text-[#64748b] mb-3">
            {UI_STRINGS.kpi.avgMonthly}
          </p>
          <p className="text-3xl font-extrabold text-[#004071] tracking-tight">
            {formatCurrency(metrics.averageMonthly)}
          </p>
          <p className="text-xs text-[#94a3b8] mt-2 font-medium">
            {UI_STRINGS.kpi.projection}: {formatCurrency(metrics.projectedAnnual)}
          </p>
        </CardContent>
      </Card>

      {/* Budget-Status */}
      <Card
        className={`relative overflow-hidden ${
          metrics.status === "on-track"
            ? "ring-1 ring-[#059669]/20"
            : metrics.status === "over"
              ? "ring-1 ring-[#dc2626]/20"
              : "ring-1 ring-[#d97706]/20"
        }`}
      >
        <div
          className={`absolute top-0 left-0 right-0 h-1 ${
            metrics.status === "on-track"
              ? "bg-[#059669]"
              : metrics.status === "over"
                ? "bg-[#dc2626]"
                : "bg-[#d97706]"
          }`}
        />
        <CardContent className="p-6">
          <p className="text-xs font-bold uppercase tracking-widest text-[#64748b] mb-3">
            Budget-Status
          </p>
          <div className="flex items-center gap-3">
            <div
              className={`flex items-center justify-center w-10 h-10 rounded-xl ${
                metrics.status === "on-track"
                  ? "bg-[#ecfdf5]"
                  : metrics.status === "over"
                    ? "bg-[#fef2f2]"
                    : "bg-[#fffbeb]"
              }`}
            >
              {metrics.status === "over" ? (
                <ArrowUpRight className="h-5 w-5 text-[#dc2626]" />
              ) : metrics.status === "under" ? (
                <ArrowDownRight className="h-5 w-5 text-[#d97706]" />
              ) : (
                <Minus className="h-5 w-5 text-[#059669]" />
              )}
            </div>
            <div>
              <Badge
                variant={
                  metrics.status === "on-track"
                    ? "success"
                    : metrics.status === "over"
                      ? "danger"
                      : "warning"
                }
              >
                {metrics.statusLabel}
              </Badge>
              <p className="text-xs text-[#94a3b8] mt-1 font-medium">
                {pacePercent}% des Budgets
              </p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
