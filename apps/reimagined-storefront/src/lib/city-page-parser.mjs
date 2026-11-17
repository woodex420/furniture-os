const slugOk = (v) => typeof v === 'string' && v.length <= 80 && /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(v);
const textOk = (v, max) => typeof v === 'string' && v.trim().length > 0 && v.length <= max * 4 && Array.from(v).length <= max;
const baseOk = (v) => v && typeof v === 'object' && !Array.isArray(v) && slugOk(v.slug) && textOk(v.city_name, 100) && textOk(v.title, 120) && textOk(v.summary, 240);

export function parseCityIndex(input, page) {
  if (!input || !Array.isArray(input.data) || input.data.length > 20 || !Number.isSafeInteger(input.current_page) || input.current_page !== page || !Number.isSafeInteger(input.last_page) || input.last_page < 1) return null;
  const seen = new Set();
  for (const item of input.data) {
    if (!baseOk(item) || seen.has(item.slug)) return null;
    seen.add(item.slug);
  }
  return { data: input.data.map(({ slug, city_name, title, summary }) => ({ slug, city_name, title, summary })), current_page: input.current_page, last_page: input.last_page };
}

export function parseCityDetail(input, slug) {
  if (!baseOk(input) || input.slug !== slug || !textOk(input.body, 4000)) return null;
  return { slug: input.slug, city_name: input.city_name, title: input.title, summary: input.summary, body: input.body };
}

export { slugOk };
