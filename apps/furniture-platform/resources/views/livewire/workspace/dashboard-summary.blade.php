{{-- Integration candidate only: the approved shell remains in the separate preview. --}}
<section aria-labelledby="business-snapshot-title">
    <h2 id="business-snapshot-title">Business snapshot</h2>
    <p>Business metrics are pending integration. No live values are available here yet.</p>
    <div>
        @foreach (['Total sales', 'Open quotations', 'Outstanding', 'Active projects'] as $metric)
            <article>
                <h3>{{ $metric }}</h3>
                <p>Pending</p>
            </article>
        @endforeach
    </div>
</section>
