<?php

namespace VirtualisAkademia\Frontend;

use VirtualisAkademia\Hookable;

/**
 * A dedicated, full-width registration page -- the form outgrew the landing
 * page's compact two-tab auth card once Activation::ensure_participation_data_fields()
 * added the grant's own required data points on top of Ultimate Member's
 * defaults. Reuses the `.va-landing` shell/backdrop/brand markup (and the
 * UM re-skin CSS scoped to that class) so it still reads as the same
 * product, not a bolted-on separate screen.
 */
class ShortcodeRegister implements Hookable {

	public function register(): void {
		add_shortcode( 'va_register', [ $this, 'render' ] );
	}

	public function render(): string {
		if ( is_user_logged_in() ) {
			$metaverse_url = \VirtualisAkademia\Support\MetaversePage::get_url();

			if ( $metaverse_url ) {
				wp_safe_redirect( $metaverse_url );
				exit;
			}
		}

		$forms             = get_option( 'um_core_forms', [] );
		$register_form_id  = $forms['register'] ?? '';
		$home_url          = \VirtualisAkademia\Support\HomePage::get_url();

		ob_start();
		?>
		<div class="va-landing">
			<div class="va-landing__backdrop"></div>
			<div class="va-landing__container va-register__container">
				<div class="va-landing__brand">
					<span class="va-landing__brand-icon" aria-hidden="true">🏛️</span>
					<span class="va-landing__brand-name">Virtuális Akadémia</span>
				</div>

				<section class="va-register__intro">
					<span class="va-label-caps va-landing__eyebrow">Regisztráció</span>
					<h1 class="va-landing__title">Csatlakozz a Virtuális Akadémiához</h1>
					<p class="va-landing__lead">
						A regisztráció a <strong>„Virtuális Akadémia a demokráciára nevelés és a
						polgári szerepvállalás érdekében”</strong> elnevezésű projekt keretében
						történik. A projektben való részvételhez az alábbi adatok megadása
						kötelező – ezekre a pályázati elszámolási és adatszolgáltatási
						kötelezettségeink teljesítéséhez van szükségünk.
					</p>
				</section>

				<section class="va-landing__auth va-glass-panel va-register__form">
					<?php
					echo $register_form_id
						? do_shortcode( '[ultimatemember form_id="' . absint( $register_form_id ) . '"]' )
						: '<p>' . esc_html__( 'A regisztrációs űrlap jelenleg nem érhető el.', 'virtualis-akademia' ) . '</p>';
					?>
				</section>

				<?php if ( $home_url ) : ?>
					<p class="va-register__back">
						<a href="<?php echo esc_url( $home_url ); ?>">&laquo; Már regisztráltál? Vissza a bejelentkezéshez</a>
					</p>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
