<?php
/**
 * Plugin Name:     Easy Digital Downloads - Resend Receipt
 * Plugin URI:      https://easydigitaldownloads.com/downloads/resend-receipt/
 * Description:     Resend Receipt allows your customers to resend their purchase receipts directly from your website without logging into their account.
 * Version:         1.0.2
 * Author:          Sandhills Development, LLC
 * Author URI:      https://sandhillsdev.com/
 * Text Domain:     edd-resend-receipt
 * EDD Version:     2.2.4
 *
 * @package         EDD\EDDResendReceipt
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'EDD_Resend_Receipt' ) ) {

    /**
     * Main EDD_RESEND_RECEIPT class
     *
     * @since       1.0.0
     */
    class EDD_Resend_Receipt {

        /**
         * @var         EDD_Resend_Receipt $instance The one true EDD_Resend_Receipt
         * @since       1.0.0
         */
        private static $instance;

        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      object self::$instance The one true EDD_Resend_Receipt
         */
        public static function instance() {
            if ( ! self::$instance ) {
                self::$instance = new EDD_Resend_Receipt();
                self::$instance->setup_constants();
                self::$instance->includes();
                self::$instance->load_textdomain();
                self::$instance->hooks();
            }

            return self::$instance;
        }

        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function setup_constants() {
            // Plugin version
            define( 'EDD_RESEND_RECEIPT_VER', '1.0.2' );

            // Plugin path
            define( 'EDD_RESEND_RECEIPT_DIR', plugin_dir_path( __FILE__ ) );

            // Plugin URL
            define( 'EDD_RESEND_RECEIPT_URL', plugin_dir_url( __FILE__ ) );
        }

        /**
         * Include necessary files
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function includes() {
            // Include required files
            require_once EDD_RESEND_RECEIPT_DIR . 'includes/scripts.php';
			require_once EDD_RESEND_RECEIPT_DIR . 'includes/functions.php';

            if ( is_admin() ) {
                require_once EDD_RESEND_RECEIPT_DIR . 'includes/class-eddrr-meta-functions.php';
            }
        }

        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function hooks() {
            // Register settings
            add_filter( 'edd_settings_extensions', array( $this, 'settings' ), 1 );

            add_shortcode( 'edd_resend_form', 'edd_resend_receipt_form' );

            add_action( 'wp_ajax_nopriv_edd_resend_receipt_on_post', 'edd_resend_receipt_on_post' );
            add_action( 'wp_ajax_edd_resend_receipt_on_post', 'edd_resend_receipt_on_post' );

            // Handle EDD licensing
            if ( class_exists( 'EDD_License' ) ) {
				$license = new EDD_License( __FILE__, 'Resend Receipt', EDD_RESEND_RECEIPT_VER, 'Sandhills Development, LLC', null, null, 930482 );
            }

            if ( class_exists( 'EDDRR_Meta_Functions' ) ) {
                $license = new EDDRR_Meta_Functions();
            }
        }

        /**
         * Internationalization
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function load_textdomain() {
            // Set filter for language directory
            $lang_dir = EDD_RESEND_RECEIPT_DIR . '/languages/';
            $lang_dir = apply_filters( 'edd_resend_receipt_languages_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), 'edd-resend-receipt' );
            $mofile = sprintf( '%1$s-%2$s.mo', 'edd-resend-receipt', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/edd-resend-receipt/' . $mofile;

            if ( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/edd-resend-receipt/ folder
                load_textdomain( 'edd-resend-receipt', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/edd-resend-receipt/languages/ folder
                load_textdomain( 'edd-resend-receipt', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'edd-resend-receipt', false, $lang_dir );
            }
        }

        /**
         * Add settings
         *
         * @access      public
         * @since       1.0.0
         * @param       array $settings The existing EDD settings array
         * @return      array The modified EDD settings array
         */
        public function settings( $settings ) {
            $new_settings = array(
                array(
                    'id'    => 'edd_resend_receipt_header',
                    'name'  => '<strong>' . __( 'Resend Receipt Settings', 'edd-resend-receipt' ) . '</strong>',
                    'desc'  => __( 'Configure Resend Receipt Settings', 'edd-resend-receipt' ),
                    'type'  => 'header',
                ),

                // Admin notification settings
                array(
                    'id'    => 'edd_resend_receipt_admin',
                    'name'  => __( 'Enable Admin Notification', 'edd-resend-receipt' ),
                    'desc'  => __( 'Check this box if you want admin to receive an email notification whenever someone resend their receipts.', 'edd-resend-receipt' ),
                    'type'  => 'checkbox',
                )
            );

            return array_merge( $settings, $new_settings );
        }
    }
} // End if class_exists check

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'edd_rr_plugin_action_links' );

function edd_rr_plugin_action_links( $links ) {
   $links[] = '<a href="'. esc_url( get_admin_url( null, 'edit.php?post_type=download&page=edd-settings&tab=extensions' ) ) .'">' . __( 'Settings', 'edd-resend-receipt' ) . '</a>';

   return $links;
}

/**
 * The main function responsible for returning the one true EDD_RESEND_RECEIPT
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \EDD_RESEND_RECEIPT The one true EDD_RESEND_RECEIPT
 */
function edd_resend_receipt_load() {
    if ( ! class_exists( 'Easy_Digital_Downloads' ) ) {
        if ( ! class_exists( 'EDD_Extension_Activation' ) ) {
            require_once 'includes/class-extension-activation.php';
        }

        $activation = new EDD_Extension_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
        $activation = $activation->run();

        return EDD_RESEND_RECEIPT::instance();
    } else {
        return EDD_RESEND_RECEIPT::instance();
    }
}
add_action( 'plugins_loaded', 'edd_resend_receipt_load' );

/**
 * The activation hook is called outside of the singleton because WordPress doesn't
 * register the call from within the class, since we are preferring the plugins_loaded
 * hook for compatibility, we also can't reference a function inside the plugin class
 * for the activation function. If you need an activation function, put it here.
 *
 * @since       1.0.0
 * @return      void
 */
function edd_resend_receipt_activation() {
    /* Activation functions here */
}
register_activation_hook( __FILE__, 'edd_resend_receipt_activation' );
