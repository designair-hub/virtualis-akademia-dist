<?php

namespace VirtualisAkademia\Certificates;

use VirtualisAkademia\Integration\LearnDash\Navigation;
use VirtualisAkademia\Support\EmailLayout;
use VirtualisAkademia\Support\Hungarian;
use VirtualisAkademia\Support\MetaversePage;

/**
 * E-mails a freshly issued certificate to the learner's registered address,
 * the PDF attached. Branded HTML body (table layout + inline styles, for
 * mail clients), plain-text alternative left to wp_mail's HTML handling.
 */
class CertificateMailer {

	/**
	 * @param array{type: string, course_id: int, issued_at: string, serial: string} $record
	 */
	public function send( int $user_id, array $record, string $pdf ): bool {
		$user = get_userdata( $user_id );

		if ( ! $user || ! is_email( $user->user_email ) || '' === $pdf ) {
			return false;
		}

		$is_master = 'master' === $record['type'];
		$course    = $is_master ? '' : get_the_title( (int) $record['course_id'] );
		$first     = trim( (string) $user->first_name ) ?: $user->display_name;

		$subject = $is_master
			? __( 'Gratulálunk! Megszerezted a Mester Oklevelet – Virtuális Akadémia', 'virtualis-akademia' )
			/* translators: 1: definite article (a/az), 2: course title */
			: sprintf( __( 'Gratulálunk! Megszerezted %1$s „%2$s” tanszék oklevelét – Virtuális Akadémia', 'virtualis-akademia' ), Hungarian::article( $course ), $course );

		$dir  = trailingslashit( get_temp_dir() ) . 'va-certificates-' . wp_generate_password( 12, false );
		$path = $dir . '/' . CertificateService::file_name( $record );

		if ( ! wp_mkdir_p( $dir ) || false === file_put_contents( $path, $pdf ) ) {
			return false;
		}

		$sent = wp_mail(
			$user->user_email,
			$subject,
			$this->body( $first, $is_master, $course, $record ),
			[ 'Content-Type: text/html; charset=UTF-8' ],
			[ $path ]
		);

		wp_delete_file( $path );
		@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- best-effort temp cleanup.

		/**
		 * Fires after a certificate e-mail was handed to wp_mail.
		 *
		 * @param bool  $sent
		 * @param int   $user_id
		 * @param array $record
		 */
		do_action( 'va_certificate_emailed', $sent, $user_id, $record );

		return $sent;
	}

	/**
	 * @param array{type: string, course_id: int, issued_at: string, serial: string} $record
	 */
	private function body( string $first, bool $is_master, string $course, array $record ): string {
		$cta_url = $is_master
			? ( MetaversePage::get_url() ?: home_url( '/' ) )
			: ( Navigation::room_url( (int) $record['course_id'] ) ?: home_url( '/' ) );

		$lead = $is_master
			? 'Mind a négy tanszék pecsétje a tiéd: teljesítetted a Virtuális Akadémia teljes tananyagát, és megszerezted a <strong style="color:#fecb00">Mester Oklevelet</strong>.'
			: sprintf( 'Sikeresen teljesítetted %1$s <strong style="color:#fecb00">%2$s</strong> tanszék teljes tananyagát, és megszerezted a tanszék oklevelét. A pecsét már az Útleveledben van.', Hungarian::article( $course ), esc_html( $course ) );

		$next = $is_master
			? 'Köszönjük, hogy végigjártad velünk a Virtuális Akadémiát!'
			: 'Folytasd a felfedezést a többi tanszéken – mind a négy pecsét összegyűjtésével a Mester Oklevelet is megszerezheted.';

		return EmailLayout::document(
			[
				/* translators: %s: learner's first name */
				'heading'    => sprintf( __( 'Gratulálunk, %s!', 'virtualis-akademia' ), $first ),
				'paragraphs' => [
					wp_kses_post( $lead ),
					'Az oklevelet <strong style="color:#dae2fd;">PDF-mellékletként</strong> csatoltuk ehhez a levélhez. Bármikor újra letöltheted a Virtuális Akadémiában, az <strong style="color:#dae2fd;">Útlevél</strong> ablakból.',
					esc_html( $next ),
				],
				'cta'        => [
					'url'   => $cta_url,
					'label' => __( 'Vissza a Virtuális Akadémiába', 'virtualis-akademia' ),
				],
				'note'       => esc_html( 'Oklevél-azonosító: ' . $record['serial'] ) . '<br>' . esc_html__( 'Ezt a levelet a rendszer automatikusan küldte, kérjük, ne válaszolj rá.', 'virtualis-akademia' ),
			]
		);
	}
}
