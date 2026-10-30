<?php
/**
 * Registry API Client for VORKEI IČO ATLAS.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Vorkei_Ico_Atlas_Client {

	private string $base_url;
	private int $cache_ttl;

	public function __construct() {
		$this->base_url  = get_option( 'vorkei_ico_api_url', 'http://ico-api.vorkei.com/api/company/proxy-search?ico=' );
		$this->cache_ttl = (int) get_option( 'vorkei_ico_cache_ttl', 86400 );
	}

	/**
	 * Looks up a company by IČO with transient caching and rigorous legal status verification.
	 *
	 * @param string $ico
	 * @return array
	 */
	public function lookup( string $ico ): array {
		$clean_ico = preg_replace( '/\s+/', '', $ico );
		$clean_ico = str_pad( $clean_ico, 8, '0', STR_PAD_LEFT );

		if ( ! preg_match( '/^\d{6,8}$/', $clean_ico ) ) {
			return [
				'found'               => false,
				'ico'                 => $clean_ico,
				'name'                => null,
				'status'              => null,
				'active'              => false,
				'is_risky'            => false,
				'verified'            => false,
				'verification_status' => 'not_found',
				'error'               => __( 'IČO musí obsahovať 6 až 8 číslic.', 'vorkei-ico-atlas' ),
			];
		}

		$cache_key = 'vorkei_ico_' . $clean_ico;
		$cached    = get_transient( $cache_key );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$request_url = $this->base_url . rawurlencode( $clean_ico );

		$response = wp_safe_remote_get(
			$request_url,
			[
				'timeout'     => 8,
				'redirection' => 2,
				'headers'     => [
					'Accept' => 'application/json',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			// Fallback attempt: if ico-api.vorkei.com had network issue, try direct VPS IP if configured
			$fallback_url = 'http://2.29.52.59:80/api/company/proxy-search?ico=' . rawurlencode( $clean_ico );
			$response     = wp_safe_remote_get(
				$fallback_url,
				[
					'timeout' => 8,
					'headers' => [ 'Accept' => 'application/json' ],
				]
			);
		}

		if ( is_wp_error( $response ) ) {
			return [
				'found'               => false,
				'ico'                 => $clean_ico,
				'name'                => null,
				'status'              => null,
				'active'              => false,
				'is_risky'            => false,
				'verified'            => false,
				'verification_status' => 'not_found',
				'error'               => $response->get_error_message(),
			];
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		if ( 200 !== $status_code || ! is_array( $data ) || empty( $data['found'] ) || empty( $data['name'] ) ) {
			$not_found = [
				'found'               => false,
				'ico'                 => $clean_ico,
				'name'                => null,
				'status'              => null,
				'active'              => false,
				'is_risky'            => false,
				'verified'            => false,
				'verification_status' => 'not_found',
				'error'               => __( 'Subjekt s týmto IČO nebol nájdený v registri SR.', 'vorkei-ico-atlas' ),
			];
			set_transient( $cache_key, $not_found, 3600 );
			return $not_found;
		}

		$raw_name     = trim( (string) $data['name'] );
		$raw_status   = isset( $data['status'] ) ? trim( (string) $data['status'] ) : '';
		$status_lower = mb_strtolower( $raw_status, 'UTF-8' );
		$name_lower   = mb_strtolower( $raw_name, 'UTF-8' );
		$dissolved_on = ! empty( $data['dissolved_on'] ) ? trim( (string) $data['dissolved_on'] ) : null;

		$is_dissolved         = ! empty( $dissolved_on );
		$is_inactive          = isset( $data['active'] ) && false === $data['active'];
		$has_liquidation_text = str_contains( $status_lower, 'likvid' ) ||
								str_contains( $status_lower, 'konkurz' ) ||
								str_contains( $status_lower, 'zanik' ) ||
								str_contains( $status_lower, 'zrušen' ) ||
								str_contains( $name_lower, 'v likvidácii' ) ||
								str_contains( $name_lower, 'v konkurze' );

		$is_risky = $is_dissolved || $is_inactive || $has_liquidation_text;

		$has_explicit_active = ( isset( $data['active'] ) && true === $data['active'] ) ||
								str_contains( $status_lower, 'aktívny' ) ||
								str_contains( $status_lower, 'aktivny' );

		$is_confirmed_active = ! $is_risky && $has_explicit_active;

		$verification_status = 'unconfirmed';
		$verified            = false;
		$display_status      = $raw_status;

		if ( $is_risky ) {
			$verification_status = 'verified_risky';
			$verified            = true;
			$display_status      = $raw_status ?: ( $is_dissolved ? "Zaniknutý ($dissolved_on)" : 'Neaktívny subjekt' );
		} elseif ( $is_confirmed_active ) {
			$verification_status = 'verified_active';
			$verified            = true;
			$display_status      = $raw_status ?: 'Aktívny subjekt';
		} else {
			$verification_status = 'unconfirmed';
			$verified            = false;
			$display_status      = $raw_status ?: 'Nezistený právny stav';
		}

		$risk_reason = null;
		if ( $is_risky ) {
			$risk_reason = sprintf(
				__( '⚠️ Pozor! Subjekt je v registri evidovaný ako rizikový / v likvidácii (%s). Platba môže predstavovať právne riziko!', 'vorkei-ico-atlas' ),
				$display_status
			);
		}

		$addr         = is_array( $data['address'] ?? null ) ? $data['address'] : [];
		$street       = trim( (string) ( $addr['street'] ?? '' ) );
		$city         = trim( (string) ( $addr['city'] ?? '' ) );
		$postal_code  = trim( (string) ( $addr['postal_code'] ?? '' ) );
		$country      = trim( (string) ( $addr['country'] ?? 'Slovensko' ) );
		$full_address = implode( ', ', array_filter( [ $street, trim( "$postal_code $city" ), $country ] ) );

		$result = [
			'found'               => true,
			'ico'                 => $clean_ico,
			'name'                => $raw_name,
			'status'              => $display_status,
			'active'              => $is_confirmed_active,
			'legal_form'          => isset( $data['legal_form'] ) ? trim( (string) $data['legal_form'] ) : null,
			'address'             => $full_address,
			'founded_on'          => $data['founded_on'] ?? null,
			'dissolved_on'        => $dissolved_on,
			'is_risky'            => $is_risky,
			'risk_reason'         => $risk_reason,
			'verified'            => $verified,
			'verified_at'         => current_time( 'mysql' ),
			'source'              => 'Register účtovných závierok / Obchodný register SR',
			'verification_status' => $verification_status,
		];

		set_transient( $cache_key, $result, $this->cache_ttl );
		return $result;
	}
}
