<?php
/**
 * The script handler functions.
 *
 * This file is used to register all the required styles and javascript files
 * for the plugin - EDD Resend Receipt
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
if( !defined( 'ABSPATH' ) ) exit;
    /**
     * Load front end scripts.
     *
     * This function is used to register and load custom scripts and styles files for
     * edd receipt resend plugin.
     *
     * @uses wp_enqueue_style()     To register style css file
     * @uses wp_enqueue_script()    To register javascript file
     *
     * @since       1.0.0
     * @return      void
     */
    function edd_resend_receipt_scripts( $hook ) {
        // Use minified libraries if SCRIPT_DEBUG is turned off
    	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

        wp_enqueue_style( 'edd_resend_receipt_css', EDD_RESEND_RECEIPT_URL . '/assets/css/styles' . $suffix . '.css' );
        wp_enqueue_script('ajax_submission', EDD_RESEND_RECEIPT_URL . '/assets/js/ajax_req' . $suffix . '.js', array("jquery"));
    }
    add_action( 'wp_enqueue_scripts', 'edd_resend_receipt_scripts' );
