import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { UI_STRINGS } from "@/lib/constants";
import { formatNumber, formatPercent } from "@/lib/format";
import type { PerspectiveFunnelKPIs } from "@/types/perspective";
import { Funnel } from "lucide-react";

interface PerspectiveSectionProps {
  funnels: PerspectiveFunnelKPIs[];
}

export function PerspectiveSection({ funnels }: PerspectiveSectionProps) {
  if (funnels.length === 0) return null;

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-lg flex items-center gap-2">
          <Funnel className="h-5 w-5" />
          {UI_STRINGS.perspective.title}
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="text-left py-3 px-2 font-medium text-gray-500">
                  {UI_STRINGS.perspective.funnel}
                </th>
                <th className="text-right py-3 px-2 font-medium text-gray-500">
                  {UI_STRINGS.perspective.visitors}
                </th>
                <th className="text-right py-3 px-2 font-medium text-gray-500">
                  {UI_STRINGS.perspective.completions}
                </th>
                <th className="text-right py-3 px-2 font-medium text-gray-500">
                  {UI_STRINGS.perspective.conversionRate}
                </th>
                <th className="text-right py-3 px-2 font-medium text-gray-500">
                  {UI_STRINGS.perspective.bounceRate}
                </th>
              </tr>
            </thead>
            <tbody>
              {funnels.map((funnel) => (
                <tr
                  key={funnel.funnelId}
                  className="border-b border-gray-100 hover:bg-gray-50"
                >
                  <td className="py-3 px-2 font-medium text-gray-900">
                    {funnel.funnelName}
                  </td>
                  <td className="text-right py-3 px-2 text-gray-700">
                    {formatNumber(funnel.visitors)}
                  </td>
                  <td className="text-right py-3 px-2 text-gray-700">
                    {formatNumber(funnel.completions)}
                  </td>
                  <td className="text-right py-3 px-2 text-green-600 font-medium">
                    {formatPercent(funnel.conversionRate)}
                  </td>
                  <td className="text-right py-3 px-2 text-gray-700">
                    {formatPercent(funnel.bounceRate)}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>
  );
}
