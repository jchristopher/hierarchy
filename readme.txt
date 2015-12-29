=== Hierarchy ===
Contributors: jchristopher
Donate link: http://mondaybynoon.com/donate/
Tags: hierarchy, pages, cpt, custom post types, url, routing
Requires at least: 3.8
Tested up to: 4.2
Stable tag: 1.0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Move your Pages/Posts/Custom Post Type admin links from the sidebar to a Content menu that nests everything where it should be

== Description ==

Custom Post Types (sometimes) need context, Hierarchy allows you to rework your content management workflow by essentially **moving Custom Post Type admin menus into your Pages list**. What this does is mimic the navigation you've set up on your site by placing Custom Post Type edit links amongst your Pages where they belong.

[**&raquo; Check out the screenshots to see Hierarchy in action**](https://wordpress.org/plugins/hierarchy/screenshots/)

Hierarchy removes unnecessary entries from the Admin Menu and instead injects theme where appropriate amongst your Pages, grouping everything under a single 'Content' Admin Menu entry.

= Custom Post Types NEED context =

Custom Post Types are great, but the **editing workflow can be improved**. Adding a Custom Post Type likely results in *another* WordPress admin sidebar menu, abstracting the management of that content from the main organization of your site, Pages. Hierarchy intelligently extends your Pages menu by including your Custom Post Type admin links *within* the Page structure, allowing for a much more natural workflow when managing your content.

= Examples =

Chances are you've customized your `Front page displays` setting to display a static page instead of your latest blog posts. Now you've got a sidebar link to manage your Posts and a WordPress Page called "Blog" that sites in your list of Pages doing absolutely nothing. Hierarchy remedies both problems by converting the "Blog" page link to be one that lists your Posts. It also hides the Posts sidebar entry (if you want it to).

It's also likely that you're utilizing Custom Post Types to power sections of your website, but it's awkward to manage the content of an internal section of your website using the main WordPress admin sidebar links to your Custom Post Type. Hierarchy will allow you to hide those sidebar links and instead nest them amongst your Pages, providing contextual links to manage the content of your Custom Post Types.

= More information =

If you'd like a lot more information on the implementation and workflow changes, [check out the screenshots](http://wordpress.org/plugins/hierarchy/screenshots/) and please see [the introduction](https://mondaybynoon.com/introducing-hierarchy/) and [the follow-up for 1.0](https://mondaybynoon.com/hierarchy-1-0-is-here/).

== Installation ==

1. Download the plugin and extract the files
1. Upload `hierarchy` to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How to I set up a Custom Post Type as a child? =

This relationship is established by the `rewrite` parameter you used in your call to `register_post_type()` &mdash; it should use your desired parent as a base. For example:

You have a WordPress `page` with the slug of `about` and you have a CPT for Team. Simply set the `rewrite` parameter for your Team CPT to be `about/team` and Hierarchy will include Team as a child of About.

== Screenshots ==

1. A typical WordPress site with multiple CPTs
2. Pages are set up to establish structure for the site, but editing CPTs is disjointed
3. Hierarchy integrates CPT edit links within your Pages and hides them from the Admin Menu
4. Hierarchy settings
5. Contextual links are included with each row in Hierarchy

== Changelog ==

= 1.0.4 =
* Taxonomies are now contingent on `show_ui` instead of `public`
* Updated WP_List_Table compat with WordPress 4.4

= 1.0.3 =
* Replaced 'Add New Page' button with a 'Shortcuts' button allowing for rapid access to post types and taxonomies
* Capability checks before outputting links (clicking links without capabilities results in a permission denied screen)
* New filter `hierarchy_settings_capability` to control who can see the Hierarchy settings
* Improved l18n

= 1.0.2 =
* Fixed an issue where pagination wasn't displaying properly
* Fixed an issue where standalone (e.g. not 'child') CPT entries were not properly output

= 1.0.1 =
* Fixed a false positive that would incorrectly output post type entries
* Clean up .org repo (deploy script didn't remove legacy files)

= 1.0 =
* Complete refactor: reorganization, optimization, PHP Warning cleanup
* Bumped minimum WordPress version support to 3.8
* Use Dashicons where applicable
* Allow 'Prevent New' for post types (prevent adding new entries)
* New filters to customize Menu entry, Menu position, page title, and more

= 0.5 =
* Improved README, added banner image to help explain Hierarchy
* Tested with WordPress 3.7

= 0.4 =
* You can now implement pagination on the main 'Content' page
* Aded entry count when considering CPTs to better call attention to posts within
* Cleaned up a PHP Warning

= 0.3 =
* Added a fix for CPTs not being nested properly in WordPress 3.4+

= 0.2 =
* Added a contextual CPT management link to the admin sidebar that displays only when editing an entry of that CPT
* Added option to include CPT entries within the Hierarchy. Added option to omit a CPT from the Hierarchy entirely.
* CPT with a rewrite slug that matches an existing Page will respect that relationship and be inserted as a child of that Page
* Posts Page is now placed properly when a custom permalink front has been put in place

= 0.1 =
* Initial release
