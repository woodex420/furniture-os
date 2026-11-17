<section aria-labelledby="business-snapshot-title">
    <div class="dash-section-title"><div><h2 id="business-snapshot-title">Business snapshot</h2><p>Business metrics are pending integration. No live values are available here yet.</p></div><span>INTEGRATION PENDING</span></div>
    <div class="dash-kpis">
        @foreach ([['Total sales', 'green', 'Wallet'], ['Open quotations', 'blue', 'FileText'], ['Outstanding', 'amber', 'Receipt'], ['Active projects', 'violet', 'FolderKanban']] as [$metric, $tone, $icon])
            <article class="dash-kpi">
                <div class="dash-kpi-top"><span class="dash-icon {{ $tone }}">@include('workspace.icon', ['icon' => $icon])</span></div>
                <span class="dash-kpi-title">{{ $metric }}</span>
                <strong>Pending</strong>
                <span class="dash-kpi-note">Not connected yet</span>
            </article>
        @endforeach
    </div>
</section>
