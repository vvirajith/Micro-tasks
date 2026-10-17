"use client";

import { useActionState, useState } from "react";

import { loginAction } from "@/lib/auth/actions";
import { EMPTY_FORM_STATE } from "@/lib/auth/form-state";

export function LoginForm() {
  const [state, formAction, isPending] = useActionState(loginAction, EMPTY_FORM_STATE);

  // Controlled so a rejected attempt keeps the address: React resets
  // uncontrolled fields once an action settles. The password is left
  // uncontrolled on purpose, so it clears.
  const [email, setEmail] = useState("");
  const [revealPassword, setRevealPassword] = useState(false);

  const emailError = state.errors.email?.[0];
  const passwordError = state.errors.password?.[0];

  // A 422 names the offending fields; anything else (bad credentials, a
  // suspended account, a rate limit) only carries the summary message.
  const banner = Object.keys(state.errors).length > 0 ? null : state.message;

  return (
    <form action={formAction} className="auth-form">
      {banner ? (
        <p className={`alert alert-${state.tone}`} role="alert">
          {banner}
        </p>
      ) : null}

      <div className="field">
        <label htmlFor="email">Email address</label>
        <input
          id="email"
          name="email"
          type="email"
          autoComplete="email"
          autoFocus
          required
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          aria-invalid={emailError ? true : undefined}
          aria-describedby={emailError ? "email-error" : undefined}
        />
        {emailError ? (
          <p className="field-error" id="email-error">
            {emailError}
          </p>
        ) : null}
      </div>

      <div className="field">
        <label htmlFor="password">Password</label>
        <div className="input-group">
          <input
            id="password"
            name="password"
            type={revealPassword ? "text" : "password"}
            autoComplete="current-password"
            required
            aria-invalid={passwordError ? true : undefined}
            aria-describedby={passwordError ? "password-error" : undefined}
          />
          <button
            type="button"
            className="reveal"
            onClick={() => setRevealPassword((shown) => !shown)}
            aria-pressed={revealPassword}
          >
            {revealPassword ? "Hide" : "Show"}
          </button>
        </div>
        {passwordError ? (
          <p className="field-error" id="password-error">
            {passwordError}
          </p>
        ) : null}
      </div>

      <button type="submit" className="button" disabled={isPending}>
        {isPending ? "Signing in…" : "Sign in"}
      </button>
    </form>
  );
}
