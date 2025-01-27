<?php 

class PR_DB_Handler {
    public static function install_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_prefix = $wpdb->prefix;

        $sql = "
        CREATE TABLE {$table_prefix}contacts (
            id INT AUTO_INCREMENT PRIMARY KEY,
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
            FOREIGN KEY (contact_id) REFERENCES {$table_prefix}contacts(id) ON DELETE CASCADE
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
            FOREIGN KEY (quote_id) REFERENCES {$table_prefix}quotes(id) ON DELETE CASCADE
        ) $charset_collate;
        ";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function uninstall_tables() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}contacts, {$wpdb->prefix}quotes, {$wpdb->prefix}line_items");
    }
}


?>