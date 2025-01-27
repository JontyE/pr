<?php
class PR_Shortcodes {
    public static function register() {
        add_shortcode('my_guarantee_plugin', [self::class, 'display_guarantee']);
    }

    public static function display_guarantee() {
        ob_start();
        ?>
        <input type="text" id="search_input" placeholder="Search by Name, Address, Email, Phone">
        <div id="results"></div>
        <script>
            function createGuarantee(id) {
                jQuery.post(ajaxurl, { action: 'create_guarantee', quote_id: id }, function() {
                    alert('Guarantee Created!');
                });
            }
        </script>
        <?php
        return ob_get_clean();
    }
}

add_action('init', ['PR_Shortcodes', 'register']);

?>