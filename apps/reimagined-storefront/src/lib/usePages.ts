/**
 * Storefront-side read hook for the Pages builder (Wave 1).
 *
 * The legacy Supabase reads are disabled; they return no page on failure and
 * never substitute demo data. Demo mode uses the local store.
 * The LabPreview badge says which one you are looking at.
 */
import { useEffect, useState } from "react";
import { getAdminApi } from "@/api";
import { isSupabaseMode } from "@/config/env";
import { getPagePreview, getPublishedPage } from "@/api/supabase";
import type { AdminPage } from "@/api/types";

export function usePagePreview(slug: string | undefined): { page: AdminPage | null; ready: boolean } {
  const [page, setPage] = useState<AdminPage | null>(null);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    let on = true;
    setReady(false);
    if (!slug) {
      setPage(null);
      setReady(true);
      return () => {
        on = false;
      };
    }
    const load = async () => {
      if (isSupabaseMode) {
        // Both legacy reads fail closed in this Furniture snapshot.
        const draft = await getPagePreview(slug).catch(() => undefined);
        return draft ?? (await getPublishedPage(slug));
      }
      return getAdminApi().getPage(slug);
    };
    load()
      .then((result) => {
        if (on) setPage(result ?? null);
      })
      .catch(() => {
        if (on) setPage(null); // demo seed absent / bagisto 501 — treated as "no page"
      })
      .finally(() => {
        if (on) setReady(true);
      });
    return () => {
      on = false;
    };
  }, [slug]);

  return { page, ready };
}
