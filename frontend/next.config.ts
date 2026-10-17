import type { NextConfig } from "next";

/**
 * Next owns this directory outright, so nothing here has to work around
 * Laravel: `public/` is the PHP web root one level up and is no longer on
 * Next's static path, and `app/` up there is the PSR-4 source tree rather than
 * a competing App Router.
 */
const nextConfig: NextConfig = {};

export default nextConfig;
