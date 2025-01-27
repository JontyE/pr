<?php
/**
 * Plugin Name: Prompt Roofing Guarantee
 * Plugin URI: https://esser.digital/
 * Description: A WordPress plugin to manage roofing guarantees.
 * Version: 1.0.0
 * Author: Esser Digital
 * Author URI: https://esser.digital/
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

define('PR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Ensure all class files are included correctly
require_once PR_PLUGIN_PATH . 'includes/class-db-handler.php';
require_once PR_PLUGIN_PATH . 'includes/class-import-csv.php';
require_once PR_PLUGIN_PATH . 'includes/class-webhook-handler.php';
require_once PR_PLUGIN_PATH . 'includes/class-webhook-cron.php';
require_once PR_PLUGIN_PATH . 'includes/class-guarantee.php';
require_once PR_PLUGIN_PATH . 'includes/class-document-generator.php';
require_once PR_PLUGIN_PATH . 'includes/class-shortcodes.php';
require_once PR_PLUGIN_PATH . 'includes/class-admin.php';




// Register activation and deactivation hooks
register_activation_hook(__FILE__, ['PR_DB_Handler', 'install_tables']);
register_deactivation_hook(__FILE__, ['PR_DB_Handler', 'uninstall_tables']);
register_activation_hook(__FILE__, ['PR_Webhook_Cron', 'schedule_event']);
register_deactivation_hook(__FILE__, ['PR_Webhook_Cron', 'clear_scheduled_event']);


function import_csv_ajax() {
    check_ajax_referer('pr_nonce', 'security');

    if (!isset($_FILES['file'])) {
        wp_send_json_error(['message' => 'No file uploaded.']);
        return;
    }

    $file = $_FILES['file'];
    $file_path = $file['tmp_name'];

    error_log("🚀 File received: " . $file_path);

    if (!file_exists($file_path)) {
        wp_send_json_error(['message' => 'Uploaded file not found.']);
        return;
    }

    require_once plugin_dir_path(__FILE__) . 'includes/class-import-csv.php';

    $csv_data = PR_Import_CSV::parse_csv($file_path);

    if (empty($csv_data)) {
        wp_send_json_error(['message' => 'CSV parsing failed or file is empty.']);
        return;
    }

    PR_Import_CSV::insert_contacts($csv_data);
    wp_send_json_success(['message' => 'CSV imported successfully.', 'total_rows' => count($csv_data)]);
}

// Ensure AJAX handlers are correctly registered
add_action('wp_ajax_import_csv', 'import_csv_ajax'); // Logged-in users only
add_action('wp_ajax_nopriv_import_csv', 'import_csv_ajax'); // Allow guests if needed

