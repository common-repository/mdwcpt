=== MD Price Tracker for WooCommerce ===
Contributors: maxidevs
Tags: woocommerce, price tracking, email, notification
Requires at least: 4.4
Tested up to: 5.6.1
Requires PHP: 5.5
Stable tag: 0.3.0
Donate link: https://maxidevs.com
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

MD Price Tracker allows customers of WooCommerce based stores keep track of products prices drops

== Description ==

[<strong>Live Demo</strong>](https://maxidevs.com/mdwcpt-demo/)

MD Price Tracker for WooCommerce extends woocommerce functionality by allowing store customers to subscribe on products price cheapening with user-friendly modal forms. Shop managers will see all subscriptions in the wordpress backend and can decide whether to adjust prices or schedule a sale for subscribed products. As soon as the price of the product will be reduced to user-expected, an automatic notification email will be sent to the subscriber.

= Main features of MD Price Tracker for WooCommerce =

* All woocommerce built-in product types are supported, including variable and grouped products
* User-friendly ajaxified modal subscription forms 
* Flexible plugin settings built with native woocommerce settings api
* About 10 options for choosing the position where to display the modal form trigger button on single product page
* Email notifications about products cheapening are sent asynchronously in the background to overcome problems such as server crashes or message duplication.
* All frontend strings and content of email notification messages can be translated thru plugin settings
* GDPR Compliance: option to show Privacy Policy url on each form and supports Export/Erase Personal Data
* Option to show tracking forms only for guests, only for customers or for both
* Option to enable Google reCaptcha to protect form only for guests or for everyone

== Installation ==
= Minimum Requirements =

* PHP 5.5 or greater
* WooCommerce 3.6 or greater

= Automatic installation =

1. Log in to your WordPress dashboard and navigate to the Plugins menu and click “Add New”
2. In the search field type “MD Price Tracker for Woocommerce”,  then click “Search Plugins”
3. Once you’ve found our plugin you can install it by simply clicking “Install Now”
 
= Manual installation =

Manual installation method requires downloading the MD Price Tracker for Woocommerce plugin and uploading it to your web server via your favorite FTP application. The WordPress codex contains [instructions on how to do this here](https://wordpress.org/support/article/managing-plugins/#manual-plugin-installation).

== Frequently Asked Questions ==
= Can I test the settings of the admin panel? =
Yes! You can setup [<strong>Pesonal Demo Sandbox</strong>](https://maxidevs.com/mdwcpt-demo/admin-demo/)

= Where can i get support? =
Currently we provide support thru Free WordPress Support Forums, submit a ticket and we’ll look in to it as soon as possible.

== Screenshots ==
1. How it works
2. Subscription form variable product opened
3. Dashboard list of subscribers screen
4. Dashboard General settings
5. Dashboard Emails configuration settings

== Changelog ==

= Version 0.1.0 =
* Initial Release

= Version 0.1.1 - 2019-06-14 =
* Localization - added missed textdomain for some strings
* Dev - removed unused methods from MDWCPT_List_Table class
* Dev - added upgrade method to MDWCPT_Loader class

= Version 0.1.2 - 2019-07-17 =
* Fix - MDWCPT_Utils::get_option() method sometimes did not return the default value when necessary
* Dev - in some places, the ctype_digit() method has been replaced by filter_var() for more optimal data validation

= Version 0.1.3 - 2019-08-10 =
* Fix - In some cases, price cheapening emails were not sent due to race conditions
* Translation - corrected several typos

= Version 0.2.0 - 2020-01-20 =
* Fix - support for decimals in expected price field
* Dev - added action hooks mdwcpt_form_fields_after, mdwcpt_subscription_created, mdwcpt_subscription_updated to be able to submit and process additional data through our forms

= Version 0.2.1 - 2020-02-02 =
* Fix - activating a plugin with deactivated woocommerce leads to an error
* Fix - corrected "step" attribute of the expected price field, which sometimes blocked the input of possible values

= Version 0.2.2 - 2020-03-27 =
* Fix - automatic deactivation of the plugin if woocommerce is disabled
* Fix - impossible to enter a value less than 1 in the expected price field

= Version 0.2.3 - 2020-04-16 =
* Fix - due to improper escaping, some special characters were removed from the unsubscribe link, which made it impossible to unsubscribe in rare cases

= Version 0.3.0 - 2021-02-05 =
* New - for better compatibility with page builders shortcode [mdwcpt_form] now can be used without "product_id" attribute inside wordpress loop
* Fix - added support for plugins which load html thru ajax ( search filters, infinite scroll pagination... )
* Dev - reworked google reCaptcha handling