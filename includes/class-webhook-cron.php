<?php
class PR_Webhook_Cron {
    public static function schedule_event() {
        if (!wp_next_scheduled('pr_process_webhook_event')) {
            wp_schedule_event(time(), 'every_5_minutes', 'pr_process_webhook_event');
        }
    }

    public static function process_webhook_data() {
        $file_path = PR_PLUGIN_PATH . 'webhook_log.txt';

        if (!file_exists($file_path)) return;

        $data = PR_Webhook_Handler::process_webhook($file_path);

        if ($data) {
            unlink($file_path); // Delete file after successful processing
        }
    }

    public static function clear_scheduled_event() {
        wp_clear_scheduled_hook('pr_process_webhook_event');
    }
}

// Hook into WP Cron
add_action('pr_process_webhook_event', ['PR_Webhook_Cron', 'process_webhook_data']);
register_activation_hook(__FILE__, ['PR_Webhook_Cron', 'schedule_event']);
register_deactivation_hook(__FILE__, ['PR_Webhook_Cron', 'clear_scheduled_event']);

// Add custom interval (every 5 minutes)
add_filter('cron_schedules', function($schedules) {
    $schedules['every_5_minutes'] = [
        'interval' => 300, // 5 minutes
        'display'  => __('Every 5 Minutes')
    ];
    return $schedules;
});
?>
