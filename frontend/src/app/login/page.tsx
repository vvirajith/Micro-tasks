import type { Metadata } from "next";
import { redirect } from "next/navigation";

import { LoginForm } from "@/components/auth/login-form";
import { DASHBOARD_PATH, SESSION_NOTICES, isSessionNotice } from "@/lib/auth/routes";
import { resolveSession } from "@/lib/auth/session";

export const metadata: Metadata = {
  title: "Sign in · Micro Task",
};

type LoginPageProps = {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
};

export default async function LoginPage({ searchParams }: LoginPageProps) {
  // Only a genuinely valid token bounces away from here, so a stale cookie
  // cannot ping-pong between this page and the dashboard.
  const session = await resolveSession();

  if (session.status === "authenticated") {
    redirect(DASHBOARD_PATH);
  }

  const { notice } = await searchParams;
  const noticeMessage = isSessionNotice(notice) ? SESSION_NOTICES[notice] : null;

  return (
    <main className="auth-main">
      <section className="auth-card">
        <header className="auth-header">
          <h1>Sign in</h1>
          <p>Use your Micro Task account to continue.</p>
        </header>

        {noticeMessage ? (
          <p className="alert alert-notice" role="status">
            {noticeMessage}
          </p>
        ) : null}

        <LoginForm />
      </section>
    </main>
  );
}
