<?php

namespace VirtualisAkademia\Admin;

use VirtualisAkademia\Hookable;
use VirtualisAkademia\PostTypes\DepartmentPostType;

/**
 * Tanszékek → Hotspotok elrendezése: every lesson hotspot of a department's
 * room on one screen -- dragged around in the 360° view (as learners see it)
 * or on the flat overview of the whole panorama, then saved in one go.
 * The per-lesson meta box (LessonHotspotMetaBox) still works; both write the
 * same `_va_hotspot_yaw` / `_va_hotspot_pitch` meta.
 *
 * Doors are shown for orientation only (they're set on the department).
 */
class HotspotEditorPage implements Hookable {

	private const SLUG       = 'va-hotspot-editor';
	private const ACTION     = 'va_save_hotspots';
	private const CAPABILITY = 'edit_others_posts';

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'admin_post_' . self::ACTION, [ $this, 'save' ] );
	}

	public function add_page(): void {
		add_submenu_page(
			'edit.php?post_type=' . DepartmentPostType::SLUG,
			__( 'Hotspotok elrendezése', 'virtualis-akademia' ),
			__( 'Hotspotok elrendezése', 'virtualis-akademia' ),
			self::CAPABILITY,
			self::SLUG,
			[ $this, 'render' ]
		);
	}

	public static function url( int $department_id = 0 ): string {
		$args = [ 'post_type' => DepartmentPostType::SLUG, 'page' => self::SLUG ];

		if ( $department_id ) {
			$args['department'] = $department_id;
		}

		return add_query_arg( $args, admin_url( 'edit.php' ) );
	}

	public function enqueue( string $hook ): void {
		if ( ! str_ends_with( $hook, '_page_' . self::SLUG ) ) {
			return;
		}

		wp_enqueue_style( 'va-pannellum', VA_PLUGIN_URL . 'assets/vendor/pannellum/pannellum.css', [], VA_VERSION );
		wp_enqueue_script( 'va-pannellum', VA_PLUGIN_URL . 'assets/vendor/pannellum/pannellum.js', [], VA_VERSION, true );
		wp_enqueue_style( 'va-hotspot-editor', VA_PLUGIN_URL . 'assets/admin/hotspot-editor.css', [], VA_VERSION );
		wp_enqueue_script( 'va-hotspot-editor', VA_PLUGIN_URL . 'assets/admin/hotspot-editor.js', [ 'va-pannellum' ], VA_VERSION, true );
	}

	/** Departments that have a panorama and a course, in curriculum order. */
	private function departments(): array {
		$departments = get_posts( [
			'post_type'      => DepartmentPostType::SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => [ 'menu_order' => 'ASC', 'ID' => 'ASC' ],
		] );

		return array_values( array_filter(
			$departments,
			static fn( $department ) => get_post_meta( $department->ID, '_va_panorama_image_id', true )
				&& (int) get_post_meta( $department->ID, '_va_linked_course_id', true ) > 0
		) );
	}

	/** @return int[] */
	private static function lesson_ids( int $department_id ): array {
		$course_id = (int) get_post_meta( $department_id, '_va_linked_course_id', true );

		return $course_id && function_exists( 'learndash_course_get_steps_by_type' )
			? array_map( 'intval', learndash_course_get_steps_by_type( $course_id, 'sfwd-lessons' ) )
			: [];
	}

	private static function title( $post ): string {
		return html_entity_decode( get_the_title( $post ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	private function editor_data( \WP_Post $department ): array {
		$panorama_id = (int) get_post_meta( $department->ID, '_va_panorama_image_id', true );
		$lessons     = [];

		foreach ( self::lesson_ids( $department->ID ) as $index => $lesson_id ) {
			$lessons[] = [
				'id'      => $lesson_id,
				'number'  => $index + 1,
				'title'   => self::title( $lesson_id ),
				'yaw'     => (float) get_post_meta( $lesson_id, '_va_hotspot_yaw', true ),
				'pitch'   => (float) get_post_meta( $lesson_id, '_va_hotspot_pitch', true ),
				'editUrl' => get_edit_post_link( $lesson_id, 'raw' ),
			];
		}

		return [
			'department' => DepartmentPostType::label( $department ),
			// Same full-resolution image the learners get (SceneDataProvider).
			'panorama'   => (string) ( wp_get_original_image_url( $panorama_id ) ?: wp_get_attachment_url( $panorama_id ) ),
			'preview'    => (string) wp_get_attachment_image_url( $panorama_id, 'large' ),
			'lessons'    => $lessons,
			'doors'      => [
				[
					'label' => __( 'Ajtó vissza az Átriumba', 'virtualis-akademia' ),
					'yaw'   => (float) get_post_meta( $department->ID, '_va_return_yaw', true ),
					'pitch' => (float) get_post_meta( $department->ID, '_va_return_pitch', true ),
				],
			],
		];
	}

	public function render(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod ehhez az oldalhoz.', 'virtualis-akademia' ) );
		}

		$departments = $this->departments();
		$requested   = isset( $_GET['department'] ) ? absint( $_GET['department'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current     = null;

		foreach ( $departments as $department ) {
			if ( ! $current || $department->ID === $requested ) {
				$current = $department;
			}
		}

		echo '<div class="wrap va-hse">';
		echo '<h1>' . esc_html__( 'Hotspotok elrendezése', 'virtualis-akademia' ) . '</h1>';

		if ( isset( $_GET['saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( sprintf(
					/* translators: %d: number of lessons */
					_n( '%d lecke hotspotja elmentve.', '%d lecke hotspotja elmentve.', absint( $_GET['saved'] ), 'virtualis-akademia' ), // phpcs:ignore
					absint( $_GET['saved'] ) // phpcs:ignore
				) )
			);
		}

		if ( ! $current ) {
			echo '<p>' . esc_html__( 'Még nincs olyan tanszék, amelyhez panorámakép és kurzus is tartozik.', 'virtualis-akademia' ) . '</p></div>';
			return;
		}

		echo '<nav class="nav-tab-wrapper va-hse__tabs">';
		foreach ( $departments as $department ) {
			printf(
				'<a href="%s" class="nav-tab%s" data-va-hse-tab>%s</a>',
				esc_url( self::url( $department->ID ) ),
				$department->ID === $current->ID ? ' nav-tab-active' : '',
				esc_html( DepartmentPostType::label( $department ) )
			);
		}
		echo '</nav>';

		?>
		<p class="va-hse__intro">
			<?php esc_html_e( 'Fogd meg és húzd a számozott hotspotokat a 360°-os nézetben vagy az alatta lévő teljes panorámán. A listában egy leckére kattintva a nézet odafordul. A változások a „Mentés” gombbal élesednek.', 'virtualis-akademia' ); ?>
		</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="va-hse__form" data-va-hse-form>
			<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
			<input type="hidden" name="department" value="<?php echo esc_attr( (string) $current->ID ); ?>" />
			<?php wp_nonce_field( self::ACTION . '_' . $current->ID ); ?>

			<div class="va-hse__layout">
				<div class="va-hse__stage">
					<div class="va-hse__viewer" data-va-hse-viewer></div>
					<div class="va-hse__flat" data-va-hse-flat></div>
				</div>
				<aside class="va-hse__side">
					<ol class="va-hse__list" data-va-hse-list></ol>
					<div class="va-hse__actions">
						<button type="submit" class="button button-primary button-large" data-va-hse-save><?php esc_html_e( 'Mentés', 'virtualis-akademia' ); ?></button>
						<button type="button" class="button" data-va-hse-reset disabled><?php esc_html_e( 'Változások elvetése', 'virtualis-akademia' ); ?></button>
						<span class="va-hse__dirty" data-va-hse-dirty hidden><?php esc_html_e( 'Nem mentett változások', 'virtualis-akademia' ); ?></span>
					</div>
					<p class="description">
						<?php
						printf(
							/* translators: %s: link to the department edit screen */
							esc_html__( 'Az ajtók helye a tanszék szerkesztőjében állítható: %s', 'virtualis-akademia' ),
							'<a href="' . esc_url( (string) get_edit_post_link( $current->ID ) ) . '">' . esc_html( self::title( $current ) ) . '</a>'
						);
						?>
					</p>
				</aside>
			</div>
			<script type="application/json" data-va-hse-data><?php echo wp_json_encode( $this->editor_data( $current ), JSON_HEX_TAG | JSON_HEX_AMP ); ?></script>
		</form>
		<?php
		echo '</div>';
	}

	public function save(): void {
		$department_id = isset( $_POST['department'] ) ? absint( $_POST['department'] ) : 0;

		if ( ! current_user_can( self::CAPABILITY ) || ! check_admin_referer( self::ACTION . '_' . $department_id ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod ehhez a művelethez.', 'virtualis-akademia' ) );
		}

		$allowed = self::lesson_ids( $department_id );
		$posted  = isset( $_POST['hotspot'] ) && is_array( $_POST['hotspot'] ) ? wp_unslash( $_POST['hotspot'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each value cast below.
		$saved   = 0;

		foreach ( $posted as $lesson_id => $position ) {
			$lesson_id = absint( $lesson_id );

			// Only this room's lessons, and only lessons this user may edit.
			if ( ! in_array( $lesson_id, $allowed, true ) || ! current_user_can( 'edit_post', $lesson_id ) || ! is_array( $position ) ) {
				continue;
			}

			$yaw   = (float) ( $position['yaw'] ?? 0 );
			$pitch = (float) ( $position['pitch'] ?? 0 );

			// Wrap yaw into -180..180, clamp pitch.
			$yaw   = fmod( fmod( $yaw + 180, 360 ) + 360, 360 ) - 180;
			$pitch = max( -90, min( 90, $pitch ) );

			update_post_meta( $lesson_id, '_va_hotspot_yaw', round( $yaw, 2 ) );
			update_post_meta( $lesson_id, '_va_hotspot_pitch', round( $pitch, 2 ) );
			++$saved;
		}

		wp_safe_redirect( add_query_arg( 'saved', $saved, self::url( $department_id ) ) );
		exit;
	}
}
