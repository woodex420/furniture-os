/** Shared plain-text article body. React escapes both fields; no HTML is accepted. */
export default function ArticleText({ summary, body }: { summary: string; body: string }) {
  return (
    <>
      <p className="text-lg text-muted-foreground leading-relaxed mb-10 pb-10 border-b border-border">{summary}</p>
      <p className="text-base text-foreground/85 leading-[1.8] whitespace-pre-line">{body}</p>
    </>
  );
}
