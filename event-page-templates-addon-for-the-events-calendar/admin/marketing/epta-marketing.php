<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('EPTA_TEC_Notice')) {

    class EPTA_TEC_Notice
    {
        private static $instance = null;

        public static function get_instance()
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct()
        {
            $all_plugins    = get_plugins();

            if (!array_key_exists('event-single-page-builder-pro/event-single-page-builder-pro.php', $all_plugins)) {
                add_action('admin_notices', [$this, 'show_elementor_epta_notice']);
            }
            add_action('wp_ajax_epta_dismiss_notice', [$this, 'epta_dismiss_notice']);
        }

         /**
         * Enqueue marketing scripts
         */
        public function epta_enqueue_marketing_scripts() {
            wp_register_script(
                'epta-tec-notice-js',
                EPTA_PLUGIN_URL . 'admin/marketing/js/epta-marketing.js',
                ['jquery'],
                EPTA_PLUGIN_CURRENT_VERSION,
                true
            );
            wp_enqueue_script('epta-tec-notice-js');
        }

        /**
         * Elementor + EPTA Notice
         */
        public function show_elementor_epta_notice()
        {
            if (!class_exists('Tribe__Events__Main') || get_option('epta_elementor_notice_dismissed')) {
                return;
            }

            $pluginList = get_option('active_plugins', []);
            $plugin  = 'elementor/elementor.php';
            $plugin1 = 'event-page-templates-addon-for-the-events-calendar/the-events-calendar-event-details-page-templates.php';

            if (in_array($plugin, $pluginList) && in_array($plugin1, $pluginList)) {
                $screen = get_current_screen();
                if (!$screen) return;

                $allowed_screens = [
                    'edit-tribe_events',
                    'tribe_events',
                    'tribe_events_page_tec-events-settings',
                    'toplevel_page_cool-plugins-events-addon',
                    'plugins'
                ];

                if (!in_array($screen->id, $allowed_screens, true)) {
                    return;
                }
                $this->epta_enqueue_marketing_scripts();
                ?>
                <div class="notice notice-info is-dismissible epta-tec-notice-elementor"
                    data-notice="epta_notice_elementor"
                    data-nonce="<?php echo esc_attr(wp_create_nonce('epta_dismiss_nonce_elementor')); ?>">
                    <p class="epta-notice-widget">
                    <a href="https://eventscalendaraddons.com/plugin/event-single-page-builder-pro/?utm_source=epta_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=inside_notice" 
                        target="_blank" class="button button-primary">
                            Try it now!
                        </a>
                        Hi! It appears that you are currently using <strong>Elementor</strong>. 
                        We suggest you try 
                        <a href="https://eventscalendaraddons.com/plugin/event-single-page-builder-pro/?utm_source=epta_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=inside_notice" 
                           target="_blank"><strong>Event Single Page Builder Pro</strong></a> 
                        for designing event single page templates in Elementor.
                    </p>
                </div>
                <?php
            }
        }

        /**
         * Dismiss notice
         */
        public function epta_dismiss_notice()
        {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'Permission denied']);
            }

            $notice_type = isset($_POST['notice']) ? sanitize_text_field($_POST['notice']) : '';
            $nonce       = isset($_POST['nonce']) ? $_POST['nonce'] : '';

            if ($notice_type === 'epta_notice_elementor' && wp_verify_nonce($nonce, 'epta_dismiss_nonce_elementor')) {
                update_option('epta_elementor_notice_dismissed', true);
                wp_send_json_success();
            }

            wp_send_json_error(['message' => 'Invalid nonce']);
        }
    }

    EPTA_TEC_Notice::get_instance();
}
