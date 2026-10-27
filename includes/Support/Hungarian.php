<?php

namespace VirtualisAkademia\Support;

/**
 * Small Hungarian-grammar helpers for generated copy. Static, not a Hookable.
 */
class Hungarian {

	/**
	 * The definite article for a word: "az" before a vowel sound, "a"
	 * otherwise -- "az Európai Unió", "a Demokrácia". Leading quotes and
	 * digits are skipped over (digits get "a"; good enough for our titles).
	 */
	public static function article( string $word ): string {
		$first = mb_strtolower( mb_substr( ltrim( $word, " \t\"'„“”«»" ), 0, 1 ) );

		return in_array( $first, [ 'a', 'á', 'e', 'é', 'i', 'í', 'o', 'ó', 'ö', 'ő', 'u', 'ú', 'ü', 'ű' ], true ) ? 'az' : 'a';
	}
}
