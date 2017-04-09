# BP Block Users #
**Contributors:** [thebrandonallen](https://profiles.wordpress.org/thebrandonallen)  
**Donate link:** https://brandonallen.me/donate/  
**Tags:** buddypress, bp, block, users, block users  
**Requires at least:** 4.3 (BP 2.4.0)  
**Tested up to:** 4.7.3 (BP 2.8.2)  
**Stable tag:** 0.1.0  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

Allows BuddyPress administrators to block users indefinitely, or for a specified period of time.

## Description ##

>This plugin requires BuddyPress 2.4.0+.

Spamming and deleting users in BuddyPress both destructive actions, leaving little to no trace that the user existed. Sometimes a user needs a period of time to let cooler heads prevail. BP Block Users allows a capable user (administrators only by default) to block a user from logging into the site. The block can be applied for a specified period of time, or indefinitely, if administrators need more time to determine the best course of action.

A message will be shown on the login screen when a blocked user attempts, but ultimately fails, to login, informing them that their account has been blocked. During the block period, email notifications are suspended.

For bug reports or to submit patches or translation files, visit https://github.com/thebrandonallen/bp-block-users/issues.

## Installation ##

### From your WordPress dashboard ###

1. Visit 'Plugins > Add New'
2. Search for 'BP Block Users'
3. Activate BP Block Users from your Plugins page.

### From WordPress.org ###

1. Download BP Block Users.
2. Upload the 'bp-block-users' directory to your '/wp-content/plugins/' directory, using your favorite method (ftp, sftp, scp, etc...)
3. Activate BP Block Users from your Plugins page.

## Changelog ##

### 0.2.0 ({{release_date}}) ###
The short story is that everything is deprecated! Yikes!

This plugin was created to satisfy an immediate need, and released on the off-chance others might want something like it. Turns out, it had staying power, as there are currently about 195 more people using it than I expected.

That brings us to 0.2.0. All functions have been renamed with a better prefix `bpbu_`, or they have been moved into separate classes. BP Block Users is now a first class citizen on BuddyPress, and is loaded as a component.

All that being said, the plugin should work the same or better than before!

* Bumped minimum required WordPress version to 4.3.0.
* Bumped minimum required BuddyPress version to 2.4.0.
* BP Block Users is now loaded as a BuddyPress component.
* Added a blocked users list table to `Users > All Users` admin screen.
* Improved handling of blocked users.
* The next version will require PHP 5.3+.

### 0.1.0 (2015-09-22) ###
* Initial release.
