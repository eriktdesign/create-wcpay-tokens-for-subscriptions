# Create _WooCommerce Payments_ tokens for _WooCommerce Subscriptions_

This script takes an input CSV file containing subscription IDs, user IDs, and Stripe token data, and creates tokens for WooCommerce Payments. The tokens are attached to the subscriptions and the user profiles, and will allow for automatic renewals to process.

This script was created to facilitate a change from Authorize.NET into WooCommerce Payments, but should also work for any migration into WooCommerce Payments.

## Preparation

The first step to the migration process is to initiate a transfer of payment data from your current payment processor into WooCommerce Payments / Stripe. The WooCommerce Payments platform is essentially a frontend to Stripe, so they will handle the importing of the customer and payment data. When the import is complete, you will be provided with a CSV file containing Stripe containing the following columns:

* `old_id` The customer ID from the system you are migrating from.
* `source_old_id` The payment method ID from the system you are migrating from. 
* `created_customer` The Stripe customer ID for the newly created customer. Starts with `cus_`.
* `source_new_id` The Stripe source ID for the newly created payment method. Starts with `pm_`.
* `card_fingerprint` The fingerprint for the card in Stripe's system.
* `card_last4` Last four digits of the card number. 
* `card_exp_month` Card expiration month. 
* `card_exp_year` Card expiration year. 
* `card_brand` Card brand, eg "Visa", "Mastercard", "Amex", etc.

You will need to export your current subscriptions from your site to obtain the IDs of the subscriptions, IDs of associated users, and the ID of the old payment method. The payment method ID may be stored in the meta of the subscription itself, or possibly in the usermeta of the associated user. 

Using these two sources of information, you will need to prepare a CSV file with the following columns:

* `subscription_id` The ID of the subscription to update
* `user_id` The ID of the user associated with the subscription
* `stripe_customer_id` The Stripe ID for the user associated with the subscription. Starts with `cus_`.
* `token_string` The Stripe token string for the payment method associated with the subscription. Starts with `pm_`.
* `last4` Last four digits of the card number.
* `year` Card expiration year. This should be a four-digit year.
* `month` Card expiration month. This should be a 1 or 2 digit numeric representation of the month. The value will be padded to include a leading zero when the script runs.
* `type` Matches up to the `card_brand` provided by stripe, eg "Visa", "Mastercard", "Amex", etc. 

## Run the script

Upload the script and dependencies to a folder on your server inside the WordPress folder hierarchy. In the same folder, upload your prepared CSV with the filename `subs.csv`. To run the process, use WP CLI to evaluate the file:

```bash
$ wp eval-file create-tokens-for-subs.php | tee -a token-import.log
```

This will execute the script and log all output to the file `token-import.log` for your records. 

## Outline of script actions

When running this script, the following actions are taken:

1. The file `subs.csv` is read, using the included `class-csv-reader.php` script to read a row at a time in a memory-efficient manner. Each row is processed in a loop.
1. The values for each column are extracted for use by the script. 
1. A new `WC_Payment_Token_CC` object is created. The object is populated with the gateway ID, expiration, card type, last 4, token string, and user ID. If the created token is invalid, an error is displayed and the script will continue to process the next record. Otherwise, the token is saved.
1. Next, the associated subscription is retrieved. The `_payment_tokens`, `_payment_method_id`, and `_stripe_customer_id` meta fields are populated with values, and the subscription is saved. 
1. The user meta with the key `wp__wcpay_customer_id_live` is updated with the Stripe customer ID. 
1. A success message is displayed with the token string, user ID, and subscription ID.

## Verification

After the process has run, you can verify success by opening a subscription and checking that the **Payment Method** displays as "WooCommerce Payments". The subscription should automatically renew on the scheduled date.

You can trigger an automatic renewal to process by visiting _WooCommerce > Status > Scheduled Actions_ and searching for the subscription ID. Look for an action called `woocommerce_scheduled_subscription_payment`, with a matching `subscription_id` in the _Arguments_ column. Check the _Scheduled Date_ column to find the next renewal. Hover over the hook title and click _Run_ to trigger the renewal. Go back to the subscription and verify that a renewal order was created, and that the payment processed successfully.
