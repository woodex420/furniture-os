<?php

declare(strict_types=1);

return [
    // Experimental owner-only workflow. Enable explicitly in an isolated environment;
    // MySQL concurrency, deployment and business policy have not been verified.
    'contact_creation_enabled' => (bool) env('FURNITURE_CONTACT_CREATION_ENABLED', false),
    'lead_creation_enabled' => (bool) env('FURNITURE_LEAD_CREATION_ENABLED', false),
];
