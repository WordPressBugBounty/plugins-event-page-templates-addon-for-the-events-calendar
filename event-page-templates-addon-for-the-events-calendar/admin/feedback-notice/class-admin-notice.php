<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Admin notice class for wordpress plugin.
 * This class can not be initialized or extended.
 */

/**************************************************************************************************
 *  HOW TO USE.
 * After including this file, use the below example to start creating admin notice / review box
 *
 * Two arguments, id & message are required and can not be ommitied.
 * id must be unique for every message or it will override the previous message with same id.
 * 
 *               create a simple admin text message
 *   epta_pro_create_admin_notice( array('id'=>'bp-greeting-mesage','message'=>'Hey there!') );
 * 
 *              create a admin text error message
 * epta_pro_create_admin_notice( array('id'=>'bp-error-mesage','message'=>'this is an example of error!','type'=>'error') );
 * The argument 'type' can be: error, success, warning
 * 
 *              create a review box by passing minimum arguments
 * $slug = 'bp';
 * update_option($slug . '_activation_time,strtotime('now') ); // must create an activation time 
 * epta_pro_create_admin_notice( 
 *          array(
 *              'id'=>'bp_review_box',  // required and must be unique
 *              'slug'=>$slug,      // required in case of review box
 *              'review'=>true,     // required and set to be true for review box
 *              'review_url'=>'http://coolplugins.net', // required
 *              'plugin_name'=>'Boiler Plate Plugin',    // required
 *              'logo'=>'http://example.com/logo.png',   // optional: it will display logo
 *              'review_interval'=>5                    // optional: this will display review notice
 *                                                      //   after 5 days from the installation_time
 *                                                      // default is 3
 *          )
 * );
 * 
 * NOTE: Review box does not be displayed unless the $slug _activation_time is equals or
 * more than the 3 days from current time. This can also be changed by setting 'review_interval' arguments
 ***************************************************************************************************** 
 */
//phpcs disable WordPress.Security.EscapeOutput.OutputNotEscaped
if (!class_exists('epta_admin_notices')):

    final class epta_admin_notices
    {

        private static $instance = null;
        private $messages = array();
        private $version = '1.0.0';

        /**
         * initialize the class with single instance
         */
        public static function epta_create_notice()
        {
            if (!empty(self::$instance)) {
                return self::$instance;
            }
            $instance = new self;
            // Hook into admin_enqueue_scripts for notice positioning with priority 20 to run after other styles
            add_action('admin_enqueue_scripts', array($instance, 'add_notice_positioning_inline'), 20);
            return self::$instance = $instance;
        }

        /**
         * add messages for admin notice
         * @param array $notice this array contains $id,$message,$type,$class,$id
         *
         */
        public function epta_add_message($notice)
        {
            if( !isset( $notice['id']) || empty($notice['id']) ){
                $this->epta_show_error('id is required for integrating admin notice.');
                return;
            }

            if ( isset($notice['review']) && true != (bool)$notice['review'] && ( !isset($notice['message']) || empty($notice['message']) )) {
                $this->epta_show_error('message can not be null. You must provide some text for message field');
                return;
            }
            $message = (isset($notice['message']) && !empty($notice['message'])) ?  wp_kses_post( $notice['message'], 'post' ) : null ;
            $type = (isset($notice['type']) && !empty($notice['type'])) ? 'notice-' . sanitize_text_field( $notice['type'] ) : 'notice-success' ;
            $class = (isset($notice['class']) && !empty($notice['class'])) ? sanitize_text_field( $notice['class'] ): '';
            $review = (bool)(isset($notice['review'] ) && !empty( $notice['review'] ) ) ? sanitize_text_field( $notice['review'] ) : false;
            $slug = (isset($notice['slug']) && !empty($notice['slug'])) ? sanitize_text_field( $notice['slug'] ): '' ;
            $plugin_name = (isset($notice['plugin_name']) && !empty($notice['plugin_name'])) ? sanitize_text_field( $notice['plugin_name'] ) : '' ;
            $review_url = (isset($notice['review_url']) && !empty($notice['review_url'])) ? esc_url( $notice['review_url'] ) : '' ;
            $review_interval = (isset($notice['review_interval']) && !empty($notice['review_interval'])) ? sanitize_text_field( $notice['review_interval'] ) : '3' ;
            if( $review == true && ( empty( $slug ) || empty( $plugin_name ) || empty( $review_url ) )){
                $this->epta_show_error( 'slug / plugin_name / review_url can not be empty if admin notice is set to review' );
                return;
            }
            $this->messages[$notice['id']] = array(
                                            'message' => $message,
                                            'type' => $type,
                                            'class' => $class,
                                            'review' => $review,
                                            'slug' => $slug,
                                            'plugin_name' => $plugin_name,
                                            'review_url' => $review_url,
                                            'review_interval' => $review_interval
                                        );
                                        
            add_action('admin_notices', array($this, 'epta_show_notice'));
            add_action('wp_ajax_epta_admin_review_notice_dismiss', array($this, 'epta_admin_review_notice_dismiss'));
        }

        /**
         * Create simple admin notice
         */
        public function epta_show_notice()
        {
            if (count($this->messages) > 0) {
                
                foreach ($this->messages as $id => $message) {
                    if( true == (bool) $message['review'] ){
                        $this->epta_admin_notice_for_review( $id, $message);
                    }
                }
            }
            return;
        }

        /**
         * This function decides if its good to show the review notice or not
         * Review notice will only be displayed if $slug_activation_time is greater or equals to the 3 days
         */
        private function epta_admin_notice_for_review( $id, $messageObj ){
            // Everyone should not be able see the review message
            if( !current_user_can( 'update_plugins' ) ){
                return;
            }
            $days = $messageObj['review_interval'];
                       
            if(get_option( 'tecset-installDate' )){
                // get installation dates and rated settings
                //$installation_date =date( 'Y-m-d h:i:s', get_option( 'ect-installDate' ));
                $installation_date =gmdate( 'Y-m-d h:i:s', strtotime(get_option( 'tecset-installDate' )) );
            }else{
                $this->epta_show_error('Review notice can not be integrated. tecset-installDate option is not set for the plugin');
                return;
            }
                       
               
                $alreadyRated =get_option( 'tecset-ratingDiv' )!=false?get_option( 'tecset-ratingDiv'):"no";

                // check user already rated 
                if( $alreadyRated=="yes") {
                    return;
                }
                
                // grab plugin installation date and compare it with current date
                $display_date = gmdate( 'Y-m-d h:i:s' );
                $install_date= new DateTime( $installation_date );
                $current_date = new DateTime( $display_date );
                $difference = $install_date->diff($current_date);
                $diff_days= $difference->days;
              
                // check if installation days is greator then week
              if (isset($diff_days) && $diff_days>= $days ) {
                wp_enqueue_style( 'epta-review-css', EPTA_PLUGIN_URL . 'admin/feedback-notice/css/epta-admin-notice.css', null, EPTA_PLUGIN_CURRENT_VERSION, 'all' );
                // Enqueue admin notices JS to handle dismiss actions instead of inline script
                wp_enqueue_script( 'epta-review-js', EPTA_PLUGIN_URL . 'admin/feedback-notice/js/epta-admin-notice.js', array( 'jquery' ), EPTA_PLUGIN_CURRENT_VERSION, true );
                    $content = $this->epta_create_notice_content( $id, $messageObj );
                    printf('%s', wp_kses_post( $content ));
                }
        }

        /**
         * Generate review notice HTMl with all required css & js
         *
         * @param array $messageObj array of a message object 
         **/ 
        function epta_create_notice_content( $id, $messageObj ) {
            if ( empty( $id ) || empty( $messageObj['slug'] ) || empty( $messageObj['plugin_name'] ) ) {
                return '';
            }
        
            $ajax_url          = esc_url( admin_url( 'admin-ajax.php' ) );
            $ajax_callback     = 'epta_admin_review_notice_dismiss';
            $wrap_cls          = 'notice notice-info is-dismissible ect-required-plugin-notice';
            $slug              = sanitize_key( $messageObj['slug'] );
            $plugin_name       = sanitize_text_field( $messageObj['plugin_name'] );
            $like_it_text      = esc_html__( 'Rate Now! ★★★★★', 'event-page-templates-addon-for-the-events-calendar' );
            $already_rated_text= esc_html__( 'Already Reviewed', 'event-page-templates-addon-for-the-events-calendar' );
            $not_like_it_text  = esc_html__( 'Not Interested', 'event-page-templates-addon-for-the-events-calendar' );
            $plugin_link       = ! empty( $messageObj['review_url'] ) ? esc_url( $messageObj['review_url'] ) : '#';
            $review_nonce      = wp_create_nonce( $id . '_review_nonce' );
        
            // Safe message with limited HTML tags
            $message = sprintf(
                /* translators: %s: Plugin name. */
                __( 'Thanks for using <b>%s</b> - WordPress plugin. We hope you liked it! <br/>Please give us a quick rating, it works as a boost for us to keep working on more <a href="https://coolplugins.net/?utm_source=ectbe_plugin&utm_medium=inside&utm_campaign=coolplugins&utm_content=review_notice" target="_blank"><strong>Cool Plugins</strong></a>!<br/>', 'event-page-templates-addon-for-the-events-calendar' ),
                esc_html( $plugin_name )
            );
        
            $message_safe = wp_kses(
                $message,
                array(
                    'b'      => array(),
                    'br'     => array(),
                    'a'      => array(
                        'href'   => array(),
                        'target' => array(),
                    ),
                    'strong' => array(),
                )
            );
        
            // HTML output
            $html  = '<div 
                data-ajax-url="%1$s" 
                data-plugin-slug="%2$s" 
                data-wp-nonce="%3$s" 
                id="%4$s" 
                data-ajax-callback="%5$s" 
                class="%2$s-feedback-notice-wrapper %6$s">
                    <div class="message_container">%7$s
                        <div class="callto_action">
                            <ul>
                                <li class="love_it">
                                    <a href="%8$s" class="like_it_btn button button-primary" target="_blank" title="%9$s">%9$s</a>
                                </li>
                                <li class="already_rated">
                                    <a href="#" class="already_rated_btn button %2$s_dismiss_notice" title="%10$s">%10$s</a>
                                </li>  
                                <li class="already_rated">
                                    <a href="#" class="already_rated_btn button %2$s_dismiss_notice" title="%11$s">%11$s</a>
                                </li>    
                            </ul>
                        </div>
                    </div>
                </div>';
        
            return sprintf(
                $html,
                $ajax_url,               // %1$s
                esc_attr( $slug ),       // %2$s
                esc_attr( $review_nonce ),// %3$s
                esc_attr( $id ),         // %4$s
                esc_attr( $ajax_callback ),// %5$s
                esc_attr( $wrap_cls ),   // %6$s
                $message_safe,           // %7$s
                $plugin_link,            // %8$s
                $like_it_text,           // %9$s
                $already_rated_text,     // %10$s
                $not_like_it_text        // %11$s
            );
        }
        

       /**
        * This function will dismiss the review notice.
        * This is called by a wordpress ajax hook
        */
        public function epta_admin_review_notice_dismiss(){
            $id = isset($_REQUEST['id'])?sanitize_text_field(wp_unslash($_REQUEST['id'])):'';
            $nonce_key = $id . '_review_nonce' ;

            if (!check_ajax_referer($nonce_key, '_nonce', false)) {
                echo wp_json_encode(array("error" => "nonce verification failed!"));
                die();
               
            }else{
                update_option( 'tecset-ratingDiv','yes' );
                echo wp_json_encode( array("success"=>"true"));
                die();
            }
        }

        /**************************************************************
         * This function is used by the class for displaying error    *
         *  in case of wrong implementation of the class.             *
         **************************************************************/
        private function epta_show_error($error_text){
            $er = "<div style='text-align:center;margin-left:20px;padding:10px;background-color: #cc0000; color: #fce94f; font-size: x-large;'>";
            $er .= "Error: ".$error_text;
            $er .= "</div>";
            echo wp_kses_post($er);
        }
        /**
         * Check if we're on the plugin admin pages
         *
         * @since 1.0.0
         *
         * @return bool
         */
        private function is_ect_plugin_page() {
            $screen = get_current_screen();
            if ( empty( $screen ) ) {
                return false;
            }
            
            // Check if we're on plugin pages that use the header
            $plugin_pages = array(
                'toplevel_page_cool-plugins-events-addon',
                'events-addons_page_tribe-events-shortcode-template-settings',
                'events-addons_page_cool-events-registration',
            );
            
            return in_array( $screen->id, $plugin_pages, true );
        }

        /**
         * Add inline CSS and JavaScript for notice positioning on plugin pages
         *
         * @since 1.0.0
         *
         * @return void
         */
        public function add_notice_positioning_inline() {
            if ( ! $this->is_ect_plugin_page() ) {
                return;
            }

            // Ensure jQuery is enqueued
            wp_enqueue_script( 'jquery' );

            // Add inline CSS
            $css = "
			/* Notice positioning for plugin pages */
			body.toplevel_page_cool-plugins-events-addon .notice,
			body.toplevel_page_cool-plugins-events-addon .error,
			body.toplevel_page_cool-plugins-events-addon .updated,
			body.toplevel_page_cool-plugins-events-addon .notice-error,
			body.toplevel_page_cool-plugins-events-addon .notice-warning,
			body.toplevel_page_cool-plugins-events-addon .notice-info,
			body.toplevel_page_cool-plugins-events-addon .notice-success,
			body.events-addons_page_tribe-events-shortcode-template-settings .notice,
			body.events-addons_page_tribe-events-shortcode-template-settings .error,
			body.events-addons_page_tribe-events-shortcode-template-settings .updated,
			body.events-addons_page_tribe-events-shortcode-template-settings .notice-error,
			body.events-addons_page_tribe-events-shortcode-template-settings .notice-warning,
			body.events-addons_page_tribe-events-shortcode-template-settings .notice-info,
			body.events-addons_page_tribe-events-shortcode-template-settings .notice-success,
			body.events-addons_page_cool-events-registration .notice,
			body.events-addons_page_cool-events-registration .error,
			body.events-addons_page_cool-events-registration .updated,
			body.events-addons_page_cool-events-registration .notice-error,
			body.events-addons_page_cool-events-registration .notice-warning,
			body.events-addons_page_cool-events-registration .notice-info,
			body.events-addons_page_cool-events-registration .notice-success {
				display: none !important;
				margin-left: 2rem;
			}

			/* Keep inline notices inside license box visible (do NOT move them) */
			body.toplevel_page_cool-plugins-events-addon [class*=\"license-box\"] .notice,
			body.toplevel_page_cool-plugins-events-addon [class*=\"license-box\"] .error,
			body.toplevel_page_cool-plugins-events-addon [class*=\"license-box\"] .updated,
			body.toplevel_page_cool-plugins-events-addon [class*=\"license-box\"] .notice-error,
			body.toplevel_page_cool-plugins-events-addon [class*=\"license-box\"] .notice-warning,
			body.toplevel_page_cool-plugins-events-addon [class*=\"license-box\"] .notice-info,
			body.toplevel_page_cool-plugins-events-addon [class*=\"license-box\"] .notice-success,
			body.events-addons_page_tribe-events-shortcode-template-settings [class*=\"license-box\"] .notice,
			body.events-addons_page_tribe-events-shortcode-template-settings [class*=\"license-box\"] .error,
			body.events-addons_page_tribe-events-shortcode-template-settings [class*=\"license-box\"] .updated,
			body.events-addons_page_tribe-events-shortcode-template-settings [class*=\"license-box\"] .notice-error,
			body.events-addons_page_tribe-events-shortcode-template-settings [class*=\"license-box\"] .notice-warning,
			body.events-addons_page_tribe-events-shortcode-template-settings [class*=\"license-box\"] .notice-info,
			body.events-addons_page_tribe-events-shortcode-template-settings [class*=\"license-box\"] .notice-success,
			body.events-addons_page_cool-events-registration [class*=\"license-box\"] .notice,
			body.events-addons_page_cool-events-registration [class*=\"license-box\"] .error,
			body.events-addons_page_cool-events-registration [class*=\"license-box\"] .updated,
			body.events-addons_page_cool-events-registration [class*=\"license-box\"] .notice-error,
			body.events-addons_page_cool-events-registration [class*=\"license-box\"] .notice-warning,
			body.events-addons_page_cool-events-registration [class*=\"license-box\"] .notice-info,
			body.events-addons_page_cool-events-registration [class*=\"license-box\"] .notice-success {
				display: block !important;
				margin-left: 0;
				margin-right: 0;
				width: auto;
			}

			/* Show notices after they are moved */
			body.toplevel_page_cool-plugins-events-addon .ect-moved-notice,
			body.events-addons_page_tribe-events-shortcode-template-settings .ect-moved-notice,
			body.events-addons_page_cool-events-registration .ect-moved-notice {
				display: block !important;
				margin-left: 2rem;
				margin-right: 2rem;
				width: auto;
			}
			";
            
            // Register and enqueue a style handle for notice positioning if not already done
            if ( ! wp_style_is( 'ect-notice-positioning', 'registered' ) ) {
                wp_register_style( 'ect-notice-positioning', null, null, EPTA_PLUGIN_CURRENT_VERSION );
            }
            wp_enqueue_style( 'ect-notice-positioning' );
            wp_add_inline_style( 'ect-notice-positioning', $css );

            // Add inline JavaScript
            $js = "
			jQuery(document).ready(function($) {
				// Wait for the page to load
				setTimeout(function() {
					// Move ONLY top admin notices (page top) - do not touch inline/content notices
					// Also: jis notice me yeh text aaye, usko move mat karo (neeche hi rahe)
					var skipText = 'to continue receiving updates and priority support.';
					var topNotices = $('#wpbody-content').find(
						'> .notice, > .error, > .updated, > .notice-error, > .notice-warning, > .notice-info, > .notice-success,' +
						'> .wrap > .notice, > .wrap > .error, > .wrap > .updated, > .wrap > .notice-error, > .wrap > .notice-warning, > .wrap > .notice-info, > .wrap > .notice-success'
					);

					var noticesToMove = topNotices.filter(function() {
						var txt = $(this).text() || '';
						return txt.indexOf(skipText) === -1;
					});

					if (noticesToMove.length > 0) {
						var headerContainer = $('.ect-top-header');
						if (headerContainer.length > 0) {
							noticesToMove.detach().insertAfter(headerContainer);
							noticesToMove.addClass('ect-moved-notice');
						}
					}
				}, 100);
			});
			";
            wp_add_inline_script( 'jquery', $js );
        }

    }   // end of main class epta_admin_notices;
endif;
    /********************************************************************************
     * A global function to create admin notice/review box using the above class.   *
     * This function makes it easy to use above class                               *
     ********************************************************************************/
    function epta_create_admin_notice($notice)
    {
        // Do not initialize anything if it's not wordpress admin dashboard
        if (!is_admin()) {
            return;
        }
        
        $main_class = epta_admin_notices::epta_create_notice();
        $main_class->epta_add_message($notice);
        return $main_class;
    }
 