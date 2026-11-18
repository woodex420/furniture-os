import { useEffect, useState } from 'react';
import DocumentSections from './DocumentSections';
import type { FurnitureDocumentV1 } from './furniture-document-v1';

// Temporary pixels only: the PHP contract remains the write authority. Drafts may
// be blank while typing; cap incoming payloads before passing them to React.
function isDraft(value: unknown): value is FurnitureDocumentV1 {
  if (!value || typeof value !== 'object') return false;
  const doc = value as Record<string, unknown>;
  if (doc.schema_version !== 1 || !Array.isArray(doc.sections) || doc.sections.length > 10) return false;
  const ids = new Set<string>();
  return doc.sections.every((item: unknown) => {
    if (!item || typeof item !== 'object') return false;
    const section = item as Record<string, unknown>;
    if (typeof section.id !== 'string' || !/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/.test(section.id) || ids.has(section.id) || section.type !== 'article_text') return false;
    ids.add(section.id);
    if (!section.props || typeof section.props !== 'object') return false;
    const props = section.props as Record<string, unknown>;
    return typeof props.summary === 'string' && props.summary.length <= 600 && typeof props.body === 'string' && props.body.length <= 24000;
  });
}

/** Isolated synthetic canvas; no data loading, writes, navigation, or publication. */
export default function DocumentCanvas() {
  const [document, setDocument] = useState<FurnitureDocumentV1 | null>(null);
  const [selected, setSelected] = useState<string | null>(null);
  const [parentOrigin, setParentOrigin] = useState<string | null>(null);
  useEffect(() => {
    const candidate = new URLSearchParams(window.location.search).get('parent-origin');
    if (!candidate || window.parent === window) return;
    let origin: string;
    try {
      const url = new URL(candidate);
      if (!['http:', 'https:'].includes(url.protocol) || url.origin !== candidate || url.origin === window.location.origin) return;
      origin = url.origin;
    } catch { return; }
    setParentOrigin(origin);
    const receive = (event: MessageEvent) => {
      if (event.source !== window.parent || event.origin !== origin || !event.data || typeof event.data !== 'object') return;
      if (event.data.type === 'furniture-document-canvas-ping') {
        window.parent.postMessage({ type: 'furniture-document-canvas-ready' }, origin);
      } else if (event.data.type === 'furniture-document-canvas-values' && isDraft(event.data.document)) {
        setDocument(event.data.document);
      }
    };
    window.addEventListener('message', receive);
    window.parent.postMessage({ type: 'furniture-document-canvas-ready' }, origin);
    return () => window.removeEventListener('message', receive);
  }, []);
  function select(id: string) {
    if (!parentOrigin) return;
    setSelected(id);
    window.parent.postMessage({ type: 'furniture-document-canvas-select', id }, parentOrigin);
  }
  return <main className="container mx-auto max-w-3xl px-4 py-8" data-document-canvas>
    <p className="mb-5 text-xs font-bold uppercase tracking-wider text-muted-foreground">Synthetic unsaved canvas · Article text only</p>
    {!document && <p role="status">Waiting for the synthetic editor…</p>}
    {document?.sections.map((section, index) => <section key={section.id} data-canvas-section={section.id} className={`mb-6 rounded border p-4 ${selected === section.id ? 'border-primary' : 'border-border'}`}>
      <button type="button" className="mb-4 rounded border border-primary px-3 py-1 text-sm font-semibold focus-visible:outline focus-visible:outline-2" onClick={() => select(section.id)}>Edit section {index + 1}</button>
      <DocumentSections document={{ schema_version: 1, sections: [section] }} />
    </section>)}
  </main>;
}
