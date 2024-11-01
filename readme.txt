=== X-Valid ===
Tags: xhtml, automatic, validation, comment, post, x-valid, xvalid 
Contributors: majelbstoat

This plugin attempts to take arbitrary posts and comments and transform them into valid XHTML, a standard that describes how web-pages should be written. While not the be-all-and-end-all of web development, it is still important as it helps to ensure that content appears as you would expect in Internet Explorer as well as <a href="http://www.mozilla.org/products/firefox/">better browsers</a>. Wordpress itself is already XHTML valid, but the same cannot be necessarily be said of post content itself. It is especially hard (that is, impossible) to guarantee that your comment posters will conform to these standards, which could theoretically lead to your site displaying incorrectly.

It covers a wide range of markup errors, and is configurable via a menu interface which can be found as a Plugins submenu.

Many thanks to Andy Skelton for a major contribution in upgrading X-Valid to use WordPress' API.

== Installation ==

1. Upload the xvalid folder to your plugins folder, usually `wp-content/plugins/`
2. Activate the plugin on the plugin screen.
3. Check the menu is available under the plugins submenu and fiddle with the settings if you like.

== Frequently Asked Questions ==

= Does X-Valid delete any of my data? =

Almost certainly not.  I use X-Valid personally and have never had anything get lost.  X-Valid has been downloaded hundreds of times and there have been no complaints so far.  On the other hand, no guarantees are made, you use it at your own risk.  What it will sometimes do is remove tags that are unneeded, or erroneous (such as close tags without open tags).  Beyond these basics of correction, the default settings are conservative and it chooses to warn rather than remove or add extra tags without your permission.  You can change these if you like.  None of the combinations of settings I've personally chosen have resulted in data loss.

= Why does the output screen keep popping up every time I go to the admin section? =

This should be fixed in 0.99.  If it isn't, drop me a line.  For versions prior to 0.99, this is because the output file isn't deleting itself properly like it should.  To fix this, delete the xv_output.php file from your wp-content folder manually.  It probably happened because the document has more tags than X-Valid's internal limit.

= How can I tell if X-Valid's working? =

X-Valid displays a small message underneath the main post textbox when you are writing a new post.  If you see this text all should be good.  The best test is of course to just feed it something you know is wrong.  Try entering something like this and see if it gets fixed:

<code><b><i>A witty quote proves nothing</b></i></code>

= Does X-Valid work with WordPress 1.x.x? =

Yes and no.  Versions 0.99 onwards only support WordPress 2.0.  Versions 0.95 onwards only support WordPress 1.5.  For a version that works for 1.2.x use <a href="http://www.jamietalbot.com/wp-hacks/xvalid/xvalid-12x.zip">X-Valid 0.92</a>.  This version only validates against the Strict 1.0 doctype.  Compatibility with WordPress 1.3 is an unknown quantity, but if you are using this you really should upgrade to 1.5 as 1.3 was only ever alpha code...
