/**
 * Shared by the server-only session helpers and by proxy.ts. It lives in its
 * own module because proxy.ts cannot import anything that pulls in
 * `next/headers`.
 */
export const SESSION_COOKIE = "micro_task_session";

/**
 * Seven days. Sanctum tokens do not expire yet (SANCTUM_TOKEN_EXPIRATION is
 * empty), so this cookie is currently the only thing bounding a session. Keep
 * the two in step once a backend expiry is configured.
 */
export const SESSION_MAX_AGE = 60 * 60 * 24 * 7;
