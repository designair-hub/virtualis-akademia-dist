<?php

namespace VirtualisAkademia\Gamification;

use VirtualisAkademia\PostTypes\DepartmentPostType;

/**
 * Stamps are only ever written here, from CertificateBridge reacting to a
 * real `learndash_course_completed` event -- the client only ever reads
 * this state via the /passport REST route, so a student can't self-award.
 */
class PassportService {

	private const STAMPS_META = '_va_passport_stamps';
	private const MASTER_EARNED_META = '_va_master_certificate_earned';

	public function get_stamped_department_ids( int $user_id ): array {
		$stamps = get_user_meta( $user_id, self::STAMPS_META, true );

		if ( ! is_array( $stamps ) ) {
			return [];
		}

		return wp_list_pluck( $stamps, 'department_id' );
	}

	public function award_stamp( int $user_id, int $department_id ): void {
		$stamps = get_user_meta( $user_id, self::STAMPS_META, true );

		if ( ! is_array( $stamps ) ) {
			$stamps = [];
		}

		foreach ( $stamps as $stamp ) {
			if ( (int) $stamp['department_id'] === $department_id ) {
				return; // Already stamped -- never duplicate or re-date it.
			}
		}

		$stamps[] = [
			'department_id' => $department_id,
			'stamped_at'    => current_time( 'mysql' ),
		];

		update_user_meta( $user_id, self::STAMPS_META, $stamps );
	}

	public function get_awardable_departments(): array {
		// Every department post carries `_va_linked_course_id` (defaulted to 0
		// by register_post_meta), so "EXISTS" alone would match all of them --
		// filter for a real, non-zero course ID instead.
		return get_posts( [
			'post_type'      => DepartmentPostType::SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_key'       => '_va_linked_course_id',
			'meta_value'     => 0,
			'meta_compare'   => '>',
			'meta_type'      => 'NUMERIC',
		] );
	}

	public function has_all_stamps( int $user_id ): bool {
		$awardable = $this->get_awardable_departments();

		if ( empty( $awardable ) ) {
			return false;
		}

		$stamped_ids = $this->get_stamped_department_ids( $user_id );

		foreach ( $awardable as $department ) {
			if ( ! in_array( $department->ID, $stamped_ids, true ) ) {
				return false;
			}
		}

		return true;
	}

	public function has_master_certificate( int $user_id ): bool {
		return (bool) get_user_meta( $user_id, self::MASTER_EARNED_META, true );
	}

	/**
	 * Marks the master certificate as earned the first time all stamps are
	 * present, firing `va_master_certificate_issued` exactly once per user.
	 */
	public function maybe_award_master_certificate( int $user_id ): void {
		if ( $this->has_master_certificate( $user_id ) ) {
			return;
		}

		if ( ! $this->has_all_stamps( $user_id ) ) {
			return;
		}

		update_user_meta( $user_id, self::MASTER_EARNED_META, true );

		/**
		 * Fires once, the first time a user earns every department stamp.
		 *
		 * @param int $user_id
		 */
		do_action( 'va_master_certificate_issued', $user_id );
	}
}
