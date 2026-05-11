<?php
// Application Base URL
define('BASE_URL', '/itec_contract_system');

// Application Paths
define('BASE_PATH', dirname(__DIR__));
define('STORAGE_PATH', BASE_PATH . '/storage');
define('CONTRACTS_STORAGE', STORAGE_PATH . '/contracts');
define('SNAPSHOTS_STORAGE', STORAGE_PATH . '/snapshots');

// Database (from your existing setup)
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'itec_contracts');
// define('DB_USER', 'root');
// define('DB_PASS', '');

// Date Formats
define('DATE_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'M d, Y g:i A');

// Upload Limits
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// Company Seal
define('COMPANY_SEAL_PATH', BASE_PATH . '/storage/seal.png');
define('COMPANY_NAME', 'ITEC LTD');