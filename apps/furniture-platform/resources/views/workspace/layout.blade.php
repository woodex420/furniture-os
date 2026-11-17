<!doctype html>
<html lang="en" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('workspace-title', 'Dashboard') · WOODEX OS</title>
    @vite(['resources/css/workspace.css', 'resources/js/workspace.js'])
    @livewireStyles
</head>
<body>
@php
    // Navigation follows the approved preview; incomplete workflows remain labelled.
    $groups = [
        'WORKSPACE' => ['Dashboard', 'Approvals', 'Reports'],
        'CUSTOMERS' => ['Leads', 'Contacts', 'Tasks & Visits', 'Inbox'],
        'SALES' => ['Estimations', 'Quotations', 'Invoices', 'Payments', 'Documents'],
        'COMMERCE' => ['Store Overview', 'Products', 'Categories', 'Series', 'Orders', 'Delivery & Installation', 'Support & Warranty'],
        'OPERATIONS' => ['Operations Board', 'Projects', 'Vendors & Costs', 'Client Portal', 'Handovers'],
        'PROCUREMENT & PRODUCTION' => ['Suppliers', 'Purchase Orders', 'Goods Receipts', 'Supplier Invoices', 'Production', 'Stock Ledger', 'Warehouses', 'Stock Transfers', 'Reservations'],
        'ACCOUNTS' => ['Ledger & Reports', 'Payables', 'Income & Expenses'],
        'GROWTH' => ['Automations', 'Sequences', 'WhatsApp Templates', 'Marketing'],
        'WEBSITE STUDIO' => ['Site Overview', 'All Pages', 'Visual Page Builder', 'Navigation', 'Reusable Sections & Templates', 'Storefront / Shop Design', 'Service pages', 'City pages', 'Blog', 'Portfolio', 'Forms', 'Media Library', 'Theme & Design System', 'SEO', 'SEO Agent', 'Speed & Performance', 'Site Health', 'Website Settings', 'Redirects'],
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
        'Site Overview' => 'PanelTop', 'All Pages' => 'FileText', 'Visual Page Builder' => 'PanelTop', 'Navigation' => 'Layers3', 'Reusable Sections & Templates' => 'Layers3', 'Storefront / Shop Design' => 'Store', 'Service pages' => 'FileText', 'City pages' => 'FileText', 'Blog' => 'FileText', 'Portfolio' => 'BriefcaseBusiness', 'Forms' => 'FileText', 'Media Library' => 'FolderOpen', 'Theme & Design System' => 'Sparkles', 'SEO' => 'Search', 'SEO Agent' => 'Sparkles', 'Speed & Performance' => 'ChartNoAxesCombined', 'Site Health' => 'BadgeCheck', 'Website Settings' => 'Settings', 'Redirects' => 'ArrowRight',
        'Settings' => 'Settings', 'Access & Team' => 'Users', 'Connections' => 'Zap', 'Platform' => 'Layers3', 'Audit & Activity' => 'Clock3', 'Workspace Preferences' => 'SlidersHorizontal', 'Team Onboarding' => 'Users',
    ];
    $available = ['Dashboard' => ['admin', 'workspace.view'], 'Leads' => ['admin.leads', 'leads.view'], 'Contacts' => ['admin.contacts', 'contacts.view']];
    if (config('furniture.home_editor_enabled')) $available['Site Overview'] = ['admin.storefront.home', 'storefront.edit'];
    if (config('furniture.service_copy_enabled')) $available['Service pages'] = ['admin.website.services', 'storefront.edit'];
    if (config('furniture.city_pages_enabled')) $available['City pages'] = ['admin.website.cities', 'storefront.edit'];
    if (config('furniture.blog_posts_enabled')) $available['Blog'] = ['admin.website.blog', 'storefront.edit'];
    if (config('furniture.media_library_enabled')) $available['Media Library'] = ['admin.website.media', 'storefront.edit'];
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
                        @if (isset($available[$item]))
                            @can($available[$item][1])
                                @php($isActive = request()->routeIs($available[$item][0], $available[$item][0].'.show', $available[$item][0].'.create', $available[$item][0].'.edit'))
                                <a href="{{ route($available[$item][0], absolute: false) }}" class="nav-item {{ $isActive ? 'active' : '' }}" @if($isActive) aria-current="{{ request()->routeIs($available[$item][0]) ? 'page' : 'location' }}" @endif>@include('workspace.icon', ['icon' => $icons[$item]])<span>{{ $item }}</span>@if($item !== 'Dashboard')<small class="pending-badge">{{ ($item === 'Contacts' && config('furniture.contact_creation_enabled')) || ($item === 'Leads' && config('furniture.lead_creation_enabled')) || in_array($item, ['Site Overview', 'Service pages', 'City pages', 'Blog', 'Media Library'], true) ? 'Testing' : 'Read only' }}</small>@endif</a>
                            @endcan
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
            <div class="top-left"><button type="button" class="mobile-menu icon-button" data-workspace-menu hidden aria-controls="workspace-navigation" aria-expanded="false" aria-label="Open navigation">☰</button><span>Workspace</span><span aria-hidden="true">›</span><strong>@yield('workspace-title', 'Dashboard')</strong></div>
            <div class="top-actions"><div class="avatar" aria-hidden="true">W</div><span class="top-user">{{ auth()->user()->name }}</span><a class="workspace-signout" href="{{ route('admin.owner.password.edit', absolute: false) }}">Account security</a><form method="POST" action="{{ route('logout', absolute: false) }}">@csrf <button type="submit" class="workspace-signout">Sign out</button></form></div>
        </header>
        @yield('workspace-content')
    </main>
</div>
{{-- Livewire renders same-origin URLs using the request host; strip that origin so
     browser requests never target an internal reverse-proxy host. External configured assets remain unchanged. --}}
@php($workspaceLivewireScripts = \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts())
{!! str_replace(rtrim(url('/'), '/').'/', '/', $workspaceLivewireScripts) !!}
</body>
</html>
