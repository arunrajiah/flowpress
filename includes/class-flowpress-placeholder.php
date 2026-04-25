<?php
/**
 * Resolves {{variable}} placeholders in action config strings.
 *
 * @package    FlowPress
 * @subpackage FlowPress/includes
 * @since      0.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class FlowPress_Placeholder
 *
 * Replaces {{token}} syntax in strings with values from a context array.
 * Unknown tokens are left as-is so nothing silently disappears.
 *
 * @since 0.3.0
 */
class FlowPress_Placeholder {

	/**
	 * Replace all {{token}} occurrences in $text with values from $context.
	 *
	 * @since  0.3.0
	 * @param  string $text    The template string.
	 * @param  array  $context Key→value map of available tokens.
	 * @return string
	 */
	public static function resolve( $text, array $context ) {
		return preg_replace_callback(
			'/\{\{([a-zA-Z0-9_]+)\}\}/',
			static function ( $matches ) use ( $context ) {
				$key = $matches[1];
				return array_key_exists( $key, $context ) ? (string) $context[ $key ] : $matches[0];
			},
			$text
		);
	}

	/**
	 * Extract all {{token}} names used in a string.
	 *
	 * @since  0.3.0
	 * @param  string $text Template string.
	 * @return string[]     Unique token names found.
	 */
	public static function extract_tokens( $text ) {
		preg_match_all( '/\{\{([a-zA-Z0-9_]+)\}\}/', $text, $matches );
		return array_unique( $matches[1] );
	}
}
