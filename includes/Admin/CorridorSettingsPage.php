<?php

namespace VirtualisAkademia\Admin;

use VirtualisAkademia\Hookable;
use VirtualisAkademia\PostTypes\DepartmentPostType;

/**
 * The corridor itself isn't a va_department post, so `va_corridor_settings`
 * (its panorama + ambient audio) had no admin UI at all until now -- it was
 * only ever set directly in the database. Needed as a prerequisite for
 * DepartmentMetaBox's own corridor-side door-hotspot picker, which has to
 * show *this* image, not the department's own.
 *
 * Also owns the EU-funding attribution popup text (shown from the HUD's EU
 * flag button) and the partner-recognition plaques placed around the
 * Átrium -- both editorial content an admin should be able to change
 * without touching code, and both only make sense scoped to this one shared
 * scene.
 */
class CorridorSettingsPage implements Hookable {

	private const OPTION       = 'va_corridor_settings';
	private const NONCE_ACTION = 'va_corridor_settings';
	private const NONCE_FIELD  = 'va_corridor_settings_nonce';

	// Fixed number of plaque slots rather than a JS add/remove repeater --
	// simpler to build and to reason about, and comfortably covers "at
	// least 3" with room to spare; an empty slot (no image) just isn't
	// rendered in the Átrium.
	private const PLAQUE_SLOTS = 6;

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_page' ] );
		add_action( 'admin_init', [ $this, 'maybe_save' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue_media' ] );
	}

	public function add_page(): void {
		add_submenu_page(
			'edit.php?post_type=' . DepartmentPostType::SLUG,
			__( 'Átrium beállításai', 'virtualis-akademia' ),
			__( 'Átrium', 'virtualis-akademia' ),
			'edit_posts',
			'va-corridor-settings',
			[ $this, 'render' ]
		);
	}

	public function maybe_enqueue_media( string $hook ): void {
		if ( 'va_department_page_va-corridor-settings' !== $hook ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'va-hotspot-picker', VA_PLUGIN_URL . 'assets/admin/hotspot-picker.css', [], VA_VERSION );
		wp_enqueue_script( 'va-hotspot-picker', VA_PLUGIN_URL . 'assets/admin/hotspot-picker.js', [], VA_VERSION, true );
		wp_enqueue_script( 'va-media-picker', VA_PLUGIN_URL . 'assets/admin/media-picker.js', [ 'jquery' ], VA_VERSION, true );
	}

	public function maybe_save(): void {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION )
		) {
			return;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$plaques = [];

		for ( $i = 1; $i <= self::PLAQUE_SLOTS; $i++ ) {
			$plaques[] = [
				'label'    => sanitize_text_field( wp_unslash( $_POST[ "plaque_{$i}_label" ] ?? '' ) ),
				'image_id' => absint( wp_unslash( $_POST[ "plaque_{$i}_image_id" ] ?? 0 ) ),
				'link'     => esc_url_raw( wp_unslash( $_POST[ "plaque_{$i}_link" ] ?? '' ) ),
				'yaw'      => (float) wp_unslash( $_POST[ "plaque_{$i}_yaw" ] ?? 0 ),
				'pitch'    => (float) wp_unslash( $_POST[ "plaque_{$i}_pitch" ] ?? 0 ),
			];
		}

		update_option( self::OPTION, [
			'panorama_image_id' => absint( wp_unslash( $_POST['panorama_image_id'] ?? 0 ) ),
			'audio_id'          => absint( wp_unslash( $_POST['audio_id'] ?? 0 ) ),
			'eu_info_text'      => wp_kses_post( wp_unslash( $_POST['eu_info_text'] ?? '' ) ),
			'plaques'           => $plaques,
		] );

		add_action( 'admin_notices', static function () {
			echo '<div class="notice notice-success is-dismissible"><p>' .
				esc_html__( 'Átrium beállításai elmentve.', 'virtualis-akademia' ) .
				'</p></div>';
		} );
	}

	/**
	 * Only the enabled (has an image) plaques, normalised for the frontend --
	 * used by SceneDataProvider so it doesn't need to know this option's
	 * internal shape or the "empty slot" convention.
	 */
	public static function get_plaques(): array {
		$settings = get_option( self::OPTION, [] );
		$plaques  = is_array( $settings['plaques'] ?? null ) ? $settings['plaques'] : [];

		$active = [];

		foreach ( $plaques as $plaque ) {
			if ( empty( $plaque['image_id'] ) ) {
				continue;
			}

			$active[] = [
				'label'    => (string) ( $plaque['label'] ?? '' ),
				// Full resolution (not WordPress' resized copy) in the metaverse.
				'imageUrl' => (string) ( wp_get_original_image_url( (int) $plaque['image_id'] ) ?: wp_get_attachment_url( (int) $plaque['image_id'] ) ),
				'link'     => (string) ( $plaque['link'] ?? '' ),
				'yaw'      => (float) ( $plaque['yaw'] ?? 0 ),
				'pitch'    => (float) ( $plaque['pitch'] ?? 0 ),
			];
		}

		return $active;
	}

	public static function get_eu_info_text(): string {
		$settings = get_option( self::OPTION, [] );

		return (string) ( $settings['eu_info_text'] ?? '' );
	}

	public function render(): void {
		$settings    = get_option( self::OPTION, [] );
		$panorama_id = (int) ( $settings['panorama_image_id'] ?? 0 );
		$audio_id    = (int) ( $settings['audio_id'] ?? 0 );
		$eu_text     = (string) ( $settings['eu_info_text'] ?? self::default_eu_info_text() );
		$plaques     = is_array( $settings['plaques'] ?? null ) ? $settings['plaques'] : [];

		echo '<div class="wrap"><h1>' . esc_html__( 'Átrium beállításai', 'virtualis-akademia' ) . '</h1>';
		echo '<form method="post">';
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		MediaPickerField::render(
			'panorama_image_id',
			__( 'Átrium panoráma kép', 'virtualis-akademia' ),
			$panorama_id
		);

		MediaPickerField::render(
			'audio_id',
			__( 'Ambient hang', 'virtualis-akademia' ),
			$audio_id,
			'audio'
		);

		echo '<h2>' . esc_html__( 'EU-s tájékoztató szöveg', 'virtualis-akademia' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Az EU kötelező tájékoztató szövege: a 3D nézet jobb alsó eszköztárának EU-zászló gombjára („A projektről”) felugró ablakban jelenik meg, a logó alatt. A projekt adatai (cím, azonosító, időszak, támogatás, partnerek) mellette, rögzítetten szerepelnek.', 'virtualis-akademia' ) . '</p>';
		printf(
			'<textarea name="eu_info_text" id="eu_info_text" class="large-text" rows="6">%s</textarea>',
			esc_textarea( $eu_text )
		);

		echo '<h2>' . esc_html__( 'Partner plakettek az Átriumban', 'virtualis-akademia' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Legalább 3 üres helyet érdemes kitölteni – egy plakett csak akkor jelenik meg az Átriumban, ha van hozzá kép beállítva.', 'virtualis-akademia' ) . '</p>';

		$points = [];

		for ( $i = 1; $i <= self::PLAQUE_SLOTS; $i++ ) {
			$points[] = [
				'yawField'   => "plaque_{$i}_yaw",
				'pitchField' => "plaque_{$i}_pitch",
				'label'      => sprintf( __( '%d. plakett', 'virtualis-akademia' ), $i ),
				'color'      => self::plaque_color( $i ),
			];
		}

		if ( $panorama_id ) {
			HotspotPickerField::render( $panorama_id, $points );
		}

		foreach ( range( 1, self::PLAQUE_SLOTS ) as $i ) {
			$plaque = $plaques[ $i - 1 ] ?? [];

			echo '<div class="va-corridor-plaque" style="border:1px solid #ccd0d4;padding:12px 16px;margin:12px 0;background:#fff;">';
			echo '<h3>' . sprintf( esc_html__( '%d. plakett', 'virtualis-akademia' ), $i ) . '</h3>';

			printf(
				'<p><label for="plaque_%1$d_label"><strong>%2$s</strong></label><br /><input type="text" class="regular-text" id="plaque_%1$d_label" name="plaque_%1$d_label" value="%3$s" placeholder="%4$s" /></p>',
				$i,
				esc_html__( 'Partner neve', 'virtualis-akademia' ),
				esc_attr( $plaque['label'] ?? '' ),
				esc_attr__( 'pl. Hook Alapítvány', 'virtualis-akademia' )
			);

			MediaPickerField::render(
				"plaque_{$i}_image_id",
				__( 'Plakett képe / logó', 'virtualis-akademia' ),
				(int) ( $plaque['image_id'] ?? 0 )
			);

			printf(
				'<p><label for="plaque_%1$d_link"><strong>%2$s</strong></label><br /><input type="url" class="regular-text" id="plaque_%1$d_link" name="plaque_%1$d_link" value="%3$s" placeholder="https://" /></p>',
				$i,
				esc_html__( 'Hivatkozás (opcionális)', 'virtualis-akademia' ),
				esc_attr( $plaque['link'] ?? '' )
			);

			printf(
				'<p><label for="plaque_%1$d_yaw">%2$s</label> <input type="text" class="small-text" id="plaque_%1$d_yaw" name="plaque_%1$d_yaw" value="%3$s" /> &nbsp; <label for="plaque_%1$d_pitch">%4$s</label> <input type="text" class="small-text" id="plaque_%1$d_pitch" name="plaque_%1$d_pitch" value="%5$s" /></p>',
				$i,
				esc_html__( 'Yaw', 'virtualis-akademia' ),
				esc_attr( $plaque['yaw'] ?? '0' ),
				esc_html__( 'Pitch', 'virtualis-akademia' ),
				esc_attr( $plaque['pitch'] ?? '0' )
			);

			echo '</div>';
		}

		submit_button( __( 'Mentés', 'virtualis-akademia' ) );
		echo '</form></div>';
	}

	private static function plaque_color( int $index ): string {
		$palette = [ '#00dbe9', '#fecb00', '#b5c4ff', '#ff8a65', '#a5d6a7', '#ce93d8' ];

		return $palette[ ( $index - 1 ) % count( $palette ) ];
	}

	private static function default_eu_info_text(): string {
		return \VirtualisAkademia\Support\ProjectInfo::DISCLAIMER;
	}
}
