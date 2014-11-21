<?php

/**
 * Hierarchy
 * Properly structure your Pages, Posts, and Custom Post Types
 *
 * @package WordPress
 * @author Jonathan Christopher
 **/
class Hierarchy {

	/**
	 * Plugin directory on disk
	 *
	 * @since 0.6
	 * @var string  Plugin directory on disk
	 */
	protected $dir;

	/**
	 * URL of plugin
	 *
	 * @since 0.6
	 * @var string  URL of plugin
	 */
	protected $url;

	/**
	 * Hierarchy version
	 *
	 * @since 0.6
	 * @var string  Hierarchy version
	 */
	protected $version;

	/**
	 * Plugin name
	 *
	 * @since 0.6
	 * @var string  Plugin name
	 */
	protected $plugin_name;

	/**
	 * Input field and settings prefix
	 *
	 * @since 0.6
	 * @var string  Input field and settings prefix
	 */
	protected $prefix;

	/**
	 * Hierarchy settings
	 *
	 * @since 0.6
	 * @var array   Hierarchy settings
	 */
	protected $settings;

	/**
	 * Registered post types
	 *
	 * @since 0.6
	 * @var array   Registered post types
	 */
	protected $post_types;

	/**
	 * The WordPress capability Hierarchy should use
	 *
	 * @since 0.6
	 * @var mixed|void  The WordPress capability Hierarchy should use
	 */
	protected $capability;

	/**
	 * Admin Menu position for Hierarchy
	 *
	 * @since 0.6
	 * @var int     Admin Menu position for Hierarchy
	 */
	protected $menu_position = 3;

	/**
	 * CONSTRUCT
	 */
	function __construct() {
		$this->plugin_name  = 'hierarchy';
		$this->prefix       = '_iti_hierarchy_';
		$this->version      = '1.0.3';
		$this->dir          = plugin_dir_path( dirname( __FILE__ ) );
		$this->url          = plugins_url( 'hierarchy', $this->dir );

		// define who can see the Hierarchy Admin Menu entry
		// using edit_posts because that's the lowest barrier to entry for editing
		$this->capability   = apply_filters( 'hierarchy_capability', 'edit_posts' );

		// initialize settings
		if ( ! $this->settings = get_option( $this->prefix . 'settings' ) ) {
			$this->settings = array(
				'per_page' => -1,
				'version' => $this->version,
				'hidden_from_admin_menu' => array()
			);

			add_option( $this->prefix . 'settings', $this->settings, '', 'no' );
		}
	}

	/**
	 * Initializer, fired in plugin bootloader
	 *
	 * @since 0.6
	 */
	function init() {
		$this->load_dependencies();
		$this->init_settings();
		$this->set_locale();
		$this->add_hooks();
	}

	/**
	 * Get registered post types
	 *
	 * @since 0.6
	 * @return array    Get registered post types
	 */
	function get_post_types() {
		$args = array(
			'public'    => true,
			'show_ui'   => true,
		);

		return get_post_types( $args, 'names', 'AND' );
	}

	/**
	 * Initialize Hierarchy Settings class
	 *
	 * @since 0.6
	 */
	function init_settings() {
		$settings = new Hierarchy_Settings();
		$settings->init();
	}

	/**
	 * Getter for the plugin name
	 *
	 * @since 0.6
	 * @return string   The plugin name
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Require all dependencies
	 *
	 * @since 0.6
	 */
	function load_dependencies() {
		require_once $this->dir . 'includes/class-hierarchy-table.php';
		require_once $this->dir . 'includes/class-hierarchy-table-cpt.php';
		require_once $this->dir . 'includes/class-hierarchy-settings.php';
		require_once $this->dir . 'includes/class-hierarchy-i18n.php';
		require_once $this->dir . 'includes/class-hierarchy-factory.php';
	}

	/**
	 * Set plugin locale
	 *
	 * @since 0.6
	 */
	function set_locale() {
		$plugin_i18n = new Hierarchy_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );
	}

	/**
	 * Add WordPress core hooks
	 *
	 * @since 0.6
	 */
	function add_hooks() {
		add_action( 'admin_menu', array( $this, 'hijack_admin_menu' ), 99999 );
		add_action( 'admin_init', array( $this, 'retrieve_post_types' ), 999 );
		add_action( 'admin_head', array( $this, 'maybe_hide_add_new_button' ) ) ;

		add_filter( 'plugin_row_meta',  array( $this, 'filter_plugin_row_meta' ), 10, 2 );
	}

	/**
	 * Hide the 'Add New' button for post types where 'Prevent New' is enabled
	 *
	 * @since 0.6
	 */
	function maybe_hide_add_new_button() {
		$screen = get_current_screen();
		if ( isset( $this->settings['post_types'] ) && is_array( $this->settings['post_types'] ) ) {
			foreach ( $this->settings['post_types'] as $post_type => $post_type_settings ) {
				if ( 'edit-' . $post_type == $screen->id && ! empty( $post_type_settings['no_new'] ) ) {
					?>
						<style type="text/css">
							.add-new-h2 { display:none; }
						</style>
					<?php
				}
			}
		}
	}

	/**
	 * Callback to retrieve and store all registered post types
	 *
	 * @since 0.6
	 */
	function retrieve_post_types() {
		$this->post_types = $this->get_post_types();
	}

	/**
	 * Add link to meta row for plugin
	 *
	 * @since 0.6
	 * @param $plugin_meta
	 * @param $plugin_file
	 * @return mixed
	 */
	function filter_plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( strstr( $plugin_file, 'hierarchy' ) ) {
			$plugin_meta[3] = 'Courtesy of <a title="Iron to Iron" href="http://irontoiron.com/">Iron to Iron</a>';
		}

		return $plugin_meta;
	}

	/**
	 * Determine which Admin Menu position we're going to utilize
	 *
	 * @since 0.6
	 */
	function set_menu_position() {
		global $menu;

		// ideally we're in position 3, just below the Dashboard but above the separator
		$position = (int) apply_filters( 'hierarchy_position', $this->menu_position );

		// we don't want to override an existing menu item
		while ( isset( $menu[ $position ] ) ) {
			$position++;
		}

		$this->menu_position = $position;
	}

	/**
	 * Add Hierarchy to the Admin Menu
	 *
	 * @since 0.6
	 */
	function add_menu_item() {
		$this->set_menu_position();

		$menu_icon = 'dashicons-category';
		$menu_label = apply_filters( 'hierarchy_menu_label', __( 'Content', 'hierarchy' ) );

		add_menu_page(
			$menu_label,
			$menu_label,
			$this->capability,
			'hierarchy',
			array( $this, 'show_hierarchy' ),
			$menu_icon,
			$this->menu_position
		);
	}

	/**
	 * Get the Admin Menu slug for a submitted post type
	 *
	 * @since 0.6
	 * @param $post_type string     The post type for which you want the slug
	 * @return string               The Menu item slug
	 */
	function get_menu_slug_from_post_type( $post_type ) {

		switch ( $post_type ) {
			case 'post':
				$slug = 'edit.php';
				break;
			case 'attachment':
				$slug = 'upload.php';
				break;
			default:
				$slug = 'edit.php?post_type=' . $post_type;
		}

		return $slug;
	}

	/**
	 * Check to see if the submitted post type is active (being worked on)
	 *
	 * @since 0.6
	 * @param $post_type string     The post type to check
	 * @return bool                 Whether the submitted post type is active
	 */
	function is_post_type_active( $post_type ) {
		if ( ! isset( $_REQUEST['post'] ) && ! isset( $_REQUEST['post_type'] ) ) {
			return false;
		}

		$active_post_type = isset( $_REQUEST['post'] ) ? $_REQUEST['post'] : $_REQUEST['post_type'];

		return $post_type == $active_post_type;
	}

	/**
	 * Determine whether the submitted Menu item is for the submitted post type
	 *
	 * @since 0.6
	 * @param $menu_item object     Admin Menu item
	 * @param $post_type string     The post type to check
	 * @return bool                 Whether the Menu item is for the post type
	 */
	function menu_item_is_for_post_type( $menu_item, $post_type ) {
		return is_array( $menu_item ) && isset( $menu_item[5] ) && $menu_item[5] == 'menu-posts-' . $post_type;
	}

	/**
	 * Remove the Admin Menu entry for the submitted post type
	 *
	 * @since 0.6
	 * @param $post_type string     The post type to remove
	 * @return mixed                Either the plucked menu item or false
	 */
	function pluck_admin_menu_item( $post_type ) {
		global $menu;

		foreach ( $menu as $key => $menu_item ) {
			if ( $this->menu_item_is_for_post_type( $menu_item, $post_type ) ) {
				return $menu_item;
			}
		}

		return false;
	}

	/**
	 * Make room in the Admin Menu for a new item
	 *
	 * @since 0.6
	 */
	function inject_placeholder_admin_menu_item() {
		global $menu;

		// bump all existing $menu keys to make room for the contextual entry we're adding
		$new_menu = array();
		foreach ( $menu as $key => $final_menu_item ) {
			$key = $key <= $this->menu_position ? $key : $key + 1;
			$new_menu[ $key ] = $final_menu_item;
		}

		// overwrite the $menu global with ours
		$menu = $new_menu;
	}

	/**
	 * When viewing an edit screen for a post type that has been hidden from the Admin
	 * menu it's helpful to have the contextual edit links in the Admin menu anyway,
	 * move them up top
	 *
	 * @param $post_type
	 */
	function make_admin_menu_item_contextual( $post_type ) {
		global $menu;

		if ( ! $post_type_admin_menu_item = $this->pluck_admin_menu_item( $post_type ) ) {
			return;
		}

		// remove the original Admin Menu entry because we're moving it up top and we
		// don't want a dupe in the original location
		$menu_slug = $this->get_menu_slug_from_post_type( $post_type );
		remove_menu_page( $menu_slug );

		$this->inject_placeholder_admin_menu_item();

		// finally, add our contextual Menu link below Hierarchy's entry
		$menu[ $this->menu_position + 1 ] = $post_type_admin_menu_item;
	}

	/**
	 * Iterate through the saved post types to hide and hide them from the Admin Menu
	 *
	 * @since 0.6
	 */
	function remove_admin_menu_items() {

		$this->retrieve_post_types();

		if ( ! is_array( $this->post_types ) ) {
			return;
		}

		$post_types_to_hide = array_intersect( $this->post_types, (array) $this->settings['hidden_from_admin_menu'] );

		foreach ( $post_types_to_hide as $post_type_to_hide ) {

			$menu_slug = $this->get_menu_slug_from_post_type( $post_type_to_hide );

			// if (right now) we're editing a post type that was hidden from the Admin menu
			// let's leave the menu links in place for convenience
			if ( $this->is_post_type_active( $post_type_to_hide ) ) {
				$this->make_admin_menu_item_contextual( $post_type_to_hide );
				continue;
			}

			remove_menu_page( $menu_slug );

		}

		// also remove 'Add New' submenu items for post types set as 'no_new'
		if ( isset( $this->settings['post_types'] ) && is_array( $this->settings['post_types'] ) ) {
			foreach ( $this->settings['post_types'] as $post_type => $post_type_settings ) {
				if ( ! empty( $post_type_settings['no_new'] ) ) {
					remove_submenu_page( 'edit.php?post_type=' . $post_type, 'post-new.php?post_type=' . $post_type );
				}
			}
		}
	}

	/**
	 * Apply our logic to the Admin Menu (remove what needs to be removed and add Hierarchy Menu item)
	 *
	 * @since 0.6
	 */
	function hijack_admin_menu() {
		$this->add_menu_item();
		$this->remove_admin_menu_items();
	}

	/**
	 * Echo WP_List_Table for Hierarchy itself
	 *
	 * @since 0.6
	 */
	function show_hierarchy() {
		$this->retrieve_post_types();

		// prep the table itself
		$hierarchy_table = new Hierarchy_Table();
		$hierarchy_table->set_url( $this->url );
		$hierarchy_table->set_post_types( $this->post_types );
		$hierarchy_table->set_settings( $this->settings );

		// build the Hierarchy
		$hierarchy_factory = new Hierarchy_Factory();
		$hierarchy_factory->set_post_types( $this->post_types );
		$hierarchy = $hierarchy_factory->build();

		// tell the table about Hierarchy
		$hierarchy_table->prepare_items( $hierarchy );

		$page_title = apply_filters( 'hierarchy_menu_label', __( 'Content', 'hierarchy' ) );
		$page_title = apply_filters( 'hierarchy_page_title', $page_title );
		?>
		<div class="wrap">
			<div id="icon-page" class="icon32"><br/></div>
			<h2>
				<?php
					echo esc_html( $page_title );
					if ( apply_filters( 'hierarchy_add_shortcuts_button', true ) ) {
						$this->echo_shortcuts_ui();
					}
				?>
			</h2>
			<div id="iti-hierarchy-wrapper">
				<form id="iti-hierarchy-form" method="get">
					<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
					<?php $hierarchy_table->display() ?>
				</form>
			</div>
			<style type="text/css">
				.add-new-h2 span.dashicons {
					display:inline-block;
					padding-top:0.4em;
				}
				#iti-hierarchy-wrapper {
					margin-top:-2em;
				}
				#iti-hierarchy-wrapper .column-icon {
					width:1em;
					text-align:center;
				}
			</style>
		</div>
	<?php }

	/**
	 * Output the 'Shortcuts' button and post type dropdown
	 *
	 * @since 1.0.3
	 */
	function echo_shortcuts_ui() {

		// use the active admin color scheme hover color
		global $_wp_admin_css_colors;
		$current_color = get_user_option( 'admin_color' );
		$current_colors = isset( $_wp_admin_css_colors[ $current_color ] ) ? $_wp_admin_css_colors[ $current_color ] : $_wp_admin_css_colors[0];
		$link_hover_color = isset( $current_colors->colors[3] ) ? $current_colors->colors[3] : '#2ea2cc';

		// grab all post types that have not been omitted via Hierarchy settings
		$potential_post_types = array();
		foreach ( $this->post_types as $post_type ) {
			if ( empty( $this->settings['post_types'][ $post_type ]['omit'] ) ) {
				$potential_post_types[] = $post_type;
			}
		}

		// allow user filtration of what's included in the shortcuts dropdown
		$show_in_add_new = apply_filters( 'hierarchy_show_in_shortcuts', $potential_post_types );
		$cpts = array();

		// grab the post objects for all necessary post types
		foreach ( $show_in_add_new as $post_type ) {
			$cpts[$post_type] = get_post_type_object( $post_type );
		}

		// capability checks
		if ( isset( $cpts['attachment'] ) && ! current_user_can( 'upload_files' ) ) {
			unset( $cpts['attachment'] );
		}
		foreach( $cpts as $cpt => $properties ) {
			if ( ! current_user_can( $properties->cap->create_posts ) ) {
				unset( $cpt );
			}
		}

		?>
		<div class="hierarchy-add-new">
			<a href="#hierarchy-new" id="hierarchy-show-add-new" class="add-new-h2"><?php _e( 'Shortcuts', 'hierarchy' ); ?><span class="dashicons dashicons-arrow-right"></span></a>
			<div id="hierarchy-new">
				<ul>
					<?php foreach ( $cpts as $cpt ) : ?>
						<li>
							<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . $cpt->name ) ); ?>"><?php echo esc_html( $cpt->labels->menu_name ); ?></a>
							<ul>
								<li><a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . $cpt->name ) ); ?>"><?php echo esc_html( $cpt->labels->add_new_item ); ?></a></li>
								<?php
								// let's see if we need to add any taxonomies
								$args = array(
									'public'        => true,
									'object_type'   => array( $cpt->name )
								);
								$output = 'objects';
								$operator = 'and';
								$taxonomies = get_taxonomies( $args, $output, $operator );
								if ( ! empty( $taxonomies ) ) : ?>
									<?php foreach( $taxonomies as $taxonomy ) : if( $taxonomy->name != 'post_format' ) : ?>
										<?php
										$tax_edit_url = 'edit-tags.php?taxonomy=' . $taxonomy->name;
										if( $cpt->name != 'post' ) {
											$tax_edit_url .= '&post_type=' . $cpt->name;
										}
										$tax_edit_url = admin_url( $tax_edit_url );
										?>
										<li>
											<a href="<?php echo esc_url( $tax_edit_url ); ?>"><?php echo esc_html( $taxonomy->labels->name ); ?></a>
										</li>
									<?php endif; endforeach; ?>
								<?php endif; ?>
							</ul>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
		<script>
			jQuery(document).ready(function($){
				var $dropdown = $('#hierarchy-new');

				var toggle_dropdown_visiblity = function(){
					if($dropdown.is(':visible')){
						$dropdown.hide();
					}else{
						$dropdown.show();
					}
				};

				$('#hierarchy-show-add-new').click(function(){
					toggle_dropdown_visiblity();
					return false;
				});
				$(document).on('click',function(){
					if($dropdown.is(':visible')){
						$dropdown.hide();
					}
				});
			});
		</script>
		<style type="text/css">
			.hierarchy-add-new {
				display:inline-block;
				position:relative;
			}
			#hierarchy-new {
				display:none;
				font-size:0.55em;
				position:absolute;
				top:0;
				left:100%;
			}
			#hierarchy-new ul {
				list-style:none;
				margin:0;
				padding:0.2em 0 0.5em;
				background:#333;
				width:13em;
				-webkit-box-shadow:0 3px 5px rgba(0,0,0,.2);
				box-shadow:0 3px 5px rgba(0,0,0,.2);
			}
			#hierarchy-new li {
				margin:0;
				padding:0;
				line-height:1.6em;
			}
			#hierarchy-new > ul > li {
				position:relative;
			}
			#hierarchy-new > ul > li > ul {
				position:absolute;
				top:-0.2em; /* the padding-top of the parent ul */
				left:100%;
				width:13em;
				display:none;
			}
			#hierarchy-new > ul > li:hover > ul {
				display:block;
			}
			#hierarchy-new a {
				display:block;
				padding:0.2em 0.8em;
				color:#fff;
				text-decoration:none;
			}
			#hierarchy-new a:hover {
				color:<?php echo $link_hover_color; ?>;
			}
		</style>
	<?php
	}

}
