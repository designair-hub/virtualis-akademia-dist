<?php

namespace VirtualisAkademia\Support;

class Dependencies {

	public static function active(): bool {
		return function_exists( 'learndash_get_post_type_slug' ) && function_exists( 'UM' );
	}
}
