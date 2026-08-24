<?php
/**
 * Boots the shared ECA dashboard module for Event Single Page Builder.
 *
 * @package EventPageTemplatesAddon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'EPTA_ECA_Integration' ) ) {

	/**
	 * Single entry point for ECA dashboard integration.
	 */
	final class EPTA_ECA_Integration {

		const DASHBOARD_PAGE_SLUG = 'cool-plugins-events-addon';
		const DASHBOARD_VERSION   = '1.0.0';

		/**
		 * Admin page slugs used by notices / notice-hiding rules.
		 *
		 * @return string[]
		 */
		public static function admin_page_slugs() {
			return array(
				self::DASHBOARD_PAGE_SLUG,
				EPTA_Onboarding::PAGE_SLUG,
			);
		}

		/**
		 * Load dashboard classes, register this addon, and init onboarding.
		 * Call when is_admin().
		 */
		public static function boot_admin() {
			if ( ! defined( 'ECA_DASHBOARD_VERSION' ) ) {
				define( 'ECA_DASHBOARD_VERSION', self::DASHBOARD_VERSION ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
			}

			require_once EPTA_PLUGIN_DIR . 'admin/eca-dashboard/includes/class-eca-addon-map.php';
			require_once EPTA_PLUGIN_DIR . 'admin/eca-dashboard/includes/class-eca-dashboard-environment.php';
			require_once EPTA_PLUGIN_DIR . 'admin/eca-dashboard/includes/class-eca-dashboard-registry.php';
			require_once EPTA_PLUGIN_DIR . 'admin/eca-dashboard/includes/class-eca-dashboard-i18n.php';
			require_once EPTA_PLUGIN_DIR . 'admin/eca-dashboard/includes/class-eca-dashboard-page.php';
			require_once EPTA_PLUGIN_DIR . 'admin/onboarding/class-epta-onboarding.php';

			ECA_Dashboard_Registry::submit( ECA_DASHBOARD_VERSION, EPTA_PLUGIN_DIR . 'admin/eca-dashboard/' );
			ECA_Dashboard_Registry::register_addon(
				array(
					'slug'          => 'spb',
					'host_slug'     => 'spb',
					'text_domain'   => 'event-page-templates-addon-for-the-events-calendar',
					'dashboard_url' => EPTA_PLUGIN_URL . 'admin/eca-dashboard/',
					'admin_urls'    => array(
						// Driver keys → More Addons / shared deep-links.
						'spb'       => admin_url( 'edit.php?post_type=epta' ),
						'esb'       => admin_url( 'admin.php?page=tribe-events-shortcode-template-settings' ),
						// Plugins without their own dashboard → shared Events Addons page.
						'divi'      => admin_url( 'admin.php?page=' . self::DASHBOARD_PAGE_SLUG ),
						'widgets'   => admin_url( 'admin.php?page=' . self::DASHBOARD_PAGE_SLUG ),
						'speakers'  => admin_url( 'admin.php?page=esas-speaker-sponsor-settings' ),
						'countdown' => admin_url( 'admin.php?page=countdown_for_the_events_calendar' ),
						// Workflow method-tab ids → "Open settings" (only tabs listed here).
						'shortcode' => admin_url( 'admin.php?page=tribe-events-shortcode-template-settings' ),
						'static'    => admin_url( 'edit.php?post_type=epta' ),
					),
					// Titles translated in ECA_Dashboard_Page::register_menus() on admin_menu (WP 6.7+).
					'menu'          => array(
						'slug'     => self::DASHBOARD_PAGE_SLUG,
						'position' => 9,
					),
				)
			);

			add_action( 'plugins_loaded', array( 'ECA_Dashboard_Registry', 'boot' ), 20 );
			EPTA_Onboarding::init();
			// Activation redirect: fresh → onboarding, reactivation → dashboard
			// (scheduled in activate(), consumed via EPTA_Onboarding::maybe_redirect).
		}
	}
}
