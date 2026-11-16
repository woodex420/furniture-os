import React, { useEffect, useMemo, useRef, useState } from 'react';
import { ArrowLeft, ArrowRight, ChevronDown, FileText, Layers3, LockKeyhole, Search, Sparkles, X } from 'lucide-react';
import ConnectedLeads from './ConnectedLeads';

// Illustrative records only: these are not a source of business data or permissions.
const workspaces = {
  Leads: { description: 'Keep track of new enquiries and the next step for each customer.', singular: 'lead', columns: ['Lead', 'Source', 'Owner', 'Next follow-up', 'Status'], rows: [
    ['Amina Malik', 'Website enquiry', 'Ayesha Khan', '18 Jun 2024', 'New'], ['Usman Ali', 'Referral', 'Saad Hassan', '20 Jun 2024', 'Qualified'], ['Sara Ahmed', 'Store visit', 'Ayesha Khan', '21 Jun 2024', 'Contacted'], ['Bilal Raza', 'Website enquiry', 'Fatima Ahmed', '24 Jun 2024', 'New'] ] },
  Contacts: { description: 'A single view of the people and customers you work with.', singular: 'contact', columns: ['Contact', 'Email', 'Phone', 'Owner', 'Status'], rows: [
    ['Amina Malik', 'amina@example.com', '+92 300 000 0001', 'Ayesha Khan', 'Active'], ['Usman Ali', 'usman@example.com', '+92 300 000 0002', 'Saad Hassan', 'Active'], ['Sara Ahmed', 'sara@example.com', '+92 300 000 0003', 'Ayesha Khan', 'Active'] ] },
  'Tasks & Visits': { description: 'Organize follow-ups, visits and the work ahead.', singular: 'task', columns: ['Task', 'Related to', 'Owner', 'Due date', 'Status'], rows: [
    ['Share material samples', 'Amina Malik', 'Ayesha Khan', '18 Jun 2024', 'Open'], ['Site measurement', 'Sara Ahmed', 'Saad Hassan', '20 Jun 2024', 'Scheduled'], ['Follow up on enquiry', 'Usman Ali', 'Fatima Ahmed', '21 Jun 2024', 'Open'] ] },
  Approvals: { description: 'Review requests across your workspace in one place.', singular: 'request', columns: ['Request', 'Type', 'Requested by', 'Amount', 'Status'], rows: [
    ['QT-2024-081', 'Quotation approval', 'Ayesha Khan', 'PKR 240,000', 'Pending'], ['PO-2024-024', 'Purchase order', 'Saad Hassan', 'PKR 180,000', 'Pending'], ['QT-2024-079', 'Discount request', 'Fatima Ahmed', 'PKR 95,000', 'Pending'] ] },
  Quotations: { description: 'Follow quotations from draft through customer decision.', singular: 'quotation', columns: ['Quotation', 'Customer', 'Owner', 'Amount', 'Status'], rows: [
    ['QT-2024-082', 'Amina Malik', 'Ayesha Khan', 'PKR 125,000', 'Draft'], ['QT-2024-081', 'Usman Ali', 'Ayesha Khan', 'PKR 240,000', 'Pending'], ['QT-2024-080', 'Sara Ahmed', 'Saad Hassan', 'PKR 185,000', 'Sent'], ['QT-2024-079', 'Bilal Raza', 'Fatima Ahmed', 'PKR 95,000', 'Pending'] ] },
  Invoices: { description: 'View issued invoices and follow outstanding balances.', singular: 'invoice', columns: ['Invoice', 'Customer', 'Due date', 'Amount', 'Status'], rows: [
    ['INV-2024-058', 'Amina Malik', '25 Jun 2024', 'PKR 125,000', 'Outstanding'], ['INV-2024-057', 'Usman Ali', '28 Jun 2024', 'PKR 240,000', 'Outstanding'], ['INV-2024-056', 'Sara Ahmed', '10 Jun 2024', 'PKR 185,000', 'Paid'] ] },
  Products: { description: 'Manage the catalogue that powers your storefront.', singular: 'product', columns: ['Product', 'SKU', 'Category', 'Price', 'Status'], rows: [
    ['Oak lounge chair', 'CHR-001', 'Living room', 'PKR 48,000', 'Published'], ['Walnut dining table', 'TBL-002', 'Dining room', 'PKR 125,000', 'Published'], ['Linen sofa', 'SOF-003', 'Living room', 'PKR 195,000', 'Draft'] ] },
  Orders: { description: 'See customer orders and their fulfilment progress.', singular: 'order', columns: ['Order', 'Customer', 'Placed on', 'Amount', 'Status'], rows: [
    ['ORD-2024-034', 'Amina Malik', '12 Jun 2024', 'PKR 125,000', 'Processing'], ['ORD-2024-033', 'Usman Ali', '10 Jun 2024', 'PKR 240,000', 'Confirmed'], ['ORD-2024-032', 'Sara Ahmed', '08 Jun 2024', 'PKR 185,000', 'Delivered'] ] },
  Projects: { description: 'Keep your projects, milestones and handovers in view.', singular: 'project', columns: ['Project', 'Client', 'Owner', 'Target date', 'Status'], rows: [
    ['DHA Residence', 'Amina Malik', 'Muhammad Rizwan', '30 Jul 2024', 'In progress'], ['Garden Home', 'Usman Ali', 'Saad Hassan', '14 Aug 2024', 'Planning'], ['Parkside Office', 'Sara Ahmed', 'Fatima Ahmed', '22 Aug 2024', 'In progress'] ] },
  'All Pages': { description: 'Organize your website pages and their publication status.', singular: 'page', columns: ['Page', 'Path', 'Updated by', 'Updated', 'Status'], rows: [
    ['Home', '/', 'Ayesha Khan', '12 Jun 2024', 'Published'], ['About us', '/about', 'Fatima Ahmed', '10 Jun 2024', 'Published'], ['Summer collection', '/summer', 'Saad Hassan', '08 Jun 2024', 'Draft'] ] },
};

// Development labels describe this isolated preview, not the Laravel source or a production release.
const nextSteps = {
  Leads: 'Map lead fields and connect the Laravel CRM.', Contacts: 'Map contact records and permissions.',
  'Tasks & Visits': 'Connect follow-ups and visit scheduling.', Approvals: 'Map approval policies and audit history.',
  Quotations: 'Connect the existing quotation actions and totals.', Invoices: 'Connect invoice records and issued-document rules.',
  Products: 'Design the catalog contract and product database.', Orders: 'Implement the order contract and fulfillment flow.',
  Projects: 'Map projects, milestones and access rules.', 'All Pages': 'Connect page records and draft/publish states.',
  'Visual Page Builder': 'Integrate the Laravel builder and storefront renderer.',
  'Storefront / Shop Design': 'Define product and category templates against the catalog.',
  'Media Library': 'Connect the secured media library and uploads.', SEO: 'Map metadata, redirects and publication.',
};
const nextStep = page => nextSteps[page] || 'Audit the existing Laravel feature and map its integration.';

const positive = ['Active', 'Paid', 'Published', 'Delivered', 'Qualified'];
const warm = ['Pending', 'Outstanding', 'New', 'Open'];
function Status({ value }) { return <span className={'record-status ' + (positive.includes(value) ? 'status-positive' : warm.includes(value) ? 'status-warm' : 'status-neutral')}><span aria-hidden="true" />{value}</span>; }

export default function PreviewWorkspace({ page, go, leadsMode = 'sample', leadsEndpoint }) {
  if (page === 'Leads' && leadsMode === 'connected') return <ConnectedLeads endpoint={leadsEndpoint} />;
  return <SampleWorkspace page={page} go={go} />;
}

function SampleWorkspace({ page, go }) {
  const config = workspaces[page];
  const [query, setQuery] = useState('');
  const [status, setStatus] = useState('All statuses');
  const [selected, setSelected] = useState(null);
  const closeRef = useRef(null);
  const panelRef = useRef(null);
  useEffect(() => {
    if (!selected) return;
    const previous = document.activeElement;
    closeRef.current?.focus();
    const keydown = e => {
      if (e.key === 'Escape') setSelected(null);
      if (e.key === 'Tab') {
        const buttons = [...panelRef.current.querySelectorAll('button:not([disabled])')];
        if (!buttons.length) return;
        if (e.shiftKey && document.activeElement === buttons[0]) { e.preventDefault(); buttons[buttons.length - 1].focus(); }
        else if (!e.shiftKey && document.activeElement === buttons[buttons.length - 1]) { e.preventDefault(); buttons[0].focus(); }
      }
    };
    document.addEventListener('keydown', keydown);
    return () => { document.removeEventListener('keydown', keydown); previous?.focus(); };
  }, [selected]);
  const rows = useMemo(() => !config ? [] : config.rows.filter(row => row.join(' ').toLowerCase().includes(query.toLowerCase()) && (status === 'All statuses' || row[4] === status)), [config, query, status]);
  if (!config) return <ConceptWorkspace page={page} go={go}/>;
  const statuses = [...new Set(config.rows.map(row => row[4]))];
  return <div className="content list-page">
    <div className="demo-banner"><span>INTEGRATION PENDING</span> Illustrative records only. Next: {nextStep(page)}</div>
    <div className="page-heading list-heading"><div><div className="eyebrow">WORKSPACE <span>/</span> {page.toUpperCase()}</div><h1>{page}</h1><p>{config.description}</p></div><span className="preview-label">PREVIEW UPDATED · INTEGRATION PENDING</span></div>
    <div className="list-overview"><div><span>WORKSPACE</span><strong>{page}</strong><small>A clearer view of your daily work</small></div><div className="overview-count"><span>EXAMPLE RECORDS</span><strong>{config.rows.length.toString().padStart(2,'0')}</strong><small>For preview purposes only</small></div></div>
    <section className="card records-card" aria-label={`${page} example records`}>
      <div className="records-title"><div><h2>All {page.toLowerCase()}</h2><p>Browse the example records below.</p></div><span className="sample-chip">Sample data</span></div>
      <div className="records-tools"><label className="records-search"><Search size={16}/><span className="sr-only">Search {page}</span><input value={query} onChange={e=>setQuery(e.target.value)} placeholder={`Search ${page.toLowerCase()}...`}/></label><label className="records-filter"><span className="sr-only">Filter by status</span><select value={status} onChange={e=>setStatus(e.target.value)}><option>All statuses</option>{statuses.map(s=><option key={s}>{s}</option>)}</select><ChevronDown size={14}/></label></div>
      <div className="table-wrap"><table><thead><tr>{config.columns.map(c=><th key={c}>{c}</th>)}<th><span className="sr-only">Details</span></th></tr></thead><tbody>{rows.map((row,i)=><tr key={row[0]}><td><button className="record-name" onClick={()=>setSelected(row)}><span className="record-initial">{row[0].charAt(0)}</span>{row[0]}</button></td>{row.slice(1,4).map((cell,index)=><td key={index}>{cell}</td>)}<td><Status value={row[4]}/></td><td><button className="row-open" onClick={()=>setSelected(row)} aria-label={`View ${row[0]}`}><ArrowRight size={16}/></button></td></tr>)}</tbody></table></div>
      {rows.length===0&&<div className="list-empty"><Search size={20}/><strong>No matching records</strong><p>Try a different search or status filter.</p><button onClick={()=>{setQuery('');setStatus('All statuses')}}>Clear filters</button></div>}
      <div className="records-footer">Showing {rows.length} of {config.rows.length} example records <span>Read-only · Preview data</span></div>
    </section>
    <div className="preview-explainer"><div className="preview-explainer-icon"><FileText size={18}/></div><div><strong>Designed for the way your team works</strong><p>List and detail layouts are shown for review. Real records, permissions and actions will require an approved backend.</p></div><button onClick={()=>go('Dashboard')}>Back to overview <ArrowRight size={15}/></button></div>
    {selected&&<div className="detail-backdrop" onClick={()=>setSelected(null)}><aside ref={panelRef} className="detail-panel" role="dialog" aria-modal="true" aria-label={`${selected[0]} details`} onClick={e=>e.stopPropagation()}><div className="detail-top"><button onClick={()=>setSelected(null)}><ArrowLeft size={16}/> Back to {page.toLowerCase()}</button><button ref={closeRef} aria-label="Close details" onClick={()=>setSelected(null)}><X size={19}/></button></div><div className="detail-body"><div className="eyebrow">EXAMPLE {config.singular.toUpperCase()}</div><h2>{selected[0]}</h2><Status value={selected[4]}/><p className="detail-intro">This is an illustrative record for reviewing the layout. No business action can be taken here.</p><h3>Details</h3><dl>{config.columns.slice(1,4).map((col,i)=><div key={col}><dt>{col}</dt><dd>{selected[i+1]}</dd></div>)}</dl><h3>Activity</h3><div className="detail-activity"><span/><div><strong>Example record added</strong><p>18 Jun 2024 · Preview only</p></div></div>{page==='Approvals'&&<div className="detail-note">Approval decisions, audit history and supporting documents require a connected and authorized workflow.</div>}</div></aside></div>}
  </div>;
}


const concepts = {
  'Visual Page Builder': ['Compose pages with approved building blocks', 'Arrange reusable sections and preview your design across devices.', 'Drafts and publishing are separate steps, with validation and approval before anything goes live.', 'All Pages'],
  'Storefront / Shop Design': ['Shape the storefront experience', 'Explore layouts for product listings and product detail pages.', 'Products, prices and stock remain owned by the catalogue, not the page editor.', 'Products'],
  'Site Overview': ['Your website, all in one place', 'Bring pages, navigation, content and publishing into a focused workspace.', 'Page changes need an authorized review and publication workflow.', 'All Pages'],
  'Production': ['Keep production moving', 'Coordinate jobs, stages and responsible teams from one operational view.', 'Production events and material consumption require an approved backend workflow.', 'Orders'],
  'Reports': ['Make sense of the bigger picture', 'Find business reports with clear filters, dates and metric definitions.', 'Reports and exports require real data and permission checks.', 'Dashboard'],
  'Inbox': ['Keep every conversation in context', 'Bring customer conversations and related work together.', 'Messaging channels and delivery states require approved provider connections.', 'Contacts'],
};
function ConceptWorkspace({page,go}) {
  const [headline, description, note, related] = concepts[page] || [`A dedicated space for ${page.toLowerCase()}`, 'Bring the information and next steps your team needs into one focused view.', 'Records, permissions and actions require an approved and connected workflow.', 'Dashboard'];
  return <div className="content concept-page">
    <div className="preview-strip"><div><span className="preview-dot"/><strong>INTEGRATION PENDING</strong><span className="preview-separator"/> Concept only. Next: {nextStep(page)}</div></div>
    <div className="page-heading list-heading"><div><div className="eyebrow">WORKSPACE <span>/</span> {page.toUpperCase()}</div><h1>{page}</h1><p>{description}</p></div><span className="preview-label">PENDING · CONCEPT VIEW</span></div>
    <div className="concept-hero"><div><div className="concept-kicker"><Sparkles size={14}/> A MORE CONNECTED WORKSPACE</div><h2>{headline}<span>.</span></h2><p>{description}</p><button onClick={()=>go(related)}>Explore {related.toLowerCase()} <ArrowRight size={15}/></button></div><div className="concept-art" aria-hidden="true"><div className="art-window"><div className="art-top"><i/><i/><i/></div><div className="art-body"><div className="art-nav"><b/><b/><b/><b/></div><div className="art-content"><strong/><span/><div><i/><i/><i/></div><span/><span/></div></div></div><span className="art-orbit one"/><span className="art-orbit two"/></div></div>
    <div className="concept-grid"><div className="card concept-card"><span className="concept-icon"><Layers3 size={19}/></span><h3>Designed around your work</h3><p>A consistent home for records, context and next steps, with shared patterns across the workspace.</p></div><div className="card concept-card"><span className="concept-icon blue"><LockKeyhole size={19}/></span><h3>Built with care</h3><p>{note}</p></div><div className="card concept-card"><span className="concept-icon orange"><FileText size={19}/></span><h3>Preview, not production</h3><p>This module is not implemented or connected. Its workflows, permissions, and data are subject to approval.</p></div></div>
    <div className="concept-next"><div><strong>Continue exploring</strong><p>See how the workspace comes together with sample records.</p></div><button onClick={()=>go('Dashboard')}>Back to dashboard <ArrowRight size={15}/></button></div>
  </div>;
}
