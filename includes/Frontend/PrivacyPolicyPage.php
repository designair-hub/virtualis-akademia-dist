<?php

namespace VirtualisAkademia\Frontend;

use VirtualisAkademia\Hookable;

/**
 * The Privacy Policy page is edited as an ordinary WordPress page (whatever
 * content an admin puts in the block editor), but should still read as part
 * of the same branded product as the landing/register pages rather than a
 * bare default-theme article -- especially since AccessGate now lets it
 * stay reachable while logged out, it's often the very first thing an
 * anonymous visitor who followed a link sees. Reuses the `.va-landing`
 * shell (and its CSS, plus the theme-chrome-hiding rule already scoped to
 * that class in app.scss) rather than inventing a second visual language.
 */
class PrivacyPolicyPage implements Hookable {

	public function register(): void {
		add_filter( 'the_content', [ $this, 'wrap_content' ] );
	}

	public function wrap_content( string $content ): string {
		if ( ! is_page() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$privacy_page_id = (int) get_option( 'wp_page_for_privacy_policy' );

		if ( ! $privacy_page_id || ! is_page( $privacy_page_id ) ) {
			return $content;
		}

		ob_start();
		?>
		<div class="va-landing va-privacy">
			<div class="va-landing__backdrop"></div>
			<div class="va-landing__container va-privacy__container">
				<div class="va-landing__brand">
					<span class="va-landing__brand-icon" aria-hidden="true">🏛️</span>
					<span class="va-landing__brand-name">Virtuális Akadémia</span>
				</div>

				<article class="va-privacy__article va-glass-panel">
					<?php echo $content; // phpcs:ignore -- already the filtered post content. ?>
				</article>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
