import type { AdminLead, Paginated } from "../api/types";

const PAGE_SIZE = 100;
const MAX_PAGES = 5;

type LeadPage = (params: { page: number; perPage: number; status: "all"; type: "all" }) => Promise<Paginated<AdminLead>>;

/** A bounded sample, not a transactionally consistent or complete business report. */
export async function loadCampaignLeadSample(listLeads: LeadPage): Promise<{ leads: AdminLead[]; total: number }> {
  const leads: AdminLead[] = [];
  const ids = new Set<string>();
  let total: number | undefined;
  for (let page = 1; page <= MAX_PAGES; page++) {
    const result = await listLeads({ page, perPage: PAGE_SIZE, status: "all", type: "all" });
    if (!result || !Array.isArray(result.items) || !Number.isSafeInteger(result.total) || result.total < 0 || result.page !== page || result.perPage !== PAGE_SIZE) {
      throw new Error("Invalid lead page");
    }
    if (total === undefined) total = result.total;
    if (result.total !== total) throw new Error("Lead list changed while loading");
    const expected = Math.min(PAGE_SIZE, Math.max(0, total - (page - 1) * PAGE_SIZE));
    if (result.items.length !== expected) throw new Error("Incomplete lead page");
    for (const lead of result.items) {
      if (!lead || typeof lead.id !== "string" || !lead.id || ids.has(lead.id)) throw new Error("Invalid or duplicate lead");
      ids.add(lead.id);
      leads.push(lead);
    }
    if (leads.length >= total) break;
  }
  return { leads, total: total ?? 0 };
}
