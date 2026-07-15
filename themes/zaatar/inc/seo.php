<?php
/**
 * SEO / GEO meta output: meta description, Open Graph and Twitter Card tags.
 *
 * Structured data (JSON-LD Article + BreadcrumbList) is provided by the
 * json_ld plugin on this site, so this file deliberately does NOT emit
 * any JSON-LD to avoid duplicate markup.
 *
 * All output is skipped when a dedicated SEO plugin is active.
 *
 * @package Zaatar
 */

/**
 * Jetpack also emits Open Graph + Twitter Card tags, which duplicated
 * everything this file outputs (seen live on prod, 2026-07-14). The
 * theme owns those tags — switch Jetpack's off.
 */
add_filter( 'jetpack_enable_open_graph', '__return_false' );
add_filter( 'jetpack_disable_twitter_cards', '__return_true' );

/**
 * Whether a dedicated SEO plugin already handles meta output.
 *
 * @return bool
 */
function zaatar_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' )        // Yoast SEO
		|| class_exists( 'RankMath' )        // Rank Math
		|| defined( 'AIOSEO_VERSION' )       // All in One SEO
		|| defined( 'SEOPRESS_VERSION' );    // SEOPress
}

/**
 * Build a meta description for the current view.
 *
 * @return string Unescaped description, empty string when none applies.
 */
function zaatar_get_meta_description() {
	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post && ! post_password_required( $post ) ) {
			// AI/hand-written description (bf-ai-meta-import plugin or a
			// manual custom field) wins over the excerpt fallback.
			$custom = get_post_meta( $post->ID, 'bf_ai_meta_description', true );
			if ( '' !== (string) $custom ) {
				return (string) $custom;
			}
			$text = has_excerpt( $post ) ? $post->post_excerpt : $post->post_content;
			$text = wp_strip_all_tags( strip_shortcodes( $text ) );
			return wp_trim_words( $text, 30, '…' );
		}
		return '';
	}

	if ( is_front_page() || is_home() ) {
		return get_bloginfo( 'description', 'display' );
	}

	if ( is_category() || is_tag() || is_tax() ) {
		$description = term_description();
		if ( $description ) {
			return wp_trim_words( wp_strip_all_tags( $description ), 30, '…' );
		}
		/* translators: %s: archive title. */
		return sprintf( __( 'Archive of %s.', 'zaatar' ), single_term_title( '', false ) );
	}

	if ( is_author() ) {
		return get_the_author_meta( 'description', get_queried_object_id() );
	}

	return '';
}

/**
 * Print the meta description tag.
 */
function zaatar_meta_description() {
	if ( zaatar_seo_plugin_active() ) {
		return;
	}
	$description = zaatar_get_meta_description();
	if ( $description ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
	}
}
add_action( 'wp_head', 'zaatar_meta_description', 2 );

/**
 * Sharing fallback image (og:image / twitter:image) used when the view
 * has no featured image: posts without thumbnail, home, archives.
 * Ships as images/default-og.png (1200x630) — replace the file to
 * change the card, keep the name.
 *
 * @return array { url, width, height } or empty array when the file is absent.
 */
function zaatar_default_og_image() {
	if ( ! file_exists( get_template_directory() . '/images/default-og.png' ) ) {
		return array();
	}
	return array(
		'url'    => get_template_directory_uri() . '/images/default-og.png',
		'width'  => 1200,
		'height' => 630,
	);
}

/**
 * Canonical URL for non-singular views (core only emits rel=canonical
 * on singular). Pagination-aware; search, 404 and date archives are
 * deliberately skipped.
 */
function zaatar_canonical() {
	if ( zaatar_seo_plugin_active() || is_singular() || is_search() || is_404() || is_date() ) {
		return;
	}

	$url = '';
	if ( is_front_page() ) {
		$url = home_url( '/' );
	} elseif ( is_home() ) {
		$page_for_posts = (int) get_option( 'page_for_posts' );
		$url            = $page_for_posts ? get_permalink( $page_for_posts ) : home_url( '/' );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$url = get_term_link( get_queried_object() );
	} elseif ( is_author() ) {
		$url = get_author_posts_url( get_queried_object_id() );
	} elseif ( is_post_type_archive() ) {
		$post_type = get_query_var( 'post_type' );
		$url       = get_post_type_archive_link( is_array( $post_type ) ? reset( $post_type ) : $post_type );
	}

	if ( ! $url || is_wp_error( $url ) ) {
		return;
	}

	$paged = (int) get_query_var( 'paged' );
	if ( $paged >= 2 ) {
		$url = trailingslashit( $url ) . user_trailingslashit( 'page/' . $paged, 'paged' );
	}

	printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $url ) );
}
add_action( 'wp_head', 'zaatar_canonical', 1 );

/**
 * Print Open Graph and Twitter Card tags.
 */
function zaatar_open_graph() {
	if ( zaatar_seo_plugin_active() ) {
		return;
	}

	$tags = array(
		'og:site_name' => get_bloginfo( 'name', 'display' ),
		'og:locale'    => get_locale(),
	);

	if ( is_singular() ) {
		$post                    = get_queried_object();
		$tags['og:type']         = 'article';
		$tags['og:title']        = get_the_title( $post );
		$tags['og:url']          = get_permalink( $post );
		$tags['og:description']  = zaatar_get_meta_description();

		// Shorter social-card title when one was set (custom field
		// bf_ai_og_title); the document <title> and H1 stay untouched.
		$og_title = get_post_meta( $post->ID, 'bf_ai_og_title', true );
		if ( '' !== (string) $og_title ) {
			$tags['og:title'] = (string) $og_title;
		}

		$tags['article:published_time'] = get_the_date( DATE_W3C, $post );
		$tags['article:modified_time']  = get_the_modified_date( DATE_W3C, $post );

		if ( has_post_thumbnail( $post ) ) {
			$thumbnail_id = get_post_thumbnail_id( $post );
			$image        = wp_get_attachment_image_src( $thumbnail_id, 'large' );
			if ( $image ) {
				$tags['og:image']        = $image[0];
				$tags['og:image:width']  = $image[1];
				$tags['og:image:height'] = $image[2];

				$alt = get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );
				if ( $alt ) {
					$tags['og:image:alt'] = $alt;
				}
			}
		}
	} else {
		$tags['og:type']        = 'website';
		$tags['og:title']       = wp_get_document_title();
		$tags['og:url']         = home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? '' ) );
		$tags['og:description'] = zaatar_get_meta_description();
	}

	if ( ! isset( $tags['og:image'] ) ) {
		$fallback = zaatar_default_og_image();
		if ( $fallback ) {
			$tags['og:image']        = $fallback['url'];
			$tags['og:image:width']  = $fallback['width'];
			$tags['og:image:height'] = $fallback['height'];
			$tags['og:image:alt']    = get_bloginfo( 'name', 'display' );
		}
	}

	$tags['twitter:card']  = isset( $tags['og:image'] ) ? 'summary_large_image' : 'summary';
	$tags['twitter:title'] = $tags['og:title'];
	if ( ! empty( $tags['og:description'] ) ) {
		$tags['twitter:description'] = $tags['og:description'];
	}
	if ( isset( $tags['og:image'] ) ) {
		$tags['twitter:image'] = $tags['og:image'];
	}

	foreach ( $tags as $property => $content ) {
		if ( '' === (string) $content ) {
			continue;
		}
		$attribute = ( 0 === strpos( $property, 'twitter:' ) ) ? 'name' : 'property';
		printf(
			'<meta %s="%s" content="%s">' . "\n",
			esc_attr( $attribute ),
			esc_attr( $property ),
			esc_attr( $content )
		);
	}
}
add_action( 'wp_head', 'zaatar_open_graph', 5 );
