<?php
/**
 * Updates from the public GitHub release ZIP built by this repository.
 */

defined( 'ABSPATH' ) || exit;

function celestial_cookie_consent_check_update( $update, $plugin_data, $plugin_file ) {
	if ( plugin_basename( dirname( __DIR__ ) . '/celestial-cookie-consent.php' ) !== $plugin_file ) {
		return $update;
	}

	$response = wp_remote_get(
		'https://api.github.com/repos/valeravilks/celestial-cookie-consent/releases/latest',
		array(
			'timeout' => 10,
			'headers' => array( 'Accept' => 'application/vnd.github+json' ),
		)
	);
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return $update;
	}

	$release = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $release ) || ! empty( $release['draft'] ) || ! empty( $release['prerelease'] ) ||
		! isset( $release['tag_name'], $release['assets'] ) || ! is_string( $release['tag_name'] ) ||
		! is_array( $release['assets'] ) || ! preg_match( '/^v(\d+\.\d+\.\d+)$/D', $release['tag_name'], $matches ) ) {
		return $update;
	}

	$version = $matches[1];
	$asset_name = 'celestial-cookie-consent-' . $version . '.zip';
	$package = 'https://github.com/valeravilks/celestial-cookie-consent/releases/download/' . $release['tag_name'] . '/' . $asset_name;
	foreach ( $release['assets'] as $asset ) {
		if ( ! is_array( $asset ) || ( $asset['name'] ?? '' ) !== $asset_name ||
			( $asset['browser_download_url'] ?? '' ) !== $package || ( $asset['state'] ?? '' ) !== 'uploaded' ) {
			continue;
		}

		return array(
			'slug'         => 'celestial-cookie-consent',
			'version'      => $version,
			'url'          => 'https://github.com/valeravilks/celestial-cookie-consent/releases/tag/' . $release['tag_name'],
			'package'      => $package,
			'requires'     => '6.5',
			'requires_php' => '7.4',
		);
	}

	return $update;
}
add_filter( 'update_plugins_github.com', 'celestial_cookie_consent_check_update', 10, 3 );
