<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}


/**
 * HierarchyTable
 * Display Hierarchy in a WP_List_table
 *
 * @package WordPress
 * @author Jonathan Christopher
 **/
class Hierarchy_Table extends WP_List_Table {
    /**
     * Constructor
     *
     * @package WordPress
     * @author Jonathan Christopher
     **/
    function __construct()
    {
        global $status, $page;

        parent::__construct( array(
                'singular'  => 'hierarchyentry',
                'plural'    => 'hierarchyentries',
                'ajax'      => false
            ) );
    }


    /**
     * Default column handler if there's no specific handler
     *
     * @package WordPress
     * @author Jonathan Christopher
     * @param $item
     * @param $column_name
     * @return mixed
     */
    function column_default( $item, $column_name )
    {
        $item = $item['entry'];

        switch( $column_name )
        {
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
     * @package WordPress
     * @author Jonathan Christopher
     *
     * @return array
     */
    function get_columns()
    {
        $columns = array(
            'icon'      => '',
            'title'     => 'Title',
            'author'    => 'Author',
            'comments'  => '<span><span class="vers"><img src="' . get_admin_url() . 'images/comment-grey-bubble.png" alt="Comments" /></span></span>',
            'date'      => 'Date'
        );
        return $columns;
    }


    /**
     * Handle the Icon column
     *
     * @package WordPress
     * @author Jonathan Christopher
     * @param $item
     * @return
     */
    function column_icon( $item )
    {
        $icon = '';

        if( isset( $item['post_type'] ) )
        {
            $post_type = get_post_type_object( $item['post_type'] );

            // if we have a hierarchical post_type we'll want to use the Page icon instead of the Post icon
            if( $post_type->hierarchical )
                $item['post_type'] = 'page';

            if( !empty( $post_type->menu_icon ) )
            {
                // we'll use the user-defined menu_icon if possible
                $icon = $post_type->menu_icon;
            }
            else
            {
                switch( $item['post_type'] )
                {
                    case 'page':
                        $icon = 'icon-page.png';
                        break;

                    case 'post':
                        $icon = 'icon-post.png';
                        break;

                    default: // custom post type
                        $icon = 'icon-post.png';
                        break;
                }

                $icon = HIERARCHY_URL . '/images/' . $icon;
            }
        }

        return '<img src="' . $icon . '" alt="Post icon" />';
    }


    /**
     * Handle the Title column
     *
     * @package WordPress
     * @author Jonathan Christopher
     * @param $item
     * @return
     */
    function column_title( $item )
    {
        // build row actions
        $actions        = array();
        $item           = $item['entry'];
        $title          = $item['title'];

        $count          = 0;
        $count_label    = '';

        // we need to make this contextual as per the content type
        if( is_int( $item['ID'] ) )
        {
            // it's an actual post
            $edit_url = get_admin_url() . 'post.php?post=' . $item['ID'] . '&action=edit';

            $actions['edit'] = '<a href="' . $edit_url . '">Edit</a>';
            $actions['view'] = '<span class="view"><a href="' . get_bloginfo( 'url' ) . '/?page_id=' . $item['ID'] . '" rel="permalink">View</a></span>';
        }
        else
        {
            // it's a CPT index
            $post_types = Hierarchy::get_post_types();
            $cpt = null;
            foreach( $post_types as $post_type )
            {
                if( $post_type->name == $item['ID'] )
                {
                    $cpt = $post_type;
                    break;
                }
            }

            $title = $item['pad'] . $cpt->labels->name;

            $posts_page = ( 'page' == get_option( 'show_on_front' ) ) ? intval( get_option( 'page_for_posts' ) ) : false;

            // set the Posts label to be the posts page Title
            if( $cpt->name == 'post' && !empty( $posts_page ) )
            {
                $title = $item['pad'] . get_the_title( $posts_page );
            }

            $title .= ' &raquo;';

            $edit_url = get_admin_url() . 'edit.php?post_type=' . $cpt->name;

            $actions['edit'] = '<a href="' . $edit_url . '">Edit</a>';

            // set entry count
            $counts = wp_count_posts( $cpt->name );

            // we've got counts broken out by status, so let's get a comprehensive number
            if( isset( $counts->publish ) ) $count += (int) $counts->publish;
            if( isset( $counts->future ) )  $count += (int) $counts->future;
            if( isset( $counts->draft ) )   $count += (int) $counts->draft;
            if( isset( $counts->pending ) ) $count += (int) $counts->pending;
            if( isset( $counts->private ) ) $count += (int) $counts->private;
            if( isset( $counts->inherit ) ) $count += (int) $counts->inherit;

            $count_label .= ' (' . $count . ' ';
            $count_label .= ( $count == 1 ) ? __( $cpt->labels->singular_name, 'hierarchy' ) : __( $cpt->labels->name, 'hierarchy' );
            $count_label .= ') ';

            // let's check to see if we in fact have a CPT archive to use for the View link
            if( $cpt->has_archive || $cpt->name == 'post' && $posts_page )
            {
                if( $cpt->name == 'post' && $posts_page )
                {
                    $actions['view'] = '<a href="' . get_permalink( $posts_page) . '">View</a>';
                }
                else
                {
                    $actions['view'] = '<a href="' . get_post_type_archive_link( $cpt->name ) . '">View</a>';
                }
            }

            $add_url = get_admin_url() . 'post-new.php?post_type=' . $cpt->name;
            $actions['add'] = '<a href="' . $add_url . '">Add New</a>';

            // let's see if we need to add any taxonomies
            $taxonomies = Hierarchy::get_taxonomies_for_post_type( $cpt->name );

            if( !empty( $taxonomies ) )
            {
                foreach( $taxonomies as $taxonomy )
                {
                    if( $taxonomy->name != 'post_format' )
                    {
                        $tax_edit_url = get_admin_url() . 'edit-tags.php?taxonomy=' . $taxonomy->name;
                        if( $cpt->name != 'post' )
                        {
                            $tax_edit_url .= '&post_type=' . $cpt->name;
                        }
                        $actions['tax_' . $taxonomy->name] = '<a href="' . $tax_edit_url . '">' . $taxonomy->labels->name . '</a>';
                    }
                }
            }
        }




        // return the title contents
        $final_title = '<strong><a class="row-title" href="' . $edit_url . '">' . $title . '</a></strong>';
        if( $count ) $final_title .= $count_label;
        $final_title .= $this->row_actions( $actions );

        return $final_title;
    }


    /**
     * Handle the Comments column
     *
     * @package WordPress
     * @author Jonathan Christopher
     * @param $item
     * @return string
     */
    function column_comments( $item )
    {
        $item = $item['entry'];

        $column = '';

        if( is_numeric( $item['ID'] ) ) // only if applicable
        {
            $column = '<div class="post-com-count-wrapper"><a class="post-com-count" style="cursor:default;"><span class="comment-count">' . $item['comments'] . '</span></a></div>';
        }

        return $column;
    }


    /**
     * Preps the data for display in the table
     *
     * @package WordPress
     * @author Jonathan Christopher
     * @param array $cpts
     */
    function prepare_items( $hierarchy = null )
    {
        // pagination
        if( defined( 'HIERARCHY_PREFIX' ) )
        {
            $settings = get_option( HIERARCHY_PREFIX . 'settings' );
            $per_page = intval( $settings['per_page'] );
        }
        else
        {
            $per_page = 100;
        }

        // define our column headers
        $columns                = $this->get_columns();
        $hidden                 = array();
        $sortable               = $this->get_sortable_columns();
        $this->_column_headers  = array( $columns, $hidden, $sortable ); // actually set the data

        // define our data to be shown
        $data = $hierarchy;

        // find out what page we're currently on and get pagination set up
        $current_page   = $this->get_pagenum();
        $total_items    = count( $data );

        if( $per_page > 0 )
        {
            // if we do want pagination, we'll just split our array
            // the class doesn't handle pagination, so we need to trim the data to only the page we're viewing
            $data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
        }

        // our data has been prepped (i.e. sorted) and we can now use it
        $this->items = $data;

        if( $per_page > 0 )
        {
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
