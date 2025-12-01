<?php

return [
    // When enabled, only agenda slots with type === 'workshop' allow check-in/out.
    'workshop_only_checkins' => env('FEATURE_WORKSHOP_ONLY_CHECKINS', false),
];
