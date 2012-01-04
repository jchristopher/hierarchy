<?php
/*
 Plugin Name: Hierarchy
 Plugin URI: http://mondaybynoon.com/wordpress-hierarchy/
 Description: Properly structure how you've set up your Pages, Posts, and Custom Post Types
 Version: 0.1
 Author: Jonathan Christopher
 Author URI: http://mondaybynoon.com/
*/

/*  Copyright 2012 Jonathan Christopher (email : jonathan@irontoiron.com)

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


// constant definition
if( !defined( 'IS_ADMIN' ) )
    define( 'IS_ADMIN',  is_admin() );

define( 'HIERARCHY_VERSION', '0.1' );
define( 'HIERARCHY_PREFIX', '_iti_hierarchy_' );
define( 'HIERARCHY_DIR', WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) );
define( 'HIERARCHY_URL', rtrim( plugin_dir_url( __FILE__ ), '/' ) );

// WordPress actions
if( IS_ADMIN )
{
    require_once HIERARCHY_DIR . '/table.php';      // handles the actual Hierarchy display
    require_once HIERARCHY_DIR . '/table-cpt.php';  // handles the CPT listing on the Settings page

    add_action( 'admin_init',       array( 'Hierarchy', 'environment_check' ) );
    add_action( 'admin_init',       array( 'Hierarchy', 'register_settings' ) );
    add_action( 'admin_menu',       array( 'Hierarchy', 'assets' ) );

    add_action( 'admin_menu',       'iti_hierarchy_init' );

    add_filter( 'plugin_row_meta',  array( 'Hierarchy', 'filter_plugin_row_meta' ), 10, 2 );
}
else
{

}

function iti_hierarchy_init()
{
    $iti_hierarchy = new Hierarchy();
    return $iti_hierarchy;
}


/**
 * Hierarncy
 * Properly structure how you've set up your Pages, Posts, and Custom Post Types
 *
 * @package WordPress
 * @author Jonathan Christopher
 **/
class Hierarchy
{
    private $settings;

    private $post_types;

    /**
     * Constructor
     *
     * @return void
     * @author Jonathan Christopher
     **/
    function __construct()
    {
        // localization
        self::l10n();

        $this->settings = get_option( HIERARCHY_PREFIX . 'settings' );

        if( !$this->settings )
        {
            // settings not found; it's our first run
            $this->first_run();

            // our default settings
            $this->settings = array(
                    'version'       => HIERARCHY_VERSION
                );

            // save for future use
            add_option( HIERARCHY_PREFIX . 'settings', $this->settings, '', 'yes' );
        }

        // grab our post types
        $this->post_types = self::get_post_types();

        // proceed to taking over the admin menu
        $this->hijack_admin_menu();
    }


    function hijack_admin_menu()
    {
        global $menu;

        // add our 'Content' menu
        $position = isset( $menu[3] ) ? null : 3;

        add_menu_page( "Content", "Content", "edit_posts", "hierarchy", array( $this, "show_hierarchy" ), null, $position );

        // do we need to remove any menu entries?
        if( is_array( $this->post_types ) )
        {
            foreach( $this->post_types as $post_type )
            {
                if( isset( $this->settings['hidden_from_admin_menu'] )
                    && ( is_array( $this->settings['hidden_from_admin_menu'] )
                         && in_array( $post_type->name, $this->settings['hidden_from_admin_menu'] )
                       )
                  )
                {
                    if( $post_type->name == 'post' )
                    {
                        $menu_slug = 'edit.php';
                    }
                    else
                    {
                        $menu_slug = 'edit.php?post_type=' . $post_type->name;
                    }
                    remove_menu_page( $menu_slug );
                }
            }
        }

    }


    function get_pad( $post )
    {
        $level = 0;
        if( (int) $post->post_parent > 0 )
        {
            $find_main_page = (int) $post->post_parent;
            while ( $find_main_page > 0 )
            {
                $parent = get_page( $find_main_page );

                if ( is_null( $parent ) )
                    break;

                $level++;
                $find_main_page = (int) $parent->post_parent;

                if ( !isset( $parent_name ) )
                    $parent_name = apply_filters( 'the_title', $parent->post_title, $parent->ID );
            }
        }
        return str_repeat( '&#8212; ', $level );
    }


    function get_pages()
    {
        $args = array(
            'sort_column'   => 'menu_order, post_title'
        );
        $base = get_pages( $args );

        $pages = array();

        foreach( $base as $page )
        {

            // get the author information
            $author = get_userdata( $page->post_author );

            // get the pad (if necessary)
            $title = self::get_pad( $page ) . $page->post_title;

            $pages[] = array(
                    'ID'        => $page->ID,
                    'post_type' => $page->post_type,
                    'title'     => $title,
                    'author'    => $author->display_name,
                    'comments'  => $page->comment_count,
                    'date'      => date( get_option( 'date_format' ), strtotime( $page->post_date ) )
                );
        }

        return $pages;
    }


    function get_hierarchy()
    {
        $settings = get_option( HIERARCHY_PREFIX . 'settings' );

        // we always work from Pages as the base
        $pages = $this->get_pages();

        // will hold the final Hierarchy
        $hierarchy = array();

        // loop through the pages
        for( $i = 0; $i < count( $pages ); $i++ )
        {
            $handled = false;

            // do we even have anything to do?
            if( isset(  $settings['post_types'] ) && count( $settings['post_types'] ) )
            {
                // loop through each of the post types to see if we have an applicable parent
                foreach( $settings['post_types'] as $post_type => $attributes )
                {
                    $target_parent = $attributes['parent'];

                    if( $pages[$i]['ID'] == $target_parent )
                    {
                        // we do have an applicable parent, let's build in our CPT entry
                        $cpt = array(
                            'ID'        => $post_type,
                            'pad'       => Hierarchy::get_pad( get_page( $target_parent ) ) . '&#8212; ',
                            'title'     => $post_type,
                            'author'    => 'author',
                            'comments'  => -1,
                            'date'      => 'date'
                        );

                        // we need to first add in the original parent
                        $hierarchy[]    = $pages[$i];

                        // lastly we'll append our CPT and flag it as handled
                        $hierarchy[]    = $cpt;
                        $handled        = true;

                        // we've added our CPT index entry, but we need to handle the CPT entries as well
                        // TODO: pull CPT entries for applicable CPT and output as rows

                        // TODO: test integration with 'page' style CTPs and parent levels therein

                        // TODO: determine the best way to integrate taxonomies
                    }
                }
            }

            if( !$handled )
                $hierarchy[] = $pages[$i];

        }

        return $hierarchy;
    }


    function show_hierarchy()
    {
        $table = new HierarchyTable();
        $table->prepare_items( $this->get_hierarchy() );

        ?>
        <div class="wrap">
            <div id="icon-page" class="icon32"><br/></div>
            <h2>Content</h2>

            <form id="iti-hierarchy-filter" method="get">
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                <?php $table->display() ?>
            </form>

        </div>
    <?php
    }


    function first_run()
    {
        // nothing to do yet
    }


    function assets()
    {
        // add options menu
        add_options_page( 'Settings', 'Hierarchy', 'manage_options', __FILE__, array( 'Hierarchy', 'admin_settings' ) );
    }

    function admin_settings()
    {
        include 'settings.php';
    }


    function register_settings()
    {
        // flag our settings
        register_setting(
            HIERARCHY_PREFIX . 'settings',                  // group
            HIERARCHY_PREFIX . 'settings',                  // name of options
            array( 'Hierarchy', 'validate_settings' )       // validation callback
        );

        add_settings_section(
            HIERARCHY_PREFIX . 'settings',                  // section ID
            'Settings',                                     // title
            array( 'Hierarchy', 'edit_settings' ),          // display callback
            HIERARCHY_PREFIX . 'settings'                   // page name (do_settings_sections)
        );

        // table for placing CPTs
        add_settings_field(
            HIERARCHY_PREFIX . 'cpts',                      // unique field ID
            'Place Custom Post Types',                      // title
            array( 'Hierarchy', 'edit_cpt_placement' ),     // input box display callback
            HIERARCHY_PREFIX . 'settings',                  // page name (as above)
            HIERARCHY_PREFIX . 'settings'                   // first arg to add_settings_section
        );

        // hide links from admin menu
        add_settings_field(
            HIERARCHY_PREFIX . 'hidden',                    // unique field ID
            'Hide from the Admin Menu',                     // title
            array( 'Hierarchy', 'edit_hidden_post_types' ), // input box display callback
            HIERARCHY_PREFIX . 'settings',                  // page name (as above)
            HIERARCHY_PREFIX . 'settings'                   // first arg to add_settings_section
        );
    }


    function validate_settings( $input )
    {

        // make sure the version is appended
        $input['version'] = HIERARCHY_VERSION;

        return $input;
    }


    function edit_settings()
    {

    }


    function edit_cpt_placement()
    {
        $settings = get_option( HIERARCHY_PREFIX . 'settings' );

        $post_types = array();

        foreach( self::get_post_types() as $post_type )
        {
            $post_type_name = $post_type->name;

            $parent = isset( $settings['post_types'][$post_type_name]['parent'] ) ? intval( $settings['post_types'][$post_type_name]['parent'] ) : 0;
            $order = isset( $settings['post_types'][$post_type_name]['order'] ) ? intval( $settings['post_types'][$post_type_name]['order'] ) : 0;

            $post_types[] = array(
                    'name'      => $post_type_name,
                    'title'     => $post_type->labels->name,
                    'parents'   => self::get_pages(),
                    'cptparent' => $parent,
                    'order'     => $order
                );
        }

        $table = new HierarchyCPTTable();
        $table->prepare_items( $post_types, self::get_pages() );
        echo '<div id="hierarchy-cpt-wrapper">';
        $table->display();
        echo '</div><style type="text/css">#hierarchy-cpt-wrapper{margin-bottom:20px;}#hierarchy-cpt-wrapper .tablenav { display:none; }#hierarchy-cpt-wrapper select { display:block; max-width:95%;}</style>';
    }


    function edit_hidden_post_types()
    {
        // grab our existing settings
        $settings = get_option( HIERARCHY_PREFIX . 'settings' );

        foreach( self::get_post_types() as $post_type )
        { ?>
            <div>
                <label for="<?php echo HIERARCHY_PREFIX; ?>type_<?php echo $post_type->name; ?>">
                    <input name="<?php echo HIERARCHY_PREFIX; ?>settings[hidden_from_admin_menu][]" type="checkbox" id="<?php echo HIERARCHY_PREFIX; ?>type_<?php echo $post_type->name; ?>" value="<?php echo $post_type->name; ?>"<?php if( isset( $settings['hidden_from_admin_menu'] ) && is_array( $settings['hidden_from_admin_menu'] ) && in_array( $post_type->name, $settings['hidden_from_admin_menu'] ) ) : ?> checked="checked"<?php endif; ?> />
                    <?php echo $post_type->labels->name; ?>
                </label>
            </div>
        <?php }
    }


    function get_post_types()
    {
        // grab all public post types
        $args       = array(
                'public'    => true,
                'show_ui'   => true
            );
        $output     = 'objects';
        $operator   = 'and';
        $post_types = get_post_types( $args, $output, $operator );

        // since we use Pages as the basis, we do not want them here
        if( isset( $post_types['page'] ) )
            unset( $post_types['page'] );

        return $post_types;
    }


    /**
     * Checks to ensure we have proper WordPress and PHP versions
     *
     * @return void
     * @author Jonathan Christopher
     */
    function environment_check()
    {
        $wp_version = get_bloginfo( 'version' );
        if( !version_compare( PHP_VERSION, '5.2', '>=' ) || !version_compare( $wp_version, '3.2', '>=' ) )
        {
            if( IS_ADMIN && ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) )
            {
                require_once ABSPATH.'/wp-admin/includes/plugin.php';
                deactivate_plugins( __FILE__ );
                wp_die( __('Hierarchy requires PHP 5.2 or higher, as will WordPress 3.2 and higher. It has been automatically deactivated.') );
            }
            else
            {
                return;
            }
        }
    }



    /**
     * Load the translation of the plugin
     *
     * @return void
     * @author Jonathan Christopher
     */
    function l10n()
    {
        load_plugin_textdomain( 'attachmentspro', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }


    /**
     * Modifies the plugin meta line on the WP Plugins page
     *
     * @return $plugin_meta Array of plugin meta data
     * @author Jonathan Christopher
     */
    function filter_plugin_row_meta( $plugin_meta, $plugin_file )
    {
        if( strstr( $plugin_file, 'hierarchy' ) )
        {
            $plugin_meta[3] = 'Plugin by <a title="Iron to Iron" href="http://irontoiron.com/">Iron to Iron</a>';
            return $plugin_meta;
        }
        else
        {
            return $plugin_meta;
        }
    }

}
