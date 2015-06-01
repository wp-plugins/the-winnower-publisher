=== The Winnower Publisher ===
Contributors: reconbot
Tags: science, DOI, permanent archival, PDF, Winnower, open-access, peer review, CLOCKSS, digital object identifier, altmetrics, piklist
Requires at least: 4.0
Tested up to: 4.2.2
Stable tag: 1.7
License: GPLv2 or later

Archiving and Aggregating “Alternative” Scholarly Content: DOIs for blogs.

== Description ==
Assign a CrossRef digital object identifier (DOI) to your blog via The Winnower and archive your work FOREVER using CLOCKSS. Scholarly publishing tools for individual publishers (bloggers).

= Features =

* Multi author blogs
* Updates The Winnower when you click update in Wordpress
* Retrieve DOI's of your posts from The Winnower and display them for citation on your paper.

= Instructions =

1. On new or existing posts find "The Winnower Post Settings"
1. Find "Cross-Post to The Winnower" and choose "Yes"
1. Chose at least one Topic for the post
1. Click "Publish"

Your post will now be posted to the winnower!

= Getting your DOI =

Currently only administrators may request a DOI.

1. Once your post has been published, find "You can request a DOI here." in "The Winnower Post Settings" click it and you will end up on the post's page at thewinnower.com. (Eg. `https://thewinnower.com/posts/what-are-winnower-authors-doing`)
1. On your post's page click "Revise this Paper"
1. Find the button "Archive" - Note: Once you archive your post it can no longer be updated.
1. You now have a DOI!
1. Go back to your post edit page in your blog and find the "Retrieve DOI" button and click it.
1. Now update your paper to save the DOI.

The DOI will now display for citation at the bottom of you post!

== Installation ==

This plugin requires the [Piklist plugin](https://wordpress.org/plugins/piklist/) which provides components for us to build this plugin.

The administrator of your blog will require an account with The Winnower and willingness to advance science. Authors currently only require a full name in their wordpress profile. Soon they'll be able to link with their own Winnower accounts.

1. Install The Winnower Publisher and Piklist plugins
1. In "The Winnower" settings in your admin dashboard save your api key. It can be found on your account page on thewinnower.com

== Changelog ==

= 1.7 =
* Fixes Topic fetching for older versions of cURL
* Update archive link

= 1.6 =
* Topics now load more reliably.
* We now give more descriptive error messages in more circumstances.
* In the rare chance topics weren't loaded we will no longer prevent saving drafts or updating posts.
* Better information about if you haven't saved your api key
* Add some feedback if you have't filled out your names in your wordpress profile
