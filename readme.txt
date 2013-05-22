=== BuddyPress Groups Extras ===
Contributors: slaFFik, valant
Tags: buddypress, groups, ajax, meta, custom, fields, extend, admin, privacy
Requires at least: 3.4 and BP 1.6
Tested up to: 3.5.1 and BP BP 1.7.2
Stable tag: 3.5.1

After activating your groups will have ability to create any custom fields they want. Also extra page will appear with chosen content.

== Description ==

After activating your groups will have ability to create any custom fields they want. Also extra page will appear with chosen content.

= Features =
* Choose groups you want to allow custom fields.
* Create custom fields using various type (radios, select, input, textarea and text)
* Edit fields data on Edit Group Details page in Group Admin area.
* Display / hide page, where fields (chosen by you) will be displayed.
* Reorder fields.
* Create group pages (for group FAQ or wiki or whatever you want).
* Edit pages data on Edit Group Details page in Group Admin area using WP RichEditor (with embedding videos!).
* and more to come in future releases!

== Installation ==

1. Upload plugin folder `/buddypress-groups-extras/` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Groups Extras under BuddyPress menu and make customisations as needed.

== Frequently Asked Questions ==

= Why don't I see Fields/Pages in group navigation after plugin activation and enabling it for that particular group? =
Please go to group admin area and define Fields and Pages navigation labels and check them to Show. This is done to prevent displaying empty pages with no content.

== Screenshots ==

1. Admin Page
2. Custom fields on Edit Group Details page
3. Extra Fields management
4. Adding new custom field
5. Adding new group page

== Changelog ==

= 3.5.1 (22.05.2013) =
* Added More page in admin area to collect votes for new features

= 3.5 (22.05.2013) =
* Completely new admin area - tabbed and better looking
* New admin area option: User Access
* New admin area option: Apply set of fields to all groups
* Lots of other code improvements

= 3.4 (10.04.2013) =
* New admin area option: delete or preserve all plugin's data on its deactivation
* Fields DB managing fully rewritten - now in a better WordPress way (supports caching!)
* Added import fields from the previous version of a plugin
* Added Italian translation (props <a href="https://github.com/luccame">Luca Camellini</a>)
* Lots of other code improvements

= 3.3.3 (26.03.2013) =
* Added German translation (props <a href="http://www.per4mance.com/">Thorsten Wollenhöfe</a>)

= 3.3.2 (23.03.2013) =
* Fixed issue with renaming "Groups" Component into anything else (like Movies)

= 3.3.1 (22.03.2013) =
* Fixed group home page logic
* Fixed pages creation/saving issues
* Other minor cleanups (like WP 3.5 better support)

= 3.3 (19.03.2013) =
* Fixed lots of notices

= 3.2.2 (28.03.2012) =
* Admin area fixes (for WP Multisite mainly)

= 3.2.1 (22.03.2012) =
* Added ability to change groups pages slug on Edit page
* Fixed a bug with group menu display after deactivating BPGE functionality for it (hackish)
* Some other minor changes

= 3.1 (19.03.2012) =
* Fixed a major bug in displaying group pages with the same title but in different groups
* Some other minor fixes

= 3.0.1 =
* Fixed a bug with updating group data without fields

= 3.0 =
* Major update
* Default set of Fields that can be imported by group admins
* Create custom pages for each group (custom post type is used) and display them (for FAQ, or Wiki, or whatever)
* Reorder everything (group navigation links, fields and groups pages order)

= 2.0 =
* Was released by mistake

= 1.0 =
* Initial realease
