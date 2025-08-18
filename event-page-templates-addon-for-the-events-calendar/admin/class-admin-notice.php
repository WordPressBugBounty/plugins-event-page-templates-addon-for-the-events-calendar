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
            return self::$instance = new self;
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

            if( array_key_exists( $notice['id'], $this->messages ) ){

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
            $logo = (isset($notice['logo']) && !empty($notice['logo'])) ? esc_url( $notice['logo'] ) : null ;
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
                                            'logo'=>$logo,
                                            'slug' => $slug,
                                            'plugin_name' => $plugin_name,
                                            'review_url' => $review_url,
                                            'review_interval' => $review_interval
                                        );
                                        
            add_action('admin_notices', array($this, 'epta_show_notice'));
            add_action( 'admin_print_scripts', array($this, 'epta_load_script' ) );
            add_action('wp_ajax_epta_admin_notice',  array($this,'epta_admin_notice_dismiss'));
            add_action('wp_ajax_epta_admin_review_notice_dismiss', array($this, 'epta_admin_review_notice_dismiss'));
        }

        /**
    	 * Load script to dismiss notices.
    	 *
    	 * @return void
    	 */
    	public function epta_load_script() {    	
            wp_register_style( 'epta-review-css', EPTA_PLUGIN_URL . 'assets/css/epta-admin-notice.css', null, null, 'all' );
            wp_enqueue_style( 'epta-review-css');
            // Enqueue admin notices JS to handle dismiss actions instead of inline script
            wp_enqueue_script( 'epta-review-js', EPTA_PLUGIN_URL . 'assets/js/epta-admin-notice.js', array( 'jquery' ), EPTA_PLUGIN_CURRENT_VERSION, true );
			
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
                    }else{
                        $this->epta_simple_notice($id, $message );
                    }
                }
            }
        }

        /**
         * Due to the nature of private function. This must not be called directly
         * Create simple text/html admin notice and initialize required JS
         * @param array $message This is an array of message object
         */
        private function epta_simple_notice($id, $message ){
           
            if( get_option($id . '_remove_notice') ) return;
            
            $classes = 'notice ' . trim( $message['type'] ) . ' is-dismissible ' . trim( $message['class'] );
            $nonce = wp_create_nonce( $id . '_notice_nonce' );
            $img_path= ( isset( $message['logo'] ) && !empty($message['logo'] ) ) ? esc_url($message['logo']) : null;
            if( $img_path != null ){
                $image_html ='<div class="logo_container"><a href="'.esc_url($url).'"><img src="'.esc_url($img_path).'" style="max-width:70px;"></a></div>';
            }
            else{
                $image_html ='';
            }
            printf( '<div class="%1$s %2$s epta-simple-notice" data-ajax-url="%3$s" data-wp-nonce="%4$s" data-plugin-slug="%5$s">%6$s<div class="message_container"><p>%7$s</p></div></div>', 
                esc_attr($id . '_admin_notice'), 
                esc_attr($classes), 
                esc_url(admin_url('admin-ajax.php')), 
                esc_attr($nonce), 
                esc_attr($id), 
                $image_html, 
                wp_kses_post($message['message']) 
            );
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
            $slug = $messageObj['slug'];
            $days = $messageObj['review_interval'];
                       
            if(get_option( 'tecset-installDate' )){
                // get installation dates and rated settings
                //$installation_date =date( 'Y-m-d h:i:s', get_option( 'ect-installDate' ));
                $installation_date =date( 'Y-m-d h:i:s', strtotime(get_option( 'tecset-installDate' )) );
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
                $display_date = date( 'Y-m-d h:i:s' );
                $install_date= new DateTime( $installation_date );
                $current_date = new DateTime( $display_date );
                $difference = $install_date->diff($current_date);
                $diff_days= $difference->days;
              
                // check if installation days is greator then week
              if (isset($diff_days) && $diff_days>= $days ) {
                    $content = $this->epta_create_notice_content( $id, $messageObj );
                    printf('%s', $content);
                }
        }

        /**
         * Generate review notice HTMl with all required css & js
         *
         * @param array $messageObj array of a message object 
         **/ 
       function epta_create_notice_content( $id, $messageObj ){

        $ajax_url=esc_url( admin_url( 'admin-ajax.php' ) );
        $ajax_callback = 'epta_admin_review_notice_dismiss';
        $wrap_cls="notice notice-info is-dismissible";
        $img_path= ( isset( $messageObj['logo'] ) && !empty($messageObj['logo'] ) ) ? esc_url($messageObj['logo']) : null;
        $slug = isset( $messageObj['slug'] ) ? sanitize_key( $messageObj['slug'] ) : '';
        $plugin_name= isset( $messageObj['plugin_name'] ) ? sanitize_text_field( $messageObj['plugin_name'] ) : '';
        $like_it_text=esc_html__( 'Rate Now! ★★★★★', 'atlt2' );
        $already_rated_text=esc_html__( 'I already rated it', 'atlt2' );
        $not_like_it_text=esc_html__( 'Not Interested', 'atlt2' );
        $plugin_link=  isset( $messageObj['review_url'] ) ? esc_url( $messageObj['review_url'] ) : '';
        $pro_url=esc_url('https://1.envato.market/calendar');
        $review_nonce = esc_attr( wp_create_nonce( $id . '_review_nonce' ) );
        $message = sprintf(
                __( 'Thanks for using <b>%s</b> - WordPress plugin.<br/>We hope you liked it!<br/>Please give us a quick rating, it works as a boost for us to keep working on more <a href="https://coolplugins.net/?utm_source=ectbe_plugin&utm_medium=inside&utm_campaign=coolplugins&utm_content=review_notice" target="_blank"><strong>Cool Plugins</strong></a>!<br/>', 'atlt2' ),
                esc_html( $plugin_name )
            );
            $message_safe = wp_kses_post( $message );
        
            // HTML markup
            $html  = '<div data-ajax-url="%8$s" data-plugin-slug="%11$s" data-wp-nonce="%12$s" id="%13$s" data-ajax-callback="%9$s" class="%11$s-feedback-notice-wrapper %1$s">';
        
        if( $img_path != null ){
            $html .= '<div class="logo_container"><a href="%5$s"><img src="%2$s" alt="%3$s" style="max-width:80px;"></a></div>';
        }

        $html .='<div class="message_container">%4$s
        <div class="callto_action">
        <ul>
            <li class="love_it"><a href="%5$s" class="like_it_btn button button-primary" target="_new" title="%6$s">%6$s</a></li>
            <li class="already_rated"><a href="javascript:void(0);" class="already_rated_btn button %11$s_dismiss_notice" title="%7$s">%7$s</a></li>  
            <li class="already_rated"><a href="javascript:void(0);" class="already_rated_btn button %11$s_dismiss_notice" title="%10$s">%10$s</a></li>    
            
        </ul>
        <div class="clrfix"></div>
        </div>
        </div>
        </div>';

        return sprintf(
                $html,
                esc_attr( $wrap_cls ),        // %1$s
                $img_path,                    // %2$s
                esc_attr( $plugin_name ),      // %3$s
                $message_safe,                 // %4$s
                $plugin_link,                  // %5$s
                $like_it_text,                  // %6$s
                $already_rated_text,            // %7$s
                $ajax_url,                      // %8$s
                esc_attr( $ajax_callback ),     // %9$s
                $not_like_it_text,              // %10$s
                esc_attr( $slug ),               // %11$s
                $review_nonce,                  // %12$s
                $id,                       // %13$s
                $pro_url                        // %14$s
            );
        
       }

       /**
        * This function will dismiss the review notice.
        * This is called by a wordpress ajax hook
        */
        public function epta_admin_review_notice_dismiss(){
            $id = isset($_REQUEST['id'])?sanitize_text_field($_REQUEST['id']):'';
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

        /************************************************************
         * This function will dismiss the text/html admin notice    *
         * This is called by a wordpress ajax hook                  *
         ************************************************************/
        public function epta_admin_notice_dismiss()
        {  
            $id = isset($_REQUEST['id'])?sanitize_text_field($_REQUEST['id']):'';
            $wp_nonce = $id . '_notice_nonce';
            if ( ! check_ajax_referer($wp_nonce,'_nonce', false ) ) {
                die( 'nonce verification failed!' );
            }else{
                $us=update_option( $id . '_remove_notice','yes' );
                die( 'Admin message removed!' );
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
 