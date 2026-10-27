<?php

namespace VirtualisAkademia\Content;

/**
 * Runs LessonBlockConverter on lessons -- safely:
 *
 *   - the result is checked against the original (same text, same images,
 *     same interactive markup, byte-identical scripts); a lesson that doesn't
 *     pass is left untouched and reported;
 *   - the original content is kept in post meta (`_va_pre_blocks_content`)
 *     and can be restored with one click.
 */
class LessonBlockMigration {

	public const BACKUP_META = '_va_pre_blocks_content';

	/** @return int[] */
	public static function lesson_ids(): array {
		return array_map( 'intval', get_posts( [
			'post_type'      => 'sfwd-lessons',
			'post_status'    => [ 'publish', 'draft', 'private', 'pending' ],
			'posts_per_page' => -1,
			'orderby'        => [ 'menu_order' => 'ASC', 'ID' => 'ASC' ],
			'fields'         => 'ids',
		] ) );
	}

	/**
	 * What converting would do -- nothing is saved.
	 *
	 * @return array{ok: bool, reason: string, content: string, stats: array<string,int>, changed: bool}
	 */
	public function preview( int $post_id ): array {
		$old = (string) get_post_field( 'post_content', $post_id );
		$new = ( new LessonBlockConverter() )->convert( $old );

		// Nothing new would become native (e.g. already converted).
		if ( LessonBlockConverter::count_blocks( $new['content'] )['native'] <= LessonBlockConverter::count_blocks( $old )['native'] ) {
			return [ 'ok' => true, 'reason' => 'nincs mit átalakítani', 'content' => $old, 'stats' => $new['stats'], 'changed' => false ];
		}

		$problem = $this->differences( $old, $new['content'] );

		return [
			'ok'      => '' === $problem,
			'reason'  => $problem,
			'content' => $new['content'],
			'stats'   => $new['stats'],
			'changed' => true,
		];
	}

	/** @return array{ok: bool, reason: string, stats: array<string,int>, changed: bool} */
	public function apply( int $post_id ): array {
		$result = $this->preview( $post_id );

		if ( ! $result['ok'] || ! $result['changed'] ) {
			return [ 'ok' => $result['ok'], 'reason' => $result['reason'], 'stats' => $result['stats'], 'changed' => false ];
		}

		if ( '' === (string) get_post_meta( $post_id, self::BACKUP_META, true ) ) {
			update_post_meta( $post_id, self::BACKUP_META, wp_slash( (string) get_post_field( 'post_content', $post_id ) ) );
		}

		$this->save( $post_id, $result['content'] );

		return [ 'ok' => true, 'reason' => '', 'stats' => $result['stats'], 'changed' => true ];
	}

	public function restore( int $post_id ): bool {
		$backup = (string) get_post_meta( $post_id, self::BACKUP_META, true );

		if ( '' === $backup ) {
			return false;
		}

		$this->save( $post_id, $backup );
		delete_post_meta( $post_id, self::BACKUP_META );

		return true;
	}

	private function save( int $post_id, string $content ): void {
		// Scripts and data attributes of the tasks must be saved untouched,
		// whatever the current user's filtering rules (only users who may
		// post unfiltered HTML get here -- see the admin page's capability).
		kses_remove_filters();
		wp_update_post( wp_slash( [ 'ID' => $post_id, 'post_content' => $content ] ) );

		if ( ! current_user_can( 'unfiltered_html' ) ) {
			kses_init_filters();
		}
	}

	// --- Verification ------------------------------------------------------------------------

	/** Empty string when equivalent, otherwise what differs. */
	private function differences( string $old, string $new ): string {
		$a = do_blocks( $old );
		$b = do_blocks( $new );

		if ( $this->scripts( $a ) !== $this->scripts( $b ) ) {
			return 'a feladatok szkriptjei eltérnek';
		}

		// (data-va-full is only the old picture markup's own attribute.)
		foreach ( [ 'data-va(?!-full)', 'onclick', '<img', '<table', '<li', '<h[1-6]', '<blockquote' ] as $needle ) {
			$x = preg_match_all( '~' . $needle . '~i', $a );
			$y = preg_match_all( '~' . $needle . '~i', $b );

			if ( $x !== $y ) {
				return sprintf( 'eltérő darabszám: %s (%d → %d)', $needle, $x, $y );
			}
		}

		$ta = $this->text( $a );
		$tb = $this->text( $b );

		if ( $ta !== $tb ) {
			$i = 0;
			$n = min( strlen( $ta ), strlen( $tb ) );
			while ( $i < $n && $ta[ $i ] === $tb[ $i ] ) {
				++$i;
			}

			return 'a szöveg eltér itt: …' . mb_substr( substr( $ta, max( 0, $i - 20 ) ), 0, 60 ) . '… / …' . mb_substr( substr( $tb, max( 0, $i - 20 ) ), 0, 60 ) . '…';
		}

		return '';
	}

	/** @return string[] */
	private function scripts( string $html ): array {
		preg_match_all( '~<script\b[^>]*>.*?</script>~is', $html, $m );

		return $m[0];
	}

	private function text( string $html ): string {
		$html = preg_replace( '~<(script|style)\b.*?</\1>~is', ' ', $html );
		// A space at every tag boundary on both sides: only real words are compared,
		// not the whitespace the block markup happens to put between elements.
		$text = html_entity_decode( wp_strip_all_tags( str_replace( '<', ' <', $html ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		return trim( preg_replace( '/\s+/u', ' ', str_replace( "\xc2\xa0", ' ', $text ) ) );
	}
}
