// Browser-test harness only; the inherited React admin is not exposed by the storefront.
import React, { useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { AdminAuthProvider, useAdminAuth } from '../src/admin/auth';

function Probe() {
  const auth = useAdminAuth();
  useEffect(() => { Object.assign(window, { testAuth: auth }); }, [auth]);
  return <div id="auth-probe">{auth.checking ? 'checking' : auth.user ? 'signed-in' : 'signed-out'}</div>;
}

export function mount() {
  const el = document.createElement('div');
  document.body.appendChild(el);
  createRoot(el).render(<AdminAuthProvider><Probe /></AdminAuthProvider>);
}
