<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Data purge is intentionally not implemented in v0.1 — departments, user
// meta (onboarding/passport state), and options are left in place on
// uninstall. Add an opt-in "delete data on uninstall" setting before wiring
// any destructive cleanup here.
