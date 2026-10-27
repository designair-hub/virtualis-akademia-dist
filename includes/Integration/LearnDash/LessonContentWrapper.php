<?php

namespace VirtualisAkademia\Integration\LearnDash;

use VirtualisAkademia\Hookable;

/**
 * Lessons stored as native blocks (Content\LessonBlockConverter) no longer
 * carry the `.va-lesson` wrapper div that all the lesson styling hangs on --
 * it is added around the rendered content here. Content that still has its
 * own wrapper (older, unconverted lessons) is left alone.
 */
class LessonContentWrapper implements Hookable {

	public function register(): void {
		add_filter( 'the_content', [ $this, 'wrap' ], 20 );
	}

	public function wrap( $content ) {
		if ( ! is_singular( [ 'sfwd-lessons', 'sfwd-topic' ] ) || get_the_ID() !== get_queried_object_id() ) {
			return $content;
		}

		if ( ! is_string( $content ) || '' === trim( $content ) || preg_match( '/class="[^"]*\b(va-lesson|democracy-content-wrapper)\b/', $content ) ) {
			return $content;
		}

		return '<div class="va-lesson democracy-content-wrapper">' . $content . '</div>';
	}
}
