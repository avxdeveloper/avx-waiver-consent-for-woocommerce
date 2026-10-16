# AVX Waiver & Consent for WooCommerce

Add a configurable waiver, consent, disclaimer, or terms acknowledgement to WooCommerce Classic Checkout and record acceptance with each order.

Customers enter a participant name, type the matching name as a signature, and confirm the final agreement. Store administrators configure the waiver and review recorded consent in the order administration screen.

**[Download on WordPress.org](https://wordpress.org/plugins/avx-waiver-consent-for-woocommerce/)**

## 👩🏻‍💻 Custom WordPress & WooCommerce Development

Need a consent workflow tailored to your store, products, or services?

I’m Ava-Victoria Aleksson, a WordPress developer working with WordPress since 2017. I develop custom WooCommerce functionality, plugins, checkout workflows, and API integrations.

Custom development can include:

- Product- or category-specific waiver rules.
- Styling and layouts that match your website.
- Consent information in customer emails or My Account.
- PDF output, document snapshots, and additional evidence records.
- Checkout Blocks support.
- Integrations with booking, membership, and other WooCommerce workflows.

These are examples of separately commissioned development, not features included in the free plugin. Scope, feasibility, pricing, and delivery time are agreed individually.

**[Discuss your project by email →](mailto:avxplugins@gmail.com)**

Please describe your current website setup and the workflow you need. Do not include passwords or customer information in public GitHub issues.

## Features

- One store-wide waiver with up to five configurable clauses.
- Mandatory clauses and customer-selectable optional clauses.
- Configurable heading, labels, introductory text, agreement text, and popup button label.
- Typed-name signature with server-side validation.
- Private order metadata recording the participant name, typed signature, selected optional points, server-generated signature date, and agreement.
- Waiver information box on the WooCommerce order edit screen.
- High-Performance Order Storage (HPOS) compatibility declaration and use of the WooCommerce order metadata API.

The waiver remains disabled until an administrator configures at least one clause and enables it.

## Compatibility and limitations

**WooCommerce is required. This version supports Classic Checkout only.**

- Checkout Blocks and Store API checkout are not supported.
- One store-wide waiver; no product- or category-specific rules.
- A maximum of five clauses.
- Typed-name signatures only; no drawn signature or identity verification.
- No PDF, document snapshot, hash, or historical evidence version.
- No waiver content in customer emails or My Account.
- Subscription carts and renewal checkouts are excluded.

See [readme.txt](readme.txt) for the release’s declared WordPress and PHP requirements.

## Installation and configuration

1. Install and activate WooCommerce.
2. Install and activate the plugin from [WordPress.org](https://wordpress.org/plugins/avx-waiver-consent-for-woocommerce/).
3. Ensure your checkout page uses WooCommerce Classic Checkout.
4. Open **WooCommerce → AVX Waiver**.
5. Configure at least one clause. Empty clause rows are not displayed.
6. Enable **Separate optional consent** for clauses customers may select individually. Optional selection requires clause text. Other clauses form part of the mandatory document accepted by the final signature.
7. Adjust the heading, labels, introductory text, agreement text, and popup button label.
8. Select **Enable waiver** and save.
9. Place a test order and inspect the recorded consent before accepting customer orders.

For a manual development installation, place the repository files inside:

`wp-content/plugins/avx-waiver-consent-for-woocommerce/`

Then activate the plugin in WordPress.

## Privacy

The plugin submits waiver form data to your own site's WordPress AJAX endpoint and WooCommerce checkout for validation. Consent details are stored in your site's order database.

The plugin makes no external requests and adds no tracking or user-data transmission to third parties.

Store owners remain responsible for privacy notices, retention policies, and access controls.

## Legal disclaimer

This plugin helps collect and record consent. It does not provide legal advice or guarantee that a waiver is legally enforceable.

Consult a qualified legal professional about your document and jurisdiction.

## Support

- [WordPress.org support forum](https://wordpress.org/support/plugin/avx-waiver-consent-for-woocommerce/)
- [Custom development enquiries](mailto:avxplugins@gmail.com)
- GitHub issues for reproducible bugs. Remove credentials and personal data before posting.

## Changelog

See [readme.txt](readme.txt) for the release changelog.

## License

GPL version 2 or later. See [LICENSE.txt](LICENSE.txt).