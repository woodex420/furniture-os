import type { ArticleTextProps } from '@/pages-builder/furniture-document-v1';

/** Shared plain-text article body. React escapes both fields; no HTML is accepted. */
export default function ArticleText({ summary, body }: ArticleTextProps) {
  return (
    <>
      <p className="text-lg text-muted-foreground leading-relaxed mb-10 pb-10 border-b border-border">{summary}</p>
      <p className="text-base text-foreground/85 leading-[1.8] whitespace-pre-line">{body}</p>
    </>
  );
}
