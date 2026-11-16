// @vitest-environment jsdom
import React from 'react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { cleanup, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import PreviewWorkspace from './PreviewWorkspace';

const json = (data = [], meta = { total: data.length, current_page: 1, last_page: 1 }) => ({
  ok: true, status: 200, redirected: false, headers: new Headers({ 'Content-Type': 'application/json' }), json: async () => ({ data, meta }),
});
const record = { id: 1, reference: 'L-EXAMPLE', contact_name: 'Test Person', source_label: 'Website Form', owner_name: null, next_action_at: null, stage: { key: 'new', name: 'New' } };
afterEach(() => { cleanup(); vi.unstubAllGlobals(); });

describe('connected Leads component (host integration pending)', () => {
  it('loads only the configured same-origin endpoint and renders its data', async () => {
    const fetch = vi.fn().mockResolvedValue(json([record])); vi.stubGlobal('fetch', fetch);
    render(<PreviewWorkspace page="Leads" go={vi.fn()} leadsMode="connected" leadsEndpoint="/admin/workspace-data/leads" />);
    expect(screen.getByRole('status').textContent).toMatch(/Loading/);
    expect(await screen.findByText('Test Person')).toBeTruthy();
    expect(screen.getByText('Unassigned')).toBeTruthy();
    expect(screen.getByText('L-EXAMPLE')).toBeTruthy();
    expect(screen.queryByText('Amina Malik')).toBeNull();
    expect(fetch).toHaveBeenCalledWith('/admin/workspace-data/leads?page=1', expect.objectContaining({ credentials: 'same-origin', cache: 'no-store' }));
  });
  it('searches server side and pages through returned results', async () => {
    const user = userEvent.setup();
    const fetch = vi.fn().mockResolvedValue(json([record], { total: 26, current_page: 1, last_page: 2 })); vi.stubGlobal('fetch', fetch);
    render(<PreviewWorkspace page="Leads" go={vi.fn()} leadsMode="connected" leadsEndpoint="/admin/workspace-data/leads" />);
    await screen.findByText('Test Person');
    await user.type(screen.getByLabelText('Search Leads'), 'Test');
    await user.click(screen.getByRole('button', { name: 'Search' }));
    await waitFor(() => expect(fetch).toHaveBeenLastCalledWith('/admin/workspace-data/leads?search=Test&page=1', expect.anything()));
    await screen.findByText('Test Person');
    await user.click(screen.getByRole('button', { name: 'Next' }));
    await waitFor(() => expect(fetch).toHaveBeenLastCalledWith('/admin/workspace-data/leads?search=Test&page=2', expect.anything()));
  });
  it.each([[401, /session is unavailable/i], [403, /do not have permission/i], [500, /could not be loaded/i]])('handles %s without showing sample data', async (status, message) => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ status, ok: false, redirected: false }));
    render(<PreviewWorkspace page="Leads" go={vi.fn()} leadsMode="connected" leadsEndpoint="/admin/workspace-data/leads" />);
    expect((await screen.findByRole('alert')).textContent).toMatch(message);
    expect(screen.queryByText('Amina Malik')).toBeNull();
  });
  it('shows an empty state and rejects an unsafe or missing endpoint', async () => {
    const fetch = vi.fn().mockResolvedValue(json()); vi.stubGlobal('fetch', fetch);
    const view = render(<PreviewWorkspace page="Leads" go={vi.fn()} leadsMode="connected" leadsEndpoint="/admin/workspace-data/leads" />);
    expect(await screen.findByText('No leads found')).toBeTruthy();
    view.unmount(); fetch.mockClear();
    render(<PreviewWorkspace page="Leads" go={vi.fn()} leadsMode="connected" leadsEndpoint="//example.com/steal" />);
    expect((await screen.findByRole('alert')).textContent).toMatch(/could not be loaded/i);
    expect(fetch).not.toHaveBeenCalled();
  });
  it('ignores stale results after a new search', async () => {
    const user = userEvent.setup(); let finishFirst;
    const first = new Promise(resolve => { finishFirst = resolve; });
    const fetch = vi.fn().mockReturnValueOnce(first).mockResolvedValue(json([{ ...record, contact_name: 'New result' }])); vi.stubGlobal('fetch', fetch);
    render(<PreviewWorkspace page="Leads" go={vi.fn()} leadsMode="connected" leadsEndpoint="/admin/workspace-data/leads" />);
    await user.type(screen.getByLabelText('Search Leads'), 'new');
    await user.click(screen.getByRole('button', { name: 'Search' }));
    expect(await screen.findByText('New result')).toBeTruthy();
    finishFirst(json([record]));
    await waitFor(() => expect(screen.queryByText('Test Person')).toBeNull());
    expect(screen.getByText('New result')).toBeTruthy();
  });
});
