<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Hierarchy_Table
 * Display Hierarchy in a WP_List_table
 **/
class Hierarchy_Table extends WP_List_Table {

	/**
	 * Plugin URL
	 *
	 * @var $url string     Plugin URL
	 */
	private $url;

	/**
	 * Registered post types
	 *
	 * @var array   Registered post types
	 */
	private $post_types = array();

	/**
	 * Hierarchy settings
	 *
	 * @var array   Hierarchy settings
	 */
	private $settings = array();


	function __construct() {
		parent::__construct( array(
			'singular'  => 'hierarchyentry',
			'plural'    => 'hierarchyentries',
			'ajax'      => false
		) );
	}

	/**
	 * Setter the plugin URL
	 *
	 * @since 0.6
	 * @param $url string    Plugin URL
	 */
	public function set_url( $url ) {
		$this->url = esc_url( $url );
	}

	/**
	 * Setter for registered post types
	 *
	 * @since 0.6
	 * @param $post_types array    Registered post types
	 */
	public function set_post_types( $post_types ) {
		$this->post_types = $post_types;
	}

	/**
	 * Setter for Hierarchy settings
	 *
	 * @since 0.6
	 * @param $settings array       Hierarchy settings
	 */
	public function set_settings( $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Default column handler if there's no specific handler
	 *
	 * @param $item
	 * @param $column_name
	 * @return mixed
	 */
	function column_default( $item, $column_name ) {
		$item = $item['entry'];

		switch( $column_name ) {
			case 'title':
			case 'author':
			case 'comments':
			case 'date':
			case 'icon':
				return $item[$column_name];
			default:
				return print_r( $item, true ); // worst case, output for debugging
		}
	}

	/**
	 * Define the columns we plan on using
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'icon'      => '',
			'title'     => __( 'Title', 'hierarchy' ),
			'author'    => __( 'Author', 'hierarchy' ),
			'comments'  => '<span><span class="vers"><span title="Comments" class="comment-grey-bubble"></span></span></span>',
			'date'      => __( 'Date', 'hierarchy' )
		);

		return $columns;
    }

	/**
	 * Get the Dashicon for a post type
	 *
	 * @since 1.0
	 * @param $post_type
	 * @return string
	 */
	function get_post_type_icon( $post_type ) {
		$icon = '<span class="dashicons dashicons-admin-post"></span>';

		switch ( $post_type->name ) {
			case 'page':
				$icon = '<span class="dashicons dashicons-admin-page"></span>';
				break;
			default: // custom post type
				if ( false !== strpos( $post_type->menu_icon, 'dashicons' ) )  {
					$icon = '<span class="dashicons ' . esc_attr( $post_type->menu_icon ) . '"></span>';
				}
				break;
		}

		return $icon;
	}

    /**
     * Handle the Icon column
     *
     * @param $item
     * @return string  HTML for Dashicon for post type
     */
    function column_icon( $item ) {

	    if ( ! isset( $item['post_type'] ) ) {
		    $item['post_type'] = 'post';
		}

	    $post_type = get_post_type_object( $item['post_type'] );
		$icon = $this->get_post_type_icon( $post_type );

        return $icon;
    }

	/**
	 * Generate markup for actions row for a single post
	 *
	 * @since 1.0
	 * @param $item array   Hierarchy row item
	 * @return array        Actions marked up as HTML
	 */
	function get_actions_for_post( $item ) {
		$actions = array();

		$post_type_object = get_post_type_object( $item['post_type'] );

		if ( current_user_can( 'edit_' . $post_type_object->capability_type, $item['ID'] ) ) {
			$edit_url = $this->get_item_edit_url($item);
			$actions['edit'] = '<a href="' . esc_url($edit_url) . '">Edit</a>';
		}

		$view_url = get_bloginfo( 'url' ) . '/?page_id=' . absint( $item['ID'] );
		$actions['view'] = '<span class="view"><a href="' . esc_url( $view_url ) . '" rel="permalink">' . __( 'View', 'hierarchy' ) . '</a></span>';

		return $actions;
	}

	/**
	 * Generate markup for actions row for a post type
	 *
	 * @since 1.0
	 * @param $item array   Hierarchy row item
	 * @return array        Available actions
	 */
	function get_actions_for_post_type( $item ) {
		$cpt = null;
		$actions = array();

		$posts_page = ( 'page' == get_option( 'show_on_front' ) ) ? intval( get_option( 'page_for_posts' ) ) : false;

		foreach( $this->post_types as $post_type ) {
			if( $post_type == $item['ID'] )  {
				$cpt = get_post_type_object( $post_type );
				break;
			}
		}

		$post_type = $cpt->name;

		if ( current_user_can( 'edit_' . $cpt->capability_type . 's', $item['ID'] ) ) {
			$edit_url = $this->get_post_type_edit_url($item);
			$actions['edit'] = '<a href="' . $edit_url . '">' . __( 'Edit', 'hierarchy' ) . '</a>';
		}

		// let's check to see if we in fact have a CPT archive to use for the View link
		if( $cpt->has_archive || $cpt->name == 'post' && $posts_page ) {
			if( $cpt->name == 'post' && $posts_page ) {
				$actions['view'] = '<a href="' . get_permalink( $posts_page) . '">View</a>';
			} else {
				$actions['view'] = '<a href="' . get_post_type_archive_link( $cpt->name ) . '">View</a>';
			}
		}

		// only include Add link if applicable
		if ( current_user_can( 'edit_' . $cpt->capability_type . 's', $item['ID'] ) && empty( $this->settings['post_types'][ $post_type ]['no_new'] ) ) {
			$add_url = get_admin_url() . 'post-new.php?post_type=' . $cpt->name;
			$actions['add'] = '<a href="' . $add_url . '">Add New</a>';
		}

		// let's see if we need to add any taxonomies
		$args = array(
			'public'        => true,
			'object_type'   => array( $cpt->name )
		);
		$output = 'objects';
		$operator = 'and';
		$taxonomies = get_taxonomies( $args, $output, $operator );

		if ( ! empty( $taxonomies ) ) {
			foreach( $taxonomies as $taxonomy ) {
				if( $taxonomy->name != 'post_format' ) {
					$tax_edit_url = get_admin_url() . 'edit-tags.php?taxonomy=' . $taxonomy->name;
					if( $cpt->name != 'post' ) {
						$tax_edit_url .= '&post_type=' . $cpt->name;
					}
					$actions['tax_' . $taxonomy->name] = '<a href="' . esc_url( $tax_edit_url ) . '">' . esc_html( $taxonomy->labels->name ) . '</a>';
				}
			}
		}

		return $actions;
	}

	/**
	 * Build an edit URL from a Hierarchy row item
	 *
	 * @since 1.0
	 * @param $item array       Hierarchy row item
	 * @return string           Edit URL for row item
	 */
	function get_item_edit_url( $item ) {
		return esc_url( get_admin_url() . 'post.php?post=' . absint( $item['ID'] ) . '&action=edit' );
	}

	/**
	 * Build edit URL for a post type from a Hierarchy row item
	 *
	 * @since 1.0
	 * @param $item array   Hierarchy row item
	 * @return string       Edit URL
	 */
	function get_post_type_edit_url( $item ) {
		$cpt = null;
		foreach( $this->post_types as $post_type ) {
			if( $post_type == $item['ID'] )  {
				$cpt = get_post_type_object( $post_type );
				break;
			}
		}

		return esc_url( get_admin_url() . 'edit.php?post_type=' . $cpt->name );
	}

	/**
	 * Return the title for a Hierarchy row item
	 *
	 * @param $item array   Hierarchy row item
	 * @return string       Title
	 */
	function get_title_for_post_item( $item ) {
		return $item['title'];
	}

	/**
	 * Build a title string for a post type Hierarchy row item
	 *
	 * @since 1.0
	 * @param $item array   Hierarchy row item
	 * @return string       Title
	 */
	function get_title_for_post_type_item( $item ) {
		$cpt = null;
		foreach( $this->post_types as $post_type ) {
			if( $post_type == $item['ID'] )  {
				$cpt = get_post_type_object( $post_type );
				break;
			}
		}

		$title = $item['pad'] . $cpt->labels->name;
		$posts_page = ( 'page' == get_option( 'show_on_front' ) ) ? intval( get_option( 'page_for_posts' ) ) : false;

		// set the Posts label to be the posts page Title
		if ( $cpt->name == 'post' && ! empty( $posts_page ) ) {
			$title = $item['pad'] . get_the_title( $posts_page );
		}

		$title .= ' &raquo;';

		return $title;
	}

	/**
	 * Retrieve a total post count for a post type from a Hierarchy row item
	 *
	 * @since 1.0
	 * @param $item array   Hierarchy row item
	 * @return string       Post count for post type
	 */
	function get_count_for_post_type_item( $item ) {
		$cpt = null;
		foreach( $this->post_types as $post_type ) {
			if( $post_type == $item['ID'] )  {
				$cpt = get_post_type_object( $post_type );
				break;
			}
		}

		$count = 0;
		$counts = wp_count_posts( $cpt->name );

		// we've got counts broken out by status, so let's get a comprehensive number
		if( isset( $counts->publish ) ) $count += (int) $counts->publish;
		if( isset( $counts->future ) )  $count += (int) $counts->future;
		if( isset( $counts->draft ) )   $count += (int) $counts->draft;
		if( isset( $counts->pending ) ) $count += (int) $counts->pending;
		if( isset( $counts->private ) ) $count += (int) $counts->private;
		if( isset( $counts->inherit ) ) $count += (int) $counts->inherit;

		$count_label = ' (' . $count . ' ';
		$count_label .= ( $count == 1 ) ? __( $cpt->labels->singular_name, 'hierarchy' ) : __( $cpt->labels->name, 'hierarchy' );
		$count_label .= ') ';

		return $count_label;
	}

    /**
     * Handle the Title column
     *
     * @param $item
     * @return string   Proper title for the column
     */
    function column_title( $item ) {

	    $post_type_object = get_post_type_object( $item['post_type'] );
	    $item = $item['entry'];

	    $edit_url = is_int( $item['ID'] ) ? $this->get_item_edit_url( $item ) : $this->get_post_type_edit_url( $item );
	    $title = is_int( $item['ID'] ) ? $this->get_title_for_post_item( $item ) : $this->get_title_for_post_type_item( $item );
	    $actions = is_int( $item['ID'] ) ? $this->get_actions_for_post( $item ) : $this->get_actions_for_post_type( $item );

        // return the title contents
	    if ( current_user_can( 'edit_' . $post_type_object->capability_type, $item['ID'] ) ) {
		    $final_title = '<a class="row-title" href="' . esc_url( $edit_url ) . '">' . esc_html( $title ) . '</a>';
	    } else {
		    $final_title = esc_html( $title );
	    }

	    $final_title = '<strong>' . $final_title . '</strong>';


	    if( ! is_int( $item['ID'] ) ) {
		    $final_title .= $this->get_count_for_post_type_item( $item );
	    }
        $final_title .= $this->row_actions( $actions );

	    $final_markup = '';
	    if ( ! empty( $post_type ) ) {
		    $final_markup .= '<div class="hierarchy-row-post-type hierarchy-row-post-type-' . $post_type . '">';
	    }

	    $final_markup .= $final_title;

	    if ( ! empty( $post_type ) ) {
		    $final_markup .= '</div>';
	    }

        return  $final_markup;
    }

    /**
     * Handle the Comments column
     *
     * @param $item
     * @return string
     */
    function column_comments( $item ) {
        $item = $item['entry'];
        $column = '';
        if ( is_numeric( $item['ID'] ) ) {
            $column = '<div class="post-com-count-wrapper"><a class="post-com-count" style="cursor:default;"><span class="comment-count">' . absint( $item['comments'] ) . '</span></a></div>';
        }

        return $column;
    }

    /**
     * Preps the data for display in the table
     *
     * @param array $hierarchy  The existing Hierarchy
     */
    function prepare_items( $hierarchy = null ) {
        // pagination
        $per_page = intval( $this->settings['per_page'] );

        // define our column headers
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable ); // actually set the data

        // define our data to be shown
        $data = $hierarchy;

        // find out what page we're currently on and get pagination set up
        $current_page   = $this->get_pagenum();
        $total_items    = count( $data );

        if( $per_page > 0 ) {
            // if we do want pagination, we'll just split our array
            // the class doesn't handle pagination, so we need to trim the data to only the page we're viewing
            $data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
        }

        // our data has been prepped (i.e. sorted) and we can now use it
        $this->items = $data;

        if( $per_page > 0 ) {
            // register our pagination options and calculations
            $this->set_pagination_args( array(
                    'total_items' => $total_items,
                    'per_page'    => $per_page,
                    'total_pages' => ceil( $total_items / $per_page ),
                )
            );
        }

    }
}
