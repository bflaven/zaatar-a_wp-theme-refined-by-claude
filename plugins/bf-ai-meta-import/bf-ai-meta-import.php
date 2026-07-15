<?php
/**
 * Plugin Name: BF AI Meta Import
 * Description: Imports and bulk-edits AI-generated meta descriptions (bf_ai_meta_description) and social titles (bf_ai_og_title). Rendering is done by the Zaatar theme (inc/seo.php); this plugin only manages the data, so it can be deactivated once the fields are in place.
 * Version: 1.3
 * Author: Bruno Flaven
 * License: GPL v2 or later
 * Text Domain: bf-ai-meta-import
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BFAMI_DESCRIPTION_KEY', 'bf_ai_meta_description' );
define( 'BFAMI_OG_TITLE_KEY', 'bf_ai_og_title' );
define( 'BFAMI_JSON_FILE', plugin_dir_path( __FILE__ ) . 'meta_descriptions.json' );
define( 'BFAMI_DESCRIPTION_SOFT_MAX', 160 );
define( 'BFAMI_OG_TITLE_SOFT_MAX', 60 );

/**
 * Register the meta so it is sanitized, visible in the Custom Fields
 * panel and available over REST for a possible future push workflow.
 */
function bfami_register_meta() {
	foreach ( array( BFAMI_DESCRIPTION_KEY, BFAMI_OG_TITLE_KEY ) as $key ) {
		register_post_meta(
			'post',
			$key,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
add_action( 'init', 'bfami_register_meta' );

/**
 * Tools submenu page.
 */
function bfami_admin_menu() {
	add_management_page(
		__( 'AI Meta Descriptions', 'bf-ai-meta-import' ),
		__( 'AI Meta', 'bf-ai-meta-import' ),
		'manage_options',
		'bf-ai-meta-import',
		'bfami_render_page'
	);
}
add_action( 'admin_menu', 'bfami_admin_menu' );

/**
 * Assets, on our screen only.
 */
function bfami_admin_assets( $hook ) {
	if ( 'tools_page_bf-ai-meta-import' !== $hook ) {
		return;
	}
	$base = plugin_dir_url( __FILE__ ) . 'assets/';
	wp_enqueue_style( 'bfami-admin', $base . 'admin.css', array(), '1.3' );
	wp_enqueue_script( 'bfami-admin', $base . 'admin.js', array(), '1.3', true );
}
add_action( 'admin_enqueue_scripts', 'bfami_admin_assets' );

/**
 * Resolve the entries to import: a JSON file uploaded through the form
 * wins; otherwise the meta_descriptions.json bundled in the plugin
 * folder. An uploaded file is also copied there (best effort) so the
 * Import tab shows what was imported last.
 *
 * @return array|WP_Error
 */
function bfami_resolve_entries() {
	if ( ! empty( $_FILES['bfami_json']['tmp_name'] ) ) {
		if ( UPLOAD_ERR_OK !== (int) $_FILES['bfami_json']['error'] ) {
			return new WP_Error( 'bfami_upload', __( 'Upload failed — try again.', 'bf-ai-meta-import' ) );
		}
		$raw     = (string) file_get_contents( sanitize_text_field( $_FILES['bfami_json']['tmp_name'] ) );
		$entries = json_decode( $raw, true );
		if ( ! is_array( $entries ) ) {
			return new WP_Error( 'bfami_json', __( 'The uploaded file is not valid JSON.', 'bf-ai-meta-import' ) );
		}
		if ( is_writable( plugin_dir_path( __FILE__ ) ) || is_writable( BFAMI_JSON_FILE ) ) {
			file_put_contents( BFAMI_JSON_FILE, $raw ); // phpcs:ignore
		}
		return $entries;
	}

	if ( ! file_exists( BFAMI_JSON_FILE ) ) {
		return new WP_Error(
			'bfami_missing',
			__( 'No JSON: pick a meta_descriptions.json file below, or FTP it into the plugin folder.', 'bf-ai-meta-import' )
		);
	}
	$entries = json_decode( (string) file_get_contents( BFAMI_JSON_FILE ), true );
	if ( ! is_array( $entries ) ) {
		return new WP_Error( 'bfami_json', __( 'meta_descriptions.json is not valid JSON.', 'bf-ai-meta-import' ) );
	}
	return $entries;
}

/**
 * Import entries. By default fills the two custom fields on posts that
 * do not have them yet; with $overwrite it also replaces values that
 * differ from the JSON. With $dry_run nothing is written — the report
 * describes what an import WOULD do (diff between JSON and database).
 *
 * @param bool $overwrite Replace existing values when the JSON differs.
 * @param bool $dry_run   Compute the diff without writing anything.
 * @return array{imported:int,updated:int,blocked:int,skipped:int,missing:int,errors:string[],changes:array[]}
 */
function bfami_run_import( $overwrite = false, $dry_run = false ) {
	$report = array(
		'imported' => 0, // posts where an empty field was filled
		'updated'  => 0, // posts where an existing value was overwritten
		'blocked'  => 0, // posts where the JSON differs but overwrite is off
		'skipped'  => 0, // posts already in sync with the JSON
		'missing'  => 0,
		'errors'   => array(),
		'changes'  => array(),
	);

	$entries = bfami_resolve_entries();
	if ( is_wp_error( $entries ) ) {
		$report['errors'][] = $entries->get_error_message();
		return $report;
	}

	$fields = array(
		'description' => BFAMI_DESCRIPTION_KEY,
		'og_title'    => BFAMI_OG_TITLE_KEY,
	);

	foreach ( $entries as $entry ) {
		$post_id = isset( $entry['id'] ) ? (int) $entry['id'] : 0;
		if ( ! $post_id || 'post' !== get_post_type( $post_id ) ) {
			$report['missing']++;
			continue;
		}

		$filled      = false;
		$overwritten = false;
		$blocked     = false;

		foreach ( $fields as $field => $key ) {
			$new = isset( $entry[ $field ] ) ? sanitize_text_field( (string) $entry[ $field ] ) : '';
			if ( '' === $new ) {
				continue;
			}
			$current = (string) get_post_meta( $post_id, $key, true );
			if ( $new === $current ) {
				continue;
			}

			if ( '' === $current ) {
				$action = 'fill';
				$filled = true;
			} elseif ( $overwrite ) {
				$action      = 'overwrite';
				$overwritten = true;
			} else {
				$action  = 'blocked';
				$blocked = true;
			}

			if ( ! $dry_run && 'blocked' !== $action ) {
				update_post_meta( $post_id, $key, $new );
			}

			$report['changes'][] = array(
				'post_id' => $post_id,
				'title'   => get_the_title( $post_id ),
				'field'   => $field,
				'action'  => $action,
				'old'     => $current,
				'new'     => $new,
			);
		}

		if ( $filled || $overwritten ) {
			if ( $filled ) {
				$report['imported']++;
			}
			if ( $overwritten && ! $filled ) {
				$report['updated']++;
			}
		} elseif ( $blocked ) {
			$report['blocked']++;
		} else {
			$report['skipped']++;
		}
	}

	return $report;
}

/**
 * Save handler for the bulk review tab.
 *
 * @return int Number of posts updated.
 */
function bfami_save_bulk_edits() {
	$updated      = 0;
	$descriptions = isset( $_POST['bfami_desc'] ) ? wp_unslash( (array) $_POST['bfami_desc'] ) : array();
	$og_titles    = isset( $_POST['bfami_og_title'] ) ? wp_unslash( (array) $_POST['bfami_og_title'] ) : array();

	foreach ( $descriptions as $post_id => $value ) {
		$post_id = (int) $post_id;
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			continue;
		}
		$changed  = bfami_update_or_delete_meta( $post_id, BFAMI_DESCRIPTION_KEY, sanitize_text_field( $value ) );
		$og_value = isset( $og_titles[ $post_id ] ) ? sanitize_text_field( $og_titles[ $post_id ] ) : null;
		if ( null !== $og_value ) {
			$changed = bfami_update_or_delete_meta( $post_id, BFAMI_OG_TITLE_KEY, $og_value ) || $changed;
		}
		if ( $changed ) {
			$updated++;
		}
	}

	return $updated;
}

/**
 * Update a meta value, deleting the key when the field was emptied.
 *
 * @return bool Whether something changed.
 */
function bfami_update_or_delete_meta( $post_id, $key, $value ) {
	$current = (string) get_post_meta( $post_id, $key, true );
	if ( $value === $current ) {
		return false;
	}
	if ( '' === $value ) {
		delete_post_meta( $post_id, $key );
	} else {
		update_post_meta( $post_id, $key, $value );
	}
	return true;
}

/**
 * Render the admin page (two tabs: import, review).
 */
function bfami_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$tab = isset( $_GET['tab'] ) && 'review' === $_GET['tab'] ? 'review' : 'import';

	$import_report = null;
	$preview       = false;
	$saved         = null;

	if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		if ( ( isset( $_POST['bfami_do_import'] ) || isset( $_POST['bfami_do_preview'] ) ) && check_admin_referer( 'bfami_import' ) ) {
			$preview       = isset( $_POST['bfami_do_preview'] );
			$overwrite     = ! empty( $_POST['bfami_overwrite'] );
			$import_report = bfami_run_import( $overwrite, $preview );
			$tab           = 'import';
		} elseif ( isset( $_POST['bfami_do_save'] ) && check_admin_referer( 'bfami_review' ) ) {
			$saved = bfami_save_bulk_edits();
			$tab   = 'review';
		}
	}

	$base_url = admin_url( 'tools.php?page=bf-ai-meta-import' );
	?>
	<div class="wrap bfami-wrap">
		<h1><?php esc_html_e( 'AI Meta Descriptions', 'bf-ai-meta-import' ); ?></h1>

		<h2 class="nav-tab-wrapper">
			<a href="<?php echo esc_url( $base_url ); ?>" class="nav-tab <?php echo 'import' === $tab ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Import', 'bf-ai-meta-import' ); ?>
			</a>
			<a href="<?php echo esc_url( $base_url . '&tab=review' ); ?>" class="nav-tab <?php echo 'review' === $tab ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Bulk review', 'bf-ai-meta-import' ); ?>
			</a>
		</h2>

		<?php
		if ( 'import' === $tab ) {
			bfami_render_import_tab( $import_report, $preview );
		} else {
			bfami_render_review_tab( $saved );
		}
		?>
	</div>
	<?php
}

/**
 * Import tab: state of the bundled JSON + preview/import buttons + report.
 *
 * @param array|null $report  Result of bfami_run_import() on POST.
 * @param bool       $preview Whether $report is a dry run (nothing written).
 */
function bfami_render_import_tab( $report, $preview = false ) {
	$json_state = __( 'No meta_descriptions.json in the plugin folder yet — pick one below.', 'bf-ai-meta-import' );
	if ( file_exists( BFAMI_JSON_FILE ) ) {
		$decoded    = json_decode( (string) file_get_contents( BFAMI_JSON_FILE ), true );
		$json_state = sprintf(
			/* translators: 1: entry count, 2: modification date */
			__( 'meta_descriptions.json in the plugin folder: %1$d entries, last modified %2$s. Importing without picking a file uses it.', 'bf-ai-meta-import' ),
			is_array( $decoded ) ? count( $decoded ) : 0,
			wp_date( 'Y-m-d H:i', (int) filemtime( BFAMI_JSON_FILE ) )
		);
	}
	?>
	<p><?php echo esc_html( $json_state ); ?></p>
	<p class="description">
		<?php esc_html_e( 'Import fills the custom fields bf_ai_meta_description and bf_ai_og_title on posts that do not have them. Existing values (including your manual edits) are never overwritten.', 'bf-ai-meta-import' ); ?>
	</p>

	<?php if ( $report ) : ?>
		<?php if ( $report['errors'] ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( implode( ' ', $report['errors'] ) ); ?></p></div>
		<?php else : ?>
			<div class="notice <?php echo $preview ? 'notice-info' : 'notice-success'; ?>">
				<p>
					<?php if ( $preview ) : ?>
						<strong><?php esc_html_e( 'Preview — nothing was written.', 'bf-ai-meta-import' ); ?></strong>
					<?php endif; ?>
					<?php
					printf(
						/* translators: 1-5: counters */
						esc_html__( '%1$d posts filled (field was empty), %2$d overwritten, %3$d differ from the JSON but were kept (overwrite is off), %4$d already in sync, %5$d entries without a matching post.', 'bf-ai-meta-import' ),
						(int) $report['imported'],
						(int) $report['updated'],
						(int) $report['blocked'],
						(int) $report['skipped'],
						(int) $report['missing']
					);
					?>
				</p>
			</div>
			<?php bfami_render_import_diff( $report['changes'], $preview ); ?>
		<?php endif; ?>
	<?php endif; ?>

	<form method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'bfami_import' ); ?>
		<p>
			<label for="bfami-json-file"><strong><?php esc_html_e( 'JSON file (optional):', 'bf-ai-meta-import' ); ?></strong></label><br>
			<input type="file" id="bfami-json-file" name="bfami_json" accept=".json,application/json">
		</p>
		<p>
			<label>
				<input type="checkbox" name="bfami_overwrite" value="1" <?php checked( ! empty( $_POST['bfami_overwrite'] ) ); ?>>
				<?php esc_html_e( 'Overwrite existing values when the JSON differs (replaces manual edits — preview first).', 'bf-ai-meta-import' ); ?>
			</label>
		</p>
		<p>
			<button type="submit" name="bfami_do_preview" value="1" class="button">
				<?php esc_html_e( 'Preview changes', 'bf-ai-meta-import' ); ?>
			</button>
			<button type="submit" name="bfami_do_import" value="1" class="button button-primary">
				<?php esc_html_e( 'Import', 'bf-ai-meta-import' ); ?>
			</button>
		</p>
		<p class="description">
			<?php esc_html_e( 'Preview shows the diff between the JSON and the database without writing anything. An uploaded file is kept for the next run, so you can preview it, then import without re-picking it.', 'bf-ai-meta-import' ); ?>
		</p>
	</form>
	<?php
}

/**
 * Diff table between the JSON and the database (used by both preview
 * and the post-import report).
 *
 * @param array[] $changes Rows from bfami_run_import()'s 'changes'.
 * @param bool    $preview Dry run (future tense labels).
 */
function bfami_render_import_diff( $changes, $preview ) {
	if ( ! $changes ) {
		return;
	}
	$labels = $preview
		? array(
			'fill'      => __( 'will fill', 'bf-ai-meta-import' ),
			'overwrite' => __( 'will overwrite', 'bf-ai-meta-import' ),
			'blocked'   => __( 'kept (overwrite off)', 'bf-ai-meta-import' ),
		)
		: array(
			'fill'      => __( 'filled', 'bf-ai-meta-import' ),
			'overwrite' => __( 'overwritten', 'bf-ai-meta-import' ),
			'blocked'   => __( 'kept (overwrite off)', 'bf-ai-meta-import' ),
		);
	$fields = array(
		'description' => __( 'Description', 'bf-ai-meta-import' ),
		'og_title'    => __( 'OG title', 'bf-ai-meta-import' ),
	);
	?>
	<h3>
		<?php
		/* translators: %d: number of field-level differences */
		printf( esc_html__( 'Diff: %d field(s) differ between the JSON and the database', 'bf-ai-meta-import' ), count( $changes ) );
		?>
	</h3>
	<table class="widefat striped bfami-diff-table">
		<thead>
			<tr>
				<th style="width:70px"><?php esc_html_e( 'ID', 'bf-ai-meta-import' ); ?></th>
				<th style="width:22%"><?php esc_html_e( 'Post', 'bf-ai-meta-import' ); ?></th>
				<th style="width:90px"><?php esc_html_e( 'Field', 'bf-ai-meta-import' ); ?></th>
				<th style="width:130px"><?php esc_html_e( 'Action', 'bf-ai-meta-import' ); ?></th>
				<th><?php esc_html_e( 'Current → JSON', 'bf-ai-meta-import' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $changes as $change ) : ?>
			<tr>
				<td><code class="bfami-id"><?php echo (int) $change['post_id']; ?></code></td>
				<td><strong><?php echo esc_html( $change['title'] ); ?></strong></td>
				<td><?php echo esc_html( $fields[ $change['field'] ] ?? $change['field'] ); ?></td>
				<td><span class="bfami-badge bfami-badge--<?php echo esc_attr( $change['action'] ); ?>"><?php echo esc_html( $labels[ $change['action'] ] ?? $change['action'] ); ?></span></td>
				<td class="bfami-diff-values">
					<?php if ( '' !== $change['old'] ) : ?>
						<del><?php echo esc_html( $change['old'] ); ?></del>
					<?php else : ?>
						<em class="description"><?php esc_html_e( '(empty)', 'bf-ai-meta-import' ); ?></em>
					<?php endif; ?>
					<ins><?php echo esc_html( $change['new'] ); ?></ins>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

/**
 * Count published posts having the description meta.
 *
 * @return int
 */
function bfami_count_with_description() {
	global $wpdb;
	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(DISTINCT p.ID)
			 FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = %s AND m.meta_value != ''
			 WHERE p.post_type = 'post' AND p.post_status = 'publish'",
			BFAMI_DESCRIPTION_KEY
		)
	);
}

/**
 * WP-style tablenav pagination block (pattern borrowed from the
 * breadcrumb-migration plugin): « ‹ [page] of N › » on the right.
 */
function bfami_render_pagination( $total, $per_page, $current, $url_params, $position ) {
	$total_pages = max( 1, (int) ceil( $total / $per_page ) );
	$is_first    = $current <= 1;
	$is_last     = $current >= $total_pages;
	$first_url   = esc_url( add_query_arg( 'paged', 1, $url_params ) );
	$prev_url    = $is_first ? '' : esc_url( add_query_arg( 'paged', $current - 1, $url_params ) );
	$next_url    = $is_last ? '' : esc_url( add_query_arg( 'paged', $current + 1, $url_params ) );
	$last_url    = esc_url( add_query_arg( 'paged', $total_pages, $url_params ) );
	$url_tpl     = esc_attr( add_query_arg( 'paged', 'BFAMI_PAGE', $url_params ) );
	$input_id    = 'bfami-page-jump-' . esc_attr( $position );
	?>
	<div class="tablenav-pages<?php echo $total_pages <= 1 ? ' one-page' : ''; ?>">
		<span class="displaying-num">
			<?php
			/* translators: %s: formatted item count */
			printf( esc_html( _n( '%s post', '%s posts', $total, 'bf-ai-meta-import' ) ), esc_html( number_format_i18n( $total ) ) );
			?>
		</span>
		<?php if ( $total_pages > 1 ) : ?>
		<span class="pagination-links">
			<?php if ( $is_first ) : ?>
				<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>
				<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>
			<?php else : ?>
				<a class="first-page button" href="<?php echo $first_url; // phpcs:ignore ?>">
					<span class="screen-reader-text"><?php esc_html_e( 'First page', 'bf-ai-meta-import' ); ?></span>
					<span aria-hidden="true">&laquo;</span>
				</a>
				<a class="prev-page button" href="<?php echo $prev_url; // phpcs:ignore ?>">
					<span class="screen-reader-text"><?php esc_html_e( 'Previous page', 'bf-ai-meta-import' ); ?></span>
					<span aria-hidden="true">&lsaquo;</span>
				</a>
			<?php endif; ?>
			<span class="paging-input">
				<label for="<?php echo $input_id; // phpcs:ignore ?>" class="screen-reader-text"><?php esc_html_e( 'Current page', 'bf-ai-meta-import' ); ?></label>
				<input class="current-page bfami-page-jump" id="<?php echo $input_id; // phpcs:ignore ?>"
					type="text" value="<?php echo esc_attr( $current ); ?>" size="3"
					data-total-pages="<?php echo esc_attr( $total_pages ); ?>"
					data-url-template="<?php echo $url_tpl; // phpcs:ignore ?>">
				<span class="tablenav-paging-text">
					<?php esc_html_e( 'of', 'bf-ai-meta-import' ); ?>
					<span class="total-pages"><?php echo esc_html( number_format_i18n( $total_pages ) ); ?></span>
				</span>
			</span>
			<?php if ( $is_last ) : ?>
				<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>
				<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>
			<?php else : ?>
				<a class="next-page button" href="<?php echo $next_url; // phpcs:ignore ?>">
					<span class="screen-reader-text"><?php esc_html_e( 'Next page', 'bf-ai-meta-import' ); ?></span>
					<span aria-hidden="true">&rsaquo;</span>
				</a>
				<a class="last-page button" href="<?php echo $last_url; // phpcs:ignore ?>">
					<span class="screen-reader-text"><?php esc_html_e( 'Last page', 'bf-ai-meta-import' ); ?></span>
					<span aria-hidden="true">&raquo;</span>
				</a>
			<?php endif; ?>
		</span>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Review tab: searchable, paginated editable table of descriptions and
 * og titles, with WP-style pagination top + bottom.
 *
 * @param int|null $saved Number of posts updated on POST.
 */
function bfami_render_review_tab( $saved ) {
	$paged        = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
	$missing_only = ! empty( $_GET['missing'] );
	$search       = isset( $_GET['bfami_s'] ) ? sanitize_text_field( wp_unslash( $_GET['bfami_s'] ) ) : '';
	$per_page     = 50;

	$args = array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'paged'          => $paged,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);
	if ( $search ) {
		$args['s'] = $search;
	}
	if ( $missing_only ) {
		$args['meta_query'] = array(
			'relation' => 'OR',
			array(
				'key'     => BFAMI_DESCRIPTION_KEY,
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => BFAMI_DESCRIPTION_KEY,
				'value'   => '',
				'compare' => '=',
			),
		);
	}
	$query = new WP_Query( $args );

	$total_posts = (int) wp_count_posts( 'post' )->publish;
	$with_desc   = bfami_count_with_description();
	$missing     = max( 0, $total_posts - $with_desc );

	$base_url   = admin_url( 'tools.php?page=bf-ai-meta-import&tab=review' );
	$url_params = add_query_arg(
		array_filter(
			array(
				'missing' => $missing_only ? '1' : null,
				'bfami_s' => $search ? $search : null,
			)
		),
		$base_url
	);
	?>
	<?php if ( null !== $saved ) : ?>
		<div class="notice notice-success is-dismissible"><p>
			<?php
			/* translators: %d: number of posts */
			printf( esc_html__( '%d posts updated.', 'bf-ai-meta-import' ), (int) $saved );
			?>
		</p></div>
	<?php endif; ?>

	<div class="bfami-stats">
		<span class="bfami-stat"><strong><?php echo esc_html( number_format_i18n( $total_posts ) ); ?></strong><?php esc_html_e( 'published posts', 'bf-ai-meta-import' ); ?></span>
		<span class="bfami-stat bfami-stat--with"><strong><?php echo esc_html( number_format_i18n( $with_desc ) ); ?></strong><?php esc_html_e( 'with description', 'bf-ai-meta-import' ); ?></span>
		<span class="bfami-stat bfami-stat--missing"><strong><?php echo esc_html( number_format_i18n( $missing ) ); ?></strong><?php esc_html_e( 'missing', 'bf-ai-meta-import' ); ?></span>
	</div>

	<div class="bfami-filters">
		<span class="bfami-filter-links">
			<a href="<?php echo esc_url( $search ? add_query_arg( 'bfami_s', $search, $base_url ) : $base_url ); ?>" class="<?php echo $missing_only ? '' : 'current'; ?>">
				<?php esc_html_e( 'All posts', 'bf-ai-meta-import' ); ?>
				<span class="bfami-filter-count"><?php echo esc_html( number_format_i18n( $total_posts ) ); ?></span>
			</a>
			<a href="<?php echo esc_url( add_query_arg( array_filter( array( 'missing' => '1', 'bfami_s' => $search ? $search : null ) ), $base_url ) ); ?>" class="<?php echo $missing_only ? 'current' : ''; ?>">
				<?php esc_html_e( 'Missing description', 'bf-ai-meta-import' ); ?>
				<span class="bfami-filter-count"><?php echo esc_html( number_format_i18n( $missing ) ); ?></span>
			</a>
		</span>

		<form method="get" action="<?php echo esc_url( admin_url( 'tools.php' ) ); ?>">
			<input type="hidden" name="page" value="bf-ai-meta-import">
			<input type="hidden" name="tab" value="review">
			<?php if ( $missing_only ) : ?>
				<input type="hidden" name="missing" value="1">
			<?php endif; ?>
			<p class="search-box" style="position:static;float:none;margin:0;">
				<label class="screen-reader-text" for="bfami-search-input"><?php esc_html_e( 'Search posts', 'bf-ai-meta-import' ); ?></label>
				<input type="search" id="bfami-search-input" name="bfami_s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search post title…', 'bf-ai-meta-import' ); ?>">
				<input type="submit" class="button" value="<?php esc_attr_e( 'Search', 'bf-ai-meta-import' ); ?>">
				<?php if ( $search ) : ?>
					<a href="<?php echo esc_url( $missing_only ? add_query_arg( 'missing', '1', $base_url ) : $base_url ); ?>" class="button">&#10005; <?php esc_html_e( 'Clear', 'bf-ai-meta-import' ); ?></a>
				<?php endif; ?>
			</p>
		</form>
	</div>

	<form method="post">
		<?php wp_nonce_field( 'bfami_review' ); ?>

		<div class="tablenav bfami-tablenav bfami-tablenav--top">
			<span class="bfami-save-group">
				<button type="submit" name="bfami_do_save" value="1" class="button button-primary bfami-save"
					data-label-dirty="<?php /* translators: %d: number of edited posts */ esc_attr_e( 'Save changes (%d posts)', 'bf-ai-meta-import' ); ?>"
					data-label-clean="<?php esc_attr_e( 'No changes to save', 'bf-ai-meta-import' ); ?>">
					<?php esc_html_e( 'Save all changes on this page', 'bf-ai-meta-import' ); ?>
				</button>
			</span>
			<?php bfami_render_pagination( (int) $query->found_posts, $per_page, $paged, $url_params, 'top' ); ?>
		</div>

		<?php if ( ! $query->have_posts() ) : ?>
			<p><?php esc_html_e( 'No posts found.', 'bf-ai-meta-import' ); ?></p>
		<?php else : ?>
		<div class="bfami-id-bar">
			<label for="bfami-page-ids"><strong><?php esc_html_e( 'IDs on this page:', 'bf-ai-meta-import' ); ?></strong></label>
			<input type="text" id="bfami-page-ids" readonly value="<?php echo esc_attr( implode( ',', wp_list_pluck( $query->posts, 'ID' ) ) ); ?>" onclick="this.select()">
			<button type="button" class="button" id="bfami-copy-ids" data-done="<?php esc_attr_e( 'Copied!', 'bf-ai-meta-import' ); ?>"><?php esc_html_e( 'Copy', 'bf-ai-meta-import' ); ?></button>
			<span class="description"><?php esc_html_e( 'Paste into --ids of tools/generate_meta_descriptions.py (use the "Missing description" filter first).', 'bf-ai-meta-import' ); ?></span>
		</div>

		<table class="widefat striped bfami-table">
			<thead>
				<tr>
					<th style="width:70px"><?php esc_html_e( 'ID', 'bf-ai-meta-import' ); ?></th>
					<th style="width:28%"><?php esc_html_e( 'Post', 'bf-ai-meta-import' ); ?></th>
					<th><?php esc_html_e( 'Meta / OG description', 'bf-ai-meta-import' ); ?></th>
					<th style="width:23%"><?php esc_html_e( 'OG title (optional)', 'bf-ai-meta-import' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php while ( $query->have_posts() ) : ?>
				<?php
				$query->the_post();
				$post_id     = get_the_ID();
				$description = (string) get_post_meta( $post_id, BFAMI_DESCRIPTION_KEY, true );
				$og_title    = (string) get_post_meta( $post_id, BFAMI_OG_TITLE_KEY, true );
				?>
				<tr class="<?php echo '' === $description ? 'bfami-row-missing' : ''; ?>">
					<td><code class="bfami-id"><?php echo (int) $post_id; ?></code></td>
					<td>
						<strong><a href="<?php echo esc_url( get_permalink() ); ?>" target="_blank"><?php the_title(); ?></a></strong><br>
						<span class="description"><?php echo esc_html( get_the_date( 'Y-m-d' ) ); ?> &mdash;
							<a href="<?php echo esc_url( get_edit_post_link() ); ?>"><?php esc_html_e( 'Edit post', 'bf-ai-meta-import' ); ?></a>
						</span>
					</td>
					<td>
						<textarea name="bfami_desc[<?php echo (int) $post_id; ?>]" rows="2" class="large-text bfami-count" data-max="<?php echo (int) BFAMI_DESCRIPTION_SOFT_MAX; ?>"><?php echo esc_textarea( $description ); ?></textarea>
						<span class="bfami-counter description"></span>
					</td>
					<td>
						<input type="text" name="bfami_og_title[<?php echo (int) $post_id; ?>]" value="<?php echo esc_attr( $og_title ); ?>" class="large-text bfami-count" data-max="<?php echo (int) BFAMI_OG_TITLE_SOFT_MAX; ?>">
						<span class="bfami-counter description"></span>
					</td>
				</tr>
			<?php endwhile; ?>
			<?php wp_reset_postdata(); ?>
			</tbody>
		</table>
		<?php endif; ?>

		<div class="tablenav bfami-tablenav bfami-tablenav--bottom">
			<span class="bfami-save-group">
				<button type="submit" name="bfami_do_save" value="1" class="button button-primary bfami-save"
					data-label-dirty="<?php /* translators: %d: number of edited posts */ esc_attr_e( 'Save changes (%d posts)', 'bf-ai-meta-import' ); ?>"
					data-label-clean="<?php esc_attr_e( 'No changes to save', 'bf-ai-meta-import' ); ?>">
					<?php esc_html_e( 'Save all changes on this page', 'bf-ai-meta-import' ); ?>
				</button>
			</span>
			<?php bfami_render_pagination( (int) $query->found_posts, $per_page, $paged, $url_params, 'bottom' ); ?>
		</div>
	</form>
	<?php
}
