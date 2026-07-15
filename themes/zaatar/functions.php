<?php
/**
 * Zaatar functions and definitions
 *
 * @package Zaatar
 */
/* Theme version, used to cache-bust enqueued assets. */
define( 'ZAATAR_VERSION', wp_get_theme()->get( 'Version' ) );

/* Shortcodes */
require get_template_directory() . '/inc/shortcodes.php';

/* SEO / GEO meta output (description, Open Graph, Twitter Cards) */
require get_template_directory() . '/inc/seo.php';

if ( ! function_exists( 'allium_setup' ) ) :
/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function allium_setup() {

	/*
	 * Make theme available for translation.
	 * Translations can be filed in the /languages/ directory.
	 * If you're building a theme based on Zaatar, use a find and replace
	 * to change 'zaatar' to the name of your theme in all the template files
	 */
	load_theme_textdomain( 'zaatar', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
	 * Let WordPress manage the document title.
	 * By adding theme support, we declare that this theme does not use a
	 * hard-coded <title> tag in the document head, and expect WordPress to
	 * provide it for us.
	 */
	add_theme_support( 'title-tag' );

	/*
	 * Enable support for custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support( 'custom-logo', array(
		'height'      => 400,
		'width'       => 580,
		'flex-height' => true,
		'flex-width'  => true,
		'header-text' => array( 'site-title', 'site-description' ),
	) );

	/*
	 * Enable support for Post Thumbnails on posts and pages.
	 *
	 * @link http://codex.wordpress.org/Function_Reference/add_theme_support#Post_Thumbnails
	 */
	add_theme_support( 'post-thumbnails' );

	// Theme Image Sizes
	add_image_size( 'allium-featured', 700, 525, true );
	add_image_size( 'allium-featured-single', 769, 0, true );

	// This theme uses wp_nav_menu() in four locations.
	register_nav_menus( array (
		'header-menu' => esc_html__( 'Header Menu', 'zaatar' ),
		'top-menu'    => esc_html__( 'Top Menu', 'zaatar' ),
	) );

	// This theme styles the visual editor to resemble the theme style.
	add_editor_style( array ( 'css/editor-style.css', 'css/fonts-local.css' ) );

	/*
	* Switch default core markup for search form, comment form, and comments
	* to output valid HTML5.
	*/
	add_theme_support( 'html5',
		array(
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'search-form',
			'script',
			'style',
		)
	);

	// Setup the WordPress core custom background feature.
	add_theme_support( 'custom-background', apply_filters( 'allium_custom_background_args', array (
		'default-color' => 'f9f9f9',
		'default-image' => '',
	) ) );

	// Quote post format, used by the quotes templates.
	add_theme_support( 'post-formats', array( 'quote' ) );

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	// Make embeds (YouTube, etc.) scale responsively.
	add_theme_support( 'responsive-embeds' );

	/*
	 * Add support for full and wide align images.
	 * @see https://wordpress.org/gutenberg/handbook/extensibility/theme-support/#wide-alignment
	 */
	add_theme_support( 'align-wide' );

}
endif; // allium_setup
add_action( 'after_setup_theme', 'allium_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function allium_content_width() {
	// This variable is intended to be overruled from themes.
	// Open WPCS issue: {@link https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/issues/1043}.
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$GLOBALS['content_width'] = apply_filters( 'allium_content_width', 769 );
}
add_action( 'after_setup_theme', 'allium_content_width', 0 );

/**
 * Register widget area.
 *
 * @link http://codex.wordpress.org/Function_Reference/register_sidebar
 */
function allium_widgets_init() {

	// Widget Areas
	register_sidebar( array(
		'name'          => esc_html__( 'Main Sidebar', 'zaatar' ),
		'id'            => 'sidebar-1',
		'description'   => esc_html__( 'Add widgets here to appear in your sidebar.', 'zaatar' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );

}
add_action( 'widgets_init', 'allium_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function allium_scripts() {

	/**
	 * Enqueue JS files
	 *
	 * All theme scripts load deferred: WordPress (6.3+) keeps dependency
	 * order for deferred scripts, unlike the old blanket async filter.
	 * enquire.js and fitvids.js were removed in 1.3: media queries use
	 * native window.matchMedia in custom.js, video embeds are sized in CSS.
	 * jQuery, superfish.js and hover-intent.js were removed in 1.4: the
	 * desktop dropdown menu is pure CSS (:hover / :focus-within), all
	 * remaining theme scripts are vanilla JS.
	 */

	// Comment Reply
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	// Keyboard image navigation support
	if ( is_singular() && wp_attachment_is_image() ) {
		wp_enqueue_script( 'allium-keyboard-image-navigation', get_template_directory_uri() . '/js/keyboard-image-navigation.js', array(), '20260712', array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}

	// Custom Script
	wp_enqueue_script( 'allium-custom', get_template_directory_uri() . '/js/custom.js', array(), ZAATAR_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_localize_script(
		'allium-custom',
		'alliumL10n',
		array(
			'toggleSubmenu' => esc_attr__( 'Toggle submenu', 'zaatar' ),
		)
	);

	/**
	 * Enqueue CSS files
	 */

	// Bootstrap Custom
	wp_enqueue_style( 'allium-bootstrap-custom', get_template_directory_uri() . '/css/bootstrap-custom.css', array(), ZAATAR_VERSION );

	// Font Awesome 5
	// For Reviewer and Developers: Unique Handle `font-awesome-5` is required to avoid the conflict with Font Awesome 4+ library.
	// Font Awesome 5+ library is completely rewritten and is different from Font Awesome 4+ library.
	wp_enqueue_style( 'font-awesome-5', get_template_directory_uri() . '/css/fontawesome-all.css', array(), '5.6.3' );

	// Fonts: self-hosted since 1.4.4 (step 24) — Nunito Sans + Roboto
	// variable woff2 in /webfonts, no more fonts.googleapis.com request.
	wp_enqueue_style( 'allium-fonts', get_template_directory_uri() . '/css/fonts-local.css', array(), ZAATAR_VERSION );

	// Theme Stylesheet
	wp_enqueue_style( 'allium-style', get_stylesheet_uri(), array(), ZAATAR_VERSION );

}
add_action( 'wp_enqueue_scripts', 'allium_scripts' );

/**
 * Dark mode bootstrap: stamp data-theme="dark|light" on <html> before
 * first paint (no flash of the wrong scheme). The stored choice
 * (localStorage "zaatar-theme") wins, otherwise the OS preference.
 * Must stay inline at priority 0, ahead of the enqueued stylesheets.
 */
function allium_theme_scheme_bootstrap() {
	?>
	<script>
	(function () {
		var stored = null;
		try { stored = localStorage.getItem('zaatar-theme'); } catch (e) {}
		var theme = (stored === 'dark' || stored === 'light') ? stored :
			(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
		document.documentElement.setAttribute('data-theme', theme);
	})();
	</script>
	<?php
}
add_action( 'wp_head', 'allium_theme_scheme_bootstrap', 0 );

/**
 * No-JS fallback: without JavaScript the mobile <dialog> cannot open and
 * the dark-mode toggle does nothing, so show the regular header menu at
 * every viewport width and hide both buttons (the site stays light).
 */
function allium_no_js_menu_fallback() {
	echo '<noscript><style>.site-header-menu{display:block}.toggle-menu-wrapper{display:none}.theme-toggle{display:none}</style></noscript>' . "\n";
}
add_action( 'wp_head', 'allium_no_js_menu_fallback' );

/**
 * Enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer/customizer-core.php';
require get_template_directory() . '/inc/customizer/customizer.php';

/**
 * Admin list-table columns for posts, pages and custom post types
 * (productforsale, bf_quotes_manager, portfolio, showcase, ...).
 */
require get_template_directory() . '/inc/admin-columns.php';


/*
 * SITE-SPECIFIC CONTENT FOR BRUNO FLAVEN WEBSITE
 */

/*****************************************************************************************/
/*
	// GET THE DATE IN FRENCH
*/
/*****************************************************************************************/
// Voir http://webmaster.multimania.fr/tips/989424764/, check header.php for usage
function MyFrenchDate () {
		$jour["Monday"] = "Lundi";
		$jour["Tuesday"] = "Mardi";
		$jour["Wednesday"] = "Mercredi";
		$jour["Thursday"] = "Jeudi";
		$jour["Friday"] = "Vendredi";
		$jour["Saturday"] = "Samedi";
		$jour["Sunday"] = "Dimanche";

		function getJour($day) {
		return $jour[$day];
		}

		$mois["January"] = "Janvier";
		$mois["Febrary"] = "Février";
		$mois["March"] = "Mars";
		$mois["April"] = "Avril";
		$mois["May"] = "Mai";
		$mois["June"] = "Juin";
		$mois["July"] = "Juillet";
		$mois["August"] = "Août";
		$mois["September"] = "Septembre";
		$mois["October"] = "Octobre";
		$mois["November"] = "Novembre";
		$mois["December"] = "Décembre";

		function getMois($month){
		return $mois[$month];
		}

		$month = date(F);
		$day = date(l);

		getJour($day);
		getMois($month);


		print "$jour[$day] ";
		print date(d)." ";
		print "$mois[$month] ";
		print date(Y);
	}//EOF
/*****************************************************************************************/
/*
	// GET THE DATE IN FRENCH
*/
/*****************************************************************************************/

/*****************************************************************************************/
/*
	// GET THE FILENAME
*/
/*****************************************************************************************/


function flaven_get_filename () {

	echo ('<!-- GET THE TPL FILE => '._PAGE_TYPE_.' -->');
}

/*****************************************************************************************/
/*
	// GET THE FILENAME
*/
/*****************************************************************************************/

/*****************************************************************************************/
/*
	// ENABLE Link Manager
*/
/*****************************************************************************************/

/*
 * See http://core.trac.wordpress.org/ticket/21307
 */

add_filter( 'pre_option_link_manager_enabled', '__return_true' );

/*****************************************************************************************/
/*
// ENABLE Link Manager
*/
/*****************************************************************************************/

/*****************************************************************************************/
/*
	// DEFINE a default post thumbnail
*/
/*****************************************************************************************/

// http://justintadlock.com/archives/2012/07/05/how-to-define-a-default-post-thumbnail

add_filter( 'post_thumbnail_html', 'bf_my_post_thumbnail_html' );

function bf_my_post_thumbnail_html( $html ) {

	/* images/default-thumbnail.png never shipped; default-og.png (added in
	   step 21 as the og:image fallback) doubles as the placeholder. Cards
	   crop it via aspect-ratio + object-fit, so the 1200x630 ratio is fine. */
	if ( empty( $html ) )
		$html = '<img src="' . trailingslashit( get_template_directory_uri() ) . 'images/default-og.png' . '" alt="" width="1200" height="630" />';

	return $html;
}

/*****************************************************************************************/
/*
	// // DEFINE a default post thumbnail
*/
/*****************************************************************************************/


/*
 * SiteSpeed: the old add_async_attribute() filter (async on every script,
 * jQuery included) and remove_query_strings() (?ver stripping) were removed
 * in 1.3. Scripts now use the core defer loading strategy, and ?ver params
 * are kept for cache busting.
 */


/* Hide Jetpack's "feedback" post type from wp-admin
 * (http://flaven.fr/wp-admin/edit.php?post_type=feedback).
 * Renamed from delete_post_type() in step 7: the old name was generic
 * enough to collide with core/plugin functions. Same hook, same behavior.
 */
function bf_unregister_feedback_post_type(){
  unregister_post_type( 'feedback');
}
add_action('init','bf_unregister_feedback_post_type', 100);


/*
If you want your theme to be backward compatible with older versions of WordPress, you will need to add a snippet in your functions.php file.
 */

if ( ! function_exists( 'wp_body_open' ) ) {
    function wp_body_open() {
        do_action( 'wp_body_open' );
    }
}

/* -----------  // For IA ----------- */

//  send to a plugin

/* -----------  // For IA ----------- */


// add_to_functions_bm_display_enriched_breadcrumb.php

/* -----------  // Enrich breadcrumb for tag and category.
				See plugin breadcrumb-migration ----------- */
 /**
   * Version: 1.3.0
   * Plugin: breadcrumb-migration
   * Function: bm_display_enriched_breadcrumb
   * Display enriched breadcrumb for tag and category archive pages.
   *
   * Reads proposed_breadcrumb from wp_breadcrumb_proposals (validation_state
   * must be 'approved' or 'published'). Falls back to native WP parent chain
   * when no enriched data exists.
   *
   * v1.1.0: graceful no-table guard in bm_fetch_breadcrumb_crumbs().
   *         Intermediate crumbs now link to categories for post_tag too.
   * v1.2.0: intermediate crumb resolution falls back to WP page by slug
   *         so "Tags" links to /tags/ (custom page) when not a category.
   * v1.3.0: category lookup now tries slug after name to fix silent mismatches
   *         (accents, entity encoding, case). Page fallback unchanged.
   *
   * Usage in template:
   *   <?php bm_display_enriched_breadcrumb(); ?>

   */
  function bm_display_enriched_breadcrumb(): void {
      if ( ! is_tag() && ! is_category() ) {
          return;
      }

      $term     = get_queried_object();
      $taxonomy = $term->taxonomy; // 'post_tag' or 'category'
      $crumbs   = bm_fetch_breadcrumb_crumbs( (int) $term->term_id, $taxonomy );

      if ( empty( $crumbs ) ) {
          $crumbs = bm_native_breadcrumb_crumbs( $term, $taxonomy );
      }

      bm_breadcrumb_output( $crumbs, $taxonomy );
  }

  /**
   * Query wp_breadcrumb_proposals for the enriched crumb array.
   *
   * @return array  e.g. ["Home","Tags","17 octobre 1961"] or []
   */
  function bm_fetch_breadcrumb_crumbs( int $wp_term_id, string $taxonomy ): array
  {
      global $wpdb;
      $pfx = $wpdb->prefix;

      // Guard: if plugin tables don't exist, skip query entirely.
      $table_exists = $wpdb->get_var( $wpdb->prepare(
          'SELECT COUNT(1) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s',
          DB_NAME,
          $pfx . 'breadcrumb_proposals'
      ) );
      if ( ! $table_exists ) {
          return [];
      }

      $json = $wpdb->get_var( $wpdb->prepare(
          "SELECT p.proposed_breadcrumb
             FROM {$pfx}breadcrumb_proposals p
             JOIN {$pfx}breadcrumb_terms t ON t.id = p.term_id
            WHERE t.wp_term_id = %d
              AND t.taxonomy   = %s
              AND p.validation_state IN ('approved','published')
            LIMIT 1",
          $wp_term_id,
          $taxonomy
      ) );

      if ( ! $json ) {
          return [];
      }
      $arr = json_decode( $json, true );
      return is_array( $arr ) ? $arr : [];
  }

  /**
   * Build crumbs from native WP data when no proposal is found.
   * Category: walks parent chain. Tag: ["Home","Tags","Name"].
   */
  function bm_native_breadcrumb_crumbs( WP_Term $term, string $taxonomy ): array {
      if ( $taxonomy === 'post_tag' ) {
          return [ 'Home', 'Tags', $term->name ];
      }

      // Walk category ancestors
      $chain = [ $term->name ];
      $parent_id = (int) $term->parent;
      $seen      = [];

      while ( $parent_id && ! isset( $seen[ $parent_id ] ) ) {
          $seen[ $parent_id ] = true;
          $parent = get_term( $parent_id, 'category' );
          if ( ! $parent || is_wp_error( $parent ) ) {
              break;
          }
          array_unshift( $chain, $parent->name );
          $parent_id = (int) $parent->parent;
      }

      return array_merge( [ 'Home' ], $chain );
  }

  /**
   * Render the breadcrumb trail.
   * Home → link. Last crumb → current span. Middle → linked if resolvable.
   */
  function bm_breadcrumb_output( array $crumbs, string $taxonomy ): void {
      echo '<div class="entry-breadcrumb"><nav class="bf-breadcrumbs"
  aria-label="Breadcrumbs">';
      echo '<span class="breadcrumb-icon"><i class="fas fa-map-marker-alt"
  style="color: #4F1993;"></i></span>';

      $last = count( $crumbs ) - 1;

      foreach ( $crumbs as $i => $label ) {
          if ( $i > 0 ) {
              echo '<span class="breadcrumb-separator">›</span>';
          }

          if ( $i === $last ) {
              echo '<span class="breadcrumb-current">' . esc_html( $label ) .
  '</span>';
          } elseif ( $i === 0 ) {

              echo '<a href="' . esc_url( home_url( '/' ) ) . '" class="breadcrumb-link">' . esc_html( $label ) . '</a>';


          } else {
              // Resolve intermediate crumb — three attempts in order:
              //   1. WP category by name  (exact, case-insensitive)
              //   2. WP category by slug  (handles accents/entity mismatch)
              //   3. WP page by slug      ("Tags" → /tags/ custom page)
              $url = '';
              $cat = get_term_by( 'name', $label, 'category' );
              if ( ! $cat || is_wp_error( $cat ) ) {
                  $cat = get_term_by( 'slug', sanitize_title( $label ), 'category' );
              }
              if ( $cat && ! is_wp_error( $cat ) ) {
                  $url = get_category_link( $cat->term_id );
              } else {
                  $page = get_page_by_path( sanitize_title( $label ) );
                  if ( $page ) {
                      $url = get_permalink( $page->ID );
                  }
              }
              if ( $url ) {


                  echo '<a href="' . esc_url( $url ) . '"  class="breadcrumb-link">' . esc_html( $label ) . '</a>';


              } else {
                  echo '<span class="breadcrumb-link">' . esc_html( $label ) .
  '</span>';
              }
          }
      }

      echo '</nav></div>';
  }

/* -----------  // Enrich breadcrumb for tag and category.
				See plugin breadcrumb-migration ----------- */
