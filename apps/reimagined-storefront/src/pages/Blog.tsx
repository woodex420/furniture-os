import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { ArrowRight } from "lucide-react";
import Header from "@/components/Header";
import Footer from "@/components/Footer";
import { useFurnitureBlog, type ArticleList } from "@/lib/useFurnitureBlog";
import blogErgonomic from "@/assets/blog-ergonomic.webp";

/** The actual Reimagin journal, now reading Furniture-published articles only. */
const Blog = () => {
  const [page, setPage] = useState(1);
  const result = useFurnitureBlog<ArticleList>("index", String(page));
  useEffect(() => { document.title = "Blog — WOODEX Journal"; }, []);

  return (
    <div className="min-h-screen flex flex-col">
      <Header />
      <main className="flex-1">
        <section className="relative h-[420px] overflow-hidden bg-primary">
          <img src={blogErgonomic} alt="" className="w-full h-full object-cover opacity-30" />
          <div className="absolute inset-0 bg-gradient-to-r from-primary via-primary/85 to-primary/40" />
          <div className="absolute inset-0 flex items-center"><div className="container mx-auto px-4">
            <nav aria-label="Breadcrumb" className="text-xs text-primary-foreground/60 mb-4 flex items-center gap-2"><Link to="/" className="hover:text-accent">Home</Link><span>/</span><span className="text-accent">Blog</span></nav>
            <p className="text-accent text-xs font-bold uppercase tracking-[0.25em] mb-3">Ideas &amp; Inspiration</p>
            <h1 className="text-5xl lg:text-6xl font-black text-primary-foreground mb-4 leading-[1.05]">WOODEX Journal</h1>
            <p className="text-primary-foreground/80 max-w-2xl text-base lg:text-lg leading-relaxed">Explore articles published in the Furniture preview.</p>
          </div></div>
        </section>
        <section className="py-16 bg-section-light border-t" aria-live="polite"><div className="container mx-auto px-4">
          <div className="flex items-center gap-3 mb-3"><div className="w-10 h-0.5 bg-accent" /><p className="text-xs font-bold uppercase tracking-[0.2em] text-accent">From the Journal</p></div>
          <h2 className="text-3xl font-bold mb-10">Latest Articles</h2>
          {result.status === "loading" && <p role="status">Loading articles…</p>}
          {result.status === "unavailable" && <p role="alert">Articles are temporarily unavailable. Please try again later.</p>}
          {result.status === "ready" && result.data && <>
            {result.data.data.length === 0 ? <p>No published articles are available on this page.</p> : <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-7">{result.data.data.map((post) => (
              <article key={post.slug} className="group bg-background border border-border rounded-sm overflow-hidden hover:border-accent hover:shadow-xl transition-all flex flex-col">
                <Link to={`/blog/${post.slug}`} className="aspect-[16/10] overflow-hidden"><img src={blogErgonomic} alt="" loading="lazy" className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" /></Link>
                <div className="p-6 flex flex-col flex-1"><h3 className="font-bold text-lg mb-3 group-hover:text-accent leading-snug"><Link to={`/blog/${post.slug}`}>{post.title}</Link></h3><p className="text-sm text-muted-foreground leading-relaxed flex-1">{post.summary}</p><Link to={`/blog/${post.slug}`} className="mt-5 text-accent text-xs font-bold uppercase tracking-widest inline-flex items-center gap-1">Read Article <ArrowRight className="h-3 w-3" /></Link></div>
              </article>
            ))}</div>}
            <nav aria-label="Article pages" className="flex gap-4 items-center mt-8"><button type="button" disabled={page <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))} className="border px-3 py-2 disabled:opacity-40">Previous</button><span>Page {page} of {result.data.last_page}</span><button type="button" disabled={page >= result.data.last_page} onClick={() => setPage((p) => p + 1)} className="border px-3 py-2 disabled:opacity-40">Next</button></nav>
          </>}
        </div></section>
      </main>
      <Footer />
    </div>
  );
};

export default Blog;
