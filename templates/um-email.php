<?php
/**
 * Ultimate Member e-mail body, Hungarian + branded -- see
 * Integration\UltimateMember\EmailTemplates. UM includes this file from
 * UM()->mail()->get_email_template(), where `$slug` is the template key.
 *
 * @var string $slug
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo \VirtualisAkademia\Integration\UltimateMember\EmailTemplates::body( (string) $slug ); // phpcs:ignore -- built from escaped parts.
