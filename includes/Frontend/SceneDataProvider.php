<?php

namespace VirtualisAkademia\Frontend;

use VirtualisAkademia\Admin\CorridorSettingsPage;
use VirtualisAkademia\Certificates\CertificateService;
use VirtualisAkademia\Integration\LearnDash\StepStatus;
use VirtualisAkademia\PostTypes\DepartmentPostType;

/**
 * Builds the full corridor + department scene graph consumed by the
 * Pannellum-based viewer: one scene per department plus a fixed "corridor"
 * scene, each carrying the door hotspots that link them together and (for
 * departments with a LearnDash course attached) the lesson hotspots for
 * that course's steps, tagged with the current user's progress state.
 */
class SceneDataProvider {

	public function get_scene_data(): array {
		$departments = get_posts( [
			'post_type'      => DepartmentPostType::SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			// Ties (every department sits at menu_order 0) fall back to
			// creation order -- the curriculum order everywhere else.
			'orderby'        => [ 'menu_order' => 'ASC', 'ID' => 'ASC' ],
		] );

		$user_id     = get_current_user_id();
		$scenes      = [];
		$minimap     = [];
		$entry_views = [];

		$number = 0;

		foreach ( $departments as $department ) {
			$panorama_id = (int) get_post_meta( $department->ID, '_va_panorama_image_id', true );

			if ( ! $panorama_id ) {
				continue;
			}

			++$number;

			$scene               = $this->build_department_scene( $department, $panorama_id, $user_id );
			$scene['department'] = $this->department_info( $department, $number, $user_id );

			$scenes[ $department->post_name ]      = $scene;
			$entry_views[ $department->post_name ] = [
				'yaw'   => $scene['entryYaw'],
				'pitch' => $scene['entryPitch'],
			];

			$position = json_decode( (string) get_post_meta( $department->ID, '_va_minimap_position', true ), true );

			if ( is_array( $position ) && isset( $position['x'], $position['y'] ) ) {
				$minimap[ $department->post_name ] = [
					'x'     => (float) $position['x'],
					'y'     => (float) $position['y'],
					'title' => DepartmentPostType::label( $department ),
				];
			}
		}

		$scenes = [ 'corridor' => $this->build_corridor_scene( $departments, $entry_views ) ] + $scenes;

		return [
			'firstSceneId'   => 'corridor',
			'onboardingSeen' => (bool) get_user_meta( $user_id, '_va_onboarding_seen', true ),
			'greeting'       => $user_id ? Greeting::consume( $user_id ) : null,
			'minimap'        => $minimap,
			'scenes'         => $scenes,
			'euInfoText'     => CorridorSettingsPage::get_eu_info_text(),
			'project'        => \VirtualisAkademia\Support\ProjectInfo::for_client(),
			// All four stamps: the Átrium panel's button downloads it directly.
			'masterCertificateUrl' => $user_id && ( new \VirtualisAkademia\Gamification\PassportService() )->has_all_stamps( $user_id )
				? CertificateService::download_url( $user_id, 'master' )
				: '',
		];
	}

	/**
	 * @param array<string, array{yaw: float, pitch: float}> $entry_views
	 *        Per-department "where the next task is" camera direction, so the
	 *        corridor's door into a room drops the visitor facing that task
	 *        rather than an arbitrary default landing spot.
	 */
	private function build_corridor_scene( array $departments, array $entry_views ): array {
		$settings    = get_option( 'va_corridor_settings', [] );
		$panorama_id = (int) ( $settings['panorama_image_id'] ?? 0 );
		$audio_id    = (int) ( $settings['audio_id'] ?? 0 );

		$hotspots = [];

		foreach ( $departments as $department ) {
			if ( ! get_post_meta( $department->ID, '_va_panorama_image_id', true ) ) {
				continue;
			}

			$entry = $entry_views[ $department->post_name ] ?? null;

			$hotspots[] = [
				'kind'          => 'door',
				'targetSceneId' => $department->post_name,
				'yaw'           => (float) get_post_meta( $department->ID, '_va_corridor_hotspot_yaw', true ),
				'pitch'         => (float) get_post_meta( $department->ID, '_va_corridor_hotspot_pitch', true ),
				'targetYaw'     => $entry ? $entry['yaw'] : (float) get_post_meta( $department->ID, '_va_return_yaw', true ),
				'targetPitch'   => $entry ? $entry['pitch'] : (float) get_post_meta( $department->ID, '_va_return_pitch', true ),
				'title'         => DepartmentPostType::label( $department ),
			];
		}

		foreach ( CorridorSettingsPage::get_plaques() as $index => $plaque ) {
			$hotspots[] = [
				'kind'     => 'plaque',
				'id'       => 'plaque-' . $index,
				'title'    => $plaque['label'],
				'imageUrl' => $plaque['imageUrl'],
				'link'     => $plaque['link'],
				'yaw'      => $plaque['yaw'],
				'pitch'    => $plaque['pitch'],
			];
		}

		return [
			'title'       => __( 'Átrium', 'virtualis-akademia' ),
			...self::panorama( $panorama_id ),
			'audioUrl'    => $audio_id ? wp_get_attachment_url( $audio_id ) : null,
			'hotspots'    => $hotspots,
		];
	}

	private function build_department_scene( \WP_Post $department, int $panorama_id, int $user_id ): array {
		$audio_id  = (int) get_post_meta( $department->ID, '_va_ambient_audio_id', true );
		$course_id = (int) get_post_meta( $department->ID, '_va_linked_course_id', true );

		$return_yaw   = (float) get_post_meta( $department->ID, '_va_return_yaw', true );
		$return_pitch = (float) get_post_meta( $department->ID, '_va_return_pitch', true );

		$hotspots   = [];
		$hotspots[] = [
			'kind'          => 'door',
			'targetSceneId' => 'corridor',
			'yaw'           => $return_yaw,
			'pitch'         => $return_pitch,
			'targetYaw'     => (float) get_post_meta( $department->ID, '_va_corridor_hotspot_yaw', true ),
			'targetPitch'   => (float) get_post_meta( $department->ID, '_va_corridor_hotspot_pitch', true ),
			'title'         => __( 'Vissza az Átriumba', 'virtualis-akademia' ),
		];

		$lesson_hotspots = $course_id ? $this->get_lesson_hotspots( $course_id, $user_id ) : [];

		foreach ( $lesson_hotspots as $lesson_hotspot ) {
			$hotspots[] = $lesson_hotspot;
		}

		$entry_view = $this->resolve_entry_view( $lesson_hotspots, $return_yaw, $return_pitch );

		// The department's name, not the room's decorative one ("Demokrácia",
		// not "Demokrácia könyvtárszoba") -- the header, panel and transition
		// labels all read this.
		return [
			'title'       => DepartmentPostType::label( $department ),
			...self::panorama( $panorama_id ),
			'audioUrl'    => $audio_id ? wp_get_attachment_url( $audio_id ) : null,
			'entryYaw'    => $entry_view['yaw'],
			'entryPitch'  => $entry_view['pitch'],
			'hotspots'    => $hotspots,
		];
	}

	/**
	 * Where the camera should face when the visitor walks into this room:
	 * centered on the lesson they should do next -- the first hotspot, in the
	 * room's own hotspot order, that LearnDash reports as currently available
	 * (not completed, not locked). Falls back to the room's plain landing
	 * spot when there's no such lesson (course finished, empty, or the
	 * visitor has no LearnDash progress yet). Only the opening view moves --
	 * the hotspots themselves keep their configured positions.
	 */
	private function resolve_entry_view( array $lesson_hotspots, float $fallback_yaw, float $fallback_pitch ): array {
		foreach ( $lesson_hotspots as $hotspot ) {
			if ( StepStatus::AVAILABLE === ( $hotspot['status'] ?? '' ) ) {
				return [
					'yaw'   => (float) $hotspot['yaw'],
					'pitch' => (float) $hotspot['pitch'],
				];
			}
		}

		return [
			'yaw'   => $fallback_yaw,
			'pitch' => $fallback_pitch,
		];
	}

	/**
	 * The panorama at full resolution: WordPress downsizes big uploads to a
	 * 2560px "-scaled" copy and wp_get_attachment_url() returns that. The
	 * original goes to the viewer, the scaled copy only as the fallback for a
	 * device whose WebGL texture limit is too small for it (Viewer.js).
	 *
	 * @return array{panoramaUrl: string, panoramaWidth?: int, panoramaHeight?: int, panoramaFallbackUrl?: string}
	 */
	private static function panorama( int $attachment_id ): array {
		if ( ! $attachment_id ) {
			return [ 'panoramaUrl' => '' ];
		}

		$scaled   = (string) wp_get_attachment_url( $attachment_id );
		$original = (string) ( wp_get_original_image_url( $attachment_id ) ?: $scaled );
		$data     = [ 'panoramaUrl' => $original ];

		$path = wp_get_original_image_path( $attachment_id );
		$size = $path && is_readable( $path ) ? @getimagesize( $path ) : false; // phpcs:ignore

		if ( $size ) {
			$data['panoramaWidth']  = (int) $size[0];
			$data['panoramaHeight'] = (int) $size[1];
		}

		if ( $scaled && $scaled !== $original ) {
			$data['panoramaFallbackUrl'] = $scaled;
		}

		return $data;
	}

	/**
	 * get_the_title() is texturized HTML ("&#8211;"); the viewer renders
	 * titles as text, so hand it plain characters.
	 */
	private static function plain_title( $post ): string {
		return html_entity_decode( get_the_title( $post ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	/**
	 * What the room's info panel (RoomPanel.js) shows beyond the hotspots
	 * themselves: the department's number and short name, the course
	 * summary, and -- once the course is complete -- the certificate link.
	 */
	private function department_info( \WP_Post $department, int $number, int $user_id ): array {
		$course_id = (int) get_post_meta( $department->ID, '_va_linked_course_id', true );
		$course    = $course_id ? get_post( $course_id ) : null;
		$completed = $course && $user_id && function_exists( 'learndash_course_completed' )
			&& learndash_course_completed( $user_id, $course_id );

		return [
			'id'             => $department->ID,
			'number'         => $number,
			'label'          => DepartmentPostType::label( $department ),
			'icon'           => DepartmentPostType::stamp_icon( $department, $number ),
			'summary'        => $course ? wp_strip_all_tags( (string) $course->post_excerpt ) : '',
			'courseUrl'      => $course ? (string) get_permalink( $course ) : '',
			'completed'      => (bool) $completed,
			'certificateUrl' => $completed ? CertificateService::download_url( $user_id, 'course', $course_id ) : '',
		];
	}

	private function get_lesson_hotspots( int $course_id, int $user_id ): array {
		$lesson_ids = learndash_course_get_steps_by_type( $course_id, 'sfwd-lessons' );
		$hotspots   = [];

		foreach ( $lesson_ids as $lesson_id ) {
			$hotspots[] = [
				'kind'   => 'lesson',
				'id'     => $lesson_id,
				'title'  => self::plain_title( $lesson_id ),
				'yaw'    => (float) get_post_meta( $lesson_id, '_va_hotspot_yaw', true ),
				'pitch'  => (float) get_post_meta( $lesson_id, '_va_hotspot_pitch', true ),
				'status' => StepStatus::for_lesson( $lesson_id, $user_id ),
				'url'    => get_permalink( $lesson_id ),
			];
		}

		return $hotspots;
	}
}
