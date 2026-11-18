/**
 * WOODEX — data adapter factory.
 *
 *   import { getAdminApi } from "@/api";
 *   const api = getAdminApi();
 *
 * VITE_API_MODE=demo (default) → localStorage demo DB seeded from the catalogue.
 * VITE_API_MODE=bagisto        → live Bagisto REST API (see docs/07-admin-integration).
 */
import { config } from "@/config/env";
import { demoAdminApi } from "@/api/demo/db";
import { bagistoAdminApi } from "@/api/bagisto/admin";
import { supabaseAdminApi } from "@/api/supabase";
import type { AdminApi } from "@/api/types";
import { restrictStagingSubmissions } from "@/api/restrict-staging.mjs";
import { selectAdminAdapter } from "@/api/disabled-supabase.mjs";

export function getAdminApi(): AdminApi {
  const api = selectAdminAdapter(config.apiMode, { demo: demoAdminApi, bagisto: bagistoAdminApi, disabledSupabase: supabaseAdminApi });
  return restrictStagingSubmissions(api, config.restrictedStaging) as AdminApi;
}

export * from "@/api/types";
