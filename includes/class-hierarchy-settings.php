<?php


class Hierarchy_Settings {

	private $prefix;
	private $version;

	function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
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
			array( 'Hierarchy', 'validate_settings' )
		);

		add_settings_section(
			$this->prefix . 'settings',
			__( 'Settings', 'hierarchy' ),
			array( 'Hierarchy', 'edit_settings' ),
			$this->prefix . 'settings'
		);

		// table for placing CPTs
		add_settings_field(
			$this->prefix . 'cpts',
			__( 'Custom Post Type Locations', 'hierarchy' ),
			array( 'Hierarchy', 'edit_cpt_placement' ),
			$this->prefix . 'settings',
			$this->prefix . 'settings'
		);

		// pagination
		add_settings_field(
			$this->prefix . 'per_page',
			__( 'Items per page', 'hierarchy'),
			array( 'Hierarchy', 'edit_per_page' ),
			$this->prefix . 'settings',
			$this->prefix . 'settings'
		);

		// hide links from admin menu
		add_settings_field(
			$this->prefix . 'hidden',
			__( 'Hide from the Admin Menu', 'hierarchy' ),
			array( 'Hierarchy', 'edit_hidden_post_types' ),
			$this->prefix . 'settings',
			$this->prefix . 'settings'
		);
	}

	function admin_settings() {
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br /></div>
			<h2>Hierarchy Settings</h2>
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
	<?php
	}

	function validate_settings( $input ) {
		// make sure the version is appended
		$input['version'] = $this->version;

		// TODO: actually validate!?

		return $input;
	}

	function edit_settings() {

	}

}