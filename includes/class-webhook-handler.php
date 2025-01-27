<?php

class PR_Webhook_Handler {
    public static function process_webhook($file_path) {
        global $wpdb;
        $table_prefix = $wpdb->prefix;

        $data = json_decode(file_get_contents($file_path), true);
        if (!$data) return false;

        foreach ($data as $event) {
            $contact_id = $wpdb->get_var(
                $wpdb->prepare("SELECT id FROM {$table_prefix}contacts WHERE email = %s", $event['quote_for']['email'])
            );

            if (!$contact_id) continue;

            $wpdb->replace("{$table_prefix}quotes", [
                'quote_number' => sanitize_text_field($event['quote_number']),
                'contact_id'   => $contact_id,
                'status'       => sanitize_text_field($event['quote_status']),
                'valid_until'  => date('Y-m-d', strtotime($event['valid_until'])),
                'total_price'  => floatval($event['total_excludes_tax'])
            ]);
        }

        return true;
    }
}


?>