<?php

namespace VirtualisAkademia\Frontend;

use VirtualisAkademia\Hookable;

/**
 * The public front page: a short trust-building introduction plus the
 * Ultimate Member login/register forms, embedded directly rather than
 * linking out -- this is the only page a logged-out visitor can reach
 * (see Integration\UltimateMember\AccessGate), so it has to carry its own
 * way in.
 */
class ShortcodeLanding implements Hookable {

	public function register(): void {
		add_shortcode( 'va_landing', [ $this, 'render' ] );
	}

	public function render(): string {
		if ( is_user_logged_in() ) {
			// Old post-registration links (`?va_welcome=1`) land here too --
			// the metaverse's opening transition does the greeting now.
			$metaverse_url = \VirtualisAkademia\Support\MetaversePage::get_url();

			if ( $metaverse_url ) {
				wp_safe_redirect( $metaverse_url );
				exit;
			}
		}

		$forms         = get_option( 'um_core_forms', [] );
		$login_form_id = $forms['login'] ?? '';
		$register_url  = \VirtualisAkademia\Support\RegisterPage::get_url();
		$departments   = $this->departments();
		$lesson_total  = array_sum( array_map( static fn( $d ) => count( $d['lessons'] ), $departments ) );

		ob_start();
		?>
		<div class="va-landing va-landing--home">
			<div class="va-landing__backdrop"></div>
			<div class="va-landing__container">
				<div class="va-landing__brand">
					<span class="va-landing__brand-icon" aria-hidden="true">🏛️</span>
					<span class="va-landing__brand-name">Virtuális Akadémia</span>
				</div>

				<div class="va-landing__grid">
					<section class="va-landing__intro">
						<span class="va-label-caps va-landing__eyebrow">
							<?php
							echo esc_html(
								$departments
									? sprintf( '%d tanszék · %d lecke · 1 virtuális épület', count( $departments ), $lesson_total )
									: 'Online tanfolyam a közös ügyekről'
							);
							?>
						</span>
						<h1 class="va-landing__title">Ismerd meg, hogyan működik a demokrácia – és hogyan állhatsz ki a közös ügyekért</h1>
						<p class="va-landing__lead">
							A Virtuális Akadémia egy bejárható, háromdimenziós épület négy
							tanszékkel: Demokrácia, Érdekérvényesítés, Európai Unió és Civil
							szektor. Teremről teremre haladva rövid leckéket olvasol,
							interaktív feladatokat oldasz meg, és minden lecke végén egy
							ellenőrző kvízzel nézheted meg, mit tanultál.
						</p>

						<div class="va-landing__hero-actions">
							<?php if ( $register_url ) : ?>
								<a class="va-welcome__cta" href="<?php echo esc_url( $register_url ); ?>">Regisztrálok</a>
							<?php endif; ?>
							<a class="va-landing__ghost-btn" href="#belepes">Már van fiókom</a>
						</div>

						<ul class="va-landing__trust">
							<li class="va-glass-panel">
								<span class="va-landing__trust-icon" aria-hidden="true">🧭</span>
								<div>
									<strong>Megérted, hogyan születnek a döntések</strong>
									<span>Helyben, az országban és az Európai Unióban is.</span>
								</div>
							</li>
							<li class="va-glass-panel">
								<span class="va-landing__trust-icon" aria-hidden="true">💬</span>
								<div>
									<strong>Kiállsz a saját ügyedért</strong>
									<span>Levél, petíció, tárgyalás, kampány: gyakorlati eszközöket kapsz hozzá.</span>
								</div>
							</li>
							<li class="va-glass-panel">
								<span class="va-landing__trust-icon" aria-hidden="true">🤝</span>
								<div>
									<strong>Közösséget építesz</strong>
									<span>Önkéntesség, közösségszervezés és helyi civil akciók, lépésről lépésre.</span>
								</div>
							</li>
							<li class="va-glass-panel">
								<span class="va-landing__trust-icon" aria-hidden="true">🏅</span>
								<div>
									<strong>Oklevelet szerzel</strong>
									<span>Minden tanszék után pecsét és oklevél jár, a négy együtt Mester Oklevelet ér.</span>
								</div>
							</li>
						</ul>
					</section>

					<section class="va-landing__auth va-glass-panel" id="belepes">
						<div class="va-tabs">
							<input type="radio" name="va-auth-tab" id="va-tab-login" class="va-tabs__input" checked>
							<input type="radio" name="va-auth-tab" id="va-tab-register" class="va-tabs__input">

							<div class="va-tabs__nav">
								<label for="va-tab-login">Bejelentkezés</label>
								<label for="va-tab-register">Regisztráció</label>
							</div>

							<div class="va-tabs__panel" data-panel="login">
								<?php
								echo $login_form_id
									? do_shortcode( '[ultimatemember form_id="' . absint( $login_form_id ) . '"]' )
									: '<p>' . esc_html__( 'A bejelentkezési űrlap jelenleg nem érhető el.', 'virtualis-akademia' ) . '</p>';
								?>
							</div>

							<div class="va-tabs__panel" data-panel="register">
								<p class="va-landing__register-blurb">
									Még nincs fiókod? A regisztráció a <strong>„Virtuális Akadémia a
									demokráciára nevelés és a polgári szerepvállalás érdekében”</strong>
									projekt keretében történik. A pályázati adatszolgáltatáshoz néhány
									további adatot is kérünk, ezért a regisztráció külön oldalon zajlik.
								</p>
								<?php if ( $register_url ) : ?>
									<a class="va-welcome__cta va-landing__register-cta" href="<?php echo esc_url( $register_url ); ?>">
										Tovább a regisztrációhoz
									</a>
								<?php else : ?>
									<p><?php esc_html_e( 'A regisztrációs oldal jelenleg nem érhető el.', 'virtualis-akademia' ); ?></p>
								<?php endif; ?>
							</div>
						</div>
					</section>
				</div>

				<?php if ( $departments ) : ?>
					<section class="va-landing__section" aria-labelledby="va-departments-title">
						<header class="va-landing__section-head">
							<span class="va-label-caps va-landing__eyebrow">A tananyag</span>
							<h2 id="va-departments-title" class="va-landing__h2">Négy tanszék, négy terem</h2>
							<p class="va-landing__section-lead">
								Minden tanszék egy saját terem az Akadémia épületében. Bármelyikkel
								kezdheted, és a saját tempódban haladsz.
							</p>
						</header>

						<ul class="va-dept-grid">
							<?php foreach ( $departments as $dept ) : ?>
								<li class="va-dept va-glass-panel">
									<div class="va-dept__media">
										<?php if ( $dept['image'] ) : ?>
											<img src="<?php echo esc_url( $dept['image'] ); ?>" alt="" loading="lazy" decoding="async">
										<?php endif; ?>
										<span class="va-dept__room"><?php echo esc_html( $dept['room'] ); ?></span>
									</div>
									<div class="va-dept__body">
										<h3 class="va-dept__title"><?php echo esc_html( $dept['title'] ); ?></h3>
										<?php if ( $dept['summary'] ) : ?>
											<p class="va-dept__summary"><?php echo esc_html( $dept['summary'] ); ?></p>
										<?php endif; ?>
										<?php if ( $dept['lessons'] ) : ?>
											<details class="va-dept__lessons">
												<summary>
													<?php echo esc_html( sprintf( '%d lecke, mindegyik végén kvíz', count( $dept['lessons'] ) ) ); ?>
												</summary>
												<ol>
													<?php foreach ( $dept['lessons'] as $lesson_title ) : ?>
														<li><?php echo esc_html( $lesson_title ); ?></li>
													<?php endforeach; ?>
												</ol>
											</details>
										<?php endif; ?>
									</div>
								</li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>

				<section class="va-landing__section" aria-labelledby="va-steps-title">
					<header class="va-landing__section-head">
						<span class="va-label-caps va-landing__eyebrow">Így működik</span>
						<h2 id="va-steps-title" class="va-landing__h2">Négy lépés az oklevélig</h2>
					</header>

					<ol class="va-steps">
						<li class="va-steps__item">
							<span class="va-steps__num" aria-hidden="true">01</span>
							<h3>Regisztrálj</h3>
							<p>Létrehozod a fiókodat. Néhány adatot a projekt pályázati adatszolgáltatásához is kérünk.</p>
						</li>
						<li class="va-steps__item">
							<span class="va-steps__num" aria-hidden="true">02</span>
							<h3>Lépj be az épületbe</h3>
							<p>A Virtuális Akadémiában körbenézhetsz a folyosón, és bármelyik terembe beléphetsz, számítógépen és telefonon is.</p>
						</li>
						<li class="va-steps__item">
							<span class="va-steps__num" aria-hidden="true">03</span>
							<h3>Tanulj a saját tempódban</h3>
							<p>Rövid leckék, példák és interaktív feladatok; minden lecke végén egy ellenőrző kvíz.</p>
						</li>
						<li class="va-steps__item">
							<span class="va-steps__num" aria-hidden="true">04</span>
							<h3>Gyűjtsd a pecséteket</h3>
							<p>Minden elvégzett tanszékért pecsét kerül az útleveledbe, és oklevelet kapsz, e-mailben is. Mind a négy pecséttel Mester Oklevél jár.</p>
						</li>
					</ol>
				</section>

				<section class="va-landing__cta-band va-glass-panel">
					<div>
						<h2 class="va-landing__h2">Kezdd el még ma</h2>
						<p>A regisztráció néhány perc, utána azonnal beléphetsz az első terembe.</p>
					</div>
					<div class="va-landing__cta-actions">
						<?php if ( $register_url ) : ?>
							<a class="va-welcome__cta" href="<?php echo esc_url( $register_url ); ?>">Regisztrálok</a>
						<?php endif; ?>
						<a class="va-landing__ghost-btn" href="#belepes">Bejelentkezés</a>
					</div>
				</section>

				<?php echo $this->render_footer(); // phpcs:ignore -- escaped inside. ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The four departments in curriculum order, each with its linked course,
	 * the room's panorama (a centre crop reads as a photo of the room) and
	 * the course's lesson titles.
	 *
	 * @return array<int, array{title: string, room: string, summary: string, image: string, lessons: string[]}>
	 */
	private function departments(): array {
		$posts = ( new \VirtualisAkademia\Gamification\PassportService() )->get_awardable_departments();

		usort(
			$posts,
			static fn( $a, $b ) => [ $a->menu_order, $a->ID ] <=> [ $b->menu_order, $b->ID ]
		);

		$out = [];

		foreach ( $posts as $dept ) {
			$course_id = (int) get_post_meta( $dept->ID, '_va_linked_course_id', true );
			$course    = get_post( $course_id );

			if ( ! $course || 'publish' !== $course->post_status ) {
				continue;
			}

			$lessons = function_exists( 'learndash_get_course_lessons_list' )
				? wp_list_pluck( wp_list_pluck( learndash_get_course_lessons_list( $course_id, null, [ 'per_page' => 0 ] ), 'post' ), 'post_title' )
				: [];

			$image_id = (int) get_post_meta( $dept->ID, '_va_panorama_image_id', true );

			$out[] = [
				'title'   => get_the_title( $course ),
				'room'    => get_the_title( $dept ),
				'summary' => wp_strip_all_tags( (string) $course->post_excerpt ),
				'image'   => $image_id ? (string) wp_get_attachment_image_url( $image_id, 'medium_large' ) : '',
				'lessons' => array_values( array_map( 'strval', $lessons ) ),
			];
		}

		return $out;
	}

	private function render_footer(): string {
		return \VirtualisAkademia\Support\SiteFooter::render();
	}

}
