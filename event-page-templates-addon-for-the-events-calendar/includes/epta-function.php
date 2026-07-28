<?php
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
/**
 * Template helper wrappers.
 */
namespace eptafunctions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once EPTA_PLUGIN_DIR . 'includes/class-epta-event-renderer.php';

/**
 * @return EPTA_Event_Renderer
 */
function epta_renderer() {
	return EPTA_Event_Renderer::instance();
}

function epta_dynamic_class() {
	return epta_renderer()->dynamic_class();
}

function epta_get_passed_event_notice() {
	return epta_renderer()->get_passed_event_notice();
}

function epta_get_event_status( $event_id ) {
	return epta_renderer()->get_event_status( $event_id );
}

function epta_should_show_countdown( $event_id, $seconds ) {
	return epta_renderer()->should_show_countdown( $event_id, $seconds );
}

function epta_get_content( $more_link_text = '(more...)', $stripteaser = 0, $more_file = '' ) {
	return epta_renderer()->get_content( $more_link_text, $stripteaser, $more_file );
}

function epta_custom_style() {
	return epta_renderer()->custom_style();
}

function epta_share_button( $event_id ) {
	return epta_renderer()->share_button( $event_id );
}

function epta_event_schedule( $event_id, $tecset_date_format ) {
	return epta_renderer()->event_schedule( $event_id, $tecset_date_format );
}

function epta_tribe_event_time( $post_id, $display = true ) {
	return epta_renderer()->tribe_event_time( $post_id, $display );
}

function ect_get_url_by_slug( $slug ) {
	$page_url_id   = get_page_by_path( sanitize_text_field( $slug ) );
	$page_url_link = get_permalink( $page_url_id );

	return esc_url( $page_url_link );
}
