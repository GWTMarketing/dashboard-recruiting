import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { UI_STRINGS } from "@/lib/constants";
import { formatCurrency, formatNumber } from "@/lib/format";
import type { AdPerformance } from "@/types/meta";

interface CampaignsTableProps {
  ads: AdPerformance[];
}

export function CampaignsTable({ ads }: CampaignsTableProps) {
  if (ads.length === 0) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="text-lg">{UI_STRINGS.campaigns.title}</CardTitle>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-gray-500">{UI_STRINGS.noData}</p>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-lg">
          {UI_STRINGS.campaigns.title}
          <span className="ml-2 text-sm font-normal text-gray-400">
            ({ads.length})
          </span>
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="text-left py-3 px-2 font-medium text-gray-500">
                  {UI_STRINGS.campaigns.campaign}
                </th>
                <th className="text-left py-3 px-2 font-medium text-gray-500">
                  {UI_STRINGS.campaigns.ad}
                </th>
                <th className="text-right py-3 px-2 font-medium text-gray-500">
                  {UI_STRINGS.campaigns.reach}
                </th>
                <th className="text-right py-3 px-2 font-medium text-gray-500">
                  {UI_STRINGS.campaigns.impressions}
                </th>
                <th className="text-right py-3 px-2 font-medium text-gray-500">
                  {UI_STRINGS.campaigns.cpc}
                </th>
                <th className="text-right py-3 px-2 font-medium text-gray-500">
                  {UI_STRINGS.campaigns.costPerResult}
                </th>
                <th className="text-right py-3 px-2 font-medium text-gray-500">
                  {UI_STRINGS.campaigns.results}
                </th>
                <th className="text-right py-3 px-2 font-medium text-gray-500">
                  {UI_STRINGS.campaigns.spend}
                </th>
              </tr>
            </thead>
            <tbody>
              {ads.map((ad) => (
                <tr
                  key={ad.adId}
                  className="border-b border-gray-100 hover:bg-gray-50"
                >
                  <td className="py-3 px-2 text-gray-700 max-w-[200px] truncate">
                    {ad.campaignName}
                  </td>
                  <td className="py-3 px-2 text-gray-900 font-medium max-w-[200px] truncate">
                    {ad.adName}
                  </td>
                  <td className="text-right py-3 px-2 text-gray-700">
                    {formatNumber(ad.reach)}
                  </td>
                  <td className="text-right py-3 px-2 text-gray-700">
                    {formatNumber(ad.impressions)}
                  </td>
                  <td className="text-right py-3 px-2 text-gray-700">
                    {formatCurrency(ad.cpc)}
                  </td>
                  <td className="text-right py-3 px-2 text-gray-700">
                    {formatCurrency(ad.costPerResult)}
                  </td>
                  <td className="text-right py-3 px-2 text-gray-700">
                    {formatNumber(ad.results)}
                  </td>
                  <td className="text-right py-3 px-2 font-medium text-gray-900">
                    {formatCurrency(ad.spend)}
                  </td>
                </tr>
              ))}
            </tbody>
            <tfoot>
              <tr className="border-t-2 border-gray-300 font-semibold">
                <td className="py-3 px-2 text-gray-900" colSpan={2}>
                  Gesamt
                </td>
                <td className="text-right py-3 px-2 text-gray-900">
                  {formatNumber(ads.reduce((s, a) => s + a.reach, 0))}
                </td>
                <td className="text-right py-3 px-2 text-gray-900">
                  {formatNumber(ads.reduce((s, a) => s + a.impressions, 0))}
                </td>
                <td className="text-right py-3 px-2 text-gray-900">-</td>
                <td className="text-right py-3 px-2 text-gray-900">-</td>
                <td className="text-right py-3 px-2 text-gray-900">
                  {formatNumber(ads.reduce((s, a) => s + a.results, 0))}
                </td>
                <td className="text-right py-3 px-2 text-gray-900">
                  {formatCurrency(ads.reduce((s, a) => s + a.spend, 0))}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </CardContent>
    </Card>
  );
}
