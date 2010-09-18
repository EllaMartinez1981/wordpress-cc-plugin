=== Creative Commons License Manager ===

Tags: cc, creativecommons, license, licensing, spreeblick
Requires at least: 2.9
Tested up to: 3.0
Stable tag: 0.7.4

== Description ==

This plugin extends the Wordpress media manager to provide support for [Creative Commons Licenses] [1]. Those licenses enable creators of works to provide simple means of communicating which rights they reserve.

License, rights holder, attribution URI and jurisdiction can be set globally, providing a default, and also individually for each media attachment. At display time, corresponding markup for inline image, video and audio content and, optionally, post thumbnail images is converted to a [HTML5 figure] [2], enriched with machine-readable [RDFa metadata] [3].

Included with the plugin are several stylesheets that modify the display of licensing details, including one that emulates the minimalist attribution style of the german blog [Spreeblick] [4].

The plugin includes a small web server that is able to serve the "Access-Control-Allow-Origin" header to allow embedding content on other web sites and the "X-Content-Duration" header for Ogg media. It can also serve HTTP 1.1 range-requests. For a detailed explanation why this is needed, see the [corresponding page at the Mozilla developer wiki] [5].

A video, with English and German subtitles available, shows [how the plugin is used] [Tutorial].

[Tutorial]: http://mirrors.creativecommons.org/movingimages/wordpress-cc-plugin-with-subtitles.ogv

[1]: http://creativecommons.org/about/licenses/

[2]: http://www.whatwg.org/specs/web-apps/current-work/multipage/grouping-content.html#the-figure-element

[3]: http://wiki.creativecommons.org/RDFa

[4]: http://spreeblick.com

[5]: https://developer.mozilla.org/en/Configuring_servers_for_Ogg_media

== Installation ==

Caution: Although the plugin may also work with older versions of Wordpress, it is recommended that you use at least Wordpress 3.0. You should also check that your blog's theme serves XHTML content and has a HTML5 doctype.

To install the plugin, move the directory you found this file in into the plugin directory of your Wordpress installation, then activate the plugin on the plugin manager page.

It is recommended that you choose defaults for license, rights holder and jurisdiction on the "CC License Manager" page in the administration area.

== Changelog ==

A detailed Changelog can be found at <http://code.creativecommons.org/viewgit/wordpress-cc-plugin.git/log/>.
