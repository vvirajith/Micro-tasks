import { NextResponse, type NextRequest } from "next/server";

import { SESSION_COOKIE } from "@/lib/auth/cookie";
import { DASHBOARD_PATH, LOGIN_PATH, loginPathWithNotice } from "@/lib/auth/routes";

/**
 * A presence check, nothing more. Whether the token is still valid is decided
 * by the API inside the pages themselves; asking Laravel from here would add a
 * round trip to every matched request.
 */
export function proxy(request: NextRequest) {
  const { pathname, searchParams } = request.nextUrl;
  const hasSession = request.cookies.has(SESSION_COOKIE);

  if (!hasSession && pathname.startsWith(DASHBOARD_PATH)) {
    return NextResponse.redirect(new URL(loginPathWithNotice("expired"), request.url));
  }

  // The page bounced the user back here because the API rejected the token, so
  // the dead cookie is dropped instead of being left to trigger the same
  // round trip on the next visit.
  if (hasSession && pathname === LOGIN_PATH && isRejectionNotice(searchParams.get("notice"))) {
    request.cookies.delete(SESSION_COOKIE);

    const response = NextResponse.next({ request });
    response.cookies.delete(SESSION_COOKIE);

    return response;
  }

  return NextResponse.next();
}

function isRejectionNotice(notice: string | null): boolean {
  return notice === "expired" || notice === "unavailable";
}

export const config = {
  matcher: ["/dashboard/:path*", "/login"],
};
