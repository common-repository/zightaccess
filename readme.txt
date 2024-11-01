=== ZigHtaccess ===
Contributors: ZigPress
Tags: htaccess editor,htaccess,editor,file,zig,zigpress,classicpress
Donate link: https://www.zigpress.com/donations/
Requires at least: 4.8
Tested up to: 5.4
Requires PHP: 5.6
Stable tag: 1.1
License: GPLv2 or later

Edit your .htaccess file from the WordPress admin console.

== Description ==

**Due to abuse received from plugin repository users we are ceasing development of free WordPress plugins and this is the last release of this plugin. It will be removed from the repository in due course. Our pro-bono plugin development will now be exclusively for the ClassicPress platform.**

A simple .htaccess file editor that lets you edit your .htaccess file from the WordPress admin console and makes a backup each time you save changes. Has been tested on Apache server on Linux, MacOS and Windows.

Compatible with ClassicPress.

To use, go to Admin > Tools > ZigHtaccess. You must be an administrator-level user to access the plugin's editor page.

If you find this plugin useful, please consider adding a positive review on the plugin's repository page.

If you make changes and end up being locked out of your site's admin pages, you will need to FTP in to your site and delete the .htaccess file at root level (the one in the same folder as your wp-config.php file). You can then copy the backup .htaccess file from the wp-content/zightaccess folder to replace it. Your FTP client must be set to show hidden files. We recommend FileZilla.

NOTE: This plugin is offered without any kind of warranty, promise or guarantee, and the plugin author bears no responsibility for any problems or loss of code or data incurred as a result of using this plugin. By installing this plugin you are agreeing to this condition.

== Installation ==

Go to Admin > Plugins > Add New and enter ZigHtaccess in the search box. Click Install then Activate.

== Changelog ==

= 1.1 =
* Notice of cessation of free WordPress plugin development
= 1.0.5 =
* Verified compatibility with WordPress 5.3.x
* Verified compatibility with ClassicPress 1.1.x
= 1.0.4 =
* Verified compatibility with WordPress 5.2.x
* Verified compatibility with ClassicPress 1.0.x
= 1.0.3 =
* Corrected WordPress tested up to version - it seems you can't specify a version still in beta
= 1.0.2 =
* Improved SSL detection method when setting editor form action attribute (fixes inability to save changes on certain hosts)
= 1.0.1 =
* Added code to avoid duplicate admin notices caused by global admin notice handler in other ZigPress plugins
= 1.0.0 =
* First public release
