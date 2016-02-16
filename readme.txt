=== WP eCommerce Gold Cart ===
Contributors: WP eCommerce.org
Tags: eCommerce, shop, cart, goldcart
Version: 3.0
Requires at least: 4.0
Tested up to: 4.5
Stable tag: 3.0
Requires: WP eCommerce: 3.11

== Description ==

This Plugin upgrades your WP eCommerce shop, allowing you access to extra features and options such as product searching, multiple product image upload, extra payment gateways and Grid view.

== Installation ==

<a href="https://wpecommerce.org/" target="blank">Download WP eCommerce</a>

To install Gold Cart, simply upload the unzipped folder called "gold-cart" to your wp-content/plugins directory or use the Plugins page to upload the zip from there.

Next, navigate to your WordPress plugins menu and click "Activate" on "Gold Cart for WP eCommerce".

== Support ==

If you have any problems with Gold Cart or require more information here are you options

<a href="http://docs.wpecommerce.org/" target="blank">General help</a>

<a href="http://docs.wpecommerce.org/gold-cart-installation-and-activation/" target="blank">Gold Cart Installation</a>

<a href="https://wpecommerce.org/support/" target="blank">Premium Support</a>

== Changelog ==

= 3.0 February 2016 =
* Added: Welcome screen on Plugin activation
* Added: legacy.php within /includes/ for deprecated Gold Cart functions
* Changed: gold_shpcrt_display_gallery() is now wpsc_gc_shpcrt_display_gallery()
* Changed: gold_shpcrt_search_sql() is now wpsc_gc_shpcrt_search_sql()
* Changed: gold_shpcrt_search_form() is now wpsc_gc_shpcrt_search_form()
* Changed: product_display_list() is now wpsc_gc_product_display_list()
* Changed: gold_shpcrt_xmlmaker() is now wpsc_gc_shpcrt_xmlmaker()
* Changed: gold_shpcrt_add_gateways() is now wpsc_gc_shpcrt_add_gateways()
* Changed: gold_shpcrt_install() is now wpsc_gc_install()
* Changed: product_display_grid() is now wpsc_gc_product_display_grid()
* Changed: Removed file grid_display_functions.php
* Changed: Cleaned up textdomain translations
* Added: admin.css for WP Admin. based styling
* Changed: Moved all WP Admin. styling to admin.css
* Added: Right Now in Store widget to WordPress Dashboard
* Fixed: Live search working again. Yay !

= 2.9.10 =
* Feature: Automatic plugin updates now available only with License keys. Product License must be registered on your website under Plugins menu.
* Update: SagePay now supports Authenticate payment type
* Update: Compatibility with WordPress 4.3
* Fixed: Some grid view forced styles have been removed
* Update: PayFlow Pro Card CVV length now accepts 4 characters
* Update: Authorize.net Endpoints and Security Certificate Updates

= 2.9.9 = 
* Automatic plugin updates now available if API key is activated.
* Update: Authorize.net Certificates Update
* Add: Gold Cart requires PHP 5.3 to work

= 2.9.8 =
* Update: BluePay to send cart items in the comments field.
* Update: SagePay gateway updated to protocol 3.00 ( Effective July 31st 2015 )
* Update: eWay now uses Direct Connection and client side encryption of card fields. No need for PCI Compliance

= 2.9.7.8 =
* Various Fixes

= 2.9.7.7 =
* Duplicate Thumbnails Fix
* Linkopoint .PEM file check
* Compatibility with WPEC 3.8.13 media UI


= 2.9.7.6 =
* Change: Featured thumbnail is always displayed first in a product image gallery
* Fix: Session view mode is not preserved
* Fix: Grid view is not displayed when first activated
* Fix: CSS for grid item thumbnail is not specific enough, causing compat issue with themes
* Fix: Add to Cart button is messed up in IE
* Fix: Product gallery always use 'product-thumbnails' size even when displayed in Single product view
* Fix: There is no way to switch back to the featured thumbnail after you clicked on another thumbnail in product gallery

= 2.9.7.5 =
* DPS updated name and ap to properly reflect the DPS module being used
* eway rewrite
* paystation return url
* sagepay fixes
* Gold Cart version checking
* added an option to hide CHECK payment option when using AIM / CIM module
* a.net function rename
* missing ' for a field

= 2.9.7.4
* Change: Minor update to API activation URL

= 2.9.2 =
* Change: Only show to gallery if the single product has more than one image
* Fix: DPS Gateway was using the wrong purchase status id to update the logs after successful payment
* Fix: LinkPoint Gateway was using the wrong purchase status id to update the logs after successful payment
* Fix: LinkPoint gateway not sending correct information to the gateway
* Add: New Authorize.net gateway supporting the CIM management
* Fix: EWAY Gateway was using the wrong purchase status id to update the logs after successful payment
* Fix: PayPal - proflow Gateway was using the wrong purchase status id to update the logs after successful payment
* Fix: BluePay was using the wrong purchase status id to update the logs after successful payment
* Fix: BluePay - send correct customer details

= 2.9.1 =
* eWay Update
* Vmerchant checkout ammount larger than 1000 fix
* Gold Cart Registration API updates
* Added nag screen for not registered plugin