<?php
/**
 * EPTA onboarding preference storage for CoolPlugins feedback / cron.
 *
 * Uses shared Cool Events options so sibling addons can share consent:
 * - cpfm_opt_in_choice_cool_events
 * - cpfm_onboarding_preferences_cool_events
 *
 * @package EventPageTemplatesAddon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'EPTA_Onboarding_Optin' ) ) {

	/**
	 * Onboarding telemetry + preference helpers for EPTA.
	 */
	final class EPTA_Onboarding_Optin {

		const CATEGORY           = 'cool_events';
		const CHOICE_OPTION      = 'cpfm_opt_in_choice_cool_events';
		const PREFERENCES_OPTION = 'cpfm_onboarding_preferences_cool_events';
		const PLUGIN_KEY         = 'epta';
		const PLUGIN_SLUG        = 'event-page-templates-addon-for-the-events-calendar';
		const AJAX_SAVE_PREFS    = 'epta_onboarding_save_preferences';
		const AJAX_DEACTIVATE    = 'epta_onboarding_save_on_deactivate';
		const NONCE_ACTION       = 'epta_onboarding_nonce';

		/**
		 * Register wizard AJAX handlers.
		 */
		public static function init() {
			static $booted = false;

			if ( $booted ) {
				return;
			}

			$booted = true;

			add_action( 'wp_ajax_' . self::AJAX_SAVE_PREFS, array( __CLASS__, 'ajax_save_preferences' ) );
		}

		/**
		 * @return string|null 'yes'|'no'|null
		 */
		public static function get_choice() {
			$choice = get_option( self::CHOICE_OPTION, null );
			if ( 'yes' === $choice || 'no' === $choice ) {
				return $choice;
			}
			return null;
		}

		/**
		 * Whether the onboarding telemetry UI should be visible.
		 *
		 * @return bool
		 */
		public static function should_show_telemetry() {
			return 'yes' !== self::get_choice();
		}

		/**
		 * Default checked state when the telemetry UI is shown.
		 *
		 * @return bool
		 */
		public static function get_default_checked() {
			$choice = self::get_choice();

			if ( null === $choice ) {
				return true;
			}

			return 'yes' === $choice;
		}

		/**
		 * Payload for wp_localize_script.
		 *
		 * @return array{show: bool, checked: bool, choice: string|null}
		 */
		public static function get_telemetry_localize() {
			return array(
				'show'    => self::should_show_telemetry(),
				'checked' => self::get_default_checked(),
				'choice'  => self::get_choice(),
			);
		}

		/**
		 * Preferences for cron / feedback extra_details. Empty unless opted in.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_preferences_for_extra_details() {
			if ( 'yes' !== self::get_choice() ) {
				return array();
			}

			$all = get_option( self::PREFERENCES_OPTION, array() );
			return is_array( $all ) ? $all : array();
		}

		/**
		 * @param string $yes_or_no 'yes' or 'no'.
		 */
		public static function save_choice( $yes_or_no ) {
			$choice = ( 'yes' === $yes_or_no ) ? 'yes' : 'no';
			update_option( self::CHOICE_OPTION, $choice, false );
		}

		/**
		 * @param string               $plugin_key Plugin bucket key.
		 * @param array<string, mixed> $payload    Preference row.
		 */
		public static function save_preferences( $plugin_key, $payload ) {
			$plugin_key = sanitize_key( $plugin_key );
			if ( '' === $plugin_key || ! is_array( $payload ) ) {
				return;
			}

			$all = get_option( self::PREFERENCES_OPTION, array() );
			if ( ! is_array( $all ) ) {
				$all = array();
			}

			$all[ $plugin_key ] = $payload;
			update_option( self::PREFERENCES_OPTION, $all, false );
		}

		/**
		 * Persist wizard selections + consent; send cron payload when opted in.
		 *
		 * @param bool                 $telemetry  User accepted sharing.
		 * @param array<string, mixed> $selections Raw wizard selections.
		 * @param string               $source     Origin tag.
		 */
		public static function persist_from_wizard( $telemetry, $selections, $source = 'wizard' ) {
			$sanitized = self::sanitize_epta_selections( $selections );
			$row       = self::build_epta_preference_row( $telemetry, $sanitized, $source );

			self::save_choice( $telemetry ? 'yes' : 'no' );
			self::save_preferences( self::PLUGIN_KEY, $row );

			if ( $telemetry ) {
				self::apply_opt_in_for_epta();
			}
		}

		/**
		 * Save from localStorage JSON (wizard state on deactivate).
		 *
		 * @param string $raw    JSON string.
		 * @param string $source Origin tag.
		 */
		public static function save_from_wizard_state_json( $raw, $source = 'deactivate' ) {
			if ( ! is_string( $raw ) || '' === $raw ) {
				return;
			}

			$decoded = json_decode( $raw, true );
			if ( ! is_array( $decoded ) ) {
				return;
			}

			$telemetry  = ! empty( $decoded['telemetryAccepted'] );
			$selections = isset( $decoded['selections'] ) && is_array( $decoded['selections'] )
				? $decoded['selections']
				: array();

			self::persist_from_wizard( $telemetry, $selections, $source );
		}

		/**
		 * AJAX: live wizard preference sync while selecting.
		 */
		public static function ajax_save_preferences() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
			}

			check_ajax_referer( self::NONCE_ACTION, 'nonce' );

			$telemetry_raw = isset( $_POST['telemetry'] ) ? sanitize_text_field( wp_unslash( $_POST['telemetry'] ) ) : '0';
			$telemetry     = ( '1' === $telemetry_raw || 'yes' === $telemetry_raw || 'true' === $telemetry_raw );

			$selections = array();
			if ( isset( $_POST['selections'] ) ) {
				$raw = wp_unslash( $_POST['selections'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				if ( is_string( $raw ) ) {
					$decoded = json_decode( $raw, true );
					if ( is_array( $decoded ) ) {
						$selections = $decoded;
					}
				} elseif ( is_array( $raw ) ) {
					$selections = $raw;
				}
			}

			self::persist_from_wizard( $telemetry, $selections, 'wizard' );

			wp_send_json_success(
				array(
					'choice'    => $telemetry ? 'yes' : 'no',
					'telemetry' => self::get_telemetry_localize(),
				)
			);
		}

		/**
		 * AJAX: persist snapshot from plugins screen before deactivate.
		 */
		public static function ajax_save_on_deactivate() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
			}

			check_ajax_referer( '_cool-plugins_deactivate_feedback_nonce' );

			$raw = isset( $_POST['onboarding_state'] ) ? wp_unslash( $_POST['onboarding_state'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			self::save_from_wizard_state_json( is_string( $raw ) ? $raw : '', 'deactivate' );

			wp_send_json_success();
		}

		/**
		 * Schedule cron + send site payload once when user opts in.
		 */
		public static function apply_opt_in_for_epta() {
			do_action( 'cpfm_after_opt_in_epta', 'cool_events' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

			if ( ! class_exists( 'EPTA_Onboarding', false ) ) {
				$file = EPTA_PLUGIN_DIR . 'admin/onboarding/class-epta-onboarding.php';
				if ( file_exists( $file ) ) {
					require_once $file;
				}
			}

			if ( class_exists( 'EPTA_Onboarding' ) ) {
				EPTA_Onboarding::schedule_crons_for_other_plugins();
			}
		}

		/**
		 * @return string[]
		 */
		public static function epta_selection_allowlist() {
			return array(
				'template',
				'target',
				'target-category',
				'target-event',
				'target-tag',
				'date-format',
				'colors',
			);
		}

		/**
		 * @param mixed $raw Raw selections.
		 * @return array<string, mixed>
		 */
		public static function sanitize_epta_selections( $raw ) {
			if ( ! is_array( $raw ) ) {
				return array();
			}

			$out       = array();
			$allowlist = self::epta_selection_allowlist();

			foreach ( $allowlist as $key ) {
				if ( ! array_key_exists( $key, $raw ) ) {
					continue;
				}

				$value = $raw[ $key ];

				if ( 'colors' === $key && is_array( $value ) ) {
					$colors = array();
					foreach ( array( 'primary', 'alternate', 'secondary' ) as $color_key ) {
						if ( empty( $value[ $color_key ] ) ) {
							continue;
						}
						$hex = sanitize_hex_color( $value[ $color_key ] );
						if ( $hex ) {
							$colors[ $color_key ] = $hex;
						}
					}
					if ( ! empty( $colors ) ) {
						$out['colors'] = $colors;
					}
					continue;
				}

				if ( is_array( $value ) ) {
					$out[ $key ] = array_map( 'sanitize_text_field', $value );
					continue;
				}

				if ( is_bool( $value ) ) {
					$out[ $key ] = $value;
					continue;
				}

				$out[ $key ] = sanitize_text_field( (string) $value );
			}

			return $out;
		}

		/**
		 * @param bool                 $telemetry  Whether user accepted sharing.
		 * @param array<string, mixed> $selections Sanitized selections.
		 * @param string               $source     Origin tag.
		 * @return array<string, mixed>
		 */
		public static function build_epta_preference_row( $telemetry, $selections, $source = 'wizard' ) {
			return array(
				'plugin'     => self::PLUGIN_SLUG,
				'updated_at' => gmdate( 'Y-m-d H:i:s' ),
				'telemetry'  => (bool) $telemetry,
				'source'     => sanitize_key( $source ),
				'selections' => is_array( $selections ) ? $selections : array(),
			);
		}
	}
}
