<?php

namespace VirtualisAkademia\Integration\LearnDash;

use VirtualisAkademia\Gamification\PassportService;
use VirtualisAkademia\Hookable;

/**
 * Reacts to a real LearnDash course completion: stamps the student's EU
 * Passport for that department, and once every awardable department is
 * stamped, marks the Master certificate earned. The per-department
 * certificates themselves need no code -- they're plain LearnDash course
 * settings (§ course completion awards) that fire independently of this.
 */
class CertificateBridge implements Hookable {

	private PassportService $passport;

	public function __construct() {
		$this->passport = new PassportService();
	}

	public function register(): void {
		add_action( 'learndash_course_completed', [ $this, 'handle_course_completed' ] );
	}

	/**
	 * @param array{user: \WP_User, course: \WP_Post} $course_data
	 */
	public function handle_course_completed( array $course_data ): void {
		if ( empty( $course_data['user'] ) || empty( $course_data['course'] ) ) {
			return;
		}

		$user_id   = (int) $course_data['user']->ID;
		$course_id = (int) $course_data['course']->ID;

		$department = CourseRoomMapper::department_for_course( $course_id );

		if ( ! $department ) {
			return;
		}

		$this->passport->award_stamp( $user_id, $department->ID );
		$this->passport->maybe_award_master_certificate( $user_id );
	}
}
