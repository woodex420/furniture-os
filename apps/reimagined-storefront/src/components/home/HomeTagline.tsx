import { useEffect, useState } from "react";

const defaultTagline = "Unmatched Capabilities. Fresh Products that Matter. Trusted Partnership.";

/** The actual Reimagin section, with an optional temporary owner-canvas overlay. */
const HomeTagline = () => {
  const [saved, setSaved] = useState<string | null>(null);
  const [draft, setDraft] = useState<string | null>(null);
  const [canvasParent, setCanvasParent] = useState<string | null>(null);

  useEffect(() => {
    let active = true;
    let loading = false;
    const load = async () => {
      if (document.hidden || loading) return;
      loading = true;
      try {
        const response = await fetch('/storefront-api/home', { cache: 'no-store' });
        if (!response.ok) return;
        const data: unknown = await response.json();
        if (active && data && typeof data === 'object' && 'home_tagline' in data &&
            (data.home_tagline === null || (typeof data.home_tagline === 'string' && data.home_tagline.length <= 1024 && Array.from(data.home_tagline).length <= 240))) {
          setSaved(typeof data.home_tagline === 'string' ? data.home_tagline : null);
        }
      } catch {
        // The original Reimagin copy remains when the optional backend is offline.
      } finally { loading = false; }
    };
    void load();
    const timer = window.setInterval(load, 2500);
    const onVisible = () => { if (!document.hidden) void load(); };
    document.addEventListener('visibilitychange', onVisible);
    return () => { active = false; window.clearInterval(timer); document.removeEventListener('visibilitychange', onVisible); };
  }, []);

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('furniture-canvas') !== 'home' || window.parent === window) return;
    const candidate = params.get('parent-origin');
    if (!candidate) return;
    let origin: string;
    try {
      const parsed = new URL(candidate);
      if ((parsed.protocol !== 'http:' && parsed.protocol !== 'https:') || parsed.origin !== candidate) return;
      origin = parsed.origin;
    } catch { return; }
    setCanvasParent(origin);
    const onMessage = (event: MessageEvent) => {
      if (event.source !== window.parent || event.origin !== origin || !event.data || typeof event.data !== 'object') return;
      if (event.data.type === 'furniture-home-canvas-ping') {
        window.parent.postMessage({ type: 'furniture-home-tagline-ready' }, origin);
      } else if (event.data.type === 'furniture-home-canvas-values' && typeof event.data.home_tagline === 'string' && event.data.home_tagline.length <= 1024) {
        setDraft(event.data.home_tagline);
      }
    };
    window.addEventListener('message', onMessage);
    window.parent.postMessage({ type: 'furniture-home-tagline-ready' }, origin);
    return () => window.removeEventListener('message', onMessage);
  }, []);

  const select = () => {
    if (canvasParent) window.parent.postMessage({ type: 'furniture-home-canvas-select', field: 'home_tagline', focus_input: true }, canvasParent);
  };

  return (
    <section className="relative py-10 bg-background border-b">
      {canvasParent && <button type="button" onClick={select} className="absolute right-2 top-1 rounded bg-primary px-2 py-1 text-xs text-primary-foreground" aria-label="Edit homepage tagline">Edit tagline</button>}
      <div className="container mx-auto px-4 text-center">
        <p data-canvas-field={canvasParent ? 'home_tagline' : undefined} onClick={select} className={`text-xl lg:text-2xl font-bold text-accent ${canvasParent ? 'cursor-pointer outline outline-1 outline-dashed outline-accent' : ''}`}>
          {draft ?? saved ?? defaultTagline}
        </p>
      </div>
    </section>
  );
};

export default HomeTagline;
