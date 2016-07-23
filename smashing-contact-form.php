<?php

/*
Plugin Name: Smashing Contact Form
Plugin URI: http://smashingmagazine.com
Description: Ajax powered contact form for WordPress
Version: 1.0
Author: Collins Agbonghama
Author URI: http://w3guy.com
*/


namespace SmashingMagazine;

class ContactForm {

	/** @var string URL of plugin folder */
	protected $plugin_dir_url;

	public function __construct() {
		$this->plugin_dir_url = plugin_dir_url( __FILE__ );
		add_shortcode( 'smashing-contact-form', [ $this, 'contact_form_template' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_ajax_nopriv_smashing_cf_submission', [ $this, 'ajax_handler' ] );
		add_action( 'wp_ajax_smashing_cf_submission', [ $this, 'ajax_handler' ] );
	}

	/**
	 * Enqueue plugin JS
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'smashing-cf', $this->plugin_dir_url . 'submit.js', [ 'jquery' ], false, true );
		wp_localize_script( 'smashing-cf', 'smashing_cf_ajax_form',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			)
		);
	}


	/**
	 * Ajax callback.
	 */
	public function ajax_handler() {
		$to = apply_filters( 'smashing_cf_reciever_email_address', get_option( 'admin_email' ) );

		$sender_name   = sanitize_text_field( $_REQUEST['smashing-cf-name'] );
		$sender_email  = sanitize_text_field( $_REQUEST['smashing-cf-email'] );
		$email_subject = sanitize_text_field( $_REQUEST['smashing-cf-subject'] );
		$email_message = sanitize_text_field( $_REQUEST['smashing-cf-message'] );

		if ( empty( $sender_name ) || empty( $sender_email ) || empty( $email_subject ) || empty( $email_message ) ) {
			wp_send_json_error( __( 'One or more required fields is empty. Try again' ) );
		}

		// set the from header to the name and email address of the contact form user.
		$headers = "From: $sender_name <$sender_email>" . "\r\n";
		$headers .= "Reply-to: $sender_name <$sender_email>" . "\r\n";

		// save uploaded file
		$file_path = $this->save_uploaded_file();

		if ( wp_mail( $to, $email_subject, $email_message, $headers, [ $file_path ] ) ) {
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}

		wp_die();
	}


	/**
	 * Save uploaded file to "wp-content/uploads/smashing-cf-files" folder.
	 *
	 * @return string|void
	 */
	public function save_uploaded_file() {

		$file = $_FILES['smashing-cf-file'];

		$upload_folder = WP_CONTENT_DIR . '/uploads/smashing-cf-files/';

		// does file upload folder exist? if NO, create it.
		if ( ! file_exists( $upload_folder ) ) {
			mkdir( $upload_folder, 0755 );
		}

		// ensure a safe filename
		$file_name = preg_replace( "/[^A-Z0-9._-]/i", "_", $file['name'] );

		// preserve file from temporary directory
		$response = move_uploaded_file( $file["tmp_name"], $upload_folder . $file_name );

		if ( $response ) {

			// set proper permissions on the new file
			chmod( $upload_folder . $file_name, 0644 );

			return $upload_folder . $file_name;
		}
	}

	/**
	 * Callback method to output contact form when shortcode is active.
	 */
	public function contact_form_template() {
		$loader_img = $this->plugin_dir_url . 'img/ajaxloader.gif';
		$sent_img   = $this->plugin_dir_url . 'img/sent.png';

		echo '<div class="smashing-cf-container">';
		echo '<form id="smashing-contact-form" method="post">';
		echo '<p>';
		echo 'Your Name (required) <br />';
		echo '<input type="text" name="smashing-cf-name" pattern="[a-zA-Z0-9 ]+" value="" size="40" required>';
		echo '</p>';
		echo '<p>';
		echo 'Your Email (required) <br />';
		echo '<input type="email" name="smashing-cf-email" size="40" required>';
		echo '</p>';
		echo '<p>';
		echo 'Subject (required) <br />';
		echo '<input type="text" name="smashing-cf-subject" pattern="[a-zA-Z ]+" size="40" required>';
		echo '</p>';
		echo '<p>';
		echo 'Your Message (required) <br />';
		echo '<textarea rows="10" cols="35" name="smashing-cf-message" required></textarea>';
		echo '</p>';
		echo '<p><input type="file" name="smashing-cf-file"/></p>';
		echo '<p><input type="submit" name="smashing-cf-submitted" value="Send"/>';
		echo '<img style="display: none" id="cf-loader" src="' . $loader_img . '">';
		echo '<img style="display: none" id="cf-sent" src="' . $sent_img . '"></p>';
		echo '</form>';
		echo '<div id="cf-notice" style="background-color:#ddd; padding:5px;color:#000;display:none;"></div>';
		echo '</div>';
	}


	/**
	 * Return a singleton class instance.
	 *
	 * @return ContactForm
	 */
	public static function get_instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}
}


/**
 * Initialize the plugin when plugins is fully loaded during WordPress bootstrap.
 */
add_action( 'plugins_loaded', function () {
	ContactForm::get_instance();
} );