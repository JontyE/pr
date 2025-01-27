<?php

class PR_Admin {
    public static function add_admin_menu() {
        add_menu_page(
            'Prompt Roofing Guarantee',
            'Guarantee Manager',
            'manage_options',
            'pr_admin',
            [self::class, 'admin_page'],
            'dashicons-shield'
        );
    }

    public static function admin_page() {
        ?>
        <div class="wrap">
            <h2>Upload CSV</h2>
            <input type="file" id="csv_file">
            <button id="upload_csv">Upload</button>
            <div class="progress"><div id="progressBar" class="progress-bar"></div></div>

            <h2>Guarantee Management</h2>
            <input type="text" id="search_input" placeholder="Search by Name, Address, Email">
            <div id="results"></div>
        </div>
        <?php
    }
}
add_action('admin_menu', ['PR_Admin', 'add_admin_menu']);


?>