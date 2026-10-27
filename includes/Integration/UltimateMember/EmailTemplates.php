<?php

namespace VirtualisAkademia\Integration\UltimateMember;

use VirtualisAkademia\Hookable;
use VirtualisAkademia\Support\EmailLayout;

/**
 * Hungarian, branded versions of Ultimate Member's e-mail notifications
 * (UM ships English only and has no Hungarian language pack).
 *
 * - Bodies: UM's plugin templates are swapped for templates/um-email.php,
 *   which renders body() below in the shared EmailLayout card. A template
 *   someone has overridden in the theme (ultimate-member/email/) is left
 *   alone.
 * - Subjects live in UM's own settings (`<slug>_sub`), so they stay
 *   editable in UM → Settings → Email; ensure_subjects() only replaces the
 *   ones still at UM's English default.
 * - {first_name}, {password_reset_link}, ... are UM placeholders, replaced
 *   by UM after rendering.
 */
class EmailTemplates implements Hookable {

	/** slug => [ UM's English default subject, Hungarian subject ] */
	private const SUBJECTS = [
		'welcome_email'           => [ 'Welcome to {site_name}!', 'Üdv a Virtuális Akadémián!' ],
		'checkmail_email'         => [ 'Please activate your account', 'Erősítsd meg a regisztrációdat' ],
		'pending_email'           => [ '[{site_name}] New user account', 'A regisztrációd jóváhagyásra vár' ],
		'approved_email'          => [ 'Your account at {site_name} is now active', 'Aktív lett a fiókod – {site_name}' ],
		'rejected_email'          => [ 'Your account has been rejected', 'A regisztrációdat nem tudtuk elfogadni' ],
		'inactive_email'          => [ 'Your account has been deactivated', 'A fiókodat felfüggesztettük' ],
		'deletion_email'          => [ 'Your account has been deleted', 'A fiókodat töröltük' ],
		'resetpw_email'           => [ 'Reset your password', 'Jelszó visszaállítása – {site_name}' ],
		'changedpw_email'         => [ 'Your {site_name} password has been changed', 'Megváltozott a jelszavad – {site_name}' ],
		'changedaccount_email'    => [ 'Your account at {site_name} was updated', 'Frissültek a fiókod adatai – {site_name}' ],
		'notification_new_user'   => [ '[{site_name}] New user account', '[{site_name}] Új regisztráció' ],
		'notification_review'     => [ '[{site_name}] New user awaiting review', '[{site_name}] Új regisztráció vár jóváhagyásra' ],
		'notification_deletion'   => [ '[{site_name}] Account deleted', '[{site_name}] Egy tag törölte a fiókját' ],
		'suspicious-activity'     => [ '[{site_name}] Suspicious Account Activity', '[{site_name}] Gyanús fióktevékenység' ],
	];

	public function register(): void {
		add_filter( 'um_locate_email_template', [ $this, 'locate' ], 10, 2 );
		add_filter( 'um_email_template_body_attrs', [ $this, 'body_attrs' ] );
	}

	public function locate( $template, $slug ) {
		if ( ! defined( 'UM_PATH' ) || ! isset( self::SUBJECTS[ $slug ] ) ) {
			return $template;
		}

		// Only replace UM's bundled file -- never a theme override.
		if ( 0 !== strpos( wp_normalize_path( (string) $template ), wp_normalize_path( UM_PATH ) ) ) {
			return $template;
		}

		return VA_PLUGIN_DIR . '/templates/um-email.php';
	}

	public function body_attrs(): string {
		return 'style="margin:0;padding:0;background:#060e20;-webkit-font-smoothing:antialiased;"';
	}

	/**
	 * Hungarian subjects for every template still on UM's English default,
	 * plus a readable sender name. Idempotent (Activation::run()).
	 */
	public static function ensure_subjects(): void {
		$options = get_option( 'um_options' );

		if ( ! is_array( $options ) ) {
			return;
		}

		$changed = false;

		foreach ( self::SUBJECTS as $slug => [ $english, $hungarian ] ) {
			$current = $options[ $slug . '_sub' ] ?? '';

			if ( '' === $current || $english === $current ) {
				$options[ $slug . '_sub' ] = $hungarian;
				$changed                   = true;
			}
		}

		if ( ( $options['mail_from'] ?? '' ) === 'virtualis-akademia' || '' === ( $options['mail_from'] ?? '' ) ) {
			$options['mail_from'] = 'Virtuális Akadémia';
			$changed              = true;
		}

		if ( $changed ) {
			update_option( 'um_options', $options );
		}
	}

	public static function body( string $slug ): string {
		$contact  = EmailLayout::link( 'mailto:{admin_email}', 'írj nekünk' );
		$help     = 'Ha bármi kérdésed van, ' . $contact . ' a {admin_email} címre.';
		$not_you  = 'Ha nem te voltál, kérjük, azonnal ' . $contact . ' a {admin_email} címre.';
		$greeting = 'Szia, {first_name}!';

		$defs = [
			'welcome_email'         => [
				'heading'    => $greeting,
				'paragraphs' => [
					'Köszönjük, hogy regisztráltál! A fiókod aktív: már beléphetsz az Akadémia háromdimenziós épületébe, és elkezdheted az első tanszéket.',
					'Négy tanszék vár – Demokrácia, Érdekérvényesítés, Európai Unió és Civil szektor. Minden elvégzett tanszékért pecsétet kapsz az Útleveledbe és oklevelet, mind a négyért pedig Mester Oklevelet.',
				],
				'details'    => [
					'E-mail-cím'    => '{email}',
					'Felhasználónév' => '{username}',
				],
				'cta'        => [ 'url' => '{action_url}', 'label' => '{action_title}' ],
				'after'      => [ $help ],
			],
			'checkmail_email'       => [
				'heading'    => $greeting,
				'paragraphs' => [
					'Köszönjük, hogy regisztráltál a Virtuális Akadémiára! Már csak egy lépés van hátra: erősítsd meg az e-mail-címedet az alábbi gombbal, és aktiváljuk a fiókodat.',
				],
				'cta'        => [ 'url' => '{account_activation_link}', 'label' => 'Fiók aktiválása' ],
				'after'      => [ 'Ha nem te regisztráltál, nyugodtan hagyd figyelmen kívül ezt a levelet.', $help ],
			],
			'pending_email'         => [
				'heading'    => $greeting,
				'paragraphs' => [
					'Köszönjük, hogy regisztráltál! A regisztrációdat egy munkatársunk hamarosan átnézi – kérjük, addig légy türelemmel.',
					'Amint jóváhagytuk, e-mailben értesítünk, és beléphetsz az Akadémiára.',
				],
				'after'      => [ $help ],
			],
			'approved_email'        => [
				'heading'    => $greeting,
				'paragraphs' => [
					'Jó hír: jóváhagytuk a regisztrációdat, a fiókod aktív. Már beléphetsz az Akadémia háromdimenziós épületébe.',
				],
				'details'    => [
					'E-mail-cím'       => '{email}',
					'Felhasználónév'   => '{username}',
					'Jelszó beállítása' => EmailLayout::link( '{password_reset_link}', 'Új jelszó megadása' ),
				],
				'cta'        => [ 'url' => '{login_url}', 'label' => 'Belépés az Akadémiára' ],
				'after'      => [ $help ],
			],
			'rejected_email'        => [
				'heading'    => $greeting,
				'paragraphs' => [
					'Köszönjük, hogy jelentkeztél a Virtuális Akadémiára. Átnéztük a regisztrációdat, de sajnos most nem tudjuk elfogadni.',
					'Később bármikor újra jelentkezhetsz, vagy ha szeretnéd, hogy újra átnézzük az adataidat, ' . $contact . '.',
				],
			],
			'inactive_email'        => [
				'heading'    => $greeting,
				'paragraphs' => [
					'Ezúton értesítünk, hogy a Virtuális Akadémián lévő fiókodat felfüggesztettük.',
					'Ha szeretnéd, hogy újra aktiváljuk, ' . $contact . ' a {admin_email} címre.',
				],
			],
			'deletion_email'        => [
				'heading'    => $greeting,
				'paragraphs' => [
					'Ezúton értesítünk, hogy a Virtuális Akadémián lévő fiókodat töröltük. Minden személyes adatodat és feltöltésedet véglegesen eltávolítottuk, a továbbiakban nem tudsz belépni.',
					'Ha a fiókod tévedésből törlődött, ' . $contact . ' a {admin_email} címre.',
				],
			],
			'resetpw_email'         => [
				'heading'    => $greeting,
				'paragraphs' => [
					'Jelszó-visszaállítást kértek a fiókodhoz. Ha te voltál, az alábbi gombbal adhatsz meg új jelszót.',
				],
				'cta'        => [ 'url' => '{password_reset_link}', 'label' => 'Új jelszó megadása' ],
				'after'      => [ 'Ha nem te kérted, hagyd figyelmen kívül ezt a levelet – a jelszavad nem változik. Ha gyanúsnak tartod, ' . $contact . '.' ],
			],
			'changedpw_email'       => [
				'heading'    => $greeting,
				'paragraphs' => [
					'Ezúton jelezzük, hogy nemrég megváltozott a Virtuális Akadémián használt jelszavad.',
				],
				'cta'        => [ 'url' => '{user_account_link}', 'label' => 'Fiókom megnyitása' ],
				'after'      => [ $not_you ],
			],
			'changedaccount_email'  => [
				'heading'    => $greeting,
				'paragraphs' => [
					'Ezúton jelezzük, hogy nemrég módosultak a Virtuális Akadémián lévő fiókod adatai.',
				],
				'cta'        => [ 'url' => '{user_account_link}', 'label' => 'Fiókom megnyitása' ],
				'after'      => [ $not_you ],
			],
			'notification_new_user' => [
				'eyebrow'    => 'Adminisztrátori értesítés',
				'heading'    => 'Új regisztráció',
				'paragraphs' => [
					'<strong style="color:#dae2fd;">{display_name}</strong> most regisztrált a Virtuális Akadémiára.',
					'A beküldött regisztrációs adatok:<br><br>{submitted_registration}',
				],
				'cta'        => [ 'url' => '{user_profile_link}', 'label' => 'Profil megtekintése' ],
				'note'       => 'Ezt az értesítést a Virtuális Akadémia adminisztrátorai kapják.',
			],
			'notification_review'   => [
				'eyebrow'    => 'Adminisztrátori értesítés',
				'heading'    => 'Új regisztráció vár jóváhagyásra',
				'paragraphs' => [
					'<strong style="color:#dae2fd;">{display_name}</strong> regisztrált a Virtuális Akadémiára, és jóváhagyásra vár.',
					'A beküldött regisztrációs adatok:<br><br>{submitted_registration}',
				],
				'cta'        => [ 'url' => '{user_profile_link}', 'label' => 'Regisztráció átnézése' ],
				'note'       => 'Ezt az értesítést a Virtuális Akadémia adminisztrátorai kapják.',
			],
			'notification_deletion' => [
				'eyebrow'    => 'Adminisztrátori értesítés',
				'heading'    => 'Egy tag törölte a fiókját',
				'paragraphs' => [
					'<strong style="color:#dae2fd;">{display_name}</strong> törölte a fiókját a Virtuális Akadémián.',
				],
				'note'       => 'Ezt az értesítést a Virtuális Akadémia adminisztrátorai kapják.',
			],
			'suspicious-activity'   => [
				'eyebrow'    => 'Adminisztrátori értesítés',
				'heading'    => 'Gyanús fióktevékenység',
				'paragraphs' => [
					'Az alábbi fiók(ok)nál gyanús tevékenységet észleltünk:',
					'{banned_profile_links}',
					'Ezért a fiók(ok) állapotát elutasítottra vagy felfüggesztettre állítottuk, a szerepköröket visszavontuk, és a munkameneteket megszakítottuk.',
				],
				'note'       => 'Automatikus biztonsági értesítés (Ultimate Member).',
			],
		];

		return isset( $defs[ $slug ] ) ? EmailLayout::card( $defs[ $slug ] ) : '';
	}
}
