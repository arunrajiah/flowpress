<?php
/**
 * Plugin-wide settings model.
 *
 * Wraps a single `flowpress_settings` WordPress option (array) with typed
 * accessors, defaults, and sanitisation. All reads go through `get()` or
 * `get_all()`; all writes go through `update()`.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes
 * @since      0.2.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class FlowPress_Settings
 *
 * @since 0.2.0
 */
class FlowPress_Settings {

	/**
	 * The WordPress option key used to persist settings.
	 *
	 * @since 0.2.0
	 * @var   string
	 */
	const OPTION_KEY = 'flowpress_settings';

	/**
	 * Factory defaults — returned when a key is missing from the saved option.
	 *
	 * @since 0.2.0
	 * @var   array<string, mixed>
	 */
	private static $defaults = array(
		'from_name'          => '',   // Falls back to blogname at runtime.
		'from_email'         => '',   // Falls back to admin_email at runtime.
		'log_retention_days' => 30,   // 0 = keep forever.
		'max_retry_attempts' => 4,    // 1–10.
	);

	/**
	 * Read a single setting.
	 *
	 * @since  0.2.0
	 * @param  string $key     Setting key (see $defaults).
	 * @param  mixed  $default Override the built-in default for this key.
	 * @return mixed
	 */
	public static function get( string $key, $default = null ) {
		$settings = get_option( self::OPTION_KEY, array() );

		if ( array_key_exists( $key, $settings ) ) {
			return $settings[ $key ];
		}

		if ( null !== $default ) {
			return $default;
		}

		return self::$defaults[ $key ] ?? null;
	}

	/**
	 * Read all settings merged with defaults.
	 *
	 * @since  0.2.0
	 * @return array<string, mixed>
	 */
	public static function get_all(): array {
		$saved = get_option( self::OPTION_KEY, array() );
		return array_merge( self::$defaults, $saved );
	}

	/**
	 * Return the effective "From" name, falling back to the site name.
	 *
	 * @since  0.2.0
	 * @return string
	 */
	public static function from_name(): string {
		$name = self::get( 'from_name' );
		return $name ? $name : get_bloginfo( 'name' );
	}

	/**
	 * Return the effective "From" email, falling back to the admin email.
	 *
	 * @since  0.2.0
	 * @return string
	 */
	public static function from_email(): string {
		$email = self::get( 'from_email' );
		return ( $email && is_email( $email ) ) ? $email : get_option( 'admin_email' );
	}

	/**
	 * Return the max retry attempts (clamped 1–10).
	 *
	 * @since  0.2.0
	 * @return int
	 */
	public static function max_retry_attempts(): int {
		return min( 10, max( 1, (int) self::get( 'max_retry_attempts', 4 ) ) );
	}

	/**
	 * Return log retention in days (0 = keep forever).
	 *
	 * @since  0.2.0
	 * @return int
	 */
	public static function log_retention_days(): int {
		return max( 0, (int) self::get( 'log_retention_days', 30 ) );
	}

	/**
	 * Persist new values, merged with the current saved values.
	 *
	 * @since  0.2.0
	 * @param  array<string, mixed> $data Partial or full settings array.
	 * @return bool True if the option was updated.
	 */
	public static function update( array $data ): bool {
		$current = self::get_all();

		$sanitized = array(
			'from_name'          => sanitize_text_field(
				array_key_exists( 'from_name', $data ) ? $data['from_name'] : $current['from_name']
			),
			'from_email'         => sanitize_email(
				array_key_exists( 'from_email', $data ) ? $data['from_email'] : $current['from_email']
			),
			'log_retention_days' => max( 0, absint(
				array_key_exists( 'log_retention_days', $data ) ? $data['log_retention_days'] : $current['log_retention_days']
			) ),
			'max_retry_attempts' => min( 10, max( 1, absint(
				array_key_exists( 'max_retry_attempts', $data ) ? $data['max_retry_attempts'] : $current['max_retry_attempts']
			) ) ),
		);

		return update_option( self::OPTION_KEY, $sanitized );
	}

	/**
	 * Delete all saved settings (restores defaults).
	 *
	 * @since  0.2.0
	 * @return bool
	 */
	public static function delete(): bool {
		return delete_option( self::OPTION_KEY );
	}
}
