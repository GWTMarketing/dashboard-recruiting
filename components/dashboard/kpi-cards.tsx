import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { formatCurrency } from "@/lib/format";
import { UI_STRINGS } from "@/lib/constants";
import type { BudgetMetrics } from "@/types/meta";
import {
  TrendingUp,
  TrendingDown,
  Minus,
  Wallet,
  PiggyBank,
  Calculator,
  Target,
} from "lucide-react";

interface KpiCardsProps {
  metrics: BudgetMetrics;
  fiscalStart: string;
  fiscalEnd: string;
}

function statusColor(status: BudgetMetrics["status"]) {
  switch (status) {
    case "on-track":
      return "border-l-green-500 bg-green-50/50";
    case "over":
      return "border-l-red-500 bg-red-50/50";
    case "under":
      return "border-l-amber-500 bg-amber-50/50";
  }
}

function statusBadgeVariant(status: BudgetMetrics["status"]) {
  switch (status) {
    case "on-track":
      return "success" as const;
    case "over":
      return "danger" as const;
    case "under":
      return "warning" as const;
  }
}

function formatGermanDate(dateStr: string): string {
  const [year, month, day] = dateStr.split("-");
  return `${day}.${month}.${year}`;
}

export function KpiCards({ metrics, fiscalStart, fiscalEnd }: KpiCardsProps) {
  const PaceIcon =
    metrics.status === "over"
      ? TrendingUp
      : metrics.status === "under"
        ? TrendingDown
        : Minus;

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      {/* Ausgegeben */}
      <Card className="border-l-4 border-l-blue-500">
        <CardContent className="p-5">
          <div className="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <Wallet className="h-4 w-4" />
            {UI_STRINGS.kpi.spent}
          </div>
          <div className="text-2xl font-bold text-gray-900">
            {formatCurrency(metrics.spent)}
          </div>
          <div className="text-xs text-gray-400 mt-1">
            {UI_STRINGS.kpi.spentSince} {formatGermanDate(fiscalStart)}
          </div>
        </CardContent>
      </Card>

      {/* Verbleibend */}
      <Card className="border-l-4 border-l-emerald-500">
        <CardContent className="p-5">
          <div className="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <PiggyBank className="h-4 w-4" />
            {UI_STRINGS.kpi.remaining}
          </div>
          <div className="text-2xl font-bold text-gray-900">
            {formatCurrency(metrics.remaining)}
          </div>
          <div className="text-xs text-gray-400 mt-1">
            {UI_STRINGS.kpi.remainingUntil} {formatGermanDate(fiscalEnd)}
          </div>
        </CardContent>
      </Card>

      {/* Durchschnitt / Monat */}
      <Card className="border-l-4 border-l-violet-500">
        <CardContent className="p-5">
          <div className="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <Calculator className="h-4 w-4" />
            {UI_STRINGS.kpi.avgMonthly}
          </div>
          <div className="text-2xl font-bold text-gray-900">
            {formatCurrency(metrics.averageMonthly)}
          </div>
          <div className="text-xs text-gray-400 mt-1">
            {UI_STRINGS.kpi.projection}: {formatCurrency(metrics.projectedAnnual)}
          </div>
        </CardContent>
      </Card>

      {/* Prognose / Pace */}
      <Card className={`border-l-4 ${statusColor(metrics.status)}`}>
        <CardContent className="p-5">
          <div className="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <Target className="h-4 w-4" />
            Budget-Status
          </div>
          <div className="flex items-center gap-2">
            <PaceIcon className="h-6 w-6" />
            <Badge variant={statusBadgeVariant(metrics.status)}>
              {metrics.statusLabel}
            </Badge>
          </div>
          <div className="text-xs text-gray-400 mt-2">
            {Math.round(metrics.paceRatio * 100)}% des geplanten Budgets
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
