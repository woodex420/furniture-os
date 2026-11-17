// Reject partial or malformed snapshots; keep the last known good preview.
export function parseServiceCopy(input, slugs) {
  if (!Array.isArray(input) || input.length !== slugs.length) return null;
  const known = new Set(slugs);
  const seen = new Set();
  const result = {};
  for (const row of input) {
    if (!row || typeof row !== 'object' || typeof row.slug !== 'string' || !known.has(row.slug) || seen.has(row.slug)) return null;
    seen.add(row.slug);
    if (row.title === null && row.tagline === null) continue;
    if (typeof row.title !== 'string' || typeof row.tagline !== 'string' || !row.title.trim() || !row.tagline.trim() || row.title.length > 480 || row.tagline.length > 960 || Array.from(row.title).length > 120 || Array.from(row.tagline).length > 240) return null;
    result[row.slug] = { title: row.title, tagline: row.tagline };
  }
  return result;
}
