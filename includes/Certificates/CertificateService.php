<?php

namespace VirtualisAkademia\Certificates;

use VirtualisAkademia\Gamification\PassportService;
use VirtualisAkademia\Hookable;
use VirtualisAkademia\Integration\LearnDash\CourseRoomMapper;

/**
 * The Virtuális Akadémia's own certificates ("oklevél"): one per completed
 * department course, plus the Master certificate once every department is
 * stamped. Replaces LearnDash's bare HTML-to-PDF certificate templates with
 * CertificateRenderer's branded PDF, and on issue e-mails it to the
 * learner's registered address as an attachment.
 *
 * Issued certificates are recorded in user meta (`_va_certificates`, keyed
 * `course-<id>` / `master`) with their issue date and a sequential serial,
 * so a re-download always shows the same date and number. Learners who
 * finished a course before this existed get their record lazily, on first
 * download, dated to LearnDash's own completion time (no e-mail then --
 * only a real completion sends one).
 */
class CertificateService implements Hookable {

	private const META          = '_va_certificates';
	private const SERIAL_OPTION = 'va_certificate_serial';
	public const QUERY_VAR      = 'va_certificate';

	private PassportService $passport;

	public function __construct() {
		$this->passport = new PassportService();
	}

	public function register(): void {
		// After CertificateBridge (priority 10) has stamped the passport, so
		// the Master certificate event -- fired from inside that -- and this
		// course certificate don't race each other.
		add_action( 'learndash_course_completed', [ $this, 'on_course_completed' ], 20 );
		add_action( 'va_master_certificate_issued', [ $this, 'on_master_issued' ] );
		add_filter( 'learndash_course_certificate_link', [ $this, 'filter_learndash_link' ], 10, 3 );
		add_action( 'template_redirect', [ $this, 'maybe_serve' ] );
	}

	// --- Issuing -------------------------------------------------------------

	/**
	 * @param array{user: \WP_User, course: \WP_Post} $data
	 */
	public function on_course_completed( $data ): void {
		if ( empty( $data['user'] ) || empty( $data['course'] ) ) {
			return;
		}

		$user_id   = (int) $data['user']->ID;
		$course_id = (int) $data['course']->ID;

		if ( ! CourseRoomMapper::department_for_course( $course_id ) ) {
			return; // Not one of the four department courses.
		}

		$this->issue( $user_id, 'course', $course_id, true );
	}

	public function on_master_issued( $user_id ): void {
		$this->issue( (int) $user_id, 'master', 0, true );
	}

	/**
	 * Records the certificate (idempotent) and, when $notify and it's newly
	 * issued, e-mails the PDF.
	 *
	 * @return array{type: string, course_id: int, issued_at: string, serial: string}|null
	 */
	public function issue( int $user_id, string $type, int $course_id = 0, bool $notify = false ): ?array {
		if ( ! $this->is_eligible( $user_id, $type, $course_id ) ) {
			return null;
		}

		$records = $this->records( $user_id );
		$key     = self::key( $type, $course_id );

		if ( isset( $records[ $key ] ) ) {
			return $records[ $key ];
		}

		$records[ $key ] = [
			'type'      => $type,
			'course_id' => $course_id,
			'issued_at' => $this->completion_time( $user_id, $type, $course_id ),
			'serial'    => $this->next_serial(),
		];

		update_user_meta( $user_id, self::META, $records );

		if ( $notify ) {
			( new CertificateMailer() )->send( $user_id, $records[ $key ], $this->render( $user_id, $records[ $key ] ) );
		}

		return $records[ $key ];
	}

	public function is_eligible( int $user_id, string $type, int $course_id = 0 ): bool {
		if ( ! $user_id ) {
			return false;
		}

		if ( 'master' === $type ) {
			return $this->passport->has_all_stamps( $user_id );
		}

		return $course_id
			&& 'sfwd-courses' === get_post_type( $course_id )
			&& learndash_course_completed( $user_id, $course_id );
	}

	/**
	 * @return array<string, array{type: string, course_id: int, issued_at: string, serial: string}>
	 */
	public function records( int $user_id ): array {
		$records = get_user_meta( $user_id, self::META, true );

		return is_array( $records ) ? $records : [];
	}

	public static function key( string $type, int $course_id = 0 ): string {
		return 'master' === $type ? 'master' : 'course-' . $course_id;
	}

	private function completion_time( int $user_id, string $type, int $course_id ): string {
		if ( 'course' === $type && function_exists( 'learndash_user_get_course_completed_date' ) ) {
			$timestamp = (int) learndash_user_get_course_completed_date( $user_id, $course_id );

			if ( $timestamp > 0 && $timestamp < time() - 60 ) {
				return wp_date( 'Y-m-d H:i:s', $timestamp );
			}
		}

		return current_time( 'mysql' );
	}

	private function next_serial(): string {
		$next = (int) get_option( self::SERIAL_OPTION, 0 ) + 1;
		update_option( self::SERIAL_OPTION, $next, false );

		return sprintf( 'VA-%s-%05d', wp_date( 'Y' ), $next );
	}

	// --- Rendering -------------------------------------------------------------

	/**
	 * @param array{type: string, course_id: int, issued_at: string, serial: string} $record
	 */
	public function render( int $user_id, array $record ): string {
		return ( new CertificateRenderer() )->render( $this->build_view( $user_id, $record ) );
	}

	/**
	 * Everything the renderer prints, resolved from the record.
	 *
	 * @param array{type: string, course_id: int, issued_at: string, serial: string} $record
	 * @return array<string, mixed>
	 */
	public function build_view( int $user_id, array $record ): array {
		$user = get_userdata( $user_id );
		$name = $user ? trim( $user->last_name . ' ' . $user->first_name ) : '';

		if ( '' === $name && $user ) {
			$name = $user->display_name;
		}

		$view = [
			'type'      => $record['type'],
			'name'      => $name,
			'date'      => wp_date( 'Y. F j.', strtotime( $record['issued_at'] ) ),
			'serial'    => $record['serial'],
			'eu_text'   => \VirtualisAkademia\Support\ProjectInfo::attribution(),
			// The EU funding logo in place of Erasmus+, then the partners.
			'logos'     => array_merge(
				[ \VirtualisAkademia\Support\ProjectInfo::logo_path() ],
				array_map(
					static fn( $partner ) => VA_PLUGIN_DIR . '/assets/img/partners/' . basename( $partner['logo'] ),
					array_filter( \VirtualisAkademia\Support\Partners::all(), static fn( $partner ) => ! str_contains( $partner['logo'], 'erasmus' ) )
				)
			),
		];

		if ( 'master' === $record['type'] ) {
			$departments = $this->passport->get_awardable_departments();
			// Curriculum order (menu order, then creation order), not newest first.
			usort( $departments, static fn( $a, $b ) => [ $a->menu_order, $a->ID ] <=> [ $b->menu_order, $b->ID ] );

			$view['departments'] = array_map(
				static fn( $department ) => \VirtualisAkademia\PostTypes\DepartmentPostType::label( $department ),
				$departments
			);
		} else {
			// The course (department) name only -- the room's name stays off
			// the certificate.
			$view['course'] = html_entity_decode( get_the_title( (int) $record['course_id'] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}

		return $view;
	}

	public static function file_name( array $record ): string {
		$slug = 'master' === $record['type'] ? 'mester-oklevel' : 'oklevel-' . sanitize_title( remove_accents( get_the_title( (int) $record['course_id'] ) ) );

		return 'virtualis-akademia-' . $slug . '.pdf';
	}

	// --- Download ------------------------------------------------------------

	public static function download_url( int $user_id, string $type, int $course_id = 0 ): string {
		$key = self::key( $type, $course_id );

		return add_query_arg(
			[
				self::QUERY_VAR => $key,
				'nonce'         => wp_create_nonce( 'va_certificate_' . $key . '_' . $user_id ),
			],
			home_url( '/' )
		);
	}

	/**
	 * Admin-only sample, drawn with the current admin's name and a
	 * placeholder serial -- for checking the design without completing a
	 * course. `?va_certificate=preview&type=course&course=<id>` or `type=master`.
	 */
	public static function preview_url( string $type, int $course_id = 0 ): string {
		return add_query_arg(
			[
				self::QUERY_VAR => 'preview',
				'type'          => $type,
				'course'        => $course_id,
				'nonce'         => wp_create_nonce( 'va_certificate_preview' ),
			],
			home_url( '/' )
		);
	}

	public function filter_learndash_link( $url, $course_id, $user_id ): string {
		if ( ! $url || ! CourseRoomMapper::department_for_course( (int) $course_id ) ) {
			return (string) $url;
		}

		return self::download_url( (int) $user_id, 'course', (int) $course_id );
	}

	public function maybe_serve(): void {
		if ( ! isset( $_GET[ self::QUERY_VAR ] ) ) {
			return;
		}

		$key   = sanitize_key( wp_unslash( $_GET[ self::QUERY_VAR ] ) );
		$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';

		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		$user_id = get_current_user_id();

		if ( 'preview' === $key ) {
			$this->serve_preview( $user_id, $nonce );

			return;
		}

		if ( ! wp_verify_nonce( $nonce, 'va_certificate_' . $key . '_' . $user_id ) ) {
			wp_die( esc_html__( 'Érvénytelen vagy lejárt letöltési hivatkozás. Nyisd meg újra az oklevelet az Útleveledből.', 'virtualis-akademia' ), '', [ 'response' => 403 ] );
		}

		$type      = 'master' === $key ? 'master' : 'course';
		$course_id = 'course' === $type ? (int) str_replace( 'course-', '', $key ) : 0;
		$record    = $this->issue( $user_id, $type, $course_id );

		if ( ! $record ) {
			wp_die( esc_html__( 'Ez az oklevél még nincs feloldva – előbb teljesítsd a hozzá tartozó tananyagot.', 'virtualis-akademia' ), '', [ 'response' => 403 ] );
		}

		$this->send_pdf( $this->render( $user_id, $record ), self::file_name( $record ) );
	}

	private function serve_preview( int $user_id, string $nonce ): void {
		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( $nonce, 'va_certificate_preview' ) ) {
			wp_die( esc_html__( 'Az oklevélminta megtekintéséhez adminisztrátori jogosultság szükséges.', 'virtualis-akademia' ), '', [ 'response' => 403 ] );
		}

		$type   = isset( $_GET['type'] ) && 'master' === $_GET['type'] ? 'master' : 'course';
		$record = [
			'type'      => $type,
			'course_id' => isset( $_GET['course'] ) ? absint( $_GET['course'] ) : 0,
			'issued_at' => current_time( 'mysql' ),
			'serial'    => 'VA-MINTA-00000',
		];

		$this->send_pdf( $this->render( $user_id, $record ), 'minta-' . self::file_name( $record ) );
	}

	private function send_pdf( string $pdf, string $file_name ): void {
		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
		header( 'Content-Length: ' . strlen( $pdf ) );
		echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- binary PDF.
		exit;
	}
}
