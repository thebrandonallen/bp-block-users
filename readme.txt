=== BP Block Users ===
Contributors: thebrandonallen
Donate link: https://brandonallen.me/donate/
Tags: buddypress, bp, block, users, block users
Requires at least: 4.3
Tested up to: 4.8
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Allows BuddyPress administrators to block users indefinitely, or for a specified period of time.

== Description ==

>This plugin requires BuddyPress 2.4.0+.

Sometimes a user in your community needs a some time to cool off. In BuddyPress, spamming or deleting the user is a destructive action, leaving little to no trace that the user existed. BP Block Users allows a capable user (administrators only by default) to block a user from logging into the site. Users can be blocked for a specified period of time, or indefinitely, if administrators need more time to determine the best course of action.

A message will be shown on the login screen when a blocked user attempts, but ultimately fails, to login, informing them that their account has been blocked. During the block period, email notifications are suspended.

For bug reports or to submit patches or translation files, visit https://github.com/thebrandonallen/bp-block-users/issues.

== Installation ==

= From your WordPress dashboard =

1. Visit 'Plugins > Add New'
2. Search for 'BP Block Users'
3. Activate BP Block Users from your Plugins page.

= From WordPress.org =

1. Download BP Block Users.
2. Upload the 'bp-block-users' directory to your '/wp-content/plugins/' directory, using your favorite method (ftp, sftp, scp, etc...)
3. Activate BP Block Users from your Plugins page.

== Frequently Asked Questions ==

**What happened to versions `0.2.0`, `0.3.0`, `0.4.0`, `0.5.0`, `0.6.0`, `0.7.0`, `0.8.0`, and `0.9.0`?**

You blinked, and you missed them.

In reality, the changes made in version `1.0.0` were drastic enough, that the version change needed to help convey the message. While BP Block Users won't be following [semver](http://semver.org/) just yet, the `1.0.0` release needed to be treated as such.

== Changelog ==

= 1.0.1 =
* Release date: TBD
* Update compatability to WordPress 4.8

= 1.0.0 =

* Release date: April 12, 2017

* **TLDR**

	* The plugin has been completely rewritten.

* **The Fuller House**

	This plugin was written as a quick solution to an immediate need. It was then released on the off-chance others might find it useful. Turns out, it had staying power. At the time of this release, there are about 195 more people using it than I expected.

	That brings us to version `1.0.0`. *All* functions from version `0.1.0` have been deprecated. The deprecated functions will still perform as they did in `0.1.0`, but they will throw a deprecation warning when using `WP_DEBUG`.

	If you were using the plugin as-is, with no custom functionality built on top, then you don't need to do anything further. If you were using any of the functions/filters/hooks from version `0.1.0` for custom functionality, please see the [Upgrading to 1.0.0](https://github.com/thebrandonallen/bp-block-users/wiki/Upgrading-to-1.0.0) wiki page.

	Despite these major changes, the plugin should work the same or better than before!

* **Minimum Requirements**

	* Bumped minimum required WordPress version to 4.3.0.
	* Bumped minimum required BuddyPress version to 2.4.0.

* **Enhancements**

	* BP Block Users is now loaded as a BuddyPress component.
	* The `Block User` terminology has been changed to `Block Member` on the front-end to be more consistent with BuddyPress terminology. Props joost-abrahams [[GH#4](https://github.com/thebrandonallen/bp-block-users/pull/4)].
	* Added a blocked users list table to `Users > All Users` admin screen.
	* Added WP-CLI commands. Enter `wp bp-block-users --help` in your terminal for usage details.

* **Future Updates**

	* The next major version will require PHP 5.3+.
	* Deprecated functions, filters, and actions will be removed in a future release. The target right now is version `1.3.0`. Upgrade any code you've written against version `0.1.0`.

= 0.1.0 =

* Release date: September 22, 2015
* Initial release.
