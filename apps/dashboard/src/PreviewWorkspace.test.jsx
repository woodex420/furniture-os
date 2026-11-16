// @vitest-environment jsdom
import React from 'react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { cleanup, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import PreviewWorkspace from './PreviewWorkspace';

afterEach(cleanup);
describe('read-only workspace preview', () => {
  it('labels sample pages as pending integration with a relevant next step', () => {
    render(<PreviewWorkspace page="Products" go={vi.fn()} />);
    expect(screen.getByText('PREVIEW UPDATED · INTEGRATION PENDING')).toBeTruthy();
    expect(screen.getByText(/Next: Design the catalog contract and product database/)).toBeTruthy();
    expect(screen.getByText('Sample data')).toBeTruthy();
  });
  it('labels unfinished modules as concept-only and pending', () => {
    render(<PreviewWorkspace page="Visual Page Builder" go={vi.fn()} />);
    expect(screen.getByText('PENDING · CONCEPT VIEW')).toBeTruthy();
    expect(screen.getByText(/Next: Integrate the Laravel builder and storefront renderer/)).toBeTruthy();
  });
  it('searches, filters and resets example records', async () => {
    const user = userEvent.setup();
    render(<PreviewWorkspace page="Leads" go={vi.fn()} />);
    expect(screen.getByText('Showing 4 of 4 example records')).toBeTruthy();
    await user.type(screen.getByLabelText('Search Leads'), 'Amina');
    expect(screen.getByText('Showing 1 of 4 example records')).toBeTruthy();
    await user.selectOptions(screen.getByLabelText('Filter by status'), 'Qualified');
    expect(screen.getByText('No matching records')).toBeTruthy();
    await user.click(screen.getByRole('button', { name: 'Clear filters' }));
    expect(screen.getByText('Showing 4 of 4 example records')).toBeTruthy();
  });
  it('opens a read-only detail and closes it with Escape', async () => {
    const user = userEvent.setup();
    render(<PreviewWorkspace page="Approvals" go={vi.fn()} />);
    const trigger = screen.getByRole('button', { name: 'View QT-2024-081' });
    await user.click(trigger);
    const dialog = screen.getByRole('dialog', { name: 'QT-2024-081 details' });
    expect(within(dialog).getByText(/Approval decisions/)).toBeTruthy();
    expect(within(dialog).queryByRole('button', { name: /approve|reject/i })).toBeNull();
    await user.keyboard('{Escape}');
    expect(screen.queryByRole('dialog')).toBeNull();
    expect(document.activeElement).toBe(trigger);
  });
  it('does not claim that unimplemented destinations are connected', () => {
    render(<PreviewWorkspace page="Production" go={vi.fn()} />);
    expect(screen.getByText(/not implemented or connected/)).toBeTruthy();
  });
});
