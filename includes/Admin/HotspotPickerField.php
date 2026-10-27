<?php

namespace VirtualisAkademia\Admin;

use VirtualisAkademia\Hookable;

/**
 * Shared "click on the actual panorama to set yaw/pitch" widget used by both
 * DepartmentMetaBox and LessonHotspotMetaBox -- rather than trusting an
 * admin to picture where -83°/12° actually points on a 360° photo, they
 * click the spot and the plain number fields (still shown alongside, so the
 * underlying values stay visible and directly editable) fill in themselves.
 */
class HotspotPickerField implements Hookable {

	public function register(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue' ] );
	}

	public function maybe_enqueue( string $hook ): void {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		global $post_type;

		if ( ! in_array( $post_type, [ 'va_department', 'sfwd-lessons' ], true ) ) {
			return;
		}

		wp_enqueue_style(
			'va-hotspot-picker',
			VA_PLUGIN_URL . 'assets/admin/hotspot-picker.css',
			[],
			VA_VERSION
		);

		wp_enqueue_script(
			'va-hotspot-picker',
			VA_PLUGIN_URL . 'assets/admin/hotspot-picker.js',
			[],
			VA_VERSION,
			true
		);
	}

	/**
	 * @param int   $image_id Attachment ID of the panorama to overlay markers
	 *                        on; if falsy, an explanatory placeholder is
	 *                        shown instead (nothing to click on yet).
	 * @param array $points   Each: ['yawField' => input id, 'pitchField' =>
	 *                        input id, 'label' => string, 'color' => optional
	 *                        CSS color, defaults to WP admin blue].
	 */
	public static function render( int $image_id, array $points ): void {
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '';

		if ( ! $image_url ) {
			printf(
				'<div class="va-hotspot-picker__empty">%s</div>',
				esc_html__( 'Először add meg és mentsd el a panoráma képet, hogy itt megjelenjen a pozicionáló.', 'virtualis-akademia' )
			);

			return;
		}

		printf(
			'<div class="va-hotspot-picker" data-image="%s" data-points="%s"></div>',
			esc_url( $image_url ),
			esc_attr( wp_json_encode( $points ) )
		);
	}
}
