<?php

/**
 * Hierarchy
 * Properly structure your Pages, Posts, and Custom Post Types
 *
 * @package WordPress
 * @author Jonathan Christopher
 **/
class Hierarchy {
	private $dir;
	private $url;
	private $version;
	private $plugin_name;
	private $prefix;
	private $settings;
	private $post_types;
	private $capability;
	private $menu_position = 3;

	function __construct() {

		$this->plugin_name = 'hierarchy';
		$this->prefix = '_iti_hierarchy_';
		$this->version = '0.6';
		$this->dir = plugin_dir_path( dirname( __FILE__ ) );
		$this->url = plugins_url( 'hierarchy', $this->dir );
		$this->capability = apply_filters( 'hierarchy_capability', 'manage_options' );
		$this->post_types = $this->get_post_types();

		$this->load_dependencies();
		$this->init_settings();
		$this->set_locale();
		$this->add_hooks();
	}

	function get_post_types() {
		$args = array(
			'public' => true,
			'show_ui' => true,
		);
		return get_post_types( $args, 'names', 'AND' );
	}

	function init_settings() {

		$settings = new Hierarchy_Settings();
		$settings->set_prefix( $this->prefix );
		$settings->set_version( $this->version );

		if ( ! $this->settings = get_option( $this->prefix . 'settings' ) ) {
			$this->settings = array(
				'per_page' => -1,
				'version' => $this->version,
				'hidden_from_admin_menu' => array()
			);

			add_option( $this->prefix . 'settings', $this->settings, '', 'no' );
		}

	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	function load_dependencies() {
		require_once $this->dir . 'includes/class-hierarchy-table.php';
		require_once $this->dir . 'includes/class-hierarchy-table-cpt.php';
		require_once $this->dir . 'includes/class-hierarchy-settings.php';
		require_once $this->dir . 'includes/class-hierarchy-i18n.php';
	}

	function set_locale() {
		$plugin_i18n = new Hierarchy_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );
	}

	function add_hooks() {
		add_action( 'admin_menu', array( $this, 'assets' ) );
		add_action( 'admin_menu', array( $this, 'hijack_admin_menu' ) );

		add_filter( 'plugin_row_meta',  array( 'Hierarchy', 'filter_plugin_row_meta' ), 10, 2 );
	}

	function assets() {
		// add options menu under Appearance
		add_options_page( __( 'Hierarchy', 'hierarchy' ), __( 'Hierarchy', 'hierarchy' ), $this->capability, __FILE__, array( $this, 'admin_settings' ) );
	}

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

	function add_menu_item() {
		$this->set_menu_position();

		// use Dashicons if possible, if not fall back to internal icon
		$menu_icon = version_compare( get_bloginfo( 'version' ), '3.8', '>=' ) ? 'dashicons-category' : $this->url . '/images/icon-hierarchy-menu.png';

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

	function get_menu_slug_from_post_type( $post_type ) {
		return 'post' == $post_type ? 'edit.php' : 'edit.php?post_type=' . $post_type;
	}

	function maybe_skip_menu_removal( $post_type ) {
		if ( ! isset( $_REQUEST['post'] ) && ! isset( $_REQUEST['post_type'] ) ) {
			return false;
		}

		$active_post_type = isset( $_REQUEST['post'] ) ? $_REQUEST['post'] : $_REQUEST['post_type'];

		return $post_type == $active_post_type;
	}

	function menu_item_is_for_post_type( $menu_item, $post_type ) {
		return is_array( $menu_item ) && isset( $menu_item[5] ) && $menu_item[5] == 'menu-posts-' . $post_type;
	}

	function pluck_admin_menu_item( $post_type ) {
		global $menu;

		foreach ( $menu as $key => $menu_item ) {
			if ( $this->menu_item_is_for_post_type( $menu_item, $post_type ) ) {
				return $menu_item;
			}
		}

		return false;
	}

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
		$menu_slug = $this->get_menu_slug_from_post_type( $post_type->name );
		remove_menu_page( $menu_slug );

		$this->inject_placeholder_admin_menu_item();

		// finally, add our contextual Menu link below Hierarchy's entry
		$menu[ $this->menu_position + 1 ] = $post_type_admin_menu_item;
	}

	function remove_admin_menu_items() {

		if ( ! is_array( $this->post_types ) ) {
			return;
		}

		$post_types_to_hide = array_intersect( $this->post_types, (array) $this->settings['hidden_from_admin_menu'] );

		foreach ( $post_types_to_hide as $post_type_to_hide ) {

			$menu_slug = $this->get_menu_slug_from_post_type( $post_type_to_hide->name );

			// if (right now) we're editing a post type that was hidden from the Admin menu
			// let's leave the menu links in place for convenience
			if ( $this->maybe_skip_menu_removal( $post_type_to_hide->name ) ) {
				$this->make_admin_menu_item_contextual( $post_type_to_hide->name );
				continue;
			}

			remove_menu_page( $menu_slug );

		}
	}

	function hijack_admin_menu() {
		global $menu;

		$this->add_menu_item();
		$this->remove_admin_menu_items();
	}

}