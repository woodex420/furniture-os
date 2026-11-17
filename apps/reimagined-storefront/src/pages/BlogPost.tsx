import { useEffect } from "react";
import { Link, useParams } from "react-router-dom";
import { ArrowLeft } from "lucide-react";
import Header from "@/components/Header";
import Footer from "@/components/Footer";
import { useFurnitureBlog, type Article } from "@/lib/useFurnitureBlog";
import blogErgonomic from "@/assets/blog-ergonomic.webp";

/** Actual journal detail page; unavailable or unpublished articles never fall back to demos. */
const BlogPost = () => {
  const { postId = "" } = useParams<{ postId: string }>();
  const result = useFurnitureBlog<Article>("detail", postId);
  const post = result.data;
  useEffect(() => { document.title = post ? `${post.title} — WOODEX Journal` : "Article — WOODEX Journal"; }, [post]);
  useEffect(() => { window.scrollTo(0, 0); }, [postId]);

  return (
    <div className="min-h-screen flex flex-col">
      <Header />
      <main className="flex-1" aria-live="polite">
        {result.status === "loading" && <section className="container mx-auto px-4 py-16"><p role="status">Loading article…</p></section>}
        {result.status === "unavailable" && <section className="container mx-auto px-4 py-16"><p role="alert">Article is temporarily unavailable. Please try again later.</p><Link to="/blog" className="text-accent font-bold mt-5 inline-block">← All articles</Link></section>}
        {result.status === "missing" && <section className="container mx-auto px-4 py-16"><h1 className="text-3xl font-bold">Article not found or unavailable</h1><p className="text-muted-foreground mt-4">This preview shows only articles published in Furniture. Older demo links are not imported.</p><Link to="/blog" className="text-accent font-bold mt-5 inline-block">← All articles</Link></section>}
        {result.status === "ready" && post && <>
          <section className="relative h-[420px] overflow-hidden bg-primary">
            <img src={blogErgonomic} alt="" className="w-full h-full object-cover opacity-30" />
            <div className="absolute inset-0 bg-gradient-to-r from-primary via-primary/85 to-primary/40" />
            <div className="absolute inset-0 flex items-end pb-14"><div className="container mx-auto px-4">
              <nav aria-label="Breadcrumb" className="text-xs text-primary-foreground/60 mb-4 flex items-center gap-2"><Link to="/" className="hover:text-accent">Home</Link><span>/</span><Link to="/blog" className="hover:text-accent">Blog</Link><span>/</span><span className="text-accent truncate max-w-[40ch]">{post.title}</span></nav>
              <p className="text-accent text-xs font-bold uppercase tracking-[0.25em] mb-3">WOODEX Journal</p>
              <h1 className="text-3xl md:text-5xl font-black text-primary-foreground max-w-4xl leading-[1.1]">{post.title}</h1>
            </div></div>
          </section>
          <section className="py-16 bg-background"><div className="container mx-auto px-4"><article className="max-w-3xl">
            <p className="text-lg text-muted-foreground leading-relaxed mb-10 pb-10 border-b border-border">{post.summary}</p>
            <p className="text-base text-foreground/85 leading-[1.8] whitespace-pre-line">{post.body}</p>
            <Link to="/blog" className="inline-flex items-center gap-2 text-accent font-bold mt-10"><ArrowLeft className="h-4 w-4" />Back to all articles</Link>
          </article></div></section>
        </>}
      </main>
      <Footer />
    </div>
  );
};

export default BlogPost;
