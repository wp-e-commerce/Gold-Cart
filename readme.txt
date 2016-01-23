=== WP e-Commerce - Gold Cart ===

Contributors: GetShopped.org
Tags: e-commerce, shop, wp e-commerce, cart, goldcart, gold, cart, premium
Version: 3.0
Requires at least: 3.5
Tested up to: 3.8
Requires: Wp e-Commerce: 3.8.12.1

== Description ==

This Plugin upgrades your WP e-Commerce shop, allowing you access to extra features and options such as product searching, multiple product image upload, extra payment gateways and Grid view.

For more information about Gold Cart, check out our Support Forum, Premium Support and Documentation sections of GetShopped.org.

See http://docs.getshopped.org/category/extending-your-store/premium-plugins/gold-cart/

== Installation ==

Note: The WP e-Commerce plugin must be installed and activated before Gold Cart will work.
Download WP e-commerce: http://getshopped.org

==== First Time Install with new API Key ====

==== If your WP e-Commerce is 3.6.* (3.6 series) ====

1. Upload the folder 'wp-e-commerce-gold-cart' to the '/wp-content/uploads/wpsc/upgrades/' directory

2. Activate 'Gold Cart files' through the 'Plugins' menu in WordPress

3. Activate your premium upgrade with your API key and name (can be found on your purchase receipt) by going to Dashboard >> Store Upgrades (This is the main Dashboard link in your WordPress admin area)

=== If your WP-e-Commerce is 3.7.* and above (recommended) ====

1. Upload the 'wp-e-commerce-gold-cart' directory within this archive to the '/wp-content/plugins/' directory

2. Activate 'Gold Cart files' through the 'Plugins' menu in WordPress

3. Activate your premium upgrade with your API key and name (can be found on your purchase receipt) by going to Dashboard >> Store Upgrades (This is the main Dashboard link in your WordPress admin area)

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

== Notes ==

If your Gold Cart Name and API Key is not working there are a number of things to check. Here are some common things to look out for.

- If your Name and API Key does not activate, please click Reset API Key then submit your Name and API Key again
- The API Name should not have any space in it, the same goes for your API Key
- If you have checked both these conditios and your API Key still won't work please ensure your server supports cURL or fsockopen. Your hosting provider needs to allow these protocols for your website to validate the Gold Cart Name and API Key. WordPress also uses fsockopen to activate Akismet spam protection therefore if Akismet works so should WP e-Commerce. If you don't know what this means contact your hosting provider or server administrator
- Your firewall may be blocking outgoing connections, ensure the GetShopped.org IP address - 209.20.70.163 - is added to your firewall's whitelist/allowed list
- Your Gold Cart files are in the wrong place. The Gold Cart Plugin needs to be unpacked and uploaded into the /wp-content/plugins/ directory
- You have not uploaded all the files correctly. Ensure no files were corrupted or missing during transfer, if in doubt, try uploading the Gold Cart Plugin again

== Support ==

If you have any problems, questions or suggestions with Gold Cart or require more information here are your options:

- General Help: http://getshopped.org/resources/docs/
- Installing Gold Cart: http://getshopped.org/resources/docs/installation/gold-files/
- Premium Support: http://getshopped.org/resources/premium-support/
- Community Support Forum: http://www.getshopped.org/forums/
- Documentation: http://docs.getshopped.org/

== Changelog ==

3.0
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
* Changed: wpsc_activate_gold_module() is now wpsc_gc_activate_gold_module()
* Changed: wpsc_gold_activation_form() is now wpsc_gc_activation_form()
* Changed: Removed file upgrade_panel.php
* Added: Standard deprecated message for legacy functions
* Changed: Cleaned up textdomain translations
* Changed: Moved wpsc_gc_activation_form() template to functions.php
* Added: admin.css for WP Admin. based styling
* Changed: Moved all WP Admin. styling to admin.css
* Added: Admin notice when Gold Cart is not activated with dismiss option
* Changed: Removed gold_check_plugin_version()
* Changed: New automatic Plugin updater
* Added: Right Now in Store widget to WordPress Dashboard

2.9.7.6
* Change: Featured thumbnail is always displayed first in a product image gallery
* Fix: Session view mode is not preserved
* Fix: Grid view is not displayed when first activated
* Fix: CSS for grid item thumbnail is not specific enough, causing compat issue with themes
* Fix: Add to Cart button is messed up in IE
* Fix: Product gallery always use 'product-thumbnails' size even when displayed in Single product view
* Fix: There is no way to switch back to the featured thumbnail after you clicked on another thumbnail in product gallery

2.9.7.5
* DPS updated name and ap to properly reflect the DPS module being used
* eway rewrite
* paystation return url
* sagepay fixes
* Gold Cart version checking
* added an option to hide CHECK payment option when using AIM / CIM module
* a.net function rename
* missing ' for a field

2.9.7.4
* Change: Minor update to API activation URL

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

2.9.1
* eWay Update
* Vmerchant checkout ammount larger than 1000 fix
* Gold Cart Registration API updates
* Added nag screen for not registered plugin
