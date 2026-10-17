import { ApiError, type FieldErrors } from "@/lib/api";

export type FormState = {
  message: string | null;
  tone: "error" | "success";
  /** Per-field messages from a 422, keyed exactly as the API names the fields. */
  errors: FieldErrors;
};

export const EMPTY_FORM_STATE: FormState = {
  message: null,
  tone: "error",
  errors: {},
};

const UNREACHABLE_MESSAGE = "Could not reach the server. Make sure the Laravel API is running.";

/**
 * Turns a thrown request into something renderable. The API already returns a
 * user-facing `message` for every failure it owns, so it is passed through
 * as-is; anything else means the request never got a response.
 */
export function toFormState(error: unknown): FormState {
  if (error instanceof ApiError) {
    return { message: error.message, tone: "error", errors: error.errors };
  }

  return { message: UNREACHABLE_MESSAGE, tone: "error", errors: {} };
}
