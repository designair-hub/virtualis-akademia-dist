<?php

namespace VirtualisAkademia\Onboarding;

use VirtualisAkademia\Hookable;

class OnboardingState implements Hookable {

	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		register_rest_route( 'virtualis-akademia/v1', '/onboarding/ack', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'acknowledge' ],
			'permission_callback' => static fn () => is_user_logged_in(),
		] );
	}

	public function acknowledge(): \WP_REST_Response {
		update_user_meta( get_current_user_id(), '_va_onboarding_seen', true );

		return new \WP_REST_Response( [ 'onboardingSeen' => true ] );
	}
}
