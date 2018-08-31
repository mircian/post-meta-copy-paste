<?php
/**
 * Plugin Name: Post Meta Copy Paste
 * Description: Allows you to easily copy-paste all custom fields from one post to another.
 * Author:      Mircea Sandu
 * Author URI:  https://mircian.com
 * Version:     1.0.0
 * Text Domain: pmcp
 * Domain Path: languages
 *
 * Post Meta Copy Paste is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Post Meta Copy Paste is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Post Meta Copy Paste. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    PostMetaCP
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PMCP_Main
 */
class PMCP_Main {

	/**
	 * The class instance.
	 *
	 * @var PMCP_Main
	 */
	private static $instance;

	/**
	 * The capability of the users allowed to view this meta-box.
	 *
	 * @var string
	 */
	public $capability;

	/**
	 * PMCP_Main constructor.
	 */
	private function __construct() {
		$this->hooks();
	}

	/**
	 * Hooks needed for displaying the meta box.
	 */
	public function hooks() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );

		// Run this as early as possible to allow other plugins to modify the meta as needed.
		add_action( 'save_post', array( $this, 'maybe_update_post_meta' ), 0 );

		add_action( 'admin_notices', array( $this, 'maybe_suggest_another_update' ) );

		add_filter( 'removable_query_args', array( $this, 'make_query_arg_removable' ) );
	}

	/**
	 * The instance initiator.
	 *
	 * @return PMCP_Main
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new PMCP_Main();
		}

		return self::$instance;
	}

	/**
	 * Add the meta-box where the users will be able to get current fields and also paste for update.
	 */
	public function add_meta_box() {

		$this->capability = apply_filters( 'pmcp_capability', 'manage_options' );
		if ( ! current_user_can( $this->capability ) || ! apply_filters( 'pmcp_post_types', '__return_true', get_post_type() ) ) {
			return;
		}

		add_meta_box( 'pmcp-meta-box', esc_html__( 'Post Meta Copy Paste', 'pmcp' ), array(
			$this,
			'meta_box_content',
		), get_post_type() );
	}

	/**
	 * Output the form used for viewing and editing the meta.
	 */
	public function meta_box_content() {

		$post_meta = get_post_meta( get_the_ID() );

		?>
		<p class="label">
			<label for="pmcp_bulk_meta"><?php esc_html_e( 'Bulk meta values', 'pmcp' ); ?></label>
		</p>
		<p>
			<textarea class="widefat" name="pmcp_bulk_meta" id="pmcp_bulk_meta" rows="10"><?php echo wp_json_encode( $post_meta ); ?></textarea>
		</p>
		<p class="label">
			<label><input type="checkbox" name="pmcp_update" value="1"/><?php esc_html_e( 'Update all meta?', 'pmcp' ); ?>
			</label>
		</p>
		<p class="description"><?php esc_html_e( 'Checking this box will update all your custom fields based on the value above when you save/update the post.', 'pmcp' ); ?></p>
		<?php
		wp_nonce_field( 'pmcp_bulk_edit', 'pmcp_nonce' );

	}

	/**
	 * If the checkbox is checked, attempt to update all the post meta with the values in the textarea.
	 *
	 * @param int $post_ID Post ID.
	 */
	public function maybe_update_post_meta( $post_ID ) {

		// Don't do anything if the checkbox is not checked.
		if ( ! isset( $_POST['pmcp_update'] ) ) {
			return;
		}
		// Check if the nonce is valid.
		if ( isset( $_POST['pmcp_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pmcp_nonce'] ) ), 'pmcp_bulk_edit' ) ) {

			$bulk_meta = isset( $_POST['pmcp_bulk_meta'] ) ? json_decode( sanitize_textarea_field( wp_unslash( $_POST['pmcp_bulk_meta'] ) ), true ) : array();

			if ( ! empty( $bulk_meta ) ) {
				foreach ( $bulk_meta as $meta_key => $meta_value ) {
					if ( $this->is_excluded_meta( $meta_key ) ) {
						continue;
					}
					if ( is_array( $meta_value ) ) {
						// Delete existing post meta with this key to prevent duplicate values.
						delete_post_meta( $post_ID, $meta_key );
						foreach ( $meta_value as $value ) {
							add_post_meta( $post_ID, $meta_key, $value );
						}
						continue;
					}
					if ( is_serialized( $meta_value ) ) {
						$meta_value = maybe_unserialize( $meta_value );
						update_post_meta( $post_ID, $meta_key, $meta_value );
						continue;
					}
					update_post_meta( $post_ID, $meta_key, $meta_value );
				}
			}

			/*
			 * Prevent plugins from making changes to the updated values in this run & add notice to suggest another save.
			 * This shouldn't be a problem considering that values are copied from another post and the date should be
			 * already processed. The suggestion to update again is to make sure plugins run other possible connections
			 * based on meta.
			 */
			remove_all_actions( 'save_post' );

			add_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
		}

	}

	public function add_notice_query_var( $location ) {
		remove_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );

		return add_query_arg( array( 'pmcp_message' => '1' ), $location );
	}

	/**
	 * Some meta should be excluded, also allow plugins to exclude their meta keys.
	 *
	 * @param string $meta_key The meta key to check.
	 *
	 * @return bool
	 */
	public function is_excluded_meta( $meta_key ) {

		// This filter allows other plugins to add their meta to the list of meta which won't be updated by this plugin.
		return in_array( $meta_key, apply_filters( 'pmcp_excluded_meta', array(
			'_edit_last',
			'_edit_lock',
		) ), true );

	}

	/**
	 * Show a notice suggesting to update the post again.
	 */
	public function maybe_suggest_another_update() {
		if ( ! isset( $_GET['pmcp_message'] ) ) {
			return;
		}
		?>
		<div class="notice notice-info is-dismissible">
			<p><?php esc_html_e( 'All meta fields were updated, please update the post/page again ( without checking "Update all meta?" ) to allow plugins to run their actions!', 'pmcp' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Add our query arg to the list of removable query args to prevent confusion on edit screens.
	 *
	 * @param array $removable The current query args.
	 *
	 * @return array
	 */
	public function make_query_arg_removable( $removable ) {

		$removable[] = 'pmcp_message';

		return $removable;
	}

}

/**
 * Initiate the main instance after plugins_loaded.
 */
function pmcp_start() {
	PMCP_Main::instance();
}

add_action( 'plugins_loaded', 'pmcp_start' );
