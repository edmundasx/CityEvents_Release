<?php
return [
    // This token should be kept secret and ideally loaded from environment variables in production.
    'admin_token' => getenv('CITYEVENTS_ADMIN_TOKEN') ?: 'change-me-admin-token',
];
