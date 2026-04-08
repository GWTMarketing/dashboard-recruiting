"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { UI_STRINGS } from "@/lib/constants";

export function LoginForm() {
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const router = useRouter();

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError("");
    setLoading(true);

    try {
      const res = await fetch("/api/auth", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ password }),
      });

      if (res.ok) {
        router.push("/");
        router.refresh();
      } else {
        const data = await res.json();
        setError(data.error || UI_STRINGS.login.error);
      }
    } catch {
      setError("Verbindungsfehler");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-[#f8fafc] p-4">
      <div className="w-full max-w-sm">
        {/* Logo / Title */}
        <div className="text-center mb-10">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[#004071] mb-6 shadow-lg shadow-[#004071]/20">
            <span className="text-xl font-black text-white tracking-tight">
              GWT
            </span>
          </div>
          <h1 className="text-2xl font-extrabold text-[#004071] tracking-tight">
            {UI_STRINGS.title}
          </h1>
          <p className="text-sm text-[#94a3b8] mt-1 font-medium">
            {UI_STRINGS.subtitle}
          </p>
        </div>

        {/* Form */}
        <div className="rounded-2xl bg-white border border-[rgba(0,64,113,0.08)] shadow-[0_1px_3px_rgba(0,64,113,0.04),0_8px_24px_rgba(0,64,113,0.06)] p-8">
          <form onSubmit={handleSubmit} className="space-y-5">
            <div>
              <label
                htmlFor="password"
                className="block text-[10px] font-bold uppercase tracking-widest text-[#64748b] mb-2"
              >
                {UI_STRINGS.login.password}
              </label>
              <input
                id="password"
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full rounded-xl border border-[rgba(0,64,113,0.12)] bg-[#f8fafc] px-4 py-3 text-sm font-medium text-[#1a1a2e] focus:outline-none focus:ring-2 focus:ring-[#004071]/20 focus:border-[#004071]/30 focus:bg-white transition-all placeholder:text-[#94a3b8]"
                required
                autoFocus
              />
            </div>
            {error && (
              <div className="rounded-xl bg-[#fef2f2] px-4 py-2.5 text-xs font-bold text-[#dc2626]">
                {error}
              </div>
            )}
            <button
              type="submit"
              disabled={loading}
              className="w-full rounded-xl bg-[#004071] px-4 py-3 text-sm font-bold text-white hover:bg-[#005e9e] focus:outline-none focus:ring-2 focus:ring-[#004071]/30 focus:ring-offset-2 disabled:opacity-50 transition-all tracking-wide"
            >
              {loading ? UI_STRINGS.loading : UI_STRINGS.login.submit}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
