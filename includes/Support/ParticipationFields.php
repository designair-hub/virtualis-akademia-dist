<?php

namespace VirtualisAkademia\Support;

/**
 * Single source of truth for the grant's participant-data registration
 * questions -- the wording Activation provisions into the Ultimate Member
 * form, the rules RegistrationRules enforces on submit, and what the
 * register page script needs to wire up the exclusive options and the
 * "Egyéb" free-text reveal. Static helper, not a Hookable.
 */
class ParticipationFields {

	public const STATUS_KEY = 'disadvantage_status';
	public const OTHER_KEY  = 'disadvantage_other';

	public const OPTION_NONE  = 'Egyik sem igaz rám, nem vagyok kevesebb lehetőséggel rendelkező.';
	public const OPTION_OTHER = 'Egyéb';

	public const STATUS_LABEL = 'A pályázat alapvető célja az előnytelen helyzetből induló, kevesebb lehetőséggel rendelkező személyek bevonása. Kérjük, jelöld meg, mi igaz rád – az „Egyik sem igaz rám…” lehetőség mellé mást nem jelölhetsz, az „Egyéb” viszont bármelyikkel együtt választható.';

	public const OTHER_LABEL       = 'Egyéb helyzet részletezése';
	public const OTHER_PLACEHOLDER = 'Kérjük, röviden írd le a helyzetedet…';

	/**
	 * Options in display order. The first is exclusive (see EXCLUSIVE):
	 * "none of these apply to me" stands on its own; "Egyéb" (please
	 * specify) can be ticked together with any of the others.
	 */
	public const OPTIONS = [
		self::OPTION_NONE,
		'Országomban nemzeti kisebbség tagja vagyok.',
		'Vidéken, kistelepülésen élek, dolgozom.',
		'Elmaradott, leszakadó térségben élek, dolgozom.',
		'Nehéz anyagi, gazdasági körülmények között élek.',
		'25 év alatti vagy 50 év feletti álláskereső vagyok.',
		'Tartós (6 hónapnál régebben) álláskereső vagyok.',
		'Alacsony iskolai végzettséggel rendelkezem.',
		'Egyszülős családban élek, nevelkedem.',
		'Gyermekeimet egyedül nevelem.',
		self::OPTION_OTHER,
	];

	/**
	 * Options that can't be combined with any other. (OPTION_OTHER used to be
	 * here too; per the client "Egyéb" now goes with the listed options.)
	 */
	public const EXCLUSIVE = [ self::OPTION_NONE ];

	/**
	 * Earlier wording of options => current wording, for re-labelling the
	 * provisioned form and the answers already stored in user meta.
	 */
	public const RENAMED_OPTIONS = [
		'Vidéken, kistelepülésen élek, dolgozok.'            => 'Vidéken, kistelepülésen élek, dolgozom.',
		'Elmaradott, leszakadó térségekben élek, dolgozok.' => 'Elmaradott, leszakadó térségben élek, dolgozom.',
		'Egyszülős családban élek, nevelkedek.'              => 'Egyszülős családban élek, nevelkedem.',
	];

	/**
	 * The other participant-data text fields: metakey => label. Plain noun
	 * labels (the earlier "Telefonszáma" / "Lakóhelye" read as formal
	 * third-person, clashing with the rest of the informal copy).
	 */
	public const TEXT_FIELD_LABELS = [
		'phone_number' => 'Telefonszám',
		'birth_place'  => 'Születési hely',
		'residence'    => 'Lakóhely',
		'citizenship'  => 'Állampolgárság',
	];
}
