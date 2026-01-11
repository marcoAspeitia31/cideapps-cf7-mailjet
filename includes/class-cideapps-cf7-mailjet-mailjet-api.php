<?php

/**
 * Mailjet API integration class
 *
 * @link       https://cideapps.com
 * @since      1.0.0
 *
 * @package    Cideapps_Cf7_Mailjet
 * @subpackage Cideapps_Cf7_Mailjet/includes
 */

/**
 * Mailjet API integration class.
 *
 * Handles all interactions with Mailjet API including:
 * - Adding/updating contacts in lists
 * - Sending transactional emails via Send API v3.1
 *
 * @package    Cideapps_Cf7_Mailjet
 * @subpackage Cideapps_Cf7_Mailjet/includes
 * @author     CIDEAPPS DIGITAL <contacto@cideapps.com>
 */
class Cideapps_Cf7_Mailjet_API {

	/**
	 * Mailjet API base URL
	 *
	 * @since    1.0.0
	 * @var      string    $api_base_url    Mailjet API base URL
	 */
	private $api_base_url = 'https://api.mailjet.com/v3.1/';

	/**
	 * Mailjet Contacts API base URL
	 *
	 * @since    1.0.0
	 * @var      string    $contacts_api_base_url    Mailjet Contacts API base URL
	 */
	private $contacts_api_base_url = 'https://api.mailjet.com/v3/REST/';

	/**
	 * Public API key
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $public_key    Mailjet public API key
	 */
	private $public_key;

	/**
	 * Private API key
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $private_key    Mailjet private API key
	 */
	private $private_key;

	/**
	 * Initialize the class and set API credentials.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->public_key  = get_option( 'cideapps_cf7_mailjet_public_key', '' );
		$this->private_key = get_option( 'cideapps_cf7_mailjet_private_key', '' );
	}

	/**
	 * Add or update contact in Mailjet list
	 *
	 * @since    1.0.0
	 * @param    string    $email         Contact email
	 * @param    array     $properties    Contact properties (name, phone, service, etc.)
	 * @param    int       $list_id       Mailjet list ID
	 * @param    string    $on_existing   Action when contact exists: 'update_properties' or 'skip'
	 * @return   array|WP_Error    Response array or WP_Error on failure
	 */
	public function add_contact_to_list( $email, $properties = array(), $list_id = 0, $on_existing = 'update_properties' ) {
		if ( empty( $this->public_key ) || empty( $this->private_key ) ) {
			return new WP_Error( 'no_credentials', 'Mailjet API credentials are not configured.' );
		}

		if ( empty( $email ) || ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', 'Invalid email address.' );
		}

		if ( empty( $list_id ) ) {
			return new WP_Error( 'no_list_id', 'Mailjet list ID is required.' );
		}

		// First, check if contact exists
		$contact_exists = $this->contact_exists( $email );

		if ( is_wp_error( $contact_exists ) ) {
			return $contact_exists;
		}

		// If contact exists and we should skip, return early
		if ( $contact_exists && $on_existing === 'skip' ) {
			// Still need to add to list if not already in it
			return $this->add_contact_to_list_only( $email, $list_id );
		}

		// Get contact ID (if exists)
		$contact_id = $this->get_contact_id( $email );

		if ( is_wp_error( $contact_id ) ) {
			return $contact_id;
		}

		// Prepare contact data
		$contact_data = array(
			'Email' => sanitize_email( $email ),
		);

		// Create or update contact
		if ( $contact_id ) {
			// Contact exists - update it if we have properties
			if ( ! empty( $properties ) && is_array( $properties ) ) {
				// Update contact properties using PUT
				$contact_data['Properties'] = $properties;
				$endpoint                   = $this->contacts_api_base_url . 'contact/' . $contact_id;
				$response                   = $this->make_request( $endpoint, 'PUT', $contact_data );
				if ( is_wp_error( $response ) ) {
					return $response;
				}
			}
		} else {
			// Create new contact
			if ( ! empty( $properties ) && is_array( $properties ) ) {
				$contact_data['Properties'] = $properties;
			}
			$endpoint = $this->contacts_api_base_url . 'contact';
			$response = $this->make_request( $endpoint, 'POST', $contact_data );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			// Get contact ID from response
			if ( isset( $response['Data'][0]['ID'] ) ) {
				$contact_id = (int) $response['Data'][0]['ID'];
			}
		}

		if ( ! $contact_id ) {
			return new WP_Error( 'no_contact_id', 'Could not get contact ID from Mailjet.' );
		}

		// Add contact to list
		$list_result = $this->add_contact_to_list_only( $email, $list_id, $contact_id );

		return $list_result;
	}

	/**
	 * Check if contact exists in Mailjet
	 *
	 * @since    1.0.0
	 * @param    string    $email    Contact email
	 * @return   bool|WP_Error    True if exists, false if not, WP_Error on failure
	 */
	private function contact_exists( $email ) {
		$contact_id = $this->get_contact_id( $email );
		if ( is_wp_error( $contact_id ) ) {
			return $contact_id;
		}
		return ! empty( $contact_id );
	}

	/**
	 * Get contact ID by email
	 *
	 * @since    1.0.0
	 * @param    string    $email    Contact email
	 * @return   int|WP_Error    Contact ID or WP_Error on failure
	 */
	private function get_contact_id( $email ) {
		$endpoint = $this->contacts_api_base_url . 'contact/' . urlencode( $email );
		$response = $this->make_request( $endpoint, 'GET' );

		if ( is_wp_error( $response ) ) {
			// 404 means contact doesn't exist, which is fine
			$error_code = $response->get_error_code();
			if ( $error_code === 'mailjet_api_error' ) {
				$error_data = $response->get_error_data();
				if ( isset( $error_data['status'] ) && $error_data['status'] === 404 ) {
					return false;
				}
			}
			return false; // Return false instead of error for non-existence
		}

		// Mailjet API returns contact data directly when getting by email
		if ( isset( $response['Data'][0]['ID'] ) ) {
			return (int) $response['Data'][0]['ID'];
		}

		// Sometimes it returns the ID directly
		if ( isset( $response['ID'] ) ) {
			return (int) $response['ID'];
		}

		return false;
	}

	/**
	 * Add contact to list only (without creating/updating contact)
	 *
	 * @since    1.0.0
	 * @param    string    $email       Contact email
	 * @param    int       $list_id     Mailjet list ID
	 * @param    int|null  $contact_id  Contact ID (optional, will be fetched if not provided)
	 * @return   array|WP_Error    Response array or WP_Error on failure
	 */
	private function add_contact_to_list_only( $email, $list_id, $contact_id = null ) {
		if ( ! $contact_id ) {
			$contact_id = $this->get_contact_id( $email );
			if ( is_wp_error( $contact_id ) || ! $contact_id ) {
				return new WP_Error( 'no_contact_id', 'Contact does not exist in Mailjet.' );
			}
		}

		$endpoint = $this->contacts_api_base_url . 'listrecipient';
		$data     = array(
			'IsUnsubscribed' => false,
			'ContactID'      => $contact_id,
			'ListID'         => (int) $list_id,
		);

		$response = $this->make_request( $endpoint, 'POST', $data );

		// If contact is already in list (409 Conflict), that's fine
		if ( is_wp_error( $response ) ) {
			$error_data = $response->get_error_data();
			if ( isset( $error_data['status'] ) && $error_data['status'] === 409 ) {
				return array( 'success' => true, 'message' => 'Contact already in list' );
			}
			// Check error message for "already" text
			if ( strpos( $response->get_error_message(), 'already' ) !== false ) {
				return array( 'success' => true, 'message' => 'Contact already in list' );
			}
		}

		return $response;
	}

	/**
	 * Send transactional email via Mailjet Send API v3.1
	 *
	 * @since    1.0.0
	 * @param    string    $to_email      Recipient email
	 * @param    int       $template_id   Mailjet template ID
	 * @param    array     $variables     Template variables (name, email, phone, service)
	 * @param    string    $from_email    From email address
	 * @param    string    $from_name     From name
	 * @return   array|WP_Error    Response array or WP_Error on failure
	 */
	public function send_email( $to_email, $template_id, $variables = array(), $from_email = '', $from_name = '' ) {
		if ( empty( $this->public_key ) || empty( $this->private_key ) ) {
			return new WP_Error( 'no_credentials', 'Mailjet API credentials are not configured.' );
		}

		if ( empty( $to_email ) || ! is_email( $to_email ) ) {
			return new WP_Error( 'invalid_email', 'Invalid recipient email address.' );
		}

		if ( empty( $template_id ) ) {
			return new WP_Error( 'no_template_id', 'Mailjet template ID is required.' );
		}

		// Get from email/name from options if not provided
		if ( empty( $from_email ) ) {
			$from_email = get_option( 'cideapps_cf7_mailjet_from_email', '' );
		}
		if ( empty( $from_name ) ) {
			$from_name = get_option( 'cideapps_cf7_mailjet_from_name', '' );
		}

		if ( empty( $from_email ) || ! is_email( $from_email ) ) {
			return new WP_Error( 'invalid_from_email', 'Invalid from email address.' );
		}

		// Prepare email data
		$email_data = array(
			'Messages' => array(
				array(
					'From'     => array(
						'Email' => sanitize_email( $from_email ),
						'Name'  => sanitize_text_field( $from_name ),
					),
					'To'       => array(
						array(
							'Email' => sanitize_email( $to_email ),
						),
					),
					'ReplyTo'  => array(
						'Email' => sanitize_email( $from_email ),
					),
					'TemplateID'       => (int) $template_id,
					'TemplateLanguage' => true,
					'Variables'        => array_map( 'sanitize_text_field', $variables ),
				),
			),
		);

		$endpoint = $this->api_base_url . 'send';
		$response = $this->make_request( $endpoint, 'POST', $email_data );

		return $response;
	}

	/**
	 * Make HTTP request to Mailjet API
	 *
	 * @since    1.0.0
	 * @param    string    $endpoint    API endpoint
	 * @param    string    $method      HTTP method (GET, POST, PUT, DELETE)
	 * @param    array     $data        Request body data (for POST/PUT)
	 * @return   array|WP_Error    Response array or WP_Error on failure
	 */
	private function make_request( $endpoint, $method = 'GET', $data = array() ) {
		$args = array(
			'method'  => $method,
			'timeout' => 30,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $this->public_key . ':' . $this->private_key ),
				'Content-Type'  => 'application/json',
			),
		);

		if ( ! empty( $data ) && in_array( $method, array( 'POST', 'PUT' ), true ) ) {
			$args['body'] = wp_json_encode( $data );
		}

		$response = wp_remote_request( $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$body_array  = json_decode( $body, true );

		if ( $status_code >= 200 && $status_code < 300 ) {
			return $body_array;
		}

		$error_message = 'Mailjet API error';
		if ( isset( $body_array['ErrorMessage'] ) ) {
			$error_message = $body_array['ErrorMessage'];
		} elseif ( isset( $body_array['ErrorInfo'] ) ) {
			$error_message = $body_array['ErrorInfo'];
		} elseif ( ! empty( $body ) ) {
			$error_message = $body;
		}

		return new WP_Error( 'mailjet_api_error', $error_message, array( 'status' => $status_code, 'response' => $body_array ) );
	}
}

