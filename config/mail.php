<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'host' => Env::get('MAIL_HOST', ''),
    'port' => (int) Env::get('MAIL_PORT', 587),
    'username' => Env::get('MAIL_USER', ''),
    'password' => Env::get('MAIL_PASS', ''),
    'from' => Env::get('MAIL_FROM', 'nao-responder@sagdoc.gov.gw'),
    'from_name' => Env::get('MAIL_FROM_NAME', 'SAGDOC'),
];
