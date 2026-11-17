import { useEffect, useState } from "react";
import { services } from "@/data/services";
import { parseServiceCopy } from "@/lib/service-copy-parser.mjs";

export type ServiceCopy = Record<string, { title: string; tagline: string }>;
const slugs = services.map((service) => service.slug);

export function useServiceCopy(): ServiceCopy {
  const [copy, setCopy] = useState<ServiceCopy>({});
  useEffect(() => {
    let active = true;
    let loading = false;
    const load = async () => {
      if (document.hidden || loading) return;
      loading = true;
      try {
        const response = await fetch('/storefront-api/services/copy', { cache: 'no-store' });
        if (!response.ok) return;
        const parsed = parseServiceCopy(await response.json(), slugs) as ServiceCopy | null;
        if (active && parsed) setCopy((previous) => JSON.stringify(previous) === JSON.stringify(parsed) ? previous : parsed);
      } catch {
        // Keep the last good copy, or the bundled preview text if none was loaded.
      } finally { loading = false; }
    };
    void load();
    const timer = window.setInterval(load, 2500);
    const onVisible = () => { if (!document.hidden) void load(); };
    document.addEventListener('visibilitychange', onVisible);
    return () => { active = false; window.clearInterval(timer); document.removeEventListener('visibilitychange', onVisible); };
  }, []);
  return copy;
}
