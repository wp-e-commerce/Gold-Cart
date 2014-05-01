=== WP e-Commerce Gold Cart Plugin ===

Contributors: GetShopped.org
Tags: e-commerce, shop, cart, goldcart
Version: 2.9.7.8
Requires at least: 3.8
Tested up to: 3.9
Requires: Wp e-Commerce: 3.8.13

== Description ==

This Plugin upgrades your WP-e-Commerce shop, allowing you access to extra features and options such as product searching, multiple product image upload, extra payment gateways and Grid view.

See http://docs.getshopped.org/category/extending-your-store/premium-plugins/gold-cart/

== Installation ==

Note: The WP e-Commerce plugin must be installed and activated before Gold Cart will work.
Download WP e-commerce: http://getshopped.org

==== First Time Install with new API Key ====

If your WP-e-Commerce is series 3.7 and above (recommended)

1. Upload the 'gold_cart_files_plugin' directory within this archive to the '/wp-content/plugins/' directory

==== Moving your Gold Cart to another site ====

If you are moving your Gold Cart to another site and are going to activate it with the same key you must first deactivate it from your old site.
To do this go to Store >> Upgrades ensure your Gold Cart is currently active and 'click reset API Key' you can now Install / Activate Gold Cart on your new site.

==== Upgrading Gold Cart from WP e-commerce 3.6 series to 3.7+ series ====

1. Remove ALL 'gold_cart_files' files from the ï/wp-content/uploads/wpsc/upgrades/Í directory, This must be done first as it causes conflicts with the new files.

2. Upload the 'gold_cart_files_plugin' to the '/wp-content/plugins/' directory

3. Activate Gold Cart.

==== Downloading a new version ====

Since version 2.4, Gold Cart has automatic plugin notification, however any premium upgrades purchased from GetShopped.org can be downloaded at:

http://getshopped.org/extend/premium-upgrades-files/

You will be required to enter your Session ID (this can be found with your API key on your purchase receipt)

== Support ==

If you have any problems with Gold Cart or require more information here are you options

General help: http://getshopped.org/resources/docs/

Gold Cart Installation: http://getshopped.org/resources/docs/installation/gold-files/

Premium Support Forum: http://getshopped.org/resources/premium-support/

== Changelog ==
2.9.1
* Change: Featured thumbnail is always displayed first in a product image gallery
* Fix: Session view mode is not preserved
* Fix: Grid view is not displayed when first activated
* Fix: CSS for grid item thumbnail is not specific enough, causing compat issue with themes
* Fix: Add to Cart button is messed up in IE
* Fix: Product gallery always use 'product-thumbnails' size even when displayed in Single product view
* Fix: There is no way to switch back to the featured thumbnail after you clicked on another thumbnail in product gallery

2.9.2
* Change: Only show to gallery if the single product has more than one image
* Fix: DPS Gateway was using the wrong purchase status id to update the logs after successful payment
* Fix: LinkPoint Gateway was using the wrong purchase status id to update the logs after successful payment
* Fix: LinkPoint gateway not sending correct information to the gateway
* Add: New Authorize.net gateway supporting the CIM management
* Fix: EWAY Gateway was using the wrong purchase status id to update the logs after successful payment
* Fix: PayPal - proflow Gateway was using the wrong purchase status id to update the logs after successful payment
* Fix: BluePay was using the wrong purchase status id to update the logs after successful payment
* Fix: BluePay - send correct customer details

2.9.7.4
* Change: Minor update to API activation URL

2.9.7.5
* DPS updated name and ap to properly reflect the DPS module being used
* eway rewrite
* paystation return url
* sagepay fixes
* Gold Cart version checking
* added an option to hide CHECK payment option when using AIM / CIM module
* a.net function rename
* missing ' for a field

2.9.7.6
* eWay Update
* Vmerchant checkout ammount larger than 1000 fix
* Gold Cart Registration API updates
* Added nag screen for not registered plugin

2.9.7.7
* Duplicate Thumbnails Fix
* Linkopoint .PEM file check
* Compatibility with WPEC 3.8.13 media UI