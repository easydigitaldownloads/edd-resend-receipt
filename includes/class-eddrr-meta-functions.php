<?php
if ( ! defined( 'WPINC' ) ) {
  die( 'Nice try. But this ain\'t gonna work. Seriously dude.!' );
}
/**
 * Custom meta box class for EDD Resend Receipt
 * 
 * This class will be used only in admin area
 * @link		http://devrix.com
 * @since		1.0.0
 *
 * @package   	EDD
 * @subpackage	EDD\EDDResendReceipt\Includes
 * @author		DevriX
 * @copyright	Copyright (c) DevriX <mpeshev@devrix.com>
 */

class EDDRR_Meta_Functions {
	
	/**
	 * Define the meta functionality of the plugin.
	 *
	 * Performs all the actions required for the meta functionality of
	 * the plugin.
	 *
	 * @since		1.0.0
	 * @author		Joel James
	 */
	public function __construct() {
	
		add_action( 'add_meta_boxes', array( $this, 'edd_resend_receipt_meta' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_settings' ) );
	}

	/**
	 * Create meta box for resend receipt.
	 *
	 * This function is used to create the meta box for download custom post
	 * to enable/disable the resend receipt option for each downloads.
	 *
	 * @uses meta_box_output()
	 *
	 * @since		1.0.0
	 * @return		$result		The response content to be shown to user
	 */
	public function edd_resend_receipt_meta() {	
		add_meta_box( 
			'eddda_meta_box', 
			__( 'Resend Receipt Settingss', 'edd-resend-receipt' ), 
			array( $this, 'edd_resend_receipt_meta_output' ), 
			'download', 
			'side' 
		);
	}
	
	/**
	 * Resend Recipt meta box content.
	 *
	 * This function is used to set the content for resend receipt meta
	 * box content. 
	 *
	 * @uses get_post_meta()	To get resend receipt meta value.
	 *
	 * @since		1.0.0
	 * @return		void
	 */
	public function edd_resend_receipt_meta_output( $post ) {
		$eddda_meta = get_post_meta( $post->ID, 'eddrr_enabled', true );
		?>
		<label for="base-add-on">
			<select id="eddrr_enabled" class="" name="eddrr_enabled">
				<option value="1" <?php selected( $eddda_meta, 1 ); ?>>Enable</option>
				<option value="0" <?php selected( $eddda_meta, 0 ); ?>>Disable</option>
			</select>
			<?php _e( 'Enable receipt resend', 'edd-resend-receipt' ); ?>
		</label>
		<?php
	}

	/**
	 * Save meta box settings.
	 *
	 * This function is used to set the content for resend receipt meta
	 * box content. 
	 *
	 * @uses delete_post_meta()		To delete the meta value.
	 * @uses update_post_meta()		To update the meta value.
	 * @used_by meta_box_output()
	 *
	 * @since		1.0.0
	 * @return		void
	 */
	public function save_settings( $post_id ) {

	    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
	        return;
	    }
		
		if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) )
				return;
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) )
				return;
		}
		
		if ( isset( $_POST['eddrr_enabled'] ) ) {
			$eddrr_enabled = $_POST['eddrr_enabled'];
			update_post_meta( $post_id, 'eddrr_enabled', $eddrr_enabled );
		} else {
			delete_post_meta( $post_id, 'eddrr_enabled' );
		}
	}
}
