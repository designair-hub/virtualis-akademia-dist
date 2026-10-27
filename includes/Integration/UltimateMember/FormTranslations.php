<?php

namespace VirtualisAkademia\Integration\UltimateMember;

use VirtualisAkademia\Hookable;

/**
 * Ultimate Member ships English-only strings on this install (no hu_HU
 * language pack loaded) -- a `gettext` filter is the standard, non-invasive
 * way to translate a handful of known strings without touching the plugin's
 * own field-label meta, which stays intact if the client ever edits the
 * form in UM's own admin builder.
 */
class FormTranslations implements Hookable {

	private const STRINGS = [
		// Login / register (also used verbatim on the account page).
		'Username or E-mail'         => 'Felhasználónév vagy e-mail cím',
		'Username'                    => 'Felhasználónév',
		'Password'                    => 'Jelszó',
		'Confirm Password'            => 'Jelszó megerősítése',
		'Confirm %s'                  => '%s megerősítése',
		// {action_title} / {password} in the welcome e-mail (EmailTemplates).
		'Login to our site'           => 'Belépés az Akadémiára',
		'Set your password'           => 'Jelszó beállítása',
		'Your set password'           => 'A regisztrációkor megadott jelszavad',
		'First Name'                  => 'Keresztnév',
		'Last Name'                   => 'Vezetéknév',
		'E-mail Address'              => 'E-mail cím',
		'Email Address'               => 'E-mail cím',
		'Login'                       => 'Bejelentkezés',
		'Register'                    => 'Regisztráció',
		'Keep me signed in'           => 'Bejelentkezve maradok',
		'Forgot your password?'       => 'Elfelejtetted a jelszavad?',
		'Only fill in if you are not human' => 'Csak akkor töltsd ki, ha nem vagy ember',

		// Account page -- tab names and headings.
		'Account'                     => 'Fiók',
		'Change Password'             => 'Jelszó módosítása',
		'Privacy'                     => 'Adatvédelem',
		'Delete Account'              => 'Fiók törlése',
		'View profile'                => 'Profil megtekintése',

		// Account page -- "General" tab.
		'Update Account'              => 'Fiók frissítése',

		// Account page -- "Change Password" tab.
		'Current Password'            => 'Jelenlegi jelszó',
		'New Password'                => 'Új jelszó',
		'Confirm New Password'        => 'Új jelszó megerősítése',
		'Update Password'             => 'Jelszó frissítése',

		// Account page -- "Privacy" tab.
		'Hide my profile from directory' => 'Profilom elrejtése a tagnévsorból',
		'Here you can hide yourself from appearing in public directory' => 'Itt elrejtheted magad a nyilvános tagnévsorból',
		'Show my last login?'         => 'Utolsó bejelentkezés megjelenítése?',
		'Here you can hide last login field on profile page and card in member directory' => 'Itt elrejtheted az utolsó bejelentkezés mezőt a profilodról és a tagnévsor kártyádról',
		'Download your data'          => 'Adataid letöltése',
		'You can request a file with the information that we believe is most relevant and useful to you.' => 'Kérhetsz egy fájlt a rólad tárolt, számodra legfontosabbnak vélt adatokkal.',
		'Enter your current password to confirm a new export of your personal data.' => 'Add meg jelenlegi jelszavad a személyes adataid exportjának megerősítéséhez.',
		'Request data'                => 'Adatok kérése',
		'Erase of your data'          => 'Adataid törlése',
		'You can request erasing of the data that we have about you.' => 'Kérheted a rólad tárolt adatok törlését.',
		'Enter your current password to confirm the erasure of your personal data.' => 'Add meg jelenlegi jelszavad a személyes adataid törlésének megerősítéséhez.',
		'Request data erase'          => 'Adattörlés kérése',
		'You must enter a password'   => 'Meg kell adnod egy jelszót',
		'Update Privacy'              => 'Adatvédelem frissítése',
		'No'                          => 'Nem',
		'Yes'                         => 'Igen',

		'Everyone'                    => 'Mindenki',
		'Only me'                     => 'Csak én',

		// Form validation errors (registration / login / account).
		'%s is required'                                  => '%s megadása kötelező.',
		'%s is required.'                                 => '%s megadása kötelező.',
		'This field is required'                          => 'A mező kitöltése kötelező.',
		'Password is required'                            => 'A jelszó megadása kötelező.',
		'Password confirmation is required'               => 'A jelszó megerősítése kötelező.',
		'Please confirm your password'                    => 'Kérjük, erősítsd meg a jelszavadat.',
		'Your passwords do not match'                     => 'A két jelszó nem egyezik.',
		'Your new password does not match'                => 'Az új jelszavak nem egyeznek.',
		'You must enter a new password'                   => 'Add meg az új jelszavadat.',
		'You must confirm your new password'              => 'Erősítsd meg az új jelszavadat.',
		'You must enter your password'                    => 'Add meg a jelszavadat.',
		'Password is incorrect. Please try again.'        => 'Hibás jelszó. Próbáld újra!',
		'The password you entered is incorrect.'          => 'A megadott jelszó hibás.',
		'The email you entered is incorrect'              => 'A megadott e-mail cím hibás.',
		'The username you entered is incorrect'           => 'A megadott felhasználónév hibás.',
		'Please enter your username or email'             => 'Add meg a felhasználónevedet vagy az e-mail címedet.',
		'Please provide your username or email'           => 'Add meg a felhasználónevedet vagy az e-mail címedet.',
		'Please enter your username'                      => 'Add meg a felhasználónevedet.',
		'Please enter your email'                         => 'Add meg az e-mail címedet.',
		'Please enter your password'                      => 'Add meg a jelszavadat.',
		'Please provide a valid email'                    => 'Adj meg egy érvényes e-mail címet.',
		'This is not a valid email'                       => 'Ez nem érvényes e-mail cím.',
		'Your email contains invalid characters'          => 'Az e-mail cím érvénytelen karaktert tartalmaz.',
		'You must provide your email'                     => 'Add meg az e-mail címedet.',
		'You must provide a username'                     => 'Adj meg egy felhasználónevet.',
		'You must provide a username or email'            => 'Add meg a felhasználónevedet vagy az e-mail címedet.',
		'You must provide your first name'                => 'Add meg a keresztnevedet.',
		'You must provide your last name'                 => 'Add meg a vezetéknevedet.',
		'You must provide a unique value'                 => 'Ez az érték már foglalt, adj meg másikat.',
		'Your username contains invalid characters'       => 'A felhasználónév érvénytelen karaktert tartalmaz.',
		'Username cannot be an email'                     => 'A felhasználónév nem lehet e-mail cím.',
		'You are not allowed to use this word as your username.' => 'Ez a szó nem használható felhasználónévként.',
		'Please enter a valid phone number'               => 'Adj meg egy érvényes telefonszámot.',
		'Please enter numbers only in this field'         => 'Ebbe a mezőbe csak számot írhatsz.',
		'Please select at least %s choices'               => 'Válassz legalább %s lehetőséget.',
		'This field must contain at least %s characters'  => 'Legalább %s karaktert kell megadnod.',
		'This field must contain less than %s characters' => 'Legfeljebb %s karaktert adhatsz meg.',
		'Your password must contain at least %d characters' => 'A jelszónak legalább %d karakterből kell állnia.',
		'Your password must contain less than %d characters' => 'A jelszó legfeljebb %d karakterből állhat.',
		'Your password must contain at least one lowercase letter, one capital letter, one number' => 'A jelszónak tartalmaznia kell legalább egy kisbetűt, egy nagybetűt és egy számot.',
		'Your password must contain at least one lowercase letter, one capital letter, one number, and one special character (e.g. !@#$%^&*)' => 'A jelszónak tartalmaznia kell legalább egy kisbetűt, egy nagybetűt, egy számot és egy speciális karaktert (pl. !@#$%^&*).',
		'Your password must contain at least one capital letter' => 'A jelszónak tartalmaznia kell legalább egy nagybetűt.',
		'Your password must contain at least one lowercase letter' => 'A jelszónak tartalmaznia kell legalább egy kisbetűt.',
		'Your password must contain at least one number'  => 'A jelszónak tartalmaznia kell legalább egy számot.',
		'Your password cannot contain the part of your username' => 'A jelszó nem tartalmazhatja a felhasználónevedet.',
		'Your password cannot contain the part of your email address' => 'A jelszó nem tartalmazhatja az e-mail címed részét.',
		'Your account has not been approved yet.'         => 'A fiókodat még nem hagyták jóvá.',
		'Your account is awaiting email verification.'    => 'A fiókod az e-mail cím megerősítésére vár.',
		'Your membership request has been rejected.'      => 'A regisztrációs kérelmedet elutasították.',
		'You are already registered.'                     => 'Már regisztráltál.',
		'This activation link is expired or have already been used.' => 'Ez az aktiváló hivatkozás lejárt, vagy már felhasználták.',
		'Are you hacking? Please try again!'              => 'Hiba történt a beküldéskor. Kérjük, próbáld újra!',

		// Password reset page.
		'To reset your password, please enter your email address or username below.' => 'A jelszavad visszaállításához add meg az e-mail címedet vagy a felhasználónevedet.',
		'Enter your username or email' => 'Felhasználónév vagy e-mail cím',
		'Reset password'              => 'Jelszó visszaállítása',
		'If an account matching the provided details exists, we will send a password reset link. Please check your inbox.' => 'Ha létezik a megadott adatokkal fiók, elküldjük rá a jelszó-visszaállító hivatkozást. Nézd meg a postafiókodat!',
		'Your password reset link appears to be invalid. Please request a new link below.' => 'A jelszó-visszaállító hivatkozás érvénytelen. Kérj lent újat!',
		'Your password reset link has expired. Please request a new link below.' => 'A jelszó-visszaállító hivatkozás lejárt. Kérj lent újat!',
		'Change password'             => 'Jelszó módosítása',
		'Change my password'          => 'Jelszavam módosítása',

		// Member profile page (/user/).
		'About'                       => 'Bemutatkozás',
		'Posts'                       => 'Bejegyzések',
		'Comments'                    => 'Hozzászólások',
		'This user has not created any posts.' => 'Ez a felhasználó még nem írt bejegyzést.',
		'This user has not made any comments.' => 'Ez a felhasználó még nem írt hozzászólást.',
		'Your profile is looking a little empty. Why not <a href="%s">add</a> some information!' => 'A profilod még elég üres. <a href="%s">Adj hozzá</a> néhány információt!',
		'Change your cover photo'     => 'Borítókép módosítása',
		'Change cover photo'          => 'Borítókép módosítása',
		'Upload a cover photo'        => 'Borítókép feltöltése',
		'Remove cover photo'          => 'Borítókép eltávolítása',
		'Change your profile photo'   => 'Profilkép módosítása',
		'Upload photo'                => 'Kép feltöltése',
		'Change photo'                => 'Kép módosítása',
		'Remove photo'                => 'Kép eltávolítása',
		'Close view photo modal'      => 'Képnézegető bezárása',
		'Edit Profile'                => 'Profil szerkesztése',
		'Update Profile'              => 'Profil frissítése',
		'Cancel'                      => 'Mégse',
		'Logout'                      => 'Kijelentkezés',

		// Account page -- "Delete Account" tab.
		'Are you sure you want to delete your account? This will erase all of your account data from the site. To delete your account enter your password below.' => 'Biztosan törölni szeretnéd a fiókodat? Ez véglegesen törli az összes fiókadatodat az oldalról. A törléshez add meg lent a jelszavadat.',
	];

	public function register(): void {
		add_filter( 'gettext', [ $this, 'translate' ], 10, 3 );
	}

	public function translate( string $translated, string $text, string $domain ): string {
		if ( 'ultimate-member' !== $domain ) {
			return $translated;
		}

		return self::STRINGS[ $text ] ?? $translated;
	}
}
