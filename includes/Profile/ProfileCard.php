<?php

namespace VirtualisAkademia\Profile;

use VirtualisAkademia\Gamification\PassportService;
use VirtualisAkademia\Integration\LearnDash\StepStatus;
use VirtualisAkademia\PostTypes\DepartmentPostType;

/**
 * The member's profile card -- the citizen card of the Stitch "EU útlevél"
 * screen: picture, name, user type, member ID and the stamp tally,
 * plus progress per department. One source for the metaverse's profile
 * popup (/profile REST route) and the account page (UmCorePages).
 */
class ProfileCard {

	public static function for_user( int $user_id ): array {
		$user       = get_userdata( $user_id );
		$first_name = (string) get_user_meta( $user_id, 'first_name', true );
		$last_name  = (string) get_user_meta( $user_id, 'last_name', true );

		// Hungarian order: family name first.
		$full_name = trim( "{$last_name} {$first_name}" );

		$courses = self::course_progress( $user_id );
		$stamped = count( array_filter( $courses, static fn( $course ) => $course['stamped'] ) );
		$total   = count( $courses );

		return [
			'displayName'  => $full_name ?: ( $user ? $user->display_name : '' ),
			'firstName'    => $first_name,
			'initials'     => self::initials( $full_name ?: ( $user ? $user->display_name : '' ) ),
			'avatarUrl'    => Avatar::url( $user_id ),
			'memberId'     => self::member_id( $user ),
			'userType'     => self::user_type( $user ),
			'stamps'       => [ 'done' => $stamped, 'total' => $total ],
			'registeredAt' => $user ? date_i18n( 'Y. F j.', strtotime( $user->user_registered ) ) : '',
			'courses'      => $courses,
		];
	}

	/** Just what the header's profile button needs (no progress queries). */
	public static function identity( int $user_id ): array {
		$user = get_userdata( $user_id );
		$name = trim( get_user_meta( $user_id, 'last_name', true ) . ' ' . get_user_meta( $user_id, 'first_name', true ) );

		return [
			'avatarUrl' => Avatar::url( $user_id ),
			'initials'  => self::initials( $name ?: ( $user ? $user->display_name : '' ) ),
		];
	}

	/** "Adminisztrátor" for site admins, everyone else "Hallgató". */
	public static function user_type( $user ): string {
		return $user instanceof \WP_User && user_can( $user, 'manage_options' )
			? __( 'Adminisztrátor', 'virtualis-akademia' )
			: __( 'Hallgató', 'virtualis-akademia' );
	}

	/** "VA-2026-0007" -- registration year + user ID. */
	public static function member_id( $user ): string {
		if ( ! $user instanceof \WP_User ) {
			return '';
		}

		return sprintf( 'VA-%s-%04d', gmdate( 'Y', strtotime( $user->user_registered ) ), $user->ID );
	}

	public static function initials( string $name ): string {
		$parts = preg_split( '/\s+/u', trim( $name ) ) ?: [];
		$first = $parts[0] ?? '';
		$last  = count( $parts ) > 1 ? end( $parts ) : '';

		return mb_strtoupper( mb_substr( $first, 0, 1 ) . mb_substr( $last, 0, 1 ) );
	}

	/**
	 * One entry per department with a course attached, in curriculum order.
	 */
	private static function course_progress( int $user_id ): array {
		$departments = get_posts( [
			'post_type'      => DepartmentPostType::SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => [ 'menu_order' => 'ASC', 'ID' => 'ASC' ],
			'meta_key'       => '_va_linked_course_id',
			'meta_value'     => 0,
			'meta_compare'   => '>',
			'meta_type'      => 'NUMERIC',
		] );

		$stamped_ids = ( new PassportService() )->get_stamped_department_ids( $user_id );
		$courses     = [];

		foreach ( $departments as $index => $department ) {
			$course_id = (int) get_post_meta( $department->ID, '_va_linked_course_id', true );
			$stamped  = in_array( $department->ID, $stamped_ids, true );
			$progress = StepStatus::lesson_counts( $course_id, $user_id );
			$done     = $stamped ? $progress['total'] : $progress['done'];

			$courses[] = [
				'number'     => $index + 1,
				'label'      => DepartmentPostType::label( $department ),
				'icon'       => DepartmentPostType::stamp_icon( $department, $index + 1 ),
				'completed'  => $done,
				'total'      => $progress['total'],
				'percentage' => $progress['total'] ? min( 100, (int) round( $done * 100 / $progress['total'] ) ) : 0,
				'stamped'    => $stamped,
			];
		}

		return $courses;
	}
}
