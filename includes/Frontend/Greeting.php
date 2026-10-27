<?php

namespace VirtualisAkademia\Frontend;

use VirtualisAkademia\Hookable;

/**
 * One-shot "Szia, <keresztnév>!" greeting played as the metaverse's opening
 * transition (Transition::intro()) -- right after registering ("new") or
 * after logging in again ("return"). The flag is set at the moment of
 * registration / login and consumed by the first metaverse page load, so a
 * plain reload or coming back from a lesson doesn't greet again.
 */
class Greeting implements Hookable {

	private const META = '_va_greeting';

	public function register(): void {
		add_action( 'um_registration_complete', [ $this, 'flag_new' ], 5 );
		add_action( 'wp_login', [ $this, 'flag_return' ], 10, 2 );
	}

	public function flag_new( $user_id ): void {
		update_user_meta( (int) $user_id, self::META, 'new' );
	}

	/**
	 * UM's post-registration auto-login fires `wp_login` too -- it must not
	 * downgrade a fresh "new" greeting to "return".
	 *
	 * @param string   $login
	 * @param \WP_User $user
	 */
	public function flag_return( $login, $user ): void {
		if ( ! $user instanceof \WP_User || 'new' === get_user_meta( $user->ID, self::META, true ) ) {
			return;
		}

		update_user_meta( $user->ID, self::META, 'return' );
	}

	/**
	 * @return array{type: string, firstName: string}|null
	 */
	public static function consume( int $user_id ): ?array {
		$type = (string) get_user_meta( $user_id, self::META, true );

		if ( ! in_array( $type, [ 'new', 'return' ], true ) ) {
			return null;
		}

		delete_user_meta( $user_id, self::META );

		return [
			'type'      => $type,
			'firstName' => trim( (string) get_user_meta( $user_id, 'first_name', true ) ),
		];
	}
}
