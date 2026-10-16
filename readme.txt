=== AVX Waiver & Consent for WooCommerce ===
Contributors: avavictoria
Tags: woocommerce, waiver, consent, disclaimer, signature
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add signed waiver, consent, disclaimer, or terms acknowledgements to WooCommerce classic checkout and record acceptance per order.

== Description ==

AVX Waiver & Consent for WooCommerce adds a configurable waiver popup to the WooCommerce Classic Checkout. A customer enters a participant name, types the matching name as a signature, and confirms the final agreement. The plugin is disabled until an administrator configures at least one clause and enables it.

WooCommerce is required. Checkout Blocks are not supported.

== Features ==

* One waiver with up to five configurable clause rows.
* Mandatory clauses and customer-selectable optional clauses.
* Configurable heading, labels, introductory text, agreement text, and popup button label.
* Typed-name signature with server-side validation.
* Private, order-level metadata for participant name, typed signature, selected optional points, server-generated signature date, and agreement.
* High-Performance Order Storage (HPOS) compatibility declaration and WooCommerce order metadata API use.
* Small waiver information box on the WooCommerce order edit screen.

== Current limitations ==

* Classic Checkout only; no Checkout Blocks or Store API integration.
* One store-wide waiver; no product- or category-specific rules.
* A maximum of five clauses.
* No PDF, document snapshot, hash, or historical evidence version.
* No waiver content in customer emails or My Account.
* Typed-name signature only; no drawn signature or identity verification.
* Subscription carts and renewal checkouts are excluded.

== Installation ==

1. Install and activate WooCommerce.
2. Install this plugin through Plugins > Add New > Upload Plugin, or copy its folder to wp-content/plugins/.
3. Activate AVX Waiver & Consent for WooCommerce.
4. Use a WooCommerce Classic Checkout page. The waiver is disabled until configured.

== Configuration ==

1. Open WooCommerce > AVX Waiver, or use the Settings link on the Plugins page.
2. Enter a title or text in at least one of the five clause rows. A row may contain a title, text, or both; an empty row is not displayed.
3. Check Separate optional consent for a clause only when customers should be able to select it. An unchecked clause is presented as mandatory. Optional selection requires clause text.
4. Adjust the heading, labels, introductory text, agreement text, and popup button label as needed.
5. Check Enable waiver and save. Enabling with all five rows empty leaves the waiver disabled.
6. Test the Classic Checkout flow before accepting orders.

== Frequently Asked Questions ==

= Does this work with Checkout Blocks? =

No. It supports WooCommerce Classic Checkout only.

= Where is consent stored? =

The plugin stores the participant name, typed signature, selected optional points, server-generated signature date, and agreement in private order-level metadata. It does not add cart-item or line-item metadata.

= Can customers leave optional clauses unchecked? =

Yes. Signing without selecting an optional clause is valid.

= Does it support HPOS? =

Yes. The plugin declares compatibility with WooCommerce High-Performance Order Storage and uses the WooCommerce order metadata API.

== Privacy ==

The plugin sends waiver form data only to the site's own WordPress AJAX endpoint and WooCommerce checkout for validation. It stores the consent details described above in the site's order database. It makes no external requests and adds no tracking or user-data transmission to third parties. Store owners remain responsible for their own privacy notices, retention policy, and access controls.

== Legal disclaimer ==

This plugin helps collect and record consent. It does not provide legal advice or guarantee that a waiver is legally enforceable. Consult a qualified legal professional about your document and jurisdiction.

== Custom development ==

Product- or category-specific rules, document evidence extensions, email/account output, Checkout Block support, and other WooCommerce integrations can be commissioned from the developer. Use the contact link on the plugin settings page.

== Changelog ==

= 1.0.0 =

* First public release with configurable Classic Checkout waiver, typed signature, server-side validation, order-level metadata, and HPOS compatibility.
