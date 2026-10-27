<?php

namespace VirtualisAkademia\Support;

/**
 * The branded footer (partner logos, the EU funding note, © + privacy
 * link) -- shared by the landing page and the plugin's page shell.
 */
class SiteFooter {

	public static function render(): string {
		$eu_text = ProjectInfo::attribution();
		$privacy = get_privacy_policy_url();
		$terms   = TermsPage::get_url();

		ob_start();
		?>
		<footer class="va-landing__footer">
			<ul class="va-landing__partners">
				<?php foreach ( Partners::all() as $partner ) : ?>
					<li>
						<a href="<?php echo esc_url( $partner['url'] ); ?>" target="_blank" rel="noopener">
							<img src="<?php echo esc_url( $partner['logo'] ); ?>" alt="<?php echo esc_attr( $partner['name'] ); ?>" height="44">
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( $eu_text ) : ?>
				<p class="va-landing__eu-text"><?php echo esc_html( $eu_text ); ?></p>
			<?php endif; ?>
			<p class="va-landing__legal">
				© <?php echo esc_html( wp_date( 'Y' ) ); ?> Virtuális Akadémia
				<?php if ( $privacy ) : ?>
					· <a href="<?php echo esc_url( $privacy ); ?>">Adatkezelési tájékoztató</a>
				<?php endif; ?>
				<?php if ( $terms ) : ?>
					· <a href="<?php echo esc_url( $terms ); ?>">Felhasználási feltételek</a>
				<?php endif; ?>
			</p>
		</footer>
		<?php
		return (string) ob_get_clean();
	}
}
