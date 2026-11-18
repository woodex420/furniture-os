/**
 * Experimental counterpart to Laravel BuilderDocumentV1. Types are not runtime
 * validation: Laravel rejects unsupported versions and all unknown fields.
 * IDs are canonical v4 UUIDs; text, section and encoded-size limits are enforced
 * by the server. No storefront route or publisher consumes these documents yet.
 */
export type ArticleTextProps = { summary: string; body: string };
export type FurnitureArticleTextSectionV1 = {
  id: string;
  type: 'article_text';
  props: ArticleTextProps;
};
export type FurnitureDocumentV1 = {
  schema_version: 1;
  sections: FurnitureArticleTextSectionV1[];
};
