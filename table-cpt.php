<?php

if( !class_exists( 'WP_List_Table' ) )
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class HierarchyCPTTable extends WP_List_Table
{

    // constructor
    function __construct()
    {
        global $status, $page;

        parent::__construct( array(
                'singular'  => 'hierarchycpt',
                'plural'    => 'hierarchycpts',
                'ajax'      => false
            ) );
    }


    // default column
    function column_default( $item, $column_name )
    {
        switch( $column_name )
        {
            case 'title':
            case 'order':
            case 'show_entries':
                return $item[$column_name];
            default:
                return print_r( $item, true ); // worst case, output for debugging
        }
    }

    // defines our columns
    function get_columns()
    {
        $columns = array(
            'title'         => 'Custom Post Type',
            'show_entries'  => 'Show Entries',
            'order'         => 'Order'
        );
        return $columns;
    }

    // title column
    function column_title( $item )
    {
        return $item['title'];
    }


    function column_order( $item )
    {
        return '<input type="text" name="' . HIERARCHY_PREFIX . 'settings[post_types][' . $item['name'] . '][order]" id="' . HIERARCHY_PREFIX . 'settings[post_types][' . $item['name'] . '][order]" value="' . $item['order'] . '" class="small-text" />';
    }


    function column_show_entries( $item )
    {
        $input = '<input type="checkbox" name="' . HIERARCHY_PREFIX . 'settings[post_types][' . $item['name'] . '][show_entries]" id="' . HIERARCHY_PREFIX . 'settings[post_types][' . $item['name'] . '][show_entries]" value="1"';
        if( $item['show_entries'] )
            $input .= ' checked="checked"';
        $input .= '/>';

        return $input;
    }


    // prepare the data for display
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

    // we're overwriting the default because we don't want the nonce...
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
