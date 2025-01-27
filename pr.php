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
require_once PR_PLUGIN_PATH . 'includes/class-webhook-cron.php';


// Activate Hooks
register_activation_hook(__FILE__, ['PR_DB_Handler', 'install_tables']);
register_uninstall_hook(__FILE__, ['PR_DB_Handler', 'uninstall_tables']);

// Add Code Start -- AJAX Handler for CSV Import (pr.php)
function import_csv_ajax() {
    if (!current_user_can('manage_options')) die('Unauthorized');

    check_ajax_referer('pr_nonce', 'security');

    if (!isset($_FILES['file'])) {
        wp_send_json_error(['message' => 'No file uploaded'], 400);
        wp_die();
    }

    $file = $_FILES['file']['tmp_name'];
    $data = PR_Import_CSV::parse_csv($file);
    PR_Import_CSV::insert_contacts($data);

    wp_send_json_success(['message' => 'CSV Import Successful!']);
    wp_die();
}
add_action('wp_ajax_import_csv', 'import_csv_ajax');
// Add Code End -- AJAX Handler for CSV Import (pr.php)