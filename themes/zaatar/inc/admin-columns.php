<?php
/**
 * Admin list-table columns for posts, pages and custom post types.
 *
 * Extracted from functions.php (step 7 refactor). Site-specific: the custom
 * post types themselves are registered by plugins (he3_product_to_sale,
 * bf_quotes_manager, ...); the theme only decorates their admin list screens.
 *
 * @package Zaatar
 */

/*****************************************************************************************/
/* BEGIN - Settings for the filtering columns for product_for_sale                        */
/*****************************************************************************************/

// ProductForSale: Add admin columns with correct image rendering
add_filter('manage_edit-productforsale_columns', 'myeditproductforsalecolumns');
add_action('manage_productforsale_posts_custom_column', 'mymanageproductforsalecolumns', 10, 2);

function myeditproductforsalecolumns($columns) {
    $columns = array(
        'cb'           => '<input type="checkbox" />',
        'cover'        => 'Cover',
        'title'        => 'Title',
        'productasin'  => 'Asin',
        'author'       => 'Auteur',
        'genres'       => 'Genres',
        'auteurs'      => 'Auteurs',
        'editions'     => 'Editions',
        'amazonproductsingle' => 'posts966',
        'shortcodesingle' => 'Shortcode',
        'id'           => 'ID',
        'views'        => 'Vues',
        'date'         => 'Date',
        'comments'     => 'Comments',
        'attachments'  => 'Attachments'
    );
    return $columns;
}

function mymanageproductforsalecolumns($column, $post_id) {
    switch ($column) {

        case 'productasin':
            $asin = get_post_meta($post_id, 'amazonitemAmazonAsin', true);
            echo empty($asin) ? 'Unknown' : esc_html($asin);
            break;

        case 'genres':
            $terms = get_the_terms($post_id, 'productforsalegenre');
            if (!empty($terms)) {
                $out = array();
                foreach ($terms as $term) {
                    $out[] = sprintf(
                        '<a href="%s">%s</a>',
                        esc_url(add_query_arg(array('post_type' => 'productforsale', 'productforsalegenre' => $term->slug), 'edit.php')),
                        esc_html($term->name)
                    );
                }
                echo join(', ', $out);
            } else {
                echo 'No Genres';
            }
            break;

        case 'auteurs':
            $terms = get_the_terms($post_id, 'productforsaleauthor');
            if (!empty($terms)) {
                $out = array();
                foreach ($terms as $term) {
                    $out[] = sprintf(
                        '<a href="%s">%s</a>',
                        esc_url(add_query_arg(array('post_type' => 'productforsale', 'productforsaleauthor' => $term->slug), 'edit.php')),
                        esc_html($term->name)
                    );
                }
                echo join(', ', $out);
            } else {
                echo 'No Authors';
            }
            break;

        case 'editions':
            $terms = get_the_terms($post_id, 'productforsalekw');
            if (!empty($terms)) {
                $out = array();
                foreach ($terms as $term) {
                    $out[] = sprintf(
                        '<a href="%s">%s</a>',
                        esc_url(add_query_arg(array('post_type' => 'productforsale', 'productforsalekw' => $term->slug), 'edit.php')),
                        esc_html($term->name)
                    );
                }
                echo join(', ', $out);
            } else {
                echo 'No Editions';
            }
            break;

        case 'shortcodesingle':
            // Display the ID in a hidden input and a textarea for shortcode use
            $id = $post_id;
            if (empty($id)) {
                echo 'Unknown';
            } else {
                printf('<input value="amazonproductsingle_posts%s" />', esc_attr($id));
                printf('<textarea rows="3" cols="15" wrap="hard">amazonproductsingle_posts%s</textarea>', esc_attr($id));
            }
            break;

        case 'cover':
            $cover_url = get_post_meta($post_id, 'amazonitemAmazonMediumImageURL', true);
            $cover_title = get_post_meta($post_id, 'amazonitemAmazonTitle', true);
            $cover_height = get_post_meta($post_id, 'amazonitemAmazonMediumImageHeight', true);
            $cover_width = get_post_meta($post_id, 'amazonitemAmazonMediumImageWidth', true);

            if (empty($cover_url)) {
                echo 'Unknown';
            } else {
                printf(
                    '<img alt="%s" src="%s" width="%d" height="%d" />',
                    esc_attr($cover_title),
                    esc_url($cover_url),
                    intval($cover_width),
                    intval($cover_height)
                );
            }
            break;

        // Add more custom columns as needed...

        default:
            // For other columns, use default behavior or break
            break;
    }
}

// Make columns sortable if needed
add_filter('manage_edit-productforsale_sortable_columns', 'myproductforsalesortablecolumns');
function myproductforsalesortablecolumns($columns) {
    $columns['title'] = 'title';
    $columns['productasin'] = 'productasin';
    $columns['genres'] = 'genres';
    $columns['auteurs'] = 'auteurs';
    $columns['editions'] = 'editions';
    $columns['views'] = 'views';
    $columns['date'] = 'date';
    $columns['id'] = 'id';
    $columns['shortcodesingle'] = 'shortcodesingle';
    $columns['cover'] = 'cover';
    return $columns;
}

/*****************************************************************************************/
/* END - Settings for the filtering columns for product_for_sale                          */
/*****************************************************************************************/


/*****************************************************************************************/
/* BEGIN - Change the admin columns for all the post_type and post                       */
/*****************************************************************************************/

/* -----------  for Posts ----------- */

/* ADD specific columns for the posts */
add_filter( 'manage_edit-post_columns', 'he3_edit_posts_columns' );
add_action( 'manage_posts_custom_column', 'he3_posts_columns', 10, 2 );

function he3_edit_posts_columns( $columns ) {

	// Insert 'id' as the first custom column after checkbox
	$new_columns = array();

	foreach ( $columns as $key => $value ) {
		if ( $key === 'cb' ) {
			$new_columns['cb'] = $value;
			$new_columns['id'] = __( 'ID' );
		} else {
			$new_columns[$key] = $value;
		}
	}

	// Add columns if not set by theme/plugins
	$new_columns['thumb'] = __( 'Thumbnail' );
	$new_columns['attachments'] = __( 'Attachments' );
	$new_columns['views'] = __( 'Views' );

	return $new_columns;
}
// End of he3_edit_posts_columns

function he3_posts_columns( $column, $post_id ) {
	switch( $column ) {

		// ID column
		case 'id':
			echo $post_id;
			break;

		// Thumbnail column
		case 'thumb':
			$thumb = get_the_post_thumbnail( $post_id, array( 125, 80 ) );
			$url = admin_url( 'media-upload.php?post_id=' . $post_id . '&type=image&TB_iframe=1&width=640&height=296' );

			if ( empty( $thumb ) ) {
				echo __( 'No Thumbnail' );
			} else {
				$html = '<div>';
				$html .= $thumb . '<br>';
				$html .= '<a href="' . $url . '" id="set-post-thumbnail" class="thickbox">Select thumbnail</a>';
				$html .= '</div>';
				echo $html;
			}
			break;

		// Attachments column
		case 'attachments':
			$attachments = get_children( array( 'post_parent' => $post_id ) );
			$count = count( $attachments );
			$html = '<code>' . $count . __( ' Files' ) . '</code>';

			foreach ( $attachments as $att ) {
				$html .= '<div style="float:left; padding: 2px; margin: 0 2px 5px; border: 1px solid #DFDFDF;">';
				$html .= '<a href="' . esc_url( $att->guid ) . '" title="' . esc_attr( $att->post_title ) . '" rel="attached" class="thickbox">';
				$html .= wp_get_attachment_image( $att->ID, array( 30, 30 ), true, array( "class" => "pinkynail" ) );
				$html .= '</a></div>';
			}
			$html .= '<br style="clear:both;" />';
			echo $html;
			break;

		default:
			break;
	}
}
// End of he3_posts_columns

/*
Uncomment if you want sortable columns
// add_filter( 'manage_edit-post_sortable_columns', 'he3_posts_sortable_columns' );
function he3_posts_sortable_columns( $columns ) {
$columns['id'] = 'id';
$columns['thumb'] = 'thumb';
$columns['title'] = 'title';
$columns['categories'] = 'categories';
$columns['tags'] = 'tags';
$columns['comments'] = 'comments';
$columns['date'] = 'date';
$columns['author'] = 'author';
$columns['views'] = 'views';
$columns['attachments'] = 'attachments';

return $columns;
}
*/

/* -----------  // for Posts ----------- */

/* -----------  For Pages ----------- */

/* ADD specific columns for the pages */
add_filter( 'manage_edit-page_columns', 'he3_edit_pages_columns' ) ;
add_action( 'manage_pages_custom_column', 'he3_pages_columns', 10, 2 );

function he3_edit_pages_columns( $columns ) {

	$columns = array(
				'cb' => '<input type="checkbox" />',
				'id' => __( 'ID' ),
				'thumb' => __( 'Thumbnail' ),
				'title' => __( 'Title' ),
				'author' => __( 'Auteur' ),
				'comments' => __( '<span class="vers"><img src="'.get_admin_url().'/images/comment-grey-bubble.png" alt="Comments"></span>'),
				'date' => __( 'Date' ),
				'views' => __( 'Vue(s)' ),
				'attachments' => __( 'Attachments' ),
	);

	return $columns;
}//EOF


function he3_pages_columns ( $column, $post_id ) {
	global $post;

	switch( $column ) {
		/* id */
		case 'id' :
			$postid = get_the_ID();
			if ( empty( $postid ) )
				echo __( 'Unknown' );
			else
				printf( __( '%s' ), $postid );
			break;
		/* // id */

		/* thumb */
		case 'thumb' :
			$postid = get_the_ID();
			$thumb = get_the_post_thumbnail($post_id, array(125, 80) );

			if ( empty( $postid ) )
				echo __( 'Unknown' );
			else
				printf( __( '%s' ), $thumb );

			break;
		/* // thumb */

		/* views */
		case 'views' :

			global $wpdb;
			/* values */
			$post_id = get_the_ID();
			$type = "page";
			if ( empty( $post_id ) )
				echo __( 'Unknown' );
			else
				/* // MOST VIEWED PAGE */
				/* QUERY */

				$sql = " SELECT
				".$wpdb->prefix."postview.post_id,
				".$wpdb->prefix."postview.view,
				".$wpdb->prefix."postview.post_id
				FROM ".$wpdb->prefix."postview
				INNER JOIN ".$wpdb->prefix."posts ON ".$wpdb->prefix."posts.ID = ".$wpdb->prefix."postview.post_id
				WHERE
				".$wpdb->prefix."postview.post_id='".$post_id."'
				";

			$results = $wpdb->get_results($sql);
			// print_r($results);
			foreach ( $results as $result )
			{
				echo $result->view;
			}
			/* // MOST VIEWED PAGE */
			break;
		/* // views */

		/* attachments */
		case 'attachments' :
			$postid = get_the_ID();
			$attachments = get_children(array('post_parent'=>$postid));
			$count = count($attachments);

			if ( empty( $postid ) )
				echo __( 'Unknown' );
			else
				$html = '<code>';
				$html .= $count. __(' Files').'</code>';

				foreach ($attachments as $att) {
						$html .= '<div style="float:left; padding: 2px; margin: 0 2px 5px; border: 1px solid #DFDFDF;">';
						$html .= '<a href="'.$att->guid.' " title="'.$att->post_title.'" rel="attached" class="thickbox">';
						$html .= wp_get_attachment_image( $att->ID, array(30, 30), true, array("class"=>"pinkynail") );
						$html .= '</a></div>' ;
				}
				$html .= '<br style="clear:both;" />';
				echo $html;

			break;
		/* // attachments */

		/* - CAUTION - */
		/* Just break out of the switch statement for everything else. */
		default :
			break;

	}//EOS

}//EOF

/* -----------  // For pages ----------- */

/* -----------  For post_type ----------- */

/*
portfolio
showcase
team
clients
testimonials
jobs
faqs
*/

/* ADD specific columns for the post_type */

// For portfolio
add_filter( 'manage_edit-portfolio_columns', 'he3_edit_posts_type_portfolio_columns' ) ;
add_action( 'manage_portfolio_custom_column', 'he3_posts_type_columns', 10, 2 );

function he3_edit_posts_type_portfolio_columns ( $columns ) {

	$columns = array(
				'cb' => '<input type="checkbox" />',
				'id' => __( 'ID' ),
				'thumb' => __( 'Thumbnail' ),
				'title' => __( 'Title' ),
				'portfolio-category' => __( 'Categories' ),
				'tags' => __( 'Tags' ),
				'comments' => __( '<span class="vers"><img src="'.get_admin_url().'/images/comment-grey-bubble.png" alt="Comments"></span>'),
				'date' => __( 'Date' ),
				'author' => __( 'Auteur' ),
				'views' => __( 'Vue(s)' ),
				'attachments' => __( 'Attachments' ),
	);

	return $columns;
}//EOF

// For showcase
add_filter( 'manage_edit-showcase_columns', 'he3_edit_posts_type_showcase_columns' ) ;
add_action( 'manage_showcase_custom_column', 'he3_posts_type_columns', 10, 2 );

function he3_edit_posts_type_showcase_columns ( $columns ) {

	$columns = array(
				'cb' => '<input type="checkbox" />',
				'id' => __( 'ID' ),
				'thumb' => __( 'Thumbnail' ),
				'title' => __( 'Title' ),
				'showcase-category' => __( 'Categories' ),
				'tags' => __( 'Tags' ),
				'comments' => __( '<span class="vers"><img src="'.get_admin_url().'/images/comment-grey-bubble.png" alt="Comments"></span>'),
				'date' => __( 'Date' ),
				'author' => __( 'Auteur' ),
				'views' => __( 'Vue(s)' ),
				'attachments' => __( 'Attachments' ),
	);

	return $columns;
}//EOF

// For team
add_filter( 'manage_edit-team_columns', 'he3_edit_posts_type_team_columns' ) ;
add_action( 'manage_team_custom_column', 'he3_posts_type_columns', 10, 2 );

function he3_edit_posts_type_team_columns ( $columns ) {

	$columns = array(
				'cb' => '<input type="checkbox" />',
				'id' => __( 'ID' ),
				'thumb' => __( 'Thumbnail' ),
				'title' => __( 'Title' ),
				'team-category' => __( 'Categories' ),
				'tags' => __( 'Tags' ),
				'comments' => __( '<span class="vers"><img src="'.get_admin_url().'/images/comment-grey-bubble.png" alt="Comments"></span>'),
				'date' => __( 'Date' ),
				'author' => __( 'Auteur' ),
				'views' => __( 'Vue(s)' ),
				'attachments' => __( 'Attachments' ),
	);

	return $columns;
}//EOF

// For clients
add_filter( 'manage_edit-clients_columns', 'he3_edit_posts_type_clients_columns' ) ;
add_action( 'manage_clients_custom_column', 'he3_posts_type_columns', 10, 2 );

function he3_edit_posts_type_clients_columns ( $columns ) {

	$columns = array(
				'cb' => '<input type="checkbox" />',
				'id' => __( 'ID' ),
				'thumb' => __( 'Thumbnail' ),
				'title' => __( 'Title' ),
				'clients-category' => __( 'Categories' ),
				'tags' => __( 'Tags' ),
				'comments' => __( '<span class="vers"><img src="'.get_admin_url().'/images/comment-grey-bubble.png" alt="Comments"></span>'),
				'date' => __( 'Date' ),
				'author' => __( 'Auteur' ),
				'views' => __( 'Vue(s)' ),
				'attachments' => __( 'Attachments' ),
	);

	return $columns;
}//EOF

// For testimonials
add_filter( 'manage_edit-testimonials_columns', 'he3_edit_posts_type_testimonials_columns' ) ;
add_action( 'manage_testimonials_custom_column', 'he3_posts_type_columns', 10, 2 );

function he3_edit_posts_type_testimonials_columns ( $columns ) {

	$columns = array(
				'cb' => '<input type="checkbox" />',
				'id' => __( 'ID' ),
				'thumb' => __( 'Thumbnail' ),
				'title' => __( 'Title' ),
				'testimonials-category' => __( 'Categories' ),
				'tags' => __( 'Tags' ),
				'comments' => __( '<span class="vers"><img src="'.get_admin_url().'/images/comment-grey-bubble.png" alt="Comments"></span>'),
				'date' => __( 'Date' ),
				'author' => __( 'Auteur' ),
				'views' => __( 'Vue(s)' ),
				'attachments' => __( 'Attachments' ),
	);

	return $columns;
}//EOF


// For jobs
add_filter( 'manage_edit-jobs_columns', 'he3_edit_posts_type_jobs_columns' ) ;
add_action( 'manage_jobs_custom_column', 'he3_posts_type_columns', 10, 2 );

function he3_edit_posts_type_jobs_columns ( $columns ) {

	$columns = array(
				'cb' => '<input type="checkbox" />',
				'id' => __( 'ID' ),
				'thumb' => __( 'Thumbnail' ),
				'title' => __( 'Title' ),
				'jobs-category' => __( 'Categories' ),
				'tags' => __( 'Tags' ),
				'comments' => __( '<span class="vers"><img src="'.get_admin_url().'/images/comment-grey-bubble.png" alt="Comments"></span>'),
				'date' => __( 'Date' ),
				'author' => __( 'Auteur' ),
				'views' => __( 'Vue(s)' ),
				'attachments' => __( 'Attachments' ),
	);

	return $columns;
}//EOF



// For faqs // NOPE
add_filter( 'manage_edit-faqs_columns', 'he3_edit_posts_type_faqs_columns' ) ;
add_action( 'manage_faqs_custom_column', 'he3_posts_type_columns', 10, 2 );

function he3_edit_posts_type_faqs_columns ( $columns ) {

	$columns = array(
				'cb' => '<input type="checkbox" />',
				'id' => __( 'ID' ),
				'thumb' => __( 'Thumbnail' ),
				'title' => __( 'Title' ),
				'faqs-category' => __( 'Categories' ),
				'tags' => __( 'Tags' ),
				'comments' => __( '<span class="vers"><img src="'.get_admin_url().'/images/comment-grey-bubble.png" alt="Comments"></span>'),
				'date' => __( 'Date' ),
				'author' => __( 'Auteur' ),
				'views' => __( 'Vue(s)' ),
				'attachments' => __( 'Attachments' ),
	);

	return $columns;
}//EOF

/* -----------  // For post_type ----------- */

/*****************************************************************************************/
/* END - Change the admin columns for all the post_type and post                         */
/*****************************************************************************************/


/*****************************************************************************************/
/* BEGIN - Settings for the filtering columns for bf_quotes_manager                      */
/*****************************************************************************************/

/* ADD specific columns to the post_type bf_quotes_manager */
add_filter( 'manage_edit-bf_quotes_manager_columns', 'my_edit_bf_quotes_manager_columns' ) ;

add_action( 'manage_bf_quotes_manager_posts_custom_column', 'my_manage_bf_quotes_manager_columns', 10, 2 );

function my_edit_bf_quotes_manager_columns( $columns ) {

	$columns = array(
				'cb' => '<input type="checkbox" />',
				'thumb' => __( 'Thumbnail' ),
				'title' => __( 'Title' ),
				'authors' => __( 'Author(s)' ),
				'flavors' => __( 'Flavors(s)' ),
				'shortcode_single' => ('Shortcode'),
				'views' => __( 'Vue(s)' ),
	);

	return $columns;
}



function my_manage_bf_quotes_manager_columns ( $column, $post_id ) {
	global $post;

	switch( $column ) {

		/* - bf_quotes_manager_genre - */

		/* If displaying the column. */
		case 'authors' :

			/* Get the types for the post. */
			$terms = get_the_terms( $post_id, 'bf_quotes_manager_author' );

			/* If terms were found. */
			if ( !empty( $terms ) ) {

				$out = array();

				/* Loop through each term, linking to the 'edit posts' page for the specific term. */
				foreach ( $terms as $term ) {
					$out[] = sprintf( '<a href="%s">%s</a>',
						esc_url( add_query_arg( array( 'post_type' => $post->post_type, 'bf_quotes_manager_genre' => $term->slug ), 'edit.php' ) ),
						esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, 'bf_quotes_manager_genre', 'display' ) )
					);
				}

				/* Join the terms, separating them with a comma. */
				echo join( ', ', $out );
			}

			/* If no terms were found, output a default message. */
			else {
				_e( 'No Genres' );
			}

			break;
		/* //- bf_quotes_manager_genre - */

		/* - bf_quotes_manager_author - */

		/* If displaying the column. */
		case 'flavors' :

			/* Get the types for the post. */
			$terms = get_the_terms( $post_id, 'bf_quotes_manager_flavor' );

			/* If terms were found. */
			if ( !empty( $terms ) ) {

				$out = array();

				/* Loop through each term, linking to the 'edit posts' page for the specific term. */
				foreach ( $terms as $term ) {
					$out[] = sprintf( '<a href="%s">%s</a>',
						esc_url( add_query_arg( array( 'post_type' => $post->post_type, 'bf_quotes_manager_flavor' => $term->slug ), 'edit.php' ) ),
						esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, 'bf_quotes_manager_flavor', 'display' ) )
					);
				}

				/* Join the terms, separating them with a comma. */
				echo join( ', ', $out );
			}

			/* If no terms were found, output a default message. */
			else {
				_e( 'No Flavor(s)' );
			}

			break;
			/* // - bf_quotes_manager_flavor - */

		/* - shortcode_single - */
		/* If displaying the 'id' column. */
		case 'shortcode_single' :

			/* Get the id. */
			$postid = get_the_ID();

			/* If no id is found, output a default message. */
			if ( empty( $postid ) )
				echo __( 'Unknown' );

			/* If there is a id, append 'id' to the text string. */
			else
				// printf( __( '<input value="[amazon_product_single posts="%s"]">' ), $postid );
				printf( __( '<textarea rows="3" cols="15" wrap="hard">[bf_quotes_manager_single posts="%s"]</textarea>' ), $postid );
			break;
			/* shortcode_single */

		/* - CAUTION - */

		/* Just break out of the switch statement for everything else. */
		default :
			break;
	}
}

add_filter( 'manage_edit-bf_quotes_manager_sortable_columns', 'my_bf_quotes_manager_sortable_columns' );

function my_bf_quotes_manager_sortable_columns( $columns ) {
	$columns['cover'] = 'cover';
	$columns['title'] = 'title';
	$columns['authors'] = 'authors';
	$columns['flavors'] = 'flavors';
	$columns['shortcode_single'] = 'shortcode_single';
	$columns['views'] = 'views';
	// $columns['date'] = 'date';
	// $columns['id'] = 'id';

	return $columns;
}
/*****************************************************************************************/
/* END - Settings for the filtering columns for bf_quotes_manager                        */
/*****************************************************************************************/
