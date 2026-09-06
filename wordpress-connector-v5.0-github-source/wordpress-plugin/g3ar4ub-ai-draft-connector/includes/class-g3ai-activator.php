<?php
/**
 * Activation and upgrade routines.
 *
 * @package G3AR4UB_AI_Draft_Connector
 */

defined( 'ABSPATH' ) || exit;

class G3AI_Activator {
	const ROLE = 'g3ai_writer';

	/**
	 * Plugin activation callback.
	 */
	public static function activate() {
		self::install_role();
		self::install_defaults();
		self::migrate_managed_posts_to_seopress();
		update_option( 'g3ai_version', G3AI_VERSION, false );
	}

	/**
	 * Applies non-destructive upgrades after a plugin update.
	 */
	public static function maybe_upgrade() {
		if ( G3AI_VERSION === get_option( 'g3ai_version' ) ) {
			return;
		}

		self::install_role();
		self::install_defaults();
		self::migrate_managed_posts_to_seopress();
		update_option( 'g3ai_version', G3AI_VERSION, false );
	}

	/**
	 * Creates a least-privilege role. It can draft and upload, but cannot publish
	 * or delete content.
	 */
	private static function install_role() {
		$capabilities = array(
			'read'                   => true,
			'edit_posts'             => true,
			'upload_files'           => true,
			'publish_posts'          => false,
			'delete_posts'           => false,
			'delete_published_posts' => false,
			'edit_published_posts'   => false,
			'edit_others_posts'      => false,
			'delete_others_posts'    => false,
			'manage_categories'      => false,
			'unfiltered_html'        => false,
		);

		$role = get_role( self::ROLE );
		if ( ! $role ) {
			add_role( self::ROLE, 'AI Draft Writer', $capabilities );
			return;
		}

		foreach ( $capabilities as $capability => $granted ) {
			if ( $granted ) {
				$role->add_cap( $capability, true );
			} else {
				$role->remove_cap( $capability );
			}
		}
	}

	/**
	 * Adds only missing defaults so updates do not overwrite admin choices.
	 */
	private static function install_defaults() {
		$defaults = array(
			'enabled'          => 1,
			'default_category' => 0,
			'schema_mode'      => 'connector_schema',
			'log_limit'        => 30,
			'rate_limit'       => 30,
			'site_profile'     => G3AI_Site_Profile::detect(),
			'seo_provider'     => 'auto',
			'custom_profile_id'=> '',
			'custom_brand'     => '',
			'custom_watermark' => '',
		);

		$current = get_option( G3AI_OPTION, array() );
		if ( ! is_array( $current ) ) {
			$current = array();
		}

		// Migrate the v1.0 Rank Math choices without overwriting other settings.
		if ( isset( $current['schema_mode'] ) ) {
			$legacy_modes = array(
				'replace_rank_math' => 'connector_schema',
				'append_rank_math'  => 'seopress_schema',
			);
			if ( isset( $legacy_modes[ $current['schema_mode'] ] ) ) {
				$current['schema_mode'] = $legacy_modes[ $current['schema_mode'] ];
			}
		}

		update_option( G3AI_OPTION, wp_parse_args( $current, $defaults ), false );
	}

	/**
	 * Copies connector-owned SEO data from v1.0 into SEOPress meta. Legacy third-
	 * party meta is left untouched so the migration is non-destructive.
	 */
	private static function migrate_managed_posts_to_seopress() {
		$post_ids = get_posts(
			array(
				'post_type'              => 'post',
				'post_status'            => array( 'draft', 'pending' ),
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_key'               => '_g3ai_managed',
				'meta_value'             => '1',
			)
		);

		foreach ( $post_ids as $post_id ) {
			$title       = (string) get_post_meta( $post_id, '_g3ai_seo_title', true );
			$description = (string) get_post_meta( $post_id, '_g3ai_meta_description', true );
			$keyword     = (string) get_post_meta( $post_id, '_g3ai_focus_keyword', true );
			$image       = get_the_post_thumbnail_url( $post_id, 'full' );
			$image       = is_string( $image ) ? $image : '';

			$meta = array(
				'_seopress_titles_title'         => $title,
				'_seopress_titles_desc'          => $description,
				'_seopress_analysis_target_kw'   => $keyword,
				'_seopress_social_fb_title'      => $title,
				'_seopress_social_fb_desc'       => $description,
				'_seopress_social_fb_img'        => $image,
				'_seopress_social_twitter_title' => $title,
				'_seopress_social_twitter_desc'  => $description,
				'_seopress_social_twitter_img'   => $image,
			);

			foreach ( $meta as $meta_key => $value ) {
				if ( '' !== $value && '' === (string) get_post_meta( $post_id, $meta_key, true ) ) {
					update_post_meta( $post_id, $meta_key, $value );
				}
			}
		}
	}
}
