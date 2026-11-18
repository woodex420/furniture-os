// Browser-test harness only; the inherited React admin is not exposed by the storefront.
import React, { useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { AdminAuthProvider, useAdminAuth } from '../src/admin/auth';
import { config } from '../src/config/env';
import { getAdminApi } from '../src/api';
import { getPublishedPage, getPagePreview, listPublishedProjects } from '../src/api/supabase';
import { supabase } from '../src/integrations/supabase/client';
import AdminImport from '../src/admin/pages/Import';

function Probe() {
  const auth = useAdminAuth();
  useEffect(() => { Object.assign(window, { testAuth: auth }); }, [auth]);
  return <div id="auth-probe">{auth.checking ? 'checking' : auth.user ? 'signed-in' : 'signed-out'}</div>;
}

Object.assign(window, {
  testAdapter: async () => {
    const failures: boolean[] = [];
    for (const operation of [() => getAdminApi().listProducts(), () => getAdminApi().createLead({} as never), () => getPublishedPage('home'), () => getPagePreview('home'), () => listPublishedProjects()]) {
      try { await operation(); failures.push(false); } catch { failures.push(true); }
    }
    // Supabase JS may return an error object rather than throw on transport failure.
    let clientBlocked = false;
    try { const response = await supabase.from('synthetic' as never).select(); clientBlocked = Boolean(response.error); } catch { clientBlocked = true; }
    return { mode: config.apiMode, enabled: config.supabase.enabled, url: config.supabase.url, key: config.supabase.publishableKey, failures, clientBlocked };
  },
});
createRoot(document.body.appendChild(document.createElement('div'))).render(<AdminAuthProvider><Probe /><AdminImport /></AdminAuthProvider>);
