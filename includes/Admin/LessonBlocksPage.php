<?php

namespace VirtualisAkademia\Admin;

use VirtualisAkademia\Content\LessonBlockConverter;
use VirtualisAkademia\Content\LessonBlockMigration;
use VirtualisAkademia\Hookable;
use VirtualisAkademia\PostTypes\DepartmentPostType;

/**
 * Tanszékek → Tananyag blokkokká alakítása: turns the lessons' Custom HTML
 * into native Gutenberg blocks (text, headings, lists, pictures, tables …),
 * leaving the interactive tasks untouched. Per lesson or all at once; every
 * lesson is verified against its original first, and can be restored.
 */
class LessonBlocksPage implements Hookable {

	private const SLUG       = 'va-lesson-blocks';
	private const CAPABILITY = 'unfiltered_html'; // saves raw <script>s -- admins only

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_page' ] );
		add_action( 'admin_post_va_lesson_blocks', [ $this, 'handle' ] );
	}

	public function add_page(): void {
		add_submenu_page(
			'edit.php?post_type=' . DepartmentPostType::SLUG,
			__( 'Tananyag blokkokká alakítása', 'virtualis-akademia' ),
			__( 'Tananyag blokkokká', 'virtualis-akademia' ),
			self::CAPABILITY,
			self::SLUG,
			[ $this, 'render' ]
		);
	}

	private function url( array $args = [] ): string {
		return add_query_arg( $args + [ 'post_type' => DepartmentPostType::SLUG, 'page' => self::SLUG ], admin_url( 'edit.php' ) );
	}

	public function render(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod ehhez az oldalhoz.', 'virtualis-akademia' ) );
		}

		$migration = new LessonBlockMigration();
		$rows      = [];

		foreach ( LessonBlockMigration::lesson_ids() as $id ) {
			$now      = LessonBlockConverter::count_blocks( (string) get_post_field( 'post_content', $id ) );
			$preview  = $migration->preview( $id );
			$rows[]   = [
				'id'      => $id,
				'now'     => $now,
				'preview' => $preview,
				'after'   => LessonBlockConverter::count_blocks( $preview['content'] ),
				'backup'  => '' !== (string) get_post_meta( $id, LessonBlockMigration::BACKUP_META, true ),
			];
		}

		$todo = array_filter( $rows, static fn( $r ) => $r['preview']['changed'] && $r['preview']['ok'] );

		echo '<div class="wrap"><h1>' . esc_html__( 'Tananyag blokkokká alakítása', 'virtualis-akademia' ) . '</h1>';

		if ( isset( $_GET['done'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( sanitize_text_field( wp_unslash( $_GET['done'] ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			);
		}

		echo '<p style="max-width:860px">' . esc_html__( 'A leckék szövege, címsorai, listái, idézetei, táblázatai és képei szerkeszthető Gutenberg-blokkok lesznek. A feladatok (kattintós, párosítós, sorrendezős stb.) a szkriptjükkel együtt, változatlanul egyedi HTML-blokkban maradnak. Átalakítás előtt minden leckét ellenőrzünk az eredetivel (szöveg, képek, feladat-jelölők, szkriptek); ami nem azonos, azt nem írjuk át. Az eredeti tartalom elmentődik, és bármikor visszaállítható.', 'virtualis-akademia' ) . '</p>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'va_lesson_blocks' );
		echo '<input type="hidden" name="action" value="va_lesson_blocks" />';

		echo '<p><button class="button button-primary button-large" name="do" value="all"' . ( $todo ? '' : ' disabled' ) . '>' . esc_html( sprintf(
			/* translators: %d: number of lessons */
			_n( 'Átalakítás: %d lecke', 'Átalakítás: mind a(z) %d lecke', count( $todo ), 'virtualis-akademia' ),
			count( $todo )
		) ) . '</button></p>';

		echo '<table class="widefat striped" style="max-width:980px"><thead><tr><th>' . esc_html__( 'Lecke', 'virtualis-akademia' ) . '</th><th>' . esc_html__( 'Natív blokk', 'virtualis-akademia' ) . '</th><th>' . esc_html__( 'Egyedi HTML', 'virtualis-akademia' ) . '</th><th>' . esc_html__( 'Állapot', 'virtualis-akademia' ) . '</th><th></th></tr></thead><tbody>';

		foreach ( $rows as $r ) {
			$p      = $r['preview'];
			$status = ! $p['ok'] ? '<strong style="color:#d63638">' . esc_html__( 'Nem azonos az eredetivel: kihagyva – ', 'virtualis-akademia' ) . esc_html( $p['reason'] ) . '</strong>'
				: ( $p['changed'] ? esc_html__( 'Átalakítható', 'virtualis-akademia' ) : esc_html__( 'Kész', 'virtualis-akademia' ) );
			$native = $p['changed'] ? sprintf( '%d → %d', $r['now']['native'], $r['after']['native'] ) : (string) $r['now']['native'];
			$html   = $p['changed'] ? sprintf( '%d → %d', $r['now']['html'], $r['after']['html'] ) : (string) $r['now']['html'];

			echo '<tr><td><a href="' . esc_url( (string) get_edit_post_link( $r['id'] ) ) . '">' . esc_html( html_entity_decode( get_the_title( $r['id'] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) . '</a></td>';
			echo '<td>' . esc_html( $native ) . '</td><td>' . esc_html( $html ) . '</td><td>' . $status . '</td><td>'; // phpcs:ignore -- built from escaped parts.

			if ( $p['changed'] && $p['ok'] ) {
				echo '<button class="button" name="do" value="convert:' . (int) $r['id'] . '">' . esc_html__( 'Átalakít', 'virtualis-akademia' ) . '</button> ';
			}

			if ( $r['backup'] ) {
				echo '<button class="button" name="do" value="restore:' . (int) $r['id'] . '" onclick="return confirm(\'' . esc_js( __( 'Visszaállítod az eredeti (egyedi HTML) tartalmat? A blokkos szerkesztés közben végzett módosításaid elvesznek ennél a leckénél.', 'virtualis-akademia' ) ) . '\')">' . esc_html__( 'Eredeti visszaállítása', 'virtualis-akademia' ) . '</button>';
			}

			echo '</td></tr>';
		}

		echo '</tbody></table></form></div>';
	}

	public function handle(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod ehhez a művelethez.', 'virtualis-akademia' ) );
		}

		check_admin_referer( 'va_lesson_blocks' );

		$do        = isset( $_POST['do'] ) ? sanitize_text_field( wp_unslash( $_POST['do'] ) ) : '';
		$migration = new LessonBlockMigration();
		$done      = 0;
		$skipped   = 0;
		$message   = '';

		if ( 'all' === $do ) {
			foreach ( LessonBlockMigration::lesson_ids() as $id ) {
				$result = $migration->apply( $id );

				if ( ! $result['ok'] ) {
					++$skipped;
				} elseif ( $result['changed'] ) {
					++$done;
				}
			}

			$message = sprintf( 'Kész. Átalakítva: %d lecke%s.', $done, $skipped ? sprintf( ', kihagyva (nem azonos az eredetivel): %d', $skipped ) : '' );
		} elseif ( str_starts_with( $do, 'convert:' ) ) {
			$id     = (int) substr( $do, 8 );
			$result = $migration->apply( $id );
			$message = $result['ok'] ? 'A lecke átalakítva.' : 'A lecke kihagyva: ' . $result['reason'];
		} elseif ( str_starts_with( $do, 'restore:' ) ) {
			$message = $migration->restore( (int) substr( $do, 8 ) ) ? 'Az eredeti tartalom visszaállítva.' : 'Nincs mentett eredeti tartalom.';
		}

		wp_safe_redirect( $this->url( [ 'done' => rawurlencode( $message ) ] ) );
		exit;
	}
}
