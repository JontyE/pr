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
        <h2 class="mb-3">Setup Plugin from Quotient</h2>

        <!-- Contacts Upload Form -->
        <div class="card shadow p-4">
            <div class="card-header bg-primary text-white">
                <h5>Upload Contacts CSV</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <input type="file" id="csv_file" class="form-control">
                </div>
                <button id="upload_csv" class="btn btn-success">Upload Contacts</button>
            </div>
        </div>
        <!-- End Contacts Upload Form -->

       <!-- ✅ Progress Popup -->
<div id="upload-popup" style="display:none; position:fixed; top:50%; left:50%; 
    transform:translate(-50%, -50%);
    background:white; padding:30px; border-radius:10px; 
    box-shadow:0 0 20px rgba(0,0,0,0.5); width:350px; text-align:center;
    z-index: 9999;">
    <h4 id="upload-status">Processing CSV...</h4>
    <div class="progress mt-2">
        <div id="progress-bar" class="progress-bar bg-success" style="width: 0%;"></div>
    </div>
</div>
<!-- End Progress Popup -->

        <!-- End Progress Popup -->

        <!-- Quotes Upload Form -->
        <div class="card mt-3">
            <div class="card-header bg-primary text-white">
                <h5>Upload Quotes CSV</h5>
            </div>
            <div class="card-body">
                <form id="quotes-upload-form" method="post" enctype="multipart/form-data">
                    <input type="file" name="quotes_csv" id="quotes_csv" class="form-control mb-2">
                    <button type="submit" class="btn btn-success">Upload Quotes</button>
                    <div id="quotes-progress-bar" class="progress mt-2" style="display:none;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                             role="progressbar" style="width: 0%"></div>
                    </div>
                </form>
            </div>
        </div>
        <!-- End Quotes Upload Form -->

        <!-- Line Items Upload Form -->
        <div class="card mt-3">
    <div class="card-header bg-primary text-white">
        <h5>Upload Line Items CSV</h5>
    </div>
    <div class="card-body">
        <form id="line-items-upload-form" method="post" enctype="multipart/form-data">
            <label for="line_items_csv" class="form-label">Select Line Items CSV</label>
            <input type="file" name="line_items_csv" id="line_items_csv" class="form-control mb-2">
            <button type="submit" class="btn btn-success" id="upload-line-items-btn">Upload Line Items</button>
            <div id="line-items-progress-bar" class="progress mt-2" style="display:none;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                     role="progressbar" style="width: 0%">0%</div>
            </div>
        </form>
    </div>
</div>

        <!-- End Line Items Upload Form -->

    </div>


  
        <?php
    }

    public static function check_import_progress() {
        $progress = get_transient('csv_import_progress');

        if (!$progress) {
            wp_send_json_success([
                'stage' => 'processing',
                'processed_rows' => 0,
                'total_rows' => 1,
            ]);
            return;
        }

        // Detect the current stage
        $stage = $progress['stage'] ?? 'processing';

        wp_send_json_success([
            'stage' => $stage,
            'processed_rows' => $progress['processed'] ?? 0,
            'total_rows' => $progress['total'] ?? 1,
        ]);

         // ✅ If process is completed, clean up transient
    if ($stage === 'completed') {
        delete_transient('csv_import_progress');
    }
    }

    // Add Code Start -- Handle Quotes CSV Upload
    public static function handle_quotes_upload() {
        check_ajax_referer('pr_nonce', 'security');
    
        if (!isset($_FILES['quotes_csv'])) {
            error_log("❌ No file uploaded.");
            wp_send_json_error(['message' => 'No file uploaded.']);
            return;
        }
    
        $file = $_FILES['quotes_csv'];
        $file_path = $file['tmp_name'];
    
        if (!file_exists($file_path)) {
            error_log("❌ File not found: " . $file_path);
            wp_send_json_error(['message' => 'File not found.']);
            return;
        }
    
        // ✅ Include CSV Importer
        include_once PR_PLUGIN_PATH . 'includes/class-import-csv.php';
        
        // ✅ Instantiate Importer
        $importer = new PR_Import_CSV();
        $result = $importer->process_quotes_csv($file_path);
    
        if ($result && is_array($result)) {
            error_log("✅ Quotes import completed. Inserted: " . $result['inserted'] . ", Skipped: " . $result['skipped']);
    
            wp_send_json_success(['silent' => true]);
        } else {
            error_log("❌ Failed to import quotes. Possible empty or corrupted file.");
            wp_send_json_error(['message' => 'Failed to import quotes. Check the error log.']);
        }
    }
    

// Add Code Start -- Handle Line Items CSV Upload
public static function handle_line_items_upload() {
    check_ajax_referer('pr_nonce', 'security');

    if (!isset($_FILES['line_items_csv'])) {
        wp_send_json_error(['message' => 'No file uploaded.']);
        return;
    }

    $file = $_FILES['line_items_csv'];
    $file_path = $file['tmp_name'];

    if (!file_exists($file_path)) {
        wp_send_json_error(['message' => 'File not found.']);
        return;
    }

    // Process CSV File
    include_once PR_PLUGIN_PATH . 'includes/class-import-csv.php';
    $importer = new PR_Import_CSV();
    $result = $importer->process_line_items_csv($file_path);

    if ($result) {
        wp_send_json_success(['message' => 'Line items imported successfully!']);
    } else {
        wp_send_json_error(['message' => 'Failed to import line items.']);
    }
}


    
    
   
    

    public static function enqueue_scripts() {
        wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', [], '5.3.0');
        wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', ['jquery'], '5.3.0', true);
        wp_enqueue_script('pr-admin-js', PR_PLUGIN_URL . 'assets/js/admin-scripts.js', ['jquery'], '1.0.0', true);
    }
    
}
add_action('wp_ajax_check_import_progress', ['PR_Admin', 'check_import_progress']);
add_action('admin_menu', ['PR_Admin', 'add_admin_menu']);
add_action('admin_enqueue_scripts', ['PR_Admin', 'enqueue_scripts']);
add_action('wp_ajax_check_progress', ['PR_Admin', 'check_import_progress']);
// Add Code Start -- Upload Quotes CSV
add_action('wp_ajax_upload_quotes_csv', ['PR_Admin', 'handle_quotes_upload']);
add_action('wp_ajax_nopriv_upload_quotes_csv', ['PR_Admin', 'handle_quotes_upload']);
// Add Code Start -- Upload Line Items CSV
add_action('wp_ajax_upload_line_items_csv', ['PR_Admin', 'handle_line_items_upload']);
add_action('wp_ajax_nopriv_upload_line_items_csv', ['PR_Admin', 'handle_line_items_upload']);


?>
