import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { UI_STRINGS } from "@/lib/constants";
import { formatNumber, formatPercent } from "@/lib/format";
import type { PerspectiveFunnelKPIs } from "@/types/perspective";

interface PerspectiveSectionProps {
  funnels: PerspectiveFunnelKPIs[];
}

export function PerspectiveSection({ funnels }: PerspectiveSectionProps) {
  if (funnels.length === 0) return null;

  return (
    <Card>
      <CardHeader>
        <CardTitle>{UI_STRINGS.perspective.title}</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {funnels.map((funnel) => (
            <div
              key={funnel.funnelId}
              className="rounded-xl bg-[#f8fafc] border border-[rgba(0,64,113,0.06)] p-5 hover:bg-[#c7eafb]/10 transition-colors"
            >
              <p className="text-sm font-bold text-[#004071] mb-4 truncate">
                {funnel.funnelName}
              </p>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <p className="text-[10px] font-bold uppercase tracking-widest text-[#94a3b8] mb-1">
                    {UI_STRINGS.perspective.visitors}
                  </p>
                  <p className="text-lg font-extrabold text-[#1a1a2e] tabular-nums">
                    {formatNumber(funnel.visitors)}
                  </p>
                </div>
                <div>
                  <p className="text-[10px] font-bold uppercase tracking-widest text-[#94a3b8] mb-1">
                    {UI_STRINGS.perspective.completions}
                  </p>
                  <p className="text-lg font-extrabold text-[#1a1a2e] tabular-nums">
                    {formatNumber(funnel.completions)}
                  </p>
                </div>
                <div>
                  <p className="text-[10px] font-bold uppercase tracking-widest text-[#94a3b8] mb-1">
                    {UI_STRINGS.perspective.conversionRate}
                  </p>
                  <p className="text-lg font-extrabold text-[#059669] tabular-nums">
                    {formatPercent(funnel.conversionRate)}
                  </p>
                </div>
                <div>
                  <p className="text-[10px] font-bold uppercase tracking-widest text-[#94a3b8] mb-1">
                    {UI_STRINGS.perspective.bounceRate}
                  </p>
                  <p className="text-lg font-extrabold text-[#1a1a2e] tabular-nums">
                    {formatPercent(funnel.bounceRate)}
                  </p>
                </div>
              </div>
            </div>
          ))}
        </div>
      </CardContent>
    </Card>
  );
}
