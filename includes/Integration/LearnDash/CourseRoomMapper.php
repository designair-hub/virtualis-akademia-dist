<?php

namespace VirtualisAkademia\Integration\LearnDash;

use VirtualisAkademia\PostTypes\DepartmentPostType;

class CourseRoomMapper {

	public static function department_for_course( int $course_id ): ?\WP_Post {
		if ( ! $course_id ) {
			return null;
		}

		$departments = get_posts( [
			'post_type'      => DepartmentPostType::SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_key'       => '_va_linked_course_id',
			'meta_value'     => $course_id,
		] );

		return $departments[0] ?? null;
	}
}
