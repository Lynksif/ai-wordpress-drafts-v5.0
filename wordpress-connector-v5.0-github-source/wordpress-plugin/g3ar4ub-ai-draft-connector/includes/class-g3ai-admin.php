<?php
/**
 * Connector settings and activity log.
 *
 * @package G3AR4UB_AI_Draft_Connector
 */

defined( 'ABSPATH' ) || exit;

class G3AI_Admin {
	const LOG_OPTION = 'g3ai_activity_log';

	/**
	 * Registers admin hooks.
	 */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Adds the Settings submenu page.
	 */
	public function add_settings_page() {
		add_options_page(
			'AI Multi-Site Draft Connector',
			'AI Draft Connector',
			'manage_options',
			'g3ar4ub-ai-connector',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Registers the settings array.
	 */
	public function register_settings() {
		register_setting(
			'g3ai_settings_group',
			G3AI_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Validates settings submitted by an administrator.
	 *
	 * @param array $input Raw settings.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$input = is_array( $input ) ? $input : array();
		$modes = array( 'connector_schema', 'seopress_schema', 'disabled' );
		$mode  = isset( $input['schema_mode'] ) ? sanitize_key( $input['schema_mode'] ) : 'connector_schema';
		$profiles = array_keys( G3AI_Site_Profile::all() );
		$profiles[] = 'custom';
		$profile  = isset( $input['site_profile'] ) ? sanitize_key( $input['site_profile'] ) : G3AI_Site_Profile::detect();
		$providers = array( 'auto', 'seopress', 'rank_math', 'yoast', 'none' );
		$provider  = isset( $input['seo_provider'] ) ? sanitize_key( $input['seo_provider'] ) : 'auto';
		$custom_id = isset( $input['custom_profile_id'] ) ? sanitize_key( $input['custom_profile_id'] ) : '';
		$custom_brand = isset( $input['custom_brand'] ) ? sanitize_text_field( $input['custom_brand'] ) : '';
		$custom_watermark = isset( $input['custom_watermark'] ) ? sanitize_text_field( $input['custom_watermark'] ) : '';
		$builtin_ids = array_diff( array_keys( G3AI_Site_Profile::all() ), array( 'custom' ) );
		$custom_invalid = ! preg_match( '/^[a-z0-9][a-z0-9-]{1,39}$/', $custom_id ) || in_array( $custom_id, $builtin_ids, true ) || '' === $custom_brand || strlen( $custom_brand ) > 80 || strlen( $custom_watermark ) > 80;

		if ( 'custom' === $profile && $custom_invalid ) {
			add_settings_error( G3AI_OPTION, 'g3ai_invalid_custom_profile', 'Custom Profile ID must be unique, 2-40 lowercase letters/numbers/hyphens, and Brand is required.' );
		}

		return array(
			'enabled'          => empty( $input['enabled'] ) || ( 'custom' === $profile && $custom_invalid ) ? 0 : 1,
			'default_category' => isset( $input['default_category'] ) ? absint( $input['default_category'] ) : 0,
			'schema_mode'      => in_array( $mode, $modes, true ) ? $mode : 'connector_schema',
			'log_limit'        => isset( $input['log_limit'] ) ? max( 10, min( 100, absint( $input['log_limit'] ) ) ) : 30,
			'rate_limit'       => isset( $input['rate_limit'] ) ? max( 5, min( 120, absint( $input['rate_limit'] ) ) ) : 30,
			'site_profile'     => in_array( $profile, $profiles, true ) ? $profile : G3AI_Site_Profile::detect(),
			'seo_provider'     => in_array( $provider, $providers, true ) ? $provider : 'auto',
			'custom_profile_id'=> $custom_id,
			'custom_brand'     => $custom_brand,
			'custom_watermark' => $custom_watermark,
		);
	}

	/**
	 * Renders the complete setup and monitoring page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings       = G3AI_REST_Controller::get_settings();
		$health_url     = rest_url( G3AI_REST_Controller::NAMESPACE . '/health' );
		$drafts_url     = rest_url( G3AI_REST_Controller::NAMESPACE . '/drafts' );
		$media_url      = rest_url( G3AI_REST_Controller::NAMESPACE . '/media' );
		$is_https       = 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME );
		$app_passwords  = function_exists( 'wp_is_application_passwords_supported' ) && wp_is_application_passwords_supported();
		$logs           = get_option( self::LOG_OPTION, array() );
		$profile        = G3AI_Site_Profile::get( $settings['site_profile'] );
		$profile_options= G3AI_Site_Profile::all();
		if ( ! isset( $profile_options['custom'] ) ) {
			$profile_options['custom'] = array( 'domain' => (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) . ' (custom)' );
		}
		$seo_status     = G3AI_SEO_Adapter::status( $settings['seo_provider'] );
		$profile_matches= ! empty( $profile ) && G3AI_Site_Profile::detect() === $profile['id'];
		$application_ok = $is_https && $app_passwords && $profile_matches;
		$provider_label = array( 'seopress' => 'SEOPress', 'rank_math' => 'Rank Math', 'yoast' => 'Yoast SEO', 'none' => 'None detected' );
		$provider_name  = isset( $provider_label[ $seo_status['detected'] ] ) ? $provider_label[ $seo_status['detected'] ] : $seo_status['detected'];
		$preferred_seo  = isset( $profile['preferred_seo'] ) ? $profile['preferred_seo'] : 'auto';
		$preferred_match= 'auto' === $preferred_seo || $preferred_seo === $seo_status['detected'];
		?>
		<div class="wrap g3ai-admin">
			<h1>AI Multi-Site Draft Connector</h1>
			<p class="description">Secure draft intake for <?php echo esc_html( isset( $profile['domain'] ) ? $profile['domain'] : home_url() ); ?>. Version <?php echo esc_html( G3AI_VERSION ); ?>.</p>

			<div class="g3ai-status <?php echo $application_ok ? 'is-ready' : 'needs-action'; ?>">
				<strong><?php echo $application_ok ? 'WordPress is ready for a secure connection.' : 'Connection setup needs attention.'; ?></strong>
				<span>Profile: <?php echo esc_html( isset( $profile['id'] ) ? $profile['id'] : 'Not selected' ); ?> · HTTPS: <?php echo $is_https ? 'Ready' : 'Required'; ?> · Application Passwords: <?php echo $app_passwords ? 'Available' : 'Unavailable'; ?> · SEO: <?php echo esc_html( $provider_name ); ?></span>
			</div>
			<?php if ( ! $profile_matches ) : ?>
				<div class="notice notice-error inline"><p><strong>Website profile does not match this WordPress domain.</strong> Select the exact domain profile before enabling draft intake.</p></div>
			<?php elseif ( 'none' === $seo_status['detected'] ) : ?>
				<div class="notice notice-warning inline"><p><strong>No supported SEO plugin was detected.</strong> Connector metadata and Schema still work, but provider-specific fields will not be synchronized.</p></div>
			<?php elseif ( ! $preferred_match ) : ?>
				<div class="notice notice-warning inline"><p><strong>This website profile expects <?php echo esc_html( isset( $provider_label[ $preferred_seo ] ) ? $provider_label[ $preferred_seo ] : $preferred_seo ); ?>.</strong> Select it as the SEO provider after confirming the plugin is active.</p></div>
			<?php endif; ?>

			<div class="g3ai-grid">
				<section class="g3ai-card">
					<h2>1. Connector settings</h2>
					<form method="post" action="options.php">
						<?php settings_fields( 'g3ai_settings_group' ); ?>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="g3ai-site-profile">Website profile</label></th>
								<td>
									<select id="g3ai-site-profile" name="<?php echo esc_attr( G3AI_OPTION ); ?>[site_profile]">
									<?php foreach ( $profile_options as $profile_id => $profile_item ) : ?>
											<option value="<?php echo esc_attr( $profile_id ); ?>" <?php selected( $settings['site_profile'], $profile_id ); ?>><?php echo esc_html( $profile_item['domain'] ); ?><?php echo 'custom' === $profile_id ? ' — Custom profile' : ''; ?></option>
										<?php endforeach; ?>
									</select>
									<p class="description">Built-in profiles remain protected. Choose Custom profile for any new domain; it is always bound to this site's real hostname.</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="g3ai-custom-profile-id">Custom Profile ID</label></th>
								<td>
									<input id="g3ai-custom-profile-id" class="regular-text" type="text" pattern="[a-z0-9][a-z0-9-]{1,39}" name="<?php echo esc_attr( G3AI_OPTION ); ?>[custom_profile_id]" value="<?php echo esc_attr( $settings['custom_profile_id'] ); ?>" placeholder="example-site">
									<p class="description">Required only for Custom profile. Use the exact same ID in Codex. Root class: <code>g3ai-site-PROFILE_ID</code>.</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="g3ai-custom-brand">Custom Brand</label></th>
								<td><input id="g3ai-custom-brand" class="regular-text" type="text" maxlength="80" name="<?php echo esc_attr( G3AI_OPTION ); ?>[custom_brand]" value="<?php echo esc_attr( $settings['custom_brand'] ); ?>" placeholder="Example Brand"></td>
							</tr>
							<tr>
								<th scope="row"><label for="g3ai-custom-watermark">Custom Watermark</label></th>
								<td>
									<input id="g3ai-custom-watermark" class="regular-text" type="text" maxlength="80" name="<?php echo esc_attr( G3AI_OPTION ); ?>[custom_watermark]" value="<?php echo esc_attr( $settings['custom_watermark'] ); ?>" placeholder="EXAMPLE.COM">
									<p class="description">Optional. Defaults to the uppercase current domain.</p>
								</td>
							</tr>
							<tr>
								<th scope="row">Connector</th>
								<td><label><input type="checkbox" name="<?php echo esc_attr( G3AI_OPTION ); ?>[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>> Enable draft intake</label></td>
							</tr>
							<tr>
								<th scope="row"><label for="g3ai-default-category">Default category</label></th>
								<td>
									<?php
									wp_dropdown_categories(
										array(
											'show_option_none' => 'No forced default',
											'option_none_value' => 0,
											'name'             => G3AI_OPTION . '[default_category]',
											'id'               => 'g3ai-default-category',
											'selected'         => absint( $settings['default_category'] ),
											'hide_empty'       => false,
										)
									);
									?>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="g3ai-seo-provider">SEO provider</label></th>
								<td>
									<select id="g3ai-seo-provider" name="<?php echo esc_attr( G3AI_OPTION ); ?>[seo_provider]">
										<option value="auto" <?php selected( $settings['seo_provider'], 'auto' ); ?>>Auto detect</option>
										<option value="seopress" <?php selected( $settings['seo_provider'], 'seopress' ); ?>>SEOPress</option>
										<option value="rank_math" <?php selected( $settings['seo_provider'], 'rank_math' ); ?>>Rank Math</option>
										<option value="yoast" <?php selected( $settings['seo_provider'], 'yoast' ); ?>>Yoast SEO</option>
										<option value="none" <?php selected( $settings['seo_provider'], 'none' ); ?>>Connector metadata only</option>
									</select>
									<p class="description">Detected now: <?php echo esc_html( $provider_name ); ?>.</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="g3ai-schema-mode">Schema handling</label></th>
								<td>
									<select id="g3ai-schema-mode" name="<?php echo esc_attr( G3AI_OPTION ); ?>[schema_mode]">
										<option value="connector_schema" <?php selected( $settings['schema_mode'], 'connector_schema' ); ?>>Connector outputs Schema; suppress supported SEO Schema on managed posts</option>
										<option value="seopress_schema" <?php selected( $settings['schema_mode'], 'seopress_schema' ); ?>>SEO plugin owns Schema output; connector stores the graph only</option>
										<option value="disabled" <?php selected( $settings['schema_mode'], 'disabled' ); ?>>Store Schema without output</option>
									</select>
									<p class="description">Connector mode is recommended for a complete same-domain @graph. It suppresses supported SEO-plugin Schema on that managed post to prevent duplicate entities.</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="g3ai-rate-limit">Requests per minute</label></th>
								<td><input id="g3ai-rate-limit" type="number" min="5" max="120" name="<?php echo esc_attr( G3AI_OPTION ); ?>[rate_limit]" value="<?php echo esc_attr( $settings['rate_limit'] ); ?>"></td>
							</tr>
							<tr>
								<th scope="row"><label for="g3ai-log-limit">Activity rows</label></th>
								<td><input id="g3ai-log-limit" type="number" min="10" max="100" name="<?php echo esc_attr( G3AI_OPTION ); ?>[log_limit]" value="<?php echo esc_attr( $settings['log_limit'] ); ?>"></td>
							</tr>
						</table>
						<?php submit_button( 'Save connector settings' ); ?>
					</form>
				</section>

				<section class="g3ai-card">
					<h2>2. Create the limited account</h2>
					<ol>
						<li>Go to <a href="<?php echo esc_url( admin_url( 'user-new.php' ) ); ?>">Users → Add New</a>.</li>
					<li>Create a unique user such as <code>ai_draft_writer</code>.</li>
					<li>Select the role <strong>AI Draft Writer</strong> (existing upgrades may still display G3AR4UB AI Writer).</li>
					<li>Open that user's profile and create an Application Password named <code>ChatGPT Work – <?php echo esc_html( isset( $profile['brand'] ) ? $profile['brand'] : 'WordPress' ); ?></code>.</li>
						<li>Store the generated value only in the connector's secret settings. Do not send it in ordinary chat or email.</li>
					</ol>
					<div class="g3ai-locks">
						<span>Draft only</span><span>No delete API</span><span>No publish API</span><span>Published posts locked</span>
					</div>
				</section>
			</div>

			<section class="g3ai-card">
				<h2>3. Connection endpoints</h2>
				<table class="widefat striped">
					<tbody>
						<tr><td><strong>Health check</strong></td><td><code><?php echo esc_html( $health_url ); ?></code></td></tr>
						<tr><td><strong>Create/update draft</strong></td><td><code><?php echo esc_html( $drafts_url ); ?></code></td></tr>
						<tr><td><strong>Upload cover</strong></td><td><code><?php echo esc_html( $media_url ); ?></code></td></tr>
					</tbody>
				</table>
				<details>
				<summary>Abridged draft payload (see examples/draft-payload.json for the complete contract)</summary>
					<pre>{
	  "external_id": "<?php echo esc_html( isset( $profile['id'] ) ? $profile['id'] : 'site' ); ?>-keyword-001",
	  "title": "Example Article",
	  "slug": "example-article",
	  "content_html": "&lt;article class=\"g3ai-article <?php echo esc_html( isset( $profile['root_class'] ) ? $profile['root_class'] : '' ); ?>\"&gt;...&lt;/article&gt;",
  "excerpt": "A concise editorial summary.",
  "categories": [12],
  "tags": [31, 42],
  "featured_media": 123,
  "seo": {
    "seo_title": "Example SEO Title",
    "meta_description": "Example meta description.",
    "focus_keyword": "example keyword",
    "social": {
      "facebook_title": "Example social title",
      "facebook_description": "Example social description.",
	      "facebook_image": "<?php echo esc_url( home_url( '/wp-content/uploads/example-cover.webp' ) ); ?>",
      "twitter_title": "Example social title",
      "twitter_description": "Example social description.",
	      "twitter_image": "<?php echo esc_url( home_url( '/wp-content/uploads/example-cover.webp' ) ); ?>"
    }
  },
  "schema": {
    "@context": "https://schema.org",
	    "@graph": [
	      {"@type":"Organization","@id":"<?php echo esc_url( home_url( '/#organization' ) ); ?>"},
	      {"@type":"WebSite","@id":"<?php echo esc_url( home_url( '/#website' ) ); ?>"},
	      {"@type":"WebPage","@id":"<?php echo esc_url( home_url( '/example-article/#webpage' ) ); ?>"},
	      {"@type":"BreadcrumbList","@id":"<?php echo esc_url( home_url( '/example-article/#breadcrumb' ) ); ?>"},
	      {"@type":"BlogPosting","@id":"<?php echo esc_url( home_url( '/example-article/#article' ) ); ?>"}
	    ]
  }
}</pre>
				</details>
			</section>

			<section class="g3ai-card">
				<h2>Recent connector activity</h2>
				<?php if ( empty( $logs ) ) : ?>
					<p>No connector activity has been recorded yet.</p>
				<?php else : ?>
					<table class="widefat striped">
						<thead><tr><th>Time</th><th>Event</th><th>Object</th><th>Details</th></tr></thead>
						<tbody>
						<?php foreach ( $logs as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['time'] ); ?></td>
								<td><code><?php echo esc_html( $row['event'] ); ?></code></td>
								<td><?php echo esc_html( $row['object_id'] ); ?></td>
								<td><?php echo esc_html( $row['message'] ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</section>
		</div>
		<style>
		.g3ai-admin{max-width:1180px}.g3ai-grid{display:grid;grid-template-columns:1.15fr .85fr;gap:18px}.g3ai-card{background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:22px;margin:18px 0;box-shadow:0 8px 24px rgba(16,24,40,.05)}.g3ai-card h2{margin-top:0}.g3ai-status{display:flex;justify-content:space-between;gap:16px;padding:14px 18px;margin:18px 0;border-radius:10px}.g3ai-status.is-ready{background:#edfff6;border:1px solid #7bd9aa;color:#075c36}.g3ai-status.needs-action{background:#fff8e5;border:1px solid #e6b84e;color:#6b4600}.g3ai-locks{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px}.g3ai-locks span{background:#0b1220;color:#72f4ff;border-radius:999px;padding:6px 10px;font-size:12px;font-weight:700}.g3ai-card pre{overflow:auto;background:#0b1220;color:#d9f8ff;padding:18px;border-radius:9px;line-height:1.55}.g3ai-card details{margin-top:16px}.g3ai-card summary{cursor:pointer;font-weight:700}@media(max-width:900px){.g3ai-grid{grid-template-columns:1fr}.g3ai-status{flex-direction:column}}
		</style>
		<?php
	}

	/**
	 * Adds one bounded activity entry. Credentials and request bodies are never
	 * logged.
	 *
	 * @param string $event     Event key.
	 * @param int    $object_id WordPress object ID.
	 * @param string $message   Short event description.
	 */
	public static function log( $event, $object_id, $message ) {
		$settings = G3AI_REST_Controller::get_settings();
		$limit    = max( 10, min( 100, absint( $settings['log_limit'] ) ) );
		$logs     = get_option( self::LOG_OPTION, array() );
		$logs     = is_array( $logs ) ? $logs : array();

		array_unshift(
			$logs,
			array(
				'time'      => current_time( 'mysql' ),
				'event'     => sanitize_key( $event ),
				'object_id' => absint( $object_id ),
				'message'   => wp_html_excerpt( wp_strip_all_tags( (string) $message ), 180, '…' ),
			)
		);

		update_option( self::LOG_OPTION, array_slice( $logs, 0, $limit ), false );
	}
}
