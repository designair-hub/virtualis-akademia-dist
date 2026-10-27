<?php

namespace VirtualisAkademia\Integration\LearnDash;

use VirtualisAkademia\Hookable;
use VirtualisAkademia\Support\MetaversePage;

/**
 * Sends a student back into the 3D room they came from after completing a
 * lesson/topic/quiz, instead of LearnDash's default course-page destination.
 *
 * LearnDash forks into two different code paths depending on the course's
 * "Automatic Progression" setting, so both have to be covered:
 * - `learndash_course_step_completion_url` fires when automatic progression
 *   is on and LearnDash does an immediate server-side redirect.
 * - `learndash_template_progression_step_next_url` fires on every render of
 *   the "Next" link in Focus Mode (progression off, the more common setup),
 *   so it must only be overridden when the step was *just* completed --
 *   otherwise it would hijack ordinary forward navigation too.
 */
class RedirectHandler implements Hookable {

	public function register(): void {
		add_filter( 'learndash_course_step_completion_url', [ $this, 'filter_completion_url' ], 10, 3 );
		add_filter( 'learndash_template_progression_step_next_url', [ $this, 'filter_next_url' ], 10, 3 );
	}

	public function filter_completion_url( string $url, int $step_id, int $course_id ): string {
		$user_id = get_current_user_id();
		$data    = learndash_course_get_step_completion_data( $step_id, $course_id, $user_id );

		return $this->build_redirect_url( $course_id, $data ) ?? $url;
	}

	/**
	 * @param mixed $step A LearnDash `Step_Model` instance (lesson/topic/quiz).
	 */
	public function filter_next_url( string $url, $step, int $user_id ): string {
		if ( ! is_object( $step ) || ! method_exists( $step, 'get_id' ) ) {
			return $url;
		}

		$step_id   = $step->get_id();
		$course_id = learndash_get_course_id( $step_id );

		if ( ! $course_id ) {
			return $url;
		}

		$data = learndash_get_step_completed_transient_data( $step_id, $course_id, $user_id );

		if ( ! $data ) {
			// Not the step the user just completed -- leave ordinary "Next" navigation alone.
			return $url;
		}

		return $this->build_redirect_url( $course_id, $data ) ?? $url;
	}

	/**
	 * @param array{is_course_completed: bool, next_step_id: int, next_step_url: string} $completion_data
	 */
	private function build_redirect_url( int $course_id, array $completion_data ): ?string {
		$department = CourseRoomMapper::department_for_course( $course_id );

		if ( ! $department ) {
			return null;
		}

		$metaverse_url = MetaversePage::get_url();

		if ( ! $metaverse_url ) {
			return null;
		}

		$args = [ 'va_room' => $department->post_name ];

		if ( empty( $completion_data['is_course_completed'] ) && ! empty( $completion_data['next_step_id'] ) ) {
			$lesson_id = learndash_get_lesson_id( $completion_data['next_step_id'], $course_id );

			if ( $lesson_id ) {
				$args['va_hotspot'] = $lesson_id;
			}
		}

		return add_query_arg( $args, $metaverse_url );
	}
}
