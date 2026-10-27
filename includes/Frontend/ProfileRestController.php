<?php

namespace VirtualisAkademia\Frontend;

use VirtualisAkademia\Hookable;
use VirtualisAkademia\Profile\Avatar;
use VirtualisAkademia\Profile\ProfileCard;

/**
 * Backs the HUD's profile popup -- always the acting user's own data
 * (get_current_user_id(), never a client-supplied ID): the citizen card
 * (Profile\ProfileCard), the data on file, and the profile picture upload
 * (Profile\Avatar, which enforces the limits). Everything else is edited on
 * Ultimate Member's Account page.
 */
class ProfileRestController implements Hookable {

	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		register_rest_route( 'virtualis-akademia/v1', '/profile', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_profile' ],
			'permission_callback' => static fn () => is_user_logged_in(),
		] );

		register_rest_route( 'virtualis-akademia/v1', '/profile/avatar', [
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'upload_avatar' ],
				'permission_callback' => static fn () => is_user_logged_in(),
			],
			[
				'methods'             => 'DELETE',
				'callback'            => [ $this, 'delete_avatar' ],
				'permission_callback' => static fn () => is_user_logged_in(),
			],
		] );
	}

	public function upload_avatar( \WP_REST_Request $request ) {
		$files = $request->get_file_params();

		$url = Avatar::store( get_current_user_id(), (array) ( $files['avatar'] ?? [] ) );

		if ( is_wp_error( $url ) ) {
			return $url;
		}

		return new \WP_REST_Response( [ 'avatarUrl' => $url ] );
	}

	public function delete_avatar(): \WP_REST_Response {
		Avatar::remove( get_current_user_id() );

		return new \WP_REST_Response( [ 'avatarUrl' => '' ] );
	}

	public function get_profile(): \WP_REST_Response {
		$user       = wp_get_current_user();
		$birth_date = get_user_meta( $user->ID, 'birth_date', true );

		return new \WP_REST_Response( ProfileCard::for_user( $user->ID ) + [
			'username'      => $user->user_login,
			'email'         => $user->user_email,
			'birthDate'     => $birth_date ? date_i18n( 'Y. F j.', strtotime( $birth_date ) ) : '',
			'accountUrl'    => function_exists( 'um_get_core_page' ) ? um_get_core_page( 'account' ) : '',
			'logoutUrl'     => wp_logout_url(),
			'participation' => $this->get_participation_data( $user->ID ),
			'avatarLimits'  => Avatar::limits(),
		] );
	}

	/**
	 * The data points Activation::ensure_participation_data_fields() added
	 * to the registration form for the project's own grant-reporting
	 * obligations -- surfaced back on the profile screen so a member can see
	 * (read-only, same as the rest of this endpoint) exactly what's on file
	 * about them.
	 */
	private function get_participation_data( int $user_id ): array {
		$disadvantage_status = get_user_meta( $user_id, 'disadvantage_status', true );

		return [
			'phoneNumber'       => (string) get_user_meta( $user_id, 'phone_number', true ),
			'birthPlace'        => (string) get_user_meta( $user_id, 'birth_place', true ),
			'residence'         => (string) get_user_meta( $user_id, 'residence', true ),
			'citizenship'       => (string) get_user_meta( $user_id, 'citizenship', true ),
			'disadvantageStatus' => is_array( $disadvantage_status ) ? array_values( $disadvantage_status ) : array_filter( [ (string) $disadvantage_status ] ),
			'disadvantageOther' => (string) get_user_meta( $user_id, 'disadvantage_other', true ),
		];
	}
}
