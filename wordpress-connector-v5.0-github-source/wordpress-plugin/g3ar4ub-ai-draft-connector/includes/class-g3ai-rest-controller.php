<?php
/**
 * Draft-only REST API controller.
 *
 * @package G3AR4UB_AI_Draft_Connector
 */

defined( 'ABSPATH' ) || exit;

class G3AI_REST_Controller {
	const NAMESPACE = 'g3ar4ub-ai/v1';

	/**
	 * Registers WordPress hooks.
	 */
	public function register_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers connector routes. Deliberately excludes publish and delete routes.
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/health',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'health' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/drafts',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save_draft' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/drafts/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_draft' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/media',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'upload_media' ),
				'permission_callback' => array( $this, 'media_permissions_check' ),
			)
		);
	}

	/**
	 * Requires an authenticated user with draft permissions and applies a small
	 * per-user rate limit.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return true|WP_Error
	 */
	public function permissions_check( $request ) {
		$settings = self::get_settings();
		if ( empty( $settings['enabled'] ) ) {
			return new WP_Error(
				'g3ai_disabled',
				'The AI Multi-Site Draft Connector is disabled.',
				array( 'status' => 503 )
			);
		}

		$profile = G3AI_Site_Profile::get( $settings['site_profile'] );
		if ( empty( $profile ) || G3AI_Site_Profile::detect() !== $profile['id'] ) {
			return new WP_Error(
				'g3ai_site_profile_mismatch',
				'The configured website profile does not match this WordPress home domain.',
				array( 'status' => 503 )
			);
		}

		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'g3ai_auth_required',
				'Authentication is required. Use a dedicated WordPress Application Password over HTTPS.',
				array( 'status' => 401 )
			);
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'g3ai_forbidden',
				'This user cannot create drafts.',
				array( 'status' => 403 )
			);
		}

		return $this->check_rate_limit( get_current_user_id(), (int) $settings['rate_limit'] );
	}

	/**
	 * Requires upload capability for the media route.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return true|WP_Error
	 */
	public function media_permissions_check( $request ) {
		$allowed = $this->permissions_check( $request );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		if ( ! current_user_can( 'upload_files' ) ) {
			return new WP_Error(
				'g3ai_upload_forbidden',
				'This user cannot upload media.',
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Returns connection information without exposing secrets.
	 *
	 * @return WP_REST_Response
	 */
	public function health() {
		$user     = wp_get_current_user();
		$settings = self::get_settings();
		$profile  = G3AI_Site_Profile::get( $settings['site_profile'] );
		$site     = G3AI_Site_Profile::public_site_data( $settings['site_profile'] );
		$seo      = G3AI_SEO_Adapter::status( $settings['seo_provider'] );

		return new WP_REST_Response(
			array(
				'connected'                       => true,
				'plugin'                          => 'AI Multi-Site Draft Connector',
				'version'                         => G3AI_VERSION,
				'mode'                            => 'draft_only',
				'site'                            => $site,
				'profile_valid'                   => ! empty( $profile ) && G3AI_Site_Profile::detect() === $profile['id'],
				'user'                            => array(
					'id'       => $user->ID,
					'username' => $user->user_login,
					'roles'    => array_values( $user->roles ),
				),
				'https'                           => 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME ),
				'application_passwords_supported' => function_exists( 'wp_is_application_passwords_supported' ) ? wp_is_application_passwords_supported() : false,
				'seo'                             => array_merge( $seo, array( 'schema_mode' => $settings['schema_mode'] ) ),
				'endpoints'                       => array(
					'drafts' => rest_url( self::NAMESPACE . '/drafts' ),
					'media'  => rest_url( self::NAMESPACE . '/media' ),
				),
				'guarantees'                      => array(
					'creates_drafts_only'       => true,
					'no_publish_endpoint'       => true,
					'no_delete_endpoint'        => true,
					'published_posts_protected' => true,
				),
			),
			200
		);
	}

	/**
	 * Creates a new managed draft or idempotently updates a managed draft.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_draft( $request ) {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = $request->get_params();
		}

		$title_input   = isset( $params['title'] ) && is_string( $params['title'] ) ? $params['title'] : '';
		$content_input = isset( $params['content_html'] ) && is_string( $params['content_html'] ) ? $params['content_html'] : '';
		$title         = sanitize_text_field( wp_unslash( $title_input ) );
		$content       = wp_unslash( $content_input );

		if ( '' === $title || '' === trim( $content ) ) {
			return new WP_Error(
				'g3ai_missing_content',
				'Both title and content_html are required.',
				array( 'status' => 400 )
			);
		}

		if ( strlen( $content ) > 3000000 ) {
			return new WP_Error(
				'g3ai_content_too_large',
				'content_html must be 3 MB or smaller.',
				array( 'status' => 413 )
			);
		}

		if ( 1 !== preg_match_all( '/<article\b/i', $content ) ) {
			return new WP_Error(
				'g3ai_invalid_article_wrapper',
				'content_html must contain exactly one article wrapper and no nested article elements.',
				array( 'status' => 400 )
			);
		}

		$slug_input  = isset( $params['slug'] ) && is_string( $params['slug'] ) ? $params['slug'] : $title;
		$external    = isset( $params['external_id'] ) && is_string( $params['external_id'] ) ? $params['external_id'] : '';
		$slug        = sanitize_title( $slug_input );
		$external_id = sanitize_key( $external );
		$post_id     = isset( $params['post_id'] ) && is_numeric( $params['post_id'] ) ? absint( $params['post_id'] ) : 0;
		$existing    = $this->find_existing_draft( $post_id, $external_id, $slug );

		if ( is_wp_error( $existing ) ) {
			return $existing;
		}

		$is_update = $existing instanceof WP_Post;
		if ( $is_update ) {
			$guard = $this->can_update_managed_draft( $existing );
			if ( is_wp_error( $guard ) ) {
				return $guard;
			}
		}

		$settings = self::get_settings();
		$profile  = G3AI_Site_Profile::get( $settings['site_profile'] );
		if ( empty( $profile ) ) {
			return new WP_Error( 'g3ai_invalid_site_profile', 'Select a matching built-in or custom website profile in connector settings.', array( 'status' => 503 ) );
		}

		$content = $this->sanitize_article_html( $content );
		if ( false === strpos( $content, 'g3ai-article' ) ) {
			$content = '<article class="g3ai-article ' . esc_attr( $profile['root_class'] ) . '">' . $content . '</article>';
		}
		$content = $this->enforce_profile_class( $content, $profile['root_class'] );

		$excerpt = isset( $params['excerpt'] ) && is_string( $params['excerpt'] ) ? $params['excerpt'] : '';
		$post_data = array(
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
			'post_excerpt' => sanitize_textarea_field( wp_unslash( $excerpt ) ),
			'post_status'  => 'draft',
			'post_type'    => 'post',
		);

		if ( $is_update ) {
			$post_data['ID'] = $existing->ID;
			$result          = wp_update_post( wp_slash( $post_data ), true );
		} else {
			$post_data['post_author'] = get_current_user_id();
			$result                   = wp_insert_post( wp_slash( $post_data ), true );
		}

		if ( is_wp_error( $result ) ) {
			G3AI_Admin::log( 'draft_error', 0, $result->get_error_message() );
			return $result;
		}

		$post_id = (int) $result;
		update_post_meta( $post_id, '_g3ai_managed', 1 );
		update_post_meta( $post_id, '_g3ai_layout', 'profile_glass' );
		update_post_meta( $post_id, '_g3ai_site_profile', $profile['id'] );
		if ( '' !== $external_id ) {
			update_post_meta( $post_id, '_g3ai_external_id', $external_id );
		}

		$term_result = $this->save_terms( $post_id, $params, $is_update );
		if ( is_wp_error( $term_result ) ) {
			return $term_result;
		}

		$featured_result = $this->save_featured_media( $post_id, $params );
		if ( is_wp_error( $featured_result ) ) {
			return $featured_result;
		}

		$this->save_seo_meta( $post_id, $params );
		$schema_result = $this->save_schema( $post_id, $params );
		if ( is_wp_error( $schema_result ) ) {
			return $schema_result;
		}

		clean_post_cache( $post_id );
		$action = $is_update ? 'updated' : 'created';
		G3AI_Admin::log( 'draft_' . $action, $post_id, $title );

		return new WP_REST_Response(
			array(
				'success'      => true,
				'site_profile' => $profile['id'],
				'domain'       => $profile['domain'],
				'action'       => $action,
				'post_id'      => $post_id,
				'status'       => 'draft',
				'slug'         => get_post_field( 'post_name', $post_id ),
				'edit_url'     => get_edit_post_link( $post_id, 'raw' ),
				'preview_url'  => get_preview_post_link( $post_id ),
				'review_state' => 'Needs human review before publishing',
			),
			$is_update ? 200 : 201
		);
	}

	/**
	 * Returns one managed draft for verification.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_draft( $request ) {
		$post = get_post( absint( $request['id'] ) );
		if ( ! $post || 'post' !== $post->post_type || ! get_post_meta( $post->ID, '_g3ai_managed', true ) ) {
			return new WP_Error( 'g3ai_not_found', 'Managed draft not found.', array( 'status' => 404 ) );
		}

		$guard = $this->can_update_managed_draft( $post );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}

		return new WP_REST_Response(
			array(
				'post_id'      => $post->ID,
				'site_profile' => get_post_meta( $post->ID, '_g3ai_site_profile', true ),
				'title'        => get_the_title( $post ),
				'slug'         => $post->post_name,
				'status'       => $post->post_status,
				'content_html' => $post->post_content,
				'excerpt'      => $post->post_excerpt,
				'featured_media'=> array(
					'id'  => get_post_thumbnail_id( $post->ID ),
					'url' => get_the_post_thumbnail_url( $post->ID, 'full' ),
				),
				'seo'          => G3AI_SEO_Adapter::read( $post->ID ),
				'schema'       => $this->read_schema( $post->ID ),
				'modified_gmt' => get_post_modified_time( DATE_ATOM, true, $post ),
				'edit_url'     => get_edit_post_link( $post->ID, 'raw' ),
				'preview_url'  => get_preview_post_link( $post->ID ),
			),
			200
		);
	}

	/**
	 * Uploads a raster cover image through a multipart request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function upload_media( $request ) {
		$files = $request->get_file_params();
		if ( empty( $files['file'] ) || ! is_array( $files['file'] ) ) {
			return new WP_Error( 'g3ai_missing_file', 'A multipart file field named file is required.', array( 'status' => 400 ) );
		}

		$file = $files['file'];
		if ( ! empty( $file['error'] ) ) {
			return new WP_Error( 'g3ai_upload_error', 'The upload could not be received.', array( 'status' => 400 ) );
		}

		if ( empty( $file['size'] ) || (int) $file['size'] > 102400 ) {
			return new WP_Error( 'g3ai_file_size', 'Cover images must be optimized to 100 KB or smaller before upload.', array( 'status' => 413 ) );
		}

		$checked = wp_check_filetype_and_ext( $file['tmp_name'], sanitize_file_name( $file['name'] ) );
		$allowed = array( 'image/webp' );
		if ( empty( $checked['type'] ) || ! in_array( $checked['type'], $allowed, true ) ) {
			return new WP_Error( 'g3ai_file_type', 'Only optimized WebP cover images are accepted.', array( 'status' => 415 ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$file['name'] = sanitize_file_name( $file['name'] );
		$caption_raw  = $request->get_param( 'caption' );
		$caption      = is_string( $caption_raw ) ? sanitize_text_field( $caption_raw ) : '';
		$attachment   = media_handle_sideload( $file, 0, $caption );

		if ( is_wp_error( $attachment ) ) {
			G3AI_Admin::log( 'media_error', 0, $attachment->get_error_message() );
			return $attachment;
		}

		$alt_raw  = $request->get_param( 'alt_text' );
		$alt_text = is_string( $alt_raw ) ? sanitize_text_field( $alt_raw ) : '';
		if ( '' !== $alt_text ) {
			update_post_meta( $attachment, '_wp_attachment_image_alt', $alt_text );
		}
		update_post_meta( $attachment, '_g3ai_managed_media', 1 );
		G3AI_Admin::log( 'media_uploaded', $attachment, get_the_title( $attachment ) );

		return new WP_REST_Response(
			array(
				'success'        => true,
				'attachment_id'  => $attachment,
				'url'            => wp_get_attachment_url( $attachment ),
				'mime_type'      => get_post_mime_type( $attachment ),
				'alt_text'       => get_post_meta( $attachment, '_wp_attachment_image_alt', true ),
				'use_in_draft_as' => array( 'featured_media' => $attachment ),
			),
			201
		);
	}

	/**
	 * Locates an existing draft by explicit post ID, external ID, or slug.
	 *
	 * @param int    $post_id     Post ID.
	 * @param string $external_id External queue identifier.
	 * @param string $slug        Post slug.
	 * @return WP_Post|null|WP_Error
	 */
	private function find_existing_draft( $post_id, $external_id, $slug ) {
		if ( $post_id ) {
			$post = get_post( $post_id );
			return $post instanceof WP_Post ? $post : new WP_Error( 'g3ai_post_not_found', 'The supplied post_id does not exist.', array( 'status' => 404 ) );
		}

		if ( '' !== $external_id ) {
			$posts = get_posts(
				array(
					'post_type'      => 'post',
					'post_status'    => array( 'draft', 'pending', 'publish', 'future', 'private', 'trash' ),
					'posts_per_page' => 1,
					'author'         => get_current_user_id(),
					'meta_key'       => '_g3ai_external_id',
					'meta_value'     => $external_id,
				)
			);
			if ( ! empty( $posts ) ) {
				return $posts[0];
			}
		}

		$post = get_page_by_path( $slug, OBJECT, 'post' );
		if ( $post instanceof WP_Post ) {
			if ( ! get_post_meta( $post->ID, '_g3ai_managed', true ) ) {
				return new WP_Error( 'g3ai_slug_conflict', 'The slug already belongs to a non-managed post.', array( 'status' => 409 ) );
			}
			return $post;
		}

		return null;
	}

	/**
	 * Prevents modification of published, scheduled, private, trashed, or other
	 * users' drafts.
	 *
	 * @param WP_Post $post Post object.
	 * @return true|WP_Error
	 */
	private function can_update_managed_draft( $post ) {
		if ( 'post' !== $post->post_type || ! get_post_meta( $post->ID, '_g3ai_managed', true ) ) {
			return new WP_Error( 'g3ai_not_managed', 'Only connector-managed drafts can be updated.', array( 'status' => 409 ) );
		}

		if ( ! in_array( $post->post_status, array( 'draft', 'pending' ), true ) ) {
			return new WP_Error( 'g3ai_post_locked', 'Published, scheduled, private, and trashed posts are protected.', array( 'status' => 409 ) );
		}

		if ( (int) $post->post_author !== get_current_user_id() && ! current_user_can( 'edit_others_posts' ) ) {
			return new WP_Error( 'g3ai_wrong_author', 'This draft belongs to another user.', array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Sanitizes article HTML while retaining the attributes used by the bundled
	 * interaction runtime. Style and script elements are intentionally rejected.
	 *
	 * @param string $html Article HTML.
	 * @return string
	 */
	private function sanitize_article_html( $html ) {
		$allowed = wp_kses_allowed_html( 'post' );
		$extra   = array(
			'class'                    => true,
			'id'                       => true,
			'role'                     => true,
			'aria-label'               => true,
			'aria-hidden'              => true,
			'aria-expanded'            => true,
			'aria-controls'            => true,
			'aria-selected'            => true,
			'data-g3ai-accordion'      => true,
			'data-g3ai-target'         => true,
			'data-g3ai-filter'         => true,
			'data-g3ai-filter-value'   => true,
			'data-g3ai-item'           => true,
			'data-g3ai-group'          => true,
			'data-g3ai-tabs'           => true,
			'data-g3ai-tab'            => true,
			'data-g3ai-panel'          => true,
		);

		foreach ( $allowed as $tag => $attributes ) {
			$allowed[ $tag ] = array_merge( $attributes, $extra );
		}

		$allowed['article'] = $extra;
		$allowed['section'] = $extra;
		$allowed['aside']   = $extra;
		$allowed['details'] = array_merge( $extra, array( 'open' => true ) );
		$allowed['summary'] = $extra;
		$allowed['time']    = array_merge( $extra, array( 'datetime' => true ) );
		$allowed['mark']    = $extra;
		$allowed['button']  = array_merge(
			$extra,
			array(
				'type'     => true,
				'disabled' => true,
			)
		);

		return wp_kses( $html, $allowed );
	}

	/**
	 * Replaces any site-profile class on the outer article with the configured one.
	 *
	 * @param string $html       Sanitized article HTML.
	 * @param string $root_class Required profile class.
	 * @return string
	 */
	private function enforce_profile_class( $html, $root_class ) {
		return preg_replace_callback(
			'/<article\b([^>]*)>/i',
			function ( $matches ) use ( $root_class ) {
				$attributes = $matches[1];
				if ( preg_match( '/\bclass\s*=\s*(["\'])(.*?)\1/i', $attributes, $class_match ) ) {
					$classes = preg_split( '/\s+/', trim( $class_match[2] ) );
					$classes = array_filter( $classes, function ( $class ) { return 0 !== strpos( $class, 'g3ai-site-' ); } );
					$classes[] = 'g3ai-article';
					$classes[] = $root_class;
					$classes = array_values( array_unique( array_filter( $classes ) ) );
					$replacement = 'class="' . esc_attr( implode( ' ', $classes ) ) . '"';
					$attributes  = preg_replace( '/\bclass\s*=\s*(["\'])(.*?)\1/i', $replacement, $attributes, 1 );
				} else {
					$attributes .= ' class="g3ai-article ' . esc_attr( $root_class ) . '"';
				}
				return '<article' . $attributes . '>';
			},
			$html,
			1
		);
	}

	/**
	 * Saves existing category and tag IDs/slugs. The connector never creates
	 * taxonomies and therefore does not require manage_categories.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $params  Request parameters.
	 * @param bool  $update  Whether this operation updates an existing draft.
	 * @return true|WP_Error
	 */
	private function save_terms( $post_id, $params, $update ) {
		$settings = self::get_settings();

		if ( array_key_exists( 'categories', $params ) ) {
			$categories = $this->resolve_term_ids( $params['categories'], 'category' );
			if ( is_wp_error( $categories ) ) {
				return $categories;
			}
			if ( empty( $categories ) && ! empty( $settings['default_category'] ) ) {
				$categories = array( absint( $settings['default_category'] ) );
			}
			wp_set_post_categories( $post_id, $categories, false );
		} elseif ( ! $update && ! empty( $settings['default_category'] ) ) {
			wp_set_post_categories( $post_id, array( absint( $settings['default_category'] ) ), false );
		}

		if ( array_key_exists( 'tags', $params ) ) {
			$tags = $this->resolve_term_ids( $params['tags'], 'post_tag' );
			if ( is_wp_error( $tags ) ) {
				return $tags;
			}
			wp_set_post_terms( $post_id, $tags, 'post_tag', false );
		}

		return true;
	}

	/**
	 * Resolves integer IDs or existing slugs into term IDs.
	 *
	 * @param mixed  $values   Term IDs or slugs.
	 * @param string $taxonomy Taxonomy name.
	 * @return array|WP_Error
	 */
	private function resolve_term_ids( $values, $taxonomy ) {
		if ( ! is_array( $values ) ) {
			return new WP_Error( 'g3ai_invalid_terms', $taxonomy . ' must be an array of existing IDs or slugs.', array( 'status' => 400 ) );
		}

		$resolved = array();
		foreach ( $values as $value ) {
			if ( is_numeric( $value ) ) {
				$term = get_term( absint( $value ), $taxonomy );
			} else {
				$term = get_term_by( 'slug', sanitize_title( $value ), $taxonomy );
			}

			if ( ! $term || is_wp_error( $term ) ) {
				return new WP_Error( 'g3ai_term_not_found', 'One or more supplied ' . $taxonomy . ' terms do not exist.', array( 'status' => 400 ) );
			}
			$resolved[] = (int) $term->term_id;
		}

		return array_values( array_unique( $resolved ) );
	}

	/**
	 * Assigns a previously uploaded image as featured media.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $params  Request parameters.
	 * @return true|WP_Error
	 */
	private function save_featured_media( $post_id, $params ) {
		if ( ! array_key_exists( 'featured_media', $params ) ) {
			return true;
		}

		$attachment_id = absint( $params['featured_media'] );
		if ( 0 === $attachment_id ) {
			delete_post_thumbnail( $post_id );
			return true;
		}

		if ( 'attachment' !== get_post_type( $attachment_id ) || 0 !== strpos( (string) get_post_mime_type( $attachment_id ), 'image/' ) ) {
			return new WP_Error( 'g3ai_invalid_featured_media', 'featured_media must be an existing image attachment ID.', array( 'status' => 400 ) );
		}

		set_post_thumbnail( $post_id, $attachment_id );
		return true;
	}

	/**
	 * Saves connector and active-provider SEO/social fields.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $params  Request parameters.
	 */
	private function save_seo_meta( $post_id, $params ) {
		$settings = self::get_settings();
		$seo      = isset( $params['seo'] ) && is_array( $params['seo'] ) ? $params['seo'] : array();
		G3AI_SEO_Adapter::sync( $post_id, $seo, $settings['seo_provider'] );
	}

	/**
	 * Validates and stores a JSON-LD object or graph.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $params  Request parameters.
	 * @return true|WP_Error
	 */
	private function save_schema( $post_id, $params ) {
		if ( ! array_key_exists( 'schema', $params ) ) {
			return true;
		}

		$schema = $params['schema'];
		if ( is_string( $schema ) ) {
			if ( strlen( $schema ) > 300000 ) {
				return new WP_Error( 'g3ai_schema_too_large', 'schema must be 300 KB or smaller.', array( 'status' => 413 ) );
			}
			$schema = json_decode( wp_unslash( $schema ), true );
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				return new WP_Error( 'g3ai_invalid_schema', 'schema is not valid JSON.', array( 'status' => 400 ) );
			}
		}

		if ( ! is_array( $schema ) || empty( $schema ) ) {
			return new WP_Error( 'g3ai_invalid_schema', 'schema must be a non-empty JSON object or graph.', array( 'status' => 400 ) );
		}

		$schema_validation = $this->validate_schema_graph( $schema );
		if ( is_wp_error( $schema_validation ) ) {
			return $schema_validation;
		}

		$encoded = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( false === $encoded ) {
			return new WP_Error( 'g3ai_invalid_schema', 'schema could not be encoded.', array( 'status' => 400 ) );
		}

		update_post_meta( $post_id, '_g3ai_schema_json', wp_slash( $encoded ) );
		return true;
	}

	/**
	 * Requires the core graph types and this site's canonical URL.
	 *
	 * @param array $schema Schema document.
	 * @return true|WP_Error
	 */
	private function validate_schema_graph( $schema ) {
		if ( 'https://schema.org' !== ( isset( $schema['@context'] ) ? $schema['@context'] : '' ) || empty( $schema['@graph'] ) || ! is_array( $schema['@graph'] ) ) {
			return new WP_Error( 'g3ai_incomplete_schema', 'schema must use https://schema.org and contain an @graph.', array( 'status' => 400 ) );
		}
		$types = array();
		foreach ( $schema['@graph'] as $node ) {
			if ( ! is_array( $node ) || ! isset( $node['@type'] ) ) {
				continue;
			}
			$node_types = is_array( $node['@type'] ) ? $node['@type'] : array( $node['@type'] );
			$types      = array_merge( $types, $node_types );
		}
		foreach ( array( 'Organization', 'WebSite', 'WebPage', 'BreadcrumbList' ) as $required ) {
			if ( ! in_array( $required, $types, true ) ) {
				return new WP_Error( 'g3ai_incomplete_schema', 'schema @graph is missing ' . $required . '.', array( 'status' => 400 ) );
			}
		}
		if ( empty( array_intersect( array( 'Article', 'BlogPosting', 'NewsArticle' ), $types ) ) ) {
			return new WP_Error( 'g3ai_incomplete_schema', 'schema @graph must include Article, BlogPosting, or NewsArticle.', array( 'status' => 400 ) );
		}
		$encoded = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES );
		if ( false === $encoded || false === strpos( $encoded, untrailingslashit( home_url( '/' ) ) ) ) {
			return new WP_Error( 'g3ai_wrong_schema_domain', 'schema must contain this WordPress site canonical URL.', array( 'status' => 400 ) );
		}
		return true;
	}

	/**
	 * Reads a stored Schema document for draft verification.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	private function read_schema( $post_id ) {
		$json = get_post_meta( $post_id, '_g3ai_schema_json', true );
		$data = is_string( $json ) ? json_decode( $json, true ) : array();
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Applies a per-user, per-minute request limit.
	 *
	 * @param int $user_id User ID.
	 * @param int $limit   Requests per minute.
	 * @return true|WP_Error
	 */
	private function check_rate_limit( $user_id, $limit ) {
		$limit = max( 5, min( 120, $limit ) );
		$key   = 'g3ai_rate_' . absint( $user_id ) . '_' . gmdate( 'YmdHi' );
		$count = (int) get_transient( $key );

		if ( $count >= $limit ) {
			return new WP_Error( 'g3ai_rate_limited', 'Too many connector requests. Try again in one minute.', array( 'status' => 429 ) );
		}

		set_transient( $key, $count + 1, 90 );
		return true;
	}

	/**
	 * Returns settings merged with safe defaults.
	 *
	 * @return array
	 */
	public static function get_settings() {
		return wp_parse_args(
			get_option( G3AI_OPTION, array() ),
			array(
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
			)
		);
	}

	/**
	 * Detects SEOPress Free without requiring a specific release.
	 *
	 * @return bool
	 */
	public static function seopress_is_active() {
		return G3AI_SEO_Adapter::is_active( 'seopress' );
	}

	/**
	 * Detects SEOPress PRO for diagnostics only; SEO meta sync works with Free.
	 *
	 * @return bool
	 */
	public static function seopress_pro_is_active() {
		return defined( 'SEOPRESS_PRO_VERSION' ) || function_exists( 'seopress_pro_get_service' ) || class_exists( 'SEOPressPro\\Core\\Kernel' );
	}
}
