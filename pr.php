<?php
/**
 * Plugin Name: Prompt Roofing Guarantee
 * Plugin URI: https://esser.digital/
 * Description: Imports CSV and JSON data, manages roofing quotes, and provides a guarantee feature.
 * Version: 1.0.0
 * Author: Esser Digital
 * Author URI: https://esser.digital/
 * License: GPL v2 or later
 * Text Domain: pr
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

define('PR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include Core Classes
require_once PR_PLUGIN_PATH . 'includes/class-db-handler.php';
require_once PR_PLUGIN_PATH . 'includes/class-import-csv.php';
require_once PR_PLUGIN_PATH . 'includes/class-webhook-handler.php';
require_once PR_PLUGIN_PATH . 'includes/class-guarantee.php';
require_once PR_PLUGIN_PATH . 'includes/class-shortcodes.php';
require_once PR_PLUGIN_PATH . 'includes/class-admin.php';

// Activate Hooks
register_activation_hook(__FILE__, ['PR_DB_Handler', 'install_tables']);
register_uninstall_hook(__FILE__, ['PR_DB_Handler', 'uninstall_tables']);
