"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { RefreshCw } from "lucide-react";
import { UI_STRINGS } from "@/lib/constants";

export function RefreshButton() {
  const [loading, setLoading] = useState(false);
  const router = useRouter();

  async function handleRefresh() {
    setLoading(true);
    try {
      await fetch("/api/refresh", { method: "POST" });
      router.refresh();
    } catch (error) {
      console.error("Refresh failed:", error);
    } finally {
      setLoading(false);
    }
  }

  return (
    <button
      onClick={handleRefresh}
      disabled={loading}
      className="inline-flex items-center gap-2 rounded-xl bg-[#004071] px-4 py-2.5 text-xs font-bold text-white hover:bg-[#005e9e] focus:outline-none focus:ring-2 focus:ring-[#004071]/30 focus:ring-offset-2 disabled:opacity-50 transition-all tracking-wide uppercase"
    >
      <RefreshCw className={`h-3.5 w-3.5 ${loading ? "animate-spin" : ""}`} />
      {UI_STRINGS.refresh}
    </button>
  );
}
