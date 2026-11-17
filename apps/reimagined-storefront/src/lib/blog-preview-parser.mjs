const slugOk = (v) => typeof v === 'string' && v.length <= 80 && /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(v);
const textOk = (v, max) => typeof v === 'string' && v.trim().length > 0 && v.length <= max * 4 && Array.from(v).length <= max;
const baseOk = (v) => v && typeof v === 'object' && !Array.isArray(v) && slugOk(v.slug) && textOk(v.title, 160) && textOk(v.summary, 300);

export function parseBlogIndex(input, page) {
  if (!input || !Array.isArray(input.data) || input.data.length > 12 || !Number.isSafeInteger(input.current_page) || input.current_page !== page || !Number.isSafeInteger(input.last_page) || input.last_page < 1) return null;
  const seen = new Set();
  for (const item of input.data) {
    if (!baseOk(item) || seen.has(item.slug)) return null;
    seen.add(item.slug);
  }
  return { data: input.data.map(({ slug, title, summary }) => ({ slug, title, summary })), current_page: input.current_page, last_page: input.last_page };
}

export function parseBlogDetail(input, slug) {
  if (!baseOk(input) || input.slug !== slug || !textOk(input.body, 12000)) return null;
  return { slug: input.slug, title: input.title, summary: input.summary, body: input.body };
}

export { slugOk };
