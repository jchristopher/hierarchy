<?php

if( !class_exists( 'WP_List_Table' ) )
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class HierarchyTable extends WP_List_Table
{
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
            'title'     => 'Title',
            'author'    => 'Author',
            'comments'  => '<span><span class="vers"><img src="' . get_admin_url() . 'images/comment-grey-bubble.png" alt="Comments" /></span></span>',
            'date'      => 'Date'
        );
        return $columns;
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
        $actions = array();

        $item = $item['entry'];

        $title = $item['title'];

        // we need to make this contextual as per the content type
        if( is_int( $item['ID'] ) )
        {
            // it's an actual post
            $edit_url = get_admin_url() . 'post.php?post=' . $item['ID'] . '&action=edit';

            $actions['edit'] = '<a href="' . $edit_url . '">Edit</a>';
            $actions['view'] = '<a href="/?page_id=' . $item['ID'] . '">View</a>';
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

            $edit_url = get_admin_url() . 'edit.php?post_type=' . $cpt->name;

            $actions['edit'] = '<a href="' . $edit_url . '">Edit</a>';

            // let's check to see if we in fact have a CPT archive to use for the View link
            if( $cpt->has_archive )
                $actions['view'] = '<a href="' . get_post_type_archive_link( $cpt->name ) . '">View</a>';

            $add_url = get_admin_url() . 'post-new.php?post_type=' . $cpt->name;
            $actions['add'] = '<a href="' . $add_url . '">Add New</a>';

        }

        // return the title contents
        return '<strong><a class="row-title" href="' . $edit_url . '">' . $title . '</a></strong>' . $this->row_actions( $actions );
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

        return '<div class="post-com-count-wrapper"><a href="#" class="post-com-count"><span class="comment-count">' . $item['comments'] . '</span></a></div>';
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
        $per_page   = -1;       // disable pagination for now

        // define our column headers
        $columns                = $this->get_columns();
        $hidden                 = array();
        $sortable               = $this->get_sortable_columns();
        $this->_column_headers  = array($columns, $hidden, $sortable); // actually set the data

        // define our data to be shown
        $data = $hierarchy;

        // find out what page we're currently on and get pagination set up
        $current_page   = $this->get_pagenum();
        $total_items    = count($data);

        // the class doesn't handle pagination, so we need to trim the data to only the page we're viewing
        $data           = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

        // our data has been prepped (i.e. sorted) and we can now use it
        $this->items    = $data;

        // register our pagination options and calculations
        $this->set_pagination_args( array(
                'total_items' => $total_items,                  // WE have to calculate the total number of items
                'per_page'    => $per_page,                     // WE have to determine how many items to show on a page
                'total_pages' => ceil($total_items/$per_page)   // WE have to calculate the total number of pages
            )
        );

    }
}
