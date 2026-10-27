<?php

namespace VirtualisAkademia\Admin;

/**
 * Renders a WP media-library picker button + live preview for an attachment
 * ID field, instead of asking an admin to type a raw numeric ID by hand --
 * the hidden input still carries the actual ID (and is still visible/
 * editable as text), so nothing about how the value is stored changes.
 */
class MediaPickerField {

	public static function render( string $field_id, string $label, int $attachment_id, string $type = 'image' ): void {
		$preview_url = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
		$filename    = $attachment_id ? basename( (string) get_attached_file( $attachment_id ) ) : '';

		echo '<div class="va-media-picker" data-type="' . esc_attr( $type ) . '">';
		echo '<p><label for="' . esc_attr( $field_id ) . '"><strong>' . esc_html( $label ) . '</strong></label></p>';

		echo '<div class="va-media-picker__preview">';
		if ( 'image' === $type && $preview_url ) {
			echo '<img src="' . esc_url( $preview_url ) . '" style="max-width:320px;height:auto;display:block;margin-bottom:8px;" />';
		} elseif ( $attachment_id ) {
			echo '<p class="va-media-picker__filename">' . esc_html( $filename ) . '</p>';
		}
		echo '</div>';

		printf(
			'<input type="number" class="regular-text va-media-picker__id" id="%1$s" name="%1$s" value="%2$s" />',
			esc_attr( $field_id ),
			esc_attr( $attachment_id ?: '' )
		);
		echo ' <button type="button" class="button va-media-picker__button">' .
			esc_html__( 'Média kiválasztása', 'virtualis-akademia' ) . '</button>';

		echo '</div>';
	}
}
