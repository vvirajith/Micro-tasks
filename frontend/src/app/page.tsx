import Link from "next/link";

import { API_URL, api } from "@/lib/api";
import { LOGIN_PATH } from "@/lib/auth/routes";

type Health = {
  status: string;
  app: string;
  environment: string;
  laravel: string;
  php: string;
  time: string;
};

async function getHealth(): Promise<Health | null> {
  try {
    return await api<Health>("/health", { cache: "no-store" });
  } catch {
    return null;
  }
}

export default async function Home() {
  const health = await getHealth();

  return (
    <main>
      <h1>Micro Task</h1>
      <p>
        Next.js and Laravel share this repository root. This page is rendered by Next and the data
        below comes from <code>GET {API_URL}/api/health</code>.
      </p>

      <p>
        <Link className="button" href={LOGIN_PATH}>
          Sign in
        </Link>
      </p>

      <section className="card">
        <h2>Backend status</h2>
        {health ? (
          <dl>
            <div className="row">
              <dt>Status</dt>
              <dd className="online">{health.status}</dd>
            </div>
            <div className="row">
              <dt>Application</dt>
              <dd>{health.app}</dd>
            </div>
            <div className="row">
              <dt>Environment</dt>
              <dd>{health.environment}</dd>
            </div>
            <div className="row">
              <dt>Laravel</dt>
              <dd>{health.laravel}</dd>
            </div>
            <div className="row">
              <dt>PHP</dt>
              <dd>{health.php}</dd>
            </div>
            <div className="row">
              <dt>Server time</dt>
              <dd>{health.time}</dd>
            </div>
          </dl>
        ) : (
          <p className="offline">
            Could not reach the API. Start Laravel with <code>php artisan serve</code> and reload.
          </p>
        )}
      </section>
    </main>
  );
}
