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
        $nonce = wp_create_nonce('pr_nonce');
        ?>
        <script>
            var pr_nonce = "<?php echo esc_js($nonce); ?>";
            var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
        </script>

        <div class="container mt-4">
            <h2 class="mb-3">Upload CSV</h2>

            <div class="card shadow p-4">
                <div class="mb-3">
                    <input type="file" id="csv_file" class="form-control">
                </div>
                <button id="upload_csv" class="btn btn-primary">Upload</button>
            </div>
        </div>

       <!-- ✅ Progress Popup -->
<div id="upload-popup" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%);
    background:white; padding:20px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.3); width:300px; text-align:center;">
    <h4 id="upload-status">Processing CSV...</h4>
    <div style="height:10px; background:#ddd; border-radius:5px; overflow:hidden;">
        <div id="progress-bar" style="width:0%; height:100%; background:#28a745;"></div>
    </div>
</div>
        <?php
    }

    public static function check_import_progress() {
        $progress = get_transient('csv_import_progress');
    
        if (!$progress) {
            wp_send_json_error(['message' => 'No progress data found.']);
            return;
        }
    
        wp_send_json_success([
            'processed_rows' => $progress['processed'] ?? 0,
            'total_rows' => $progress['total'] ?? 1,
        ]);
    }
 

    
    

    public static function enqueue_scripts() {
        wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', [], '5.3.0');
        wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', ['jquery'], '5.3.0', true);
        wp_enqueue_script('pr-admin-js', PR_PLUGIN_URL . 'assets/js/admin-scripts.js', ['jquery'], '1.0.0', true);
    }
    
}

add_action('admin_menu', ['PR_Admin', 'add_admin_menu']);
add_action('admin_enqueue_scripts', ['PR_Admin', 'enqueue_scripts']);
add_action('wp_ajax_check_progress', ['PR_Admin', 'check_import_progress']);

?>
