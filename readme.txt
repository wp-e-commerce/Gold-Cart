=== WP eCommerce Gold Cart ===
Contributors: WP eCommerce.org
Tags: eCommerce, shop, cart, goldcart
Version: 2.9.9
Requires at least: 3.9
Tested up to: 4.2.2
Stable tag: 2.9.9
Requires: WP eCommerce: 3.9

== Description ==

This Plugin upgrades your WP eCommerce shop, allowing you access to extra features and options such as product searching, multiple product image upload, extra payment gateways and Grid view.

== Installation ==

Note: The WP eCommerce plugin must be installed and activated before Gold Cart will work.

<a href="https://wpecommerce.org/" target="blank">Download WP eCommerce</a>

To install Gold Cart, simply upload the unzipped folder called "gold-cart" to your wp-content/plugins directory or use the Plugins page to upload the zip from there.

Next, navigate to your WordPress plugins menu and click "Activate" on "Gold Cart for WP eCommerce".

==== Moving your Gold Cart to another site ====

If you are moving your Gold Cart to another site and are going to activate it with the same key you must first deactivate it from your old site.
To do this go to Store >> Upgrades ensure your Gold Cart is currently active and click 'Reset API Key'.
You can now Install / Activate Gold Cart on your new site.

==== Downloading a new version ====

Since version 2.9.9, Gold Cart has automatic plugin updates but you will need a valid API key to be able to update it.

For older versions you can download it from your account at <a href="https://wpecommerce.org/store/your-account/" target="blank">WPeCommerce.org</a>

== Support ==

If you have any problems with Gold Cart or require more information here are you options

<a href="http://docs.wpecommerce.org/" target="blank">General help</a>

<a href="http://docs.wpecommerce.org/gold-cart-installation-and-activation/" target="blank">Gold Cart Installation</a>

<a href="https://wpecommerce.org/premium-support/" target="blank">Premium Support</a>

== Changelog ==
= 2.9.9 = 
* Automatic plugin updates now available if API key is activated.
* Update: Authorize.net Certificates Update

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
* eWay Update
* Vmerchant checkout ammount larger than 1000 fix
* Gold Cart Registration API updates
* Added nag screen for not registered plugin

= 2.9.7.5 =
* DPS updated name and ap to properly reflect the DPS module being used
* eway rewrite
* paystation return url
* sagepay fixes
* Gold Cart version checking
* added an option to hide CHECK payment option when using AIM / CIM module
* a.net function rename
* missing ' for a field

= 2.9.7.4 =
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
* Change: Featured thumbnail is always displayed first in a product image gallery
* Fix: Session view mode is not preserved
* Fix: Grid view is not displayed when first activated
* Fix: CSS for grid item thumbnail is not specific enough, causing compat issue with themes
* Fix: Add to Cart button is messed up in IE
* Fix: Product gallery always use 'product-thumbnails' size even when displayed in Single product view
* Fix: There is no way to switch back to the featured thumbnail after you clicked on another thumbnail in product gallery