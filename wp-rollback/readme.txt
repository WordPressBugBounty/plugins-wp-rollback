=== WP Rollback - Rollback Plugins and Themes ===
Contributors: dlocc, drrobotnik, webdevmattcrom
Tags: rollback, revert, downgrade, version, plugins
Requires at least: 6.5
Donate Link: https://wprollback.com/
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 3.0.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Rollback (or forward) any WordPress.org plugin, theme, or block like a boss.

== Description ==

Quickly and easily rollback any theme or plugin from WordPress.org to any previous (or newer) version without any of the manual fuss. Works just like the plugin updater, except you're rolling back (or forward) to a specific version. No need for manually downloading and FTPing the files or learning Subversion. This plugin takes care of the trouble for you.

= 🔙 Rollback WordPress.org Plugins and Themes =

While it's considered best practice to always keep your WordPress plugins and themes updated, we understand there are times you may need to quickly revert to a previous version. This plugin makes that process as easy as a few mouse clicks. Simply select the version of the plugin or theme that you'd like to rollback to, confirm, and in a few moments you'll be using the version requested. No more fumbling to find the version, downloading, unzipping, FTPing, learning Subversion or hair pulling.

For advanced features like premium plugin/theme support (Envato, Kadence Pro, Astra Pro, etc.), comprehensive activity logging, multisite network support, and priority support, consider upgrading to [WP Rollback Pro](https://wprollback.com/).

= Muy Importante (Very Important): Always Test and Backup =

**Important Disclaimer:** This plugin is not intended to be used without first taking the proper precautions to ensure zero data loss or site downtime. Always be sure you have first tested the rollback on a staging or development site prior to using WP Rollback on a live site.

We provide no (zero) assurances, guarantees, or warranties that the plugin, theme, or WordPress version you are downgrading to will work as you expect. Use this plugin at your own risk.

= Translation Ready =

Do you speak another language? Want to contribute in a meaningful way to WP Rollback? There's no better way than to help us translate the plugin. This plugin is translation ready. Simply header over to the WP Rollback [translation project](https://translate.wordpress.org/projects/wp-plugins/wp-rollback/) that's powered by WordPress.org volunteer translators. There you can contribute to the translation of the plugin into your language.

= Support and Documentation =

We answer all support requests [on the WordPress.org support forum](https://wordpress.org/support/plugin/wp-rollback).

WP Rollback was created to be as intuitive to the natural WordPress experience as possible. There are is no dedicated settings page or options panel. We believe that once you activate WP Rollback, you'll quickly discover exactly how it works without question.

**BUT!!**

We do have documentation on the plugin [GitHub Wiki](https://github.com/impress-org/wp-rollback/wiki).

== Installation ==

= Minimum Requirements =

* WordPress 5.5 or greater
* PHP version 7.4 or greater
* MySQL version 5.0 or greater

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don't need to leave your web browser. To do an automatic install of WP Rollback, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type "WP Rollback" and click Search Plugins. Once you have found the plugin you can view details about it such as the point release, rating and description. Most importantly of course, you can install it by simply clicking "Install Now".

= Manual installation =

The manual installation method involves downloading our donation plugin and uploading it to your server via your favorite FTP application. The WordPress codex contains [instructions on how to do this here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Updating =

Automatic updates should work like a charm; as always though, ensure you backup your site just in case.

== Frequently Asked Questions ==

= Is this plugin safe to use? =
Short answer = Yes. Longer answer = It depends on how you use it.

WP Rollback is completely safe because all it does is take publicly available versions of the plugins you already have on your site and install the version that you designate. There is no other kinds of trickery or fancy offsite calls or anything. BUT!!!

Safety largely depends on you. The WordPress website admin. We absolutely do NOT recommend rolling back any plugins or themes on a live site. Test the rollback locally first, have backups, use all the best practice tools available to you. This is intended to make rolling back easier, that's all.

= Why isn't there a rollback button next to X plugin or theme? =

WP Rollback only works with plugins or themes installed from the WordPress Repository. If you don't see the rollback link, then most likely that plugin or theme is not found on WordPress.org. This plugin does not support plugins from GitHub, ThemeForest, or other sources other than the WordPress.org Repo.

= I rolled my [insert plugin name] back to version X.X and now my site is broken. This is your fault. =

Nope. We warned you in **bold** print several times in many places. And our plugin delivered exactly what it said it would do. May the Gods of the internet pity your broken site's soul.

= Where is the complete documentation located? =

The documentation for this plugin is located on our [Github Wiki](https://github.com/impress-org/wp-rollback/wiki). This is where we make regular updates.

= What's the difference between WP Rollback Free and Pro? =

WP Rollback Free provides essential rollback functionality for WordPress.org plugins and themes. WP Rollback Pro adds powerful features including premium plugin/theme support (Gravity Forms, Elementor, Kadence Pro, Astra Pro, Divi, etc.), comprehensive activity logging, multisite network support, and priority support. [Learn more about Pro features](https://wprollback.com/).

= Can this plugin be translated? =

Yes! All strings are internationalized and ready to be translated. Simply use the languages/wp-rollback.pot file and your favorite translation tool. Once finished, please reach out to us on the WordPress.org forums or better yet, submit a pull request on the [GitHub Repo](https://github.com/impress-org/wp-rollback/).

== Screenshots ==

1. Click the Rollback link on the Plugins page to begin a plugin rollback.

2. Select the version you would like to switch to on the version selection page.

3. Confirm you would like to proceed with the rollback.

4. The plugin will update to the selected version.

5. Click the Rollback button on the Theme details screen to begin a theme rollback.

5. The theme Rollback version selection page works exactly like the plugins page.

== Upgrade Notice ==

This is the first version of this plugin. It is a tool for your convenience. Rollback at your own risk!

== Changelog ==

= 3.0.2 =
* Improvement: Simplified theme rollback button display functionality - all themes now display rollback buttons without checking WordPress.org availability.
* Improvement: Consolidated theme rollback JavaScript handlers between free and pro versions for better code maintainability.
* Improvement: Removed visual distinction between WordPress.org and premium plugin rollback links for a more consistent UI.
* Fix: Resolved fatal error on themes.php page caused by incorrect namespace references.

= 3.0.1 =
* Fix: Resolved an error with JetPack Sync and potentially other plugins that modify plugin data and return null.

= 3.0.0 =
* New: Added additional "WP Rollback" menu item under WP-Admin > Tools. 
* New: Added new "Plugin" and "Themes" list views to select a rollback more easily.
* New: WP Rollback now stores premium assets locally on your server for easy future access.
* New: Added upsells to the new [WP Rollback Pro](https://wprollback.com/).
* New: Updated plugin to support PHP versions 7.4 - 8.4.

= 2.0.7 =
* Fix: Resolved a bug with plain permalink websites which caused a `rest_no_route` error when trying to rollback a plugin or theme. Thanks, @afizesan for helping pinpoint the issue.
* Fix: Update the way the React app is loaded to suppress React 18+ warnings.
* Tweak: Bumped the plugin's minimum required WordPress version to 6.0+ for best compatibility with new React components in UI.

= 2.0.6 =
Fix: The release corrects the paths used in plugin file includes and requires. The unnecessary forward slashes at the start of each file path have been removed. This change ensures proper file inclusion and requirement, avoiding potential issues with file not found errors.

= 2.0.5 =
* New: In this version we've brought back the "trunk" option to rollback to. This allows plugin or theme developers who use trunk for beta testing to rollback to the latest trunk version. Thanks, @megamenu for suggesting this be brought back.
* Fix: Refactored how plugin avatar images are checked so that all available image types and sizes are checked. This resolves an issue where some plugins would not display an avatar image.
* Fix: On the final rollback confirmation screen, the plugin name field was outputting raw HTML. This has been fixed to properly display the plugin name, even if it contains some html characters.

= 2.0.4 =
* Fix: Resolved issue REST route not including proper permission callback which created a PHP notice. Thanks, @rom1our for submitting the issue.
* Fix: Resolve issue with REST API and multisite installs not being able to properly communicate with the endpoint.

= 2.0.3 =
* Fix: A few additional strings in JavaScript needed to be internationalized. Thanks, @pedro-mendonca for contributing the fix.

= 2.0.2 =
* Fix: Resolves an issue with WP Rollback not being able to communicate to its REST API on WordPress subdirectory installs. Thanks, @emaralive for reporting the issue. 

= 2.0.1 =
* Fix: Resolved an issue with the POT file not properly being generated at release. This resolves the issue with the new UI not being able to be translated.

= 2.0.0 =
* New: Introducing version 2.0! In this new version the UI is now better looking and snappier than ever. The branding has also been updated to look and feel more modern.

= 1.7.3 =
* Fix: Resolved an issue with plugin rollbacks not correctly setting a filepath for the plugin being rolled back. Props to WP.org user @itmesteren for the fix.

= 1.7.2 =
* Fix: Ensure that the "Rollback" button displays properly when a WordPress site only has a single theme installed. Thanks [@eldertech](https://wordpress.org/support/users/eldertech/) for your help uncovering this bug.
* Fix: Minor CSS fixes for the Rollback page.
* Tweak: Update the WordPress.org readme.txt file to have better instructions for translating the plugin. We also fixed a few typos.

= 1.7.1 =
* Fix: Prevent PHP notice when rolling back a plugin or theme on PHP 7.4.

= 1.7.0 =
* Tweak: Removed the WP Time Capsule staging button and banner.

= 1.6.0 =
* New: You now have the ability to rollback to the trunk for plugins. This is useful for beta testing releases and more. Thanks to [karpstrucking](https://github.com/karpstrucking) for making this happen. [#45](https://github.com/impress-org/wp-rollback/issues/45)
* New: Add actions "wpr_plugin_success", "wpr_plugin_failure", "wpr_theme_success", and "wpr_theme_failure" for developers.
* New: If a plugin or theme does not have any tagged releases to select from then then an informative notice appears rather than empty space for a better user experience. [#42](https://github.com/impress-org/wp-rollback/issues/42)
* Tweak: Use the WP.org API to retrieve plugin release version information for more reliable results. [#35](https://github.com/impress-org/wp-rollback/issues/35)

= 1.5.1 =
* Tweak: Added additional information about the importance of Staging and Backups and links to our preferred plugin.

= 1.5 =
* New: You can now view plugin changelogs within the rollback screen. [#7](https://github.com/impress-org/wp-rollback/issues/7)
* New: Added support for WordPress Multisite rollbacks for themes and plugins. [#22](https://github.com/impress-org/wp-rollback/issues/22)
* New: Rollback button is fixed to the bottom of the page now to prevent long scrolls for rollbacks with many versions. [#23](https://github.com/impress-org/wp-rollback/issues/23)
* New: Updated the WP.org plugin header graphic. [#37](https://github.com/impress-org/wp-rollback/issues/37)

= 1.4 =
* New: Updated plugin's text domain to the plugin's slug of 'wp-rollback' to support WordPress' GlotPress translations. [#28](https://github.com/impress-org/wp-rollback/issues/28)
* New: Gulp automated POT file generation and text domain checker. [#28](https://github.com/impress-org/wp-rollback/issues/28)
* Fix: Check the WP install's themes transient is present, if not fetch it to see if a theme can be rolled back. Allows rollbacks for new WP installs or in a case where the transient is not set properly.[#27](https://github.com/impress-org/wp-rollback/issues/27)

= 1.3 =
* Tested compatibility with WordPress 4.4 and verified as working; bumped up compatibility
* Fix: Trying to get property of non-object warning. [#20](https://github.com/impress-org/wp-rollback/issues/20)
* Improvement: Better version sorting now using usort & version_compare. [#16](https://github.com/impress-org/wp-rollback/issues/16)

= 1.2.4 =
* New: Portuguese translations added.
* Fix: Limit HTTP requests to Plugin page only. [Report 1](https://wordpress.org/support/topic/great-plugin-but-small-issue?replies=5) [Report 2](https://wordpress.org/support/topic/great-plugin-but-small-issue?replies=1#post-7234287)

= 1.2.3 =
* Fixed: XSS hardening. Thanks @secupress
* Fixed: CSRF patch regarding missing nonces. Thanks @secupress
* Improvement: escape all of the things.

= 1.2.2 =
* New: Russian translations from @Flector - thanks!
* Fix: Replaced use of wp_json_encode to support older WordPress versions. [Report](https://wordpress.org/support/topic/wordpress-requirement-issue-with-wp_json_encode)

= 1.2.1 =
* Fix: Rollback link appears on non wp.org plugins - thanks @scottopolis. [#14](https://github.com/impress-org/wp-rollback/issues/14)
* Removed unnecessary WP_ROLLBACK_VERSION constant.

= 1.2 =
* New: Swedish translation files - Thanks @WPDailyThemes.

= 1.1 =
* Fixed "Cancel" button which was falsely submitting the form.

= 1.0 =
* Initial plugin release. Yippee!
* Adds "Rollback" link to all plugins from the WordPress repo on the plugin screen.
* Adds "Rollback" link to all themes from the WordPress repo inside the modal details screen.
* The "Rollback" page allows you to choose which version you want to rollback to.
