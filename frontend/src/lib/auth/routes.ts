export const LOGIN_PATH = "/login";
export const DASHBOARD_PATH = "/dashboard";

/**
 * Why the user landed back on the login page. A code is passed through the URL
 * rather than the raw server message, so nothing arbitrary is echoed into the
 * address bar or rendered from a query string.
 */
export const SESSION_NOTICES = {
  expired: "Your session has ended. Please sign in again.",
  unavailable: "Your account is no longer available. Please contact support.",
  "signed-out": "You have been signed out.",
} as const;

export type SessionNotice = keyof typeof SESSION_NOTICES;

export function isSessionNotice(value: unknown): value is SessionNotice {
  return typeof value === "string" && value in SESSION_NOTICES;
}

export function loginPathWithNotice(notice: SessionNotice): string {
  return `${LOGIN_PATH}?notice=${notice}`;
}
