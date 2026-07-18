<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'env' => Env::get('APP_ENV', 'local'),
    'url' => Env::get('APP_URL', 'http://localhost:8000'),
    'key' => Env::get('APP_KEY', ''),
    'upload_max_mb' => (int) Env::get('UPLOAD_MAX_MB', 10),
    'session_lifetime_min' => (int) Env::get('SESSION_LIFETIME_MIN', 60),
];
