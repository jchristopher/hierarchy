<?php

class Hierarchy_Factory extends Hierarchy {

	/**
	 * Post types
	 *
	 * @since 1.0
	 * @var array
	 */
	protected $post_types = array();

	/**
	 * Everything is based on the Pages so they must be stored
	 *
	 * @since 1.0
	 * @var array   Page post objects
	 */
	private $pages = array();

	/**
	 * Hierarchy's hierarchy
	 *
	 * @since 1.0
	 * @var array   Hierarchy's hierarchy (so meta!)
	 */
	private $hierarchy = array();

	/**
	 * The post ID of the page set to be the hub for Posts
	 *
	 * @since 1.0
	 * @var int     The post ID of the page set to be the hub for Posts
	 */
	private $posts_page;

	/**
	 * The current post object to work with
	 *
	 * @since 1.0
	 * @var object  The current post object to work with
	 */
	private $context;

	/**
	 * Build the Hierarchy array
	 *
	 * @since 1.0
	 */
	function build() {

		$this->pages = $this->get_post_type_posts( 'page', true );

		// determine whether a Page has been set to display Posts
		$this->posts_page = ( 'page' == get_option( 'show_on_front' ) ) ? intval( get_option( 'page_for_posts' ) ) : 0;

		// we'll build the Hierarchy one link at a time, using the Pages as our main structure
		foreach ( $this->pages as $page ) {
			$this->context = $page;
			$new_entry = $this->generate_page_entry( $this->context );

			// if we're not dealing with the Posts page, we need to inject this entry
			if ( $this->posts_page !== absint( $this->context['ID'] ) ) {
				$this->inject_entry( $new_entry );
			}

			// handle all post types
			foreach( $this->post_types as $post_type ) {
				if ( 'page' !== $post_type ) {
					$this->process_post_type($post_type, $this->context);
				}
			}
		}

		// append any orphaned post types
		if ( ! empty( $this->post_types ) ) {
			$this->clean_up_post_types();
		}

		return $this->hierarchy;

	}

	/**
	 * Take care of any post types that may have been omitted from settings (e.g. added after settings save) by
	 * appending them to the bottom of the Hierarchy
	 *
	 * @since 1.0
	 */
	function clean_up_post_types() {
		foreach ( $this->post_types as $post_type ) {

			if ( $post_type == 'page' || ! empty( $this->settings['post_types'][ $post_type ]['omit'] ) ) {
				continue;
			}

			$order = ! empty( $this->settings['post_types'][ $post_type ]['order'] ) ? intval( $this->settings['post_types'][ $post_type ]['order'] ) : 0;

			$post_type_entry = array(
				'entry'     => array(
					'ID'        => $post_type,
					'post_type' => $post_type,
					'pad'       => '',
					'title'     => $post_type,
					'author'    => '',
					'comments'  => '&ndash;',
					'date'      => '',
					'order'     => $order,
				),
				'order'     => $order,
				'post_type' => $post_type,
				'parent'    => 0,
			);

			$this->inject_entry( $post_type_entry );

		}
	}

	/**
	 * Inject a post type into the Hierarchy
	 *
	 * @since 1.0
	 * @param $post_type string         The post type to inject
	 * @param $target_parent_id int     The parent in the Hierarchy
	 * @return string
	 */
	function inject_post_type( $post_type, $target_parent_id ) {

		// build the CPT Hierarchy entry
		$order = ! empty( $settings['post_types'][ $post_type ]['order'] ) ? intval( $settings['post_types'][ $post_type ]['order'] ) : 0;

		$base_pad = $this->determine_base_pad_for_post_type( $post_type, $target_parent_id );

		$post_type_entry = array(
			'entry'     => array(
				'ID'        => $post_type,
				'post_type' => $post_type,
				'pad'       => $base_pad,
				'title'     => $post_type,
				'author'    => '',
				'comments'  => '',
				'date'      => '',
				'order'     => $order,
			),
			'order'     => $order,
			'post_type' => $post_type,
			'parent'    => $target_parent_id,
		);

		$this->inject_entry( $post_type_entry );
	}

	/**
	 * Build a base pad out of a post type and target parent post ID
	 *
	 * @since 1.0
	 * @param $post_type string         The post type
	 * @param $target_parent_id string  The parent post ID
	 * @return string                   The pad itself
	 */
	function determine_base_pad_for_post_type( $post_type, $target_parent_id ) {
		global $wp_rewrite;

		// prevent an extra padding level for a customized slug front
		$pad = ! empty( $wp_rewrite->front ) && $this->posts_page && $this->posts_page == $this->context['ID'] && $post_type == 'post' ? '' : '&#8212; ';
		$base_pad = $this->get_pad( get_post( $target_parent_id ) ) . $pad;

		return $base_pad;
	}

	/**
	 * Inject a post type into the Hierarchy
	 *
	 * @since 1.0
	 * @param $post_type string     The post type to process
	 */
	function process_post_type( $post_type ) {

		global $wp_rewrite;

		if ( ! empty( $this->settings['post_types'][ $post_type ]['omit'] ) ) {
			return;
		}

		$target_parent_id = 0;
		$struct = isset( $wp_rewrite->extra_permastructs[ $post_type ]['struct'] ) ? $wp_rewrite->extra_permastructs[ $post_type ]['struct'] : '';

		if ( ! empty( $struct ) ) {
			$target_parent_id = $this->get_target_parent_id_from_slug( $struct );
		}

		$target_parent_id = $this->maybe_custom_slug_front( $target_parent_id, $post_type );
		$target_parent_id = $this->maybe_faux_parent( $target_parent_id, get_post_type_object( $post_type ) );

		if ( absint( $this->context['ID'] ) !== $target_parent_id ) {
			return;
		}

		$this->inject_post_type( $post_type, $target_parent_id );

		// maybe inject post type entries as well
		if ( empty( $this->settings['post_types'][ $post_type ]['entries'] ) ) {
			$base_pad = $this->determine_base_pad_for_post_type( $post_type, $target_parent_id );
			$this->process_post_type_posts( $post_type, $base_pad );
		}

		// remove this post type because we're going to append anything left over (e.g. post type added
		// after settings save) to the bottom of the Hierarchy
		unset( $this->post_types[ $post_type ] );
	}

	/**
	 * Process the posts for a post type and potentially inject them into the Hierarchy
	 *
	 * @since 1.0
	 * @param $post_type string     The post type to process
	 * @param $base_pad string      The base pad for the post type
	 */
	function process_post_type_posts( $post_type, $base_pad ) {

		$post_type_obj = get_post_type_object( $post_type );
		$cpt_posts = $post_type_obj->hierarchical ? $this->get_post_type_posts( $post_type, true ) : $this->get_post_type_posts( $post_type );

		if ( empty( $cpt_posts ) ) {
			return;
		}

		$cpt_posts_hierarchy = array();

		foreach ( $cpt_posts as $cpt_post ) {
			$cpt_post = get_post( absint( $cpt_post['ID'] ) );
			$author = get_userdata( $cpt_post->post_author );
			$pad = $base_pad . $this->get_pad( $cpt_post ) . '&#8212; ';

			$cpt_posts_hierarchy[] = array(
				'entry'     => array(
					'ID'        => $cpt_post->ID,
					'post_type' => $cpt_post->post_type,
					'pad'       => $pad,
					'title'     => $pad . $cpt_post->post_title,
					'author'    => $author->display_name,
					'comments'  => $cpt_post->comment_count,
					'date'      => date( get_option( 'date_format' ), strtotime( $cpt_post->post_date ) ),
					'order'     => $cpt_post->menu_order,
					'parent'    => $cpt_post->post_parent != 0 ? $cpt_post->post_parent : $post_type,
				),
				'order'     => $cpt_post->menu_order,
				'post_type' => $cpt_post->post_type,
				'parent'    => $cpt_post->post_parent != 0 ? $cpt_post->post_parent : $post_type,
			);
		}

		// append the CPT hierarchy to Hierarchy
		$this->hierarchy = array_merge( $this->hierarchy, $cpt_posts_hierarchy );
	}

	/**
	 * Determine a post id from a child slug
	 *
	 * @since 1.0
	 * @param $slug string  The slug to check against
	 * @return int          The parent post ID
	 */
	function get_target_parent_id_from_slug( $slug ) {
		$target_parent_id = 0;

		// break things up into URI segments and remove empty segments
		$cpt_archive_slug_segments = explode( '/', trim( $slug ) );
		$cpt_archive_slug_segments = array_filter( $cpt_archive_slug_segments, 'strlen' );

		// the last two segments represent the CPT archive & the slug, we need everything before that
		// if the array length isn't > 2 there is no possible parent
		if ( count( $cpt_archive_slug_segments ) > 2 ) {
			$parent_slug = implode( '/', array_slice( $cpt_archive_slug_segments, 0, count( $cpt_archive_slug_segments ) - 2 ) );
			$parent_page = get_page_by_path( $parent_slug );
			if ( isset( $parent_page->ID ) ) { // edge case: permalinks are out of date
				$target_parent_id = absint( $parent_page->ID );
			}
		}

		return $target_parent_id;
	}

	/**
	 * Properly decipher a parent ID from a custom slug front
	 *
	 * @since 1.0
	 * @param $parent_id int        Parent post ID
	 * @param $post_type string     Post type
	 * @return int                  Parent post ID
	 */
	function maybe_custom_slug_front( $parent_id, $post_type ) {
		global $wp_rewrite;

		// if the front of pretty permalinks have been customized
		if ( ! empty( $wp_rewrite->front ) && $this->posts_page && $this->posts_page == $this->context['ID'] && $post_type == 'post' ) {
			// we're working with posts & a customized permalink structure
			// which will interfere with the placement of this entry, we need to find our new parent
			$parent_id = $this->posts_page; // we've got the right padding, we just have the wrong parent
		}

		return $parent_id;
	}

	/**
	 * If a CPT is using a Page slug as an archive, properly retrieve that parent post ID
	 *
	 * @since 1.0
	 * @param $parent_id int        Post ID
	 * @param $post_type string     Post type
	 * @return int                  Parent post ID
	 */
	function maybe_faux_parent( $parent_id, $post_type ) {
		// if a post type wants to use a Page as an archive
		$faux_parent_id = 0;
		if ( ! empty( $post_type->rewrite['slug'] ) && empty( $post_type->has_archive ) ) {
			$faux_parent = get_page_by_path( $post_type->rewrite['slug'] );
		}
		if ( ! empty( $faux_parent ) ) {
			$faux_parent_id = absint( $faux_parent->ID );
			$faux_archive = get_permalink( $faux_parent_id );
		}
		if ( ! empty( $faux_archive  ) ) {
			$parent_id = $faux_parent_id;
		}

		return $parent_id;
	}

	/**
	 * Inject an entry into the Hierarchy
	 *
	 * @since 1.0
	 * @param $new_entry array  Hierarchy entry
	 * @todo refactor
	 */
	function inject_entry( $new_entry ) {
		$order          = intval( $new_entry['order'] );
		$last_index     = 0;
		$target_index   = -1;

		// loop through and find out where we need to insert
		for ( $i = 0; $i < count( $this->hierarchy ); $i++ ) {
			if ( isset( $this->hierarchy[$i]['parent'] ) && $this->hierarchy[$i]['parent'] == $new_entry['parent'] ) {
				if ( $this->hierarchy[$i]['post_type'] == 'page' ) { // otherwise we might inject within CTP entries
					// we only want to proceed when we're working with the first level
					$last_index = $i;
					if ( $order < intval( $this->hierarchy[$i]['order'] ) ) {
						// we've hit a ceiling
						$target_index = $last_index;
						break;
					}
				}
			}
		}

		// we might be dealing with the last entry
		if( $target_index === -1 ) {
			$target_index = count($this->hierarchy);
		}

		// we'll insert our new entry in the appropriate place
		array_splice( $this->hierarchy, $target_index, 0, array( $new_entry ) );
	}

	/**
	 * Generate a Hierarchy entry array
	 *
	 * @since 1.0
	 * @param $post WP_Post         Post object
	 * @param $post_type string     Post type
	 * @return array                Formatted Hierarchy entry array
	 */
	function generate_entry_args_for_post( $post, $post_type ) {
		return array(
			'entry'     => $post,
			'order'     => $post['order'],
			'parent'    => $post['parent'],
			'post_type' => $post_type,
		);
	}

	/**
	 * Generate a Hierarchy entry array for a Page
	 *
	 * @since 1.0
	 * @param $post WP_Post     WordPress Page
	 * @return array            Formatted Hierarchy array
	 */
	function generate_page_entry( $post ) {
		return $this->generate_entry_args_for_post( $post, 'page' );
	}

	/**
	 * Determine the pad count for a Hierarchy entry
	 *
	 * @since 1.0
	 * @param $post WP_Post     Post object
	 * @return int              How many levels deep we are
	 */
	function get_pad_count( $post ) {
		$level = 0;
		$post_parent = isset( $post->post_parent ) ? absint( $post->post_parent ) : 0;

		// if there's no parent there's no more to do
		if ( $post_parent < 1 ) {
			return $level;
		}

		while ( $post_parent > 0 ) {
			$parent = get_post( $post_parent );
			$level++;
			$post_parent = absint( $parent->post_parent );
		}

		return $level;
	}

	/**
	 * Build a pad out of the pad count
	 *
	 * @since 1.0
	 * @param $post WP_Post     The post object
	 * @return string           The pad string itself
	 */
	function get_pad( $post ) {
		return str_repeat( '&#8212; ', $this->get_pad_count( $post ) );
	}

	/**
	 * Get the entries for a post type
	 *
	 * @since 1.0
	 * @param string $post_type     Post type
	 * @param bool $hierarchical    Whether it's hierarchical
	 * @return array                Post objects
	 */
	function get_post_type_posts( $post_type = 'page', $hierarchical = false ) {

		$posts = array();

		$args = array(
			'post_type' => $post_type,
			'posts_per_page' => -1,
		);

		if ( $hierarchical ) {
			$args['sort_column'] = 'menu_order, post_title';
		}

		$base = $hierarchical ? get_pages( $args ) : get_posts( $args );

		if ( empty( $base ) ) {
			return $posts;
		}

		foreach ( $base as $post ) {
			$author = get_userdata( $post->post_author );
			$title = $this->get_pad( $post ) . $post->post_title;
			$posts[] = array(
				'ID'        => $post->ID,
				'post_type' => $post->post_type,
				'title'     => $title,
				'author'    => $author->display_name,
				'comments'  => $post->comment_count,
				'date'      => date( get_option( 'date_format' ), strtotime( $post->post_date ) ),
				'order'     => $post->menu_order,
				'parent'    => $post->post_parent,
			);
		}

		return $posts;
	}

	/**
	 * Setter for accurate post types within object
	 *
	 * @since 1.0
	 * @param $post_types
	 */
	function set_post_types( $post_types ) {
		$this->post_types = $post_types;
	}

}
