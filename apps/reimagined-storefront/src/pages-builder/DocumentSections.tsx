import ArticleText from '@/components/blog/ArticleText';
import type { FurnitureDocumentV1 } from './furniture-document-v1';

/** Only call with server-validated V1 documents; TypeScript is not JSON validation. */
export default function DocumentSections({ document }: { document: FurnitureDocumentV1 }) {
  if (document.schema_version !== 1 || !Array.isArray(document.sections)) {
    throw new Error('Unsupported builder document');
  }
  return <>{document.sections.map(section => {
    switch (section.type) {
      case 'article_text':
        return <ArticleText key={section.id} {...section.props} />;
      default:
        throw new Error('Unsupported builder section');
    }
  })}</>;
}
