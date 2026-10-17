"use server";

import { headers } from "next/headers";
import { redirect } from "next/navigation";

import { api, apiData, type Envelope } from "@/lib/api";

import { EMPTY_FORM_STATE, toFormState, type FormState } from "./form-state";
import { DASHBOARD_PATH, loginPathWithNotice } from "./routes";
import { clearSessionToken, readSessionToken, storeSessionToken } from "./session";
import type { User } from "./types";

export async function loginAction(_state: FormState, formData: FormData): Promise<FormState> {
  const email = String(formData.get("email") ?? "").trim();
  const password = String(formData.get("password") ?? "");

  try {
    const { token } = await apiData<{ user: User; token: string }>("/auth/login", {
      method: "POST",
      cache: "no-store",
      body: JSON.stringify({ email, password, device_name: await deviceName() }),
    });

    await storeSessionToken(token);
  } catch (error) {
    return toFormState(error);
  }

  // Outside the catch: redirect() signals by throwing, so it must not be caught.
  redirect(DASHBOARD_PATH);
}

export async function logoutAction(): Promise<void> {
  const token = await readSessionToken();

  if (token) {
    try {
      await api("/auth/logout", { method: "POST", token, cache: "no-store" });
    } catch {
      // Already revoked or unreachable. Dropping the cookie is what matters,
      // and a failure here must not strand the user in a signed-in shell.
    }
  }

  await clearSessionToken();

  redirect(loginPathWithNotice("signed-out"));
}

export async function resendVerificationAction(
  _state: FormState,
  _formData: FormData,
): Promise<FormState> {
  const token = await readSessionToken();

  if (!token) {
    return { ...EMPTY_FORM_STATE, message: "Your session has ended. Please sign in again." };
  }

  try {
    const { message } = await api<Envelope<unknown>>("/auth/email/verification-notification", {
      method: "POST",
      token,
      cache: "no-store",
    });

    return { message, tone: "success", errors: {} };
  } catch (error) {
    return toFormState(error);
  }
}

/**
 * Labels the Sanctum token so sessions are recognisable when they are later
 * listed or revoked. Capped to the `max:100` rule on LoginRequest.
 */
async function deviceName(): Promise<string> {
  const userAgent = (await headers()).get("user-agent");

  return (userAgent?.trim() || "web").slice(0, 100);
}
