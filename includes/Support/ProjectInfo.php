<?php

namespace VirtualisAkademia\Support;

/**
 * The grant's mandatory publicity data, in one place: the "A projektről"
 * popup (🇪🇺 in the 3D view), the certificates' partner band and the landing
 * page footer all read it from here.
 *
 * The EU disclaimer shown in the popup stays editable (Tanszékek → Átrium,
 * "EU-s tájékoztató szöveg"); DISCLAIMER is its default.
 */
class ProjectInfo {

	public const TITLE = 'Virtuális Akadémia a demokráciára nevelés és a polgári szerepvállalás érdekében';
	public const ID    = '2025-2-HU01-KA210-YOU000383291';

	public const DISCLAIMER = 'Az Európai Unió finanszírozásával. Az itt szereplő információk és állítások a szerző(k) álláspontját képviselik, és nem feltétlenül tükrözik az Európai Unió vagy a Tempus Közalapítvány hivatalos véleményét. Sem az Európai Unió, sem a támogatást nyújtó hatóság nem vonható felelősségre miattuk.';

	/** "Az Európai Unió támogatásával" funding logo (EU emblem + text). */
	public const LOGO = 'assets/img/eu-tamogatasaval.svg';

	/**
	 * The project data rows, in the order given by the grant.
	 *
	 * @return array<int, array{label: string, value: string}>
	 */
	public static function details(): array {
		return [
			[ 'label' => 'Projekt címe', 'value' => self::TITLE ],
			[ 'label' => 'Azonosítószáma', 'value' => self::ID ],
			[ 'label' => 'Megvalósítási időszak', 'value' => '2026.03.01–2027.04.30.' ],
			[ 'label' => 'Elnyert támogatás', 'value' => '60.000 euró' ],
			[ 'label' => 'Támogatás intenzitás', 'value' => '100%' ],
			[ 'label' => 'Partnerek', 'value' => 'HÖOK a Hallgatókért Alapítvány, Marosvásárhelyi Magyar Diákszövetség (MMDSZ)' ],
		];
	}

	/** Certificates and landing footer. */
	public static function attribution(): string {
		return 'A „' . self::TITLE . '” című projekt az Erasmus+ program támogatásával, a HÖOK a Hallgatókért Alapítvány és a Marosvásárhelyi Magyar Diákszövetség (MMDSZ) közös megvalósításában jött létre. Projektazonosító: ' . self::ID;
	}

	/** The popup's disclaimer: the admin's text, or the official default. */
	public static function disclaimer(): string {
		$settings = get_option( 'va_corridor_settings', [] );
		$text     = is_array( $settings ) ? trim( (string) ( $settings['eu_info_text'] ?? '' ) ) : '';

		return '' !== $text ? $text : self::DISCLAIMER;
	}

	public static function logo_url(): string {
		return VA_PLUGIN_URL . self::LOGO;
	}

	public static function logo_path(): string {
		return VA_PLUGIN_DIR . '/' . self::LOGO;
	}

	/** For the 3D view's popup. */
	public static function for_client(): array {
		return [
			'logo'       => self::logo_url(),
			'disclaimer' => self::disclaimer(),
			'details'    => self::details(),
		];
	}
}
