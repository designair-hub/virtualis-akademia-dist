<?php

namespace VirtualisAkademia\Frontend;

use VirtualisAkademia\Hookable;

/**
 * Backs the header's contact popup (Contact.js) -- a logged-in member sends
 * a message that's relayed by e-mail to the project's own inbox rather than
 * stored anywhere on the site (no admin UI to read it back exists, and none
 * is needed for a simple contact form). Requires the same consent checkbox
 * the registration form does; the request is rejected server-side if it's
 * not ticked, since a client could otherwise skip that validation entirely.
 */
class ContactRestController implements Hookable {

	private const RECIPIENT = 'rabi.sandor@innopod.hu';

	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		register_rest_route( 'virtualis-akademia/v1', '/contact', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'send' ],
			'permission_callback' => static fn () => is_user_logged_in(),
			'args'                => [
				'name'    => [ 'required' => true, 'type' => 'string' ],
				'email'   => [ 'required' => true, 'type' => 'string' ],
				'message' => [ 'required' => true, 'type' => 'string' ],
				'consent' => [ 'required' => true, 'type' => 'boolean' ],
			],
		] );
	}

	public function send( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! $request->get_param( 'consent' ) ) {
			return new \WP_REST_Response( [ 'error' => 'consent_required' ], 400 );
		}

		$name    = sanitize_text_field( (string) $request->get_param( 'name' ) );
		$email   = sanitize_email( (string) $request->get_param( 'email' ) );
		$message = sanitize_textarea_field( (string) $request->get_param( 'message' ) );

		if ( ! $name || ! is_email( $email ) || ! $message ) {
			return new \WP_REST_Response( [ 'error' => 'invalid_input' ], 400 );
		}

		$user    = wp_get_current_user();
		$subject = sprintf(
			/* translators: %s: sender's name. */
			__( 'Virtuális Akadémia – kapcsolatfelvétel: %s', 'virtualis-akademia' ),
			$name
		);

		$body = sprintf(
			"Név: %s\nE-mail: %s\nWordPress felhasználó: %s (#%d)\n\nÜzenet:\n%s",
			$name,
			$email,
			$user->user_login,
			$user->ID,
			$message
		);

		// Replying to the message answers the sender: the address typed into
		// the form. The display name is stripped of the characters that
		// would break an address header (comma, quotes, <>, ;).
		$reply_name = trim( preg_replace( '/[\\\\",;<>\\[\\]()@:]+/', ' ', $name ) );
		$reply_name = trim( preg_replace( '/\s+/', ' ', $reply_name ) );
		$headers    = [ 'Reply-To: ' . ( '' !== $reply_name ? $reply_name . ' <' . $email . '>' : $email ) ];

		$sent = wp_mail( self::RECIPIENT, $subject, $body, $headers );

		if ( ! $sent ) {
			return new \WP_REST_Response( [ 'error' => 'mail_failed' ], 500 );
		}

		return new \WP_REST_Response( [ 'success' => true ] );
	}
}
