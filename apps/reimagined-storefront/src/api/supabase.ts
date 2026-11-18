import type { AdminApi, AdminPage, AdminProject } from "@/api/types";
import { createDisabledSupabaseAdapter, UnsupportedSupabaseAdapterError } from "@/api/disabled-supabase.mjs";

// Compatibility surface only: every operation fails closed. The archived
// reference adapter is not a Furniture backend, and no demo data is substituted.
// The proxy returns rejected promises, so fail compilation if the API ever gains
// a synchronous property or method instead of silently claiming compatibility.
type NonAsyncKeys = { [K in keyof AdminApi]: AdminApi[K] extends (...args: never[]) => Promise<unknown> ? never : K }[keyof AdminApi];
type MustBeNever<T extends never> = T;
type AllAdminOperationsAreAsync = MustBeNever<NonAsyncKeys>;
export const supabaseAdminApi = createDisabledSupabaseAdapter();

export async function listPublishedProjects(): Promise<AdminProject[]> {
  throw new UnsupportedSupabaseAdapterError("listPublishedProjects");
}

export async function getPublishedPage(_slug: string): Promise<AdminPage | undefined> {
  throw new UnsupportedSupabaseAdapterError("getPublishedPage");
}

export async function getPagePreview(_slug: string): Promise<AdminPage | undefined> {
  throw new UnsupportedSupabaseAdapterError("getPagePreview");
}
