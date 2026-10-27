<?php

namespace VirtualisAkademia\Frontend;

use VirtualisAkademia\Hookable;
use VirtualisAkademia\Support\MetaversePage;

/**
 * First-paint cover for the metaverse ⇄ learning-page transition.
 *
 * The iris closes on the page being left (Transition.leave()) and opens on
 * the next one, but the next page's bundle only runs after the page has
 * already been painted -- so this tiny inline <head> script decides, before
 * anything renders, whether this page is the far side of a transition and
 * if so puts a dark cover over it (`html.va-warp-pending`), which the
 * bundle then replaces with the real, labelled overlay.
 *
 * A page is the far side when:
 *  - the page before stored a hand-off for exactly this path
 *    (sessionStorage `va-warp`, a few seconds old at most), or
 *  - it was reached straight from the other side: the metaverse from a
 *    course/lesson/quiz page (e.g. LearnDash's own redirect after "Mark
 *    Complete"), or a learning page from the metaverse.
 * Reloads and back/forward navigation never animate.
 */
class PageTransition implements Hookable {

	private const LEARN_TYPES = [ 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ];

	public function register(): void {
		add_action( 'wp_head', [ $this, 'print_head' ], 1 );
	}

	public function print_head(): void {
		if ( ! is_user_logged_in() || ! is_singular() ) {
			return;
		}

		$metaverse_url = MetaversePage::get_url();
		$post          = get_post();

		if ( ! $metaverse_url || ! $post ) {
			return;
		}

		if ( is_singular( self::LEARN_TYPES ) ) {
			$page = 'learn';
		} elseif ( has_shortcode( (string) $post->post_content, 'va_metaverse' ) ) {
			$page = 'metaverse';
		} else {
			return;
		}

		$courses_slug = class_exists( '\LearnDash_Settings_Section' )
			? (string) \LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'courses' )
			: '';

		$config = [
			'page'          => $page,
			'metaversePath' => (string) wp_parse_url( $metaverse_url, PHP_URL_PATH ),
			'learnBase'     => (string) wp_parse_url( home_url( '/' . trim( $courses_slug ?: 'courses', '/' ) . '/' ), PHP_URL_PATH ),
		];
		?>
<style id="va-warp-cover">html.va-warp-pending::after{content:"";position:fixed;inset:0;z-index:2147483000;background:radial-gradient(circle at 50% 50%,#0d2152 0%,#081026 45%,#060e20 80%)}</style>
<script id="va-warp-head">
window.vaWarp = <?php echo wp_json_encode( $config ); ?>;
(function () {
	try {
		var c = window.vaWarp, d = document.documentElement;
		var nav = performance.getEntriesByType && performance.getEntriesByType( 'navigation' )[ 0 ];
		if ( nav && ( 'reload' === nav.type || 'back_forward' === nav.type ) ) { return; }
		var ok = false, f = JSON.parse( sessionStorage.getItem( 'va-warp' ) || 'null' );
		if ( f && Date.now() - f.t < 15000 && f.path === location.pathname ) { ok = true; }
		if ( ! ok && document.referrer ) {
			var r = new URL( document.referrer );
			if ( r.origin === location.origin ) {
				if ( 'metaverse' === c.page && 0 === r.pathname.indexOf( c.learnBase ) ) { ok = true; }
				if ( 'learn' === c.page && r.pathname === c.metaversePath ) { ok = true; }
			}
		}
		if ( ok ) {
			d.classList.add( 'va-warp-pending' );
			setTimeout( function () { d.classList.remove( 'va-warp-pending' ); }, 6000 );
		}
	} catch ( e ) {}
})();
</script>
		<?php
	}
}
