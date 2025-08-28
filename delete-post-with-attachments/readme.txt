=== Delete Post with Attachments ===
Contributors: alsvin
Tags: delete, post, attachment, media, cleanup
Requires at least: 5.1
Tested up to: 6.8
Stable tag: 2.0
Requires PHP: 5.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple plugin to delete attached media files e.g. images/videos/documents, when the post is deleted. Supports Elementor, Divi Builder, Thrive Architect, Brizy and others Page Builders.

== Description ==

By default, when you delete a post or page, any associated media files or attachments to that post do not get deleted. Keeping these orphan files to your server will eat up a lot of precious web space for no reason.

Using this plugin when you delete a post, any associated attachments will also get deleted automatically.

Before deleting any media file or attachment the plugin smartly checks that the attachment is not in use elsewhere, i.e. on any other post, page, or product.

**Works with popular Page Builders:**

* Elementor
* Thrive Architect
* Divi Builder
* Brizy
* and many more...

**Works with all popular plugins such as:**

* WooCommerce
* Easy Digital Downloads
* LearnDash
* BuddyPress
* MemberPress
* Paid Memberships Pro
* and many more...

**Features:**

* No configuration required
* Just activate and use
* Save your precious server storage
* Works automatically on post/page deletion


== Installation ==

1. Upload the plugin directory to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress


== Frequently Asked Questions ==

= Why my attachment does not get deleted when I Trash the post? =

The attachment will not be deleted if the post is moved to Trash, the attachment will only get deleted when the post is permanently deleted

= Why the attachment does not get deleted after I deleted the post permanently? =

Sometimes single attachment is used in multiple posts, for example you have used "image1.jpg" in "Post 1" and "Post 2" both. Deleting "Post 1" will not delete the image until "Post 2" is also deleted permanently. Please make sure your attachment is not in used any other post.

= Does this plugin delete attachments inserted via page builders?

Yes! it removes media files linked to posts, pages, and products, including those created using popular page builders.

= Which page builders are supported?

We have tested the plugin with Gutenberg, Elementor, Thrive Architect, Brizy, and Divi Builder. Other page builders may also work, but have not been fully tested yet.

= What if my page builder isn’t supported or has issues?

We have got you covered! Let us know via the plugin's support page, and we will work to add compatibility with your builder.

= Does this plugin require any configuration after installation? =

No, there is absolutely no configuration required, just activate the plugin, and it will start working.

= Does this plugin delete any associated media with the post? =

Yes, when you delete a post the plugin will check if there is any associated media or attachment in this post it will be deleted also.

= Does this plugin work with custom post types also? =

Yes, it works with any types, posts, pages, products etc

= What if a single attachment is used on multiple posts? =

If a single attachment is used on multiple posts, the attachment will not get deleted until all associated posts are deleted.

= What if attachment is used as a featured image, does it get also deleted on post deletion? =

Yes, the plugin will check if the image is not used in any other post then it will get deleted along with the current post.


== Changelog ==

= [2.0.0] – 2025‑08‑19 =
* Added: support for Gutenberg editor
* Added: support for Elementor
* Added: support for Thrive Architect
* Added: support for Divi Builder
* Added: support for Brizy

= 1.4 =
* Fix - Compatibility issues with WordPress 6.8.1

= 1.3 =
* Fix - Compatibility issues with WordPress 6.8

= 1.2 =
* Fix - wp_delete_attachment function second param to bool instead of string

= 1.1.3 =
* Fix - Compatibility issues with WordPress 6.4

= 1.1.2 =
* Fix - Compatibility issues with WordPress 6.0

= 1.1.1 =
* Fix - Compatibility issues with WordPress 5.9

= 1.1.0 =
* New - Implement featured image deletion

= 1.0.0 =
* Initial release


== Upgrade Notice ==
