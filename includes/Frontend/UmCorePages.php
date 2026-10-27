<?php

namespace VirtualisAkademia\Frontend;

use VirtualisAkademia\Hookable;

/**
 * Ultimate Member's own core pages (Login, Password reset, the member
 * profile, ...) are ordinary WordPress pages rendered by the block theme --
 * so a learner who clicks "Elfelejtetted a jelszavad?" on the branded
 * landing page used to land on a bare Twenty Twenty-Five page with the
 * theme's English demo footer ("Blog · About · FAQs · Shop"). Same approach
 * as PrivacyPolicyPage: wrap the content in the `.va-landing` shell, whose
 * CSS already hides the theme chrome and skins the UM forms.
 *
 * The Account page gets its own layout instead -- the member's profile page
 * (profile_page(), assets/src/account.js, assets/src/styles/account.scss).
 */
class UmCorePages implements Hookable {

	public const WRAPPED = [ 'login', 'password-reset', 'register', 'user', 'members', 'logout' ];

	public function register(): void {
		add_filter( 'the_content', [ $this, 'wrap_content' ], 20 );
		add_action( 'template_redirect', [ $this, 'redirect_own_profile' ] );
	}

	/**
	 * UM's public profile page (/user/<name>/) duplicates the account page
	 * with a cover-photo layout of its own -- a member looking at their own
	 * profile is sent to the one profile page instead.
	 */
	public function redirect_own_profile(): void {
		if ( ! is_user_logged_in() || ! is_page() || 'user' !== get_post_meta( get_queried_object_id(), '_um_core', true ) ) {
			return;
		}

		$requested = (string) get_query_var( 'um_user' );
		$user      = wp_get_current_user();

		if ( '' !== $requested && ! in_array( $requested, [ $user->user_login, $user->user_nicename, (string) $user->ID ], true ) ) {
			return;
		}

		$account = function_exists( 'um_get_core_page' ) ? um_get_core_page( 'account' ) : '';

		if ( $account ) {
			wp_safe_redirect( $account );
			exit;
		}
	}

	private function profile_page( string $content ): string {
		$metaverse_url = \VirtualisAkademia\Support\MetaversePage::get_url();

		ob_start();
		?>
		<div class="va-profile-page">
			<header class="va-profile-page__top">
				<span class="va-profile-page__brand">
					<span aria-hidden="true">🏛️</span>
					<span>Virtuális Akadémia</span>
				</span>
				<?php if ( $metaverse_url ) : ?>
					<a class="va-profile-page__back" href="<?php echo esc_url( $metaverse_url ); ?>">
						<span aria-hidden="true">←</span> <?php esc_html_e( 'Vissza a Virtuális Akadémiába', 'virtualis-akademia' ); ?>
					</a>
				<?php endif; ?>
			</header>

			<p class="va-profile-page__eyebrow va-label-caps"><?php esc_html_e( 'Profilom', 'virtualis-akademia' ); ?></p>

			<div class="va-profile-page__grid">
				<div class="va-profile-page__card" id="va-profile-card">
					<noscript><?php esc_html_e( 'A profilkártya megjelenítéséhez engedélyezd a JavaScriptet.', 'virtualis-akademia' ); ?></noscript>
				</div>
				<section class="va-profile-page__settings" aria-labelledby="va-profile-settings-title">
					<h2 class="va-profile-page__settings-title" id="va-profile-settings-title"><?php esc_html_e( 'Fiókbeállítások', 'virtualis-akademia' ); ?></h2>
					<?php echo $content; // phpcs:ignore -- the filtered post content (UM's account form). ?>
				</section>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	public static function current_core_slug(): ?string {
		if ( ! is_page() ) {
			return null;
		}

		$slug = (string) get_post_meta( get_the_ID(), '_um_core', true );

		return in_array( $slug, self::WRAPPED, true ) ? $slug : null;
	}

	public function wrap_content( string $content ): string {
		if ( ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		// The Account page is the member's own profile page: the ID card
		// (filled in by assets/src/account.js) next to UM's settings tabs.
		if ( is_page() && 'account' === get_post_meta( get_the_ID(), '_um_core', true ) ) {
			return $this->profile_page( $content );
		}

		$slug = self::current_core_slug();

		if ( ! $slug ) {
			return $content;
		}

		// Logged-in learners (profile, members) belong back in the metaverse;
		// anyone else back on the landing page.
		$metaverse_url = is_user_logged_in() ? \VirtualisAkademia\Support\MetaversePage::get_url() : null;
		$home_url      = $metaverse_url ?: \VirtualisAkademia\Support\HomePage::get_url();
		$back_label    = $metaverse_url ? __( 'Vissza a Virtuális Akadémiába', 'virtualis-akademia' ) : __( 'Vissza a kezdőlapra', 'virtualis-akademia' );

		ob_start();
		?>
		<div class="va-landing va-privacy va-um-page va-um-page--<?php echo esc_attr( $slug ); ?>">
			<div class="va-landing__backdrop"></div>
			<div class="va-landing__container va-um-page__container">
				<div class="va-landing__brand">
					<span class="va-landing__brand-icon" aria-hidden="true">🏛️</span>
					<span class="va-landing__brand-name">Virtuális Akadémia</span>
				</div>

				<section class="va-landing__auth va-glass-panel va-um-page__panel">
					<h1 class="va-um-page__title"><?php echo esc_html( get_the_title() ); ?></h1>
					<?php echo $content; // phpcs:ignore -- already the filtered post content. ?>
				</section>

				<?php if ( $home_url ) : ?>
					<p class="va-register__back">
						<a href="<?php echo esc_url( $home_url ); ?>">&laquo; <?php echo esc_html( $back_label ); ?></a>
					</p>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
