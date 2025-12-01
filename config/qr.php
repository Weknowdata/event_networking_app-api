<?php

return [
    // Secret used to sign and validate QR tokens for session scans.
    'signing_secret' => env('QR_SIGNING_SECRET', env('APP_KEY', '')),

    // TTL (in seconds) for session QR tokens issued to clients.
    'slot_token_ttl_seconds' => env('QR_SLOT_TOKEN_TTL_SECONDS', 900),
];
