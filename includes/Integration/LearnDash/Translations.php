<?php

namespace VirtualisAkademia\Integration\LearnDash;

use VirtualisAkademia\Hookable;

/**
 * The LearnDash course experience is Hungarian, but a handful of strings in
 * LearnDash core and (mostly) the "LearnDash Ratings, Reviews and Feedback"
 * add-on (wdm-course-review) ship without a Hungarian translation and fall
 * back to English on the frontend ("Write a Review", "Sort by", "No Reviews
 * Found!", ...).
 *
 * Rather than maintain a full .mo file for a third-party plugin, this filters
 * `gettext` for just those two text domains and swaps the known English
 * source strings for Hungarian. Scoped tightly to the two domains so it adds
 * a cheap `isset()` to every other translation call and nothing more.
 */
class Translations implements Hookable {

	/**
	 * @var array<string, array<string, string>> domain => ( english => hungarian )
	 */
	private const MAP = [
		'learndash' => [
			'Skip to content'           => 'Ugrás a tartalomra',
			// LD's "presenter mode" hides the sidebar + header for distraction-
			// free reading -- "előadó" (presenter/lecturer) misleads learners.
			'Presenter Mode'            => 'Olvasó mód',
			// LD30 presenter-mode toggle stacks "Presenter Mode" over a bare
			// "Enter" / "Exit" -- both plain esc_html_e( ..., 'learndash' ).
			'Enter'                     => 'Bekapcsolás',
			'Exit'                      => 'Kikapcsolás',
			'Not started'               => 'Nincs elkezdve',
			'Breadcrumbs'               => 'Morzsamenü',
			'Correct answer'            => 'Helyes válasz',
			'Toggle sidebar navigation' => 'Oldalsáv be- és kikapcsolása',
			'Expand'                    => 'Kibontás',
			'Collapse'                  => 'Összecsukás',
			'Expand All'                => 'Összes kibontása',
			'Continue'                  => 'Tovább',
			// Lesson-complete banner (LD30 helpers); %s = "lecke" / "téma".
			'Great Job! You\'ve completed this %s - Keep it going!' => 'Szép munka! Ez a %s kész – csak így tovább!',
			'Congratulations! You just completed %s.' => 'Gratulálunk! Elvégezted: %s.',
			'Collapse All'              => 'Összes összecsukása',
		],
		'wdm_ld_course_review' => [
			'Ratings and Reviews'                     => 'Értékelések és vélemények',
			'Ratings & Reviews'                       => 'Értékelések és vélemények',
			'Avg. Rating'                             => 'Átlagos értékelés',
			'Ratings'                                 => 'értékelés',
			'Rating'                                  => 'értékelés',
			// sprintf( __( '...%d... Ratings', ... ), $count ) -- the count label
			// under the average score.
			'<span class="reviews-total">%d</span> Ratings' => '<span class="reviews-total">%d</span> értékelés',
			'Course Rating'                           => 'Tanfolyam értékelése',
			'Your Rating'                             => 'Az értékelésed',
			'Your Review'                             => 'A véleményed',
			'Review'                                  => 'Vélemény',
			'Write a Review'                          => 'Vélemény írása',
			'Edit Review'                             => 'Vélemény szerkesztése',
			'Edit your Review'                        => 'Vélemény szerkesztése',
			'Login to Review'                         => 'A véleményezéshez jelentkezz be',
			'Delete Your Review?'                     => 'Törlöd a véleményed?',
			'Yes, Delete My Review'                   => 'Igen, töröljük a véleményem',
			"What's your experience? We'd love to know!" => 'Milyen a véleményed? Kíváncsiak vagyunk rá!',
			'How would you rate this course?'         => 'Hogyan értékelnéd ezt a tanfolyamot?',
			'Sort by'                                 => 'Rendezés',
			'Filter by'                              => 'Szűrés',
			'All Stars'                               => 'Összes értékelés',
			'1 star only'                             => 'Csak 1 csillag',
			'2 star only'                             => 'Csak 2 csillag',
			'3 star only'                             => 'Csak 3 csillag',
			'4 star only'                             => 'Csak 4 csillag',
			'5 star only'                             => 'Csak 5 csillag',
			'Most Recent'                             => 'Legfrissebb',
			'Top Ratings'                             => 'Legjobb értékelés',
			'No Reviews Found!'                       => 'Még nincs vélemény.',
			'No Reviews Found'                        => 'Még nincs vélemény',
			'No Review Found.'                        => 'Még nincs vélemény.',
			'Show more reviews'                       => 'További vélemények',
			'Awful, not what I expected at all'       => 'Szörnyű, egyáltalán nem ezt vártam',
			'Poor, pretty disappointed'              => 'Gyenge, elég csalódott vagyok',
			'Average, could be better'               => 'Közepes, lehetne jobb',
			'Good, what I expected'                   => 'Jó, ezt vártam',
			'Amazing, above expectations!'           => 'Kiváló, a várakozásaimon felül!',
			'Not Rated'                               => 'Még nincs értékelve',
			// Course feedback button + popup (class-feedback-handler.php).
			'Provide your feedback'                   => 'Visszajelzés küldése',
			'Your feedback on %s'                     => 'Visszajelzésed a tanfolyamról: %s',
			'Please provide feedback'                 => 'Kérjük, írd meg a visszajelzésedet',
			'*This feedback will be privately emailed to the author.' => '*Ezt a visszajelzést csak a tananyag készítői kapják meg, e-mailben.',
			'Send Feedback'                           => 'Visszajelzés küldése',
			'Thank you for your feedback!'            => 'Köszönjük a visszajelzésedet!',
			'Sorry! You have already submitted the feedback form for this course.' => 'Ehhez a tanfolyamhoz már küldtél visszajelzést.',
			'Please wait'                             => 'Kis türelmet…',
			'Something went wrong!'                   => 'Hiba történt, kérjük, próbáld újra!',
			'remaining character(s)'                  => 'karakter maradt',
			'Should not exceed '                      => 'Legfeljebb ',
			' characters.'                            => ' karakter lehet.',
			'If you want to review/rate the %1$s please %2$s' => 'Ha értékelni szeretnéd ezt a tanfolyamot (%1$s), %2$s',
			'click here'                              => 'kattints ide',
			'Failed from timeout'                     => 'Időtúllépés miatt nem sikerült',
			'Close'                                   => 'Bezárás',
		],
	];

	/**
	 * Awkward strings from LearnDash's own hu_HU .mo file, keyed by the
	 * *translated* text (the English source varies with LD's internal
	 * context strings, the Hungarian result is what learners actually see).
	 *
	 * @var array<string, array<string, string>>
	 */
	private const FIXES = [
		'learndash' => [
			'Kérdés áttekintés' => 'Kérdések áttekintése',
			'Kinyitás'          => 'Kibontás',
			'Becsukás'          => 'Összecsukás',
			'Előadó mód'        => 'Olvasó mód',
			// The product calls them "oklevél" everywhere else (passport, e-mail, PDF).
			'Tanúsítvány letöltése' => 'Oklevél letöltése',
			'Tanúsítvány'           => 'Oklevél',
			'Megszereztél egy tanúsítványt!' => 'Megszerezted a tanszék oklevelét!',
			// Quiz result: "9 / 10 Kérdések válaszod volt helyes".
			'%1$s / %2$s %3$s válaszod volt helyes' => '%1$s / %2$s kérdésre válaszoltál helyesen',
		],
	];

	/**
	 * Hungarian inflects the step noun ("a leckéhez", "a tanfolyamhoz"), so
	 * LD's "Back to %s" / "Next %s" templates -- which drop in the
	 * capitalised custom label ("Következő Kvíz", "Vissza: Tanfolyam") --
	 * are replaced whole, per step type.
	 */
	private const STEP_LABELS = [
		'next'     => [
			'sfwd-lessons' => 'Következő lecke',
			'sfwd-topic'   => 'Következő téma',
			'sfwd-quiz'    => 'Következő kvíz',
		],
		'previous' => [
			'sfwd-lessons' => 'Előző lecke',
			'sfwd-topic'   => 'Előző téma',
			'sfwd-quiz'    => 'Előző kvíz',
		],
		'back'     => [
			'sfwd-courses' => 'Vissza a tanfolyamhoz',
			'sfwd-lessons' => 'Vissza a leckéhez',
			'sfwd-topic'   => 'Vissza a témához',
			'sfwd-quiz'    => 'Vissza a kvízhez',
		],
	];

	public function register(): void {
		add_filter( 'gettext', [ $this, 'translate' ], 20, 3 );
		add_filter( 'gettext_with_context', [ $this, 'translate_with_context' ], 20, 4 );

		add_filter( 'learndash_get_label_course_step_next', fn( $label, $type ) => self::STEP_LABELS['next'][ $type ] ?? $label, 10, 2 );
		add_filter( 'learndash_get_label_course_step_previous', fn( $label, $type ) => self::STEP_LABELS['previous'][ $type ] ?? $label, 10, 2 );
		add_filter(
			'learndash_get_label_course_step_back',
			fn( $label, $type, $plural = false ) => $plural ? $label : ( self::STEP_LABELS['back'][ $type ] ?? $label ),
			10,
			3
		);
	}

	/**
	 * @param string $translation Text already run through WP's own .mo lookup.
	 * @param string $text        Original (English) source string.
	 * @param string $domain      Text domain of the call.
	 */
	public function translate( $translation, $text, $domain ) {
		return self::MAP[ $domain ][ $text ] ?? self::FIXES[ $domain ][ $translation ] ?? $translation;
	}

	/**
	 * @param string $translation
	 * @param string $text
	 * @param string $context
	 * @param string $domain
	 */
	public function translate_with_context( $translation, $text, $context, $domain ) {
		return self::MAP[ $domain ][ $text ] ?? self::FIXES[ $domain ][ $translation ] ?? $translation;
	}
}
