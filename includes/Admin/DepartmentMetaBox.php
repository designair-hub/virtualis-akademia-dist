<?php

namespace VirtualisAkademia\Admin;

use VirtualisAkademia\Hookable;
use VirtualisAkademia\PostTypes\DepartmentPostType;

class DepartmentMetaBox implements Hookable {

	private const NONCE_ACTION = 'va_department_meta_box';
	private const NONCE_FIELD  = 'va_department_meta_box_nonce';

	public function register(): void {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'save_post_' . DepartmentPostType::SLUG, [ $this, 'save' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue' ] );
	}

	public function maybe_enqueue( string $hook ): void {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		global $post_type;

		if ( DepartmentPostType::SLUG !== $post_type ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script( 'va-media-picker', VA_PLUGIN_URL . 'assets/admin/media-picker.js', [ 'jquery' ], VA_VERSION, true );
	}

	public function add_meta_box(): void {
		add_meta_box(
			'va_department_settings',
			__( 'Tanszék beállításai', 'virtualis-akademia' ),
			[ $this, 'render' ],
			DepartmentPostType::SLUG,
			'normal',
			'high'
		);
	}

	public function render( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$panorama_id = (int) get_post_meta( $post->ID, '_va_panorama_image_id', true );

		echo '<h3>' . esc_html__( 'Alapadatok', 'virtualis-akademia' ) . '</h3>';
		echo '<table class="form-table"><tbody>';
		$this->text_row( $post, '_va_linked_course_id', __( 'LearnDash kurzus ID', 'virtualis-akademia' ) );
		echo '</tbody></table>';

		MediaPickerField::render(
			'_va_panorama_image_id',
			__( 'Panoráma kép', 'virtualis-akademia' ),
			$panorama_id
		);

		MediaPickerField::render(
			'_va_ambient_audio_id',
			__( 'Ambient hang', 'virtualis-akademia' ),
			(int) get_post_meta( $post->ID, '_va_ambient_audio_id', true ),
			'audio'
		);

		echo '<h3>' . esc_html__( 'Belépési pont az Átriumból (Átrium nézetben)', 'virtualis-akademia' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'Kattints az Átrium panorámaképén arra a pontra, ahol ennek a tanszéknek az ajtaja látszódik.', 'virtualis-akademia' ) . '</p>';

		$corridor_settings  = get_option( 'va_corridor_settings', [] );
		$corridor_image_id  = (int) ( $corridor_settings['panorama_image_id'] ?? 0 );

		HotspotPickerField::render( $corridor_image_id, [
			[
				'yawField'   => '_va_corridor_hotspot_yaw',
				'pitchField' => '_va_corridor_hotspot_pitch',
				'label'      => __( 'Ajtó az Átriumban', 'virtualis-akademia' ),
				'color'      => '#2271b1',
			],
		] );

		if ( ! $corridor_image_id ) {
			printf(
				'<p class="description">%s <a href="%s">%s</a></p>',
				esc_html__( 'Az Átrium panorámaképe még nincs beállítva.', 'virtualis-akademia' ),
				esc_url( admin_url( 'edit.php?post_type=' . DepartmentPostType::SLUG . '&page=va-corridor-settings' ) ),
				esc_html__( 'Beállítás itt', 'virtualis-akademia' )
			);
		}

		echo '<table class="form-table"><tbody>';
		$this->text_row( $post, '_va_corridor_hotspot_yaw', __( 'Yaw (Átriumban)', 'virtualis-akademia' ) );
		$this->text_row( $post, '_va_corridor_hotspot_pitch', __( 'Pitch (Átriumban)', 'virtualis-akademia' ) );
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Pontok a tanszék saját panorámaképén', 'virtualis-akademia' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'Válaszd ki lent, melyik pontot helyezed el, majd kattints a képre.', 'virtualis-akademia' ) . '</p>';

		HotspotPickerField::render( $panorama_id, [
			[
				'yawField'   => '_va_return_yaw',
				'pitchField' => '_va_return_pitch',
				'label'      => __( 'Belépési pont (vissza az Átriumba)', 'virtualis-akademia' ),
				'color'      => '#2271b1',
			],
		] );

		echo '<table class="form-table"><tbody>';
		$this->text_row( $post, '_va_return_yaw', __( 'Belépési pont yaw', 'virtualis-akademia' ) );
		$this->text_row( $post, '_va_return_pitch', __( 'Belépési pont pitch', 'virtualis-akademia' ) );
		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Egyéb', 'virtualis-akademia' ) . '</h3>';
		echo '<table class="form-table"><tbody>';
		$this->text_row( $post, '_va_minimap_position', __( 'Minimap pozíció (JSON: {"x":0,"y":0})', 'virtualis-akademia' ) );
		$this->text_row( $post, '_va_passport_stamp_icon_id', __( 'Útlevél pecsét ikon (média ID)', 'virtualis-akademia' ) );
		$this->text_row( $post, '_va_passport_stamp_label', __( 'Útlevél pecsét felirata', 'virtualis-akademia' ) );
		echo '</tbody></table>';
	}

	private function text_row( \WP_Post $post, string $key, string $label ): void {
		$value = get_post_meta( $post->ID, $key, true );
		printf(
			'<tr><th><label for="%1$s">%2$s</label></th><td><input type="text" class="regular-text" id="%1$s" name="%1$s" value="%3$s" /></td></tr>',
			esc_attr( $key ),
			esc_html( $label ),
			esc_attr( is_scalar( $value ) ? $value : '' )
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

		$string_keys  = [ '_va_minimap_position', '_va_passport_stamp_label' ];
		$integer_keys = [
			'_va_linked_course_id',
			'_va_panorama_image_id',
			'_va_ambient_audio_id',
			'_va_passport_stamp_icon_id',
		];
		$float_keys = [
			'_va_corridor_hotspot_yaw',
			'_va_corridor_hotspot_pitch',
			'_va_return_yaw',
			'_va_return_pitch',
		];

		foreach ( $string_keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
			}
		}

		foreach ( $integer_keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, absint( wp_unslash( $_POST[ $key ] ) ) );
			}
		}

		foreach ( $float_keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, (float) wp_unslash( $_POST[ $key ] ) );
			}
		}
	}
}
