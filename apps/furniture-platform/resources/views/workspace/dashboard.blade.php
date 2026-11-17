<!doctype html>
<html lang="en" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Dashboard · WOODEX OS</title>
    @vite(['resources/css/workspace.css', 'resources/js/workspace.js'])
    @livewireStyles
</head>
<body>
@php
    // Navigation follows the approved preview. Only Dashboard exists so far.
    $groups = [
        'WORKSPACE' => ['Dashboard', 'Approvals', 'Reports'],
        'CUSTOMERS' => ['Leads', 'Contacts', 'Tasks & Visits', 'Inbox'],
        'SALES' => ['Estimations', 'Quotations', 'Invoices', 'Payments', 'Documents'],
        'COMMERCE' => ['Store Overview', 'Products', 'Categories', 'Series', 'Orders', 'Delivery & Installation', 'Support & Warranty'],
        'OPERATIONS' => ['Operations Board', 'Projects', 'Vendors & Costs', 'Client Portal', 'Handovers'],
        'PROCUREMENT & PRODUCTION' => ['Suppliers', 'Purchase Orders', 'Goods Receipts', 'Supplier Invoices', 'Production', 'Stock Ledger', 'Warehouses', 'Stock Transfers', 'Reservations'],
        'ACCOUNTS' => ['Ledger & Reports', 'Payables', 'Income & Expenses'],
        'GROWTH' => ['Automations', 'Sequences', 'WhatsApp Templates', 'Marketing'],
        'WEBSITE STUDIO' => ['Site Overview', 'All Pages', 'Visual Page Builder', 'Navigation', 'Reusable Sections & Templates', 'Storefront / Shop Design', 'Blog', 'Portfolio', 'Forms', 'Media Library', 'Theme & Design System', 'SEO', 'SEO Agent', 'Speed & Performance', 'Site Health', 'Website Settings', 'Redirects'],
        'SYSTEM' => ['Settings', 'Access & Team', 'Connections', 'Platform', 'Audit & Activity', 'Workspace Preferences', 'Team Onboarding'],
    ];
    $icons = [
        'Dashboard' => 'LayoutDashboard', 'Approvals' => 'BadgeCheck', 'Reports' => 'ChartNoAxesCombined',
        'Leads' => 'Users', 'Contacts' => 'Users', 'Tasks & Visits' => 'CalendarDays', 'Inbox' => 'MessageSquare',
        'Estimations' => 'Calculator', 'Quotations' => 'FileText', 'Invoices' => 'Receipt', 'Payments' => 'CreditCard', 'Documents' => 'FolderOpen',
        'Store Overview' => 'Store', 'Products' => 'Package', 'Categories' => 'Tags', 'Series' => 'Layers3', 'Orders' => 'ShoppingBag', 'Delivery & Installation' => 'Truck', 'Support & Warranty' => 'Headphones',
        'Operations Board' => 'KanbanSquare', 'Projects' => 'BriefcaseBusiness', 'Vendors & Costs' => 'Handshake', 'Client Portal' => 'Users', 'Handovers' => 'BadgeCheck',
        'Suppliers' => 'Handshake', 'Purchase Orders' => 'FileText', 'Goods Receipts' => 'Package', 'Supplier Invoices' => 'Receipt', 'Production' => 'Factory', 'Stock Ledger' => 'Layers3', 'Warehouses' => 'Warehouse', 'Stock Transfers' => 'Truck', 'Reservations' => 'CalendarDays',
        'Ledger & Reports' => 'Wallet', 'Payables' => 'Receipt', 'Income & Expenses' => 'ChartNoAxesCombined', 'Automations' => 'Zap', 'Sequences' => 'Layers3', 'WhatsApp Templates' => 'MessageSquare', 'Marketing' => 'Megaphone',
        'Site Overview' => 'PanelTop', 'All Pages' => 'FileText', 'Visual Page Builder' => 'PanelTop', 'Navigation' => 'Layers3', 'Reusable Sections & Templates' => 'Layers3', 'Storefront / Shop Design' => 'Store', 'Blog' => 'FileText', 'Portfolio' => 'BriefcaseBusiness', 'Forms' => 'FileText', 'Media Library' => 'FolderOpen', 'Theme & Design System' => 'Sparkles', 'SEO' => 'Search', 'SEO Agent' => 'Sparkles', 'Speed & Performance' => 'ChartNoAxesCombined', 'Site Health' => 'BadgeCheck', 'Website Settings' => 'Settings', 'Redirects' => 'ArrowRight',
        'Settings' => 'Settings', 'Access & Team' => 'Users', 'Connections' => 'Zap', 'Platform' => 'Layers3', 'Audit & Activity' => 'Clock3', 'Workspace Preferences' => 'SlidersHorizontal', 'Team Onboarding' => 'Users',
    ];
@endphp
<div class="app">
    <div class="workspace-backdrop" data-workspace-backdrop hidden aria-hidden="true"></div>
    <aside class="sidebar" id="workspace-navigation" aria-label="Workspace navigation">
        <div class="brand">
            <span class="brand-mark" aria-hidden="true">W</span>
            <div class="brand-name">WOODEX <small>OS</small><div>THE OPERATING WORKSPACE</div></div>
            <button type="button" class="icon-button close-mobile" data-workspace-close hidden aria-label="Close navigation">×</button>
        </div>
        <div class="workspace-switch"><span class="workspace-icon" aria-hidden="true">W</span><span class="workspace-text"><strong>Woodex Furniture</strong><small>Workspace</small></span></div>
        <div class="side-search" aria-label="Workspace search pending"><span>Search pending</span></div>
        <nav class="nav-scroll" aria-label="Modules">
            @foreach ($groups as $group => $items)
                <details class="nav-group" @if(in_array($group, ['WORKSPACE', 'CUSTOMERS', 'SALES'], true)) open @endif>
                    <summary class="group-label"><span>{{ $group }}</span><span class="group-chevron" aria-hidden="true">⌄</span></summary>
                    @foreach ($items as $item)
                        @if ($item === 'Dashboard')
                            <a href="{{ route('admin') }}" class="nav-item active" aria-current="page">@include('workspace.icon', ['icon' => $icons[$item]])<span>Dashboard</span></a>
                        @else
                            <span class="nav-item pending-item">@include('workspace.icon', ['icon' => $icons[$item]])<span>{{ $item }}</span><small class="pending-badge">Pending</small></span>
                        @endif
                    @endforeach
                </details>
            @endforeach
        </nav>
        <div class="sidebar-bottom"><div class="avatar dark-avatar" aria-hidden="true">W</div><div class="profile-copy"><strong>{{ auth()->user()->name }}</strong><small>Owner workspace</small></div></div>
    </aside>
    <main class="main" id="main-content">
        <header class="topbar">
            <div class="top-left"><button type="button" class="mobile-menu icon-button" data-workspace-menu hidden aria-controls="workspace-navigation" aria-expanded="false" aria-label="Open navigation">☰</button><span>Workspace</span><span aria-hidden="true">›</span><strong>Dashboard</strong></div>
            <div class="top-actions"><div class="avatar" aria-hidden="true">W</div><span class="top-user">{{ auth()->user()->name }}</span><form method="POST" action="{{ route('logout') }}">@csrf <button type="submit" class="workspace-signout">Sign out</button></form></div>
        </header>
        <div class="content dashboard-v2">
            <div class="preview-strip"><div><span class="preview-dot"></span> <strong>INTEGRATION IN PROGRESS</strong><span class="preview-separator"></span> Dashboard shell is available; business data and workflows are pending.</div></div>
            <div class="dash-heading"><div><div class="dash-kicker"><span></span> YOUR WORKSPACE, AT A GLANCE</div><h1>Welcome back, {{ auth()->user()->name }}<span>.</span></h1><p>Everything you need to keep your business moving, in one place.</p></div></div>
            <livewire:workspace.dashboard-summary />
            <div class="dash-primary-grid">
                <section class="dash-card dash-sales"><div class="dash-card-head"><div><div class="dash-card-kicker">PERFORMANCE</div><h2>Sales overview</h2><p>Reporting integration pending</p></div></div><div class="pending-placeholder">Sales reporting is not connected yet.<br>No sales figures are shown.</div></section>
                <section class="dash-card dash-attention"><div class="dash-card-head"><div><div class="dash-card-kicker">YOUR PRIORITIES</div><h2>Needs your attention</h2><p>Approvals integration pending</p></div></div><div class="pending-placeholder">Approval workflows are not connected yet.<br>No requests are shown.</div></section>
            </div>
            <div class="dash-section-title lower-title"><div><h2>Keep things moving</h2><p>Sales, operations and activity will appear here when connected.</p></div><span>INTEGRATION PENDING</span></div>
            <div class="dash-secondary-grid">
                @foreach (['Sales pipeline', 'Work in motion', 'Recent activity'] as $panel)
                    <section class="dash-card"><div class="dash-card-head"><div><div class="dash-card-kicker">WORKSPACE</div><h2>{{ $panel }}</h2><p>Integration pending</p></div></div><p class="workspace-pending-note">No live information available yet.</p></section>
                @endforeach
            </div>
            <footer>WOODEX OS <span>Workspace integration in progress</span><span>Built for better work</span></footer>
        </div>
    </main>
</div>
@livewireScripts
</body>
</html>
