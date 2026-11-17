@extends('workspace.layout')

@section('workspace-title', 'Dashboard')

@section('workspace-content')
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
@endsection
