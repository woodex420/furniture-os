<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Synthetic builder HTML proof</title>
    <style>
        body { max-width: 48rem; margin: 3rem auto; padding: 0 1rem; font-family: system-ui, sans-serif; line-height: 1.8; color: #292620; }
        .text-muted-foreground { color: #625d55; } .border-border { border-color: #ddd8cd; }
        .text-lg { font-size: 1.125rem; } .text-base { font-size: 1rem; }
        .leading-relaxed { line-height: 1.625; } .leading-\[1\.8\] { line-height: 1.8; }
        .mb-10 { margin-bottom: 2.5rem; } .pb-10 { padding-bottom: 2.5rem; }
        .border-b { border-bottom-width: 1px; border-bottom-style: solid; } .whitespace-pre-line { white-space: pre-line; }
    </style>
</head>
<body>
<main>
    <h1>Synthetic article HTML proof</h1>
    <p>Isolated experiment — not published storefront content.</p>
    <article data-builder-revision="{{ $revision }}">
        @foreach ($document['sections'] as $section)
            <section data-section-id="{{ $section['id'] }}">
                @include('proof.sections.article-text', ['props' => $section['props']])
            </section>
        @endforeach
    </article>
</main>
</body>
</html>
