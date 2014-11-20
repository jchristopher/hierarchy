<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Hierarchy_Table_CPT
 * Display registered Custom Post Types in a WP_List_table
 *
 * @author Jonathan Christopher
 **/
class Hierarchy_Table_CPT extends WP_List_Table {

	/**
	 * The settings field prefix we're going to use
	 *
	 * @since 0.6
	 * @var string $prefix The settings field prefix we're going to use
	 */
	private $prefix;

	function __construct() {
		parent::__construct( array(
			'singular'  => 'hierarchycpt',
			'plural'    => 'hierarchycpts',
			'ajax'      => false
		) );
	}

	/**
	 * Setter for the settings fields prefix we need to use
	 *
	 * @since 0.6
	 * @param $prefix
	 */
	public function set_prefix( $prefix ) {
		$this->prefix = $prefix;
	}

	public function set_post_types( $post_types ) {
		$this->post_types = $post_types;
	}


    /**
     * Default column handler if there's no specific handler
     *
     * @author Jonathan Christopher
     * @param $item
     * @param $column_name
     * @return mixed
     */
    function column_default( $item, $column_name ) {
        switch( $column_name ) {
            case 'title':
	        case 'no_new':
            case 'order':
            case 'omit':
            case 'entries':
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
            'title'         => 'Custom Post Type',
	        'no_new'        => 'Prevent New',
            'entries'       => 'Show Entries',
            'omit'          => 'Omit',
            'order'         => 'Order',
        );

        return $columns;
    }


    /**
     * Handle the Title column
     *
     * @param $item
     * @return string
     */
    function column_title( $item ) {
        return $item['title'];
    }


    /**
     * Handle the Order column
     *
     * @param $item
     * @return string
     */
    function column_order( $item ) {
        return '<input type="text" name="' . esc_attr( $this->prefix ) . 'settings[post_types][' . esc_attr( $item['name'] ) . '][order]" id="' . esc_attr( $this->prefix ) . 'settings[post_types][' . esc_attr( $item['name'] ) . '][order]" value="' . absint( $item['order'] ) . '" class="small-text" />';
    }


	/**
	 * Handle the No New column
	 *
	 * @param $item
	 * @return string
	 */
	function column_no_new( $item ) {
		$checked = ! empty( $item['no_new'] ) ? ' checked="checked"' : '';
		return '<input type="checkbox" name="' . esc_attr( $this->prefix ) . 'settings[post_types][' . esc_attr( $item['name'] ) . '][no_new]" id="' . esc_attr( $this->prefix ) . 'settings[post_types][' . esc_attr( $item['name'] ) . '][no_new]" value="1"' . $checked . ' />';
	}


    /**
     * Handle the Omit column
     *
     * @param $item
     * @return string
     */
    function column_omit( $item ) {
        $checked = ! empty( $item['omit'] ) ? ' checked="checked"' : '';
        return '<input type="checkbox" name="' . esc_attr( $this->prefix ) . 'settings[post_types][' . esc_attr( $item['name'] ) . '][omit]" id="' . esc_attr( $this->prefix ) . 'settings[post_types][' . esc_attr( $item['name'] ) . '][omit]" value="1"' . $checked . ' />';
    }


    /**
     * Handle the Entries column
     *
     * @param $item
     * @return string
     */
    function column_entries( $item ) {
        $checked = ! empty( $item['entries'] ) ? ' checked="checked"' : '';
        return '<input type="checkbox" name="' . esc_attr( $this->prefix ) . 'settings[post_types][' . esc_attr( $item['name'] ) . '][entries]" id="' . esc_attr( $this->prefix ) . 'settings[post_types][' . esc_attr( $item['name'] ) . '][entries]" value="1"' . $checked . ' />';
    }


    /**
     * Preps the data for display in the table
     *
     * @param array $cpts
     */
    function prepare_items( $cpts = array() ) {

        // define our column headers
        $columns                = $this->get_columns();
        $hidden                 = array();
        $sortable               = $this->get_sortable_columns();
        $this->_column_headers  = array( $columns, $hidden, $sortable ); // actually set the data

        // define our data to be shown
        $data = $cpts;

        // our data has been prepped (i.e. sorted) and we can now use it
        $this->items = $data;
    }


    /**
     * Overwrite the default display() function because we don't want the nonce
     * as it will interfere with our Settings page
     **/
    function display() { ?>
        <table class="<?php echo esc_attr( implode( ' ', $this->get_table_classes() ) ); ?>" cellspacing="0">
            <thead>
                <tr><?php $this->print_column_headers(); ?></tr>
            </thead>
            <tfoot>
                <tr><?php $this->print_column_headers( false ); ?></tr>
            </tfoot>
            <tbody id="the-comment-list" class="list:comment">
                <?php $this->display_rows_or_placeholder(); ?>
            </tbody>
        </table>
    <?php }

}
