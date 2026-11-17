import { useEffect, useState, type ReactNode } from "react";
import { Link, useParams } from "react-router-dom";
import { ArrowRight, MapPin } from "lucide-react";
import Header from "@/components/Header";
import Footer from "@/components/Footer";
import { parseCityDetail, parseCityIndex, slugOk } from "@/lib/city-page-parser.mjs";

type CitySummary = { slug: string; city_name: string; title: string; summary: string };
type CityDetail = CitySummary & { body: string };
type CityList = { data: CitySummary[]; current_page: number; last_page: number };
type Result<T> = { key: string; status: "loading" | "ready" | "missing" | "unavailable"; data: T | null };

function useCityData<T>(kind: "index" | "detail", value: string): Result<T> {
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
        const url = kind === "index" ? `/storefront-api/cities?page=${encodeURIComponent(value)}` : `/storefront-api/cities/${encodeURIComponent(value)}`;
        const response = await fetch(url, { cache: "no-store", signal: requestController.signal });
        if (response.status === 404 && kind === "detail") { update("missing"); return; }
        if (!response.ok) { update("unavailable"); return; }
        const raw: unknown = await response.json();
        const parsed = kind === "index" ? parseCityIndex(raw, Number(value)) : parseCityDetail(raw, value);
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

const Layout = ({ children }: { children: ReactNode }) => (
  <div className="min-h-screen flex flex-col"><Header /><main className="flex-1">{children}</main><Footer /></div>
);

export function CityIndex() {
  const [page, setPage] = useState(1);
  const result = useCityData<CityList>("index", String(page));
  useEffect(() => { document.title = "Service areas | WOODEX"; }, []);
  return <Layout>
    <section className="bg-primary text-primary-foreground"><div className="container mx-auto px-4 py-16"><p className="text-xs uppercase tracking-widest font-bold text-accent mb-3">Furniture services</p><h1 className="text-4xl font-black">Service areas</h1><p className="mt-4 text-primary-foreground/80">Explore the locations covered by our published service-area pages.</p></div></section>
    <section className="container mx-auto px-4 py-16" aria-live="polite">
      {result.status === "loading" && <p role="status">Loading service areas…</p>}
      {result.status === "unavailable" && <p role="alert">Service areas are temporarily unavailable. Please try again later.</p>}
      {result.status === "ready" && result.data && <>
        {result.data.data.length === 0 ? <p>No service-area pages are available on this page.</p> : <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">{result.data.data.map((city) => <article key={city.slug} className="border border-border p-6"><MapPin className="text-accent mb-3" aria-hidden="true" /><p className="text-sm text-muted-foreground">{city.city_name}</p><h2 className="text-xl font-bold mt-2">{city.title}</h2><p className="text-muted-foreground mt-3">{city.summary}</p><Link className="inline-flex items-center gap-2 font-bold text-accent mt-5" to={`/locations/${city.slug}`}>Explore service area <ArrowRight className="h-4 w-4" /></Link></article>)}</div>}
        <nav aria-label="Service area pages" className="flex gap-4 items-center mt-8"><button type="button" disabled={page <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))} className="border px-3 py-2 disabled:opacity-40">Previous</button><span>Page {page} of {result.data.last_page}</span><button type="button" disabled={page >= result.data.last_page} onClick={() => setPage((p) => p + 1)} className="border px-3 py-2 disabled:opacity-40">Next</button></nav>
      </>}
    </section>
  </Layout>;
}

export function CityDetailPage() {
  const { slug = "" } = useParams<{ slug: string }>();
  const result = useCityData<CityDetail>("detail", slug);
  useEffect(() => { document.title = result.data ? `${result.data.title} | WOODEX` : "Service area | WOODEX"; }, [result.data]);
  useEffect(() => { window.scrollTo(0, 0); }, [slug]);
  return <Layout><section className="container mx-auto px-4 py-16" aria-live="polite">
    <Link to="/locations" className="text-accent font-bold">← All service areas</Link>
    {result.status === "loading" && <p role="status" className="mt-8">Loading service area…</p>}
    {result.status === "missing" && <h1 className="mt-8 text-3xl font-bold">Service area not found or unavailable</h1>}
    {result.status === "unavailable" && <p role="alert" className="mt-8">Service area is temporarily unavailable. Please try again later.</p>}
    {result.status === "ready" && result.data && <article className="max-w-3xl mt-10"><p className="text-accent uppercase tracking-widest text-xs font-bold">{result.data.city_name}</p><h1 className="text-4xl font-black mt-3">{result.data.title}</h1><p className="text-lg text-muted-foreground mt-5">{result.data.summary}</p><p className="mt-8 whitespace-pre-line leading-relaxed">{result.data.body}</p><Link to="/contact" className="inline-flex items-center gap-2 text-accent font-bold mt-8">Contact us <ArrowRight className="h-4 w-4" /></Link></article>}
  </section></Layout>;
}
