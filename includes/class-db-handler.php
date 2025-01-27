<?php
class PR_DB_Handler {
    public static function install_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_prefix = $wpdb->prefix;

        $sql = "
        CREATE TABLE {$table_prefix}contacts (
            contact_id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(255) NOT NULL,
            last_name VARCHAR(255),
            email VARCHAR(255) UNIQUE NOT NULL,
            phone VARCHAR(50),
            address TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;

        CREATE TABLE {$table_prefix}quotes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quote_number VARCHAR(50) UNIQUE NOT NULL,
            contact_id INT NOT NULL,
            status ENUM('No Guarantee Created', 'Valid', 'Expired') DEFAULT 'No Guarantee Created',
            valid_until DATE DEFAULT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX contact_idx (contact_id)
        ) $charset_collate;

        CREATE TABLE {$table_prefix}line_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quote_id INT NOT NULL,
            item_code VARCHAR(50) NOT NULL,
            heading TEXT NOT NULL,
            description TEXT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            quantity INT NOT NULL,
            item_total DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX quote_idx (quote_id)
        ) $charset_collate;
        ";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function uninstall_tables() {
        global $wpdb;

        // Check if foreign keys exist before dropping them
        $fk_check_quotes = $wpdb->get_results("SHOW CREATE TABLE {$wpdb->prefix}quotes", ARRAY_N);
        if (strpos($fk_check_quotes[0][1], "FOREIGN KEY") !== false) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}quotes DROP FOREIGN KEY {$wpdb->prefix}quotes_ibfk_1");
        }

        $fk_check_line_items = $wpdb->get_results("SHOW CREATE TABLE {$wpdb->prefix}line_items", ARRAY_N);
        if (strpos($fk_check_line_items[0][1], "FOREIGN KEY") !== false) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}line_items DROP FOREIGN KEY {$wpdb->prefix}line_items_ibfk_1");
        }

        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}contacts, {$wpdb->prefix}quotes, {$wpdb->prefix}line_items");
    }

    public static function check_tables_exist() {
        global $wpdb;
        $tables = ["contacts", "quotes", "line_items"];
    
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    
            if (!$exists) {
                error_log("❌ Missing Table: $table_name");
                return false;
            }
        }
        return true;
    }
    
}
?>
