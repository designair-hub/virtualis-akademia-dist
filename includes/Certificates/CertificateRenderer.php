<?php

namespace VirtualisAkademia\Certificates;

/**
 * Draws the certificate PDF in the product's own "Aetherium Academy" look
 * (assets/src/styles/_tokens.scss): deep EU-blue ground with a soft blue /
 * cyan glow, a gold frame with cyan HUD corner brackets, the ring of twelve
 * EU stars, Montserrat / Inter / JetBrains Mono type. The partner logos are
 * dark artwork, so they sit on a light band along the bottom edge.
 *
 * Uses the TCPDF library LearnDash already bundles; the fonts are shipped
 * pre-converted in assets/fonts/tcpdf (see the README there).
 */
class CertificateRenderer {

	private const W = 297;
	private const H = 210;

	private const SURFACE  = [ 11, 19, 38 ];
	private const SURFACE2 = [ 6, 14, 32 ];
	private const EU_BLUE  = [ 0, 51, 153 ];
	private const GOLD     = [ 254, 203, 0 ];
	private const GOLD_SOFT = [ 255, 236, 192 ];
	private const CYAN     = [ 0, 219, 233 ];
	private const TEXT     = [ 218, 226, 253 ];
	private const TEXT_DIM = [ 196, 197, 213 ];
	private const MUTED    = [ 154, 160, 180 ];

	private \TCPDF $pdf;

	/**
	 * @param array<string, mixed> $view See CertificateService::build_view().
	 */
	public function render( array $view ): string {
		$this->load_library();

		// No hidden "Powered by TCPDF" link on the certificate.
		$pdf = new class( 'L', 'mm', 'A4', true, 'UTF-8', false ) extends \TCPDF {
			public function hide_credit_link(): void {
				$this->tcpdflink = false; // Set by TCPDF's constructor.
			}
		};
		$pdf->hide_credit_link();
		$pdf->SetCreator( 'Virtuális Akadémia' );
		$pdf->SetAuthor( 'Virtuális Akadémia' );
		$pdf->SetTitle( 'master' === $view['type'] ? 'Mester Oklevél' : 'Oklevél – ' . $view['course'] );
		$pdf->setPrintHeader( false );
		$pdf->setPrintFooter( false );
		$pdf->SetMargins( 0, 0, 0 );
		$pdf->SetAutoPageBreak( false, 0 );
		$pdf->setCellPaddings( 0, 0, 0, 0 );

		$font_dir = VA_PLUGIN_DIR . '/assets/fonts/tcpdf/';

		foreach ( [ 'montserratextrab', 'montserratb', 'inter', 'intersemib', 'jetbrainsmonomedium' ] as $font ) {
			$pdf->AddFont( $font, '', $font_dir . $font . '.php' );
		}

		$pdf->AddPage();
		$this->pdf = $pdf;

		$this->background();
		$this->frame();
		$this->header_block();
		$this->body( $view );
		$this->footer( $view );

		return $pdf->Output( 'oklevel.pdf', 'S' );
	}

	private function load_library(): void {
		if ( class_exists( '\TCPDF' ) ) {
			return;
		}

		$path = defined( 'LEARNDASH_LMS_LIBRARY_DIR' )
			? LEARNDASH_LMS_LIBRARY_DIR . '/tcpdf/tcpdf.php'
			: WP_PLUGIN_DIR . '/sfwd-lms/includes/lib/tcpdf/tcpdf.php';

		require_once $path;
	}

	// --- Layers ----------------------------------------------------------------

	private function background(): void {
		$pdf = $this->pdf;

		$pdf->Rect( 0, 0, self::W, self::H, 'F', [], self::SURFACE );
		// Soft EU-blue glow top-left, faint cyan bottom-right -- the same two
		// radial washes the lesson pages use.
		$pdf->RadialGradient( 0, 0, self::W, self::H, [ 18, 48, 120 ], self::SURFACE, [ 0.12, 1.0, 0.12, 1.0, 0.75 ] );
		$pdf->SetAlpha( 0.55 );
		$pdf->RadialGradient( 0, 0, self::W, self::H, [ 4, 60, 78 ], self::SURFACE, [ 1.0, 0.0, 1.0, 0.0, 0.6 ] );
		$pdf->SetAlpha( 1 );
	}

	private function frame(): void {
		$pdf = $this->pdf;

		// Gold outer frame.
		$pdf->RoundedRect( 8, 8, self::W - 16, self::H - 16, 4, '1111', 'D', [ 'width' => 0.7, 'color' => self::GOLD ] );

		// Thin cyan inner line.
		$pdf->SetAlpha( 0.45 );
		$pdf->RoundedRect( 11.5, 11.5, self::W - 23, self::H - 23, 3, '1111', 'D', [ 'width' => 0.25, 'color' => self::CYAN ] );
		$pdf->SetAlpha( 1 );

		// HUD corner brackets.
		$style = [ 'width' => 0.9, 'color' => self::CYAN, 'cap' => 'square' ];
		$len   = 12;
		$in    = 15;

		foreach ( [ [ $in, $in, 1, 1 ], [ self::W - $in, $in, -1, 1 ], [ $in, self::H - $in, 1, -1 ], [ self::W - $in, self::H - $in, -1, -1 ] ] as [ $x, $y, $dx, $dy ] ) {
			$pdf->Line( $x, $y, $x + $dx * $len, $y, $style );
			$pdf->Line( $x, $y, $x, $y + $dy * $len, $style );
		}
	}

	private function header_block(): void {
		$pdf = $this->pdf;

		$pdf->SetTextColorArray( self::GOLD );
		$pdf->SetFont( 'jetbrainsmonomedium', '', 9 );
		$pdf->setFontSpacing( 0.9 );
		$pdf->SetXY( 0, 21 );
		$pdf->Cell( self::W, 5, 'VIRTUÁLIS AKADÉMIA', 0, 1, 'C' );
		$pdf->setFontSpacing( 0 );

		$pdf->SetTextColorArray( self::MUTED );
		$pdf->SetFont( 'inter', '', 8.5 );
		$pdf->SetXY( 0, 26.5 );
		$pdf->Cell( self::W, 5, 'a demokráciára nevelés és a polgári szerepvállalás érdekében', 0, 1, 'C' );

		$this->star_seal( self::W / 2, 47 );
	}

	/**
	 * Twelve gold stars in a ring around an EU-blue disc with the site's
	 * icon (favicon) in the middle -- the "VA" monogram only if there's none.
	 */
	private function star_seal( float $cx, float $cy ): void {
		$pdf = $this->pdf;

		$pdf->SetAlpha( 0.35 );
		$pdf->Circle( $cx, $cy, 14.5, 0, 360, 'F', [], self::EU_BLUE );
		$pdf->SetAlpha( 1 );
		$pdf->Circle( $cx, $cy, 14.5, 0, 360, 'D', [ 'width' => 0.35, 'color' => self::CYAN ] );

		for ( $i = 0; $i < 12; $i++ ) {
			$angle = deg2rad( $i * 30 - 90 );
			$pdf->StarPolygon( $cx + 10.5 * cos( $angle ), $cy + 10.5 * sin( $angle ), 1.35, 5, 2, 0, false, 'F', [], self::GOLD );
		}

		$icon = $this->seal_icon();

		if ( $icon ) {
			$pdf->Image( $icon, $cx - 5.5, $cy - 5.5, 11, 11, 'PNG' );
			return;
		}

		$pdf->SetTextColorArray( self::GOLD_SOFT );
		$pdf->SetFont( 'montserratextrab', '', 11 );
		$pdf->SetXY( $cx - 10, $cy - 2.9 );
		$pdf->Cell( 20, 6, 'VA', 0, 0, 'C' );
	}

	/**
	 * The site icon (Settings → General → Site Icon), recoloured to the
	 * seal's soft gold -- icons are usually drawn black on transparent, which
	 * would vanish on the blue disc. The shape is kept from the alpha channel
	 * (or, for an opaque icon, from its darkness). Cached in uploads.
	 */
	private function seal_icon(): string {
		$icon_id = (int) get_option( 'site_icon' );
		$source  = $icon_id ? ( wp_get_original_image_path( $icon_id ) ?: get_attached_file( $icon_id ) ) : '';

		if ( ! $source || ! is_readable( $source ) || ! function_exists( 'imagecreatefromstring' ) ) {
			return '';
		}

		$uploads = wp_upload_dir( null, false );
		$dir     = $uploads['basedir'] . '/va-certificates';
		$target  = $dir . '/seal-' . $icon_id . '-' . filemtime( $source ) . '.png';

		if ( is_readable( $target ) ) {
			return $target;
		}

		$image = @imagecreatefromstring( (string) file_get_contents( $source ) ); // phpcs:ignore

		if ( ! $image || ! wp_mkdir_p( $dir ) ) {
			return '';
		}

		$size = 256;
		$src  = imagecreatetruecolor( $size, $size );
		imagealphablending( $src, false );
		imagesavealpha( $src, true );
		imagefill( $src, 0, 0, imagecolorallocatealpha( $src, 0, 0, 0, 127 ) );
		imagecopyresampled( $src, $image, 0, 0, 0, 0, $size, $size, imagesx( $image ), imagesy( $image ) );

		$out = imagecreatetruecolor( $size, $size );
		imagealphablending( $out, false );
		imagesavealpha( $out, true );

		[ $r, $g, $b ] = self::GOLD_SOFT;

		for ( $y = 0; $y < $size; $y++ ) {
			for ( $x = 0; $x < $size; $x++ ) {
				$pixel = imagecolorat( $src, $x, $y );
				$alpha = ( $pixel >> 24 ) & 0x7F; // 0 opaque … 127 transparent.
				$light = ( ( ( $pixel >> 16 ) & 0xFF ) + ( ( $pixel >> 8 ) & 0xFF ) + ( $pixel & 0xFF ) ) / 765;

				// Opaque light background (icon without transparency): drop it.
				$coverage = ( 127 - $alpha ) / 127 * ( 1 - $light * 0.95 );
				imagesetpixel( $out, $x, $y, imagecolorallocatealpha( $out, $r, $g, $b, (int) round( 127 - $coverage * 127 ) ) );
			}
		}

		imagepng( $out, $target );

		foreach ( glob( $dir . '/seal-*.png' ) ?: [] as $old ) {
			if ( $old !== $target ) {
				wp_delete_file( $old );
			}
		}

		return is_readable( $target ) ? $target : '';
	}

	/**
	 * @param array<string, mixed> $view
	 */
	private function body( array $view ): void {
		$pdf       = $this->pdf;
		$is_master = 'master' === $view['type'];

		// Title.
		$pdf->SetTextColorArray( self::TEXT );
		$pdf->SetFont( 'montserratextrab', '', $is_master ? 36 : 40 );
		$pdf->setFontSpacing( 1.6 );
		$pdf->SetXY( 0, 65 );
		$pdf->Cell( self::W, 16, $is_master ? 'MESTER OKLEVÉL' : 'OKLEVÉL', 0, 1, 'C' );
		$pdf->setFontSpacing( 0 );

		$pdf->SetTextColorArray( self::TEXT_DIM );
		$pdf->SetFont( 'inter', '', 12 );
		$pdf->SetXY( 0, 85 );
		$pdf->Cell( self::W, 6, 'Ezúton igazoljuk, hogy', 0, 1, 'C' );

		// Name.
		$pdf->SetTextColorArray( self::GOLD );
		$pdf->SetFont( 'montserratb', '', $this->fit_size( (string) $view['name'], 'montserratb', 30, 200 ) );
		$pdf->SetXY( 0, 93 );
		$pdf->Cell( self::W, 13, (string) $view['name'], 0, 1, 'C' );

		$pdf->SetAlpha( 0.6 );
		$pdf->Line( self::W / 2 - 55, 108.5, self::W / 2 + 55, 108.5, [ 'width' => 0.3, 'color' => self::CYAN ] );
		$pdf->SetAlpha( 1 );

		if ( $is_master ) {
			$this->master_text( $view );
		} else {
			$this->course_text( $view );
		}
	}

	/**
	 * @param array<string, mixed> $view
	 */
	private function course_text( array $view ): void {
		$pdf = $this->pdf;

		$pdf->SetTextColorArray( self::TEXT_DIM );
		$pdf->SetFont( 'inter', '', 12 );
		$pdf->SetXY( 0, 113 );
		$pdf->Cell( self::W, 6, 'sikeresen teljesítette a Virtuális Akadémia', 0, 1, 'C' );

		// The department's name only -- not the room's ("Civil közösségi tér").
		$pdf->SetTextColorArray( self::TEXT );
		$pdf->SetFont( 'montserratb', '', $this->fit_size( (string) $view['course'], 'montserratb', 22, 220 ) );
		$pdf->SetXY( 0, 123.5 );
		$pdf->Cell( self::W, 10, (string) $view['course'], 0, 1, 'C' );

		$pdf->SetTextColorArray( self::TEXT_DIM );
		$pdf->SetFont( 'inter', '', 12 );
		$pdf->SetXY( 0, 135.5 );
		$pdf->Cell( self::W, 6, 'tanszékének teljes tananyagát.', 0, 1, 'C' );
	}

	/**
	 * @param array<string, mixed> $view
	 */
	private function master_text( array $view ): void {
		$pdf = $this->pdf;

		$pdf->SetTextColorArray( self::TEXT_DIM );
		$pdf->SetFont( 'inter', '', 12 );
		$pdf->SetXY( 30, 113 );
		$count = count( (array) $view['departments'] );
		$words = [ 2 => 'két', 3 => 'három', 4 => 'négy', 5 => 'öt', 6 => 'hat' ];
		$pdf->MultiCell( self::W - 60, 6, 'sikeresen teljesítette a Virtuális Akadémia mind a ' . ( $words[ $count ] ?? (string) $count ) . ' tanszékének tananyagát.', 0, 'C' );

		$pdf->SetTextColorArray( self::CYAN );
		$pdf->SetFont( 'jetbrainsmonomedium', '', 9.5 );
		$pdf->setFontSpacing( 0.4 );
		$pdf->SetXY( 20, 124 );
		$pdf->MultiCell( self::W - 40, 6, mb_strtoupper( implode( '   ·   ', (array) $view['departments'] ) ), 0, 'C' );
		$pdf->setFontSpacing( 0 );
	}

	/**
	 * @param array<string, mixed> $view
	 */
	private function footer( array $view ): void {
		$pdf = $this->pdf;

		// Date (left) and serial (right) above the partner band.
		$pdf->SetFont( 'jetbrainsmonomedium', '', 8 );
		$pdf->setFontSpacing( 0.3 );
		$pdf->SetTextColorArray( self::MUTED );
		$pdf->SetXY( 24, 152 );
		$pdf->Cell( 100, 5, 'KELT', 0, 0, 'L' );
		$pdf->SetXY( self::W - 124, 152 );
		$pdf->Cell( 100, 5, 'OKLEVÉL-AZONOSÍTÓ', 0, 0, 'R' );
		$pdf->setFontSpacing( 0 );

		$pdf->SetTextColorArray( self::TEXT );
		$pdf->SetFont( 'intersemib', '', 11 );
		$pdf->SetXY( 24, 157 );
		$pdf->Cell( 100, 6, (string) $view['date'], 0, 0, 'L' );
		$pdf->SetXY( self::W - 124, 157 );
		$pdf->Cell( 100, 6, (string) $view['serial'], 0, 0, 'R' );

		$pdf->Line( 24, 164.5, 84, 164.5, [ 'width' => 0.2, 'color' => self::GOLD ] );
		$pdf->Line( self::W - 84, 164.5, self::W - 24, 164.5, [ 'width' => 0.2, 'color' => self::GOLD ] );

		// Light partner band.
		// Inside the corner brackets; pure white so the logos' own (opaque,
		// when TCPDF can't honour PNG alpha) white ground blends in.
		$band_y = 169;
		$band_h = 24;
		$pdf->RoundedRect( 32, $band_y, self::W - 64, $band_h, 3, '1111', 'F', [], [ 255, 255, 255 ] );

		$logos  = array_values( array_filter( (array) $view['logos'], 'is_readable' ) );
		$slot_w = 56;
		$gap    = 10;
		$total  = count( $logos ) * $slot_w + max( 0, count( $logos ) - 1 ) * $gap;
		$x      = ( self::W - $total ) / 2;

		foreach ( $logos as $logo ) {
			if ( str_ends_with( $logo, '.svg' ) ) {
				// Vector EU funding logo (826×201): fit the 11 mm slot height, centred.
				$w = min( $slot_w, 11 * 826 / 201 );
				$pdf->ImageSVG( $logo, $x + ( $slot_w - $w ) / 2, $band_y + 3, $w, 0 );
			} else {
				$pdf->Image( $logo, $x, $band_y + 3, $slot_w, 11, 'PNG', '', '', true, 300, '', false, false, 0, 'CM' );
			}
			$x += $slot_w + $gap;
		}

		if ( ! empty( $view['eu_text'] ) ) {
			$pdf->SetTextColorArray( [ 68, 70, 83 ] );
			$pdf->SetFont( 'inter', '', 6.5 );
			$pdf->SetXY( 40, $band_y + 15 );
			$pdf->MultiCell( self::W - 80, 3.4, (string) $view['eu_text'], 0, 'C' );
		}
	}

	/**
	 * Largest size (<= $max) at which $text fits in $width mm on one line.
	 */
	private function fit_size( string $text, string $font, float $max, float $width ): float {
		$size = $max;

		while ( $size > 12 && $this->pdf->GetStringWidth( $text, $font, '', $size ) > $width ) {
			$size -= 1;
		}

		return $size;
	}
}
