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
			<label><?php esc_html_e( 'Update all meta?', 'pmcp' ); ?>
				<input type="checkbox" name="pmcp_update" value="1"/></label>
		</p>
		<p class="description"><?php esc_html_e( 'Checking this box will update all your custom fields based on the value above when you save/update the post.', 'pmcp' ); ?></p>
		<?php

	}

}

/**
 * Initiate the main instance after plugins_loaded.
 */
function pmcp_start() {
	PMCP_Main::instance();
}

add_action( 'plugins_loaded', 'pmcp_start' );
