<?php
/**
 * The script handler functions.
 *
 * This file is used to write all the helper function for the plugin.
 * This file doesn't use a class. These functions can be accessed
 * throught the site. So please make sure that you properly prefix
 * all the function names with 'edd_'
 *
 * @link		http://devrix.com
 * @since		1.0.0
 *
 * @package   	EDD
 * @subpackage	EDD\EDDResendReceipt\Includes
 * @author		DevriX
 * @copyright	Copyright (c) DevriX <mpeshev@devrix.com>
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Check if the user enabled resend receipt option.
 *
 * @since		1.0.0
 * @return      true if enabled, or false
 */
function edd_resend_receipt_admin_enabled() {

	$edd_options = get_option( 'edd_settings' );

	if ( ! empty( $edd_options['edd_resend_receipt_admin'] ) ) {
		return true;
	} else {
		return false;
	}

}

/**
 * Get downloads list.
 *
 * Use WordPress get_posts() function and get the resend disabled downloads.
 *
 * @uses		get_posts()
 * @since		1.0.0
 * @return		array	$downloads list
 */
function edd_get_resend_disabled_downloads() {

	$downloads = array();

	$args = array(
		'post_type' 	=> 'download',
		'post_status'	=> 'publish',
		'meta_query' 	=> array(
			array(
				'key' 	=> 'eddrr_enabled',
				'value'	=> 0 
			)
		)
	);
	
	$downloads = get_posts( $args );
	
	// Return the download posts array
	return $downloads;
}

/**
 * Receipt Resend Form.
 *
 * This function is called via shortcode [edd_resend_form].
 * HTML form will be generated here. And if post data is
 * available from the same form, it will call the edd_resend_receipt_on_post()
 * function to start processing with resend receipt.
 *
 * @var			array	atts  Shortcode attributes
 * @since		1.0.0
 * @return		void
 */
function edd_resend_receipt_form( $atts = false ) {
	
	// Initilize the attributes
	$class = '';
	
	// Take optional attributes from shortcode
	if ( ! empty( $atts ) ) {
		if ( array_key_exists( 'class', $atts ) ) {
			$class = $atts['class'];
		}
	}
	
	/*************************** Starts html form ************************************/ ?>
	
	<div class="edd-resend-div <?php echo $class; ?>">
		<?php
		ob_start();
		echo '<form action="'.$_SERVER['REQUEST_URI'].'" method="post" id="eddrr-form">';
		// WordPress nonce field for security purpose
		wp_nonce_field( 'edd_rr_action', 'edd_rr_nonce' );
		
		echo '<input name="edd_resend_ajax" id="edd_resend_ajax" type="hidden" value="'.home_url().'/wp-admin/admin-ajax.php"/>';
		echo '<select name="edd_resend_key" id="edd_resend_key" class="eddrr-form eddrr-form-key">';
		echo '<option value="purchase_key">Purchase Key</option>';
		echo '<option value="payment_id">Payment ID</option>';
		echo '<option value="license_key">License Key</option>';
		echo '</select>';
		// @TODO: This should be updated in the future version, I don't like the markup here
		// Form input area for email of the customer
		echo '<input type="text" name="edd_resend_value" id="edd_resend_value" class="eddrr-form eddrr-form-value"><br/>
				<input type="submit" class="edd-submit eddrr-form" name="eddrr_submit" id="edd_resend_button" value="Resend Receipt"><br/>
				</form>
				<div id="eddrr_response_div">';

		// Incase if form submitted normally without jQuery
		if ( isset( $_POST['edd_resend_value'] ) ) {
			echo edd_resend_receipt_on_post();
		}

		echo '</div>';

		return ob_get_clean();
}

/**
 * Process Resend Request.
 *
 * This function is called from the html form.
 * If post data us available we will start this function after
 * verifying WordPress nonce field.
 *
 * @var		$edd_resend_key -  Whether payment_id, purchase_key or license_key
 * @var		$edd_resend_value - Value for aove said key
 * @var		$response_array	- 	Response message array to be shown
 *
 * @since		1.0.0
 * @return		$output		The response content to be shown to user
 */	
function edd_resend_receipt_on_post(){
	
	$output = edd_resend_receipt_language( 'no_purchase_found', 'error' );
	
	if ( ! isset( $_POST['edd_rr_nonce'] ) || ! wp_verify_nonce( $_POST['edd_rr_nonce'], 'edd_rr_action' ) ) {
		
		_e( 'Cheatin&#8217; huh?', 'edd-resend-receipt' );
		exit;
	} else if ( isset( $_POST['edd_resend_value'] ) && isset( $_POST['edd_resend_key'] ) ) {

		if ( ! isset( $_COOKIE['edd_resend_last_query'] ) ) {
			$edd_resend_value = $_POST['edd_resend_value'];
			$edd_resend_key = $_POST['edd_resend_key'];

			switch ( $edd_resend_key ) {
				case 'purchase_key':
					$output = edd_resend_receipt_purchase_key( $edd_resend_value );
					break;

				case 'payment_id':
					$output = edd_resend_receipt_payment_id( $edd_resend_value );
					break;
					
				default:
					$output = edd_resend_receipt_language( 'no_purchase_found', 'error' );
					break;
			}

			setcookie( 'edd_resend_last_query', md5(0), time() + 15 );
		} else {
			$output = edd_resend_receipt_language( 'receipt_query_wait', 'error' );
		}
	} else {	
		$output = edd_resend_receipt_language( 'invalid_data', 'error' );
	}

	echo $output; 
	wp_die(); // Do not remove this
}

/**
 * Payment ID is given by the user.
 *
 * This function is called if the user selected payment id as
 * key and entered the value.
 *
 * @var			$payment_id -  The payement id entered by the user.
 * @var			$result	- 	Response message array to be shown
 *
 * @since		1.0.0
 * @return		$result		The response content to be shown to user
 */	
function edd_resend_receipt_payment_id( $payment_id ){

	$output = edd_resend_receipt_language( 'no_purchase_found', 'error' );
	$meta = get_post_meta( $payment_id, '_edd_payment_meta', true );

	if ( isset( $meta ) && is_array( $meta ) && ! empty( $meta['key'] ) ) {
		$output = edd_resend_receipt_again( $payment_id );
	}
	
	return $output;
}

/**
 * Purchase Key is given by the user.
 *
 * This function is called if the user selected purchase key as
 * key and entered the value.
 *
 * @var			$purchase_key -  The purchase key entered by the user.
 * @var			$result	- 	Response message array to be shown
 *
 * @since		1.0.0
 * @return		$result		The response content to be shown to user
 */	
function edd_resend_receipt_purchase_key( $purchase_key ){

	$output = edd_resend_receipt_language( 'no_purchase_found', 'error' );

	$payments_id = edd_get_purchase_id_by_key( $purchase_key );

	if ( $payments_id ) {
		$output = edd_resend_receipt_again( $payments_id );
	}

	return $output;
}

/**
 * Check if the current item is disabled/enabled to resend.
 *
 * This function is used to check if the items in the requested purchase
 * receipt is disabled to resend the recipt. Enable/Disable can be done
 * in the download edit screen.
 * If the purchase contains multiples files, entire purchase receipt will be resent
 * even if one or more items disabled resending receipt.
 *
 * @var			$payment_id - The payment id for the current purchase
 * @var			$disabled_count - Count of the items disabled in the current urchase
 * @var			$disabled_downloads - The list of disabled downloads
 * @var			$download_ids - Download IDs array of the disabled downloads
 * @var			$purchased_count - The count of the items in the current purchase
 *
 * @uses		EDD class
 * @since		1.0.0
 * @return		true if count of the $purchased_count is more than $disabled_count, else false.
 */
function edd_resend_receipt_download_enabled( $payment_id ) {

	$disabled_count = 0;

	// Get the disabled downloads list
	$disabled_downloads = edd_get_resend_disabled_downloads();
	$disabled = array();
	foreach ( $disabled_downloads as $e_download ) {
		$disabled[] = $e_download->ID; // Make an array of disabled downloads
	}
	
	$meta = get_post_meta( $payment_id, '_edd_payment_meta', true );
	$download_ids = array();
	foreach ( $meta['downloads'] as $current_download ) {
		$download_ids[] = $current_download['id']; // Make an array of current payment's download items ids
	}
	
	foreach ( $download_ids as $key ) {
		if ( in_array( $key, $disabled ) ) {
			$disabled_count++;
		}
	}
	
	$purchased_count = sizeof( $download_ids );
	$status = ( $purchased_count == 0 ) ? 'no_purchase' : 'purchased';

	return ( $disabled_count <= $purchased_count ) ? $status : false;
}

/**
 * Resending the Receipts.
 *
 * This is the final function to resend the receipts to user.
 * User will be able to get a new download link in the mail and the download
 * limit will be increased by one.
 *
 * @var			$payment_id - Payment ID of the purchase to be resent
 * @var			$admin_notice - Check if admin notification mail should be disabled ( Default - 1)
 *
 * @uses		EDD class
 * @since		1.0.0
 * @return		array	$result	 an array of a response message and class.
 */
function edd_resend_receipt_again( $payment_id ) {

	$output = edd_resend_receipt_language( 'receipt_disabled', 'error' );
	
	if ( edd_resend_receipt_download_enabled( $payment_id ) ) {
		
		if ( 'purchased' == edd_resend_receipt_download_enabled( $payment_id ) ) {
			$admin_notice = edd_resend_receipt_admin_enabled();
			$output = edd_resend_receipt_language( 'error_sending', 'error' );
			edd_email_purchase_receipt( $payment_id, $admin_notice );
			$output = edd_resend_receipt_language( 'success_receipt', 'success' );
		} else {
			$output = edd_resend_receipt_language( 'no_purchase_found', 'error' );
		}
	}
	
	return $output;
}



/**
 * Error and success messages.
 *
 * This function conatins all the language texts. You will get the error/success
 * messages accordingly.
 *
 * @var			$text - Unique name of the message
 * @var			$type - Type of the message. Error or Success
 *
 * @since		1.0.0
 * @return		string	$output	 Div with error/sucess messages.
 */
function edd_resend_receipt_language( $text, $type ) {

	$output = '';
	$class = ( $type == 'success' ) ? 'eddrr-success' : 'eddrr-error';
	
	switch ( $text ) {
		
		case 'no_purchase_found':
			
			$response = __( 'Sorry, but no purchase details found!', 'edd-resend-receipt' );
			break;

		case 'invalid_data':
			
			$response = __( 'Sorry, but you have entered invalid data!', 'edd-resend-receipt' );
			break;

		case 'no_purchase_license':
			
			$response = __( 'No valid purchase found for this license key', 'edd-resend-receipt' );
			break;

		case 'receipt_disabled':
			
			$response = __( 'Sorry, but manual receipt resend is disabled for that product. Kindly contact the admin!', 'edd-resend-receipt' );
			break;

		case 'error_sending':
			
			$response = __( 'Error while sending receipt. Please Try again', 'edd-resend-receipt' );
			break;

		case 'success_receipt':
			
			$response = __( 'Success. Your purchase receipt has been re-sent to you!', 'edd-resend-receipt' );
			break;

		case 'receipt_query_wait':
			$response = __( 'Sorry, but you have to wait 15 seconds before send another query!', 'edd-resend-receipt' );
			break;
		
		default:
			$response = __( 'Oops! Un-expected error!', 'edd-resend-receipt' );
			break;
	}

	// Result div
	$output = '<div class="'.$class.'"><p>'.$response.'</p></div>';
	
	return $output;
}
