<?php
/**
 * Given an input file named subs.csv, for each row, this script will:
 * 1. Create a new WC_Payment_Token_CC
 * 2. Set the data of the token to match columns in the file
 * 3. Save the token to the database
 * 4. Update the relevant subscription with the token
 * 5. Update the subscription with payment method and customer id meta
 */

// Include the CSV Reader class
include "csv-reader/class-csv-reader.php";

// Load the CSV for reading
$csv = new CSV_Reader( 'subs.csv' );

// Iterate the rows of the CSV
foreach ( $csv->rows() as $row ) {
	// Extract the row values into variables
	// Columns: subscription_id, user_id, stripe_customer_id, token_string, last4, year, month, type 
	extract( $row );

	// Create the token
	$token = new WC_Payment_Token_CC();

	// Set up the token data
	$token->set_gateway_id( 'woocommerce_payments' );
	$token->set_expiry_month( str_pad( $month, 2, "0", STR_PAD_LEFT ) ); // Make sure this has leading zeroes
	$token->set_expiry_year( $year );
	$token->set_card_type( strtolower( $type ) ); // In the database these seem to always be lowercase
	$token->set_last4( $last4 );
	
	$token->set_token( $token_string );
	$token->set_user_id( $user_id );

	if ( ! $token->validate() ) {
		echo "Error: Invalid token $token_string for user $user_id and subscription $subscription_id\n";
		continue;
	}

	// Save the token
	$token->save();

	// Get the subscription profile
	$subscription = wc_get_order( $subscription_id );
	
	// Add the token meta to the subscription profile
	$subscription->update_meta_data( '_payment_tokens', array( $token->get_id() ) );

	// Add the other meta to the subscription profile (unsure if this is needed, but let's be thorough)
	$subscription->update_meta_data( '_payment_method_id', $token_string );
	$subscription->update_meta_data( '_stripe_customer_id', $stripe_customer_id );

	// Save the subscription profile
	$subscription->save();

	update_user_meta( $user_id, 'wp__wcpay_customer_id_live', $stripe_customer_id );

	echo "Success: Added token $token_string to user $user_id and subscription $subscription_id\n";
}
