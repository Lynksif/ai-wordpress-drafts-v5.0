<?php
/**
 * SEO metadata adapters for supported WordPress SEO plugins.
 *
 * @package G3AR4UB_AI_Draft_Connector
 */

defined( 'ABSPATH' ) || exit;

class G3AI_SEO_Adapter {
	/**
	 * Detects the active SEO provider or honors an active configured provider.
	 *
	 * @param string $configured Configured provider.
	 * @return string
	 */
	public static function resolve( $configured = 'auto' ) {
		$configured = sanitize_key( (string) $configured );
		if ( in_array( $configured, array( 'seopress', 'rank_math', 'yoast', 'none' ), true ) ) {
			return self::is_active( $configured ) || 'none' === $configured ? $configured : 'none';
		}
		foreach ( array( 'seopress', 'rank_math', 'yoast' ) as $provider ) {
			if ( self::is_active( $provider ) ) {
				return $provider;
			}
		}
		return 'none';
	}

	/**
	 * Checks whether a provider is active.
	 *
	 * @param string $provider Provider key.
	 * @return bool
	 */
	public static function is_active( $provider ) {
		switch ( $provider ) {
			case 'seopress':
				return defined( 'SEOPRESS_VERSION' ) || function_exists( 'seopress_get_service' ) || class_exists( 'SEOPress\\Core\\Kernel' );
			case 'rank_math':
				return defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' ) || class_exists( 'RankMath\\Helper' );
			case 'yoast':
				return defined( 'WPSEO_VERSION' ) || class_exists( 'WPSEO_Options' );
			case 'none':
				return true;
			default:
				return false;
		}
	}

	/**
	 * Returns provider diagnostics without secrets.
	 *
	 * @param string $configured Configured provider.
	 * @return array
	 */
	public static function status( $configured = 'auto' ) {
		return array(
			'configured' => sanitize_key( (string) $configured ),
			'detected'   => self::resolve( $configured ),
			'available'  => array(
				'seopress' => self::is_active( 'seopress' ),
				'rank_math'=> self::is_active( 'rank_math' ),
				'yoast'    => self::is_active( 'yoast' ),
			),
			'meta_sync'  => true,
		);
	}

	/**
	 * Synchronizes connector and provider-specific SEO/social metadata.
	 *
	 * @param int    $post_id    Post ID.
	 * @param array  $seo        SEO payload.
	 * @param string $configured Configured provider.
	 */
	public static function sync( $post_id, $seo, $configured = 'auto' ) {
		$seo      = is_array( $seo ) ? $seo : array();
		$provider = self::resolve( $configured );
		$title    = isset( $seo['seo_title'] ) && is_scalar( $seo['seo_title'] ) ? sanitize_text_field( (string) $seo['seo_title'] ) : '';
		$desc     = isset( $seo['meta_description'] ) && is_scalar( $seo['meta_description'] ) ? sanitize_textarea_field( (string) $seo['meta_description'] ) : '';
		$keyword  = isset( $seo['focus_keyword'] ) && is_scalar( $seo['focus_keyword'] ) ? sanitize_text_field( (string) $seo['focus_keyword'] ) : '';
		$social   = isset( $seo['social'] ) && is_array( $seo['social'] ) ? $seo['social'] : array();
		$image    = '';
		$thumb_id = get_post_thumbnail_id( $post_id );
		if ( $thumb_id ) {
			$image_url = wp_get_attachment_image_url( $thumb_id, 'full' );
			$image     = is_string( $image_url ) ? $image_url : '';
		}

		$values = array(
			'seo_title'             => $title,
			'meta_description'      => $desc,
			'focus_keyword'         => $keyword,
			'facebook_title'        => self::social_value( $social, 'facebook_title', $title, 'text' ),
			'facebook_description'  => self::social_value( $social, 'facebook_description', $desc, 'textarea' ),
			'facebook_image'        => self::social_value( $social, 'facebook_image', $image, 'url' ),
			'twitter_title'         => self::social_value( $social, 'twitter_title', $title, 'text' ),
			'twitter_description'   => self::social_value( $social, 'twitter_description', $desc, 'textarea' ),
			'twitter_image'         => self::social_value( $social, 'twitter_image', $image, 'url' ),
		);

		$connector_map = array(
			'seo_title'            => '_g3ai_seo_title',
			'meta_description'     => '_g3ai_meta_description',
			'focus_keyword'        => '_g3ai_focus_keyword',
			'facebook_title'       => '_g3ai_facebook_title',
			'facebook_description' => '_g3ai_facebook_description',
			'facebook_image'       => '_g3ai_facebook_image',
			'twitter_title'        => '_g3ai_twitter_title',
			'twitter_description'  => '_g3ai_twitter_description',
			'twitter_image'        => '_g3ai_twitter_image',
		);
		foreach ( $connector_map as $field => $meta_key ) {
			update_post_meta( $post_id, $meta_key, $values[ $field ] );
		}

		$provider_maps = self::provider_maps();
		if ( isset( $provider_maps[ $provider ] ) ) {
			foreach ( $provider_maps[ $provider ] as $field => $meta_key ) {
				update_post_meta( $post_id, $meta_key, $values[ $field ] );
			}
		}
		update_post_meta( $post_id, '_g3ai_seo_provider', $provider );
	}

	/**
	 * Returns saved connector SEO data for verification.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function read( $post_id ) {
		return array(
			'seo_title'        => get_post_meta( $post_id, '_g3ai_seo_title', true ),
			'meta_description' => get_post_meta( $post_id, '_g3ai_meta_description', true ),
			'focus_keyword'    => get_post_meta( $post_id, '_g3ai_focus_keyword', true ),
			'provider'         => get_post_meta( $post_id, '_g3ai_seo_provider', true ),
			'social'           => array(
				'facebook_title'       => get_post_meta( $post_id, '_g3ai_facebook_title', true ),
				'facebook_description' => get_post_meta( $post_id, '_g3ai_facebook_description', true ),
				'facebook_image'       => get_post_meta( $post_id, '_g3ai_facebook_image', true ),
				'twitter_title'        => get_post_meta( $post_id, '_g3ai_twitter_title', true ),
				'twitter_description'  => get_post_meta( $post_id, '_g3ai_twitter_description', true ),
				'twitter_image'        => get_post_meta( $post_id, '_g3ai_twitter_image', true ),
			),
		);
	}

	private static function social_value( $social, $field, $fallback, $type ) {
		$raw = isset( $social[ $field ] ) && is_scalar( $social[ $field ] ) ? (string) $social[ $field ] : $fallback;
		if ( 'url' === $type ) {
			return esc_url_raw( $raw, array( 'http', 'https' ) );
		}
		return 'textarea' === $type ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
	}

	private static function provider_maps() {
		return array(
			'seopress' => array(
				'seo_title' => '_seopress_titles_title', 'meta_description' => '_seopress_titles_desc', 'focus_keyword' => '_seopress_analysis_target_kw',
				'facebook_title' => '_seopress_social_fb_title', 'facebook_description' => '_seopress_social_fb_desc', 'facebook_image' => '_seopress_social_fb_img',
				'twitter_title' => '_seopress_social_twitter_title', 'twitter_description' => '_seopress_social_twitter_desc', 'twitter_image' => '_seopress_social_twitter_img',
			),
			'rank_math' => array(
				'seo_title' => 'rank_math_title', 'meta_description' => 'rank_math_description', 'focus_keyword' => 'rank_math_focus_keyword',
				'facebook_title' => 'rank_math_facebook_title', 'facebook_description' => 'rank_math_facebook_description', 'facebook_image' => 'rank_math_facebook_image',
				'twitter_title' => 'rank_math_twitter_title', 'twitter_description' => 'rank_math_twitter_description', 'twitter_image' => 'rank_math_twitter_image',
			),
			'yoast' => array(
				'seo_title' => '_yoast_wpseo_title', 'meta_description' => '_yoast_wpseo_metadesc', 'focus_keyword' => '_yoast_wpseo_focuskw',
				'facebook_title' => '_yoast_wpseo_opengraph-title', 'facebook_description' => '_yoast_wpseo_opengraph-description', 'facebook_image' => '_yoast_wpseo_opengraph-image',
				'twitter_title' => '_yoast_wpseo_twitter-title', 'twitter_description' => '_yoast_wpseo_twitter-description', 'twitter_image' => '_yoast_wpseo_twitter-image',
			),
		);
	}
}
