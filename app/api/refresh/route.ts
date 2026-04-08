import { NextResponse } from "next/server";
import { revalidateTag } from "next/cache";

export async function POST() {
  try {
    revalidateTag("meta-insights", "default");
    revalidateTag("perspective", "default");
    return NextResponse.json({
      success: true,
      message: "Cache aktualisiert",
      timestamp: new Date().toISOString(),
    });
  } catch (error) {
    console.error("Error refreshing cache:", error);
    return NextResponse.json(
      { error: "Fehler beim Aktualisieren" },
      { status: 500 }
    );
  }
}
