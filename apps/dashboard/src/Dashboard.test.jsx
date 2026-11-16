// @vitest-environment jsdom
import React from 'react';
import { afterEach, expect, it, vi } from 'vitest';
import { cleanup, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Dashboard from './Dashboard';

vi.mock('recharts', () => {
  const Stub = () => null;
  return { AreaChart: Stub, Area: Stub, XAxis: Stub, YAxis: Stub, CartesianGrid: Stub, ResponsiveContainer: Stub, Tooltip: Stub };
});
afterEach(cleanup);
it('shows the redesigned overview and navigates to the example approval queue', async () => {
  const go = vi.fn();
  const user = userEvent.setup();
  render(<Dashboard go={go} explore={vi.fn()} />);
  expect(screen.getByText('Welcome back, Admin')).toBeTruthy();
  expect(screen.getByText('Sales pipeline')).toBeTruthy();
  expect(screen.getByText(/Sample data only/)).toBeTruthy();
  await user.click(screen.getByRole('button', {name: /View approval queue/}));
  expect(go).toHaveBeenCalledWith('Approvals');
});
