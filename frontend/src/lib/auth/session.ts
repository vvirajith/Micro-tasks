import { cookies } from "next/headers";

import { ApiError, apiData } from "@/lib/api";

import { SESSION_COOKIE, SESSION_MAX_AGE } from "./cookie";
import type { User } from "./types";

/**
 * Importing `next/headers` keeps this module server-only: Next refuses to
 * bundle it into a client component, so the token cannot leak into the browser.
 */

export type Session =
  | { status: "authenticated"; user: User }
  | { status: "unauthenticated" }
  /** Logged in, but `account.active` now rejects the token: suspended or blocked. */
  | { status: "unavailable"; message: string }
  | { status: "unreachable" };

export async function storeSessionToken(token: string): Promise<void> {
  const store = await cookies();

  store.set(SESSION_COOKIE, token, {
    httpOnly: true,
    sameSite: "lax",
    secure: process.env.NODE_ENV === "production",
    path: "/",
    maxAge: SESSION_MAX_AGE,
  });
}

export async function readSessionToken(): Promise<string | null> {
  const store = await cookies();

  return store.get(SESSION_COOKIE)?.value ?? null;
}

export async function clearSessionToken(): Promise<void> {
  const store = await cookies();

  store.delete(SESSION_COOKIE);
}

/**
 * Resolves the signed-in user against the API. The cookie alone is not treated
 * as proof of a session: a token can be revoked, or the account suspended,
 * long before the cookie expires.
 */
export async function resolveSession(): Promise<Session> {
  const token = await readSessionToken();

  if (!token) {
    return { status: "unauthenticated" };
  }

  try {
    const { user } = await apiData<{ user: User }>("/auth/me", {
      token,
      cache: "no-store",
    });

    return { status: "authenticated", user };
  } catch (error) {
    if (!(error instanceof ApiError)) {
      return { status: "unreachable" };
    }

    if (error.status === 403) {
      return { status: "unavailable", message: error.message };
    }

    return { status: "unauthenticated" };
  }
}
