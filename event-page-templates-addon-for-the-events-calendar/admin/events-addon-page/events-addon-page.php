<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * This is the main class for creating dashboard addon page and all submenu items
 * Do not call or initialize this class directly, instead use the function mentioned at the bottom of this file
 */
if (!class_exists('cool_plugins_events_addons')) {

    class cool_plugins_events_addons
    {

        /**
         * None of these variables should be accessible from the outside of the class
         */
        private static $instance;
        private $pro_plugins = array();
        private $pages = array();
        private $main_menu_slug = null; // 'cool-plugins-events-addon';
        private $plugin_tag = null;
        private $dashboar_page_heading;
        private $disable_plugins = array();
        private $addon_dir = __DIR__; // point to the main addon-page directory
        private $addon_file = __FILE__;
        private $plugin_api = 'https://plugins.coolplugins.net/plugins-list/';

        /**
         * initialize the class and create dashboard page only one time
         */
        public static function init()
        {

            if (empty(self::$instance)) {
                return self::$instance = new self;
            }
            return self::$instance;
        }

        /**
         * Initialize the dashboard with specific plugins as per plugin tag
         */
        public function show_plugins($plugin_tag, $menu_slug, $dashboard_heading)
        {

            if (!empty($plugin_tag) && !empty($menu_slug) && !empty($dashboard_heading)) {
                $this->plugin_tag = $plugin_tag;
                $this->main_menu_slug = $menu_slug;
                $this->dashboar_page_heading = $dashboard_heading;
            } else {
                return false;
            }
            add_action('admin_menu', array($this, 'init_plugins_dasboard_page'), 10);
            add_action('wp_ajax_cool_plugins_install_' . $this->plugin_tag, array($this, 'cool_plugins_install'));
            add_action('wp_ajax_cool_plugins_activate_' . $this->plugin_tag, array($this, 'cool_plugins_activate'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_required_scripts'));
        }

        /**
         * handle ajax request for activating plugin from dashboard
         */
        public function cool_plugins_activate()
        {
            if (current_user_can('upload_plugins')) {

                $plugin_slug = isset($_POST["ect_activate_slug"]) ? sanitize_text_field($_POST["ect_activate_slug"]) : '';

                $wp_nonce = 'ect-plugins-activate-' . $plugin_slug;
                if (!empty($plugin_slug)) {
                    if (!check_ajax_referer($wp_nonce, 'wp_nonce', false)) {
                        wp_send_json_error('Invalid security token sent.');
                        wp_die();
                    }
                    $pluginBase = (isset($_POST['ect_activate_pluginbase']) && !empty($_POST['ect_activate_pluginbase'])) ? sanitize_text_field($_POST['ect_activate_pluginbase']) : null;

                    $plugin_base_arr = explode("/", $pluginBase);
                    if (isset($plugin_base_arr[0]) && $plugin_base_arr[0] == $plugin_slug) {
                        activate_plugin($pluginBase);

                    } else {
                        wp_send_json_error('Something wrong with plugin path.');
                        wp_die();
                    }
                } else {
                    wp_send_json_error('Plugin slug is missing.');
                    wp_die();
                }
            } else {
                wp_send_json_error('You have no permission to do this action.');
                wp_die();
            }
        }

        /**
         * Handle AJAX for installing plugin from the dashboard.
         * This function uses the core WordPress functionality of installing a plugin through URL
         */
        public function cool_plugins_install()
        {
            if (current_user_can('upload_plugins')) {
                $plugin_slug = isset($_POST['ect_slug']) ? sanitize_text_field($_POST['ect_slug']) : '';
                $wp_nonce = wp_create_nonce('ect-plugins-download-' . $plugin_slug);
                if (!empty($plugin_slug)) {
                    if (!check_ajax_referer('ect-plugins-download-' . $plugin_slug, 'wp_nonce', false)) {

                        wp_send_json_error('Invalid security token sent.');
                        wp_die();
                    }

                    require_once 'includes/cool_plugins_downloader.php';
                    $downloader = new cool_plugins_downloader();

                    $plugins = $this->request_wp_plugins_data($this->plugin_tag);

                    if (isset($plugins[$plugin_slug])) {
                        $url = $plugins[$plugin_slug]['download_link'];
                        return $downloader->install(filter_var($url, FILTER_SANITIZE_URL), 'install');

                    } else {
                        wp_send_json_error('Sorry, You are installing a wrong plugin.');
                        wp_die();
                    }
                } else {
                    wp_send_json_error('Plugin slug is missing.');
                    wp_die();
                }
            } else {
                wp_send_json_error('You have no permission to do this action.');
                wp_die();
            }
        }

        /**
         * This function will initialize the main dashboard page for all plugins
         */
        public function init_plugins_dasboard_page()
        {
            add_menu_page('Events Addons', 'Events Addons', 'manage_options', $this->main_menu_slug, array($this, 'displayPluginAdminDashboard'), 'dashicons-calendar-alt', 9);
            add_submenu_page($this->main_menu_slug, 'Dashboard', 'Dashboard', 'manage_options', $this->main_menu_slug, array($this, 'displayPluginAdminDashboard'), 5);
        }

        /**
         * This function will render and create the HTML display of dashboard page.
         * All the HTML can be located in other template files.
         * Avoid using any HTML here or use nominal HTML tags inside this function.
         */
        public function displayPluginAdminDashboard()
        {

            $tag = $this->plugin_tag;
            $plugins = $this->request_wp_plugins_data($tag);
            $this->request_pro_plugins_data($tag);
            $this->ect_disable_free_plugins();
            if (!empty($plugins) && count($plugins) > 0) {

                // merge free & pro plugins into one array
                if (count($this->pro_plugins) > 0) {
                    $plugins = array_merge($plugins, $this->pro_plugins);
                }

                require $this->addon_dir . '/includes/dashboard-header.php';

                echo '<div class="cool-body-left">
                    <div class="plugins-list installed-addons" data-empty-message="You have not installed any addon at the moment"><h3>Currently Installed Addons</h3>';

                foreach ($plugins as $plugin) {

                    $plugin_name = esc_html($plugin['name']);
                    $plugin_desc = esc_html($plugin['desc']);
                    $plugin_logo = esc_url($this->event_addon_plugins_logo($plugin['slug']));
                    $plugin_url = $plugin['download_link'];
                    $plugin_slug = esc_attr($plugin['slug']);
                    $plugin_version = esc_html($plugin['version']);

                    if (file_exists(WP_PLUGIN_DIR . '/' . $plugin_slug)) {
                        require $this->addon_dir . '/includes/dashboard-page.php';
                    }
                }
                echo "</div>";

                echo "<div class='plugins-list more-addons' data-empty-message='No more free addons available at the moment'><h3>More Addons</h3>";
                foreach ($plugins as $plugin) {

                    if ($plugin['download_link'] == null) {
                        continue;
                    }
                    $plugin_name = esc_html($plugin['name']);
                    $plugin_desc = esc_html($plugin['desc']);
                    $plugin_logo = esc_url($this->event_addon_plugins_logo($plugin['slug']));
                    $plugin_url = $plugin['download_link'];
                    $plugin_slug = esc_attr($plugin['slug']);
                    $plugin_version = esc_html($plugin['version']);

                    if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin_slug)) {
                        require $this->addon_dir . '/includes/dashboard-page.php';
                    }

                }
                echo '</div>';
                if (!empty($this->pro_plugins) && count($this->pro_plugins) > 0):
                    /**
                     * Load this Pro Plugin container only if there are any pro plugins available
                     */
                    echo "<div class='plugins-list pro-addons' data-empty-message='No more Pro plugins available at the moment'><h3>Pro Addons</h3>";
                    foreach ($this->pro_plugins as $plugin) {
                        $plugin_name = esc_html($plugin['name']);
                        $plugin_desc = esc_html($plugin['desc']);
                        $plugin_logo = esc_url($this->event_addon_plugins_logo($plugin['slug']));
                        $plugin_pro_url = esc_url($plugin['buyLink']);
                        $plugin_url = null;
                        $plugin_version = null;
                        $plugin_slug = esc_attr($plugin['slug']);

                        if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin_slug)) {
                            require $this->addon_dir . '/includes/dashboard-page.php';
                        }
                    }
                    echo '</div>';
                endif;
                echo '</div>'; // end of .cool-body-left
                require $this->addon_dir . '/includes/dashboard-sidebar.php';

            } else {
                // plugins are not available under this tag.
            }
        }

        /**
         * Lets enqueue all the required CSS & JS
         */
        public function enqueue_required_scripts()
        {
            // A common CSS file will be enqueued for admin panel
            wp_enqueue_style('cool-plugins-events-addon', plugin_dir_url(__FILE__) . 'assets/css/styles.css', null, null, 'all');
            $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
            if ($current_page == $this->main_menu_slug) {
                wp_enqueue_script('cool-plugins-events-addon', plugin_dir_url(__FILE__) . 'assets/js/script.js', array('jquery'), null, true);
                wp_localize_script('cool-plugins-events-addon', 'cp_events', array('ajax_url' => admin_url('admin-ajax.php')));
            }
        }

        /**
         * This function will gather all information regarding pro plugins.
         */
        public function request_pro_plugins_data($tag = null)
        {
            $trans_name = $this->main_menu_slug . '_pro_api_cache' . $this->plugin_tag;
            $option_name = $this->main_menu_slug . '-' . $this->plugin_tag . '-pro';
            if (get_transient($trans_name) != false) {
                return $this->pro_plugins = get_option($option_name, false);
            }
            $url = $this->plugin_api . 'pro/' . $this->plugin_tag;

            $pro_api = esc_url($url);
            $response = wp_remote_get($pro_api, array('timeout' => 300));

            if (is_wp_error($response)) {
                return;
            }
            $plugin_info = (array) json_decode($response['body']);

            foreach ($plugin_info as $plugin) {

                $this->pro_plugins[$plugin->slug] = array(
                    'name' => sanitize_text_field($plugin->name),
                    'logo' => esc_url($plugin->image_url),
                    'desc' => sanitize_text_field($plugin->info),
                    'slug' => sanitize_text_field($plugin->slug),
                    'buyLink' => esc_url($plugin->buy_url),
                    'version' => sanitize_text_field($plugin->version),
                    'download_link' => null,
                    'incompatible' => $plugin->free_version,
                    'buyLink' => $plugin->buy_url,
                );
                if (property_exists($plugin, 'free_version') && $plugin->free_version != null) {
                    $this->disable_plugins[$plugin->free_version] = array('pro' => $plugin->slug);
                }

            }

            if (!empty($this->pro_plugins) && is_array($this->pro_plugins) && count($this->pro_plugins)) {
                set_transient($trans_name, $this->pro_plugins, DAY_IN_SECONDS);
                update_option($option_name, $this->pro_plugins);
                return $this->pro_plugins;
            } else if (get_option($option_name, false) != false) {
                return get_option($option_name);
            }

        }

        /**
         * Gather all the free plugin information from wordpress.org API
         */
        public function request_wp_plugins_data($tag = null)
        {

            if (get_transient($this->main_menu_slug . '_api_cache' . $this->plugin_tag) != false) {
                return get_option($this->main_menu_slug . '-' . $this->plugin_tag, false);
            }
            $url = $this->plugin_api . 'free/' . $this->plugin_tag;

            $response = wp_remote_get($url, array('timeout' => 300));

            if (is_wp_error($response)) {
                return;
            }
            $plugin_info = json_decode($response['body'], true);
            $all_plugins = array();
            // var_dump($plugin->slug);
            foreach ($plugin_info as $plugin) {
                // if (!property_exists($plugin['tag'], $tag)) {
                //     continue;
                // }
                $plugins_data['name'] = $plugin['name'];
                $plugins_data['logo'] = $plugin['image_url'];

                /*   foreach ($plugin->icons as $icon) {
                $plugins_data['logo'] = $icon;
                break;
                } */
                $plugins_data['slug'] = $plugin['slug'];
                $plugins_data['desc'] = $plugin['info'];
                $plugins_data['version'] = $plugin['version'];
                $plugins_data['tags'] = $plugin['tag'];
                $plugins_data['download_link'] = $plugin['download_url'];
                $all_plugins[$plugin['slug']] = $plugins_data;
            }

            if (!empty($all_plugins) && is_array($all_plugins) && count($all_plugins)) {
                set_transient($this->main_menu_slug . '_api_cache' . $this->plugin_tag, $all_plugins, DAY_IN_SECONDS);
                update_option($this->main_menu_slug . '-' . $this->plugin_tag, $all_plugins);
                return $all_plugins;
            } elseif (get_option($this->main_menu_slug . '-' . $this->plugin_tag, false) != false) {
                return get_option($this->main_menu_slug . '-' . $this->plugin_tag);
            }
        }

        public function event_addon_plugins_logo($slug)
        {
            $logos_arr = [
                'events-block-for-the-events-calendar' => 'events-block-icon.svg',
                'events-widgets-for-elementor-and-the-events-calendar' => 'events-widgets-icon.svg',
                'the-events-calendar-templates-and-shortcode' => 'events-shortcodes-icon.svg',
                'template-events-calendar' => 'events-shortcodes-icon.svg',
                'countdown-for-the-events-calendar' => 'event-countdown-icon.svg',
                'event-page-templates-addon-for-the-events-calendar' => 'event-single-page-icon.svg',
                'events-search-addon-for-the-events-calendar' => 'events-search-icon.svg',
                'events-widgets-pro' => 'events-widgets-icon.svg',
                'event-single-page-builder-pro' => 'event-single-page-icon.svg',
                'events-calendar-modules-for-divi' => 'events-calendar-modules-for-divi.svg',
                'events-calendar-modules-for-divi-pro' => 'events-calendar-modules-for-divi.svg',
                'events-speakers-and-sponsors' => 'events-speakers-sponsors-icon.png',
            ];
            if (isset($logos_arr[$slug])) {
                return $logo_url = plugin_dir_url(__FILE__) . 'assets/images/' . $logos_arr[$slug];
            } else {
                return $logo_url = plugin_dir_url(__FILE__) . 'assets/images/the-events-calendar-addon-icon.svg';
            }

        }
        public function ect_disable_free_plugins()
        {
            if (isset($this->pro_plugins)) {
                foreach ($this->pro_plugins as $plugin) {
                    if (isset($plugin['incompatible']) && $plugin['incompatible'] != null) {
                        $this->disable_plugins[$plugin['incompatible']] = array('pro' => $plugin['slug']);
                    }
                }
            }
        }
    }

    /**
     *
     * initialize the main dashboard class with all required parameters
     */

    function cool_plugins_events_addon_settings_page($tag, $settings_page_slug, $dashboard_heading)
    {
        $event_page = cool_plugins_events_addons::init();
        $event_page->show_plugins($tag, $settings_page_slug, $dashboard_heading);

    }

}
