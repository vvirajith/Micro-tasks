import { defineConfig, globalIgnores } from "eslint/config";
import coreWebVitals from "eslint-config-next/core-web-vitals";
import typescript from "eslint-config-next/typescript";

export default defineConfig([
  globalIgnores([".next/**", "out/**", "next-env.d.ts", "node_modules/**"]),
  ...coreWebVitals,
  ...typescript,
  {
    rules: {
      // React fixes the shape of a server action: the leading state and
      // trailing FormData parameters have to be declared even when a
      // particular action ignores them.
      "@typescript-eslint/no-unused-vars": [
        "warn",
        {
          argsIgnorePattern: "^_",
          varsIgnorePattern: "^_",
          caughtErrorsIgnorePattern: "^_",
        },
      ],
    },
  },
]);
