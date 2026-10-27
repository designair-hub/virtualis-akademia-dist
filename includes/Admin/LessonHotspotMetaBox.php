<?php

namespace VirtualisAkademia\Admin;

use VirtualisAkademia\Hookable;
use VirtualisAkademia\Integration\LearnDash\CourseRoomMapper;

/**
 * Adds the spatial hotspot fields LearnDash itself has no concept of (yaw,
 * pitch, which symbolic object renders the hotspot) directly to the lesson
 * edit screen, so course structure and 3D placement are edited in one place.
 */
class LessonHotspotMetaBox implements Hookable {

	private const NONCE_ACTION = 'va_lesson_hotspot_meta_box';
	private const NONCE_FIELD  = 'va_lesson_hotspot_meta_box_nonce';

	public function register(): void {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'save_post_sfwd-lessons', [ $this, 'save' ] );
	}

	public function add_meta_box(): void {
		add_meta_box(
			'va_lesson_hotspot',
			__( 'Hotspot a 3D térben', 'virtualis-akademia' ),
			[ $this, 'render' ],
			'sfwd-lessons',
			'normal',
			'high'
		);
	}

	public function render( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$course_id  = function_exists( 'learndash_get_course_id' ) ? (int) learndash_get_course_id( $post->ID ) : 0;
		$department = $course_id ? CourseRoomMapper::department_for_course( $course_id ) : null;

		if ( $department ) {
			$panorama_id = (int) get_post_meta( $department->ID, '_va_panorama_image_id', true );

			printf(
				'<p class="description">%s <strong>%s</strong></p>',
				esc_html__( 'Tanszék panorámaképe:', 'virtualis-akademia' ),
				esc_html( get_the_title( $department ) )
			);

			HotspotPickerField::render( $panorama_id, [
				[
					'yawField'   => '_va_hotspot_yaw',
					'pitchField' => '_va_hotspot_pitch',
					'label'      => get_the_title( $post ) ?: __( 'Ez a lecke', 'virtualis-akademia' ),
					'color'      => '#00a32a',
				],
			] );
		} else {
			echo '<p class="description">' .
				esc_html__( 'A pozicionáló megjelenítéséhez a leckének egy kurzushoz kell tartoznia, amelyik egy tanszékhez van rendelve.', 'virtualis-akademia' ) .
				'</p>';
		}

		$fields = [
			'_va_hotspot_yaw'          => __( 'Yaw (vízszintes pozíció, -180 – 180)', 'virtualis-akademia' ),
			'_va_hotspot_pitch'        => __( 'Pitch (függőleges pozíció, -90 – 90)', 'virtualis-akademia' ),
			'_va_hotspot_icon_variant' => __( 'Szimbolikus tárgy (pl. Könyvespolc)', 'virtualis-akademia' ),
		];

		foreach ( $fields as $key => $label ) {
			$value = get_post_meta( $post->ID, $key, true );
			printf(
				'<p><label for="%1$s"><strong>%2$s</strong></label><br /><input type="text" class="widefat" id="%1$s" name="%1$s" value="%3$s" /></p>',
				esc_attr( $key ),
				esc_html( $label ),
				esc_attr( is_scalar( $value ) ? $value : '' )
			);
		}

		$is_noticeboard = (bool) get_post_meta( $post->ID, '_va_hotspot_is_noticeboard', true );
		printf(
			'<p><label><input type="checkbox" name="_va_hotspot_is_noticeboard" value="1" %s /> %s</label></p>',
			checked( $is_noticeboard, true, false ),
			esc_html__( 'Hirdetőtábla stílusú hotspot', 'virtualis-akademia' )
		);
	}

	public function save( int $post_id ): void {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION )
		) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['_va_hotspot_yaw'] ) ) {
			update_post_meta( $post_id, '_va_hotspot_yaw', (float) wp_unslash( $_POST['_va_hotspot_yaw'] ) );
		}

		if ( isset( $_POST['_va_hotspot_pitch'] ) ) {
			update_post_meta( $post_id, '_va_hotspot_pitch', (float) wp_unslash( $_POST['_va_hotspot_pitch'] ) );
		}

		if ( isset( $_POST['_va_hotspot_icon_variant'] ) ) {
			update_post_meta( $post_id, '_va_hotspot_icon_variant', sanitize_text_field( wp_unslash( $_POST['_va_hotspot_icon_variant'] ) ) );
		}

		update_post_meta( $post_id, '_va_hotspot_is_noticeboard', isset( $_POST['_va_hotspot_is_noticeboard'] ) );
	}
}
