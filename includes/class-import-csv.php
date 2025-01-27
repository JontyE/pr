<?php
class PR_Import_CSV {
    public static function parse_csv($file_path) {
        $rows = [];
        if (($handle = fopen($file_path, "r")) !== FALSE) {
            $headers = fgetcsv($handle, 1000, ","); // Read first row as headers
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row = array_combine($headers, array_map('trim', $data)); 
                $rows[] = $row;
            }
            fclose($handle);
        }
        return $rows;
    }

    public static function insert_contacts($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . "contacts";

        foreach ($data as $contact) {
            $wpdb->replace($table_name, [
                'first_name' => sanitize_text_field($contact['first_name']),
                'last_name'  => sanitize_text_field($contact['last_name']),
                'email'      => sanitize_email($contact['email']),
                'phone'      => sanitize_text_field($contact['phone']),
                'address'    => sanitize_textarea_field($contact['address'])
            ]);
        }
    }
}

?>
