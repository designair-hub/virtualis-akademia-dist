<?php

namespace VirtualisAkademia\Integration\LearnDash;

use LearnDash\Core\Mappers\Models\Step_Mapper;

/**
 * Thin wrapper around LearnDash's own Step model so hotspot rendering never
 * has to re-derive progression/drip/access rules itself -- Step::is_content_visible()
 * already folds in course access, linear progression, and drip-content dates.
 */
class StepStatus {

	public const COMPLETED = 'completed';
	public const AVAILABLE = 'available';
	public const LOCKED    = 'locked';

	public static function for_lesson( int $lesson_id, int $user_id ): string {
		$step = Step_Mapper::create( $lesson_id );

		if ( ! $step ) {
			return self::LOCKED;
		}

		if ( $step->is_complete( $user_id ) ) {
			return self::COMPLETED;
		}

		if ( ! $step->is_content_visible( $user_id ) ) {
			return self::LOCKED;
		}

		return self::AVAILABLE;
	}

	/**
	 * Lessons done / lessons in the course -- the count learners see on the
	 * room panel ("3 / 7 lecke"), also for courses not started yet (where
	 * LearnDash's own course progress still reports 0 steps).
	 *
	 * @return array{done: int, total: int}
	 */
	public static function lesson_counts( int $course_id, int $user_id ): array {
		if ( ! $course_id || ! function_exists( 'learndash_course_get_steps_by_type' ) ) {
			return [ 'done' => 0, 'total' => 0 ];
		}

		$lesson_ids = learndash_course_get_steps_by_type( $course_id, 'sfwd-lessons' );
		$done       = 0;

		foreach ( $lesson_ids as $lesson_id ) {
			if ( $user_id && self::COMPLETED === self::for_lesson( (int) $lesson_id, $user_id ) ) {
				++$done;
			}
		}

		return [ 'done' => $done, 'total' => count( $lesson_ids ) ];
	}
}
