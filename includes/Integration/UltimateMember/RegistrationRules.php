<?php

namespace VirtualisAkademia\Integration\UltimateMember;

use VirtualisAkademia\Hookable;
use VirtualisAkademia\Support\Hungarian;
use VirtualisAkademia\Support\ParticipationFields as Fields;

/**
 * Server-side half of the disadvantage question's rules (the register page
 * script enforces the same thing interactively, but a disabled checkbox is
 * only a UI hint):
 *
 *   - the exclusive option ("Egyik sem…") can't be combined with any other
 *     ("Egyéb" can),
 *   - "Egyéb" needs its free-text description,
 *   - without "Egyéb", no stale description is stored.
 */
class RegistrationRules implements Hookable {

	public function register(): void {
		add_action( 'um_submit_form_errors_hook__registration', [ $this, 'validate' ] );
		add_action( 'um_registration_complete', [ $this, 'clean_other_text' ], 10, 1 );
	}

	/**
	 * @return string[]
	 */
	private static function selected( array $data ): array {
		$value = $data[ Fields::STATUS_KEY ] ?? [];

		return array_values( array_filter( array_map( 'strval', is_array( $value ) ? $value : [ $value ] ) ) );
	}

	public function validate( array $submitted_data ): void {
		$selected = self::selected( $submitted_data );

		if ( ! $selected ) {
			return; // UM's own "required" check reports the empty group.
		}

		$exclusive = array_values( array_intersect( $selected, Fields::EXCLUSIVE ) );

		if ( $exclusive && count( $selected ) > 1 ) {
			UM()->form()->add_error(
				Fields::STATUS_KEY,
				sprintf(
					/* translators: 1: definite article (A/Az), 2: the exclusive option's text */
					__( '%1$s „%2$s” lehetőség mellé nem jelölhetsz meg mást.', 'virtualis-akademia' ),
					ucfirst( Hungarian::article( $exclusive[0] ) ),
					rtrim( $exclusive[0], '.' )
				)
			);

			return;
		}

		if ( in_array( Fields::OPTION_OTHER, $selected, true )
			&& '' === trim( (string) ( $submitted_data[ Fields::OTHER_KEY ] ?? '' ) )
		) {
			UM()->form()->add_error( Fields::OTHER_KEY, __( 'Az „Egyéb” választásakor kérjük, röviden írd le a helyzetedet.', 'virtualis-akademia' ) );
		}
	}

	public function clean_other_text( $user_id ): void {
		$selected = get_user_meta( (int) $user_id, Fields::STATUS_KEY, true );
		$selected = is_array( $selected ) ? $selected : [ $selected ];

		if ( ! in_array( Fields::OPTION_OTHER, $selected, true ) ) {
			delete_user_meta( (int) $user_id, Fields::OTHER_KEY );
		}
	}
}
