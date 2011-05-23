== Description ==


For more information about Gold Cart, check out our Support Forum, Premium Support and Documentation sections of GetShopped.org.


http://getshopped.org/resources/docs/installation/gold-files/


== Installation ==


1. If you are upgrading Gold Cart from an older version be sure to delete your existing Gold Cart files or it may cause conflicts


2. Upload the folder 'gold_cart_files_plugin' to the '/wp-content/plugins/' directory


3. Activate the Gold Cart Plugin through the 'Plugins' menu in WordPress


4. Open the Dashboard > Store Upgrade page and under Gold Cart enter the Name and API Key provided in your Purchase Receipt


4. After "Congratulations! Gold cart is now activated" appears all all Gold Cart features are enabled.


For more information read the Support section of this readme.


== Notes ==


If your Gold Cart Name and API Key is not working there are a number of things to check. Here are some common things to look out for.


- If your Name and API Key does not activate, please click Reset API Key then submit your Name and API Key again


- The API Name should not have any space in it, the same goes for your API Key


- If you have checked both these conditios and your API Key still won't work please ensure your server supports cURL or fsockopen. Your hosting provider needs to allow these protocols for your website to validate the Gold Cart Name and API Key. WordPress also uses fsockopen to activate Akismet spam protection therefore if Akismet works so should WP e-Commerce. If you don't know what this means contact your hosting provider or server administrator


- Your firewall may be blocking outgoing connections, ensure the GetShopped.org IP address - 209.20.70.163 - is added to your firewall's whitelist/allowed list


- Your Gold Cart files are in the wrong place. The Gold Cart Plugin needs to be unpacked and uploaded into the /wp-content/plugins/ directory


- You have not uploaded all the files correctly. Ensure no files were corrupted or missing during transfer, if in doubt, try uploading the Gold Cart Plugin again


== Support ==


If you have any problems, questions or suggestions please raise a Premium Support topic or a community Support Forum topic from the links below.


Documentation: http://docs.getshopped.org/


Premium Support: http://getshopped.org/resources/premium-support/


Support Forum: http://getshopped.org/forums/