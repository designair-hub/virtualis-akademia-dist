<?php

namespace VirtualisAkademia\PostTypes;

use VirtualisAkademia\Hookable;

class DepartmentPostType implements Hookable {

	public const SLUG = 'va_department';

	public function register(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'init', [ $this, 'register_meta' ] );
	}

	public function register_post_type(): void {
		register_post_type( self::SLUG, [
			'labels'       => [
				'name'          => __( 'Tanszékek', 'virtualis-akademia' ),
				'singular_name' => __( 'Tanszék', 'virtualis-akademia' ),
				'add_new_item'  => __( 'Új tanszék hozzáadása', 'virtualis-akademia' ),
				'edit_item'     => __( 'Tanszék szerkesztése', 'virtualis-akademia' ),
			],
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => true,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-align-center',
			'supports'     => [ 'title' ],
		] );
	}

	public function register_meta(): void {
		$string_fields = [
			'_va_minimap_position'  => '',
			'_va_passport_stamp_label' => '',
			'_va_noticeboard_type'  => 'none',
		];

		foreach ( $string_fields as $key => $default ) {
			register_post_meta( self::SLUG, $key, [
				'type'         => 'string',
				'single'       => true,
				'default'      => $default,
				'show_in_rest' => true,
				'auth_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
			] );
		}

		$integer_fields = [
			'_va_linked_course_id',
			'_va_panorama_image_id',
			'_va_ambient_audio_id',
			'_va_passport_stamp_icon_id',
			'_va_noticeboard_target_id',
		];

		foreach ( $integer_fields as $key ) {
			register_post_meta( self::SLUG, $key, [
				'type'         => 'integer',
				'single'       => true,
				'default'      => 0,
				'show_in_rest' => true,
				'auth_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
			] );
		}

		$float_fields = [
			'_va_corridor_hotspot_yaw',
			'_va_corridor_hotspot_pitch',
			'_va_return_yaw',
			'_va_return_pitch',
			'_va_noticeboard_yaw',
			'_va_noticeboard_pitch',
		];

		foreach ( $float_fields as $key ) {
			register_post_meta( self::SLUG, $key, [
				'type'         => 'number',
				'single'       => true,
				'default'      => 0,
				'show_in_rest' => true,
				'auth_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
			] );
		}
	}
	/**
	 * The department's short name as learners see it everywhere -- panel,
	 * header, passport, certificates ("Demokrácia"), not the room's
	 * decorative name ("Demokrácia könyvtárszoba").
	 */
	public static function label( $department ): string {
		$department = get_post( $department );

		if ( ! $department ) {
			return '';
		}

		$label = (string) get_post_meta( $department->ID, '_va_passport_stamp_label', true );

		return $label ?: html_entity_decode( get_the_title( $department ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	/**
	 * Which stamp motif belongs to the department (assets/src/metaverse/stamps.js):
	 * matched on the name, falling back to curriculum position.
	 */
	public static function stamp_icon( $department, int $position = 0 ): string {
		$name = mb_strtolower( self::label( $department ) );

		$by_name = [
			'demokr'  => 'scales',
			'érdek'   => 'megaphone',
			'európ'   => 'stars',
			' eu'     => 'stars',
			'civil'   => 'hands',
		];

		foreach ( $by_name as $needle => $icon ) {
			if ( str_contains( ' ' . $name, $needle ) ) {
				return $icon;
			}
		}

		return [ 'scales', 'megaphone', 'stars', 'hands' ][ max( 0, $position - 1 ) % 4 ];
	}
}
