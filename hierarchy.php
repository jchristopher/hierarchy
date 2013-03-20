<?php
/*
 Plugin Name: Hierarchy
 Plugin URI: http://mondaybynoon.com/wordpress-hierarchy/
 Description: Properly structure your Pages, Posts, and Custom Post Types
 Version: 0.4
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

define( 'HIERARCHY_VERSION', '0.4' );
define( 'HIERARCHY_PREFIX', '_iti_hierarchy_' );
define( 'HIERARCHY_DIR', WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) );
define( 'HIERARCHY_URL', rtrim( plugin_dir_url( __FILE__ ), '/' ) );

// WordPress actions
if( IS_ADMIN )
{
    // TODO: we only need these within the proper function, not all the time
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


/**
 * Initialize Hierarchy in the admin menu
 *
 * @package WordPress
 * @author Jonathan Christopher
 **/
function iti_hierarchy_init()
{
    $iti_hierarchy = new Hierarchy();
    return $iti_hierarchy;
}


/**
 * Hierarchy
 * Properly structure your Pages, Posts, and Custom Post Types
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
                    'per_page'      => '-1',
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


    /**
     * Correctly position our Content menu entry and hide what should be hidden
     *
     * @package WordPress
     * @author Jonathan Christopher
     **/
    function hijack_admin_menu()
    {
        global $menu;

        // add our 'Content' menu
        $position = 3;                      // ideally we're in position 3, just below the Dashboard, but above the separator
        while( isset( $menu[$position] ) )  // we don't want to override an existing $menu entry so let's find the closest
            $position++;

        // add our menu item
        add_menu_page( __( "Content", "hierarchy" ), __( "Content", "hierarchy" ), "edit_posts", "hierarchy", array( $this, "show_hierarchy" ), HIERARCHY_URL . '/images/icon-hierarchy-menu.png', $position );

        // do we need to remove any menu entries?
        if( is_array( $this->post_types ) )
        {
            // loop through available post types and check to see if any are supposed to be hidden
            foreach( $this->post_types as $post_type )
            {
                if( isset( $this->settings['hidden_from_admin_menu'] )
                    && ( is_array( $this->settings['hidden_from_admin_menu'] )
                         && in_array( $post_type->name, $this->settings['hidden_from_admin_menu'] )
                       )
                  )
                {
                    // yes it needs to be hidden, we need to determine the slug as that's the index used for deletion
                    if( $post_type->name == 'post' )
                    {
                        $menu_slug = 'edit.php';
                    }
                    else
                    {
                        $menu_slug = 'edit.php?post_type=' . $post_type->name;
                    }

                    // do we want to add a 'convenience' link back to the CPT index?
                    $active_post_type = '';
                    if( isset( $_REQUEST['post'] ) )
                    {

                        $active_post_type = get_post_type( intval( $_REQUEST['post'] ) );
                    }
                    elseif( isset( $_REQUEST['post_type'] ) )
                    {
                        $active_post_type = sanitize_text_field( $_REQUEST['post_type'] );
                    }

                    // get rid of it
                    if( $post_type->name != $active_post_type )
                    {
                        remove_menu_page( $menu_slug );
                    }
                    else
                    {
                        // we need to move it up top...
                        foreach( $menu as $key => $menu_item )
                        {
                            if( isset( $menu_item[5] ) && $menu_item[5] == 'menu-posts-' . $active_post_type )
                            {
                                // we need to remove the original first
                                remove_menu_page( $menu_slug );

                                // we're going to bump all the keys to make room for a potential
                                // context link when editing a CPT
                                $menu_updated = array();

                                foreach( $menu as $key => $final_menu_item )
                                {
                                    if( $key <= $position )
                                    {
                                        $menu_updated[$key] = $final_menu_item;
                                    }
                                    else
                                    {
                                        $menu_updated[$key+1] = $final_menu_item;
                                    }
                                }

                                // we can put everything back now
                                $menu = $menu_updated;
                                unset( $menu_updated );

                                $menu[$position+1] = $menu_item;
                            }
                        }
                    }
                }
            }
        }
    }


    /**
     * Figure out how many levels deep a $post is
     *
     * @package WordPress
     * @author Jonathan Christopher
     * @param $post
     * @return int
     */
    function get_pad_count( $post )
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
            }
        }

        return intval( $level );
    }


    /**
     * Produce our separator based on how many levels deep a $post is
     *
     * @package WordPress
     * @author Jonathan Christopher
     * @param $post
     * @return string
     */
    function get_pad( $post )
    {
        return str_repeat( '&#8212; ', self::get_pad_count( $post ) );
    }


    /**
     * Pull the existing WordPress page post type
     *
     * @package WordPress
     * @author Jonathan Christopher
     *
     * @return array
     */
    function get_pages( $post_type = 'page' )
    {
        $args = array(
            'sort_column'   => 'menu_order, post_title',
            'post_type'     => $post_type
        );
        $base = get_pages( $args );

        $pages = array();

        if( !empty( $base ) )
        {
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
                        'date'      => date( get_option( 'date_format' ), strtotime( $page->post_date ) ),
                        'order'     => $page->menu_order,
                        'parent'    => $page->post_parent
                    );
            }
        }

        return $pages;
    }


    /**
     * Pull the existing posts for a Custom Post Type
     *
     * @package WordPress
     * @author Jonathan Christopher
     *
     * @return array
     */
    function get_posts( $post_type = 'post' )
    {
        // TODO: This thing is SUPER redundant, need to figure out how to merge this and get_pages

        $args = array(
            'post_type'     => $post_type,
            'numberposts'   => -1
        );
        $base = get_posts( $args );

        $pages = array();

        if( !empty( $base ) )
        {
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
                        'date'      => date( get_option( 'date_format' ), strtotime( $page->post_date ) ),
                        'order'     => $page->menu_order,
                        'parent'    => $page->post_parent
                    );
            }
        }

        return $pages;
    }


    /**
     * Integrate CPT entries into the Page stack where appropriate
     *
     * @package WordPress
     * @author Jonathan Christopher
     *
     * @return array
     */
    function get_hierarchy()
    {
        global $wp_rewrite, $wp_version;

        $settings = get_option( HIERARCHY_PREFIX . 'settings' );

        // we always work from Pages as the base
        $pages = $this->get_pages();

        // will hold the final Hierarchy
        $hierarchy = array();

        // store our post types
        $post_types = $this->get_post_types();

        $posts_page = ( 'page' == get_option( 'show_on_front' ) ) ? intval( get_option( 'page_for_posts' ) ) : false;

        // loop through the pages because we're building our tree one link at a time
        for( $i = 0; $i < count( $pages ); $i++ )
        {
            // we'll always want to add the entry to the Hierarchy
            $new = array(
                    'entry'     => $pages[$i],
                    'order'     => $pages[$i]['order'],
                    'parent'    => $pages[$i]['parent'],
                    'post_type' => 'page'
                );

            // instead of appending, we need to inject
            // but only if we're not dealing with an alternate Posts Page
            if( empty( $posts_page ) || ( $posts_page != $pages[$i]['ID'] ) )
            {
                $hierarchy = $this->inject_hierarchy_entry( $hierarchy, $new );
            }

            // we're going to loop through all of the CPTs WP knows about
            // because we might not have any settings for them
            // if the settings have not been re-saved
            foreach( $post_types as $post_type )
            {
                // make sure we're not omitting this post type entirely as per the Settings
                if( empty( $settings['post_types'][$post_type->name]['omit'] ) )
                {

                    $target_parent  = 0;        // safe to assume there's no parent

                    $the_post_type = $post_type;

                    if( isset( $post_type->name ) )
                    {
                        $post_type = $post_type->name;
                    }
                    else
                    {
                        $post_type = '';
                    }

                    $order = ( !empty( $settings['post_types'][$post_type]['order'] ) ) ? intval( $settings['post_types'][$post_type]['order'] ) : 0;

                    // with WordPress 3.4 the $wp_rewrite data structure changed
                    if( version_compare( $wp_version, '3.4', '>=' ) )
                    {
                        $struct = isset( $wp_rewrite->extra_permastructs[$post_type]['struct'] ) ? $wp_rewrite->extra_permastructs[$post_type]['struct'] : '';
                    }
                    else
                    {
                        // legacy
                        $struct = '';
                        if( $wp_rewrite->extra_permastructs[$post_type][1] )
                        {
                            $struct = $wp_rewrite->extra_permastructs[$post_type][0];
                        }
                    }

                    // we can only figure out the parent if WordPress knows about the archive slug
                    if( isset( $wp_rewrite->extra_permastructs[$post_type] )
                        && !empty( $struct ) ) // make sure rewrite is enabled
                    {

                        // we have a permalink structure for the CPT at hand...
                        $cpt_archive_slug = $struct;

                        // let's break it up into URI segments
                        $cpt_archive_slug = explode( '/', trim( $cpt_archive_slug ) );

                        // let's remove empty values
                        if( count( $cpt_archive_slug ) )
                        {
                            for( $f = 0; $f < count( $cpt_archive_slug ); $f++ )
                            {
                                if( empty( $cpt_archive_slug[$f] ) )
                                    unset( $cpt_archive_slug[$f] );
                            }
                        }

                        // the last two segments represent the CPT archive and the slug, so we need everything before that

                        // that said, if the array isn't at least 3 keys, there is no possible parent
                        if( count( $cpt_archive_slug ) > 2 )
                        {
                            $parent_slug = implode( '/', array_slice( $cpt_archive_slug, 0, count( $cpt_archive_slug ) - 2 ) );

                            // we have the parent's slug
                            $parent_page = get_page_by_path( $parent_slug );

                            // let's fetch the ID and be done with it
                            if( isset( $parent_page->ID ) )     // this could be undefined if Permalinks have not been regenerated
                                $target_parent = $parent_page->ID;
                        }
                    }

                    // edge case: user has customized the front of the permalink
                    if( !empty( $wp_rewrite->front ) && $posts_page && $posts_page == $pages[$i]['ID'] && $post_type == 'post' )
                    {
                        // we're working with posts & a customized permalink structure
                        // which will interfere with the placement of this entry, we need to find our new parent

                        // we've got the right padding, we just have the wrong parent
                        $target_parent = $posts_page;
                    }

                    // what if there is a CPT that wants to use a Page as it's archive?
                    // for example a Page has the same slug as a CPT rewrite slug (AND has no archive)
                    if( !empty( $the_post_type->rewrite['slug'] ) && !$the_post_type->has_archive )
                    {
                        $faux_parent        = get_page_by_path( $the_post_type->rewrite['slug'] );

                        if( $faux_parent )
                        {
                            $faux_parent_id     = $faux_parent->ID;
                            $faux_archive       = get_permalink( $faux_parent_id );

                            // does our CPT archive slug match an existing Page?
                            if( !empty( $faux_archive ) )   // TODO: determine if this is completely effective
                            {
                                // let's set the right parent
                                $target_parent = $faux_parent_id;
                            }
                        }
                    }

                    if( $pages[$i]['ID'] == $target_parent )
                    {
                        // we do have an applicable parent, let's build in our CPT entry
                        $pad = '&#8212; ';

                        // continuing with our edge case considering a customized front on the permalink structure
                        // in conjunction with a Posts Page, we might end up with an extra padding level
                        if( !empty( $wp_rewrite->front ) && $posts_page && $posts_page == $pages[$i]['ID'] && $post_type == 'post' )
                            $pad = '';

                        $base_pad = Hierarchy::get_pad( get_page( $target_parent ) ) . $pad;

                        $cpt = array(
                                'ID'        => $post_type,
                                'post_type' => $post_type,
                                'pad'       => $base_pad,
                                'title'     => $post_type,
                                'author'    => '',
                                'comments'  => '',
                                'date'      => '',
                                'order'     => $order
                            );


                        $new = array(
                                'entry'     => $cpt,
                                'order'     => $order,
                                'post_type' => $post_type,
                                'parent'    => $target_parent
                            );

                        // instead of appending, we need to inject
                        $hierarchy = $this->inject_hierarchy_entry( $hierarchy, $new );

                        // we've added our CPT index entry, but we need to handle the CPT entries as well
                        if(  !empty( $settings['post_types'][$post_type]['entries'] ) )
                        {
                            if( $the_post_type->hierarchical )
                            {
                                $cpt_pages = $this->get_pages( $post_type );
                            }
                            else
                            {
                                $cpt_pages = $this->get_posts( $post_type );
                            }

                            if( !empty( $cpt_pages ) )
                            {
                                $cpt_page_hierarchy = array();
                                foreach( $cpt_pages as $cpt_page_ref )
                                {
                                    $cpt_page   = get_post( $cpt_page_ref['ID'] );

                                    // grab our author info
                                    $author     = get_userdata( $cpt_page->post_author );

                                    $new_cpt = array(
                                            'ID'        => $cpt_page->ID,
                                            'pad'       => $base_pad . Hierarchy::get_pad( $cpt_page ) . '&#8212; ',
                                            'title'     => $base_pad . Hierarchy::get_pad( $cpt_page ) . '&#8212; ' . $cpt_page->post_title,
                                            'author'    => $author->display_name,
                                            'comments'  => $cpt_page->comment_count,
                                            'date'      => date( get_option( 'date_format' ), strtotime( $cpt_page->post_date ) ),
                                            'order'     => $cpt_page->menu_order,
                                            'parent'    => ( $cpt_page->post_parent != 0 ) ? $cpt_page->post_parent : $post_type,
                                            'post_type' => $cpt_page->post_type
                                        );

                                    $new_entry = array(
                                            'entry'     => $new_cpt,
                                            'order'     => $cpt_page->menu_order,
                                            'parent'    => ( $cpt_page->post_parent != 0 ) ? $cpt_page->post_parent : $post_type,
                                            'post_type' => $cpt_page->post_type
                                        );

                                    // we'll go ahead and append our entry because it's already in order
                                    $cpt_page_hierarchy[] = $new_entry;
                                }

                                // append the CPT entry hierarchy to the main hierarchy since it's already in order and properly padded
                                if( is_array( $cpt_page_hierarchy ) )
                                {
                                    foreach( $cpt_page_hierarchy as $cpt_entry )
                                    {
                                        $hierarchy[] = $cpt_entry;
                                    }
                                    unset( $cpt_page_hierarchy );
                                }
                            }
                        }

                        // we'll never need this again so we'll remove it because we're going to dump out the leftovers at the end
                        unset( $post_types[$post_type] );
                    }
                }
            }
        }

        // check to see if we need to append additional orphan CPTs
        if( !empty( $post_types ) )
        {
            // we have some 'left over' CPTs that have no parents so let's append them
            foreach( $post_types as $post_type )
            {
                // we definitely do not want to include Pages here, or omitted CPTs
                if( $post_type->name != 'page' && empty( $settings['post_types'][$post_type->name]['omit'] ) )
                {
                    // we need to put it in the proper place
                    $order = ( !empty( $settings['post_types'][$post_type->name]['order'] ) ) ? intval( $settings['post_types'][$post_type->name]['order'] ) : 0;

                    $cpt = array(
                        'ID'            => $post_type->name,
                        'pad'           => '',
                        'title'         => $post_type->name,
                        'author'        => '',
                        'comments'      => '&ndash;',
                        'date'          => '',
                        'order'         => $order,
                        'post_type'     => $post_type->name
                    );

                    $new = array(
                            'entry'     => $cpt,
                            'order'     => $order,
                            'post_type' => $post_type->name,
                            'parent'    => 0
                        );

                    // instead of appending, we need to inject
                    $hierarchy = $this->inject_hierarchy_entry( $hierarchy, $new );
                }
            }
        }

        return $hierarchy;
    }


    /**
     * Inject a new entry into the Hierarchy at the correct index
     *
     * @package WordPress
     * @author Jonathan Christopher
     * @param array $existing
     * @param array $new
     * @return array
     */
    function inject_hierarchy_entry( $existing = array(), $new = array(), $post_type = null )
    {
        // note that $existing is already in order (but not multi-dimensional) and we
        // want to inject $new in the proper place on the proper level

        $order          = intval( $new['order'] );
        $last_index     = 0;
        $target_index   = -1;

        // loop through and find out where we need to insert
        for( $i = 0; $i < count( $existing ); $i++ )
        {
            if( isset( $existing[$i]['parent'] ) && $existing[$i]['parent'] == $new['parent'] )
            {
                if( $existing[$i]['post_type'] == 'page' )  // otherwise we might inject within CTP entries
                {
                    // we only want to proceed when we're working with the first level
                    $last_index = $i;
                    if( $order < intval( $existing[$i]['order'] ) )
                    {
                        // we've hit a ceiling
                        $target_index = $last_index;
                        break;
                    }
                }
            }
        }

        // we might be dealing with the last entry
        if( $target_index === -1 )
            $target_index = count( $existing );

        // we'll insert our new entry in the appropriate place
        array_splice( $existing, $target_index, 0, array( $new ) );

        return $existing;

    }


    /**
     * Output the Hierarchy
     *
     * @package WordPress
     * @author Jonathan Christopher
     **/
    function show_hierarchy()
    {
        $table = new HierarchyTable();
        $table->prepare_items( $this->get_hierarchy() );

        ?>
        <div class="wrap">
            <div id="icon-page" class="icon32"><br/></div>
            <h2><?php echo _e( "Content", "hierarchy" ); ?></h2>

            <div id="iti-hierarchy-wrapper">
                <form id="iti-hierarchy-form" method="get">
                    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                    <?php $table->display() ?>
                </form>
            </div>

            <style type="text/css">
                #iti-hierarchy-wrapper .column-icon { width:38px; }
            </style>

        </div>
    <?php
    }


    /**
     * Housekeeping on the first run
     *
     * @package WordPress
     * @author Jonathan Christopher
     **/
    function first_run()
    {
        // nothing to do yet
    }


    /**
     * Implement our needed assets in the admin
     *
     * @package WordPress
     * @author Jonathan Christopher
     **/
    function assets()
    {
        // add options menu under Appearance
        add_options_page( 'Hierarchy', 'Hierachy', 'manage_options', __FILE__, array( 'Hierarchy', 'admin_settings' ) );
    }


    /**
     * Callback for the Hierarchy settings
     *
     * @package WordPress
     * @author Jonathan Christopher
     **/
    function admin_settings()
    {
        include 'settings.php';
    }


    /**
     * Register our settings
     *
     * @package WordPress
     * @author Jonathan Christopher
     **/
    function register_settings()
    {
        // flag our settings
        register_setting(
            HIERARCHY_PREFIX . 'settings',
            HIERARCHY_PREFIX . 'settings',
            array( 'Hierarchy', 'validate_settings' )
        );

        add_settings_section(
            HIERARCHY_PREFIX . 'settings',
            __( 'Settings', 'hierarchy' ),
            array( 'Hierarchy', 'edit_settings' ),
            HIERARCHY_PREFIX . 'settings'
        );

        // table for placing CPTs
        add_settings_field(
            HIERARCHY_PREFIX . 'cpts',
            __( 'Custom Post Type Locations', 'hierarchy' ),
            array( 'Hierarchy', 'edit_cpt_placement' ),
            HIERARCHY_PREFIX . 'settings',
            HIERARCHY_PREFIX . 'settings'
        );

        // pagination
        add_settings_field(
            HIERARCHY_PREFIX . 'per_page',
            __( 'Items per page', 'hierarchy'),
            array( 'Hierarchy', 'edit_per_page' ),
            HIERARCHY_PREFIX . 'settings',
            HIERARCHY_PREFIX . 'settings'
        );

        // hide links from admin menu
        add_settings_field(
            HIERARCHY_PREFIX . 'hidden',
            __( 'Hide from the Admin Menu', 'hierarchy' ),
            array( 'Hierarchy', 'edit_hidden_post_types' ),
            HIERARCHY_PREFIX . 'settings',
            HIERARCHY_PREFIX . 'settings'
        );
    }


    /**
     * Validate our settings
     *
     * @package WordPress
     * @author Jonathan Christopher
     * @param $input
     * @return array
     */
    function validate_settings( $input )
    {

        // make sure the version is appended
        $input['version'] = HIERARCHY_VERSION;

        return $input;
    }


    /**
     * Callback for the settings
     *
     * @package WordPress
     * @author Jonathan Christopher
     **/
    function edit_settings()
    {

    }


    /**
     * Display the available CPT settings
     *
     * @package WordPress
     * @author Jonathan Christopher
     **/
    function edit_cpt_placement()
    {
        $settings = get_option( HIERARCHY_PREFIX . 'settings' );

        $post_types = array();

        foreach( self::get_post_types() as $post_type )
        {
            $post_type_name = $post_type->name;

            $order      = isset( $settings['post_types'][$post_type_name]['order'] ) ? intval( $settings['post_types'][$post_type_name]['order'] ) : 0;

            $entries    = isset( $settings['post_types'][$post_type_name]['entries'] ) ? true : false;
            $omit       = isset( $settings['post_types'][$post_type_name]['omit'] ) ? true : false;

            $post_types[] = array(
                    'name'          => $post_type_name,
                    'title'         => $post_type->labels->name,
                    'order'         => $order,
                    'entries'       => $entries,
                    'omit'          => $omit,
                );
        }

        $table = new HierarchyCPTTable();

        // since we use Pages as the basis, we do not want them here
        for( $i = 0; $i < count( $post_types ); $i++ )
        {
            if( $post_types[$i]['name'] == 'page' )
                unset( $post_types[$i] );
        }

        // prepare our data
        $table->prepare_items( $post_types );

        // output the table
        ?>
        <div id="hierarchy-cpt-wrapper">
            <?php $table->display(); ?>
            <p>
                <?php _e( '<strong>Show Entries:</strong> Include CPT entries in the Hierarchy', 'hierarchy' ); ?><br />
                <?php _e( '<strong>Omit:</strong> Ignore CPT completely in the Hierarchy', 'hierarchy' ); ?><br />
                <?php _e( '<strong>Order:</strong> customize the <code>menu_order</code> for the CPT', 'hierarchy' ); ?>
            </p>
        </div>
        <style type="text/css">
            #hierarchy-cpt-wrapper p { padding-top:5px; }
            #hierarchy-cpt-wrapper .tablenav { display:none; }
            #hierarchy-cpt-wrapper .column-title { width:50%; }
            #hierarchy-cpt-wrapper .column-entries { width:20%; }
            #hierarchy-cpt-wrapper .column-omit { width:15%; }
            #hierarchy-cpt-wrapper .column-order { width:15%; }
        </style>
    <?php }


    /**
     * List possible CPT entries to hide from the admin menu
     *
     * @package WordPress
     * @author Jonathan Christopher
     **/
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


    function edit_per_page()
    {
        // grab our existing settings
        $settings = get_option( HIERARCHY_PREFIX . 'settings' );
        ?>
            <input type="text" name="<?php echo HIERARCHY_PREFIX; ?>settings[per_page]" id="<?php echo HIERARCHY_PREFIX; ?>settings[per_page]" value="<?php echo isset( $settings['per_page'] ) ? intval( $settings['per_page'] ) : '-1'; ?>" class="small-text" /> <p class="description"><?php _e( 'To show all, use <strong>-1</strong>', 'hierarchy' ); ?></p>
        <?php
    }


    /**
     * Retrieve the registered post types from WP
     *
     * @package WordPress
     * @author Jonathan Christopher
     *
     * @return array
     */
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

        return $post_types;
    }


    /**
     * Retrieve the registered taxonomies from WP for a specific post type
     *
     * @package WordPress
     * @author Jonathan Christopher
     *
     * @return array
     */
    function get_taxonomies_for_post_type( $post_type )
    {
        // grab all public taxonomies
        $args       = array(
                'public'        => true,
                'object_type'   => array( $post_type )
            );
        $output     = 'objects';
        $operator   = 'and';
        $taxonomies = get_taxonomies( $args, $output, $operator );

        return $taxonomies;
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
        if( !version_compare( PHP_VERSION, '5.2', '>=' ) || !version_compare( $wp_version, '3.3', '>=' ) )
        {
            if( IS_ADMIN && ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) )
            {
                require_once ABSPATH.'/wp-admin/includes/plugin.php';
                deactivate_plugins( __FILE__ );
                wp_die( __('Hierarchy requires PHP 5.2 or higher, as will WordPress 3.3 and higher. It has been automatically deactivated.') );
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
        load_plugin_textdomain( 'hierarchy', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
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
