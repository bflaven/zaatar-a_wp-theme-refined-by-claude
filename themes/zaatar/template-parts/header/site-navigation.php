<?php
/**
 * The template for displaying site navigation
 *
 * @package Zaatar
 */
?>

<nav id="site-navigation" class="main-navigation" role="navigation" aria-label="<?php esc_attr_e( 'Primary Menu', 'zaatar' ); ?>">
	<div class="main-navigation-inside">

		<div class="toggle-menu-wrapper">
			<button type="button" class="toggle-menu-control" aria-expanded="false" aria-controls="header-menu-responsive">
				<span class="toggle-menu-label"><?php esc_html_e( 'Menu', 'zaatar' ); ?></span>
			</button>
		</div>

		<?php
		// Header Menu
		wp_nav_menu( apply_filters( 'allium_header_menu_args', array(
			'container'       => 'div',
			'container_class' => 'site-header-menu',
			'theme_location'  => 'header-menu',
			'menu_class'      => 'header-menu sf-menu',
			'menu_id'         => 'menu-1',
			'depth'           => 3,
		) ) );
		?>

		<button type="button" class="theme-toggle" aria-pressed="false" aria-label="<?php esc_attr_e( 'Toggle dark mode', 'zaatar' ); ?>">
			<span class="theme-toggle-icon-moon fas fa-moon" aria-hidden="true"></span>
			<span class="theme-toggle-icon-sun fas fa-sun" aria-hidden="true"></span>
		</button>

	</div><!-- .main-navigation-inside -->
</nav><!-- .main-navigation -->

<dialog id="header-menu-responsive" class="site-header-menu-responsive" aria-label="<?php esc_attr_e( 'Menu', 'zaatar' ); ?>">
	<div class="header-menu-responsive-inside">
		<button type="button" class="header-menu-responsive-close" aria-label="<?php esc_attr_e( 'Close menu', 'zaatar' ); ?>">&times;</button>
		<?php
		// Responsive Header Menu (server-rendered; no JS clone)
		wp_nav_menu( apply_filters( 'allium_header_menu_responsive_args', array(
			'container'       => false,
			'theme_location'  => 'header-menu',
			'menu_class'      => 'header-menu-responsive',
			'menu_id'         => 'menu-responsive',
			'depth'           => 3,
		) ) );
		?>
	</div><!-- .header-menu-responsive-inside -->
</dialog><!-- .site-header-menu-responsive -->
