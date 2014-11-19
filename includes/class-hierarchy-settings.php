<?php


class Hierarchy_Settings extends Hierarchy {

	private $post_types_formatted = array();

	function init() {
		add_action( 'admin_menu', array( $this, 'settings_page_link' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	function settings_page_link() {
		// add options menu under Appearance
		add_options_page( __( 'Hierarchy', 'hierarchy' ), __( 'Hierarchy', 'hierarchy' ), $this->capability, 'hierarchy-settings', array( $this, 'admin_settings' ) );
	}

	function set_capability( $capability ) {
		$this->capability = $capability;
	}

	function set_prefix( $prefix ) {
		$this->prefix = $prefix;
	}

	function set_version( $version ) {
		$this->version = $version;
	}

	function register_settings() {
		// flag our settings
		register_setting(
			$this->prefix . 'settings',
			$this->prefix . 'settings',
			array( $this, 'validate_settings' )
		);

		add_settings_section(
			$this->prefix . 'settings',
			__( 'Settings', 'hierarchy' ),
			array( $this, 'edit_settings' ),
			$this->prefix . 'settings'
		);

		// table for placing CPTs
		add_settings_field(
			$this->prefix . 'cpts',
			__( 'Custom Post Type Locations', 'hierarchy' ),
			array( $this, 'display_edit_cpt_placement' ),
			$this->prefix . 'settings',
			$this->prefix . 'settings'
		);

		// pagination
		add_settings_field(
			$this->prefix . 'per_page',
			__( 'Items per page', 'hierarchy'),
			array( $this, 'display_edit_per_page' ),
			$this->prefix . 'settings',
			$this->prefix . 'settings'
		);

		// hide links from admin menu
		add_settings_field(
			$this->prefix . 'hidden',
			__( 'Hide from the Admin Menu', 'hierarchy' ),
			array( $this, 'display_edit_hidden_post_types' ),
			$this->prefix . 'settings',
			$this->prefix . 'settings'
		);
	}

	function admin_settings() { ?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br /></div>
			<h2>Hierarchy <?php _e( 'Settings', 'hierarchy' ); ?></h2>
			<form action="options.php" method="post">
				<div id="poststuff" class="metabox-holder">
					<?php settings_fields( $this->prefix . 'settings' ); ?>
					<?php do_settings_sections( $this->prefix . 'settings' ); ?>
				</div>
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e( 'Save Settings', 'hierarchy' ); ?>" />
				</p>
			</form>
		</div>
	<?php }

	function validate_settings( $input ) {
		// make sure the version is appended
		$input['version'] = $this->version;

		// ensure that the hidden post types are defined
		if ( ! isset( $input['hidden_from_admin_menu'] ) ) {
			$input['hidden_from_admin_menu'] = array();
		}

		// TODO: actually validate!?

		return $input;
	}

	function edit_settings() {

	}

	function prepare_post_types() {
		foreach ( $this->post_types as $post_type ) {

			// we need the object now
			$post_type = get_post_type_object( $post_type );

			// we don't want Pages included in the Hierarchy since that's the basis of everything
			if ( 'page' == $post_type->name ) {
				continue;
			}

			$post_type_order = isset( $this->settings['post_types'][ $post_type->name ]['order'] ) ? absint( $this->settings['post_types'][ $post_type->name ]['order'] ) : 0;
			$post_type_show_entries = isset( $this->settings['post_types'][ $post_type->name ]['entries'] ) ? true : false;
			$post_type_omit = isset( $this->settings['post_types'][ $post_type->name ]['omit'] ) ? true : false;

			$post_types[] = array(
				'name'      => $post_type->name,
				'title'     => $post_type->labels->name,
				'order'     => $post_type_order,
				'entries'   => $post_type_show_entries,
				'omit'      => $post_type_omit
			);
		}

		$this->post_types_formatted = $post_types;
	}

	function display_edit_cpt_placement() {
		$this->prepare_post_types();

		// build the WP_List_table
		$post_types_table = new Hierarchy_Table_CPT();
		$post_types_table->set_prefix( $this->prefix );
		$post_types_table->prepare_items( $this->post_types_formatted );

		// output the table
		?>
		<div id="hierarchy-cpt-wrapper">
			<?php $post_types_table->display(); ?>
			<p>
				<?php _e( '<strong>Show Entries:</strong> Include CPT entries in the Hierarchy', 'hierarchy' ); ?><br />
				<?php _e( '<strong>Omit:</strong> Ignore CPT completely in the Hierarchy', 'hierarchy' ); ?><br />
				<?php _e( '<strong>Order:</strong> customize the <code>menu_order</code> for the CPT', 'hierarchy' ); ?>
			</p>
		</div>
		<style type="text/css">
			#poststuff > h3 { display:none; }
			#hierarchy-cpt-wrapper p { padding-top:5px; }
			#hierarchy-cpt-wrapper th { padding-left:10px; }
			#hierarchy-cpt-wrapper .tablenav { display:none; }
			#hierarchy-cpt-wrapper .column-title { width:50%; }
			#hierarchy-cpt-wrapper .column-entries { width:20%; }
			#hierarchy-cpt-wrapper .column-omit { width:15%; }
			#hierarchy-cpt-wrapper .column-order { width:15%; }
		</style>
		<?php
	}

	function display_edit_per_page() {
		?>
			<input type="text" name="<?php echo $this->prefix; ?>settings[per_page]" id="<?php echo $this->prefix; ?>settings[per_page]" value="<?php echo isset( $this->settings['per_page'] ) ? absint( $this->settings['per_page'] ) : '-1'; ?>" class="small-text" /> <p class="description"><?php _e( 'To show all, use <strong>-1</strong>', 'hierarchy' ); ?></p>
		<?php
	}

	function display_edit_hidden_post_types() {
		foreach ( $this->post_types as $post_type ) : $post_type = get_post_type_object( $post_type ); ?>
			<div style="padding-top:0.4em;">
				<label for="<?php echo $this->prefix; ?>type_<?php echo $post_type->name; ?>">
					<input name="<?php echo $this->prefix; ?>settings[hidden_from_admin_menu][]" type="checkbox" id="<?php echo $this->prefix; ?>type_<?php echo $post_type->name; ?>" value="<?php echo $post_type->name; ?>"<?php if( isset( $this->settings['hidden_from_admin_menu'] ) && is_array( $this->settings['hidden_from_admin_menu'] ) && in_array( $post_type->name, $this->settings['hidden_from_admin_menu'] ) ) : ?> checked="checked"<?php endif; ?> />
					<?php echo $post_type->labels->name; ?>
				</label>
			</div>
		<?php endforeach;
	}

}