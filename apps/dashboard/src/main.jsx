import React, { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, ResponsiveContainer, Tooltip } from 'recharts';
import { LayoutDashboard, BadgeCheck, ChartNoAxesCombined, Users, MessageSquare, Calculator, FileText, Receipt, CreditCard, FolderOpen, Store, Package, Tags, Layers3, ShoppingBag, Truck, Headphones, KanbanSquare, BriefcaseBusiness, Handshake, Factory, Warehouse, Wallet, Zap, Megaphone, PanelTop, Settings, ChevronDown, ChevronRight, ChevronsUpDown, Search, Plus, Bell, CircleHelp, ArrowUpRight, ArrowRight, Clock3, MoreHorizontal, CalendarDays, Menu, X, PanelLeftClose, Command, LogOut, SlidersHorizontal, ExternalLink, Check, Sparkles, CircleAlert } from 'lucide-react';
import '../../../shared/tokens.css';
import './style.css';
import PreviewWorkspace from './PreviewWorkspace';
import Dashboard from './Dashboard';

const groups = [
 {label:'WORKSPACE', items:[['Dashboard',LayoutDashboard],['Approvals',BadgeCheck],['Reports',ChartNoAxesCombined]]},
 {label:'CUSTOMERS', items:[['Leads',Users],['Contacts',Users],['Tasks & Visits',CalendarDays],['Inbox',MessageSquare]]},
 {label:'SALES', items:[['Estimations',Calculator],['Quotations',FileText],['Invoices',Receipt],['Payments',CreditCard],['Documents',FolderOpen]]},
 {label:'COMMERCE', items:[['Store Overview',Store],['Products',Package],['Categories',Tags],['Series',Layers3],['Orders',ShoppingBag],['Delivery & Installation',Truck],['Support & Warranty',Headphones]]},
 {label:'OPERATIONS', items:[['Operations Board',KanbanSquare],['Projects',BriefcaseBusiness],['Vendors & Costs',Handshake],['Client Portal',Users],['Handovers',BadgeCheck]]},
 {label:'PROCUREMENT & PRODUCTION', items:[['Suppliers',Handshake],['Purchase Orders',FileText],['Goods Receipts',Package],['Supplier Invoices',Receipt],['Production',Factory],['Stock Ledger',Layers3],['Warehouses',Warehouse],['Stock Transfers',Truck],['Reservations',CalendarDays]]},
 {label:'ACCOUNTS', items:[['Ledger & Reports',Wallet],['Payables',Receipt],['Income & Expenses',ChartNoAxesCombined]]},
 {label:'GROWTH', items:[['Automations',Zap],['Sequences',Layers3],['WhatsApp Templates',MessageSquare],['Marketing',Megaphone]]},
 {label:'WEBSITE STUDIO', items:[['Site Overview',PanelTop],['All Pages',FileText],['Visual Page Builder',PanelTop],['Navigation',Layers3],['Reusable Sections & Templates',Layers3],['Storefront / Shop Design',Store],['Blog',FileText],['Portfolio',BriefcaseBusiness],['Forms',FileText],['Media Library',FolderOpen],['Theme & Design System',Sparkles],['SEO',Search],['SEO Agent',Sparkles],['Speed & Performance',ChartNoAxesCombined],['Site Health',BadgeCheck],['Website Settings',Settings],['Redirects',ArrowRight]]},
 {label:'SYSTEM', items:[['Settings',Settings],['Access & Team',Users],['Connections',Zap],['Platform',Layers3],['Audit & Activity',Clock3],['Workspace Preferences',SlidersHorizontal],['Team Onboarding',Users]]}
];
const quick = [['Explore leads',Users,'Leads'],['View quotations',FileText,'Quotations'],['View invoices',Receipt,'Invoices'],['View products',Package,'Products']];
function App(){
 const [page,setPage]=useState('Dashboard'), [collapsed,setCollapsed]=useState(false), [mobile,setMobile]=useState(false), [open,setOpen]=useState(['WORKSPACE','CUSTOMERS','SALES']), [search,setSearch]=useState(false), [query,setQuery]=useState(''), [notice,setNotice]=useState(''), [modal,setModal]=useState('');
 useEffect(()=>{const fn=e=>{if((e.metaKey||e.ctrlKey)&&e.key.toLowerCase()==='k'){e.preventDefault();setSearch(true)} if(e.key==='Escape'){setSearch(false);setModal('');setMobile(false)}};window.addEventListener('keydown',fn);return()=>window.removeEventListener('keydown',fn)},[]);
 const all=groups.flatMap(g=>g.items.map(([name,Icon])=>({name,Icon,group:g.label})));
 const aliases = { Leads: 'crm enquiry customer', Contacts: 'crm people customers', 'Tasks & Visits': 'crm follow up reminders', 'All Pages': 'website studio cms', Products: 'catalogue inventory' };
 const results=useMemo(()=>all.filter(x=>`${x.name} ${x.group} ${aliases[x.name] || ''}`.toLowerCase().includes(query.toLowerCase())).slice(0,10),[query]);
 function go(name){setPage(name);const group=groups.find(g=>g.items.some(([item])=>item===name));if(group)setOpen(prev=>prev.includes(group.label)?prev:[...prev,group.label]);setSearch(false);setMobile(false);setQuery(''); window.scrollTo(0,0)}
 function toggle(label){setOpen(x=>x.includes(label)?x.filter(a=>a!==label):[...x,label])}
 return <div className={'app '+(collapsed?'compact':'')}>
 {mobile&&<div className="backdrop" onClick={()=>setMobile(false)}/>}
 <aside className={'sidebar '+(mobile?'mobile-open':'')}>
  <div className="brand"><button className="brand-mark" title="Toggle compact navigation" aria-label="Toggle compact navigation" onClick={()=>setCollapsed(!collapsed)}><span>W</span></button><div className="brand-name">WOODEX <small>OS</small><div>THE OPERATING WORKSPACE</div></div><button className="icon-button side-collapse" aria-label="Collapse sidebar" onClick={()=>setCollapsed(!collapsed)}><PanelLeftClose size={17}/></button><button className="icon-button close-mobile" aria-label="Close navigation" onClick={()=>setMobile(false)}><X size={19}/></button></div>
  <div className="workspace-switch"><span className="workspace-icon">W</span><span className="workspace-text"><strong>Woodex Furniture</strong><small>Workspace</small></span><ChevronsUpDown size={15}/></div>
  <button className="side-search" onClick={()=>setSearch(true)}><Search size={16}/><span>Search anything...</span><kbd>⌘ K</kbd></button>
  <div className="nav-scroll">{groups.map(g=><div className="nav-group" key={g.label}><button className="group-label" onClick={()=>toggle(g.label)}><span>{g.label}</span><ChevronDown size={13} className={open.includes(g.label)?'':'rotated'}/></button>{open.includes(g.label)&&g.items.map(([name,Icon])=><button key={name} title={name} className={'nav-item '+(page===name?'active':'')} onClick={()=>go(name)}><Icon size={17} strokeWidth={1.8}/><span>{name}</span>{name==='Approvals'&&<b>3</b>}</button>)}</div>)}</div>
  <div className="sidebar-bottom"><div className="avatar dark-avatar">AW</div><div className="profile-copy"><strong>Admin Workspace</strong><small>Administrator</small></div><ChevronsUpDown size={15}/></div>
 </aside>
 <main className="main"><header className="topbar"><div className="top-left"><button className="mobile-menu icon-button" aria-label="Open navigation" onClick={()=>setMobile(true)}><Menu size={20}/></button><span>Workspace</span><ChevronRight size={15}/><strong>{page}</strong></div><div className="top-actions"><button className="top-search" onClick={()=>setSearch(true)}><Search size={17}/><span>Search anything</span><kbd>⌘ K</kbd></button><button className="top-icon" aria-label="Help" onClick={()=>setNotice('Help and support will be available in the live application.')}><CircleHelp size={19}/></button><button className="top-icon notification" aria-label="Notifications" onClick={()=>setNotice('You’re all caught up. This is a demo preview.')}><Bell size={19}/><i/></button><div className="top-divider"/><div className="avatar">AW</div><span className="top-user">Admin <ChevronDown size={14}/></span></div></header>
 {notice&&<div className="toast" role="status">{notice}<button onClick={()=>setNotice('')} aria-label="Dismiss"><X size={15}/></button></div>}
 {page==='Dashboard'?<Dashboard go={go} explore={()=>setModal('Explore workflows')}/>:<PreviewWorkspace key={page} page={page} go={go}/>}
 </main>
 {search&&<div className="modal-backdrop" onClick={()=>setSearch(false)}><div className="command" role="dialog" aria-modal="true" aria-label="Search navigation" onClick={e=>e.stopPropagation()}><div className="command-input"><Search size={20}/><input autoFocus placeholder="Search pages and modules..." value={query} onChange={e=>setQuery(e.target.value)}/><button onClick={()=>setSearch(false)}>ESC</button></div><div className="command-results"><p>QUICK NAVIGATION</p>{results.map(({name,Icon,group})=><button key={name} onClick={()=>go(name)}><Icon size={17}/><span>{name}</span><small>{group}</small><ArrowRight size={15}/></button>)}{!results.length&&<div className="no-results">No pages found for “{query}”.</div>}</div><div className="command-footer">Search the Woodex workspace <span>↵ to open</span></div></div></div>}
 {modal&&<div className="modal-backdrop" onClick={()=>setModal('')}><div className="create-modal" role="dialog" aria-modal="true" aria-label="Explore workflows" onClick={e=>e.stopPropagation()}><div className="card-heading"><div><h3>Explore workflows</h3><p>Choose a preview to explore.</p></div><button className="icon-button" onClick={()=>setModal('')} aria-label="Close"><X size={19}/></button></div>{quick.map(([name,Icon,p])=><button className="create-option" key={name} onClick={()=>{setModal('');go(p)}}><Icon size={18}/>{name}<ArrowRight size={16}/></button>)}<p className="modal-note">Actions and business workflows are not connected in this preview.</p></div></div>}
 </div>
}

createRoot(document.getElementById('root')).render(<App/>);
