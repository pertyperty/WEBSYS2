<?php

return [
    'enabled' => env('APP_ENV') !== 'testing',
    'mysql_client_directory' => env('MYSQL_CLIENT_BIN') ?: (PHP_OS_FAMILY === 'Windows' ? 'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin' : null),
    'record_application_errors' => env('APP_ENV') !== 'testing',
    'owner' => env('OPERATIONS_OWNER', 'Project administrator'),
    'backup_directory' => env('OPERATIONS_BACKUP_DIRECTORY'),
    'max_age_minutes' => ['scheduler' => 15, 'reconciliation' => 1560, 'backup' => 1560],
];
