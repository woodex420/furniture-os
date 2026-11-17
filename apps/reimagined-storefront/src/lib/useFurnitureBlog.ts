import { useEffect, useState } from "react";
import { parseBlogDetail, parseBlogIndex, slugOk } from "@/lib/blog-preview-parser.mjs";

export type ArticleSummary = { slug: string; title: string; summary: string };
export type Article = ArticleSummary & { body: string };
export type ArticleList = { data: ArticleSummary[]; current_page: number; last_page: number };
type Result<T> = { key: string; status: "loading" | "ready" | "missing" | "unavailable"; data: T | null };

/** Only Furniture-published articles. Never fall back to legacy browser/demo content. */
export function useFurnitureBlog<T>(kind: "index" | "detail", value: string): Result<T> {
  const key = `${kind}:${value}`;
  const [state, setState] = useState<Result<T>>({ key, status: "loading", data: null });
  useEffect(() => {
    let active = true;
    let loading = false;
    let controller: AbortController | null = null;
    const update = (status: Result<T>["status"], data: T | null = null) => {
      if (active) setState((previous) => {
        const next = { key, status, data };
        return JSON.stringify(previous) === JSON.stringify(next) ? previous : next;
      });
    };
    if (kind === "detail" && !slugOk(value)) {
      update("missing");
      return () => { active = false; };
    }
    update("loading");
    const load = async () => {
      if (document.hidden || loading) return;
      loading = true;
      controller = new AbortController();
      const requestController = controller;
      const timeout = window.setTimeout(() => requestController.abort(), 8000);
      try {
        const url = kind === "index" ? `/storefront-api/blog?page=${encodeURIComponent(value)}` : `/storefront-api/blog/${encodeURIComponent(value)}`;
        const response = await fetch(url, { cache: "no-store", signal: requestController.signal });
        if (response.status === 404 && kind === "detail") { update("missing"); return; }
        if (!response.ok) { update("unavailable"); return; }
        const raw: unknown = await response.json();
        const parsed = kind === "index" ? parseBlogIndex(raw, Number(value)) : parseBlogDetail(raw, value);
        if (!parsed) { update("unavailable"); return; }
        update("ready", parsed as T);
      } catch {
        update("unavailable");
      } finally {
        window.clearTimeout(timeout);
        loading = false;
      }
    };
    void load();
    const timer = window.setInterval(load, 2500);
    const onVisible = () => { if (!document.hidden) void load(); };
    document.addEventListener("visibilitychange", onVisible);
    return () => { active = false; controller?.abort(); window.clearInterval(timer); document.removeEventListener("visibilitychange", onVisible); };
  }, [kind, value, key]);
  return state.key === key ? state : { key, status: "loading", data: null };
}
