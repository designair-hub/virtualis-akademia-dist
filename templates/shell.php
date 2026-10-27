<?php
/**
 * The plugin's page template (Frontend\BrandedShell): every request that
 * isn't one of the product's own pages -- 404, search, archives, posts,
 * ordinary pages -- renders here, in the product's look.
 */

use VirtualisAkademia\Frontend\BrandedShell;
use VirtualisAkademia\Support\SiteFooter;

$back    = BrandedShell::home_link();
$account = is_user_logged_in() && function_exists( 'um_get_core_page' ) ? um_get_core_page( 'account' ) : '';
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'va-shell-body' ); ?>>
<?php wp_body_open(); ?>
<div class="va-landing va-shell">
	<div class="va-landing__backdrop"></div>
	<div class="va-landing__container va-shell__container">

		<header class="va-shell__bar">
			<a class="va-landing__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<span class="va-landing__brand-icon" aria-hidden="true">🏛️</span>
				<span class="va-landing__brand-name">Virtuális Akadémia</span>
			</a>
			<nav class="va-shell__nav" aria-label="<?php esc_attr_e( 'Navigáció', 'virtualis-akademia' ); ?>">
				<?php if ( $account ) : ?>
					<a class="va-shell__link" href="<?php echo esc_url( $account ); ?>"><?php esc_html_e( 'Profilom', 'virtualis-akademia' ); ?></a>
				<?php endif; ?>
				<a class="va-shell__btn va-shell__btn--ghost" href="<?php echo esc_url( $back['url'] ); ?>">
					<span aria-hidden="true">←</span> <?php echo esc_html( $back['label'] ); ?>
				</a>
			</nav>
		</header>

		<main class="va-shell__main" id="va-main">
			<?php if ( is_404() ) : ?>

				<section class="va-shell__notfound va-glass-panel" aria-labelledby="va-404-title">
					<p class="va-shell__code" aria-hidden="true">404</p>
					<p class="va-shell__eyebrow va-label-caps"><?php esc_html_e( 'Hiba · nem található', 'virtualis-akademia' ); ?></p>
					<h1 id="va-404-title" class="va-shell__title"><?php esc_html_e( 'Ez az oldal nem található', 'virtualis-akademia' ); ?></h1>
					<p class="va-shell__lead"><?php esc_html_e( 'Lehet, hogy a cím elgépelődött, vagy az oldalt már áthelyeztük. A kezdőlapról vagy a keresővel biztosan odatalálsz, ahová indultál.', 'virtualis-akademia' ); ?></p>
					<div class="va-shell__actions">
						<a class="va-shell__btn" href="<?php echo esc_url( $back['url'] ); ?>"><?php echo esc_html( $back['label'] ); ?></a>
						<a class="va-shell__btn va-shell__btn--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Kezdőlap', 'virtualis-akademia' ); ?></a>
					</div>
					<?php if ( is_user_logged_in() ) : ?>
						<form class="va-shell__search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
							<label class="va-sr-only" for="va-404-search"><?php esc_html_e( 'Keresés', 'virtualis-akademia' ); ?></label>
							<input id="va-404-search" type="search" name="s" placeholder="<?php esc_attr_e( 'Keresés az oldalon…', 'virtualis-akademia' ); ?>">
							<button type="submit" class="va-shell__btn"><?php esc_html_e( 'Keresés', 'virtualis-akademia' ); ?></button>
						</form>
					<?php endif; ?>
				</section>

			<?php elseif ( is_singular() ) : ?>

				<?php while ( have_posts() ) : the_post(); ?>
					<article <?php post_class( 'va-shell__article va-glass-panel' ); ?>>
						<h1 class="va-shell__title"><?php the_title(); ?></h1>
						<?php if ( is_singular( 'post' ) ) : ?>
							<p class="va-shell__meta va-label-caps"><?php echo esc_html( get_the_date() ); ?></p>
						<?php endif; ?>
						<div class="va-shell__content entry-content"><?php the_content(); ?></div>
					</article>
				<?php endwhile; ?>

			<?php else : ?>

				<header class="va-shell__head">
					<h1 class="va-shell__title">
						<?php
						if ( is_search() ) {
							/* translators: %s: search query */
							printf( esc_html__( 'Keresés: „%s”', 'virtualis-akademia' ), esc_html( get_search_query() ) );
						} elseif ( is_archive() ) {
							echo esc_html( wp_strip_all_tags( get_the_archive_title() ) );
						} else {
							esc_html_e( 'Bejegyzések', 'virtualis-akademia' );
						}
						?>
					</h1>
				</header>

				<?php if ( have_posts() ) : ?>
					<ul class="va-shell__list">
						<?php while ( have_posts() ) : the_post(); ?>
							<li class="va-shell__card va-glass-panel">
								<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
								<?php // LearnDash content has no usable excerpt ("Open to access this content"). ?>
								<?php if ( ! str_starts_with( (string) get_post_type(), 'sfwd-' ) ) : ?>
									<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 32 ) ); ?></p>
								<?php endif; ?>
							</li>
						<?php endwhile; ?>
					</ul>
					<?php the_posts_pagination( [ 'mid_size' => 1, 'prev_text' => '←', 'next_text' => '→' ] ); ?>
				<?php else : ?>
					<section class="va-shell__notfound va-glass-panel">
						<h2 class="va-shell__title"><?php esc_html_e( 'Nincs találat', 'virtualis-akademia' ); ?></h2>
						<p class="va-shell__lead"><?php esc_html_e( 'Ehhez nem találtunk semmit. Próbálkozz másik kifejezéssel.', 'virtualis-akademia' ); ?></p>
						<form class="va-shell__search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
							<label class="va-sr-only" for="va-search"><?php esc_html_e( 'Keresés', 'virtualis-akademia' ); ?></label>
							<input id="va-search" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>">
							<button type="submit" class="va-shell__btn"><?php esc_html_e( 'Keresés', 'virtualis-akademia' ); ?></button>
						</form>
					</section>
				<?php endif; ?>

			<?php endif; ?>
		</main>

		<?php echo SiteFooter::render(); // phpcs:ignore -- built from escaped parts. ?>
	</div>
</div>
<?php wp_footer(); ?>
</body>
</html>
