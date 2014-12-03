=== YAST : Yet Another Support Tool ===
Contributors: bastho, n4thaniel
Tags: ticket, support, wphelp, assistance, tickets, multisite
Requires at least: 3.1
Tested up to: 4.0.1
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
* For logged-in users: a submit button in he admin bar
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

= 1.0.1 =
* Fix localization path

= 1.0.0 =
* Initial release

== Upgrade notice ==

No particular informations

== Languages ==

* en	: 100%
* fr_FR : 100%
