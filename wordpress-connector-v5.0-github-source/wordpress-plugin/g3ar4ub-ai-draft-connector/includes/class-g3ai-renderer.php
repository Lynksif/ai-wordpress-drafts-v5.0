<?php
/**
 * Front-end assets and JSON-LD rendering.
 *
 * @package G3AR4UB_AI_Draft_Connector
 */

defined( 'ABSPATH' ) || exit;

class G3AI_Renderer {
	/**
	 * Registers front-end hooks.
	 */
	public function register_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_article_assets' ) );
		add_action( 'wp_head', array( $this, 'output_connector_schema' ), 99 );
		$this->register_seopress_schema_filters();
		add_filter( 'rank_math/json_ld', array( $this, 'suppress_seopress_schema_data' ), 99 );
		add_filter( 'wpseo_json_ld_output', array( $this, 'suppress_seopress_schema_data' ), 99 );
		add_filter( 'wpseo_schema_graph', array( $this, 'suppress_seopress_schema_data' ), 99 );
	}

	/**
	 * Registers SEOPress' documented Schema filters. They only suppress SEOPress
	 * output on a connector-managed post when the connector owns that post's
	 * complete JSON-LD graph.
	 */
	private function register_seopress_schema_filters() {
		$automatic_types = array(
			'article',
			'course',
			'event',
			'faq',
			'howto',
			'job',
			'localbusiness',
			'product',
			'recipe',
			'review',
			'service',
			'softwareapplication',
			'softwareapp',
			'software',
			'video',
			'custom',
		);

		foreach ( $automatic_types as $type ) {
			add_filter( 'seopress_schemas_auto_' . $type . '_json', array( $this, 'suppress_seopress_schema_data' ), 99 );
			add_filter( 'seopress_schemas_auto_' . $type . '_html', array( $this, 'suppress_seopress_schema_html' ), 99 );
		}

		$manual_types = array(
			'article',
			'course',
			'event',
			'faq',
			'howto',
			'job',
			'localbusiness',
			'product',
			'recipe',
			'review',
			'service',
			'softwareapplication',
			'softwareapp',
			'software',
			'video',
			'custom',
		);

		foreach ( $manual_types as $type ) {
			add_filter( 'seopress_pro_get_json_data_' . $type, array( $this, 'suppress_seopress_schema_data' ), 99 );
		}

		add_filter( 'seopress_get_json_data_organization', array( $this, 'suppress_seopress_schema_data' ), 99 );
		add_filter( 'seopress_schemas_website', array( $this, 'suppress_seopress_schema_data' ), 99 );
		add_filter( 'seopress_schemas_website_html', array( $this, 'suppress_seopress_schema_html' ), 99 );
	}

	/**
	 * Loads the scoped profile article layout only on connector-managed posts.
	 */
	public function enqueue_article_assets() {
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id || ! get_post_meta( $post_id, '_g3ai_managed', true ) ) {
			return;
		}

		wp_enqueue_style(
			'g3ai-article-runtime',
			G3AI_URL . 'assets/css/article-runtime.css',
			array(),
			G3AI_VERSION
		);
		wp_enqueue_script(
			'g3ai-article-runtime',
			G3AI_URL . 'assets/js/article-runtime.js',
			array(),
			G3AI_VERSION,
			true
		);
	}

	/**
	 * Suppresses a SEOPress Schema data structure when connector Schema is active.
	 *
	 * @param mixed $data SEOPress Schema value.
	 * @return mixed
	 */
	public function suppress_seopress_schema_data( $data ) {
		if ( ! $this->connector_owns_current_schema() ) {
			return $data;
		}

		return is_array( $data ) ? array() : '';
	}

	/**
	 * Suppresses rendered SEOPress Schema HTML when connector Schema is active.
	 *
	 * @param mixed $html SEOPress Schema markup.
	 * @return mixed
	 */
	public function suppress_seopress_schema_html( $html ) {
		return $this->connector_owns_current_schema() ? '' : $html;
	}

	/**
	 * Outputs the complete connector JSON-LD graph when it owns Schema handling.
	 */
	public function output_connector_schema() {
		if ( ! $this->connector_owns_current_schema() ) {
			return;
		}

		$schema = $this->get_current_schema_document();
		if ( empty( $schema ) ) {
			return;
		}

		$json = wp_json_encode(
			$schema,
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		);

		if ( false !== $json ) {
			echo "\n" . '<script type="application/ld+json" id="g3ai-schema">' . $json . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Checks whether the current managed post has a complete connector graph and
	 * the connector has been selected as the one Schema output source.
	 *
	 * @return bool
	 */
	private function connector_owns_current_schema() {
		$settings = G3AI_REST_Controller::get_settings();
		if ( 'connector_schema' !== $settings['schema_mode'] ) {
			return false;
		}

		return ! empty( $this->get_current_schema_document() );
	}

	/**
	 * Returns the stored schema document for the current managed post.
	 *
	 * @return array
	 */
	private function get_current_schema_document() {
		if ( ! is_singular( 'post' ) ) {
			return array();
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id || ! get_post_meta( $post_id, '_g3ai_managed', true ) ) {
			return array();
		}

		$json = get_post_meta( $post_id, '_g3ai_schema_json', true );
		if ( ! is_string( $json ) || '' === $json ) {
			return array();
		}

		$decoded = json_decode( $json, true );
		return is_array( $decoded ) ? $decoded : array();
	}

}
