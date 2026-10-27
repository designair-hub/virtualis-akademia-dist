<?php

namespace VirtualisAkademia\Support;

/**
 * The project's supporting organisations, shown from the metaverse HUD's
 * "Partnerek" button (bottom-right toolbar). Fixed list bundled with the
 * plugin -- unlike the Átrium plaques (Admin\CorridorSettingsPage), which an
 * admin places and edits, these three are the grant's named partners and
 * their logos ship as plugin assets.
 */
class Partners {

	/**
	 * @return array<int, array{name: string, logo: string, url: string}>
	 */
	public static function all(): array {
		$base = VA_PLUGIN_URL . 'assets/img/partners/';

		return [
			[
				'name' => 'Erasmus+ Program',
				'logo' => $base . 'erasmus-plus.png',
				'url'  => 'https://erasmusplusz.hu/',
			],
			[
				'name' => 'HÖOK a Hallgatókért Alapítvány',
				'logo' => $base . 'hook-alapitvany.png',
				'url'  => 'https://hookalapitvany.hu/',
			],
			[
				'name' => 'Marosvásárhelyi Magyar Diákszövetség',
				'logo' => $base . 'mmdsz.png',
				'url'  => 'https://mmdsz.ro/',
			],
		];
	}
}
