<?php
/**
 * Built-in and current-site profile registry.
 *
 * @package G3AR4UB_AI_Draft_Connector
 */

defined( 'ABSPATH' ) || exit;

class G3AI_Site_Profile {
	/**
	 * Returns protected built-in profiles.
	 *
	 * @return array
	 */
	private static function builtins() {
		return array(
			'g3ar4ub'     => array(
				'id' => 'g3ar4ub', 'domain' => 'g3ar4ub.com', 'brand' => 'G3AR4UB',
				'watermark' => 'G3AR4UB.COM', 'root_class' => 'g3ai-site-g3ar4ub',
			),
			'bootstup'     => array(
				'id' => 'bootstup', 'domain' => 'bootstup.org', 'brand' => 'BootStup',
				'watermark' => 'BOOTSTUP.ORG', 'root_class' => 'g3ai-site-bootstup',
			),
			'beportsquad'  => array(
				'id' => 'beportsquad', 'domain' => 'beportsquad.com', 'brand' => 'BeportSquad',
				'watermark' => 'BEPORTSQUAD.COM', 'root_class' => 'g3ai-site-beportsquad',
			),
			'sporttokvn'   => array(
				'id' => 'sporttokvn', 'domain' => 'sporttokvn.com', 'brand' => 'SportTokVN',
				'watermark' => 'SPORTTOKVN.COM', 'root_class' => 'g3ai-site-sporttokvn',
			),
			'rayesports'   => array(
				'id' => 'rayesports', 'domain' => 'rayesports.com', 'brand' => 'RAYbet',
				'watermark' => 'RAYESPORTS.COM', 'root_class' => 'g3ai-site-rayesports', 'preferred_seo' => 'rank_math',
			),
			'99ggcloud'    => array(
				'id' => '99ggcloud', 'domain' => '99ggcloud.com', 'brand' => '9GG CLOUD',
				'watermark' => '99GGCLOUD.COM', 'root_class' => 'g3ai-site-99ggcloud', 'preferred_seo' => 'rank_math',
			),
			'chillspec'    => array(
				'id' => 'chillspec', 'domain' => 'chillspec.com', 'brand' => 'ChillSpec',
				'watermark' => 'CHILLSPEC.COM', 'root_class' => 'g3ai-site-chillspec', 'preferred_seo' => 'seopress',
			),
		);
	}

	/**
	 * Returns the custom profile bound to this WordPress host.
	 *
	 * @return array
	 */
	private static function custom() {
		$settings = get_option( G3AI_OPTION, array() );
		$settings = is_array( $settings ) ? $settings : array();
		$id       = isset( $settings['custom_profile_id'] ) ? sanitize_key( $settings['custom_profile_id'] ) : '';
		$brand    = isset( $settings['custom_brand'] ) ? sanitize_text_field( $settings['custom_brand'] ) : '';
		$host     = self::current_host();

		if ( ! preg_match( '/^[a-z0-9][a-z0-9-]{1,39}$/', $id ) || '' === $brand || '' === $host ) {
			return array();
		}

		$watermark = isset( $settings['custom_watermark'] ) ? sanitize_text_field( $settings['custom_watermark'] ) : '';
		$provider  = isset( $settings['seo_provider'] ) ? sanitize_key( $settings['seo_provider'] ) : 'auto';

		return array(
			'id'            => $id,
			'domain'        => $host,
			'brand'         => $brand,
			'watermark'     => '' !== $watermark ? $watermark : strtoupper( $host ),
			'root_class'    => 'g3ai-site-' . $id,
			'preferred_seo' => in_array( $provider, array( 'auto', 'seopress', 'rank_math', 'yoast', 'none' ), true ) ? $provider : 'auto',
			'source'        => 'custom',
		);
	}

	/**
	 * Returns all selectable profiles. The custom entry always represents the
	 * current WordPress domain and never routes to a different host.
	 *
	 * @return array
	 */
	public static function all() {
		$profiles = self::builtins();
		$custom   = self::custom();
		if ( ! empty( $custom ) ) {
			$profiles['custom'] = $custom;
		}
		return $profiles;
	}

	/**
	 * Returns the canonical current host.
	 *
	 * @return string
	 */
	private static function current_host() {
		$host = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		return (string) preg_replace( '/^www\./i', '', strtolower( $host ) );
	}

	/**
	 * Detects the profile from the WordPress home host.
	 *
	 * @return string
	 */
	public static function detect() {
		$host = self::current_host();
		foreach ( self::builtins() as $id => $profile ) {
			if ( $host === $profile['domain'] ) {
				return $id;
			}
		}
		$custom = self::custom();
		return ! empty( $custom ) && $host === $custom['domain'] ? $custom['id'] : '';
	}

	/**
	 * Resolves a configured profile ID, falling back to host detection.
	 *
	 * @param string $id Configured ID or the custom sentinel.
	 * @return array
	 */
	public static function get( $id = '' ) {
		$id       = sanitize_key( (string) $id );
		$builtins = self::builtins();
		if ( isset( $builtins[ $id ] ) ) {
			return $builtins[ $id ];
		}

		$custom = self::custom();
		if ( ! empty( $custom ) && ( 'custom' === $id || $custom['id'] === $id ) ) {
			return $custom;
		}

		$detected = self::detect();
		if ( isset( $builtins[ $detected ] ) ) {
			return $builtins[ $detected ];
		}
		return ! empty( $custom ) && $custom['id'] === $detected ? $custom : array();
	}

	/**
	 * Returns public live site identity used by the client for Schema.
	 *
	 * @param string $id Profile ID.
	 * @return array
	 */
	public static function public_site_data( $id = '' ) {
		$profile = self::get( $id );
		$logo_id = absint( get_theme_mod( 'custom_logo' ) );
		$logo    = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
		return array(
			'profile'       => isset( $profile['id'] ) ? $profile['id'] : '',
			'domain'        => isset( $profile['domain'] ) ? $profile['domain'] : '',
			'brand'         => isset( $profile['brand'] ) ? $profile['brand'] : get_bloginfo( 'name' ),
			'watermark'     => isset( $profile['watermark'] ) ? $profile['watermark'] : '',
			'root_class'    => isset( $profile['root_class'] ) ? $profile['root_class'] : '',
			'preferred_seo' => isset( $profile['preferred_seo'] ) ? $profile['preferred_seo'] : 'auto',
			'source'        => isset( $profile['source'] ) ? $profile['source'] : 'builtin',
			'name'          => get_bloginfo( 'name' ),
			'description'   => get_bloginfo( 'description' ),
			'url'           => home_url( '/' ),
			'language'      => get_bloginfo( 'language' ),
			'logo'          => is_string( $logo ) ? $logo : '',
		);
	}
}

