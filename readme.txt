=== Hierarchy ===
Contributors: jchristopher
Donate link: http://mondaybynoon.com/donate/
Tags: hierarchy, structure, pages, cpt, custom post types, url, routing
Requires at least: 3.3
Tested up to: 3.3.1
Stable tag: 1.0

Properly structure your Pages, Posts, and Custom Post Types

== Description ==

Description coming soon

== Installation ==

1. Upload Hierarchy to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Enjoy

== Frequently Asked Questions ==

= How to I set up a Custom Post Type as a child? =

This relationship is established by the `rewrite` parameter you used in your call to `register_post_type()` &mdash; it should use your desired parent as a base. For example:

You have a WordPress `page` with the slug of `about` and you have a CPT for Team. Simply set the `rewrite` parameter for your Team CPT to be `about/team` and Hierarchy will include Team as a child of About.

== Screenshots ==

1. Multiple native Custom Post Types
2. Same site with Hierarchy activated and Custom Post Type entries disabled in the navigation
3. Hierarchy Settings

== Changelog ==

= 1.0 =
* Initial release

== Roadmap ==

* Automatic inclusion of CPT entries when inserting a hierarchical CPT
