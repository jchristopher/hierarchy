=== Hierarchy ===
Contributors: jchristopher
Donate link: http://mondaybynoon.com/donate/
Tags: hierarchy, pages, cpt, custom post types, url, routing
Requires at least: 3.3
Tested up to: 3.4.1
Stable tag: 0.3

Properly structure your Pages, Posts, and Custom Post Types

== Description ==

Hierarchy allows you to contextually include your Custom Post Types within your Pages. Please see [the introduction](http://jchr.co/rv).

== Installation ==

1. Upload Hierarchy to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How to I set up a Custom Post Type as a child? =

This relationship is established by the `rewrite` parameter you used in your call to `register_post_type()` &mdash; it should use your desired parent as a base. For example:

You have a WordPress `page` with the slug of `about` and you have a CPT for Team. Simply set the `rewrite` parameter for your Team CPT to be `about/team` and Hierarchy will include Team as a child of About.

== Screenshots ==

1. Multiple native Custom Post Types
2. Same site with Hierarchy activated and Custom Post Type entries disabled in the navigation
3. Hierarchy Settings

== Changelog ==

= 0.3 =
* Added a fix for CPTs not being nested properly in WordPress 3.4+

= 0.2 =
* Added a contextual CPT management link to the admin sidebar that displays only when editing an entry of that CPT
* Added option to include CPT entries within the Hierarchy. Added option to omit a CPT from the Hierarchy entirely.
* CPT with a rewrite slug that matches an existing Page will respect that relationship and be inserted as a child of that Page
* Posts Page is now placed properly when a custom permalink front has been put in place

= 0.1 =
* Initial release

== Roadmap ==

* Edge case fixes
