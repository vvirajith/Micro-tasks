"use client";

import { useActionState } from "react";

import { resendVerificationAction } from "@/lib/auth/actions";
import { EMPTY_FORM_STATE } from "@/lib/auth/form-state";

export function VerificationNotice({ email }: { email: string }) {
  const [state, formAction, isPending] = useActionState(
    resendVerificationAction,
    EMPTY_FORM_STATE,
  );

  return (
    <section className="card">
      <h2>Verify your email</h2>
      <p className="card-body">
        We sent a verification link to <strong>{email}</strong>. The worker, client and admin areas
        stay locked until it is confirmed.
      </p>

      {state.message ? (
        <p className={`alert alert-${state.tone}`} role="status">
          {state.message}
        </p>
      ) : null}

      <form action={formAction}>
        <button type="submit" className="button button-quiet" disabled={isPending}>
          {isPending ? "Sending…" : "Resend verification email"}
        </button>
      </form>
    </section>
  );
}
