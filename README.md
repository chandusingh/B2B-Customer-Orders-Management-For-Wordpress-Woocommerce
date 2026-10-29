# Manual B2B Orders for WooCommerce
Contributors: ashawebtech
Developer: Chandrabhan Singh Dhakad
Developer URI: https://ashawebtechitsolutions.com/
Tags: woocommerce, b2b, manual order, custom products, shadowfax
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
WC requires at least: 6.0
Stable tag: 1.0.0
License: GPLv2 or later

Create manual / B2B WooCommerce orders from a private custom product list that is not shown on your website.

## Description

Some orders (B2B, bulk, offline) are for products you do not list on the website. This plugin lets you keep a private product list and create real WooCommerce orders from it, so they:

* count in WooCommerce sales and analytics
* appear in WooCommerce > Orders
* trigger your integrations (Shadowfax, invoice plugins, etc.) when status is "Processing"

### Features
* Custom Products manager (name, default price, active/inactive)
* Add New Order screen, similar to WooCommerce
* Customer: Guest, Existing customer (search + auto-fill) or New account
* Billing and shipping details with country/state dropdowns, GSTIN field, "same as billing"
* Editable price and quantity per line, or fully custom items
* Shipping charge, discount and GST % fields
* Order date, status, payment method, transaction ID, PO/reference number
* Customer note and internal note
* Optional customer emails (off by default)
* "All Manual Orders" list with total value
* HPOS (High-Performance Order Storage) compatible

## Installation

1. Go to Plugins > Add New > Upload Plugin and upload the zip.
2. Activate the plugin (WooCommerce must be active).
3. Open B2B Orders > Custom Products and review/edit your product list.
4. Open B2B Orders > Add New Order to create an order.

## Usage

1. Choose customer type (Guest / Existing / New account).
2. Fill billing details; tick "Same as billing" or enter shipping separately.
3. Add items: pick a custom product (price can be changed) or "Custom item...".
4. Add shipping, discount or GST % if needed.
5. Set status (default Processing) and click Create order.

You are redirected to the normal WooCommerce order edit screen.

## Notes

* Items are stored as order line items not linked to a WooCommerce product, so stock is not affected.
* GST is added as a flat fee line, not through WooCommerce tax rates.
* Integrations that only fire on "Processing" orders (e.g. Shadowfax) need the status set to Processing.
* Products are stored in the option "cbd_mo_products". Orders are tagged with meta "_cbd_manual_order" = yes.

## Frequently Asked Questions

### Will these orders show in WooCommerce reports?
Yes. They are normal WooCommerce orders and count towards revenue.

### Will customers get emails?
Only if you tick "Send order emails to customer".

### Are the custom products visible on the website?
No. They exist only inside this plugin.

## Changelog

### 1.0.0
* Initial release.

## Developer & Support

Asha Webtech IT Solution Private Limited
Developer: Chandrabhan Singh Dhakad
Website: https://ashawebtechitsolutions.com/
Email: info@chandrabhansingh.in
Phone: +91 7024315567 / +91 9755121478
Address: Office No. 69, Ratan Colony, New Jail Road, Huzur, Bhopal - 462038, India
