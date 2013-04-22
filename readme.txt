=== Custom Shortlink Structure ===
Contributors: a.hoereth
Plugin Name: Custom Shortlink Structure
Plugin URI: http://yrnxt.com/wordpress/custom-shortlink-structure/
Tags: shortlink, url, permalink, custom
Author: Alexander Höreth
Author URI: http://yrnxt.com/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=a%2ehoereth%40gmail%2ecom
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 3.1
Tested up to: 3.6
Stable tag: 1.0

Change the default WordPress shortlink structure dynamically using your permalink settings.

== Description ==

This plugin will add new settings to your Settings -> Permalinks screen for you to
specify your blog's shortlink structure. By default WordPress shortlinks look like
`http://yrnxt.com/?p=153`. Using this plugin you can specify every other structure
you like, e.g. [http://yrnxt.com/~153](http://yrnxt.com/~153).

Specifying new shortlink structures will never break WordPress' default. Also when
changing the shortlink strucutre again afterwards old structures won't break until you
explicitly remove them.

Take a look at the [screenshots](http://wordpress.org/extend/plugins/custom-shortlink-structure/screenshots/).

== Installation ==

1. Visit your WordPress Administration interface and go to Plugins -> Add New
2. Search for "*Custom Shortlink Structure*", and click "*Install Now*" below the plugin's name
3. When the installation finished, click "*Activate Plugin*"

Now visit Settings -> Permalinks and specify your own shortlink structure. Changes here won't
break WordPress default `?p=%post_id%` structure.

== Changelog ==

= 1.0: 2013-04-23 =
* Release

== Upgrade Notice ==

== Screenshots ==

1. Post edit screen: Get Shortlink
2. Settings -> Permalinks

== Frequently Asked Questions ==

= When visiting a shortlink defined using the plugin I just get redirected to home? =
Some other plugins might interfere with redirections. Using WordPress SEO's *Redirect ugly URL's to clean permalinks* setting for example will break this plugin.

= I specified a custom shortlink structure but get a "Not Found" error on load =
The plugin only works when pretty permalinks are enabled. Under Settings -> Permalinks
specify a permalink structure other then default.