import { cookies } from "next/headers";

const COOKIE_NAME = "dashboard_auth";
const COOKIE_MAX_AGE = 60 * 60 * 24 * 30; // 30 days

function getSecret(): string {
  const secret = process.env.AUTH_SECRET;
  if (!secret) throw new Error("AUTH_SECRET not configured");
  return secret;
}

function getPassword(): string {
  const password = process.env.DASHBOARD_PASSWORD;
  if (!password) throw new Error("DASHBOARD_PASSWORD not configured");
  return password;
}

async function hashToken(value: string): Promise<string> {
  const encoder = new TextEncoder();
  const data = encoder.encode(value + getSecret());
  const hashBuffer = await crypto.subtle.digest("SHA-256", data);
  const hashArray = Array.from(new Uint8Array(hashBuffer));
  return hashArray.map((b) => b.toString(16).padStart(2, "0")).join("");
}

export async function verifyPassword(password: string): Promise<boolean> {
  return password === getPassword();
}

export async function createSession(): Promise<void> {
  const token = await hashToken(getPassword());
  const cookieStore = await cookies();
  cookieStore.set(COOKIE_NAME, token, {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    maxAge: COOKIE_MAX_AGE,
    path: "/",
  });
}

export async function isAuthenticated(): Promise<boolean> {
  const cookieStore = await cookies();
  const token = cookieStore.get(COOKIE_NAME)?.value;
  if (!token) return false;
  const expected = await hashToken(getPassword());
  return token === expected;
}

export async function clearSession(): Promise<void> {
  const cookieStore = await cookies();
  cookieStore.delete(COOKIE_NAME);
}
