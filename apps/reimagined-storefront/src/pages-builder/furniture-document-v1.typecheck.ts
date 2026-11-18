import type { ComponentProps } from 'react';
import type ArticleText from '@/components/blog/ArticleText';
import type { FurnitureDocumentV1 } from './furniture-document-v1';

// Compile-only contract: the actual shared article component accepts the props
// defined by the experimental page section. This does not validate runtime JSON.
const section: FurnitureDocumentV1['sections'][number] = {
  id: 'e38c052c-f2e0-43ca-85d0-a5760caa7d03',
  type: 'article_text',
  props: { summary: 'Synthetic summary', body: 'Synthetic body' },
};
const props: ComponentProps<typeof ArticleText> = section.props;
const document: FurnitureDocumentV1 = { schema_version: 1, sections: [section] };
void props;
void document;
// @ts-expect-error Unsupported schema versions must not typecheck as v1.
const future: FurnitureDocumentV1 = { schema_version: 2, sections: [] };
void future;
