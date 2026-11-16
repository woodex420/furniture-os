import { describe, expect, it, vi } from 'vitest';
import { loadCampaignLeadSample } from '../../reimagined-storefront/src/lib/campaign-lead-sample';

function adapterFor(total) {
  return vi.fn(async ({ page, perPage }) => ({
    total, page, perPage,
    items: Array.from({ length: Math.min(perPage, Math.max(0, total - (page - 1) * perPage)) }, (_, i) => ({ id: String((page - 1) * perPage + i) })),
  }));
}

describe('bounded campaign lead sample', () => {
  it.each([0, 1, 99, 100, 101, 199, 200, 201, 499, 500, 501, 1200])('loads the partial final page and caps requests for %i records', async (total) => {
    const list = adapterFor(total);
    const result = await loadCampaignLeadSample(list);
    expect(result.leads).toHaveLength(Math.min(total, 500));
    expect(result.total).toBe(total);
    expect(list).toHaveBeenCalledTimes(Math.min(5, Math.max(1, Math.ceil(total / 100))));
  });
  it('rejects a failed later page rather than returning partial totals', async () => {
    const list = adapterFor(101);
    list.mockImplementationOnce(async () => ({ items: Array.from({ length: 100 }, (_, i) => ({ id: String(i) })), total: 101, page: 1, perPage: 100 }));
    list.mockRejectedValueOnce(new Error('unavailable'));
    await expect(loadCampaignLeadSample(list)).rejects.toThrow('unavailable');
  });
  it('rejects a changed total or incomplete page', async () => {
    const list = adapterFor(101);
    list.mockResolvedValueOnce({ items: Array.from({ length: 100 }, (_, i) => ({ id: String(i) })), total: 101, page: 1, perPage: 100 });
    list.mockResolvedValueOnce({ items: [], total: 100, page: 2, perPage: 100 });
    await expect(loadCampaignLeadSample(list)).rejects.toThrow('changed');
    const incomplete = vi.fn().mockResolvedValue({ items: [], total: 1, page: 1, perPage: 100 });
    await expect(loadCampaignLeadSample(incomplete)).rejects.toThrow('Incomplete');
  });
  it('rejects duplicate or malformed data rather than double-counting', async () => {
    const duplicate = vi.fn().mockResolvedValue({ items: [{ id: '1' }, { id: '1' }], total: 2, page: 1, perPage: 100 });
    await expect(loadCampaignLeadSample(duplicate)).rejects.toThrow('duplicate');
    const invalid = vi.fn().mockResolvedValue({ items: [], total: -1, page: 1, perPage: 100 });
    await expect(loadCampaignLeadSample(invalid)).rejects.toThrow('Invalid');
  });
});
