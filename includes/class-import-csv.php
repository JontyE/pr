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


public function process_quotes_csv($file_path) {
    error_log("🔍 process_quotes_csv started for file: " . $file_path);

    $handle = fopen($file_path, 'r');
    if (!$handle) {
        error_log("❌ Failed to open quotes CSV file.");
        wp_send_json_error(['message' => 'Error: Unable to open CSV file.']);
        return false;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'quotes';

    // Normalize emails in contacts table before processing
    $wpdb->query("UPDATE {$wpdb->prefix}contacts SET email = TRIM(LOWER(email))");

    $row_index = 0;
    $inserted_count = 0;
    $skipped_count = 0;
    $errors = [];

    while (($data = fgetcsv($handle, 1000, ",", '"')) !== FALSE) { // ✅ Handle quoted fields
        if ($row_index === 0) { // Skip header row
            error_log("✅ Header row skipped.");
            $row_index++;
            continue;
        }

        error_log("🔹 Processing Row $row_index: " . print_r($data, true));

        // Ensure the row has the correct number of columns
        if (count($data) < 15) { 
            error_log("❌ Skipping Row $row_index: Incorrect column count. Data: " . print_r($data, true));
            $errors[] = "Error in row $row_index: Incorrect column structure.";
            $skipped_count++;
            continue;
        }

        // Extract and sanitize data
        $quote_number = sanitize_text_field($data[0]);
        $email = trim(sanitize_email($data[6]));
        $total_price = floatval(str_replace(',', '', $data[7]));
        $currency = sanitize_text_field($data[8]);
        $overall_discount = !empty($data[10]) ? floatval($data[10]) : 0.00;
        $quote_status = sanitize_text_field($data[11]);
        $last_status_change = date('Y-m-d H:i:s', strtotime($data[13]));
        $expiry_date = date('Y-m-d', strtotime($data[14]));
        $sent_when = !empty($data[12]) ? date('Y-m-d H:i:s', strtotime($data[12])) : NULL;

        // Ensure email is valid
        if (empty($email) || !is_email($email)) {
            error_log("⚠️ Invalid or Empty Email in Row $row_index: " . print_r($data, true));
            $errors[] = "Error in row $row_index: Invalid or empty email.";
            $skipped_count++;
            continue;
        }

        // Retrieve contact_id based on email
        $contact_id = $wpdb->get_var($wpdb->prepare(
            "SELECT contact_id FROM {$wpdb->prefix}contacts WHERE TRIM(LOWER(email)) = TRIM(LOWER(%s))",
            $email
        ));

        if (!$contact_id) {
            error_log("⚠️ Contact Not Found for Email in Row $row_index: " . $email);
            $errors[] = "Error in row $row_index: Contact not found for email " . $email;
            $skipped_count++;
            continue;
        }

        // ✅ Check if quote number already exists
        $existing_quote = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}quotes WHERE quote_number = %s",
            $quote_number
        ));

        if ($existing_quote) {
            error_log("⚠️ Skipping row $row_index: Quote number $quote_number already exists in database.");
            $errors[] = "Error in row $row_index: Quote number $quote_number already exists. Skipping row.";
            $skipped_count++;
            continue;
        }

        error_log("✅ Valid Data - Quote Number: $quote_number, Contact ID: $contact_id, Status: $quote_status, Total Price: $total_price");

        // Insert into quotes table
        $inserted = $wpdb->insert($table_name, [
            'quote_number' => $quote_number,
            'contact_id' => $contact_id,
            'status' => 'No Guarantee Created', // Default status as required
            'g_valid_date' => NULL, // Reserved for future use
            'total_price' => $total_price,
            'currency' => $currency,
            'overall_discount' => $overall_discount,
            'quote_status' => $quote_status,
            'last_status_change' => $last_status_change,
            'expiry_date' => $expiry_date,
            'sent_when' => $sent_when,
            'email' => $email,
            'created_at' => current_time('mysql')
        ]);

        if ($inserted) {
            error_log("✅ Successfully inserted row $row_index into quotes table.");
            $inserted_count++;
        } else {
            error_log("❌ Failed to insert row $row_index into quotes table. MySQL Error: " . $wpdb->last_error);
            $errors[] = "Error in row $row_index: Database insertion failed.";
            $skipped_count++;
        }

        $row_index++;
    }

    fclose($handle);
    error_log("📌 process_quotes_csv completed. Inserted: $inserted_count, Skipped: $skipped_count");

    // ✅ Corrected success message
    wp_send_json_success(['silent' => true]);
}




public function process_line_items_csv($file_path) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'line_items';

    if (!file_exists($file_path)) {
        return false;
    }

    $handle = fopen($file_path, 'r');
    if (!$handle) {
        return false;
    }

    // Skip header row
    fgetcsv($handle);

    while (($row = fgetcsv($handle, 1000, ",")) !== false) {
        $quote_id = intval($row[0]); // Quote Number
        $item_code = sanitize_text_field($row[1]);
        $heading = sanitize_text_field($row[2]);
        $unit_price = floatval(str_replace(',', '', $row[4])); // Remove thousands separator
        $quantity = intval($row[5]);
        $item_total = floatval(str_replace(',', '', $row[7])); // Remove thousands separator

        // Ensure required fields are present
        if (empty($quote_id) || empty($heading) || empty($unit_price) || empty($quantity) || empty($item_total)) {
            continue;
        }

        $wpdb->insert($table_name, [
            'quote_id'   => $quote_id,
            'item_code'  => $item_code,
            'heading'    => $heading,
            'unit_price' => $unit_price,
            'quantity'   => $quantity,
            'item_total' => $item_total,
            'created_at' => current_time('mysql')
        ]);
    }

    fclose($handle);
    return true;
}

    
    
}
?>
