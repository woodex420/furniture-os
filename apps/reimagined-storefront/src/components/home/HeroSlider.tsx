import { useState, useEffect, useRef } from "react";
import { Button } from "@/components/ui/button";
import { ChevronLeft, ChevronRight, Pause, Play } from "lucide-react";
import slide1 from "@/assets/hero-slide-1.webp?url";
import slide2 from "@/assets/hero-slide-2.webp?url";
import slide3 from "@/assets/hero-slide-3.webp?url";
import slide4 from "@/assets/hero-slide-4.webp?url";
import slide5 from "@/assets/hero-slide-5.webp?url";

const slides = [
  { image: slide1, title: "Design For Your Workspace", subtitle: "From concept to installation — design, manufacture, and deliver", cta: "Shop Now", ctaHref: "/shop?category=workstations" },
  { image: slide2, title: "Meeting Rooms That Inspire", subtitle: "Conference furniture engineered for collaboration and focus", cta: "Shop Now", ctaHref: "/shop?category=meeting-tables" },
  { image: slide3, title: "Executive Offices, Crafted", subtitle: "Premium executive desks and seating with timeless presence", cta: "Shop Now", ctaHref: "/shop?category=executive-tables" },
  { image: slide4, title: "Reception That Welcomes", subtitle: "First impressions built with bespoke reception solutions", cta: "Shop Now", ctaHref: "/shop?category=reception" },
  { image: slide5, title: "Manager Cabins, Refined", subtitle: "Functional, elegant private offices for leadership teams", cta: "Shop Now", ctaHref: "/shop?category=executive-tables" },
];


const heroImages = { workspace: slide1, meeting: slide2, executive: slide3, reception: slide4, manager: slide5 } as const;
type HeroImage = keyof typeof heroImages;
const isHeroImage = (value: unknown): value is HeroImage => typeof value === "string" && Object.prototype.hasOwnProperty.call(heroImages, value);
const isMediaId = (value: unknown): value is string => typeof value === "string" && /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(value);

type HeroOrder = "title_first" | "subtitle_first";
const isHeroOrder = (value: unknown): value is HeroOrder => value === "title_first" || value === "subtitle_first";
// Mirrors the server's restricted root-relative path contract; never render unsafe hrefs.
const isInternalPath = (value: unknown): value is string => typeof value === "string" && value.length <= 240 && /^\/(?!\/)[A-Za-z0-9._~/?=&-]*(?![\s\S])/.test(value);
type HomeContent = { hero_title: string | null; hero_subtitle: string | null; hero_order: HeroOrder; hero_cta_label: string | null; hero_cta_path: string | null; hero_image: HeroImage; hero_media_id: string | null };
type CanvasContent = { hero_title: string; hero_subtitle: string; hero_order: HeroOrder | null; hero_cta_label: string | null; hero_cta_path: string | null; hero_image: HeroImage | null; hero_media_id: string | null | undefined };
type CanvasField = "hero_title" | "hero_subtitle" | "hero_cta_label" | "hero_image";

/** Shared by the current Reimagin homepage and the isolated Astro parity spike. */
const HeroSlider = () => {
  const [current, setCurrent] = useState(0);
  const [playing, setPlaying] = useState(true);
  const [homeContent, setHomeContent] = useState<HomeContent | null>(null);

  const [canvasParent, setCanvasParent] = useState<string | null>(null);
  const [canvasContent, setCanvasContent] = useState<CanvasContent | null>(null);
  const [selectedField, setSelectedField] = useState<CanvasField>("hero_title");
  const [canvasImage, setCanvasImage] = useState<{ id: string; url: string } | null>(null);
  const imageRef = useRef<{ id: string; url: string } | null>(null);
  const selectedMediaRef = useRef<string | null | undefined>(undefined);

  // This mode only changes pixels inside an embedded preview. It has no write access.
  // A parent must send from the origin in the URL and from the actual iframe parent.
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.get("furniture-canvas") !== "home" || window.parent === window) return;
    const candidate = params.get("parent-origin");
    if (!candidate) return;
    let origin: string;
    try {
      const parsed = new URL(candidate);
      if ((parsed.protocol !== "https:" && parsed.protocol !== "http:") || parsed.origin !== candidate) return;
      origin = parsed.origin;
    } catch { return; }
    setCanvasParent(origin);
    setCurrent(0);
    setPlaying(false);
    const onMessage = (event: MessageEvent) => {
      if (event.source !== window.parent || event.origin !== origin) return;
      const data: unknown = event.data;
      if (!data || typeof data !== "object" || !("type" in data)) return;
      if (data.type === "furniture-home-canvas-ping") {
        window.parent.postMessage({ type: "furniture-home-canvas-ready" }, origin);
        return;
      }
      if (data.type === "furniture-home-canvas-image") {
        if (!("id" in data) || !isMediaId(data.id) || data.id !== selectedMediaRef.current || !("mime" in data) || typeof data.mime !== "string" || !["image/png", "image/jpeg"].includes(data.mime) || !("bytes" in data) || !(data.bytes instanceof ArrayBuffer) || data.bytes.byteLength === 0 || data.bytes.byteLength > 12 * 1024 * 1024) return;
        const url = URL.createObjectURL(new Blob([data.bytes], { type: String(data.mime) }));
        if (imageRef.current) URL.revokeObjectURL(imageRef.current.url);
        imageRef.current = { id: data.id, url };
        setCanvasImage(imageRef.current);
        return;
      }
      if (data.type !== "furniture-home-canvas-values") return;
      if (!("hero_title" in data) || !("hero_subtitle" in data) || typeof data.hero_title !== "string" || typeof data.hero_subtitle !== "string") return;
      if (data.hero_title.length > 1024 || data.hero_subtitle.length > 1024) return;
      if ("hero_order" in data && !isHeroOrder(data.hero_order)) return;
      if ("hero_cta_label" in data && (typeof data.hero_cta_label !== "string" || data.hero_cta_label.length > 1024)) return;
      if ("hero_cta_path" in data && (typeof data.hero_cta_path !== "string" || data.hero_cta_path.length > 1024)) return;
      if ("hero_image" in data && !isHeroImage(data.hero_image)) return;
      if ("hero_media_id" in data && data.hero_media_id !== null && !isMediaId(data.hero_media_id)) return;
      const mediaId = "hero_media_id" in data ? (isMediaId(data.hero_media_id) ? data.hero_media_id : null) : undefined;
      selectedMediaRef.current = mediaId;
      if (imageRef.current && imageRef.current.id !== mediaId) {
        URL.revokeObjectURL(imageRef.current.url);
        imageRef.current = null;
        setCanvasImage(null);
      }
      setCanvasContent({ hero_title: data.hero_title, hero_subtitle: data.hero_subtitle, hero_order: "hero_order" in data && isHeroOrder(data.hero_order) ? data.hero_order : null,
        hero_cta_label: "hero_cta_label" in data && typeof data.hero_cta_label === "string" ? data.hero_cta_label : null,
        hero_cta_path: "hero_cta_path" in data && typeof data.hero_cta_path === "string" ? data.hero_cta_path : null,
        hero_image: "hero_image" in data && isHeroImage(data.hero_image) ? data.hero_image : null,
        hero_media_id: mediaId });
    };
    // Keep this limited canvas on the homepage; it is not a navigation preview.
    const blockNavigation = (event: MouseEvent) => {
      if (event.target instanceof Element && event.target.closest("a")) event.preventDefault();
    };
    window.addEventListener("message", onMessage);
    document.addEventListener("click", blockNavigation, true);
    window.parent.postMessage({ type: "furniture-home-canvas-ready" }, origin);
    return () => {
      window.removeEventListener("message", onMessage);
      document.removeEventListener("click", blockNavigation, true);
      if (imageRef.current) URL.revokeObjectURL(imageRef.current.url);
      imageRef.current = null;
      selectedMediaRef.current = undefined;
    };
  }, []);

  const selectCanvasField = (field: CanvasField, focusInput = true) => {
    if (!canvasParent) return;
    setSelectedField(field);
    window.parent.postMessage({ type: "furniture-home-canvas-select", field, focus_input: focusInput }, canvasParent);
  };

  // Reads the Furniture admin's public, same-origin content endpoint. Only the first
  // slide is connected; all other Reimagin v1 content stays in its real components.
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
        if (active && data && typeof data === 'object' &&
            ('hero_title' in data) && ('hero_subtitle' in data) &&
            (typeof data.hero_title === 'string' || data.hero_title === null) &&
            (typeof data.hero_subtitle === 'string' || data.hero_subtitle === null) &&
            (!('hero_order' in data) || data.hero_order === null || isHeroOrder(data.hero_order))) {
          setHomeContent({
            hero_title: typeof data.hero_title === 'string' ? data.hero_title : null,
            hero_subtitle: typeof data.hero_subtitle === 'string' ? data.hero_subtitle : null,
            hero_order: 'hero_order' in data && isHeroOrder(data.hero_order) ? data.hero_order : 'title_first',
            hero_cta_label: 'hero_cta_label' in data && typeof data.hero_cta_label === 'string' && data.hero_cta_label.length <= 80 ? data.hero_cta_label : null,
            hero_cta_path: 'hero_cta_path' in data && isInternalPath(data.hero_cta_path) ? data.hero_cta_path : null,
            hero_image: 'hero_image' in data && isHeroImage(data.hero_image) ? data.hero_image : 'workspace',
            hero_media_id: 'hero_media_id' in data && isMediaId(data.hero_media_id) ? data.hero_media_id : null,
          });
        }
      } catch {
        // The original component remains available when the optional backend is offline.
      } finally {
        loading = false;
      }
    };
    void load();
    const timer = window.setInterval(load, 2500);
    const onVisible = () => { if (!document.hidden) void load(); };
    document.addEventListener('visibilitychange', onVisible);
    return () => { active = false; window.clearInterval(timer); document.removeEventListener('visibilitychange', onVisible); };
  }, []);

  useEffect(() => {
    if (!playing) return;
    const timer = setInterval(() => setCurrent((c) => (c + 1) % slides.length), 5000);
    return () => clearInterval(timer);
  }, [playing]);

  const prev = () => setCurrent((c) => (c - 1 + slides.length) % slides.length);
  const next = () => setCurrent((c) => (c + 1) % slides.length);
  const heroOrder = current === 0 ? (canvasContent?.hero_order ?? homeContent?.hero_order ?? "title_first") : "title_first";
  const firstField = heroOrder === "title_first" ? "hero_title" : "hero_subtitle";
  const draftMediaId = canvasContent?.hero_media_id;
  const bundledImage = heroImages[canvasContent?.hero_image ?? homeContent?.hero_image ?? 'workspace'];
  const firstImage = draftMediaId !== undefined
    ? (draftMediaId === null ? bundledImage : (canvasImage?.id === draftMediaId ? canvasImage.url : undefined))
    : (homeContent?.hero_media_id ? `/storefront-api/home/image/${homeContent.hero_media_id}` : bundledImage);
  const draftPath = canvasContent?.hero_cta_path;
  const firstCtaPath = draftPath !== null && draftPath !== undefined ? (isInternalPath(draftPath) ? draftPath : slides[0].ctaHref) : (homeContent?.hero_cta_path ?? slides[0].ctaHref);
  const moveCanvasField = (direction: "up" | "down") => {
    if (!canvasParent) return;
    window.parent.postMessage({ type: "furniture-home-canvas-move", field: selectedField, direction }, canvasParent);
  };
  const titleNode = <h1 key="title" data-canvas-field={canvasParent && current === 0 ? "hero_title" : undefined} onClick={() => current === 0 && selectCanvasField("hero_title")} className={`text-3xl sm:text-4xl lg:text-5xl xl:text-6xl font-black mb-3 leading-tight ${canvasParent && current === 0 ? "cursor-pointer outline outline-1 outline-dashed outline-white" : ""}`}>{current === 0 ? (canvasContent?.hero_title ?? homeContent?.hero_title ?? slides[current].title) : slides[current].title}</h1>;
  const subtitleNode = <p key="subtitle" data-canvas-field={canvasParent && current === 0 ? "hero_subtitle" : undefined} onClick={() => current === 0 && selectCanvasField("hero_subtitle")} className={`text-base lg:text-lg text-primary-foreground/85 mb-6 leading-relaxed ${canvasParent && current === 0 ? "cursor-pointer outline outline-1 outline-dashed outline-white" : ""}`}>{current === 0 ? (canvasContent?.hero_subtitle ?? homeContent?.hero_subtitle ?? slides[current].subtitle) : slides[current].subtitle}</p>;

  return (
        <section className="relative h-[min(68vh,720px)] min-h-[390px] sm:min-h-[480px] overflow-hidden bg-primary">
          {canvasParent && (
            <div className="absolute top-3 left-3 z-20 flex flex-wrap items-center gap-2 rounded bg-primary px-3 py-2 text-xs text-primary-foreground shadow-lg" role="toolbar" aria-label="Homepage preview selection">
              <span>UNSAVED CANVAS</span>
              <button type="button" aria-pressed={selectedField === "hero_image"} onClick={() => selectCanvasField("hero_image")} className={`rounded border border-primary-foreground/50 px-2 py-1 ${selectedField === "hero_image" ? "bg-accent text-accent-foreground" : ""}`}>Background</button>
              <button type="button" aria-pressed={selectedField === "hero_cta_label"} onClick={() => selectCanvasField("hero_cta_label", false)} className={`rounded border border-primary-foreground/50 px-2 py-1 ${selectedField === "hero_cta_label" ? "bg-accent text-accent-foreground" : ""}`}>Button</button>
              <button type="button" aria-pressed={selectedField === "hero_title"} onClick={() => selectCanvasField("hero_title", false)} className={`rounded border border-primary-foreground/50 px-2 py-1 ${selectedField === "hero_title" ? "bg-accent text-accent-foreground" : ""}`}>Title</button>
              <button type="button" aria-pressed={selectedField === "hero_subtitle"} onClick={() => selectCanvasField("hero_subtitle", false)} className={`rounded border border-primary-foreground/50 px-2 py-1 ${selectedField === "hero_subtitle" ? "bg-accent text-accent-foreground" : ""}`}>Subtitle</button>
              {(selectedField === "hero_title" || selectedField === "hero_subtitle") && <>
                <button type="button" aria-label={`Move ${selectedField === "hero_title" ? "title" : "subtitle"} up`} onClick={() => moveCanvasField("up")} disabled={selectedField === firstField} className="rounded border border-primary-foreground/50 px-2 py-1 disabled:opacity-40">↑</button>
                <button type="button" aria-label={`Move ${selectedField === "hero_title" ? "title" : "subtitle"} down`} onClick={() => moveCanvasField("down")} disabled={selectedField !== firstField} className="rounded border border-primary-foreground/50 px-2 py-1 disabled:opacity-40">↓</button>
              </>}
            </div>
          )}
          {slides.map((slide, i) => (
            <div key={i} className={`absolute inset-0 transition-opacity duration-1000 ${i === current ? "opacity-100" : "opacity-0"}`}>
              <img src={i === 0 ? firstImage : slide.image} alt="" className="w-full h-full object-cover" />
              <div className="absolute inset-0 bg-gradient-to-r from-primary/75 via-primary/40 to-transparent" />
            </div>
          ))}
          <div className="absolute inset-0 flex items-end pb-20 sm:pb-24">
            <div className="container mx-auto px-4">
              <div className="max-w-xl text-primary-foreground">
                {heroOrder === "title_first" ? [titleNode, subtitleNode] : [subtitleNode, titleNode]}
                <Button size="lg" className="bg-accent hover:bg-hon-green-dark text-accent-foreground font-semibold px-8" asChild>
                  <a href={current === 0 ? firstCtaPath : slides[current].ctaHref} data-canvas-field={canvasParent && current === 0 ? "hero_cta_label" : undefined} onClick={() => current === 0 && selectCanvasField("hero_cta_label")}>
                    {current === 0 ? (canvasContent?.hero_cta_label ?? homeContent?.hero_cta_label ?? slides[current].cta) : slides[current].cta}
                  </a>
                </Button>
              </div>
            </div>
          </div>
          {!canvasParent && <div className="absolute bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-3">
            <button onClick={prev} aria-label="Previous slide" className="w-8 h-8 rounded-full bg-primary-foreground/20 hover:bg-primary-foreground/40 flex items-center justify-center text-primary-foreground transition-colors"><ChevronLeft className="h-4 w-4" /></button>
            <button onClick={() => setPlaying(!playing)} aria-label={playing ? "Pause slideshow" : "Play slideshow"} aria-pressed={playing} className="w-8 h-8 rounded-full bg-primary-foreground/20 hover:bg-primary-foreground/40 flex items-center justify-center text-primary-foreground transition-colors">{playing ? <Pause className="h-3 w-3" /> : <Play className="h-3 w-3" />}</button>
            <button onClick={next} aria-label="Next slide" className="w-8 h-8 rounded-full bg-primary-foreground/20 hover:bg-primary-foreground/40 flex items-center justify-center text-primary-foreground transition-colors"><ChevronRight className="h-4 w-4" /></button>
            <div className="flex gap-1.5 ml-2">
              {slides.map((_, i) => (
                <button key={i} onClick={() => setCurrent(i)} className={`h-1.5 rounded-full transition-all ${i === current ? "w-6 bg-accent" : "w-1.5 bg-primary-foreground/40"}`} aria-label={`Go to slide ${i + 1}`} aria-current={i === current} />
              ))}
            </div>
          </div>}
        </section>

  );
};

export default HeroSlider;
