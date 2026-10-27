<?php

namespace VirtualisAkademia\Admin;

use VirtualisAkademia\Hookable;

/**
 * Lets an admin download every registered participant's grant-reporting
 * data (the fields Activation::ensure_participation_data_fields() added to
 * registration) as a CSV, to fulfil the project's own data-supply
 * obligations toward the funder -- streamed directly on request rather than
 * generated/stored as a file, since it always needs to reflect the current
 * member list, not a stale snapshot.
 */
class ParticipantsExportPage implements Hookable {

	private const ACTION = 'va_export_participants';

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_page' ] );
		add_action( 'admin_post_' . self::ACTION, [ $this, 'export' ] );
	}

	public function add_page(): void {
		add_users_page(
			__( 'Résztvevők exportálása', 'virtualis-akademia' ),
			__( 'Résztvevők exportálása', 'virtualis-akademia' ),
			'list_users',
			'va-participants-export',
			[ $this, 'render' ]
		);
	}

	public function render(): void {
		$url = wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::ACTION ),
			self::ACTION
		);

		echo '<div class="wrap"><h1>' . esc_html__( 'Résztvevők exportálása', 'virtualis-akademia' ) . '</h1>';
		echo '<p>' . esc_html__( 'A pályázati adatszolgáltatáshoz szükséges, regisztrációkor megadott résztvevői adatok táblázatos (CSV) letöltése.', 'virtualis-akademia' ) . '</p>';
		printf(
			'<p><a href="%s" class="button button-primary">%s</a></p>',
			esc_url( $url ),
			esc_html__( 'CSV letöltése', 'virtualis-akademia' )
		);
		echo '</div>';
	}

	public function export(): void {
		if ( ! current_user_can( 'list_users' ) ||
			! isset( $_GET['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), self::ACTION )
		) {
			wp_die( esc_html__( 'Nincs jogosultságod ehhez a művelethez.', 'virtualis-akademia' ) );
		}

		$users = get_users( [ 'role' => 'subscriber', 'orderby' => 'registered', 'order' => 'ASC' ] );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="reszvevok-' . gmdate( 'Y-m-d' ) . '.csv"' );

		$out = fopen( 'php://output', 'w' );

		// UTF-8 BOM so Excel (still the realistic target for a funder's own
		// spreadsheet template) doesn't mangle Hungarian accented characters.
		fwrite( $out, "\xEF\xBB\xBF" );

		fputcsv( $out, [
			'Felhasználónév',
			'Vezetéknév',
			'Keresztnév',
			'E-mail cím',
			'Telefonszám',
			'Születési dátum',
			'Születési hely',
			'Lakóhely',
			'Állampolgárság',
			'Hátrányos helyzet',
			'Egyéb (hátrányos helyzet)',
			'Regisztráció dátuma',
		] );

		foreach ( $users as $user ) {
			$disadvantage_status = get_user_meta( $user->ID, 'disadvantage_status', true );
			$disadvantage_list   = is_array( $disadvantage_status ) ? implode( '; ', $disadvantage_status ) : (string) $disadvantage_status;

			fputcsv( $out, [
				$user->user_login,
				get_user_meta( $user->ID, 'last_name', true ),
				get_user_meta( $user->ID, 'first_name', true ),
				$user->user_email,
				get_user_meta( $user->ID, 'phone_number', true ),
				get_user_meta( $user->ID, 'birth_date', true ),
				get_user_meta( $user->ID, 'birth_place', true ),
				get_user_meta( $user->ID, 'residence', true ),
				get_user_meta( $user->ID, 'citizenship', true ),
				$disadvantage_list,
				get_user_meta( $user->ID, 'disadvantage_other', true ),
				$user->user_registered,
			] );
		}

		fclose( $out );
		exit;
	}
}
