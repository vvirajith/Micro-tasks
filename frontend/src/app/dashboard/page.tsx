import type { Metadata } from "next";
import { redirect } from "next/navigation";

import { VerificationNotice } from "@/components/auth/verification-notice";
import { apiData } from "@/lib/api";
import { logoutAction } from "@/lib/auth/actions";
import { loginPathWithNotice } from "@/lib/auth/routes";
import { readSessionToken, resolveSession } from "@/lib/auth/session";
import {
  ROLE_LABELS,
  STATUS_LABELS,
  WORKSPACE_ENDPOINTS,
  type UserRole,
  type Workspace,
} from "@/lib/auth/types";

export const metadata: Metadata = {
  title: "Dashboard · Micro Task",
};

export default async function DashboardPage() {
  const session = await resolveSession();

  if (session.status === "unreachable") {
    return (
      <main>
        <h1>Dashboard</h1>
        <p className="offline">
          Could not reach the API. Start Laravel with <code>php artisan serve</code> and reload.
        </p>
      </main>
    );
  }

  if (session.status !== "authenticated") {
    redirect(loginPathWithNotice(session.status === "unavailable" ? "unavailable" : "expired"));
  }

  const { user } = session;
  const workspace = user.email_verified ? await loadWorkspace(user.role) : null;

  return (
    <main>
      <header className="page-header">
        <div>
          <h1>{user.full_name}</h1>
          <p className="page-subtitle">
            <span className="badge">{ROLE_LABELS[user.role]}</span>
            <span className={`badge badge-${user.status}`}>{STATUS_LABELS[user.status]}</span>
          </p>
        </div>

        <form action={logoutAction}>
          <button type="submit" className="button button-quiet">
            Sign out
          </button>
        </form>
      </header>

      <section className="card">
        <h2>Account</h2>
        <dl>
          <Row label="Email" value={user.email} />
          <Row label="Country" value={user.country_code} />
          <Row label="Language" value={user.preferred_language} />
          <Row label="Member since" value={formatDateTime(user.created_at)} />
          <Row label="Last login" value={formatDateTime(user.last_login_at)} />
        </dl>
      </section>

      {user.client_profile ? (
        <section className="card">
          <h2>Company</h2>
          <dl>
            <Row label="Name" value={user.client_profile.company_name} />
            <Row label="Phone" value={user.client_profile.phone ?? "—"} />
          </dl>
        </section>
      ) : null}

      {user.email_verified ? (
        <section className="card">
          <h2>{ROLE_LABELS[user.role]} area</h2>
          {workspace ? (
            <p className="card-body">
              <span className="online">Reachable.</span> This is where the {user.role} modules will
              mount. The API confirms the role as <code>{workspace.role}</code>.
            </p>
          ) : (
            <p className="card-body offline">
              The {user.role} area rejected this token. Sign out and in again to refresh it.
            </p>
          )}
        </section>
      ) : (
        <VerificationNotice email={user.email} />
      )}
    </main>
  );
}

/** Calls the role-scoped entry point so the backend guards are exercised, not assumed. */
async function loadWorkspace(role: UserRole): Promise<Workspace | null> {
  const token = await readSessionToken();

  try {
    return await apiData<Workspace>(WORKSPACE_ENDPOINTS[role], { token, cache: "no-store" });
  } catch {
    return null;
  }
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <div className="row">
      <dt>{label}</dt>
      <dd>{value}</dd>
    </div>
  );
}

function formatDateTime(value: string | null): string {
  if (!value) {
    return "Never";
  }

  return new Intl.DateTimeFormat("en-GB", { dateStyle: "medium", timeStyle: "short" }).format(
    new Date(value),
  );
}
