<?php

namespace VirtualisAkademia;

use VirtualisAkademia\Support\ParticipationFields as Fields;

/**
 * The real LearnDash/Ultimate Member dependency enforcement lives in
 * DependencyGuard (admin_init), since a plugin's own activation hook can't
 * reliably self-deactivate it — WordPress core re-marks it active right
 * after the hook fires. This just registers the CPT and flushes rewrite
 * rules; DependencyGuard::check() will deactivate the plugin again on the
 * next admin page load if the dependencies still aren't met.
 */
class Activation {

	public static function run(): void {
		( new PostTypes\DepartmentPostType() )->register_post_type();
		flush_rewrite_rules();
		self::ensure_um_default_forms();
		self::configure_um_login_redirect();
		\VirtualisAkademia\Integration\UltimateMember\EmailTemplates::ensure_subjects();
		self::configure_um_registration_redirect();
		self::ensure_privacy_policy_page();
		self::ensure_register_page();
		self::ensure_registration_fields();
		self::ensure_participation_data_fields();
		self::remove_obsolete_participation_fields();
		self::update_participation_field_wording();
		self::ensure_registration_field_order();
		self::ensure_hungarian_core_page_titles();
		self::ensure_hungarian_um_option_strings();
		self::ensure_hungarian_date_format();
		self::disable_um_profile_content_tabs();
		self::configure_learndash_look();
		self::rename_metaverse_page();
	}

	/**
	 * The product is "Virtuális Akadémia" everywhere -- the 3D page's old
	 * "Metaverzum" title goes (its address stays, links keep working).
	 */
	public static function rename_metaverse_page(): void {
		$page_id = \VirtualisAkademia\Support\MetaversePage::get_id();

		if ( $page_id && 'Metaverzum' === get_post_field( 'post_title', $page_id ) ) {
			wp_update_post( [ 'ID' => $page_id, 'post_title' => 'Virtuális Akadémia' ] );
		}
	}

	/**
	 * LearnDash's own theme colours and one custom label, in the site's
	 * design: the LD30 default blue (#235af3) -- quiz selection frame, the
	 * "lesson complete" banner icon, the quiz "Continue" button -- becomes
	 * the site's cyan, and that button's label ("Continue", a LearnDash
	 * custom label with no translation) reads "Tovább". Only defaults are
	 * replaced; colours or labels someone set in LearnDash → Settings stay.
	 */
	public static function configure_learndash_look(): void {
		$theme = get_option( 'learndash_settings_theme_ld30', [] );

		if ( is_array( $theme ) ) {
			$changed = false;

			foreach ( [ 'color_primary', 'color_secondary' ] as $key ) {
				if ( in_array( strtolower( (string) ( $theme[ $key ] ?? '' ) ), [ '', '#235af3' ], true ) ) {
					$theme[ $key ] = '#00dbe9';
					$changed       = true;
				}
			}

			if ( in_array( strtolower( (string) ( $theme['color_tertiary'] ?? '' ) ), [ '', '#ffd200' ], true ) ) {
				$theme['color_tertiary'] = '#fecb00';
				$changed                 = true;
			}

			if ( $changed ) {
				self::update_learndash_option( 'learndash_settings_theme_ld30', $theme );
			}
		}

		$labels = get_option( 'learndash_settings_custom_labels', [] );
		$labels = is_array( $labels ) ? $labels : [];

		if ( empty( $labels['button_click_here_to_continue'] ) ) {
			$labels['button_click_here_to_continue'] = 'Tovább';
			self::update_learndash_option( 'learndash_settings_custom_labels', $labels );
		}
	}

	/**
	 * LearnDash's settings sections veto any write to their option that
	 * doesn't come from their own admin form (a `pre_update_option_*`
	 * filter) -- set aside for this one programmatic write only.
	 */
	private static function update_learndash_option( string $option, array $value ): void {
		global $wp_filter;

		$hook  = 'pre_update_option_' . $option;
		$saved = $wp_filter[ $hook ] ?? null;
		unset( $wp_filter[ $hook ] );

		update_option( $option, $value );

		if ( $saved ) {
			$wp_filter[ $hook ] = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * The member profile's "Bejegyzések" / "Hozzászólások" tabs list blog
	 * posts and comments -- neither exists for learners on this site, so they
	 * only ever show an empty state. Turned off once; an admin can re-enable
	 * them under Ultimate Member → Beállítások → Megjelenés → Profil menü.
	 */
	private static function disable_um_profile_content_tabs(): void {
		$options = get_option( 'um_options', [] );

		if ( ! is_array( $options ) || get_option( 'va_um_profile_tabs_configured' ) ) {
			return;
		}

		$options['profile_tab_posts']    = 0;
		$options['profile_tab_comments'] = 0;
		update_option( 'um_options', $options );
		update_option( 'va_um_profile_tabs_configured', 1, false );
	}

	/**
	 * WordPress keeps the date/time *format* options from install time, so a
	 * site set up in English shows LearnDash's "Utolsó aktivitás szeptember
	 * 28, 2026 9:52 de." -- a Hungarian month name in an English pattern.
	 * Only WordPress's own English defaults are replaced; a format someone
	 * chose deliberately is left alone.
	 */
	private static function ensure_hungarian_date_format(): void {
		if ( in_array( get_option( 'date_format' ), [ 'F j, Y', 'M j, Y', 'm/d/Y' ], true ) ) {
			update_option( 'date_format', 'Y. F j.' );
		}

		if ( in_array( get_option( 'time_format' ), [ 'g:i a', 'g:i A' ], true ) ) {
			update_option( 'time_format', 'H:i' );
		}
	}

	/**
	 * Ultimate Member's own installer for its default Login/Register/Profile
	 * forms (`install_default_forms()`) silently no-ops unless it runs in an
	 * admin user context (`current_user_can('manage_options')`) -- true for
	 * a normal wp-admin activation click, but not for a site provisioned
	 * entirely through WP-CLI (e.g. `wp-env start`, which activates plugins
	 * with no logged-in user at all). Without those forms, the landing
	 * page's embedded `[ultimatemember form_id="..."]` shortcodes -- and
	 * Ultimate Member's own /login//register/ pages -- render blank.
	 */
	private static function ensure_um_default_forms(): void {
		if ( ! function_exists( 'UM' ) || ! class_exists( '\um\core\Setup' ) ) {
			return;
		}

		$forms_exist = get_posts( [
			'post_type'      => 'um_form',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		] );

		if ( $forms_exist ) {
			return;
		}

		$admin_ids = get_users( [ 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ] );

		if ( empty( $admin_ids ) ) {
			return;
		}

		$original_user_id = get_current_user_id();

		wp_set_current_user( $admin_ids[0] );
		UM()->setup()->install_default_forms();
		wp_set_current_user( $original_user_id );
	}

	/**
	 * Ultimate Member's default role behaviour after login is "go to my own
	 * profile" -- on this single-purpose site the only real destination is
	 * the metaverse, so the default new-member role needs "redirect to
	 * custom URL" instead for Integration\UltimateMember\LoginRedirect's
	 * `um_login_redirect_url` filter to ever run. Only touched once (skipped
	 * if already set), so a client who deliberately changes it back later
	 * via UM's own Role admin UI won't have it silently reset by a later
	 * reactivation.
	 */
	private static function configure_um_login_redirect(): void {
		$role = get_option( 'default_role', 'subscriber' );
		$key  = "um_role_{$role}_meta";
		$meta = get_option( $key );

		if ( ! is_array( $meta ) || 'redirect_url' === ( $meta['_um_after_login'] ?? null ) ) {
			return;
		}

		$meta['_um_after_login'] = 'redirect_url';
		update_option( $key, $meta );
	}

	/**
	 * Unlike login, Ultimate Member doesn't expose a filter over the
	 * post-registration redirect URL for the auto-login ("approved") case --
	 * the target URL is read straight from role meta (`um_check_user_status()`
	 * in um-actions-register.php), so it has to be baked in here rather than
	 * resolved dynamically per-request the way LoginRedirect resolves the
	 * login target. Points straight at the metaverse (whose opening
	 * transition greets the new member) instead of Ultimate Member's own
	 * default -- the member's bare, unbranded profile page -- or a separate
	 * welcome screen with one more click. Re-derives the URL on every activation (rather
	 * than a true run-once flag) so a later permalink change doesn't leave a
	 * stale link stuck in role meta.
	 */
	private static function configure_um_registration_redirect(): void {
		$role = get_option( 'default_role', 'subscriber' );
		$key  = "um_role_{$role}_meta";
		$meta = get_option( $key );

		if ( ! is_array( $meta ) ) {
			return;
		}

		// Straight into the metaverse: its opening transition greets the new
		// member ("Szia, <keresztnév>! Örülünk, hogy látunk!" -- see
		// Frontend\Greeting), so no separate welcome screen/click in between.
		$welcome_url = \VirtualisAkademia\Support\MetaversePage::get_url();

		if ( ! $welcome_url ) {
			return;
		}

		if ( 'redirect_url' === ( $meta['_um_auto_approve_act'] ?? null )
			&& ( $meta['_um_auto_approve_url'] ?? null ) === $welcome_url
		) {
			return;
		}

		$meta['_um_auto_approve_act'] = 'redirect_url';
		$meta['_um_auto_approve_url'] = $welcome_url;
		update_option( $key, $meta );
	}

	/**
	 * WordPress auto-creates a draft "Privacy Policy" page full of English
	 * boilerplate ("Suggested text: ...") the moment a site is set up --
	 * publishing that as-is would be worse than not having the page at all,
	 * since a visitor would reasonably take it for the site's real policy.
	 * Renamed and replaced with an honest, clearly-labelled Hungarian
	 * placeholder instead; a real one still needs writing by whoever
	 * actually owns the site's data practices. Only touched once (skipped
	 * once it's been published), so the client's own edits are never
	 * overwritten by a later reactivation.
	 */
	private static function ensure_privacy_policy_page(): void {
		$page_id = (int) get_option( 'wp_page_for_privacy_policy' );

		if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
			return;
		}

		if ( ! $page_id ) {
			$page_id = wp_insert_post( [
				'post_type'   => 'page',
				'post_title'  => 'Adatkezelési tájékoztató',
				'post_status' => 'draft',
			] );
		}

		if ( ! $page_id || is_wp_error( $page_id ) ) {
			return;
		}

		wp_update_post( [
			'ID'           => $page_id,
			'post_title'   => 'Adatkezelési tájékoztató',
			'post_status'  => 'publish',
			'post_content' => "<!-- wp:paragraph -->\n<p><strong>Ez az oldal egyelőre helyőrző szöveget tartalmaz.</strong> "
				. 'A végleges, az oldal tényleges adatkezelési gyakorlatát pontosan leíró adatkezelési '
				. 'tájékoztatót az oldal üzemeltetőjének kell elkészítenie (szükség esetén jogi '
				. "szakértő bevonásával), mielőtt éles felhasználói regisztráció történne.</p>\n<!-- /wp:paragraph -->",
		] );

		update_option( 'wp_page_for_privacy_policy', $page_id );
	}

	/**
	 * Creates the dedicated [va_register] page (see ShortcodeRegister).
	 * Deliberately mirrors ensure_privacy_policy_page()'s pattern -- an
	 * option remembering the page's own ID -- rather than HomePage/
	 * MetaversePage's pure shortcode-scan lookup: unlike those two (which
	 * only ever look up a page a client already created by hand), this
	 * method also *creates* the page, and re-running a `get_posts()` shortcode
	 * scan as the guard immediately after an earlier run's `wp_insert_post()`
	 * was observed (on at least one environment) to occasionally miss the
	 * just-created page and insert a duplicate. A stored ID is a single,
	 * direct `get_option()` lookup with no such race.
	 */
	private static function ensure_register_page(): void {
		$page_id = (int) get_option( 'va_register_page_id' );

		if ( $page_id && 'trash' !== get_post_status( $page_id ) ) {
			return;
		}

		// A client (or an earlier version of this method, before the option
		// existed) may already have a [va_register] page we just don't have
		// the ID for yet -- adopt it instead of creating a second one.
		$existing = \VirtualisAkademia\Support\RegisterPage::get_id();

		if ( $existing ) {
			update_option( 'va_register_page_id', $existing );

			return;
		}

		$page_id = wp_insert_post( [
			'post_type'    => 'page',
			'post_title'   => 'Regisztráció',
			'post_status'  => 'publish',
			'post_content' => '<!-- wp:shortcode -->[va_register]<!-- /wp:shortcode -->',
		] );

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_option( 'va_register_page_id', $page_id );
		}
	}

	/**
	 * Adds the two fields the registration form legally/product-wise needs
	 * on top of Ultimate Member's own defaults: a required consent checkbox
	 * for the Privacy Policy, and a required birth date (paired with
	 * Integration\UltimateMember\AgeGate, which rejects under-16
	 * registrations using this same field). Skipped once already present,
	 * so an admin who removes/edits either field via UM's own form builder
	 * doesn't have it silently re-added on the next reactivation.
	 */
	private static function ensure_registration_fields(): void {
		$forms         = get_option( 'um_core_forms', [] );
		$register_form = (int) ( $forms['register'] ?? 0 );

		if ( ! $register_form ) {
			return;
		}

		$fields = get_post_meta( $register_form, '_um_custom_fields', true );

		if ( ! is_array( $fields ) ) {
			return;
		}

		if ( isset( $fields['birth_date'] ) || isset( $fields['gdpr_consent'] ) ) {
			return;
		}

		$next_position = 1;

		foreach ( $fields as $field ) {
			if ( is_array( $field ) && isset( $field['position'] ) ) {
				$next_position = max( $next_position, (int) $field['position'] + 1 );
			}
		}

		$layout = [ 'in_row' => '_um_row_1', 'in_sub_row' => '0', 'in_column' => '1', 'in_group' => '' ];

		$fields['birth_date'] = array_merge( [
			'title'    => 'Születési dátum',
			'metakey'  => 'birth_date',
			'type'     => 'date',
			'label'    => 'Születési dátum',
			'required' => 1,
			'public'   => 0,
			'editable' => true,
			'years'    => 100,
			'years_x'  => 'past',
			'position' => (string) $next_position,
		], $layout );

		$fields['gdpr_consent'] = array_merge( [
			'title'    => 'Adatkezelési hozzájárulás',
			'metakey'  => 'gdpr_consent',
			'type'     => 'checkbox',
			'label'    => '',
			'options'  => [ 'Elfogadom az Adatkezelési tájékoztatóban foglaltakat' ],
			'required' => 1,
			'public'   => 0,
			'editable' => false,
			'position' => (string) ( $next_position + 1 ),
		], $layout );

		update_post_meta( $register_form, '_um_custom_fields', $fields );
	}

	/**
	 * The "Résztvevői véleménygyűjtő, és adatbekérő lap" the project supplied
	 * (media/files/) lists the data points a participant must provide for the
	 * grant's own reporting obligations -- these are added as further
	 * required registration fields, stored as ordinary user meta so
	 * ProfileRestController can read them back for the profile screen and
	 * ParticipantsExport can pull them into the funder's CSV export. Kept as
	 * a second pass after ensure_registration_fields() (not folded into it)
	 * because it has its own independent "already added" guard -- a site
	 * that already has these from an earlier activation is left alone even
	 * if an admin has since edited/reordered them.
	 *
	 * The sheet's own "Teljes neve" and a second "chat program" e-mail field
	 * were dropped per client feedback as redundant with the existing
	 * Vezetéknév/Keresztnév fields and the account's own e-mail --
	 * see remove_obsolete_participation_fields() for sites that already
	 * picked up the original, larger set.
	 */
	private static function ensure_participation_data_fields(): void {
		$forms         = get_option( 'um_core_forms', [] );
		$register_form = (int) ( $forms['register'] ?? 0 );

		if ( ! $register_form ) {
			return;
		}

		$fields = get_post_meta( $register_form, '_um_custom_fields', true );

		if ( ! is_array( $fields ) || isset( $fields['phone_number'] ) ) {
			return;
		}

		$next_position = 1;

		foreach ( $fields as $field ) {
			if ( is_array( $field ) && isset( $field['position'] ) ) {
				$next_position = max( $next_position, (int) $field['position'] + 1 );
			}
		}

		$layout = [ 'in_row' => '_um_row_1', 'in_sub_row' => '0', 'in_column' => '1', 'in_group' => '' ];
		$pos    = $next_position;

		$new_fields = [
			'phone_number' => array_merge( [
				'title'    => Fields::TEXT_FIELD_LABELS['phone_number'],
				'metakey'  => 'phone_number',
				'type'     => 'text',
				'label'    => Fields::TEXT_FIELD_LABELS['phone_number'],
				'required' => 0,
				'public'   => 0,
				'editable' => true,
			], $layout ),
			'birth_place'  => array_merge( [
				'title'    => Fields::TEXT_FIELD_LABELS['birth_place'],
				'metakey'  => 'birth_place',
				'type'     => 'text',
				'label'    => Fields::TEXT_FIELD_LABELS['birth_place'],
				'required' => 1,
				'public'   => 0,
				'editable' => true,
			], $layout ),
			'residence'    => array_merge( [
				'title'    => Fields::TEXT_FIELD_LABELS['residence'],
				'metakey'  => 'residence',
				'type'     => 'text',
				'label'    => Fields::TEXT_FIELD_LABELS['residence'],
				'required' => 1,
				'public'   => 0,
				'editable' => true,
			], $layout ),
			'citizenship'  => array_merge( [
				'title'    => Fields::TEXT_FIELD_LABELS['citizenship'],
				'metakey'  => 'citizenship',
				'type'     => 'text',
				'label'    => Fields::TEXT_FIELD_LABELS['citizenship'],
				'required' => 1,
				'public'   => 0,
				'editable' => true,
			], $layout ),
			Fields::STATUS_KEY => array_merge( [
				'title'    => 'Hátrányos helyzet',
				'metakey'  => Fields::STATUS_KEY,
				'type'     => 'checkbox',
				'label'    => Fields::STATUS_LABEL,
				'options'  => Fields::OPTIONS,
				'required' => 1,
				'public'   => 0,
				'editable' => true,
			], $layout ),
			// Rendered by the register page script as the free-text reveal
			// under the "Egyéb" option, not as a standalone question.
			Fields::OTHER_KEY  => array_merge( [
				'title'       => Fields::OTHER_LABEL,
				'metakey'     => Fields::OTHER_KEY,
				'type'        => 'text',
				'label'       => Fields::OTHER_LABEL,
				'placeholder' => Fields::OTHER_PLACEHOLDER,
				'required'    => 0,
				'public'      => 0,
				'editable'    => true,
			], $layout ),
		];

		foreach ( $new_fields as $key => $field ) {
			$field['position'] = (string) $pos;
			$fields[ $key ]    = $field;
			$pos++;
		}

		// The Privacy Policy consent checkbox must stay the very last field
		// regardless of where these were inserted.
		if ( isset( $fields['gdpr_consent'] ) ) {
			$fields['gdpr_consent']['position'] = (string) $pos;
		}

		update_post_meta( $register_form, '_um_custom_fields', $fields );
	}

	/**
	 * A site that already ran ensure_participation_data_fields() before the
	 * "Teljes neve" / second e-mail fields were dropped (see that method's
	 * docblock) has them permanently stuck in `_um_custom_fields` -- that
	 * earlier method's own "already added" guard means it will never touch
	 * the form again to remove them, so this actively strips just those two
	 * keys, every activation, rather than a run-once flag. Already-collected
	 * user meta from anyone who registered under the old form is left in
	 * place (not shown or exported anymore, but not deleted either) -- undoing
	 * data someone already submitted isn't this method's call to make.
	 */
	private static function remove_obsolete_participation_fields(): void {
		$forms         = get_option( 'um_core_forms', [] );
		$register_form = (int) ( $forms['register'] ?? 0 );

		if ( ! $register_form ) {
			return;
		}

		$fields = get_post_meta( $register_form, '_um_custom_fields', true );

		if ( ! is_array( $fields ) ) {
			return;
		}

		$changed = false;

		foreach ( [ 'id_full_name', 'chat_email' ] as $obsolete_key ) {
			if ( isset( $fields[ $obsolete_key ] ) ) {
				unset( $fields[ $obsolete_key ] );
				$changed = true;
			}
		}

		if ( $changed ) {
			update_post_meta( $register_form, '_um_custom_fields', $fields );
		}
	}

	/**
	 * Brings an already-provisioned registration form up to the current
	 * ParticipationFields wording: informal, plain-noun labels, the
	 * disadvantage checkbox list with its closing "Egyéb" option, and the
	 * free-text field repurposed as that option's reveal (optional, right
	 * after the list). Re-applied on every activation -- the guard in
	 * ensure_participation_data_fields() means that method never touches an
	 * existing form again. Answers already stored under a renamed option
	 * string are rewritten too, so profile / CSV export stay consistent.
	 */
	private static function update_participation_field_wording(): void {
		$forms         = get_option( 'um_core_forms', [] );
		$register_form = (int) ( $forms['register'] ?? 0 );

		if ( ! $register_form ) {
			return;
		}

		$fields = get_post_meta( $register_form, '_um_custom_fields', true );

		if ( ! is_array( $fields ) || ! isset( $fields[ Fields::STATUS_KEY ] ) ) {
			return;
		}

		foreach ( Fields::TEXT_FIELD_LABELS as $key => $label ) {
			if ( isset( $fields[ $key ] ) ) {
				$fields[ $key ]['title'] = $label;
				$fields[ $key ]['label'] = $label;
			}
		}

		$fields[ Fields::STATUS_KEY ]['label']   = Fields::STATUS_LABEL;
		$fields[ Fields::STATUS_KEY ]['options'] = Fields::OPTIONS;

		if ( isset( $fields[ Fields::OTHER_KEY ] ) ) {
			$fields[ Fields::OTHER_KEY ]['title']       = Fields::OTHER_LABEL;
			$fields[ Fields::OTHER_KEY ]['label']       = Fields::OTHER_LABEL;
			$fields[ Fields::OTHER_KEY ]['placeholder'] = Fields::OTHER_PLACEHOLDER;
			$fields[ Fields::OTHER_KEY ]['required']    = 0;
			$fields[ Fields::OTHER_KEY ]['position']    = (string) ( (int) $fields[ Fields::STATUS_KEY ]['position'] + 1 );
		}

		update_post_meta( $register_form, '_um_custom_fields', $fields );

		global $wpdb;

		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s",
			Fields::STATUS_KEY
		) );

		foreach ( $rows as $row ) {
			$value = maybe_unserialize( $row->meta_value );

			if ( ! is_array( $value ) ) {
				continue;
			}

			$renamed = array_map( fn( $option ) => Fields::RENAMED_OPTIONS[ $option ] ?? $option, $value );

			if ( $renamed !== $value ) {
				update_user_meta( (int) $row->user_id, Fields::STATUS_KEY, $renamed );
			}
		}
	}

	/**
	 * Ultimate Member ships First Name before Last Name by default; Hungarian
	 * name order (and the client's preference) is family name first. Swaps
	 * the two fields' `position` values rather than a true run-once flag, so
	 * it keeps correcting the order even if a future UM update or reactivation
	 * re-seeds the default fields -- a client who deliberately reverses it
	 * again via UM's own form builder would have that undone on the next
	 * activation, but that's an acceptable trade-off for a field order this
	 * unlikely to be intentionally reverted.
	 */
	private static function ensure_registration_field_order(): void {
		$forms         = get_option( 'um_core_forms', [] );
		$register_form = (int) ( $forms['register'] ?? 0 );

		if ( ! $register_form ) {
			return;
		}

		$fields = get_post_meta( $register_form, '_um_custom_fields', true );

		if ( ! is_array( $fields )
			|| ! isset( $fields['first_name']['position'], $fields['last_name']['position'] )
		) {
			return;
		}

		if ( (int) $fields['last_name']['position'] < (int) $fields['first_name']['position'] ) {
			return;
		}

		$first_position                   = $fields['first_name']['position'];
		$fields['first_name']['position'] = $fields['last_name']['position'];
		$fields['last_name']['position']  = $first_position;

		update_post_meta( $register_form, '_um_custom_fields', $fields );
	}

	/**
	 * Ultimate Member's own core pages (Login, Register, Account, ...) get
	 * their titles set once, in English, at creation time -- a stored post
	 * field, not a runtime string, so no `gettext` filter can touch it (that
	 * only translates strings passed through `__()`/`_e()` at render time).
	 * Retitled here rather than at `install_default_pages()` time so this
	 * still runs even against a site whose pages already existed before this
	 * method was added. Only touched once per page (skipped once retitled),
	 * so a client's own rename sticks across later reactivations.
	 */
	private static function ensure_hungarian_core_page_titles(): void {
		// slug => [ original English default UM ships, our Hungarian replacement ].
		$titles = [
			'user'           => [ 'User', 'Felhasználó' ],
			'login'          => [ 'Login', 'Bejelentkezés' ],
			'register'       => [ 'Register', 'Regisztráció' ],
			'members'        => [ 'Members', 'Tagok' ],
			'logout'         => [ 'Logout', 'Kijelentkezés' ],
			'account'        => [ 'Account', 'Fiók' ],
			'password-reset' => [ 'Password Reset', 'Jelszó visszaállítása' ],
		];

		foreach ( $titles as $slug => [ $english_default, $hungarian_title ] ) {
			$pages = get_posts( [
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'meta_key'       => '_um_core',
				'meta_value'     => $slug,
			] );

			$page = $pages[0] ?? null;

			// Only ever replaces UM's own untouched English default -- a
			// title that's already something else (our Hungarian one from a
			// prior activation, or a client's own rename) is left alone.
			if ( ! $page || $page->post_title !== $english_default ) {
				continue;
			}

			wp_update_post( [ 'ID' => $page->ID, 'post_title' => $hungarian_title ] );
		}
	}

	/**
	 * A handful of Ultimate Member strings are resolved through `__()` once,
	 * at first activation, and the *result* stored verbatim in `um_options`
	 * -- e.g. the Delete Account confirmation text -- so no `gettext` filter
	 * ever sees them again at render time (FormTranslations only catches
	 * strings translated fresh on every request). Same "only replace UM's
	 * own untouched English default" guard as the page titles above.
	 */
	private static function ensure_hungarian_um_option_strings(): void {
		$options = get_option( 'um_options', [] );

		if ( ! is_array( $options ) ) {
			return;
		}

		$replacements = [
			'delete_account_text'                  => [
				'Are you sure you want to delete your account? This will erase all of your account data from the site. To delete your account enter your password below.',
				'Biztosan törölni szeretnéd a fiókodat? Ez véglegesen törli az összes fiókadatodat az oldalról. A törléshez add meg lent a jelszavadat.',
			],
			'delete_account_no_pass_required_text' => [
				'Are you sure you want to delete your account? This will erase all of your account data from the site. To delete your account, click on the button below.',
				'Biztosan törölni szeretnéd a fiókodat? Ez véglegesen törli az összes fiókadatodat az oldalról. A törléshez kattints a lenti gombra.',
			],
		];

		$changed = false;

		foreach ( $replacements as $key => [ $english_default, $hungarian_text ] ) {
			if ( ( $options[ $key ] ?? null ) === $english_default ) {
				$options[ $key ] = $hungarian_text;
				$changed         = true;
			}
		}

		if ( $changed ) {
			update_option( 'um_options', $options );
		}
	}
}
