/**
 * Mirrors App\Enums\UserRole and App\Enums\UserStatus. Keeping the values in
 * one place here is the frontend equivalent of the backend enums: no route or
 * component should compare against a bare "worker" / "admin" string.
 */

export const USER_ROLES = ["worker", "client", "admin"] as const;
export type UserRole = (typeof USER_ROLES)[number];

export const USER_STATUSES = ["active", "pending", "suspended", "blocked"] as const;
export type UserStatus = (typeof USER_STATUSES)[number];

export const ROLE_LABELS: Record<UserRole, string> = {
  worker: "Worker",
  client: "Client",
  admin: "Administrator",
};

export const STATUS_LABELS: Record<UserStatus, string> = {
  active: "Active",
  pending: "Pending",
  suspended: "Suspended",
  blocked: "Blocked",
};

/**
 * Role-scoped entry points from routes/api.php. Each one sits behind both the
 * matching role guard and the `verified` middleware.
 */
export const WORKSPACE_ENDPOINTS: Record<UserRole, string> = {
  worker: "/worker",
  client: "/client",
  admin: "/admin",
};

export type ClientProfile = {
  id: number;
  company_name: string;
  phone: string | null;
};

/** Matches the allow-list in App\Http\Resources\UserResource. */
export type User = {
  id: number;
  first_name: string;
  last_name: string;
  full_name: string;
  email: string;
  role: UserRole;
  status: UserStatus;
  country_code: string;
  preferred_language: string;
  email_verified: boolean;
  email_verified_at: string | null;
  last_login_at: string | null;
  created_at: string | null;
  /** Only present for clients: the resource omits the key via `whenLoaded`. */
  client_profile?: ClientProfile;
};

export type Workspace = {
  role: UserRole;
  status: UserStatus;
  email_verified: boolean;
};
