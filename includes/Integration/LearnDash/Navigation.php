<?php

namespace VirtualisAkademia\Integration\LearnDash;

use VirtualisAkademia\Hookable;
use VirtualisAkademia\Support\MetaversePage;

/**
 * Wayfinding between the LearnDash pages and the metaverse. Learners arrive
 * at a lesson by clicking a hotspot in a 3D room, but LearnDash's own chrome
 * only ever points further into the course -- there was no way back to the
 * room short of the browser's back button. This puts a "back to the room"
 * link into the focus-mode header (the otherwise empty logo slot) and a
 * branded title + the same link above the course overview, and greets the
 * learner by first name instead of their login slug.
 */
class Navigation implements Hookable {

	public function register(): void {
		add_filter( 'learndash_focus_header_element', [ $this, 'focus_header_element' ], 10, 4 );
		add_filter( 'ld_focus_mode_welcome_name', [ $this, 'welcome_name' ], 10, 2 );
		add_filter( 'learndash_focus_header_user_dropdown_items', [ $this, 'user_menu_items' ], 10, 2 );
		add_action( 'learndash-course-before', [ $this, 'course_header' ], 10, 3 );
	}

	/**
	 * The metaverse URL opened in the course's own room (and, from a lesson,
	 * facing that lesson's hotspot) -- the same `va_room` / `va_hotspot`
	 * contract RedirectHandler uses after Mark Complete.
	 */
	public static function room_url( int $course_id, int $lesson_id = 0 ): ?string {
		$metaverse_url = MetaversePage::get_url();

		if ( ! $metaverse_url ) {
			return null;
		}

		$department = $course_id ? CourseRoomMapper::department_for_course( $course_id ) : null;

		if ( ! $department ) {
			return $metaverse_url;
		}

		$args = [ 'va_room' => $department->post_name ];

		if ( $lesson_id ) {
			$args['va_hotspot'] = $lesson_id;
		}

		return add_query_arg( $args, $metaverse_url );
	}

	/** The course's room name, carried on the back links for the transition label. */
	public static function room_title( int $course_id ): string {
		$department = $course_id ? CourseRoomMapper::department_for_course( $course_id ) : null;

		return $department ? \VirtualisAkademia\PostTypes\DepartmentPostType::label( $department ) : '';
	}

	public function focus_header_element( $element, $header, $course_id, $user_id ): string {
		$post_id   = get_the_ID();
		$lesson_id = $post_id && $course_id ? (int) learndash_get_lesson_id( $post_id, (int) $course_id ) : 0;

		if ( ! $lesson_id && 'sfwd-lessons' === get_post_type( $post_id ) ) {
			$lesson_id = (int) $post_id;
		}

		$url = self::room_url( (int) $course_id, $lesson_id );

		if ( ! $url ) {
			return (string) $element;
		}

		return sprintf(
			'<a class="va-ld-back" href="%1$s" data-va-room-title="%3$s"><span class="va-ld-back__icon" aria-hidden="true">←</span><span class="va-ld-back__label">%2$s</span></a>',
			esc_url( $url ),
			esc_html__( 'Vissza a terembe', 'virtualis-akademia' ),
			esc_attr( self::room_title( (int) $course_id ) )
		);
	}

	public function welcome_name( $name, $user ): string {
		if ( $user instanceof \WP_User ) {
			$first = trim( (string) $user->first_name );

			if ( '' !== $first ) {
				return $first;
			}

			if ( '' !== trim( (string) $user->display_name ) ) {
				return $user->display_name;
			}
		}

		return (string) $name;
	}

	/**
	 * @param array<string, array{url: string, label: string}> $items
	 */
	public function user_menu_items( $items, $course_id ): array {
		$items = is_array( $items ) ? $items : [];
		$url   = self::room_url( (int) $course_id );

		$prepend = [];

		if ( $url ) {
			$prepend['va-metaverse'] = [
				'url'   => $url,
				'label' => __( 'Vissza a Virtuális Akadémiába', 'virtualis-akademia' ),
			];
		}

		// Logging out from a lesson otherwise lands on the (locked) course
		// page, which the access gate bounces to the landing page anyway.
		if ( isset( $items['logout'] ) ) {
			$items['logout']['url']   = wp_logout_url( home_url( '/' ) );
			$items['logout']['label'] = __( 'Kijelentkezés', 'virtualis-akademia' );
		}

		return $prepend + $items;
	}

	public function course_header( $post_id, $course_id, $user_id ): void {
		$url = self::room_url( (int) $course_id );
		?>
		<header class="va-course-head">
			<?php if ( $url ) : ?>
				<a class="va-ld-back va-ld-back--inline" href="<?php echo esc_url( $url ); ?>" data-va-room-title="<?php echo esc_attr( self::room_title( (int) $course_id ) ); ?>"><span class="va-ld-back__icon" aria-hidden="true">←</span><span class="va-ld-back__label"><?php esc_html_e( 'Vissza a terembe', 'virtualis-akademia' ); ?></span></a>
			<?php endif; ?>
			<span class="va-label-caps va-course-head__eyebrow"><?php esc_html_e( 'Tanfolyam', 'virtualis-akademia' ); ?></span>
			<h1 class="va-course-head__title"><?php echo esc_html( get_the_title( (int) $course_id ) ); ?></h1>
		</header>
		<?php
	}
}
