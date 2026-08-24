<?php
/**
 * This file is used to add filter in single page of the event calender plugin
 * 
 * @package the-events-calendar-single-event-templates-addon-free/includes
 */
namespace EPTAFilterCls;
if ( ! defined( 'ABSPATH' ) ) exit;
 if(!class_exists('EPTAFilterCls')){
     Class  EPTAFilterCls{
        public function __construct()
        {
            $this->epta_single_event_page_filter();
           
        }
          /**
         * custom event page
         */

        public function epta_single_event_page_filter()
        {
            
            add_filter('tribe_events_template_single-event.php', array($this,'epta_tribe_events_single_event_page'), 99, 1);
        }

        /**
         * Get single event page file
         */
        public function epta_tribe_events_single_event_page($tec_file)
        {   
            if(  basename($tec_file) !== 'single-event.php' && basename($tec_file) !== 'blank.php'){
                return $tec_file;
            }
            $get_temp_id = get_option('tec_tribe_single_event_page');
            $EPTA_get_all_event = get_post_meta($get_temp_id, 'epta-apply-on', true);
            $custom_event_page = EPTA_PLUGIN_DIR .'includes/epta-single-event-page.php';
            $EPTA_event_id = get_the_id();
            if ($EPTA_get_all_event == 'specific-tag' || $EPTA_get_all_event == 'specific-cate') {
                $EPTA_get_cat = $this->epta_get_single_event_categoery($EPTA_event_id);
                $EPTA_get_tag = $this->epta_get_single_event_tag($EPTA_event_id);
                $EPTA_comp_cat_result = array();

                if ($EPTA_get_all_event == 'specific-cate' && !empty($EPTA_get_cat)) {
                    $EPTA_get_selected_cat = $this->epta_meta_slug_list($get_temp_id, 'epta-categoery');
                    $EPTA_comp_cat_result = array_intersect($EPTA_get_cat, $EPTA_get_selected_cat);
                } elseif ($EPTA_get_all_event == 'specific-tag' && !empty($EPTA_get_tag)) {
                    $EPTA_get_selected_tag = $this->epta_meta_slug_list($get_temp_id, 'epta-tag');
                    $EPTA_comp_cat_result = array_intersect($EPTA_get_tag, $EPTA_get_selected_tag);
                }

                if (!empty($EPTA_comp_cat_result)) {
                    return $custom_event_page;
                }
            } elseif ($EPTA_get_all_event == 'all-event') {
                return $custom_event_page;
            } elseif ($EPTA_get_all_event == 'specific-event') {
                $specific_event = $this->epta_get_event($EPTA_event_id);
                $EPTA_get_selected_event = array_map('sanitize_text_field', get_post_meta($get_temp_id, 'epta-specific-event', true));
                $EPTA_comp_specific_result = array_intersect($specific_event, $EPTA_get_selected_event);

                if (!empty($EPTA_comp_specific_result)) {
                    return $custom_event_page;
                }
            }

            return $tec_file;
        }

        /**
         * Normalize stored multiselect meta to a flat slug list.
         *
         * @param int    $post_id Post ID.
         * @param string $key     Meta key.
         * @return array
         */
        private function epta_meta_slug_list( $post_id, $key ) {
            $raw = get_post_meta( $post_id, $key, true );
            if ( empty( $raw ) ) {
                return array();
            }
            if ( ! is_array( $raw ) ) {
                $raw = array( $raw );
            }
            $out = array();
            foreach ( $raw as $slug ) {
                if ( is_string( $slug ) || is_numeric( $slug ) ) {
                    $out[] = sanitize_text_field( (string) $slug );
                }
            }
            return array_values( array_filter( $out ) );
        }

       

        /**
         * get categoery
         */
        public function epta_get_single_event_categoery($EPTA_event_id){
                    
            $EPTA_get_cat = get_the_terms($EPTA_event_id, 'tribe_events_cat' );
            if ($EPTA_get_cat && ! is_wp_error($EPTA_get_cat)) :
                $EPTA_tslugs_arr = array();
                foreach ($EPTA_get_cat as $EPTA_slug_name) {
                $EPTA_tslugs_arr[] = $EPTA_slug_name->slug;
            }
            $EPTA_cat_name = $EPTA_tslugs_arr;
            return $EPTA_cat_name;
            endif;
        }


        /**
         * Get event
         */
        public function epta_get_event($EPTA_event_id){
            
            $EPTA_events = get_post($EPTA_event_id);
            $EPTA_etslugs_arr = array();
            $EPTA_etslugs_arr[] = $EPTA_events->post_name;
            $EPTA_event_title = $EPTA_etslugs_arr;
            return $EPTA_event_title;
        }

        /**
         * Get tag
         */
        public function epta_get_single_event_tag($EPTA_event_id) {
            $EPTA_get_tag = get_the_terms($EPTA_event_id, 'post_tag');
            if ($EPTA_get_tag && !is_wp_error($EPTA_get_tag)) {
                $EPTA_tslugs_arr = array();
                foreach ($EPTA_get_tag as $EPTA_slug_name) {
                    $EPTA_tslugs_arr[] = sanitize_text_field($EPTA_slug_name->slug);
                }
                return $EPTA_tslugs_arr;
            }
            return array();
        }
    } // End class
}
new EPTAFilterCls();
 
