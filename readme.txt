=== YAST : Yet Another Support Tool ===
Contributors: bastho, n4thaniel
Tags: ticket, support, wphelp, assistance, tickets, multisite
Requires at least: 3.1
Tested up to: 4.1.1
Donate link: http://ba.stienho.fr/#don
Stable tag: /trunk
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bug tickets management, throw classic site, multisite plateform

== Description ==

Bug tickets management, throw single site or multisite plateform
this plugin allows WordPress users to open supports tickets from front or admin pages.
The purpose is to manage a community of webmasters on a WP plateform, to give assitance for editing or publishing, get bugs from themes or plugins.

#### Full integration

* Custom categories to filter tickets
* For logged-in users: a submit button in the admin bar
* For every one: possibility to use a form (with shortcode). The form can assign tickets to a specific category.

#### Here some usefull tools provided to help resolve tickets

* Automaticly add page URL, Browser details and and POST variables to new tickets
* Filter by categories
* User assignation
* Comments, with spent time
* Merge tickets
* Close or re-open tickets

### Form submission shortcode

#### Basic shortcode

`[BugTickets_form]` Will output a support form, like the one in the admin bar

#### Basic shortcode options
 Basic options are:

* type (string, must be a ticket_type slug)
* title (string)
* only_known (true/false) filter logged-in users or not
* force_ssl (true/false)

example :
 `[BugTickets_form type="bug" title="New bug" only_known=false force_ssl=true]`


#### Full shortcode use
You can assist the description filling by using custom form fields like :
`<field_type field_name (field_label)>`

usable field types are :

* text
* textarea
* select
* radio

add possible values for *select* and *radio* with "comma,separated,values"

example :
`
[BugTickets_form type="bug" title="New bug" only_known=false force_ssl=true]
<text email (your email)>
<select color (Your prefered color) "Red,Blue,Green">
<textarea description (Description)>

Some normal text, being stylized by the editor

<radio ok (Ok?) "yes,no"> this question is very important !
[/BugTickets_form]
`

<a id="external">
### External website form
</a>
You can oput a submission form in an external website. wtih 2 steps :

1. Allow the remote host, in Support tickets > Options
2. Insert the javascript file into a page of your remote site
3. The form is hidden by default and can be opened by a button.

Some details about this feature :

the jascript URL looks like :
//your-wp-site.com/wp-admin/admin-ajax.php?action=yast_form_js

So, just put this ligne into your HTML:
`
<script src="//your-wp-site.com/wp-admin/admin-ajax.php?action=yast_form_js"></script>
`

Je javascript auto add a button to open the form, but you can use your own just by adding the class "yast-dist-support-button" a any HTML element.
A click on an HTML element with class "yast-dist-support-button" will open the support form.

You can cutomize the by by adding parameters to the script URL:

* autoload: if set to "no", do not append the form to the body, but wait a click of the user
* visibility: will force "private" or "public"
* user: used to identify the reporter. can be a username, login or email
* type: any ticket type defined in your WordPress
* title: any string

example:

`
<script src="//your-wp-site.com/wp-admin/admin-ajax.php?action=yast_form_js&autoload=no&visibility=private&type=bug&username=<?php $current_user['email']?>"></script>
`


### Credits

 Icons: from http://icomoon.io under GPL / CC BY 3.0 licences

== Installation ==

1. Upload `yast` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress admin

== Frequently asked questions ==

= Does this plugin intend to replace a full support tool?=

No, this plugin intends to let admins hemp other user on a single or multi-site WP

== Screenshots ==

1. Ticket list
2. Single ticket view
3. New ticket form

== Changelog ==

= 1.3.1 =
* [BUG] Fix bad nonce_field name in single page

= 1.3.0 =
* [UI] Add "auto spent time" and "comment and close"
* [UI] Add submenus for ticket types in the admin bar
* [UI] Add ticket types post count
* [Form] Add search in existing tickets while typing a new title
* [Code] Make code more readable

= 1.2.1 =
* [Edit] Better redirections after actions
* [AdminBar] Make support form visible on mobile

= 1.2.0 =
* [List] filter by ticket type
* [List] better responsiveness
* [Notifications] more verbose titles
* [Notifications] use username in email of creation confirmation
* [Options] improved UI
* [Localization] update french locale

= 1.1.3 =
* [Single] fix ajax url bug

= 1.1.2 =
* [Single] add standalone display option with bootstrap support

= 1.1.1 =
* [external form by JS] add no_autoload option
* [external form by JS] automaticly add button if needed

= 1.1.0 =
* Add: Possibility to add a form in an external site
* Add: Tickets can be displayed on front
* Add: Option to force visibility in shortcode
* Add: Form is now bootstrap ready
* Notify every one concerned
* Performances improvements
* Some bug fix

= 1.0.4 =
* Display open AND publish tickets by default

= 1.0.3 =
* Found a way to display tickets with empty title

= 1.0.2 =
* explicit localization strings
* improved nonce security
* Fix XSS vuln
* Code cleanup

thanks to @juliobox

= 1.0.1 =
* Fix localization path

= 1.0.0 =
* Initial release

== Upgrade notice ==

= 1.0.2 =
Security update thanks to @juliobox

== Languages ==

* en	: 100%
* fr_FR : 100%
