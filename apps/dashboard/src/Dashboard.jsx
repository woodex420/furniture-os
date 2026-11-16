import React from 'react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, ResponsiveContainer, Tooltip } from 'recharts';
import { ArrowRight, ArrowUpRight, BadgeCheck, CalendarDays, ChartNoAxesCombined, CheckCircle2, ChevronRight, Clock3, FileText, FolderKanban, Package, Receipt, ShoppingBag, Sparkles, Users, Wallet, Zap } from 'lucide-react';

const trend = [
  {day:'01', current:25, previous:20}, {day:'05', current:38, previous:25},
  {day:'10', current:33, previous:22}, {day:'15', current:51, previous:31},
  {day:'20', current:44, previous:29}, {day:'25', current:65, previous:40}, {day:'30', current:59, previous:37}
];
const kpis = [
  {title:'Total sales', value:'PKR 2,450,000', change:'+12.8%', note:'vs previous month', icon:Wallet, tone:'green', page:'Reports'},
  {title:'Open quotations', value:'24', change:'+4', note:'vs previous month', icon:FileText, tone:'blue', page:'Quotations'},
  {title:'Outstanding', value:'PKR 840,000', change:'8 invoices', note:'awaiting payment', icon:Receipt, tone:'amber', page:'Invoices'},
  {title:'Active projects', value:'18', change:'+3', note:'vs previous month', icon:FolderKanban, tone:'violet', page:'Projects'},
];
const requests = [
  {icon:FileText, tone:'amber', title:'Quotation approval', id:'QT-2024-081', person:'Ayesha Khan'},
  {icon:Package, tone:'violet', title:'Purchase order', id:'PO-2024-024', person:'Saad Hassan'},
  {icon:BadgeCheck, tone:'blue', title:'Discount request', id:'QT-2024-079', person:'Fatima Ahmed'},
];
const activities = [
  {initials:'AK', tone:'peach', name:'Ayesha Khan', action:'created a quotation', record:'QT-2024-082', time:'12 min ago', page:'Quotations'},
  {initials:'SH', tone:'blue', name:'Saad Hassan', action:'marked an invoice as paid', record:'INV-2024-056', time:'38 min ago', page:'Invoices'},
  {initials:'FA', tone:'purple', name:'Fatima Ahmed', action:'added a new lead', record:'Amina Malik', time:'2 hours ago', page:'Leads'},
  {initials:'MR', tone:'green', name:'Muhammad Rizwan', action:'updated a project', record:'DHA Residence', time:'4 hours ago', page:'Projects'}
];
const stages = [
  {label:'New leads', value:'48', width:'100%', page:'Leads'},
  {label:'Qualified', value:'32', width:'67%', page:'Leads'},
  {label:'Quotations sent', value:'18', width:'38%', page:'Quotations'},
  {label:'Accepted', value:'12', width:'25%', page:'Quotations'}
];

export default function Dashboard({go, explore}) {
 return <div className="content dashboard-v2">
   <div className="preview-strip"><div><span className="preview-dot"/> <strong>DESIGN PREVIEW</strong><span className="preview-separator"/> Sample data only — not connected to your business.</div><span>BACKEND PENDING · June 2024 · PKR</span></div>
   <div className="dash-heading"><div><div className="dash-kicker"><span/> YOUR WORKSPACE, AT A GLANCE</div><h1>Welcome back, Admin<span>.</span></h1><p>Everything you need to keep your business moving, in one place.</p></div><div className="dash-heading-actions"><span className="dash-date"><CalendarDays size={16}/> June 2024 <span>·</span> Sample period</span><button className="dash-primary" onClick={explore}>Explore workflows <ArrowRight size={16}/></button></div></div>
   <div className="dash-section-title"><div><h2>Business snapshot</h2><p>A little perspective on the bigger picture.</p></div><span>ILLUSTRATIVE OVERVIEW</span></div>
   <div className="dash-kpis">{kpis.map(({title,value,change,note,icon:Icon,tone,page})=><button className="dash-kpi" key={title} onClick={()=>go(page)}><div className="dash-kpi-top"><span className={'dash-icon '+tone}><Icon size={19}/></span><ArrowUpRight size={16}/></div><span className="dash-kpi-title">{title}</span><strong>{value}</strong><div><span className={'dash-change '+(tone==='amber'?'warn':'')}>{change}</span><span className="dash-kpi-note">{note}</span></div></button>)}</div>
   <div className="dash-primary-grid">
     <section className="dash-card dash-sales"><div className="dash-card-head"><div><div className="dash-card-kicker">PERFORMANCE</div><h2>Sales overview</h2><p>Illustrative sales trend · PKR thousands</p></div><button className="dash-card-link" onClick={()=>go('Reports')}>View reports <ArrowUpRight size={15}/></button></div><div className="dash-chart-summary"><div><span>MONTHLY SALES</span><div>PKR 2,450,000 <small>↗ 12.8%</small></div></div><div className="dash-legend"><span><i/> Jun 2024</span><span><i/> May 2024</span></div></div><div className="dash-chart"><ResponsiveContainer width="100%" height="100%"><AreaChart data={trend} margin={{top:15,right:10,left:-20,bottom:0}}><defs><linearGradient id="sales-fill-v2" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stopColor="#80ba56" stopOpacity={.22}/><stop offset="100%" stopColor="#80ba56" stopOpacity={0}/></linearGradient></defs><CartesianGrid vertical={false} stroke="#edf1ed" strokeDasharray="3 4"/><XAxis dataKey="day" axisLine={false} tickLine={false} tick={{fontSize:11,fill:'#9aa59e'}} dy={10}/><YAxis axisLine={false} tickLine={false} tick={{fontSize:11,fill:'#9aa59e'}} tickFormatter={v=>`${v}k`}/><Tooltip formatter={v=>`PKR ${v}k · example`}/><Area type="monotone" dataKey="previous" name="Previous period" stroke="#c3cec3" strokeWidth={2} strokeDasharray="4 5" fill="none"/><Area type="monotone" dataKey="current" name="Current period" stroke="#65a83c" strokeWidth={2.5} fill="url(#sales-fill-v2)"/></AreaChart></ResponsiveContainer></div><div className="dash-chart-foot"><span>June 1 – 30, 2024</span><span>Example values, not actual sales</span></div></section>
     <section className="dash-card dash-attention"><div className="dash-card-head"><div><div className="dash-card-kicker">YOUR PRIORITIES</div><h2>Needs your attention <span className="dash-count">3</span></h2><p>Example requests awaiting review.</p></div><span className="attention-spark"><Sparkles size={18}/></span></div><div className="dash-requests">{requests.map(({icon:Icon,tone,title,id,person})=><button key={id} onClick={()=>go('Approvals')}><span className={'request-icon '+tone}><Icon size={17}/></span><span className="request-copy"><strong>{title}</strong><small>{id} · {person}</small></span><span className="request-status">Pending</span><ChevronRight size={16}/></button>)}</div><button className="dash-wide-link" onClick={()=>go('Approvals')}>View approval queue <ArrowRight size={15}/></button><div className="dash-note"><span><CheckCircle2 size={16}/></span><p><strong>A clearer way to keep up.</strong> Your most important work, right where you need it.</p></div></section>
   </div>
   <div className="dash-section-title lower-title"><div><h2>Keep things moving</h2><p>Explore your sales, operations and recent activity.</p></div><span>SAMPLE WORKSPACE</span></div>
   <div className="dash-secondary-grid"><section className="dash-card funnel-card"><div className="dash-card-head"><div><div className="dash-card-kicker">CUSTOMER JOURNEY</div><h2>Sales pipeline</h2><p>Example activity for June 2024.</p></div><span className="mini-icon"><ChartNoAxesCombined size={17}/></span></div><div className="funnel-list">{stages.map(stage=><button key={stage.label} onClick={()=>go(stage.page)}><div><span>{stage.label}</span><strong>{stage.value}</strong></div><span className="funnel-track"><i style={{width:stage.width}}/></span></button>)}</div><button className="dash-card-link bottom-link" onClick={()=>go('Leads')}>Explore leads <ArrowRight size={15}/></button></section>
     <section className="dash-card operation-card"><div className="dash-card-head"><div><div className="dash-card-kicker">OPERATIONS</div><h2>Work in motion</h2><p>A snapshot across your workspace.</p></div><span className="mini-icon"><Zap size={17}/></span></div><div className="operation-list">{[{icon:ShoppingBag,title:'Orders in progress',value:'12',page:'Orders',tone:'blue'}, {icon:Package,title:'Production queue',value:'08',page:'Production',tone:'amber'}, {icon:FolderKanban,title:'Active projects',value:'18',page:'Projects',tone:'violet'}].map(({icon:Icon,title,value,page,tone})=><button key={title} onClick={()=>go(page)}><span className={'request-icon '+tone}><Icon size={16}/></span><span>{title}</span><strong>{value}</strong><ChevronRight size={15}/></button>)}</div><div className="operation-caption"><span/> Illustrative values · not live data</div></section>
     <section className="dash-card dash-activity"><div className="dash-card-head"><div><div className="dash-card-kicker">WORKSPACE UPDATES</div><h2>Recent activity</h2><p>Example activity from your team.</p></div><button className="dash-card-link" onClick={()=>go('Audit & Activity')}>View all <ArrowRight size={15}/></button></div><div className="dash-activity-list">{activities.map(a=><button key={a.initials} onClick={()=>go(a.page)}><span className={'person-avatar '+a.tone}>{a.initials}</span><span className="dash-activity-copy"><strong>{a.name}</strong> {a.action} <b>{a.record}</b><small>{a.time}</small></span><ChevronRight size={15}/></button>)}</div></section></div>
   <div className="dash-bottom"><div><span className="bottom-mark">W</span><div><strong>Made for the way you work.</strong><p>One workspace for the details that matter.</p></div></div><button onClick={explore}>Explore your workspace <ArrowRight size={15}/></button></div>
   <footer>WOODEX OS <span>Design preview · sample data</span><span>Built for better work</span></footer>
 </div>;
}
