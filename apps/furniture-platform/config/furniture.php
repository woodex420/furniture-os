<?php

declare(strict_types=1);

return [
    // Experimental owner-only workflow. Enable explicitly in an isolated environment;
    // MySQL concurrency, deployment and business policy have not been verified.
    'contact_creation_enabled' => (bool) env('FURNITURE_CONTACT_CREATION_ENABLED', false),
    'lead_creation_enabled' => (bool) env('FURNITURE_LEAD_CREATION_ENABLED', false),
    // Limited title/source editing, separately enabled only for testing.
    'lead_editing_enabled' => (bool) env('FURNITURE_LEAD_EDITING_ENABLED', false),
    'home_editor_enabled' => (bool) env('FURNITURE_HOME_EDITOR_ENABLED', false),
    // Temporary service copy bridge for isolated storefront integration.
    'service_copy_enabled' => (bool) env('FURNITURE_SERVICE_COPY_ENABLED', false),
    // Independent synthetic service-area pages; off outside an explicitly enabled preview.
    'city_pages_enabled' => (bool) env('FURNITURE_CITY_PAGES_ENABLED', false),
    // Furniture-owned blog experiment; disabled until explicitly enabled.
    'blog_posts_enabled' => (bool) env('FURNITURE_BLOG_POSTS_ENABLED', false),
    // Synthetic local/testing-only experiment, never a production publishing switch.
    'builder_html_proof_enabled' => (bool) env('FURNITURE_BUILDER_HTML_PROOF_ENABLED', false),
    // Experimental same-origin storefront shell, off by default. The bundle must be
    // built separately; this flag does not make demo commerce or login production-ready.
    'storefront_bundle_enabled' => (bool) env('FURNITURE_STOREFRONT_BUNDLE_ENABLED', false),
    'storefront_bundle_index' => storage_path('app/storefront-preview/index.html'),
    // Dedicated synthetic admin preview only: send its root to the authenticated workspace.
    'admin_preview_landing' => (bool) env('FURNITURE_ADMIN_PREVIEW_LANDING', false),
    // Higher but still bounded limits for disposable, shared-IP synthetic previews only.
    'synthetic_preview_login_limit' => (bool) env('FURNITURE_SYNTHETIC_PREVIEW_LOGIN_LIMIT', false),
    // Isolated, owner-only media experiment; disabled until explicitly enabled.
    'media_library_enabled' => (bool) env('FURNITURE_MEDIA_LIBRARY_ENABLED', false),
    // Optional, explicitly configured storefront origin for the visual preview.
    // This does not enable authentication or publishing on the storefront.
    'storefront_canvas_url' => env('FURNITURE_STOREFRONT_CANVAS_URL'),
];
