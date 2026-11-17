<?php

declare(strict_types=1);

return [
    // Experimental owner-only workflow. Enable explicitly in an isolated environment;
    // MySQL concurrency, deployment and business policy have not been verified.
    'contact_creation_enabled' => (bool) env('FURNITURE_CONTACT_CREATION_ENABLED', false),
    'lead_creation_enabled' => (bool) env('FURNITURE_LEAD_CREATION_ENABLED', false),
    'home_editor_enabled' => (bool) env('FURNITURE_HOME_EDITOR_ENABLED', false),
    // Temporary service copy bridge for isolated storefront integration.
    'service_copy_enabled' => (bool) env('FURNITURE_SERVICE_COPY_ENABLED', false),
    // Independent synthetic service-area pages; off outside an explicitly enabled preview.
    'city_pages_enabled' => (bool) env('FURNITURE_CITY_PAGES_ENABLED', false),
    // Furniture-owned blog experiment; disabled until explicitly enabled.
    'blog_posts_enabled' => (bool) env('FURNITURE_BLOG_POSTS_ENABLED', false),
    // Isolated, owner-only media experiment; disabled until explicitly enabled.
    'media_library_enabled' => (bool) env('FURNITURE_MEDIA_LIBRARY_ENABLED', false),
    // Optional, explicitly configured storefront origin for the visual preview.
    // This does not enable authentication or publishing on the storefront.
    'storefront_canvas_url' => env('FURNITURE_STOREFRONT_CANVAS_URL'),
];
