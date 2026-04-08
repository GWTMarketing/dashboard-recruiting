import { NextRequest, NextResponse } from "next/server";
import { verifyPassword, createSession, clearSession } from "@/lib/auth";

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { password } = body;

    if (!password) {
      return NextResponse.json(
        { error: "Passwort erforderlich" },
        { status: 400 }
      );
    }

    const valid = await verifyPassword(password);
    if (!valid) {
      return NextResponse.json(
        { error: "Falsches Passwort" },
        { status: 401 }
      );
    }

    await createSession();
    return NextResponse.json({ success: true });
  } catch {
    return NextResponse.json(
      { error: "Anmeldefehler" },
      { status: 500 }
    );
  }
}

export async function DELETE() {
  await clearSession();
  return NextResponse.json({ success: true });
}
