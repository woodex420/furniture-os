import React, { useEffect, useState } from 'react';
import { Search } from 'lucide-react';

// This component requires a protected Laravel host. It is not enabled in the standalone demo.
function safeEndpoint(value) {
  if (typeof value !== 'string' || !value.startsWith('/') || value.startsWith('//') || value.includes('\\')) return null;
  try {
    const url = new URL(value, window.location.origin);
    return url.origin === window.location.origin && !url.search && !url.hash ? url : null;
  } catch { return null; }
}

function formatDate(value) {
  if (!value) return '—';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? '—' : new Intl.DateTimeFormat('en-GB', { dateStyle: 'medium', timeZone: 'Asia/Karachi' }).format(date);
}

export default function ConnectedLeads({ endpoint }) {
  const [draft, setDraft] = useState('');
  const [query, setQuery] = useState('');
  const [page, setPage] = useState(1);
  const [attempt, setAttempt] = useState(0);
  const [result, setResult] = useState({ status: 'loading' });

  useEffect(() => {
    const controller = new AbortController();
    let current = true;
    setResult({ status: 'loading' });
    async function load() {
      const url = safeEndpoint(endpoint);
      if (!url) { if (current) setResult({ status: 'error' }); return; }
      if (query) url.searchParams.set('search', query);
      url.searchParams.set('page', String(page));
      try {
        const response = await fetch(url.pathname + url.search, {
          credentials: 'same-origin', headers: { Accept: 'application/json' }, cache: 'no-store', signal: controller.signal,
        });
        if (response.status === 401 || response.redirected) { if (current) setResult({ status: 'signed-out' }); return; }
        if (response.status === 403) { if (current) setResult({ status: 'denied' }); return; }
        if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) throw new Error('Invalid response');
        const payload = await response.json();
        if (!Array.isArray(payload.data) || !Number.isInteger(payload.meta?.total) || !Number.isInteger(payload.meta?.last_page) || !Number.isInteger(payload.meta?.current_page)) throw new Error('Invalid response');
        if (current) setResult({ ...payload, status: 'ready' });
      } catch (error) {
        if (current && error.name !== 'AbortError') setResult({ status: 'error' });
      }
    }
    load();
    return () => { current = false; controller.abort(); };
  }, [endpoint, query, page, attempt]);

  function search(event) {
    event.preventDefault();
    setPage(1);
    setQuery(draft.trim());
  }

  return <div className="content list-page">
    <div className="demo-banner"><span>READ-ONLY INTEGRATION</span> Leads use the authenticated workspace when available. Actions and other pages remain pending.</div>
    <div className="page-heading list-heading"><div><div className="eyebrow">WORKSPACE <span>/</span> LEADS</div><h1>Leads</h1><p>Keep track of new enquiries and the next step for each customer.</p></div><span className="preview-label">READ ONLY · ACTIONS PENDING</span></div>
    <div className="list-overview"><div><span>WORKSPACE</span><strong>Leads</strong><small>A clearer view of your daily work</small></div><div className="overview-count"><span>VISIBLE RECORDS</span><strong>{result.status === 'ready' ? result.meta.total : '—'}</strong><small>Matching your search</small></div></div>
    <section className="card records-card" aria-label="Leads records">
      <div className="records-title"><div><h2>All leads</h2><p>Only leads you are allowed to see are shown.</p></div></div>
      <form className="records-tools" onSubmit={search}><label className="records-search"><Search size={16}/><span className="sr-only">Search Leads</span><input value={draft} maxLength={100} onChange={event=>setDraft(event.target.value)} placeholder="Search leads..."/></label><button type="submit">Search</button></form>
      {result.status === 'loading' && <div className="list-empty" role="status">Loading leads…</div>}
      {result.status === 'signed-out' && <div className="list-empty" role="alert">Your session is unavailable. Sign in to the workspace and try again.</div>}
      {result.status === 'denied' && <div className="list-empty" role="alert">You do not have permission to view leads.</div>}
      {result.status === 'error' && <div className="list-empty" role="alert"><strong>Leads could not be loaded.</strong><button onClick={()=>setAttempt(value=>value+1)}>Try again</button></div>}
      {result.status === 'ready' && <>
        {result.data.length === 0 ? <div className="list-empty"><strong>No leads found</strong><p>Try another search or check back later.</p></div> : <div className="table-wrap"><table><thead><tr><th>Lead</th><th>Source</th><th>Owner</th><th>Next follow-up</th><th>Status</th></tr></thead><tbody>{result.data.map(lead=><tr key={lead.id}><td><strong>{lead.contact_name || 'Unnamed contact'}</strong><br/><small>{lead.reference}</small></td><td>{lead.source_label || '—'}</td><td>{lead.owner_name || 'Unassigned'}</td><td>{formatDate(lead.next_action_at)}</td><td><span className="record-status status-neutral"><span aria-hidden="true" />{lead.stage?.name || '—'}</span></td></tr>)}</tbody></table></div>}
        <div className="records-footer"><span>Page {result.meta.current_page} of {result.meta.last_page} · {result.meta.total} leads</span><span><button disabled={page <= 1} onClick={()=>setPage(value=>value-1)}>Previous</button>{' · '}<button disabled={page >= result.meta.last_page} onClick={()=>setPage(value=>value+1)}>Next</button></span></div>
      </>}
    </section>
  </div>;
}
