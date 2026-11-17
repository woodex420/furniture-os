import { useState, useEffect } from "react";
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


/** Shared by the current Reimagin homepage and the isolated Astro parity spike. */
const HeroSlider = () => {
  const [current, setCurrent] = useState(0);
  const [playing, setPlaying] = useState(true);
  const [homeContent, setHomeContent] = useState<{ hero_title: string | null; hero_subtitle: string | null } | null>(null);

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
            (typeof data.hero_subtitle === 'string' || data.hero_subtitle === null)) {
          setHomeContent({
            hero_title: typeof data.hero_title === 'string' ? data.hero_title : null,
            hero_subtitle: typeof data.hero_subtitle === 'string' ? data.hero_subtitle : null,
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

  return (
        <section className="relative h-[min(68vh,720px)] min-h-[390px] sm:min-h-[480px] overflow-hidden bg-primary">
          {slides.map((slide, i) => (
            <div key={i} className={`absolute inset-0 transition-opacity duration-1000 ${i === current ? "opacity-100" : "opacity-0"}`}>
              <img src={slide.image} alt="" className="w-full h-full object-cover" />
              <div className="absolute inset-0 bg-gradient-to-r from-primary/75 via-primary/40 to-transparent" />
            </div>
          ))}
          <div className="absolute inset-0 flex items-end pb-20 sm:pb-24">
            <div className="container mx-auto px-4">
              <div className="max-w-xl text-primary-foreground">
                <h1 className="text-3xl sm:text-4xl lg:text-5xl xl:text-6xl font-black mb-3 leading-tight">{current === 0 ? (homeContent?.hero_title ?? slides[current].title) : slides[current].title}</h1>
                <p className="text-base lg:text-lg text-primary-foreground/85 mb-6 leading-relaxed">{current === 0 ? (homeContent?.hero_subtitle ?? slides[current].subtitle) : slides[current].subtitle}</p>
                <Button size="lg" className="bg-accent hover:bg-hon-green-dark text-accent-foreground font-semibold px-8" asChild>
                  <a href={slides[current].ctaHref}>{slides[current].cta}</a>
                </Button>
              </div>
            </div>
          </div>
          <div className="absolute bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-3">
            <button onClick={prev} aria-label="Previous slide" className="w-8 h-8 rounded-full bg-primary-foreground/20 hover:bg-primary-foreground/40 flex items-center justify-center text-primary-foreground transition-colors"><ChevronLeft className="h-4 w-4" /></button>
            <button onClick={() => setPlaying(!playing)} aria-label={playing ? "Pause slideshow" : "Play slideshow"} aria-pressed={playing} className="w-8 h-8 rounded-full bg-primary-foreground/20 hover:bg-primary-foreground/40 flex items-center justify-center text-primary-foreground transition-colors">{playing ? <Pause className="h-3 w-3" /> : <Play className="h-3 w-3" />}</button>
            <button onClick={next} aria-label="Next slide" className="w-8 h-8 rounded-full bg-primary-foreground/20 hover:bg-primary-foreground/40 flex items-center justify-center text-primary-foreground transition-colors"><ChevronRight className="h-4 w-4" /></button>
            <div className="flex gap-1.5 ml-2">
              {slides.map((_, i) => (
                <button key={i} onClick={() => setCurrent(i)} className={`h-1.5 rounded-full transition-all ${i === current ? "w-6 bg-accent" : "w-1.5 bg-primary-foreground/40"}`} aria-label={`Go to slide ${i + 1}`} aria-current={i === current} />
              ))}
            </div>
          </div>
        </section>

  );
};

export default HeroSlider;
