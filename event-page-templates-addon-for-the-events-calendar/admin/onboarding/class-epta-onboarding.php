<?php
/**
 * Onboarding wizard for Event Single Page Builder.
 *
 * Ports the dash-latest single-page-builder wizard into WP admin and
 * applies selections to the existing `epta` template settings.
 *
 * @package EventPageTemplatesAddon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class EPTA_Onboarding
 */
class EPTA_Onboarding {

	const PAGE_SLUG          = 'epta-onboarding';
	const OPTION_COMPLETED   = 'epta_onboarding_completed';
	const OPTION_REDIRECTED  = 'epta_onboarding_redirected';
	const TRANSIENT_REDIRECT = 'epta_activation_redirect';
	const NONCE_ACTION       = 'epta_onboarding_nonce';
	const AJAX_APPLY         = 'epta_onboarding_apply';
	const AJAX_SEARCH        = 'epta_onboarding_search';
	const AJAX_COMPLETE      = 'epta_onboarding_complete';
	const AJAX_ACTIVATE_PRO  = 'epta_onboarding_activate_pro';
	const AJAX_ACTIVATE_DIVI = 'epta_onboarding_activate_divi';
	const PRO_HANDOFF_OPTION = 'espbp_free_handoff';

	/**
	 * Absolute path to the onboarding folder.
	 *
	 * @var string
	 */
	private $dir;

	/**
	 * URL to the onboarding folder.
	 *
	 * @var string
	 */
	private $url;

	/**
	 * Register hooks (called from EPTA_ECA_Integration::boot_admin()).
	 */
	public static function init() {
		new self();
	}

	/**
	 * Boot hooks.
	 */
	public function __construct() {
		$this->dir = EPTA_PLUGIN_DIR . 'admin/onboarding/';
		$this->url = EPTA_PLUGIN_URL . 'admin/onboarding/';

		if ( ! class_exists( 'EPTA_Onboarding_Optin', false ) ) {
			$optin = EPTA_PLUGIN_DIR . 'admin/feedback/class-epta-onboarding-optin.php';
			if ( file_exists( $optin ) ) {
				require_once $optin;
			}
		}

		add_action( 'admin_menu', array( $this, 'register_page' ), 60 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'maybe_redirect' ) );
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );

		add_action( 'wp_ajax_' . self::AJAX_APPLY, array( $this, 'ajax_apply_settings' ) );
		add_action( 'wp_ajax_' . self::AJAX_SEARCH, array( $this, 'ajax_search' ) );
		add_action( 'wp_ajax_' . self::AJAX_COMPLETE, array( $this, 'ajax_mark_complete' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTIVATE_PRO, array( $this, 'ajax_activate_pro' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTIVATE_DIVI, array( $this, 'ajax_activate_divi' ) );
	}

	/**
	 * Schedule a one-shot post-activation redirect.
	 * Call from the activation hook BEFORE writing install options.
	 *
	 * Fresh install  → Get Started (onboarding).
	 * Reactivation   → shared Events Addons dashboard.
	 */
	public static function maybe_schedule_redirect() {
		$is_fresh_install = ( false === get_option( 'epta-install-date', false ) )
			&& ( false === get_option( 'epta_initial_save_version', false ) );

		$target = $is_fresh_install ? 'onboarding' : 'dashboard';
		// Short TTL: WP reloads admin immediately after Activate, then maybe_redirect()
		// consumes this. If it expires, the user stays on the dashboard (onboarding
		// remains available from the menu).
		set_transient( self::TRANSIENT_REDIRECT, $target, MINUTE_IN_SECONDS );
	}

	/**
	 * @deprecated Use maybe_schedule_redirect() — kept for call-site compatibility.
	 *
	 * @param bool|null $is_first_install Unused.
	 */
	public static function flag_activation_redirect( $is_first_install = null ) {
		unset( $is_first_install );
		self::maybe_schedule_redirect();
	}

	/**
	 * Whether this site should still get an automatic Get Started redirect.
	 *
	 * @return bool
	 */
	public static function should_redirect_new_user() {
		return ! get_option( self::OPTION_COMPLETED ) && ! get_option( self::OPTION_REDIRECTED );
	}

	/**
	 * Dashboard admin URL (Events Addons hub).
	 *
	 * @return string
	 */
	public static function get_dashboard_url() {
		return admin_url( 'admin.php?page=cool-plugins-events-addon' );
	}

	/**
	 * Register Get Started as a hidden admin page (not shown in sidebar).
	 * Still reachable via activation redirect and Plugins → Get Started link.
	 */
	public function register_page() {
		$hook = add_submenu_page(
			null,
			__( 'Get Started', 'event-page-templates-addon-for-the-events-calendar' ),
			__( 'Get Started', 'event-page-templates-addon-for-the-events-calendar' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
		if ( $hook ) {
			add_action( 'load-' . $hook, array( $this, 'set_admin_title' ) );
		}
	}

	/**
	 * Give the hidden wizard page a real title before admin-header.php runs
	 * (avoids PHP 8.1+ strip_tags(null) deprecation).
	 */
	public function set_admin_title() {
		$GLOBALS['title'] = __( 'Get Started', 'event-page-templates-addon-for-the-events-calendar' );
	}

	/**
	 * Whether the current screen is the onboarding page.
	 *
	 * @return bool
	 */
	private function is_onboarding_screen() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		return self::PAGE_SLUG === $page;
	}

	/**
	 * Consume the post-activation redirect transient (one shot).
	 */
	public function maybe_redirect() {
		$target = get_transient( self::TRANSIENT_REDIRECT );
		if ( ! $target ) {
			return;
		}
		delete_transient( self::TRANSIENT_REDIRECT );

		// Legacy transient value from earlier builds.
		if ( '1' === $target || 1 === $target ) {
			$target = 'onboarding';
		}

		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( wp_doing_ajax() || wp_doing_cron() || is_network_admin() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- bulk-activation marker only.
		if ( isset( $_GET['activate-multi'] ) ) {
			return;
		}

		// Fresh installs that somehow already finished onboarding still land on the dashboard.
		if ( 'onboarding' === $target && get_option( self::OPTION_COMPLETED ) ) {
			$target = 'dashboard';
		}

		$page = ( 'onboarding' === $target )
			? self::PAGE_SLUG
			: ( class_exists( 'ECA_Dashboard_Page' ) ? ECA_Dashboard_Page::PAGE_SLUG : 'cool-plugins-events-addon' );

		if ( self::PAGE_SLUG === $page && $this->is_onboarding_screen() ) {
			update_option( self::OPTION_REDIRECTED, '1', false );
			return;
		}

		update_option( self::OPTION_REDIRECTED, '1', false );
		wp_safe_redirect( admin_url( 'admin.php?page=' . $page ) );
		exit;
	}

	/**
	 * Add body class for chrome-hiding CSS.
	 *
	 * @param string $classes Body classes.
	 * @return string
	 */
	public function admin_body_class( $classes ) {
		if ( $this->is_onboarding_screen() ) {
			$classes .= ' epta-onboarding-page';
		}
		return $classes;
	}

	/**
	 * Enqueue wizard assets only on the onboarding screen.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( ! $this->is_onboarding_screen() ) {
			return;
		}

		$ver = EPTA_PLUGIN_CURRENT_VERSION;
		$css  = $this->url . 'assets/css/';
		$js   = $this->url . 'assets/js/';

		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'epta-onboarding-base', $css . 'epta-base.css', array(), $ver );
		wp_enqueue_style( 'epta-onboarding-wizard', $css . 'epta-wizard.css', array( 'epta-onboarding-base' ), $ver );
		wp_enqueue_style( 'epta-onboarding-wp', $css . 'epta-onboarding-wp.css', array( 'epta-onboarding-wizard' ), $ver );

		wp_enqueue_script( 'epta-onboarding-wizard', $js . 'epta-wizard.js', array(), $ver, true );
		wp_enqueue_script( 'epta-onboarding', $js . 'epta-onboarding.js', array( 'epta-onboarding-wizard' ), $ver, true );
		wp_enqueue_script( 'epta-onboarding-wp', $js . 'epta-onboarding-wp.js', array( 'epta-onboarding' ), $ver, true );

		$template_id     = $this->get_template_post_id();
		$pro_status      = 'absent';
		$pro_init        = 'event-single-page-builder-pro/event-single-page-builder-pro.php';
		$divi_pro_status = 'absent';
		$divi_pro_init   = 'cp-events-calendar-modules-for-divi-pro/cp-events-calendar-modules-for-divi-pro.php';
		$divi_free_status = 'absent';
		$divi_free_init  = 'events-calendar-modules-for-divi/events-calendar-modules-for-divi.php';

		if ( class_exists( 'ECA_Addon_Map' ) ) {
			$pro_status = ECA_Addon_Map::tier_status( 'spb', 'pro' );
			$mapped     = ECA_Addon_Map::expected_tier_init( 'spb', 'pro' );
			if ( $mapped ) {
				$pro_init = $mapped;
			}

			$divi_pro_status  = ECA_Addon_Map::tier_status( 'divi', 'pro' );
			$divi_pro_mapped  = ECA_Addon_Map::expected_tier_init( 'divi', 'pro' );
			if ( $divi_pro_mapped ) {
				$divi_pro_init = $divi_pro_mapped;
			}

			$divi_free_status = ECA_Addon_Map::tier_status( 'divi', 'free' );
			$divi_free_mapped = ECA_Addon_Map::expected_tier_init( 'divi', 'free' );
			if ( $divi_free_mapped ) {
				$divi_free_init = $divi_free_mapped;
			}
		}

		wp_localize_script(
			'epta-onboarding',
			'EPTA_ONBOARDING',
			array(
				'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
				'nonce'              => wp_create_nonce( self::NONCE_ACTION ),
				'applyAction'        => self::AJAX_APPLY,
				'searchAction'       => self::AJAX_SEARCH,
				'completeAction'     => self::AJAX_COMPLETE,
				'activateProAction'  => self::AJAX_ACTIVATE_PRO,
				'activateDiviAction' => self::AJAX_ACTIVATE_DIVI,
				'savePrefsAction'    => 'epta_onboarding_save_preferences',
				'exitUrl'            => $this->get_exit_url(),
				'settingsUrl'        => $this->get_settings_url( $template_id ),
				'createEventUrl'     => admin_url( 'post-new.php?post_type=tribe_events' ),
				'previewUrl'         => $this->get_preview_event_url(),
				'hasEvents'          => $this->has_published_events(),
				'proStatus'          => $pro_status,
				'proInit'            => $pro_init,
				'proBuyUrl'          => 'https://eventscalendaraddons.com/plugin/event-single-page-builder-pro/',
				'diviProStatus'      => $divi_pro_status,
				'diviProInit'        => $divi_pro_init,
				'diviFreeStatus'     => $divi_free_status,
				'diviFreeInit'       => $divi_free_init,
				'diviBuyUrl'         => 'https://eventscalendaraddons.com/plugin/the-events-calendar-modules-for-divi/',
				'telemetry'          => class_exists( 'EPTA_Onboarding_Optin' )
					? \EPTA_Onboarding_Optin::get_telemetry_localize()
					: array(
						'show'    => true,
						'checked' => true,
						'choice'  => null,
					),
				'i18n'               => array(
					'applying'            => __( 'Applying…', 'event-page-templates-addon-for-the-events-calendar' ),
					'finishing'           => __( 'Finishing…', 'event-page-templates-addon-for-the-events-calendar' ),
					'activatingPro'       => __( 'Activating Pro…', 'event-page-templates-addon-for-the-events-calendar' ),
					'activatePro'         => __( 'Activate Pro & Continue', 'event-page-templates-addon-for-the-events-calendar' ),
					'activateProError'    => __( 'Could not activate Pro. Please activate it from Plugins and try again.', 'event-page-templates-addon-for-the-events-calendar' ),
					'activatingDivi'      => __( 'Activating Divi Modules…', 'event-page-templates-addon-for-the-events-calendar' ),
					'activateDiviPro'     => __( 'Activate Pro & Continue', 'event-page-templates-addon-for-the-events-calendar' ),
					'activateDiviFree'    => __( 'Activate & Continue', 'event-page-templates-addon-for-the-events-calendar' ),
					'activateDiviError'   => __( 'Could not activate Divi Modules. Please activate it from Plugins and try again.', 'event-page-templates-addon-for-the-events-calendar' ),
					'applyError'          => __( 'Could not apply template settings. Please try again.', 'event-page-templates-addon-for-the-events-calendar' ),
					'pickCategories'      => __( 'Please select at least one category.', 'event-page-templates-addon-for-the-events-calendar' ),
					'pickEvents'          => __( 'Please select at least one event.', 'event-page-templates-addon-for-the-events-calendar' ),
				),
			)
		);
	}

	/**
	 * Render the wizard page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'event-page-templates-addon-for-the-events-calendar' ) );
		}

		$epta_icons_url         = $this->url . 'assets/images/';
		$epta_images_url        = $this->url . 'assets/images/';
		$epta_exit_url          = $this->get_exit_url();
		$epta_settings_url      = $this->get_settings_url( $this->get_template_post_id() );
		$epta_create_event_url  = admin_url( 'post-new.php?post_type=tribe_events' );
		$epta_preview_event_url = $this->get_preview_event_url();
		$epta_preview_state     = $this->detect_preview_state();
		$epta_categories        = $this->get_term_options( 'tribe_events_cat', 50 );
		$epta_events            = $this->get_event_options( 50 );
		$epta_show_telemetry    = class_exists( 'EPTA_Onboarding_Optin' ) ? \EPTA_Onboarding_Optin::should_show_telemetry() : true;
		$epta_show_bundle       = ! $this->all_pro_addons_installed();

		include $this->dir . 'views/wizard.php';
	}

	/**
	 * Whether every mapped Pro addon is already installed (active or inactive).
	 * Used to hide the final-step Bundle CTA when nothing left to buy.
	 *
	 * @return bool
	 */
	private function all_pro_addons_installed() {
		if ( ! class_exists( 'ECA_Addon_Map' ) ) {
			return false;
		}

		foreach ( ECA_Addon_Map::definitions() as $env_key => $def ) {
			if ( empty( $def['pro'] ) ) {
				continue;
			}
			if ( 'absent' === ECA_Addon_Map::tier_status( $env_key, 'pro' ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Map detected builders + event count to wizard preview state.
	 *
	 * @return string
	 */
	private function detect_preview_state() {
		$elementor = class_exists( '\Elementor\Plugin' );
		$divi      = defined( 'ET_BUILDER_VERSION' ) || function_exists( 'et_setup_theme' );
		$no_events = ! $this->has_published_events();

		if ( $no_events ) {
			return 'no-events';
		}
		if ( $elementor && $divi ) {
			return 'both';
		}
		if ( $elementor ) {
			return 'elementor';
		}
		if ( $divi ) {
			return 'divi';
		}
		return 'default';
	}

	/**
	 * @return bool
	 */
	private function has_published_events() {
		$counts = wp_count_posts( 'tribe_events' );
		return is_object( $counts ) && ! empty( $counts->publish ) && (int) $counts->publish > 0;
	}

	/**
	 * Default / active template post ID.
	 *
	 * @return int
	 */
	private function get_template_post_id() {
		$active = (int) get_option( 'tec_tribe_single_event_page' );
		if ( $active && 'epta' === get_post_type( $active ) ) {
			return $active;
		}

		$fallback = (int) get_option( 'tecset-single-page-id' );
		if ( $fallback && 'epta' === get_post_type( $fallback ) ) {
			return $fallback;
		}

		return 0;
	}

	/**
	 * @param int $template_id Template post ID.
	 * @return string
	 */
	private function get_settings_url( $template_id ) {
		if ( $template_id > 0 ) {
			return admin_url( 'post.php?post=' . $template_id . '&action=edit' );
		}
		return admin_url( 'edit.php?post_type=epta' );
	}

	/**
	 * Exit / Finish destination — Events Addons dashboard when available.
	 *
	 * @return string
	 */
	private function get_exit_url() {
		return admin_url( 'admin.php?page=cool-plugins-events-addon' );
	}

	/**
	 * Permalink of a matching published event for Preview Event, or empty.
	 *
	 * @param string   $target all|categories|events|tags.
	 * @param string[] $slugs  Category/event/tag slugs for the selected scope.
	 * @return string
	 */
	private function get_preview_event_url( $target = 'all', $slugs = array() ) {
		$target = sanitize_key( $target );
		$slugs  = array_values(
			array_filter(
				array_map(
					static function ( $slug ) {
						return sanitize_title( (string) $slug );
					},
					(array) $slugs
				)
			)
		);

		$args = array(
			'post_type'              => 'tribe_events',
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		if ( 'events' === $target && ! empty( $slugs ) ) {
			$args['post_name__in'] = $slugs;
		} elseif ( 'categories' === $target && ! empty( $slugs ) ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'tribe_events_cat',
					'field'    => 'slug',
					'terms'    => $slugs,
				),
			);
		} elseif ( 'tags' === $target && ! empty( $slugs ) ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'post_tag',
					'field'    => 'slug',
					'terms'    => $slugs,
				),
			);
		}

		$events = get_posts( $args );
		if ( empty( $events ) && 'all' !== $target ) {
			// Selected scope has no published match — fall back to first event.
			return $this->get_preview_event_url( 'all' );
		}

		if ( empty( $events ) ) {
			return '';
		}

		$url = get_permalink( $events[0] );
		return $url ? $url : '';
	}

	/**
	 * Term options keyed by slug.
	 *
	 * For post_tag, only tags assigned to at least one published/private
	 * tribe_events post are returned (zero-event tags stay hidden).
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param int    $limit    Max terms.
	 * @return array
	 */
	private function get_term_options( $taxonomy, $limit = 50 ) {
		if ( 'post_tag' === $taxonomy ) {
			return $this->get_event_assigned_tag_options( $limit );
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
				'number'     => (int) $limit,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$out = array();
		foreach ( $terms as $term ) {
			$out[ $term->slug ] = $term->name;
		}
		return $out;
	}

	/**
	 * Tags assigned to at least one event (via get_terms object_ids).
	 *
	 * @param int    $limit  Max tags.
	 * @param string $search Optional name search.
	 * @return array<string, string> slug => name
	 */
	private function get_event_assigned_tag_options( $limit = 50, $search = '' ) {
		$event_ids = get_posts(
			array(
				'post_type'              => 'tribe_events',
				'post_status'            => array( 'publish', 'private' ),
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( empty( $event_ids ) ) {
			return array();
		}

		$args = array(
			'taxonomy'   => 'post_tag',
			'object_ids' => $event_ids,
			'hide_empty' => false,
			'number'     => max( 1, (int) $limit ),
			'orderby'    => 'name',
			'order'      => 'ASC',
		);

		$search = is_string( $search ) ? trim( $search ) : '';
		if ( '' !== $search ) {
			$args['name__like'] = $search;
		}

		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$out = array();
		foreach ( $terms as $term ) {
			$out[ $term->slug ] = $term->name;
		}

		return $out;
	}

	/**
	 * Event options keyed by post_name (slug) — matches existing CMB2 storage.
	 * Uses get_posts so past events are included (tribe_get_events defaults to upcoming only).
	 *
	 * @param int $limit Max events.
	 * @return array
	 */
	private function get_event_options( $limit = 100 ) {
		$events = get_posts(
			array(
				'post_type'              => 'tribe_events',
				'post_status'            => array( 'publish', 'private' ),
				'posts_per_page'         => (int) $limit,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$out = array();
		foreach ( (array) $events as $event ) {
			if ( empty( $event->post_name ) ) {
				continue;
			}
			$out[ $event->post_name ] = $event->post_title;
		}
		return $out;
	}

	/**
	 * AJAX: apply wizard selections to the template post meta.
	 */
	public function ajax_apply_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$raw = isset( $_POST['payload'] ) ? sanitize_text_field( wp_unslash( $_POST['payload'] ) ) : '';
		if ( is_string( $raw ) ) {
			$payload = json_decode( $raw, true );
		} else {
			$payload = array();
		}

		if ( ! is_array( $payload ) ) {
			wp_send_json_error( array( 'message' => 'Invalid payload' ), 400 );
		}

		$selections = isset( $payload['selections'] ) && is_array( $payload['selections'] )
			? $payload['selections']
			: array();

		$telemetry = ! empty( $payload['telemetryAccepted'] );

		$post_id = $this->get_template_post_id();
		if ( ! $post_id ) {
			$post_id = wp_insert_post(
				array(
					'post_title'  => 'Single Event Template',
					'post_type'   => 'epta',
					'post_status' => 'publish',
					'post_author' => get_current_user_id(),
				)
			);
			if ( is_wp_error( $post_id ) || ! $post_id ) {
				wp_send_json_error( array( 'message' => 'Could not create template' ), 500 );
			}
			update_option( 'tecset-single-page-id', (int) $post_id );
		}

		// Template — free wizard only applies template-1.
		$template_map = array(
			'classic' => 'template-1',
		);
		$tpl_key = isset( $selections['template'] ) ? sanitize_key( $selections['template'] ) : 'classic';
		$tpl_meta = isset( $template_map[ $tpl_key ] ) ? $template_map[ $tpl_key ] : 'template-1';
		update_post_meta( $post_id, 'epta-template', $tpl_meta );

		// Apply-on scope — prefer DOM-synced target from payload.
		$target     = isset( $selections['target'] ) ? sanitize_key( $selections['target'] ) : 'all';
		$apply_map  = array(
			'all'        => 'all-event',
			'categories' => 'specific-cate',
			'events'     => 'specific-event',
			'tags'       => 'specific-tag',
		);
		if ( ! isset( $apply_map[ $target ] ) ) {
			$target = 'all';
		}
		$apply_on = $apply_map[ $target ];

		$category_slugs = $this->parse_csv_slugs( isset( $selections['target-category'] ) ? $selections['target-category'] : '' );
		$event_slugs    = $this->parse_csv_slugs( isset( $selections['target-event'] ) ? $selections['target-event'] : '' );
		$tag_slugs      = $this->parse_csv_slugs( isset( $selections['target-tag'] ) ? $selections['target-tag'] : '' );

		if ( 'categories' === $target && empty( $category_slugs ) ) {
			wp_send_json_error( array( 'message' => __( 'Please select at least one category.', 'event-page-templates-addon-for-the-events-calendar' ) ), 400 );
		}
		if ( 'events' === $target && empty( $event_slugs ) ) {
			wp_send_json_error( array( 'message' => __( 'Please select at least one event.', 'event-page-templates-addon-for-the-events-calendar' ) ), 400 );
		}
		if ( 'tags' === $target && empty( $tag_slugs ) ) {
			wp_send_json_error( array( 'message' => __( 'Please select at least one tag.', 'event-page-templates-addon-for-the-events-calendar' ) ), 400 );
		}

		update_post_meta( $post_id, 'epta-apply-on', $apply_on );

		// Store slug => slug (CMB2 pw_multiselect shape). Clear unused scopes.
		$cat_meta   = ( 'categories' === $target && ! empty( $category_slugs ) ) ? array_combine( $category_slugs, $category_slugs ) : array();
		$event_meta = ( 'events' === $target && ! empty( $event_slugs ) ) ? array_combine( $event_slugs, $event_slugs ) : array();
		$tag_meta   = ( 'tags' === $target && ! empty( $tag_slugs ) ) ? array_combine( $tag_slugs, $tag_slugs ) : array();

		update_post_meta( $post_id, 'epta-categoery', is_array( $cat_meta ) ? $cat_meta : array() );
		update_post_meta( $post_id, 'epta-specific-event', is_array( $event_meta ) ? $event_meta : array() );
		update_post_meta( $post_id, 'epta-tag', is_array( $tag_meta ) ? $tag_meta : array() );

		// Date format.
		$date_format = isset( $selections['date-format'] ) ? sanitize_text_field( $selections['date-format'] ) : 'default';
		$allowed_dates = array( 'default', 'DM', 'MD', 'FD', 'DF', 'FD,Y', 'MD,Y', 'MD,YT', 'full', 'dFY', 'dMY' );
		if ( ! in_array( $date_format, $allowed_dates, true ) ) {
			$date_format = 'default';
		}
		update_post_meta( $post_id, 'tecset-date-format', $date_format );

		// Colors.
		$colors = isset( $selections['colors'] ) && is_array( $selections['colors'] ) ? $selections['colors'] : array();
		$primary   = isset( $colors['primary'] ) ? sanitize_hex_color( $colors['primary'] ) : '';
		$alternate = isset( $colors['alternate'] ) ? sanitize_hex_color( $colors['alternate'] ) : '';
		$secondary = isset( $colors['secondary'] ) ? sanitize_hex_color( $colors['secondary'] ) : '';

		if ( $primary ) {
			update_post_meta( $post_id, 'epta-primary-color', $primary );
		}
		if ( $alternate ) {
			update_post_meta( $post_id, 'epta-alternate-primary-color', $alternate );
		}
		if ( $secondary ) {
			update_post_meta( $post_id, 'epta-secondary-alternate-color', $secondary );
		}

		update_option( 'tec_tribe_single_event_page', $post_id );

		if ( class_exists( 'EPTA_Onboarding_Optin' ) ) {
			\EPTA_Onboarding_Optin::persist_from_wizard( $telemetry, $selections, 'apply' );
		} else {
			update_option( 'cpfm_opt_in_choice_cool_events', $telemetry ? 'yes' : 'no' );
			if ( $telemetry ) {
				do_action( 'cpfm_after_opt_in_epta', 'cool_events' );
			}
		}

		if ( $telemetry ) {
			self::schedule_crons_for_other_plugins();
		}

		update_option( self::OPTION_COMPLETED, '1', false );

		$preview_slugs = array();
		if ( 'categories' === $target ) {
			$preview_slugs = $category_slugs;
		} elseif ( 'events' === $target ) {
			$preview_slugs = $event_slugs;
		} elseif ( 'tags' === $target ) {
			$preview_slugs = $tag_slugs;
		}

		wp_send_json_success(
			array(
				'templateId'  => $post_id,
				'settingsUrl' => $this->get_settings_url( $post_id ),
				'previewUrl'  => $this->get_preview_event_url( $target, $preview_slugs ),
				'exitUrl'     => $this->get_exit_url(),
				'telemetry'   => class_exists( 'EPTA_Onboarding_Optin' )
					? \EPTA_Onboarding_Optin::get_telemetry_localize()
					: array(
						'show'    => true,
						'checked' => true,
						'choice'  => null,
					),
			)
		);
	}

	public static function schedule_crons_for_other_plugins() {
		$plugin_files = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$plugin_files = array_merge( $plugin_files, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
		}

		$self_slug = 'event-page-templates-addon-for-the-events-calendar';
		$seen      = array();

		foreach ( $plugin_files as $plugin_file ) {
			$slug = dirname( (string) $plugin_file );
			if ( '.' === $slug || '' === $slug || $self_slug === $slug || isset( $seen[ $slug ] ) ) {
				continue;
			}
			$seen[ $slug ] = true;
			do_action( 'plugin_opt_in_' . $slug );
		}
	}

	/**
	 * AJAX: activate Event Single Page Builder Pro (already installed) and
	 * hand the selected Pro template off to Pro onboarding.
	 *
	 * Free and Pro cannot stay active together — Pro's activation hook
	 * deactivates Free. We force Pro's post-activation redirect to onboarding
	 * (not dashboard) so reactivation-with-Pro-on-disk still opens Get Started.
	 */
	public function ajax_activate_pro() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$template = isset( $_POST['template'] ) ? sanitize_key( wp_unslash( $_POST['template'] ) ) : '';
		$allowed  = array( 'hero', 'split', 'elementor' );
		if ( ! in_array( $template, $allowed, true ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please select a Pro template first.', 'event-page-templates-addon-for-the-events-calendar' ),
				),
				400
			);
		}

		$colors = array();
		if ( isset( $_POST['colors'] ) ) {
			$raw_colors = wp_unslash( $_POST['colors'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( is_string( $raw_colors ) ) {
				$decoded = json_decode( $raw_colors, true );
				$raw_colors = is_array( $decoded ) ? $decoded : array();
			}
			if ( is_array( $raw_colors ) ) {
				foreach ( array( 'primary', 'alternate', 'secondary' ) as $key ) {
					if ( empty( $raw_colors[ $key ] ) ) {
						continue;
					}
					$hex = sanitize_hex_color( $raw_colors[ $key ] );
					if ( $hex ) {
						$colors[ $key ] = $hex;
					}
				}
			}
		}

		if ( ! class_exists( 'ECA_Addon_Map' ) ) {
			require_once EPTA_PLUGIN_DIR . 'admin/eca-dashboard/includes/class-eca-addon-map.php';
		}

		$pro_status = ECA_Addon_Map::tier_status( 'spb', 'pro' );
		if ( 'absent' === $pro_status ) {
			wp_send_json_error(
				array(
					'message' => __( 'Event Single Page Builder Pro is not installed on this site.', 'event-page-templates-addon-for-the-events-calendar' ),
					'buyUrl'  => 'https://eventscalendaraddons.com/plugin/event-single-page-builder-pro/',
				),
				404
			);
		}

		if ( 'active' === $pro_status ) {
			// Pro already active — Free should not still be running, but hand off anyway.
			$this->store_pro_handoff( $template, $colors );
			$this->force_pro_onboarding_redirect();
			wp_send_json_success(
				array(
					'redirectUrl' => admin_url( 'admin.php?page=espbp-onboarding' ),
				)
			);
		}

		$init = ECA_Addon_Map::expected_tier_init( 'spb', 'pro' );
		if ( ! $init ) {
			wp_send_json_error(
				array(
					'message' => __( 'Could not locate the Pro plugin file.', 'event-page-templates-addon-for-the-events-calendar' ),
				),
				500
			);
		}

		// Persist handoff before activate — Free is deactivated during Pro activation.
		$this->store_pro_handoff( $template, $colors );

		if ( ! function_exists( 'activate_plugin' ) || ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$result = activate_plugin( $init );
		if ( is_wp_error( $result ) && ! is_plugin_active( $init ) ) {
			delete_option( self::PRO_HANDOFF_OPTION );
			wp_send_json_error(
				array(
					'message' => $result->get_error_message(),
				)
			);
		}

		// Pro's activation hook may have scheduled dashboard for non-fresh installs.
		$this->force_pro_onboarding_redirect();
		update_option( self::OPTION_COMPLETED, '1', false );

		wp_send_json_success(
			array(
				'redirectUrl' => admin_url( 'admin.php?page=espbp-onboarding' ),
			)
		);
	}

	/**
	 * Store Free → Pro wizard handoff payload.
	 *
	 * @param string               $template Template key (hero|split|elementor).
	 * @param array<string,string> $colors   Optional color map.
	 */
	private function store_pro_handoff( $template, $colors ) {
		update_option(
			self::PRO_HANDOFF_OPTION,
			array(
				'template' => $template,
				'colors'   => $colors,
				'target'   => 'all',
				'source'   => 'epta_onboarding',
				'created'  => time(),
			),
			false
		);
	}

	/**
	 * Force Pro Get Started after Free→Pro handoff (even on Pro reactivation).
	 */
	private function force_pro_onboarding_redirect() {
		set_transient( 'espbp_activation_redirect', 'onboarding', 5 * MINUTE_IN_SECONDS );
		set_transient( 'espbp_onboarding_fresh', '1', 5 * MINUTE_IN_SECONDS );
		// Allow Get Started even if Pro onboarding was completed in a prior session.
		delete_option( 'espbp_onboarding_completed' );
	}

	/**
	 * AJAX: activate Events Calendar Modules for Divi (Pro preferred, else Free)
	 * and open its Get Started wizard.
	 */
	public function ajax_activate_divi() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! class_exists( 'ECA_Addon_Map' ) ) {
			require_once EPTA_PLUGIN_DIR . 'admin/eca-dashboard/includes/class-eca-addon-map.php';
		}

		$tier = 'pro';
		$status = ECA_Addon_Map::tier_status( 'divi', 'pro' );
		if ( 'absent' === $status ) {
			$tier   = 'free';
			$status = ECA_Addon_Map::tier_status( 'divi', 'free' );
		}

		if ( 'absent' === $status ) {
			wp_send_json_error(
				array(
					'message' => __( 'Events Calendar Modules for Divi is not installed on this site.', 'event-page-templates-addon-for-the-events-calendar' ),
					'buyUrl'  => 'https://eventscalendaraddons.com/plugin/the-events-calendar-modules-for-divi/',
				),
				404
			);
		}

		$init = ECA_Addon_Map::expected_tier_init( 'divi', $tier );
		if ( ! $init ) {
			wp_send_json_error(
				array(
					'message' => __( 'Could not locate the Divi Modules plugin file.', 'event-page-templates-addon-for-the-events-calendar' ),
				),
				500
			);
		}

		if ( 'active' !== $status ) {
			if ( ! function_exists( 'activate_plugin' ) || ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$result = activate_plugin( $init );
			if ( is_wp_error( $result ) && ! is_plugin_active( $init ) ) {
				wp_send_json_error(
					array(
						'message' => $result->get_error_message(),
					)
				);
			}
		}

		// Divi's activation may schedule dashboard for non-fresh installs — force Get Started.
		$this->force_divi_onboarding_redirect();
		update_option( self::OPTION_COMPLETED, '1', false );

		wp_send_json_success(
			array(
				'redirectUrl' => admin_url( 'admin.php?page=ecmd-onboarding' ),
				'tier'        => $tier,
			)
		);
	}

	/**
	 * Force Divi Modules Get Started after Free SPB handoff.
	 */
	private function force_divi_onboarding_redirect() {
		set_transient( 'ecmd_onboarding_redirect', 'onboarding', 5 * MINUTE_IN_SECONDS );
		delete_option( 'ecmd_onboarding_completed' );
	}

	/**
	 * AJAX: mark onboarding complete (Exit / Finish without re-applying).
	 */
	public function ajax_mark_complete() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		update_option( self::OPTION_COMPLETED, '1', false );
		wp_send_json_success();
	}

	/**
	 * AJAX: search categories / events / tags for the multiselect.
	 */
	public function ajax_search() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$type  = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		$query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';

		$results = array();

		if ( 'categories' === $type || 'target-category' === $type ) {
			$terms = get_terms(
				array(
					'taxonomy'   => 'tribe_events_cat',
					'hide_empty' => false,
					'number'     => 20,
					'name__like' => $query,
					'orderby'    => 'name',
				)
			);
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$results[] = array(
						'value' => $term->slug,
						'label' => $term->name,
					);
				}
			}
		} elseif ( 'tags' === $type || 'target-tag' === $type ) {
			$tag_options = $this->get_event_assigned_tag_options( 20, $query );
			foreach ( $tag_options as $slug => $name ) {
				$results[] = array(
					'value' => $slug,
					'label' => $name,
				);
			}
		} elseif ( 'events' === $type || 'target-event' === $type ) {
			$posts = get_posts(
				array(
					'post_type'      => 'tribe_events',
					'post_status'    => array( 'publish', 'private' ),
					'posts_per_page' => 20,
					's'              => $query,
					'orderby'        => 'title',
					'order'          => 'ASC',
				)
			);
			foreach ( $posts as $post ) {
				if ( empty( $post->post_name ) ) {
					continue;
				}
				$results[] = array(
					'value' => $post->post_name,
					'label' => $post->post_title,
				);
			}
		}

		wp_send_json_success( array( 'results' => $results ) );
	}

	/**
	 * Parse CSV of slugs into a sanitized array.
	 *
	 * @param string $csv CSV string.
	 * @return array
	 */
	private function parse_csv_slugs( $csv ) {
		if ( ! is_string( $csv ) || '' === $csv ) {
			return array();
		}
		$parts = array_filter( array_map( 'trim', explode( ',', $csv ) ) );
		$out   = array();
		foreach ( $parts as $part ) {
			$slug = sanitize_title( $part );
			if ( $slug ) {
				$out[] = $slug;
			}
		}
		return array_values( array_unique( $out ) );
	}
}
