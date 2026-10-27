<?php

namespace VirtualisAkademia\Update;

use VirtualisAkademia\Hookable;
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

/**
 * Automatic plugin updates from the distribution repository on GitHub
 * (designair-hub/virtualis-akademia-dist) through Plugin Update Checker.
 *
 * The dist repo holds the built plugin -- exactly what goes into the zip
 * (production Composer autoloader, compiled assets, no sources) -- on its
 * `main` branch, published by bin/publish-dist.sh on every push of the source
 * repository. This checks that branch's plugin header: when its `Version`
 * is newer than the installed one, WordPress lists the update (Bővítmények /
 * Irányítópult → Frissítések) and installs it with the usual one-click
 * update; the branch archive is the package.
 *
 * Which version is offered: the `Version` header of the plugin file at the
 * highest `vX.Y.Z` tag of the dist repo (what bin/publish-dist.sh creates with
 * every release), or of `main` while there are no tags.
 *
 * Settings (all optional, e.g. in wp-config.php):
 *   VA_UPDATE_TOKEN     GitHub token, only needed while the dist repo is private
 *   VA_UPDATE_DISABLED  true: never check for updates (e.g. a staging copy)
 * Filters: `va_update_repository` (repository URL), `va_update_branch`.
 */
class GitHubUpdater implements Hookable {

	public const REPOSITORY = 'https://github.com/designair-hub/virtualis-akademia-dist/';
	public const BRANCH     = 'main';
	public const SLUG       = 'virtualis-akademia';

	/** @var object|null The Plugin Update Checker instance. */
	private $checker = null;

	/** The registered updater, for diagnostics (e.g. `wp eval`). */
	public static ?self $instance = null;

	public function register(): void {
		if ( ( defined( 'VA_UPDATE_DISABLED' ) && VA_UPDATE_DISABLED ) || ! class_exists( PucFactory::class ) ) {
			return;
		}

		self::$instance = $this;

		// Early enough for the admin's update screens and the cron check.
		add_action( 'plugins_loaded', [ $this, 'boot' ], 20 );
	}

	public function boot(): void {
		$repository = (string) apply_filters( 'va_update_repository', self::REPOSITORY );

		$this->checker = PucFactory::buildUpdateChecker( $repository, VA_PLUGIN_FILE, self::SLUG );
		$this->checker->setBranch( (string) apply_filters( 'va_update_branch', self::BRANCH ) );

		if ( defined( 'VA_UPDATE_TOKEN' ) && VA_UPDATE_TOKEN ) {
			$this->checker->setAuthentication( VA_UPDATE_TOKEN );
		}
	}

	/** @return object|null */
	public function checker() {
		return $this->checker;
	}
}
