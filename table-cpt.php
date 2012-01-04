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
            case 'cptparent':
            case 'order':
                return $item[$column_name];
            default:
                return print_r( $item, true ); // worst case, output for debugging
        }
    }

    // defines our columns
    function get_columns()
    {
        $columns = array(
            'title'     => 'Custom Post Type',
            'cptparent' => 'Parent',
            'order'     => 'Order'
        );
        return $columns;
    }

    // title column
    function column_title( $item )
    {
        return $item['title'];
    }

    function column_cptparent( $item )
    {
        if( is_array( $item['parents'] ) && count( $item['parents'] ) )
        {
            $pages = '';
            foreach( $item['parents'] as $parent )
            {
                $pages .= '<option value="' . $parent['ID'] . '"';
                $pages .= ( intval( $parent['ID'] ) === intval( $item['cptparent'] ) ) ? ' selected="selected"' : '' ;
                $pages .= '>' . $parent['title'] . '</option>';

            }
        }

        return '<select name="' . HIERARCHY_PREFIX . 'settings[post_types][' . $item['name'] . '][parent]"><option value="0">&mdash; No Parent &mdash;' . $pages . '</option></select>';
    }

    function column_order( $item )
    {
        return '<input type="text" name="' . HIERARCHY_PREFIX . 'settings[post_types][' . $item['name'] . '][order]" id="' . HIERARCHY_PREFIX . 'settings[post_types][' . $item['name'] . '][order]" value="' . $item['order'] . '" class="small-text" />';
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

        // HERE IS WHERE THE QUERY GOES

        // find out what page we're currently on and get pagination set up
        $current_page   = $this->get_pagenum();
        // $total_items    = count( $data );

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
