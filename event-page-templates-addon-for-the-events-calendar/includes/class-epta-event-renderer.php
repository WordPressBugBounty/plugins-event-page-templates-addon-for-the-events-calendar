<?php
declare(strict_types=1);

/**
 * Event rendering helpers: schedule markup, share buttons, styles, and status.
 *
 * @package EventPageTemplatesAddon
 */

namespace eptafunctions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Groups event display helpers used by single-event templates.
 */
class EPTA_Event_Renderer {

	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get the active template slug class.
	 *
	 * @return string
	 */
	public function dynamic_class() {
		$tecset_pageid    = get_option( 'tec_tribe_single_event_page' );
		$get_select_temp  = get_post_meta( $tecset_pageid, 'epta-select-temp', true );
		$tecset_page_slug = ( ! empty( $get_select_temp ) ) ? sanitize_text_field( $get_select_temp ) : 'template-1';

		return esc_attr( $tecset_page_slug );
	}

	/**
	 * Capture TEC passed-event notices.
	 *
	 * @return string
	 */
	public function get_passed_event_notice() {
		ob_start();
		tribe_the_notices();

		return ob_get_clean();
	}

	/**
	 * Get the event status slug from The Events Calendar.
	 *
	 * @param int $event_id Event post ID.
	 * @return string Status slug (e.g. canceled, postponed) or empty string.
	 */
	public function get_event_status( $event_id ) {
		if ( function_exists( 'tribe_get_event' ) ) {
			$event = tribe_get_event( $event_id );

			if ( $event && ! empty( $event->event_status ) ) {
				return sanitize_key( $event->event_status );
			}
		}

		$status = get_post_meta( $event_id, '_tribe_events_status', true );

		if ( ! empty( $status ) ) {
			return sanitize_key( $status );
		}

		$legacy_status = get_post_meta( $event_id, '_tribe_events_control_status', true );

		return ! empty( $legacy_status ) ? sanitize_key( $legacy_status ) : '';
	}

	/**
	 * Whether the sidebar countdown should be shown for an event.
	 *
	 * @param int $event_id Event post ID.
	 * @param int $seconds  Seconds remaining until the event start.
	 * @return bool
	 */
	public function should_show_countdown( $event_id, $seconds ) {
		if ( $seconds <= 0 ) {
			return false;
		}

		$status = $this->get_event_status( $event_id );

		return ! in_array( $status, array( 'canceled', 'postponed' ), true );
	}

	/**
	 * Get sanitized event content.
	 *
	 * @param string $more_link_text More link text.
	 * @param int    $stripteaser    Strip teaser flag.
	 * @param string $more_file      More file path.
	 * @return string
	 */
	public function get_content( $more_link_text = '(more...)', $stripteaser = 0, $more_file = '' ) {
		$content             = get_the_content( $more_link_text, $stripteaser, $more_file );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$ept_content         = apply_filters( 'the_excerpt', $content );
		$ept_get_content     = str_replace( ']]>', ']]&gt;', $ept_content );

		return wp_kses_post( $ept_get_content );
	}

	/**
	 * Build inline CSS for template color overrides.
	 *
	 * @return string
	 */
	public function custom_style() {
		$colors = $this->get_template_colors();
// phpcs:ignore PluginCheck.CodeAnalysis.Heredoc.NotAllowed
		$css = <<<CSS
#epta-template.epta-template-1 {
	--epta-primary: {$colors['primary']};
	--epta-secondary: {$colors['secondary']};
	--epta-alternate: {$colors['alternate']};
}
#epta-template.epta-template-1 .epta-light-bg,
#epta-template.epta-template-1 .epta-countdown-cell,
#epta-template.epta-template-1 .epta-sidebar-box .epta-past-event-notice .tribe-events-notices li,
#epta-template.epta-template-1 .epta-sidebar-box .epta-past-event-notice .tribe-events-status-single__header--bold,
#epta-template.epta-template-1 .epta-sidebar-box h2.tribe-events-single-section-title,
#epta-template.epta-template-1 .epta-addto-calendar a,
#epta-template.epta-template-1 .epta-registration-form #rtec .rtec-register-button {
	background-color: var(--epta-primary);
}
#epta-template.epta-template-1 .epta-title-date h2,
#epta-template.epta-template-1 .epta-title-date .tecset-date,
#epta-template.epta-template-1 .epta-countdown-cell,
#epta-template.epta-template-1 .epta-sidebar-box .epta-past-event-notice .tribe-events-notices li,
#epta-template.epta-template-1 .epta-sidebar-box .epta-past-event-notice .tribe-events-status-single__header--bold,
#epta-template.epta-template-1 .epta-sidebar-box h2.tribe-events-single-section-title,
#epta-template.epta-template-1 .epta-addto-calendar a,
#epta-template.epta-template-1 .epta-registration-form #rtec .rtec-register-button,
#epta-template.epta-template-1 .epta-related-title h4,
#epta-template.epta-template-1 .epta-related-title h4 a,
#epta-template.epta-template-1 .epta-related-date {
	color: var(--epta-alternate);
}
#epta-template.epta-template-1 .epta-sidebar-area,
#epta-template.epta-template-1 .epta-map-area .tribe-events-venue-map {
	background-color: var(--epta-secondary);
}
#epta-template.epta-template-1 .epta-share-area a {
	color: var(--epta-primary);
}
#epta-template.epta-template-1 .epta-map-area .tribe-events-venue-map {
	border-color: var(--epta-secondary);
}
#epta-template.epta-template-2,
.epta-template-2 {
	--epta-primary: {$colors['primary']};
	--epta-secondary: {$colors['secondary']};
	--epta-alternate: {$colors['alternate']};
}
.epta-template-2 #epta-tribe-events-content.tribe-events-single .epta-events-cta .epta-events-cta-date .tecset-ev-day,
.epta-template-2 #epta-tribe-events-content.tribe-events-single .epta-events-cta .epta-events-cta-date .tecset-ev-mo,
.epta-template-2 #epta-tribe-events-content.tribe-events-single .epta-events-cta .epta-events-cta-date .tecset-ev-yr,
#epta-template.epta-template-2 .epta-related-title h4 a,
#epta-template.epta-template-2 .epta-related-date,
.epta-template-2 li.tribe-events-nav-previous a,
.epta-template-2 li.tribe-events-nav-next a {
	color: var(--epta-alternate);
}
.epta-template-2 .epta-events-single-left {
	background: var(--epta-secondary);
}
.epta-template-2 .epta-light-bg,
.epta-template-2 h2.tribe-events-single-section-title,
.epta-template-2 h3.tecset-share-title,
.epta-template-2 h3.epta-related-head {
	background: var(--epta-primary);
}
.epta-template-2 h2.tribe-events-single-section-title,
.epta-template-2 h3.tecset-share-title,
.epta-template-2 h3.epta-related-head {
	color: var(--epta-alternate);
}
#tribe-events .epta-template-2 .tribe-events-button {
	color: var(--epta-alternate) !important;
	background: var(--epta-primary) !important;
}
.epta-template-2 .epta-share-area a {
	color: var(--epta-primary);
}
.epta-template-2 .epta-events-meta-group.epta-events-meta-group-details,
.epta-template-2 .epta-events-meta-group.epta-events-meta-group-venue,
.epta-template-2 #epta-tribe-events-content.tribe-events-single .epta-events-meta-group-schedule,
.epta-template-2 .epta-share-area,
.epta-template-2 #epta-tribe-events-content.tribe-events-single .epta-events-single-left {
	border-color: var(--epta-primary);
}
.epta-template-2 .epta-events-meta-group.epta-events-meta-group-details,
.epta-template-2 .epta-events-meta-group.epta-events-meta-group-venue,
.epta-template-2 #epta-tribe-events-content.tribe-events-single .epta-events-meta-group-schedule,
.epta-template-2 .epta-share-area {
	border-top: 2px solid var(--epta-primary);
}
.epta-template-2 #epta-tribe-events-content.tribe-events-single .epta-events-single-left {
	border-right: 2px solid var(--epta-primary);
}
CSS;

		return wp_strip_all_tags( $css );
	}

	/**
	 * Build share-button markup for an event.
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	public function share_button( $event_id ) {
		wp_enqueue_script( 'tecset-sharebutton', EPTA_PLUGIN_URL . 'assets/js/epta-sharebutton.js', array( 'jquery' ), EPTA_PLUGIN_CURRENT_VERSION, true );
		wp_enqueue_style( 'tecset-customicon-css', EPTA_PLUGIN_URL . 'assets/css/epta-custom-icon.css', null, EPTA_PLUGIN_CURRENT_VERSION, 'all' );

		$tecset_get_url      = urlencode( get_permalink( $event_id ) );
		$tecset_gettitle     = htmlspecialchars( urlencode( html_entity_decode( get_the_title( $event_id ), ENT_COMPAT, 'UTF-8' ) ), ENT_COMPAT, 'UTF-8' );
		$subject             = str_replace( '+', ' ', $tecset_gettitle );
		$tecset_twitter_url  = add_query_arg( array( 'text' => $tecset_gettitle, 'url' => $tecset_get_url ), 'https://twitter.com/intent/tweet' );
		$tecset_whatsapp_url = add_query_arg( array( 'text' => $tecset_gettitle . ' ' . $tecset_get_url ), 'https://wa.me/' );
		$tecset_facebook_url = add_query_arg( array( 'u' => $tecset_get_url ), 'https://www.facebook.com/sharer/sharer.php' );
		$tecset_email_url    = add_query_arg( array( 'Subject' => $subject, 'Body' => $tecset_get_url ), 'mailto:' );

		$tecset_sharecontent  = '<h3 class="tecset-share-title">' . __( 'Share This Event', 'event-page-templates-addon-for-the-events-calendar' ) . '</h3>';
		$tecset_sharecontent .= '<a class="tecset-share-link" href="' . esc_url( $tecset_facebook_url ) . '" target="_blank" title="Facebook" aria-haspopup="true"><i class="ect-icon-facebook"></i></a>';
		$tecset_sharecontent .= '<a class="tecset-share-link" href="' . esc_url( $tecset_twitter_url ) . '" target="_blank" title="Twitter" aria-haspopup="true"><i class="ect-icon-twitter"></i></a>';
		$tecset_sharecontent .= '<a class="tecset-email" href="' . esc_url( $tecset_email_url ) . ' "title="Email" aria-haspopup="true"><i class="ect-icon-mail"></i></a>';
		$tecset_sharecontent .= '<a class="tecset-share-link" href="' . esc_url( $tecset_whatsapp_url ) . '" target="_blank" title="WhatsApp" aria-haspopup="true"><i class="ect-icon-whatsapp"></i></a>';

		return $tecset_sharecontent;
	}

	/**
	 * Generate event date schedule HTML.
	 *
	 * @param int    $event_id           Event post ID.
	 * @param string $tecset_date_format Selected date format key.
	 * @return string
	 */
	public function event_schedule( $event_id, $tecset_date_format ) {
		/*Date Format START*/
		$tecset_ev_time        = epta_tribe_event_time( $event_id, false );
		$tecset_event_schedule = '';

		$tecset_start_date_attr = esc_attr(
			tribe_get_start_date( $event_id, false, 'Y-m-dTg:i' )
		);

		$tecset_end_date_attr = esc_attr(
			tribe_get_end_date( $event_id, false, 'Y-m-dTg:i' )
		);

	// $tecset_ev_time=$this->ect_tribe_event_time($event_id,false);
	if ( $tecset_date_format == 'DM' ) {
			$tecset_event_schedule = '<div class="tecset-date"  itemprop="startDate" content="' . $tecset_start_date_attr . '">';
			if ( ! tribe_event_is_multiday( $event_id ) ) {
				$tecset_event_schedule .= '<span class="tecset-ev-day">' . tribe_get_start_date( $event_id, false, 'd' ) . '</span>
					<span class="tecset-ev-mo">' . tribe_get_start_date( $event_id, false, 'M' ) . '</span>
					</div>';
			} else {
				$tecset_event_schedule .= '<span class="tecset-ev-day">' . tribe_get_start_date( $event_id, false, 'd' ) . '</span>
					<span class="tecset-ev-mo">' . tribe_get_start_date( $event_id, false, 'M' ) . '</span>
					<span class="tecset-ev-blank"> - </span>
					<span class="tecset-ev-day">' . tribe_get_end_date( $event_id, false, 'd' ) . '</span>
					<span class="tecset-ev-mo">' . tribe_get_end_date( $event_id, false, 'M' ) . '</span>
					</div>';
			}
				$tecset_event_schedule .= '<meta itemprop="endDate" content="' . $tecset_end_date_attr . '">';
		} elseif ( $tecset_date_format == 'MD' ) {
				$tecset_event_schedule = '<div class="tecset-date" itemprop="startDate" content="' . $tecset_start_date_attr . '">';
			if ( ! tribe_event_is_multiday( $event_id ) ) {
				$tecset_event_schedule .= '<span class="tecset-ev-mo">' . tribe_get_start_date( $event_id, false, 'M' ) . '</span>
					<span class="tecset-ev-day">' . tribe_get_start_date( $event_id, false, 'd' ) . '</span>
					</div>';
			} else {
				$tecset_event_schedule .= '<span class="tecset-ev-mo">' . tribe_get_start_date( $event_id, false, 'M' ) . '</span>
					<span class="tecset-ev-day">' . tribe_get_start_date( $event_id, false, 'd' ) . '</span>
					<span class="tecset-ev-blank"> - </span>
					<span class="tecset-ev-mo">' . tribe_get_end_date( $event_id, false, 'M' ) . '</span>
					<span class="tecset-ev-day">' . tribe_get_end_date( $event_id, false, 'd' ) . '</span>
					</div>';
			}
				$tecset_event_schedule .= '<meta itemprop="endDate" content="' . $tecset_end_date_attr . '">';
		} elseif ( $tecset_date_format == 'FD' ) {
				$tecset_event_schedule = '<div class="tecset-date" itemprop="startDate" content="' . $tecset_start_date_attr . '">';
			if ( ! tribe_event_is_multiday( $event_id ) ) {
				$tecset_event_schedule .= '<span class="tecset-ev-mo">' . tribe_get_start_date( $event_id, false, 'F' ) . '</span>
					<span class="tecset-ev-day">' . tribe_get_start_date( $event_id, false, 'd' ) . '</span>
					</div>';
			} else {
				$tecset_event_schedule .= '<span class="tecset-ev-mo">' . tribe_get_start_date( $event_id, false, 'F' ) . '</span>
					<span class="tecset-ev-day">' . tribe_get_start_date( $event_id, false, 'd' ) . '</span>
					<span class="tecset-ev-blank"> - </span>
					<span class="tecset-ev-mo">' . tribe_get_end_date( $event_id, false, 'F' ) . '</span>
					<span class="tecset-ev-day">' . tribe_get_end_date( $event_id, false, 'd' ) . '</span>
					</div>';
			}
				$tecset_event_schedule .= '<meta itemprop="endDate" content="' . $tecset_end_date_attr . '">';
		} elseif ( $tecset_date_format == 'DF' ) {
				$tecset_event_schedule = '<div class="tecset-date" itemprop="startDate" content="' . $tecset_start_date_attr . '">';
			if ( ! tribe_event_is_multiday( $event_id ) ) {
				$tecset_event_schedule .= '<span class="tecset-ev-day">' . tribe_get_start_date( $event_id, false, 'd' ) . '</span>
					<span class="tecset-ev-mo">' . tribe_get_start_date( $event_id, false, 'F' ) . '</span>
					</div>';
			} else {
				$tecset_event_schedule .= '<span class="tecset-ev-day">' . tribe_get_start_date( $event_id, false, 'd' ) . '</span>
					<span class="tecset-ev-mo">' . tribe_get_start_date( $event_id, false, 'F' ) . '</span>
					<span class="tecset-ev-blank"> - </span>
					<span class="tecset-ev-day">' . tribe_get_end_date( $event_id, false, 'd' ) . '</span>
					<span class="tecset-ev-mo">' . tribe_get_end_date( $event_id, false, 'F' ) . '</span>
					</div>';
			}
				$tecset_event_schedule .= '<meta itemprop="endDate" content="' . $tecset_end_date_attr . '">';
		} elseif ( $tecset_date_format == 'FD,Y' ) {
				$tecset_event_schedule = '<div class="tecset-date"  itemprop="startDate" content="' .$tecset_start_date_attr . '">';
			if ( ! tribe_event_is_multiday( $event_id ) ) {
				$tecset_event_schedule .= '<span class="tecset-ev-mo">' . tribe_get_start_date( $event_id, false, 'F' ) . '</span>
					<span class="tecset-ev-day">' . tribe_get_start_date( $event_id, false, 'd' ) . ', </span>
					<span class="tecset-ev-yr">' . tribe_get_start_date( $event_id, false, 'Y' ) . '</span>
					</div>';
			} else {
				$tecset_event_schedule .= '<span class="tecset-ev-mo">' . tribe_get_start_date( $event_id, false, 'F' ) . '</span>
					<span class="tecset-ev-day">' . tribe_get_start_date( $event_id, false, 'd' ) . ', </span>
					<span class="tecset-ev-yr">' . tribe_get_start_date( $event_id, false, 'Y' ) . '</span>
					<span class="tecset-ev-blank"> - </span>
					<span class="tecset-ev-mo">' . tribe_get_end_date( $event_id, false, 'F' ) . '</span>
					<span class="tecset-ev-day">' . tribe_get_end_date( $event_id, false, 'd' ) . ', </span>
					<span class="tecset-ev-yr">' . tribe_get_end_date( $event_id, false, 'Y' ) . '</span>
					</div>';
			}
				$tecset_event_schedule .= '<meta itemprop="endDate" content="' . $tecset_end_date_attr . '">';
		} elseif ( $tecset_date_format == 'MD,Y' ) {
				$tecset_event_schedule = '<div class="tecset-date"  itemprop="startDate" content="' . $tecset_start_date_attr . '">';
			if ( ! tribe_event_is_multiday( $event_id ) ) {
				$tecset_event_schedule .= '<span class="tecset-ev-mo">' . tribe_get_start_date( $event_id, false, 'M' ) . '</span>
					<span class="tecset-ev-day">' . tribe_get_start_date( $event_id, false, 'd' ) . ', </span>
					<span class="tecset-ev-yr">' . tribe_get_start_date( $event_id, false, 'Y' ) . '</span>
					</div>';
			} else {
				$tecset_event_schedule .= '<span class="tecset-ev-mo">' . tribe_get_start_date( $event_id, false, 'M' ) . '</span>
					<span class="tecset-ev-day">' . tribe_get_start_date( $event_id, false, 'd' ) . ', </span>
					<span class="tecset-ev-yr">' . tribe_get_start_date( $event_id, false, 'Y' ) . '</span>
					<span class="tecset-ev-blank"> - </span>
					<span class="tecset-ev-mo">' . tribe_get_end_date( $event_id, false, 'M' ) . '</span>
					<span class="tecset-ev-day">' . tribe_get_end_date( $event_id, false, 'd' ) . ', </span>
					<span class="tecset-ev-yr">' . tribe_get_end_date( $event_id, false, 'Y' ) . '</span>
					</div>';
			}
				$tecset_event_schedule .= '<meta itemprop="endDate" content="' . $tecset_end_date_attr . '">';
		} elseif ( $tecset_date_format == 'MD,YT' ) {
				$tecset_event_schedule = '<div class="tecset-date" itemprop="startDate" content="' . $tecset_start_date_attr . '">';
			if ( ! tribe_event_is_multiday( $event_id ) ) {
				$tecset_event_schedule .= '<span class="tecset-ev-mo">' . tribe_get_start_date( $event_id, false, 'M' ) . '</span>
					<span class="tecset-ev-day">' . tribe_get_start_date( $event_id, false, 'd' ) . ', </span>
					<span class="tecset-ev-yr">' . tribe_get_start_date( $event_id, false, 'Y' ) . '</span>
					<span class="tecset-ev-time"><span class="ect-icon"><i class="ect-icon-clock" aria-hidden="true"></i></span> ' . $tecset_ev_time . '</span>
					</div>';
			} else {
				$tecset_event_schedule .= '<span class="tecset-ev-mo">' . tribe_get_start_date( $event_id, false, 'M' ) . '</span>
					<span class="tecset-ev-day">' . tribe_get_start_date( $event_id, false, 'd' ) . ', </span>
					<span class="tecset-ev-yr">' . tribe_get_start_date( $event_id, false, 'Y' ) . '</span>
					<span class="tecset-ev-time">(' . tribe_get_start_date( $event_id, false, 'g:i A' ) . ')</span>
					<span class="tecset-ev-blank"> - </span>
					<span class="tecset-ev-mo">' . tribe_get_end_date( $event_id, false, 'M' ) . '</span>
					<span class="tecset-ev-day">' . tribe_get_end_date( $event_id, false, 'd' ) . ', </span>
					<span class="tecset-ev-yr">' . tribe_get_end_date( $event_id, false, 'Y' ) . '</span>
					<span class="tecset-ev-time">(' . tribe_get_end_date( $event_id, false, 'g:i A' ) . ')</span>
					</div>';
			}
				$tecset_event_schedule .= '<meta itemprop="endDate" content="' . $tecset_end_date_attr . '">';
		} elseif ( $tecset_date_format == 'full' ) {
				$tecset_event_schedule = '<div class="tecset-date" itemprop="startDate" content="' . $tecset_start_date_attr . '">';
			if ( ! tribe_event_is_multiday( $event_id ) ) {
				$tecset_event_schedule .= '<span class="tecset-ev-day">' . tribe_get_start_date( $event_id, false, 'd' ) . '</span>
						<span class="tecset-ev-mo">' . tribe_get_start_date( $event_id, false, 'F' ) . '</span>
						<span class="tecset-ev-yr">' . tribe_get_start_date( $event_id, false, 'Y' ) . '</span>
						<span class="tecset-ev-time">
						<span class="ect-icon"><i class="ect-icon-clock" aria-hidden="true"></i></span> ' . $tecset_ev_time . '</span>
						</div>';
			} else {
				$tecset_event_schedule .= '<span class="tecset-ev-day">' . tribe_get_start_date( $event_id, false, 'd' ) . '</span>
						<span class="tecset-ev-mo">' . tribe_get_start_date( $event_id, false, 'F' ) . '</span>
						<span class="tecset-ev-yr">' . tribe_get_start_date( $event_id, false, 'Y' ) . '</span>
						<span class="tecset-ev-time">(' . tribe_get_start_date( $event_id, false, 'g:i A' ) . ')</span>
						<span class="tecset-ev-blank"> - </span>
						<span class="tecset-ev-day">' . tribe_get_end_date( $event_id, false, 'd' ) . '</span>
						<span class="tecset-ev-mo">' . tribe_get_end_date( $event_id, false, 'F' ) . '</span>
						<span class="tecset-ev-yr">' . tribe_get_end_date( $event_id, false, 'Y' ) . '</span>
						<span class="tecset-ev-time">(' . tribe_get_end_date( $event_id, false, 'g:i A' ) . ')</span>
						</div>';
			}
					$tecset_event_schedule .= '<meta itemprop="endDate" content="' . $tecset_end_date_attr . '">';
		} elseif ( $tecset_date_format == 'dFY' ) {
				$tecset_event_schedule = '<div class="tecset-date" itemprop="startDate" content="' . $tecset_start_date_attr . '">';
			if ( ! tribe_event_is_multiday( $event_id ) ) {
				$tecset_event_schedule .= '<span class="tecset-ev-day">' . tribe_get_start_date( $event_id, false, 'd' ) . '</span>
					<span class="tecset-ev-mo">' . tribe_get_start_date( $event_id, false, 'F' ) . '</span>
					<span class="tecset-ev-yr">' . tribe_get_start_date( $event_id, false, 'Y' ) . '</span>
					</div>';
			} else {
				$tecset_event_schedule .= '<span class="tecset-ev-day">' . tribe_get_start_date( $event_id, false, 'd' ) . '</span>
					<span class="tecset-ev-mo">' . tribe_get_start_date( $event_id, false, 'F' ) . '</span>
					<span class="tecset-ev-yr">' . tribe_get_start_date( $event_id, false, 'Y' ) . '</span>
					<span class="tecset-ev-blank"> - </span>
					<span class="tecset-ev-day">' . tribe_get_end_date( $event_id, false, 'd' ) . '</span>
					<span class="tecset-ev-mo">' . tribe_get_end_date( $event_id, false, 'F' ) . '</span>
					<span class="tecset-ev-yr">' . tribe_get_end_date( $event_id, false, 'Y' ) . '</span>
					</div>';
			}
				$tecset_event_schedule .= '<meta itemprop="endDate" content="' . $tecset_end_date_attr . '">';
		} elseif ( $tecset_date_format == 'dMY' ) {
				$tecset_event_schedule = '<div class="tecset-date" itemprop="startDate" content="' . $tecset_start_date_attr . '">';
			if ( ! tribe_event_is_multiday( $event_id ) ) {
				$tecset_event_schedule .= '<span class="tecset-ev-day">' . tribe_get_start_date( $event_id, false, 'd' ) . '</span>
					<span class="tecset-ev-mo">' . tribe_get_start_date( $event_id, false, 'M' ) . '</span>
					<span class="tecset-ev-yr">' . tribe_get_start_date( $event_id, false, 'Y' ) . '</span>
					</div>';
			} else {
				$tecset_event_schedule .= '<span class="tecset-ev-day">' . tribe_get_start_date( $event_id, false, 'd' ) . '</span>
					<span class="tecset-ev-mo">' . tribe_get_start_date( $event_id, false, 'M' ) . '</span>
					<span class="tecset-ev-yr">' . tribe_get_start_date( $event_id, false, 'Y' ) . '</span>
					<span class="tecset-ev-blank"> - </span>
					<span class="tecset-ev-day">' . tribe_get_end_date( $event_id, false, 'd' ) . '</span>
					<span class="tecset-ev-mo">' . tribe_get_end_date( $event_id, false, 'M' ) . '</span>
					<span class="tecset-ev-yr">' . tribe_get_end_date( $event_id, false, 'Y' ) . '</span>
					</div>';
			}
				$tecset_event_schedule .= '<meta itemprop="endDate" content="' . $tecset_end_date_attr . '">';
		} else {
				$tecset_event_schedule = '<div class="tecset-date">' . tribe_events_event_schedule_details( $event_id ) . '</div>';
		}
		/*Date Format END*/
		return wp_kses_post( $tecset_event_schedule );
	}

	/**
	 * Format event time for display.
	 *
	 * @param int  $post_id Event post ID.
	 * @param bool $display Echo when true, return when false.
	 * @return string|void
	 */
	public function tribe_event_time( $post_id, $display = true ) {
		$event = $post_id;

		if ( tribe_event_is_all_day( $event ) ) {
			if ( $display ) {
				esc_html_e( 'All day', 'event-page-templates-addon-for-the-events-calendar' );
				return;
			}

			return esc_html__( 'All day', 'event-page-templates-addon-for-the-events-calendar' );
		}

		if ( tribe_event_is_multiday( $event ) ) {
			$tecset_start_date = tribe_get_start_date( $event, false, false );
			$tecset_end_date   = tribe_get_end_date( $event, false, false );

			if ( $display ) {
				printf( '%1$s - %2$s', esc_html( $tecset_start_date ), esc_html( $tecset_end_date ) );
				return;
			}

			return sprintf( '%1$s - %2$s', esc_html( $tecset_start_date ), esc_html( $tecset_end_date ) );
		}

		$time_format       = get_option( 'time_format' );
		$tecset_start_date = tribe_get_start_date( $event, false, $time_format );
		$tecset_end_date   = tribe_get_end_date( $event, false, $time_format );

		if ( $tecset_start_date !== $tecset_end_date ) {
			if ( $display ) {
				printf( '%1$s - %2$s', esc_html( $tecset_start_date ), esc_html( $tecset_end_date ) );
				return;
			}

			return sprintf( '%1$s - %2$s', esc_html( $tecset_start_date ), esc_html( $tecset_end_date ) );
		}

		if ( $display ) {
			printf( '%s', esc_html( $tecset_start_date ) );
			return;
		}

		return sprintf( '%s', esc_html( $tecset_start_date ) );
	}

	/**
	 * @return array<string, string>
	 */
	private function get_template_colors() {
		$get_temp_id                    = get_option( 'tec_tribe_single_event_page' );
		$tecset_get_primary_color       = get_post_meta( $get_temp_id, 'epta-primary-color', true );
		$tecset_set_primary_color       = ! empty( $tecset_get_primary_color ) ? sanitize_hex_color( $tecset_get_primary_color ) : '#222222';
		$tecset_get_secondary_color     = get_post_meta( $get_temp_id, 'epta-secondary-alternate-color', true );
		$tecset_set_secondary_color     = ! empty( $tecset_get_secondary_color ) ? sanitize_hex_color( $tecset_get_secondary_color ) : '#cccccc';
		$tecset_primary_alternate_color = get_post_meta( $get_temp_id, 'epta-alternate-primary-color', true );
		$tecset_set_alternate_color     = ! empty( $tecset_primary_alternate_color ) ? sanitize_hex_color( $tecset_primary_alternate_color ) : '#ffffff';

		return array(
			'primary'   => $tecset_set_primary_color,
			'secondary' => $tecset_set_secondary_color,
			'alternate' => $tecset_set_alternate_color,
		);
	}

	/**
	 * @param int                  $event_id Event post ID.
	 * @param array<int, string[]> $tokens   Date token definitions.
	 * @param bool                 $is_end   Whether to render the end date.
	 * @return string
	 */
	private function render_schedule_tokens( $event_id, $tokens, $is_end ) {
		$html = '';

		foreach ( $tokens as $token ) {
			$format = $token[0];
			$class  = $token[1];
			$suffix = isset( $token[2] ) ? $token[2] : '';
			$value  = $is_end
				? tribe_get_end_date( $event_id, false, $format )
				: tribe_get_start_date( $event_id, false, $format );

			$html .= '<span class="' . esc_attr( $class ) . '">' . esc_html( $value . $suffix ) . '</span>';
		}

		return $html;
	}

	/**
	 * @param int    $event_id Event post ID.
	 * @param string $mode     Time rendering mode.
	 * @param string $ev_time  Preformatted event time string.
	 * @param bool   $is_multiday Whether the event spans multiple days.
	 * @param bool   $is_end   Whether to render the end-date time.
	 * @return string
	 */
	private function render_schedule_time( $event_id, $mode, $ev_time, $is_multiday, $is_end ) {
		if ( 'icon' !== $mode ) {
			return '';
		}

		if ( $is_multiday ) {
			$time = $is_end
				? tribe_get_end_date( $event_id, false, 'g:i A' )
				: tribe_get_start_date( $event_id, false, 'g:i A' );

			return '<span class="tecset-ev-time">(' . esc_html( $time ) . ')</span>';
		}

		return '<span class="tecset-ev-time"><span class="ect-icon"><i class="ect-icon-clock" aria-hidden="true"></i></span> ' . esc_html( $ev_time ) . '</span>';
	}
}
