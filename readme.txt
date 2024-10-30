=== Carta Online ===
Contributors: carta-online
Donate link:
Tags: Carta,cursusadministratie,aanbod,publiceren,evenement,cursus,inschrijven,Carta Online,register,event administration
Requires at least: 4.2
Requires PHP: 7.4
Tested up to: 6.5
Stable tag: 2.9.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Author: Carta Online
Author URI: https://www.cartaonline.nl
Plugin URI: https://www.cartaonline.nl/carta-wordpress-plugin/

Use the Carta Online WordPress plugin to embed your offerings on your website. 

== Description ==

The Carta Online Course Administrator WordPress plugin enables integration, publishing and processing of your offerings in Carta.
With the co-offerlist shortcode your offerings can easily be integrated in your existing WordPress web pages.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the 'carta online' screen to configure the plugin
1. Direct download link: [Carta Online plugin](https://downloads.wordpress.org/plugin/carta-online.zip "Carta Online WordPress plugin") 

Detailed installation instructions can be found at [Carta Online](https://www.cartaonline.nl/carta-wordpress-plugin/ "Carta Online WordPress plugin")

== Frequently Asked Questions ==

= Does the plugin work without a valid Carta License? =

No, you need to obtain a Carta Online API key te use the plugin. A demo site is available on request. Send an e-mail with your project details to info@cartaonline.nl to obtain a demo API-KEY.

= Can I hide the thumbnail picture or location in the generated list of offerings? =

Yes, You can. You can add styling to the plugin, or to your theme. If needed every redered object can be styled individualy, by type or by usage. Use the styling option display:none to hide the disable unneeded elements.

== Screenshots ==

1. Example of rendered offerings

== Changelog ==

** version 2.12.1
 - Cache integrity check improved

** version 2.12.0
 - Improved robustness, error handling
 - Added short-term cache for non-200 API results
 - Rendertype money added

** version 2.11.4
 - Fixed error accessing field subscribeURL via co-detail rendering

** version 2.11.3
 - Fixed API compatability with old portals

** version 2.11.2
 - Improved rendering of price, price components and calculated price

** version 2.11.1
 - Fixed API backwards compatability

** version 2.11.0
 - Rendering (optional) priceComponents

** version 2.10.1
 - External subscribe link
 - Wordpress 6.5

** version 2.10.0 
 - Naming Consistency
 - Cache folder location
 
 ** Version 2.9.4
 - co_datatable more robust error handling and cache usage
 
** Version 2.9.3
 - Added API call timeout 
 - Added Grace period timeout 
 - Added Clear API Cache option in settings

** Version 2.9.2
 - WordPress 6.3 tested
 - Backwards compatability mixed case usage in [co-detail] shortcode

** Version 2.9.1
 - Fixed bug in planning-template returning incorrect location and module information
 - Fixed rendering bug in admin settings on first use

** Version 2.9.0
 - Added support for class filtering via auto-class en class attributes in co-offerlist

** Version 2.8.2
 - Fixed bug in Widget rendering

** Version 2.8.1
 - Minor fixes

** Version 2.8.0
 - Google Tag Manager
 - PHP 8 compatibility

** Version 2.7.1
 - co_datatable kolom voor planning pagina toegevoegd
 
** Version 2.7.0
 - Branding added
 - Fix casing co-detail planning subfields

** Version 2.6.1
 - WordPress 6.1.1 tested
 - Fix default filter for datatables

** Version 2.6.0
 - WordPress 6.1 tested
 - Datatable php plugin support for dynamic fields

** Version 2.5.0
 - WordPress 6.0 tested
 - Datatable php plugin provided on (base-url)/wp-content/plugins/carta-online/includes/co_datatable.php

** Version 2.4.5
 - Compatibility met de nieuwste versie van Yoast SEO verbeterd

** Version 2.4.4
 - corrected typo corrected in label "Enable cache limiter"

** Version 2.4.3
 - Added a setting "session cache limiter"  

** Version 2.4.2
 - Fix back button in browser resulting in: ERR_CACHE_MISS 	

** Version 2.4.1
 - Added %studyLoadTotal% to template co-offerlist
 - Added %studyLoadPerWeek% to template co-offerlist
 - Tested 5.8.2 compatibility

** Version 2.4.0
 - You can now use a co_search attribute (meta field) to overrule default url-based-filtering
 - Tested WordPress 4.8 compatability

** Version 2.3.8
 - Added special render option selectsize to be used for co_filter rendering with all options shown
 - Added template field %seoName% for rendering seo friendly name of class

** Version 2.3.7
 - Added special render option money-thousand-separator with default value null
 - Added special render option money-decimal-separator with default value null

** Version 2.3.6
 - Enabled rendering of (short) day names
 - Corrected class names. Renamed "co-offer-datestartdaynameshort" to "co-offer-planning-datestartdaynameshort"
 - Corrected class names. Renamed "co-offer-datestartdaynamefull"  to "co-offer-planning-datestartdaynamefull" 
 - Added class "co-offer-planning-datestartdaynameshort" in offering template
 - Added class "co-offer-planning-datestartdaynamefull" in offering template
 - Short or long name of day of week added to shortcode offering template
 - Improved exception handling
 - Fixed bug in Google Ads plugin

** Version 2.3.5
 - Rendering Techerlist based on links to teacher detail page added
 - Short or long name of day of week added to shortcode offering planning-template
 - 'Dates in YYYY will be announced later' will be rendered if start date is 01-01 and special render option dateunknownvalue="01-01"

** Version 2.3.4
 - User defined text on submitbutton when rendering offerings using the widget is not correctly generated.
 - Filtering on fields/expertise when field description contains space fixed
  
** Version 2.3.3
 - Improved session management.
 - Google ads tag renering changed. Altered page_view to view_item
 - Workaround for bug in rendering Image URL in CartaOnline
 - Fixed several issues with co-expertiselist

** Version 2.3.2
 - Fixed bug when using a Location filter on a Field page  
 
** Version 2.3.1
 - Compatibility with newest Carta Online portal (20.6)

** version 2.3
 - Tested compatibility 5.5 

** version 2.2.3
 - Introduction of special field pages with filtering on field
 - Introduction of implementation of v2 of api
 - fixed bug in google gtag script
 - new template variables: dateStartDay, dateStartMonth, dateStartYear, fieldOpen, fields, fieldLinks, fieldClose, detail_mainTeacher, detail_Teacherlist, detail_ContactDays, detail_Price, detail_PriceComponents, detail_PriceInfo
 - support for price components
 - new special render options added: carta-field-id
 - fixed bug in [co-test] shortcode

** version 2.2.2
 - Improved version of co-filter

** version 2.2.1
 - Introduced co-filter shortcode
 - Added attribute 'use-filter-selection' to filter offerlist based on selection in 'co-filter'
 
** version 2.2.0
 - Added support for Google Ads dynamic remarketing

** version 2.1.24
 - Compatibility with PHP 7.4 and WordPress 5.3 tested
 - Fixed issue with shortcode attributes containging '-' in PHP 7.x
 - Caching of 404 results added to further reduce load on server when aggressive bots visit website

** version 2.1.23
 - co-test handling of special characters improved

** version 2.1.22
 - co-detail redirect to searchpage if class not found
 - co-test fixed some bugs
 - tested wordpress 4.5.2

** version 2.1.21
 - co-expertiselist

** verison 2.1.20
 - co_expertiselist filter bug fixed
 - field-filter render option added

** version 2.1.19
 - co-offerlist added template substitution for category

** version 2.1.18
 - co-detail added fields minimumNumberOfTrainees and maximumNumberOfTrainees
 - co-detail added type="documentlist". Generates list of hyperlinks to public documents.

** version 2.1.17
 - filter-by-field fixed
 - class naming improved

** version 2.1.16
 - added [co-test] for ad-hoc shortcode testing 

** version 2.1.15
 - added rewrite base for detail pages. /rewrite_base/classid/classname => /details/?class=classid&cn=classname

** version 2.1.14
 - first-only flag added for rendering only info for first planning item
 - page-size may now be max 1000 records
 - max-one flag added for rendering exactly one planning item when multiple daugters are present
 - Nothing will be rendered for 'datelist' detail field if no planning is present
 - Detail field startendtime added  

** version 2.1.13
 - No detail picture rendered in case of empty picture
 - datelist, list of all course dates, added to detail-rendering

** version 2.1.12
 - course description added to URL
 - fix for detail page check

** version 2.1.10
 - dynamic fields in company list
 - Automatic meta tags based on course/class summary in detailpage
 - Automatic page titel base on course/class name in detailpage
 - Added Search attribute for use of shortcode in 'search results' page

** version 2.1.9
 - Added posibility for rendering a collapsable planning-overview within a offerable item

** version 2.1.8
 - Removed unneeded ?> at the end of some php files to improve compatibility

** version 2.1.7
 - special render option added: striped-zeroes
 - Support for alternate subscription page added

** version 2.1.6
 - Fix in widget for unchecking 'url-based-filter'

** version 2.1.5
 - Added options for rendering offerlist:
 - Render template and submit-caption can now be defined for each widget and shortcode

** version 2.1.4
- URL based filtering now using REQUEST_URI

** version 2.1.3 **
- API call result CompanyList 

** version 2.1.2 **
- readme.txt in English + Translation in WordPress 

** version 2.1.1 **
- Minimal Carta Online version required: 17.0.10.4747
- Fix in filtering url-based-content

** version 2.1.0 **
- Added new shortcode co-teacher
- Added url-based-filtering to shortcode co-offerlist and to widget
- Added fields filter to co-offerlist and to widget
- Added co-expertiselist to list all known fields
- Added shortcode co-detail for rendering detail pages based on Carta content
- Added shortcode co-teacher for rendering teacher profile
- Added special content type teacher-profile for attaching WordPress teacher profile to Carta Person
- Compatibiliteit WordPres 4.8
- Consistency naming shortcodes and class names

** version 2.0.10 **
- Local translation precedes online translation

** version 2.0.9 **
- Text only  

** version 2.0.8 **
- Fixed bug in admin page

** version 2.0.6 **
- Text domain changed to plugin name (carta-online)

** version 2.0.4 **
- Meta information translation

** version 2.0.3 **
- Added dutch translation
- Extended filter with fields / specialsm
- SEO friendly generated detail page
- Wordpress 4.7.5 compatibility

** initial version 1.0 **
- [co_offerlist] shortcode en widget

== Upgrade Notice ==

= 2.1.0 =
Breaking change: All class names are now using- in stead of _

= 2.0.3 =
 
= 1.0.1 =
Compatibility issues

= 1.0
No previous versions publicly available