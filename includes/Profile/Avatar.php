<?php

namespace VirtualisAkademia\Profile;

use VirtualisAkademia\Hookable;

/**
 * Member-uploaded profile pictures, with strict limits:
 *
 * - JPG, PNG or WebP only -- detected from the file's bytes, not its name
 *   or the browser's claim;
 * - at most 2 MB, at least 200×200 px, at most 6000 px a side / 24 MP;
 * - never stored as uploaded: decoded, EXIF-rotated, centre-cropped to a
 *   400×400 square and re-encoded (WebP, JPEG fallback). That drops every
 *   byte of the original -- metadata (GPS!), embedded payloads, animation;
 * - at most 5 uploads an hour per member;
 * - one picture per member: a new upload or "Eltávolítás" deletes the old
 *   file, and so does deleting the account.
 *
 * Files live in uploads/va-avatars/ under an unguessable name; the URL is
 * handed to Ultimate Member (and WordPress' get_avatar_url), so the same
 * picture shows in the metaverse, on the account page and in the admin.
 */
class Avatar implements Hookable {

	public const MAX_BYTES      = 2 * 1024 * 1024;
	public const MIN_SIDE       = 200;
	public const MAX_SIDE       = 6000;
	public const MAX_PIXELS     = 24000000;
	public const OUTPUT_SIDE    = 400;
	public const RATE_LIMIT     = 5;
	public const RATE_WINDOW    = HOUR_IN_SECONDS;
	public const ALLOWED_MIMES  = [ 'image/jpeg', 'image/png', 'image/webp' ];

	private const META        = '_va_avatar';
	private const UPLOADS_LOG = '_va_avatar_uploads';
	private const DIR         = 'va-avatars';

	public function register(): void {
		add_filter( 'um_user_avatar_url_filter', [ $this, 'filter_um_url' ], 10, 2 );
		add_filter( 'get_avatar_url', [ $this, 'filter_wp_url' ], 30, 2 );
		add_action( 'deleted_user', [ self::class, 'remove' ] );
	}

	public function filter_um_url( $url, $user_id ) {
		return self::url( (int) $user_id ) ?: $url;
	}

	public function filter_wp_url( $url, $id_or_email ) {
		$user_id = 0;

		if ( is_numeric( $id_or_email ) ) {
			$user_id = (int) $id_or_email;
		} elseif ( $id_or_email instanceof \WP_User ) {
			$user_id = $id_or_email->ID;
		} elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
			$user    = get_user_by( 'email', $id_or_email );
			$user_id = $user ? $user->ID : 0;
		}

		return ( $user_id ? self::url( $user_id ) : '' ) ?: $url;
	}

	/** The member's own picture, or '' when they have none. */
	public static function url( int $user_id ): string {
		$meta = get_user_meta( $user_id, self::META, true );

		if ( ! is_array( $meta ) || empty( $meta['file'] ) ) {
			return '';
		}

		$uploads = wp_upload_dir( null, false );

		return $uploads['baseurl'] . '/' . self::DIR . '/' . rawurlencode( $meta['file'] );
	}

	/**
	 * Validates and stores an upload ($_FILES entry). Returns the new URL.
	 *
	 * @param array{name?: string, tmp_name?: string, size?: int, error?: int} $file
	 * @return string|\WP_Error
	 */
	public static function store( int $user_id, array $file ) {
		$error = (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE );

		if ( UPLOAD_ERR_INI_SIZE === $error || UPLOAD_ERR_FORM_SIZE === $error ) {
			return self::error( 'too_big', self::too_big_message(), 413 );
		}

		$tmp = (string) ( $file['tmp_name'] ?? '' );

		if ( UPLOAD_ERR_OK !== $error || '' === $tmp || ! is_uploaded_file( $tmp ) ) {
			return self::error( 'no_file', __( 'Nem érkezett kép. Válassz egy fájlt, és próbáld újra.', 'virtualis-akademia' ) );
		}

		if ( ! self::within_rate_limit( $user_id ) ) {
			return self::error( 'rate_limited', __( 'Egy órán belül legfeljebb 5 alkalommal cserélheted a profilképedet. Próbáld újra később.', 'virtualis-akademia' ), 429 );
		}

		if ( filesize( $tmp ) > self::MAX_BYTES ) {
			return self::error( 'too_big', self::too_big_message(), 413 );
		}

		// The real type, from the file's magic bytes.
		$mime = wp_get_image_mime( $tmp );

		if ( ! in_array( $mime, self::ALLOWED_MIMES, true ) ) {
			return self::error( 'bad_type', __( 'Csak JPG, PNG vagy WebP képet tölthetsz fel.', 'virtualis-akademia' ), 415 );
		}

		$size = @getimagesize( $tmp ); // phpcs:ignore -- a broken file is reported below.

		if ( ! $size || empty( $size[0] ) || empty( $size[1] ) ) {
			return self::error( 'broken', __( 'Ezt a képet nem sikerült beolvasni. Próbálkozz egy másikkal.', 'virtualis-akademia' ) );
		}

		[ $width, $height ] = $size;

		if ( $width < self::MIN_SIDE || $height < self::MIN_SIDE ) {
			/* translators: %d: minimum side length in pixels */
			return self::error( 'too_small', sprintf( __( 'A kép túl kicsi: legalább %1$d×%1$d képpontos legyen.', 'virtualis-akademia' ), self::MIN_SIDE ) );
		}

		if ( $width > self::MAX_SIDE || $height > self::MAX_SIDE || $width * $height > self::MAX_PIXELS ) {
			/* translators: %d: maximum side length in pixels */
			return self::error( 'too_large', sprintf( __( 'A kép felbontása túl nagy: legfeljebb %d képpont lehet egy oldala.', 'virtualis-akademia' ), self::MAX_SIDE ) );
		}

		self::log_upload( $user_id );

		$editor = wp_get_image_editor( $tmp );

		if ( is_wp_error( $editor ) ) {
			return self::error( 'broken', __( 'Ezt a képet nem sikerült beolvasni. Próbálkozz egy másikkal.', 'virtualis-akademia' ) );
		}

		if ( method_exists( $editor, 'maybe_exif_rotate' ) ) {
			$editor->maybe_exif_rotate();
		}

		$resized = $editor->resize( self::OUTPUT_SIDE, self::OUTPUT_SIDE, true );

		if ( is_wp_error( $resized ) ) {
			return self::error( 'broken', __( 'Ezt a képet nem sikerült feldolgozni. Próbálkozz egy másikkal.', 'virtualis-akademia' ) );
		}

		$editor->set_quality( 82 );

		$dir = self::dir();

		if ( ! $dir ) {
			return self::error( 'storage', __( 'A profilkép mentése most nem sikerült. Próbáld újra később.', 'virtualis-akademia' ), 500 );
		}

		$webp      = wp_image_editor_supports( [ 'mime_type' => 'image/webp' ] );
		$extension = $webp ? 'webp' : 'jpg';
		$filename  = $user_id . '-' . wp_generate_password( 16, false, false ) . '.' . $extension;
		$saved     = $editor->save( $dir . '/' . $filename, $webp ? 'image/webp' : 'image/jpeg' );

		if ( is_wp_error( $saved ) || empty( $saved['path'] ) ) {
			return self::error( 'storage', __( 'A profilkép mentése most nem sikerült. Próbáld újra később.', 'virtualis-akademia' ), 500 );
		}

		self::delete_file( $user_id );
		update_user_meta( $user_id, self::META, [
			'file' => basename( $saved['path'] ),
			'time' => time(),
		] );

		return self::url( $user_id );
	}

	/** Deletes the member's picture (file + record). */
	public static function remove( $user_id ): void {
		self::delete_file( (int) $user_id );
		delete_user_meta( (int) $user_id, self::META );
	}

	/** The limits, for the client-side pre-check and the help text. */
	public static function limits(): array {
		return [
			'maxBytes' => self::MAX_BYTES,
			'minSide'  => self::MIN_SIDE,
			'maxSide'  => self::MAX_SIDE,
			'types'    => self::ALLOWED_MIMES,
		];
	}

	private static function delete_file( int $user_id ): void {
		$meta = get_user_meta( $user_id, self::META, true );
		$dir  = self::dir( false );

		if ( ! is_array( $meta ) || empty( $meta['file'] ) || ! $dir ) {
			return;
		}

		$path = $dir . '/' . basename( (string) $meta['file'] );

		if ( is_file( $path ) ) {
			wp_delete_file( $path );
		}
	}

	private static function dir( bool $create = true ): string {
		$uploads = wp_upload_dir( null, false );

		if ( ! empty( $uploads['error'] ) ) {
			return '';
		}

		$dir = $uploads['basedir'] . '/' . self::DIR;

		if ( $create && ! is_dir( $dir ) ) {
			if ( ! wp_mkdir_p( $dir ) ) {
				return '';
			}

			// No directory listing.
			file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore
		}

		return $dir;
	}

	private static function within_rate_limit( int $user_id ): bool {
		return count( self::recent_uploads( $user_id ) ) < self::RATE_LIMIT;
	}

	private static function log_upload( int $user_id ): void {
		$log   = self::recent_uploads( $user_id );
		$log[] = time();

		update_user_meta( $user_id, self::UPLOADS_LOG, $log );
	}

	/** @return int[] Upload timestamps inside the rate window. */
	private static function recent_uploads( int $user_id ): array {
		$log = get_user_meta( $user_id, self::UPLOADS_LOG, true );

		return array_values( array_filter(
			is_array( $log ) ? array_map( 'intval', $log ) : [],
			static fn( int $time ) => $time > time() - self::RATE_WINDOW
		) );
	}

	private static function too_big_message(): string {
		return __( 'A kép túl nagy: legfeljebb 2 MB-os fájlt tölthetsz fel.', 'virtualis-akademia' );
	}

	private static function error( string $code, string $message, int $status = 400 ): \WP_Error {
		return new \WP_Error( 'va_avatar_' . $code, $message, [ 'status' => $status ] );
	}
}
