<?php
/*
Plugin Name: Event Single Page Builder For The Events Calendar
Plugin URI: https://eventscalendaraddons.com/plugin/event-single-page-builder-pro/?utm_source=epta_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=plugin_uri
Description: <a href="http://wordpress.org/plugins/the-events-calendar/"><b>📅 The Events Calendar Addon</b></a> - Design The Event Calendar plugin event single page template with custom colors and fonts.
Version: 1.8.0
Author:  Cool Plugins
Author URI: https://coolplugins.net/?utm_source=epta_plugin&utm_medium=inside&utm_campaign=author_page&utm_content=plugins_list
License:GPL2
Domain Path: /languages
Text Domain: event-page-templates-addon-for-the-events-calendar
Requires Plugins: the-events-calendar
*/

namespace EventPageTemplatesAddon;
//phpcs:disable WordPress.Security.NonceVerification.Recommended
if (!defined('ABSPATH')) {
    exit();
}
if (!defined('EPTA_PLUGIN_CURRENT_VERSION')) {
    define('EPTA_PLUGIN_CURRENT_VERSION', '1.8.0');
}
define('EPTA_PLUGIN_FILE', __FILE__);
define('EPTA_PLUGIN_URL', plugin_dir_url(EPTA_PLUGIN_FILE));
define('EPTA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EPTA_FEEDBACK_API',"https://feedback.coolplugins.net/");

/**
 * Main Class
 */
if (!class_exists('EventPageTemplatesAddon')) {
    class EventPageTemplatesAddon
    {

        /**
         *  Construct the plugin object
         */
        public function __construct()
        {
            register_activation_hook(__FILE__, array($this, 'epta_single_page_builder_activate'));
            register_deactivation_hook(__FILE__, array($this, 'epta_single_page_builder_deactivate'));
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'epta_add_action_links'));
            add_action('plugin_row_meta', array($this, 'eptaaddMetaLinks'), 10, 2);
            add_action('elementor/widgets/register', array($this, 'epta_on_widgets_registered'));
            add_action('admin_head', array($this, 'epta_hide_preview_button'));
            if (is_admin()) {
                require_once __DIR__ . '/admin/events-addon-page/events-addon-page.php';
                cool_plugins_events_addon_settings_page('the-events-calendar', 'cool-plugins-events-addon', '📅 Events Addons For The Events Calendar');
                add_action('admin_menu', array($this, 'epta_reorder_cool_plugins_submenu'), 99);
            }
            add_action('plugins_loaded', array($this, 'epta_init'));
          
            $this->epta_page_include_files();
            add_action('init', array($this, 'epta_add_text_domain'));
            add_action('init', array($this, 'epta_notice_required_plugin'));

            add_action('init', array($this, 'epta_add_single_event_page_details'), 15);
            $this->epta_add_actions();
            add_action('cmb2_admin_init', array($this, 'cmb2_tecsbp_metaboxes'));
            add_action('save_post_epta', array($this, 'save_event_meta_data'), 1, 2);
            add_action( 'all_admin_notices', array( $this, 'epta_display_header' ), 1 );
        }


        /**
         * Initialize cron : MUST USE ON PLUGIN ACTIVATION
         */
        public function epta_cron_job_init() {
            $review_option = get_option("cpfm_opt_in_choice_cool_events");

            if ($review_option === 'yes') {
                if (!wp_next_scheduled('epta_extra_data_update')) {

                    wp_schedule_event(time(), 'every_30_days', 'epta_extra_data_update');

                }
            }
       
        }


        /**
         * Function to blacklist Tec widgets
         */
        public function epta_on_widgets_registered()
        {

            $post_type = get_post_type();
            global $tec_registered_widgets;
            $tec_registered_widgets = array(//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                'tec_events_elementor_widget_event_categories',
                'tec_events_elementor_widget_event_calendar_link',
                'tec_events_elementor_widget_event_cost',
                'tec_events_elementor_widget_event_datetime',
                'tec_events_elementor_widget_event_export',
                'tec_events_elementor_widget_event_image',
                'tec_events_elementor_widget_event_navigation',
                'tec_events_elementor_widget_event_organizer',
                'tec_events_elementor_widget_event_status',
                'tec_events_elementor_widget_event_tags',
                'tec_events_elementor_widget_event_title',
                'tec_events_elementor_widget_event_venue',
                'tec_events_elementor_widget_event_website',
                'tec_events_elementor_widget_event_related',
                'tec_events_elementor_widget_event_venue',
                'tec_events_elementor_widget_event_organizer',
                'tec_events_elementor_widget_event_additional_fields',
                'tec_elementor_widget_event_single_legacy',
                'tec_elementor_widget_countdown',
                'tec_elementor_widget_events_list_widget',
                'tec_elementor_widget_events_view',
            );

            if ('tribe_events' == $post_type) {
                add_filter(
                    'elementor/editor/localize_settings',
                    function ($settings) {
                        global $tec_registered_widgets;
                        foreach ($tec_registered_widgets as $widget_name) {
                            $settings['initial_document']['widgets'][$widget_name]['show_in_panel'] = false;
                        }
                        return $settings;
                    },
                    99
                );
                ?>
					<style>
						.tec-events-elementor-template-selection-helper {
							display: none !important;
						}
					</style>
				<?php
}

        }

        /**
         *  Function to order the submenu of events addon
         */
        public function epta_reorder_cool_plugins_submenu()
        {
            global $submenu;

            // Initialize an array for reordered items.
            $reorderedSubmenu = array();

            // Ensure the target menu exists.
            if (isset($submenu['cool-plugins-events-addon'])) {
                $menuItems = $submenu['cool-plugins-events-addon'];

                // Function to find menu items by label
                function epta_find_menu_item_by_label($menuItems, $label)
                {
                    foreach ($menuItems as $index => $item) {
                        if ($item[0] === $label) {
                            return $index;
                        }
                    }
                    return false;
                }

                // Second plugin reordering (event-single-page-builder-pro)
                $plugin = 'event-page-templates-addon-for-the-events-calendar/the-events-calendar-event-details-page-templates.php';
                if (is_plugin_active($plugin)) {
                    $index = epta_find_menu_item_by_label($menuItems, 'Event Page Template');
                    if ($index !== false) {
                        $reorderedSubmenu[] = $menuItems[$index];
                        unset($submenu['cool-plugins-events-addon'][$index]);
                    }
                }

                // Append the reordered items to the submenu
                $submenu['cool-plugins-events-addon'] = array_merge($submenu['cool-plugins-events-addon'], $reorderedSubmenu);
            }
        }

        /**
         * Add Actions
         * function to create new column on template list table
         *
         * @since 1.6.6
         *
         * @access private
         */
        private function epta_add_actions()
        {
            add_action('init', array($this, 'epta_post_type'), 5);
            add_filter('manage_epta_posts_columns', array($this, 'epta_add_new_columns'));
            add_action('manage_epta_posts_custom_column', array($this, 'epta_manage_columns'), 10, 2);
        }
        public function epta_add_new_columns()
        {
            $new_columns = array();
            $new_columns['cb'] = '<input type="checkbox" />';
            $new_columns['title'] = __('Title', 'event-page-templates-addon-for-the-events-calendar');
            $new_columns['apply_on'] = __('Applied On', 'event-page-templates-addon-for-the-events-calendar');
            $new_columns['date'] = __('Date', 'event-page-templates-addon-for-the-events-calendar');
            return $new_columns;
        }
        public function epta_manage_columns($column, $post_id)
        {
            $text = '';
            $value = '';
            $specifc_val = '';
            if ('apply_on' == $column) {
                // $get_temp_id =  get_option('tec_tribe_single_event_page');
                $epta_apply_on = get_post_meta($post_id, 'epta-apply-on', true);
                if (!empty($epta_apply_on)) {
                    if ($epta_apply_on == 'specific-event') {
                        $text = __('Specific Event', 'event-page-templates-addon-for-the-events-calendar');
                        $value = get_post_meta($post_id, 'epta-specific-event', true);
                    } elseif ($epta_apply_on == 'specific-tag') {
                        $text = __('Specific Tag', 'event-page-templates-addon-for-the-events-calendar');
                        $value = get_post_meta($post_id, 'epta-tag', true);
                    } elseif ($epta_apply_on == 'specific-cate') {
                        $text = __('Specific Category', 'event-page-templates-addon-for-the-events-calendar');
                        $value = get_post_meta($post_id, 'epta-categoery', true);
                    } elseif ($epta_apply_on == 'all-event') {
                        $text = __('All Event', 'event-page-templates-addon-for-the-events-calendar');
                    }
                    if (!empty($value)) {
                        $specifc_val = implode(',', $value);
                    }
                    $set_value = ($text . ':-' . $specifc_val);
                    if ($set_value == ':-') {
                        echo 'N/A';
                    } else {
                        echo esc_html($set_value);
                    }
                } else {
                    echo 'N/A';
                }
            }

        }
        /**
         * Add meta links to the Plugins list page.
         *
         * @param array  $links The current action links.
         * @param string $file  The plugin to see if we are on Event Single Page.
         *
         * @return array The modified action links array.
         */
        public function eptaaddMetaLinks($links, $file)
        {
            if (strpos($file, basename(__FILE__))) {
                $eptaanchor = esc_html__('Video Tutorials', 'event-page-templates-addon-for-the-events-calendar');
                $eptavideourl = esc_url('https://youtu.be/50FBrcqoB-M?si=6pMuWooiNCv0aLkC');
                $links[] = '<a href="' . $eptavideourl . '" target="_blank">' . $eptaanchor . '</a>';
            }

            return $links;
        }
        /**
         *  Function to create notice for promotion of Event Single Page Builder Pro
         */
        public function epta_pro_promotion_notice()
        {
            epta_create_admin_notice(
                array(
                    'id' => 'epta-review-box', // required and must be unique
                    'slug' => 'epta', // required in case of review box
                    'review' => true, // required and set to be true for review box
                    'review_url' => esc_url('https://wordpress.org/support/plugin/event-page-templates-addon-for-the-events-calendar/reviews/#new-post'), // required
                    'plugin_name' => 'Event Single Page Builder For The Event Calendar', // required
                    'review_interval' => 0, // optional: this will display review notice
                    // after 5 days from the installation_time
                    // default is 3
                )
            );
        }
        // custom links for add widgets in all plugins section
        public function epta_add_action_links($links)
        {
            $epta_settings = esc_url(admin_url('edit.php?post_type=epta'));
            $plugin_visit_website = esc_url('https://eventscalendaraddons.com/plugin/event-single-page-builder-pro/?utm_source=epta_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=plugins_list');
            $links[] = '<a  style="font-weight:bold" href="' . $epta_settings . '" target="_self">' . __('Template', 'event-page-templates-addon-for-the-events-calendar') . '</a>';
            $links[] = '<a  style="font-weight:bold" href="' . $plugin_visit_website . '" target="_blank">' . __('Get Pro', 'event-page-templates-addon-for-the-events-calendar') . '</a>';
            return $links;

        }
      
        /*
        |--------------------------------------------------------------------------
        | Code you want to run when all other plugins loaded.
        |--------------------------------------------------------------------------
         */
        public function epta_init()
        {

            if (is_admin()) {
                add_action('admin_init', array($this, 'epta_pro_promotion_notice'));
                require EPTA_PLUGIN_DIR . '/admin/feedback-notice/class-admin-notice.php';
                require_once EPTA_PLUGIN_DIR . 'admin/marketing/epta-marketing.php';

                require_once __DIR__ . '/admin/feedback/admin-feedback-form.php';
             
            }

        }

        public function epta_add_text_domain()
        {
            load_plugin_textdomain('epta', false, basename(dirname(__FILE__)) . '/languages/');//phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound

             if (!get_option( 'epta_initial_save_version' ) ) {
                add_option( 'epta_initial_save_version', EPTA_PLUGIN_CURRENT_VERSION );
            }

            if(!get_option( 'epta-install-date' ) ) {
                add_option( 'epta-install-date', gmdate('Y-m-d h:i:s') );
            }
        }

        public function save_event_meta_data($post_id, $post)
        {
            // handle the case when the custom post is quick edited
            // otherwise all custom meta fields are cleared out
            if (isset($_POST['_inline_edit']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_inline_edit'])), 'inlineeditnonce')) {
                return;
            }

            if (empty($post_id) || empty($post)) {
                return;
            }

            // Dont' save meta boxes for revisions or autosaves
            if (defined('DOING_AUTOSAVE') || is_int(wp_is_post_revision($post)) || is_int(wp_is_post_autosave($post))) {
                return;
            }

            // Check the post being saved == the $post_id to prevent triggering this call for other save_post events
            if (empty($_POST['post_ID']) || $_POST['post_ID'] != $post_id) {
                return;
            }

            // Check user has permission to edit
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
            // if (!empty($_POST['epta-apply-on'])) {
            if (isset($_POST['epta-apply-on']) && !empty(sanitize_text_field(wp_unslash($_POST['epta-apply-on'])))) {
                update_option('tec_tribe_single_event_page', $post_id);
            }

            // }
        }

        /**
         * This function is used to display notice if the required plugin is not activated.
         */
        public function epta_notice_required_plugin()
        {
            if (file_exists(plugin_dir_path(__DIR__) . 'event-single-page-builder-pro/event-single-page-builder-pro.php')) {
                include_once ABSPATH . 'wp-admin/includes/plugin.php';
                if (is_plugin_active('event-single-page-builder-pro/event-single-page-builder-pro.php')) {
                    deactivate_plugins(plugin_basename(__FILE__));
                }
            }

        }
        
        /*
        |--------------------------------------------------------------------------
        | generating page with shortcode for single event page
        |--------------------------------------------------------------------------
         */
        public function epta_add_single_event_page_details()
        {
            $tecset_post_data = array(
                'post_title' => 'Single Event Template',
                'post_type' => 'epta',
                'post_status' => 'publish',
                'post_author' => get_current_user_id(),
            );

            $single_page_id = intval(get_option('tecset-single-page-id'));

            if ('publish' === get_post_status($single_page_id) && get_post_type($single_page_id) == 'epta') {

            } else {
                $post_id = wp_insert_post($tecset_post_data);
                update_option('tecset-single-page-id', $post_id);
            }
        }

        /*
        |--------------------------------------------------------------------------
        |   on plugin activation hook adding page
        |--------------------------------------------------------------------------
         */
        public function epta_single_page_builder_activate()
        {
            update_option('tecset-installDate', gmdate('Y-m-d h:i:s'));
            update_option('tecset-ratingDiv', 'no');
            $this->epta_cron_job_init();

            if (!get_option( 'epta_initial_save_version' ) ) {
                add_option( 'epta_initial_save_version', EPTA_PLUGIN_CURRENT_VERSION );
            }

            if(!get_option( 'epta-install-date' ) ) {
                add_option( 'epta-install-date', gmdate('Y-m-d h:i:s') );
            }
        }

         /*
        |--------------------------------------------------------------------------
        |   on plugin deactivation hook adding page
        |--------------------------------------------------------------------------
         */
        public function epta_single_page_builder_deactivate()
        {
            if (wp_next_scheduled('epta_extra_data_update')) {
                wp_clear_scheduled_hook('epta_extra_data_update');
            }
        }
        /**
         * Display header on epta post type admin pages
         */
        public function epta_display_header() {
           $current_page = ( isset( $_SERVER['PHP_SELF'] ) && is_string( $_SERVER['PHP_SELF'] ) )? basename( sanitize_file_name( wp_unslash( $_SERVER['PHP_SELF'] ) ) ): '';
            if ( $current_page === 'plugins.php' ) {
                return;
            }
            global $post, $typenow, $current_screen;
            
            // Check if we're on epta post type pages
            $is_epta_page = false;
            
            if ( $current_screen && isset( $current_screen->post_type ) && $current_screen->post_type === 'epta' ) {
                $is_epta_page = true;
            } elseif ( $typenow && $typenow === 'epta' ) {
                $is_epta_page = true;
            } elseif ( isset( $_REQUEST['post_type'] ) && sanitize_key( $_REQUEST['post_type'] ) === 'epta' ) {
                $is_epta_page = true;
            } elseif ( $post && get_post_type( $post ) === 'epta' ) {
                $is_epta_page = true;
            }
            
            $show_header = $this->epta_required_plugin_display_header();
            if ( $is_epta_page && $show_header ) {
                // Add CSS to position header at top
                ?>
                <div class="ect-dashboard-wrapper">
                <?php
                // Include the header
                $header_file = EPTA_PLUGIN_DIR . 'admin/events-addon-page/includes/dashboard-header.php';
                if ( file_exists( $header_file ) ) {
                    $prefix = 'ect';
                    $show_wrapper = false;
                    include $header_file;
                }
                ?>
                </div>
                <?php
            }
        }

        // Register Custom Post Type
        public function epta_post_type()
        {
            $labels = array(
                'name' => _x('Event Page Template', 'Post Type General Name', 'event-page-templates-addon-for-the-events-calendar'),
                'singular_name' => _x('Event Page Template', 'Post Type Singular Name', 'event-page-templates-addon-for-the-events-calendar'),
                'menu_name' => __('Event Page Templates', 'event-page-templates-addon-for-the-events-calendar'),
                'name_admin_bar' => __('Event Page Templates', 'event-page-templates-addon-for-the-events-calendar'),
                'archives' => __('Item Archives', 'event-page-templates-addon-for-the-events-calendar'),
                'attributes' => __('Item Attributes', 'event-page-templates-addon-for-the-events-calendar'),
                'parent_item_colon' => __('Parent Item:', 'event-page-templates-addon-for-the-events-calendar'),
                'all_items' => __('Event Page Template', 'event-page-templates-addon-for-the-events-calendar'),

                'update_item' => __('Update Item', 'event-page-templates-addon-for-the-events-calendar'),
                'view_item' => __('View Item', 'event-page-templates-addon-for-the-events-calendar'),
                'view_items' => __('View Items', 'event-page-templates-addon-for-the-events-calendar'),
                'search_items' => __('Search Item', 'event-page-templates-addon-for-the-events-calendar'),
                'not_found' => __('Not found', 'event-page-templates-addon-for-the-events-calendar'),
                'not_found_in_trash' => __('Not found in Trash', 'event-page-templates-addon-for-the-events-calendar'),
                'featured_image' => __('Featured Image', 'event-page-templates-addon-for-the-events-calendar'),
                'set_featured_image' => __('Set featured image', 'event-page-templates-addon-for-the-events-calendar'),
                'remove_featured_image' => __('Remove featured image', 'event-page-templates-addon-for-the-events-calendar'),
                'use_featured_image' => __('Use as featured image', 'event-page-templates-addon-for-the-events-calendar'),
                'insert_into_item' => __('Insert into item', 'event-page-templates-addon-for-the-events-calendar'),
                'uploaded_to_this_item' => __('Uploaded to this item', 'event-page-templates-addon-for-the-events-calendar'),
                'items_list' => __('Items list', 'event-page-templates-addon-for-the-events-calendar'),
                'items_list_navigation' => __('Items list navigation', 'event-page-templates-addon-for-the-events-calendar'),
                'filter_items_list' => __('Filter items list', 'event-page-templates-addon-for-the-events-calendar'),
            );
            $args = array(
                'label' => __('Event Page Template', 'event-page-templates-addon-for-the-events-calendar'),
                'description' => __('Post Type Description', 'event-page-templates-addon-for-the-events-calendar'),
                'labels' => $labels,
                'supports' => array('title'),
                'taxonomies' => array(''),
                'hierarchical' => true,
                'public' => false, // it's not public, it shouldn't have it's own permalink, and so on
                'show_ui' => true,
                'show_in_menu' => 'cool-plugins-events-addon', // 'edit.php?post_type=tribe_events',
                'menu_position' => 5,
                'show_in_admin_bar' => true,
                'show_in_nav_menus' => true,
                'can_export' => true,
                'has_archive' => false, // it shouldn't have archive page
                'rewrite' => false, // it shouldn't have rewrite rules
                'exclude_from_search' => true,
                'publicly_queryable' => true,
                // 'menu_icon'           => EPTA_PLUGIN_URL.'/assets/images/pb-icon.png',
                'capability_type' => 'post',
                'capabilities' => array(
                    'create_posts' => 'do_not_allow', // false < WP 4.5, credit @Ewout
                ),
                'map_meta_cap' => true,

            );
            register_post_type('epta', $args);
        }
        /**
         * Define the metabox and field configurations.
         */
        public function cmb2_tecsbp_metaboxes()
        {
            $prefix = 'epta-';
            if (!class_exists('Tribe__Events__Main')) {
                return;
            } else {
                require_once EPTA_PLUGIN_DIR . 'includes/epta-settings.php';
            }

        }

        /**
         * Include required files
         */
        public function epta_page_include_files()
        {
            require_once EPTA_PLUGIN_DIR . 'admin/cmb2/init.php';

            require_once EPTA_PLUGIN_DIR . 'includes/epta-filter.php';

            if (is_admin()) {
                $tecset_get_post_type = $this->epta_get_post_type_page();
                if ($tecset_get_post_type == 'epta') {
                    require_once EPTA_PLUGIN_DIR . 'admin/cmb2/cmb2-conditionals.php';
                    require_once EPTA_PLUGIN_DIR . 'admin/cmb2/cmb-field-select2/cmb-field-select2.php';
                }

                add_action('admin_enqueue_scripts', array($this, 'epta_tc_css'));
                add_action('manage_posts_extra_tablenav', array($this, 'add_pro_button'));

            } else {
                add_action('wp_enqueue_scripts', array($this, 'epta_register_assets'));
            }

            require_once EPTA_PLUGIN_DIR . 'admin/cpfm-feedback/cron/class-cron.php';

            if(!class_exists('CPFM_Feedback_Notice')){
                require_once EPTA_PLUGIN_DIR . 'admin/cpfm-feedback/cpfm-feedback-notice.php';
            }

            add_action('cpfm_register_notice', function () {
            
                if (!class_exists('CPFM_Feedback_Notice') || !current_user_can('manage_options')) {
                    return;
                }
                $notice = [
                    'title' => __('Events Addons By Cool Plugins', 'event-page-templates-addon-for-the-events-calendar'),
                    'message' => __('Help us make this plugin more compatible with your site by sharing non-sensitive site data.', 'event-page-templates-addon-for-the-events-calendar'),
                    'pages' => ['cool-plugins-events-addon'],
                    'always_show_on' => ['cool-plugins-events-addon'], // This enables auto-show
                    'plugin_name'=>'epta',
                    
                ];

                \CPFM_Feedback_Notice::cpfm_register_notice('cool_events', $notice);
                    if (!isset($GLOBALS['cool_plugins_feedback'])) {
                        $GLOBALS['cool_plugins_feedback'] = [];//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                    }
                
                    $GLOBALS['cool_plugins_feedback']['cool_events'][] = $notice;//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
           
            });
            add_action('cpfm_after_opt_in_epta', function($category) {

                if ($category === 'cool_events') {
                    \EPTA_cronjob::epta_send_data();
                }
            });
        }
        /*
        check admin side post type page
         */
        public function epta_get_post_type_page()
        {
            global $post, $typenow, $current_screen;

            if ($post && $post->post_type) {
                return $post->post_type;
            } elseif ($typenow) {
                return $typenow;
            } elseif ($current_screen && $current_screen->post_type) {
                return $current_screen->post_type;
            } elseif (isset($_REQUEST['page'])) {
                return sanitize_key($_REQUEST['page']);
            } elseif (isset($_REQUEST['post_type'])) {
                return sanitize_key($_REQUEST['post_type']);
            } elseif (isset($_REQUEST['post'])) {
                return get_post_type(sanitize_text_field(wp_unslash($_REQUEST['post'])));
            }
            return null;
        }

        /**
         * Remove preview changes button
         */
        public function epta_hide_preview_button()
        {
            $epta_get_post_type = $this->epta_get_post_type_page();
            if ($epta_get_post_type == 'epta') {
                echo '<style>#preview-action,.updated a{display:none;}</style>';
            }
        }

        /**
         * Get Pro button on templates page
         */
        public function add_pro_button($which)
        {
            if ($which == 'top') {
                $epta_get_post_type = $this->epta_get_post_type_page();
                if ($epta_get_post_type != 'epta') {
                    return false;
                }
                ?>
				<a class="like_it_btn button button-primary" target="_blank"
				href="<?php echo esc_url('https://eventscalendaraddons.com/plugin/event-single-page-builder-pro/?utm_source=epta_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=event_page_template'); ?>">
					Get Pro ⇗</a>
				<?php
}
        }
        public static function epta_required_plugin_display_header() {
            // Required plugins list (path + minimum version)
            $required_plugins = [
                'countdown-for-the-events-calendar/countdown-for-events-calendar.php' => '1.4.16',
				'cp-events-calendar-modules-for-divi-pro/cp-events-calendar-modules-for-divi-pro.php' => '2.0.2',
				'event-page-templates-addon-for-the-events-calendar/the-events-calendar-event-details-page-templates.php' => '1.7.15',
				'events-block-for-the-events-calendar/events-block-for-the-event-calender.php' => '1.3.12',
				'event-single-page-builder-pro/event-single-page-builder-pro.php' => '2.0.1',
				'events-search-addon-for-the-events-calendar/events-calendar-search-addon.php' => '1.2.18',
				'events-speakers-and-sponsors/events-speakers-and-sponsors.php' => '1.1.1',
				'events-widgets-for-elementor-and-the-events-calendar/events-widgets-for-elementor-and-the-events-calendar.php' => '1.6.28',
				'events-widgets-pro/events-widgets-pro.php' => '3.0.1',
				'template-events-calendar/events-calendar-templates.php' => '2.5.4',
				'the-events-calendar-templates-and-shortcode/the-events-calendar-templates-and-shortcode.php' => '4.0.1',
            ];

            $show_header = true;

            // Loop through all plugins
            foreach ($required_plugins as $plugin_path => $min_version) {

                // Plugin active hai?
                if (is_plugin_active($plugin_path)) {

                    // Plugin data get karo
                    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_path);
                    $current_version = $plugin_data['Version'];

                    // Version check
                    if (version_compare($current_version, $min_version, '<=')) {
                        $show_header = false;
                        break;
                    }
                }
            }
            return $show_header;
        }


        /**
         * Admin side css
         */
        public function epta_tc_css()
        {
            wp_enqueue_style('tecset-sg-icon', plugins_url('/assets/css/epta-admin.css', __FILE__), array(), EPTA_PLUGIN_CURRENT_VERSION);
            wp_enqueue_script('tecset-select-temp', plugins_url('/assets/js/epta-template-preview.js', __FILE__), array(), EPTA_PLUGIN_CURRENT_VERSION, true);
            $show_header = $this->epta_required_plugin_display_header();
            $screen = get_current_screen();
            $screen_id = $screen ? $screen->id : '';
            $parent_file = ['events-addons_page_tribe-events-shortcode-template-settings',
                            'events-addons_page_tribe_events-events-template-settings',
                            'toplevel_page_cool-plugins-events-addon',
                            'events-addons_page_cool-events-registration',
                            'events-addons_page_countdown_for_the_events_calendar',
                            'edit-epta',
                            'edit-esas_speaker',
                            'edit-esas_sponsor',
                            'events-addons_page_esas-speaker-sponsor-settings',
                            'edit-ewpe'];
            if (in_array($screen_id, $parent_file)){
                wp_enqueue_style( 'cool-plugins-events-addon', EPTA_PLUGIN_URL . 'admin/events-addon-page/assets/css/styles.min.css', array(), EPTA_PLUGIN_CURRENT_VERSION, 'all' );
            }
            if($show_header && in_array($screen_id, $parent_file)) {
               
              // Common admin notice filter script (runs only on our target pages)
                wp_enqueue_script(
                    'epta-admin-notice-filter',
                    EPTA_PLUGIN_URL . 'assets/js/epta-admin-notice-filter.js',
                    array( 'jquery' ),
                    EPTA_PLUGIN_CURRENT_VERSION,
                    true
                );

                wp_localize_script(
                    'epta-admin-notice-filter',
                    'epta_notice_filter',
                    array(
                        'nonce'             => wp_create_nonce( 'epta_notice_filter' ),
                        'allowedBodyClasses' => array(
                            'events-addons_page_tribe-events-shortcode-template-settings',
                            'events-addons_page_tribe_events-events-template-settings',
                            'toplevel_page_cool-plugins-events-addon',
                            'events-addons_page_cool-events-registration',
                            'events-addons_page_countdown_for_the_events_calendar',
                            'post-type-epta',
                            'post-type-esas_speaker',
                            'post-type-esas_sponsor',
                            'events-addons_page_esas-speaker-sponsor-settings',
                            'post-type-ewpe',
                        ),
                    )
                );
            }
        }
        /**
         * register assets
         */
        public function epta_register_assets()
        {
            wp_register_style('epta-frontend-css', EPTA_PLUGIN_URL . 'assets/css/epta-style.css', null, EPTA_PLUGIN_CURRENT_VERSION, 'all');
            wp_register_style('epta-template2-css', EPTA_PLUGIN_URL . 'assets/css/epta-template2-style.css', null, EPTA_PLUGIN_CURRENT_VERSION, 'all');
            wp_register_style('epta-bootstrap-css', EPTA_PLUGIN_URL . 'assets/css/epta-bootstrap.css', null, EPTA_PLUGIN_CURRENT_VERSION, 'all');
            $add_customcss = $this->epta_custom_css();
            wp_add_inline_style('epta-frontend-css', $add_customcss);
            wp_add_inline_style('epta-template2-css', $add_customcss);
            wp_register_script('epta-events-countdown-widget', EPTA_PLUGIN_URL . 'assets/js/epta-widget-countdown.js', array('jquery'), EPTA_PLUGIN_CURRENT_VERSION, true);
        }
        /**
         * Dynamic style
         */
        public function epta_custom_css()
        {
            // global $post;
            $tecset_pageid = get_option('tec_tribe_single_event_page');
            $tecset_custom_css = get_post_meta($tecset_pageid, 'epta-custom-css', true);
            return wp_strip_all_tags($tecset_custom_css);
        }

    } //end class
}
new EventPageTemplatesAddon();