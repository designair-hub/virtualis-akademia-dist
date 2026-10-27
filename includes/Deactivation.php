<?php

namespace VirtualisAkademia;

class Deactivation {

	public static function run(): void {
		flush_rewrite_rules();
	}
}
