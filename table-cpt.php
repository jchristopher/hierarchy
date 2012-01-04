<?php

if( !class_exists( 'WP_List_Table' ) )
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );


class HierarchyCPTTable extends WP_List_Table
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
                'singular'  => 'hierarchycpt',
                'plural'    => 'hierarchycpts',
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
        switch( $column_name )
        {
            case 'title':
            case 'order':
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
            'title'         => 'Custom Post Type',
            'order'         => 'Order'
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
        return $item['title'];
    }


    /**
     * Handle the Order column
     *
     * @package WordPress
     * @author Jonathan Christopher
     * @param $item
     * @return string
     */
    function column_order( $item )
    {
        return '<input type="text" name="' . HIERARCHY_PREFIX . 'settings[post_types][' . $item['name'] . '][order]" id="' . HIERARCHY_PREFIX . 'settings[post_types][' . $item['name'] . '][order]" value="' . $item['order'] . '" class="small-text" />';
    }


    /**
     * Preps the data for display in the table
     *
     * @package WordPress
     * @author Jonathan Christopher
     * @param array $cpts
     */
    function prepare_items( $cpts = array() )
    {
        // pagination
        $per_page   = 10000;

        // define our column headers
        $columns                = $this->get_columns();
        $hidden                 = array();
        $sortable               = $this->get_sortable_columns();
        $this->_column_headers  = array( $columns, $hidden, $sortable ); // actually set the data

        // define our data to be shown
        $data = $cpts;

        $current_page   = $this->get_pagenum();

        // the class doesn't handle pagination, so we need to trim the data to only the page we're viewing
        $data           = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

        // our data has been prepped (i.e. sorted) and we can now use it
        $this->items    = $data;
    }


    /**
     * Overwrite the default display() function because we don't want the nonce
     * as it will interfere with our Settings page
     *
     * @package WordPress
     * @author Jonathan Christopher
     **/
    function display()
    { ?>
        <table class="<?php echo implode( ' ', $this->get_table_classes() ); ?>" cellspacing="0">
            <thead>
                <tr>
                    <?php $this->print_column_headers(); ?>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <?php $this->print_column_headers( false ); ?>
                </tr>
            </tfoot>
            <tbody id="the-comment-list" class="list:comment">
                <?php $this->display_rows_or_placeholder(); ?>
            </tbody>
        </table>
    <?php
    }

}
