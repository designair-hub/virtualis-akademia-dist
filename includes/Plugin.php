<?php

namespace VirtualisAkademia;

use VirtualisAkademia\Admin\CorridorSettingsPage;
use VirtualisAkademia\Admin\DepartmentMetaBox;
use VirtualisAkademia\Admin\HotspotPickerField;
use VirtualisAkademia\Admin\HotspotEditorPage;
use VirtualisAkademia\Admin\LessonBlocksPage;
use VirtualisAkademia\Admin\LessonHotspotMetaBox;
use VirtualisAkademia\Admin\ParticipantsExportPage;
use VirtualisAkademia\Frontend\AssetLoader;
use VirtualisAkademia\Frontend\BrandedShell;
use VirtualisAkademia\Frontend\ContactRestController;
use VirtualisAkademia\Frontend\PrivacyPolicyPage;
use VirtualisAkademia\Frontend\UmCorePages;
use VirtualisAkademia\Frontend\ProfileRestController;
use VirtualisAkademia\Frontend\ShortcodeLanding;
use VirtualisAkademia\Frontend\ShortcodeMetaverse;
use VirtualisAkademia\Frontend\ShortcodeRegister;
use VirtualisAkademia\Certificates\CertificateService;
use VirtualisAkademia\Gamification\PassportRestController;
use VirtualisAkademia\Integration\LearnDash\CertificateBridge;
use VirtualisAkademia\Integration\LearnDash\RedirectHandler;
use VirtualisAkademia\Integration\LearnDash\LessonContentWrapper;
use VirtualisAkademia\Integration\LearnDash\Navigation;
use VirtualisAkademia\Integration\LearnDash\Translations;
use VirtualisAkademia\Integration\UltimateMember\AccessGate;
use VirtualisAkademia\Integration\UltimateMember\AgeGate;
use VirtualisAkademia\Integration\UltimateMember\FormTranslations;
use VirtualisAkademia\Integration\UltimateMember\RegistrationRules;
use VirtualisAkademia\Integration\UltimateMember\LoginRedirect;
use VirtualisAkademia\Frontend\Greeting;
use VirtualisAkademia\Frontend\PageTransition;
use VirtualisAkademia\Integration\UltimateMember\EmailTemplates;
use VirtualisAkademia\Onboarding\OnboardingState;
use VirtualisAkademia\Profile\Avatar;
use VirtualisAkademia\PostTypes\DepartmentPostType;
use VirtualisAkademia\Update\GitHubUpdater;

class Plugin {

	private static ?Plugin $instance = null;

	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function boot(): void {
		$services = [
			new DependencyGuard(),
			new DepartmentPostType(),
			new DepartmentMetaBox(),
			new LessonHotspotMetaBox(),
			new HotspotPickerField(),
			new HotspotEditorPage(),
			new LessonBlocksPage(),
			new CorridorSettingsPage(),
			new ShortcodeMetaverse(),
			new ShortcodeLanding(),
			new ShortcodeRegister(),
			new PrivacyPolicyPage(),
			new UmCorePages(),
			new ParticipantsExportPage(),
			new ContactRestController(),
			new AssetLoader(),
			new BrandedShell(),
			new RedirectHandler(),
			new Translations(),
			new Navigation(),
			new LessonContentWrapper(),
			new OnboardingState(),
			new CertificateBridge(),
			new PassportRestController(),
			new ProfileRestController(),
			new Avatar(),
			new CertificateService(),
			new AccessGate(),
			new LoginRedirect(),
			new Greeting(),
			new PageTransition(),
			new EmailTemplates(),
			new FormTranslations(),
			new AgeGate(),
			new RegistrationRules(),
			new GitHubUpdater(),
		];

		foreach ( $services as $service ) {
			$service->register();
		}
	}
}
