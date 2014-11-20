This is a WordPress plugin. [Official download available on wordpress.org](http://wordpress.org/extend/plugins/hierarchy/).

# Hierarchy

Move your Pages/Posts/Custom Post Type admin links from the sidebar to a Content menu that nests everything where it should be

## Description

Custom Post Types (sometimes) need context, Hierarchy allows you to rework your content management workflow by essentially **moving Custom Post Type admin menus into your Pages list**. What this does is mimic the navigation you've set up on your site by placing Custom Post Type edit links amongst your Pages where they belong.

#### Custom Post Types *need* context

Custom Post Types are great, but the **editing workflow can be improved**. Adding a Custom Post Type likely results in *another* WordPress admin sidebar menu, abstracting the management of that content from the main organization of your site, Pages. Hierarchy intelligently extends your Pages menu by including your Custom Post Type admin links *within* the Page structure, allowing for a much more natural workflow when managing your content.

#### Examples

Chances are you've customized your `Front page displays` setting to display a static page instead of your latest blog posts. Now you've got a sidebar link to manage your Posts and a WordPress Page called "Blog" that sites in your list of Pages doing absolutely nothing. Hierarchy remedies both problems by converting the "Blog" page link to be one that lists your Posts. It also hides the Posts sidebar entry (if you want it to).

It's also likely that you're utilizing Custom Post Types to power sections of your website, but it's awkward to manage the content of an internal section of your website using the main WordPress admin sidebar links to your Custom Post Type. Hierarchy will allow you to hide those sidebar links and instead nest them amongst your Pages, providing contextual links to manage the content of your Custom Post Types.

#### Screenshots

###### A typical WordPress site with multiple CPTs:

![A typical WordPress site with multiple CPTs](https://mondaybynoon.com/wp-content/uploads/2014/11/hierarchy-1.0-1.png)

###### Pages are set up to establish structure for the site, but editing CPTs is disjointed:

![The Page structure](https://mondaybynoon.com/wp-content/uploads/2014/11/hierarchy-1.0-2.png)

###### Hierarchy integrates CPT edit links within your Pages and hides them from the Admin Menu:

![Hierarchy integrates CPT edit links within your Pages and hides them from the Admin Menu](https://mondaybynoon.com/wp-content/uploads/2014/11/hierarchy-1.0-4.png)

###### Hierarchy settings:

![Hierarchy settings](https://mondaybynoon.com/wp-content/uploads/2014/11/hierarchy-1.0-5.png)

###### Contextual links are included with each row in Hierarchy:

![Contextual links are included with each row in Hierarchy](https://mondaybynoon.com/wp-content/uploads/2014/11/hierarchy-1.0-7.png)

#### More information

If you'd like a lot more information on the implementation and workflow changes please see [the detailed introduction](https://mondaybynoon.com/introducing-hierarchy/) and [the follow-up for 1.0](https://mondaybynoon.com/hierarchy-1-0/).

## Installation

1. Download the plugin and extract the files
1. Upload `hierarchy` to your `~/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

#### Changelog

**1.0.2**
* Fixed an issue where pagination wasn't displaying properly
* Fixed an issue where standalone (e.g. not 'child') CPT entries were not properly output

**1.0.1**
* Fixed a false positive that would incorrectly output post type entires

**1.0**
* Complete refactor: reorganization, optimization, PHP Warning cleanup
* Bumped minimum WordPress version support to 3.8
* Use Dashicons where applicable
* Allow 'Prevent New' for post types (prevent adding new entries)
* New filters to customize Menu entry, Menu position, page title, and more

**0.5**
* Improved README, added banner image to help explain Hierarchy
* Tested with WordPress 3.7

**0.4**
* You can now implement pagination on the main 'Content' page
* Aded entry count when considering CPTs to better call attention to posts within
* Cleaned up a PHP Warning

**0.3**
* Added a fix for CPTs not being nested properly in WordPress 3.4+

**0.2**
* Added a contextual CPT management link to the admin sidebar that displays only when editing an entry of that CPT
* Added option to include CPT entries within the Hierarchy. Added option to omit a CPT from the Hierarchy entirely.
* CPT with a rewrite slug that matches an existing Page will respect that relationship and be inserted as a child of that Page
* Posts Page is now placed properly when a custom permalink front has been put in place

**0.1**
* Initial release
