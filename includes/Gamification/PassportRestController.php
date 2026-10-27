<?php

namespace VirtualisAkademia\Gamification;

use VirtualisAkademia\Certificates\CertificateService;
use VirtualisAkademia\Hookable;
use VirtualisAkademia\Integration\LearnDash\StepStatus;
use VirtualisAkademia\PostTypes\DepartmentPostType;

class PassportRestController implements Hookable {

	private PassportService $passport;

	public function __construct() {
		$this->passport = new PassportService();
	}

	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		register_rest_route( 'virtualis-akademia/v1', '/passport', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_passport' ],
			'permission_callback' => static fn () => is_user_logged_in(),
		] );
	}

	public function get_passport(): \WP_REST_Response {
		$user_id     = get_current_user_id();
		$stamped_ids = $this->passport->get_stamped_department_ids( $user_id );

		$departments = get_posts( [
			'post_type'      => DepartmentPostType::SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => [ 'menu_order' => 'ASC', 'ID' => 'ASC' ],
		] );

		$stamps = [];
		$number = 0;

		foreach ( $departments as $department ) {
			$course_id = (int) get_post_meta( $department->ID, '_va_linked_course_id', true );

			if ( $course_id <= 0 ) {
				continue; // Not an awardable stamp slot (no course attached yet).
			}

			$stamped  = in_array( $department->ID, $stamped_ids, true );
			$progress = StepStatus::lesson_counts( $course_id, $user_id );
			++$number;

			$stamps[] = [
				'departmentId'   => $department->ID,
				'sceneId'        => $department->post_name,
				'number'         => $number,
				'label'          => DepartmentPostType::label( $department ),
				'icon'           => DepartmentPostType::stamp_icon( $department, $number ),
				'lessonsDone'    => $stamped ? $progress['total'] : $progress['done'],
				'lessonsTotal'   => $progress['total'],
				'stamped'        => $stamped,
				// The department certificate, downloadable once the course is done.
				'certificateUrl' => $stamped ? CertificateService::download_url( $user_id, 'course', $course_id ) : null,
			];
		}

		$has_all_stamps = $this->passport->has_all_stamps( $user_id );

		return new \WP_REST_Response( [
			'stamps'               => $stamps,
			'allStamped'           => $has_all_stamps,
			'masterCertificateUrl' => $has_all_stamps ? CertificateService::download_url( $user_id, 'master' ) : null,
		] );
	}
}
