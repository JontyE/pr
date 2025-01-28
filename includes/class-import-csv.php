 <?php

class PR_Import_CSV {

    public static function parse_csv($file_path) {
        $rows = [];
        if (($handle = fopen($file_path, "r")) !== FALSE) {
            // Read the first row as headers and remove BOM characters
            $headers = fgetcsv($handle, 1000, ",");
            if ($headers) {
                $headers[0] = preg_replace('/\x{FEFF}/u', '', $headers[0]); // Remove BOM
                $headers = array_map('trim', $headers); // Trim spaces
                $headers = array_map('strtolower', $headers); // Convert to lowercase
                $headers = array_map(fn($h) => preg_replace('/[^a-z0-9_]/iu', '', str_replace(' ', '_', $h)), $headers); // Remove special chars
    
                // ✅ Manual Fix for Known Header Issues (Ensure Correct Mapping)
                $header_mapping = [
                    'contact_' => 'contact_id', // Fix stripped 'contact_ÏĐ' issue
                ];
                foreach ($headers as &$header) {
                    if (isset($header_mapping[$header])) {
                        $header = $header_mapping[$header];
                    }
                }
    
                error_log("✅ Cleaned CSV Headers: " . print_r($headers, true));
            } else {
                fclose($handle);
                error_log("❌ CSV Header Error: No headers found.");
                return [];
            }
    
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) != count($headers)) {
                    error_log("❌ CSV Row Mismatch: " . print_r($data, true));
                    continue;
                }
    
                $row = array_combine($headers, array_map('trim', $data));
    
                if (!$row) {
                    error_log("❌ CSV Mapping Failed for Row: " . print_r($data, true));
                    continue;
                }
    
                $rows[] = $row;
            }
            fclose($handle);
        }
    
      //  error_log("✅ Parsed CSV Data: " . print_r($rows, true));
        return $rows;
    }
public static function insert_contacts($data) {
    global $wpdb;

    if (!PR_DB_Handler::check_tables_exist()) {
        error_log("❌ Database tables missing. Run setup again.");
        wp_send_json_error(['message' => 'Database tables missing. Run setup again.']);
        return;
    }

    $table_name = $wpdb->prefix . "contacts";
    $total_rows = count($data);

    // 🟢 Start Processing Phase
    set_transient('csv_import_progress', ['stage' => 'processing', 'processed' => 0, 'total' => $total_rows], 60 * 5);

    foreach ($data as $index => $row) {
        set_transient('csv_import_progress', ['stage' => 'processing', 'processed' => $index + 1, 'total' => $total_rows], 60 * 5);
    }

    // 🟢 Switch to Inserting Phase
    set_transient('csv_import_progress', ['stage' => 'inserting', 'processed' => 0, 'total' => $total_rows], 60 * 5);

    $processed_rows = 0;

    foreach ($data as $index => $contact) {
        $processed_rows++;

        // ✅ Ensure `contact_id` exists
        if (!isset($contact['contact_id']) || empty(trim($contact['contact_id']))) {
            continue;
        }

        // ✅ Sanitize Fields
        $first_name = sanitize_text_field($contact['first_name'] ?? '');
        if (empty($first_name) && !empty($contact['company_name'])) {
            $first_name = sanitize_text_field($contact['company_name']);
        }

        $phone = preg_replace('/[^0-9]/', '', $contact['phone'] ?? '');
        $phone = substr($phone, 0, 15);
        $email = sanitize_email($contact['email'] ?? '');
        
        // ✅ Assign default email if missing
        if (empty($email)) {
            $email = "unknown_" . intval($contact['contact_id']) . "@noemail.com";
        }

        // ✅ Check if email already exists
        $existing_email_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE email = %s", $email
        ));

        // ✅ Append unique identifier if email already exists
        if ($existing_email_count > 0) {
            $counter = 1;
            $base_email = preg_replace('/@/', '_', $email, 1); // Modify email to avoid duplicates
            while ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE email = %s", $email)) > 0) {
                $email = $base_email . "_$counter@noemail.com";
                $counter++;
            }
        }

        // ✅ Check if contact exists by `contact_id`
        $existing_contact = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE contact_id = %d", intval($contact['contact_id'])
        ), ARRAY_A);

        if ($existing_contact) {
            // ✅ Update Existing Contact
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET first_name = %s, last_name = %s, email = %s, phone = %s, address = %s WHERE contact_id = %d",
                $first_name,
                sanitize_text_field($contact['last_name'] ?? ''),
                $email,
                sanitize_text_field($phone),
                sanitize_text_field($contact['address'] ?? ''),
                intval($contact['contact_id'])
            ));
        } else {
            // ✅ Insert New Contact
            $wpdb->query($wpdb->prepare(
                "INSERT INTO $table_name (contact_id, first_name, last_name, email, phone, address) 
                VALUES (%d, %s, %s, %s, %s, %s)",
                intval($contact['contact_id']),
                $first_name,
                sanitize_text_field($contact['last_name'] ?? ''),
                $email,
                sanitize_text_field($phone),
                sanitize_text_field($contact['address'] ?? '')
            ));
        }

        // 🟢 Update Inserting Progress
        set_transient('csv_import_progress', ['stage' => 'inserting', 'processed' => $processed_rows, 'total' => $total_rows], 60 * 5);
    }

   // 🟢 Mark as "completed" after inserting all contacts
   set_transient('csv_import_progress', ['stage' => 'completed', 'processed' => $total_rows, 'total' => $total_rows], 60 * 5);

}

    
    
    
}
?>
