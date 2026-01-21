<?php
/**
 * Global Configuration
 * Security Note: Ensure this file is ignored by git if it contains sensitive tokens.
 */

// Application Constants
define('APP_NAME', 'AI_ENGINE_PRO');
define('PROJECT_ROOT', __DIR__ . '/project_files'); // Directory where your code will live

// Security Configuration
// The password "classicboy0781" is stored here as a secure hash.
define('AUTH_PASSWORD_HASH', '$2y$10$7yM0U8O8p6m.eO0G.mC8SeP7IeQo2.7f6yXWz.G5u.vP0L8i.XyqG'); 
define('SESSION_EXPIRY', 1800); // 30 minutes in seconds

// GitHub Configuration
define('GH_TOKEN', 'YOUR_GITHUB_TOKEN_HERE');
define('GH_OWNER', 'YOUR_GITHUB_USERNAME');
define('GH_REPO', 'YOUR_REPO_NAME');

// Error Reporting (Disable in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Ensure the project root exists
if (!is_dir(PROJECT_ROOT)) {
    mkdir(PROJECT_ROOT, 0755, true);
}
