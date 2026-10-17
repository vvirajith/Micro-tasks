export const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000";

/** Shape that App\Support\ApiResponse puts around every successful payload. */
export type Envelope<T> = {
  success: boolean;
  message: string;
  data: T;
};

/** Laravel's 422 body: one array of messages per rejected field. */
export type FieldErrors = Record<string, string[]>;

export class ApiError extends Error {
  constructor(
    message: string,
    readonly status: number,
    readonly errors: FieldErrors = {},
    readonly body: unknown = null,
  ) {
    super(message);
    this.name = "ApiError";
  }
}

export type ApiRequest = RequestInit & {
  /** Sanctum personal access token sent as a bearer credential. */
  token?: string | null;
};

/**
 * Calls the Laravel API. `path` is relative to /api, e.g. "/health".
 *
 * The backend issues Sanctum bearer tokens rather than using the cookie based
 * SPA guard, so credentials are never sent from the browser: the token is held
 * in an httpOnly cookie and attached here, on the server.
 */
export async function api<T>(path: string, { token, ...init }: ApiRequest = {}): Promise<T> {
  const response = await fetch(`${API_URL}/api${path}`, {
    ...init,
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...init.headers,
    },
  });

  const payload = response.status === 204 ? null : await response.json().catch(() => null);

  if (!response.ok) {
    throw new ApiError(
      messageFrom(payload, response.status),
      response.status,
      errorsFrom(payload),
      payload,
    );
  }

  return payload as T;
}

/** As `api`, but unwraps the envelope so callers only see the `data` key. */
export async function apiData<T>(path: string, init: ApiRequest = {}): Promise<T> {
  const { data } = await api<Envelope<T>>(path, init);

  return data;
}

function messageFrom(payload: unknown, status: number): string {
  if (payload && typeof payload === "object" && "message" in payload) {
    const { message } = payload as { message?: unknown };

    if (typeof message === "string" && message.trim() !== "") {
      return message;
    }
  }

  return `The server responded with status ${status}.`;
}

function errorsFrom(payload: unknown): FieldErrors {
  if (payload && typeof payload === "object" && "errors" in payload) {
    const { errors } = payload as { errors?: unknown };

    if (errors && typeof errors === "object") {
      return errors as FieldErrors;
    }
  }

  return {};
}
