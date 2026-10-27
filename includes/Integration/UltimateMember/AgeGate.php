<?php

namespace VirtualisAkademia\Integration\UltimateMember;

use VirtualisAkademia\Hookable;

/**
 * Blocks registration for under-16s, using the `birth_date` field added to
 * the register form by Activation::ensure_registration_fields(). Hooks the
 * same "add a field-level error" mechanism Ultimate Member's own validators
 * use (`um_submit_form_errors_hook__registration`), so this surfaces exactly
 * like a native validation failure -- inline on the field, submission
 * blocked -- rather than a generic rejection after the fact.
 */
class AgeGate implements Hookable {

	private const MINIMUM_AGE = 16;

	public function register(): void {
		add_action( 'um_submit_form_errors_hook__registration', [ $this, 'validate_age' ] );
		add_filter( 'um_get_field__birth_date', [ $this, 'limit_date_picker' ] );
	}

	/**
	 * The date picker's latest selectable day is exactly MINIMUM_AGE years
	 * before today (the picker also opens there), the earliest 100 years
	 * back -- so nobody under 16 can even pick a date. Same cut-off as
	 * validate_age(); computed per request, so it moves with the calendar.
	 */
	public function limit_date_picker( $field ) {
		if ( ! is_array( $field ) || 'date' !== ( $field['type'] ?? '' ) ) {
			return $field;
		}

		$today = new \DateTimeImmutable( 'today', wp_timezone() );

		$field['range']    = 'years';
		$field['date_max'] = $today->modify( '-' . self::MINIMUM_AGE . ' years' )->format( 'Y-m-d' );
		$field['date_min'] = $today->modify( '-100 years' )->format( 'Y-m-d' );

		return $field;
	}

	public function validate_age( array $submitted_data ): void {
		if ( empty( $submitted_data['birth_date'] ) ) {
			return;
		}

		$birth_timestamp = strtotime( (string) $submitted_data['birth_date'] );

		if ( ! $birth_timestamp ) {
			return; // Not our job to validate the date format itself.
		}

		// Calendar-day comparison (no time-of-day / timezone drift): turning
		// 16 today is allowed, same as the picker's latest selectable day.
		$latest = ( new \DateTimeImmutable( 'today', wp_timezone() ) )->modify( '-' . self::MINIMUM_AGE . ' years' )->format( 'Y-m-d' );

		if ( gmdate( 'Y-m-d', $birth_timestamp ) > $latest ) {
			UM()->form()->add_error(
				'birth_date',
				sprintf(
					/* translators: %d: minimum age in years */
					__( 'A regisztrációhoz legalább %d évesnek kell lenned.', 'virtualis-akademia' ),
					self::MINIMUM_AGE
				)
			);
		}
	}
}
