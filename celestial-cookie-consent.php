<?php
/**
 * Plugin Name: Celestial Cookie Consent
 * Description: Lets visitors set their cookie preferences.
 * Version: 1.0.1
 * Text Domain: celestial-cookie-consent
 * Domain Path: /languages
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Update URI: https://github.com/valeravilks/celestial-cookie-consent
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/updates.php';

function celestial_cookie_consent_load_textdomain() {
	load_plugin_textdomain( 'celestial-cookie-consent', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'celestial_cookie_consent_load_textdomain', 0 );

function celestial_cookie_consent_register_settings() {
	register_setting(
		'celestial_cookie_consent',
		'celestial_cookie_consent_message',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => __( 'Choose whether to allow optional cookies.', 'celestial-cookie-consent' ),
			'show_in_rest'      => true,
		)
	);
}
add_action( 'init', 'celestial_cookie_consent_register_settings' );

function celestial_cookie_consent_can_manage_scripts() {
	return current_user_can( 'manage_options' ) && current_user_can( 'unfiltered_html' );
}

function celestial_cookie_consent_save_scripts() {
	if ( ! celestial_cookie_consent_can_manage_scripts() ) {
		wp_die( esc_html__( 'You are not allowed to edit scripts.', 'celestial-cookie-consent' ), '', array( 'response' => 403 ) );
	}

	if ( ( $_SERVER['REQUEST_METHOD'] ?? '' ) !== 'POST' ) {
		header( 'Allow: POST' );
		wp_die( '', '', array( 'response' => 405 ) );
	}

	$nonce = isset( $_POST['_wpnonce'] ) && is_string( $_POST['_wpnonce'] ) ? wp_unslash( $_POST['_wpnonce'] ) : '';
	if ( ! wp_verify_nonce( $nonce, 'celestial_cookie_consent_save_scripts' ) ) {
		wp_nonce_ays( 'celestial_cookie_consent_save_scripts' );
	}

	$status = 'error';
	$raw = isset( $_POST['scripts'] ) && is_string( $_POST['scripts'] ) ? wp_unslash( $_POST['scripts'] ) : '';
	$submitted = json_decode( $raw );
	if ( is_array( $submitted ) ) {
		$scripts = array();
		foreach ( $submitted as $script ) {
			if ( ! is_object( $script ) ||
				! isset( $script->name, $script->description, $script->code ) ||
				! is_string( $script->name ) ||
				! is_string( $script->description ) ||
				! is_string( $script->code ) ) {
				break;
			}

			$scripts[] = array(
				'name'        => sanitize_text_field( $script->name ),
				'description' => sanitize_textarea_field( $script->description ),
				'code'        => $script->code,
			);
		}

		if ( count( $scripts ) === count( $submitted ) ) {
			if ( update_option( 'celestial_cookie_consent_scripts', $scripts, false ) ||
				$scripts === get_option( 'celestial_cookie_consent_scripts', array() ) ) {
				$status = 'saved';
			}
		}
	}

	wp_safe_redirect( add_query_arg( 'ccc-scripts-status', $status, admin_url( 'options-general.php?page=celestial-cookie-consent' ) ), 303 );
	exit;
}
add_action( 'admin_post_celestial_cookie_consent_save_scripts', 'celestial_cookie_consent_save_scripts' );

function celestial_cookie_consent_admin_menu() {
	$page = add_options_page(
		'Celestial Cookie Consent',
		__( 'Cookie Consent', 'celestial-cookie-consent' ),
		'manage_options',
		'celestial-cookie-consent',
		'celestial_cookie_consent_render_settings'
	);

	if ( $page ) {
		add_action( 'admin_enqueue_scripts', static function ( $hook ) use ( $page ) {
			if ( $hook !== $page || ! is_readable( __DIR__ . '/build/backend/index.asset.php' ) ) {
				return;
			}

			$asset = require __DIR__ . '/build/backend/index.asset.php';
			wp_enqueue_style( 'wp-components' );
			wp_enqueue_script( 'celestial-cookie-consent-admin', plugins_url( 'build/backend/index.js', __FILE__ ), $asset['dependencies'], $asset['version'], true );
			wp_set_script_translations( 'celestial-cookie-consent-admin', 'celestial-cookie-consent', __DIR__ . '/languages' );
		} );
	}
}
add_action( 'admin_menu', 'celestial_cookie_consent_admin_menu' );

function celestial_cookie_consent_render_settings() {
	echo '<div class="wrap"><h1>Celestial Cookie Consent</h1><div id="celestial-cookie-consent-settings"></div></div>';

	if ( celestial_cookie_consent_can_manage_scripts() ) {
		$status = isset( $_GET['ccc-scripts-status'] ) && is_string( $_GET['ccc-scripts-status'] ) ? sanitize_key( wp_unslash( $_GET['ccc-scripts-status'] ) ) : '';
		$config = array(
			'actionUrl' => admin_url( 'admin-post.php' ),
			'nonce'     => wp_create_nonce( 'celestial_cookie_consent_save_scripts' ),
			'scripts'   => get_option( 'celestial_cookie_consent_scripts', array() ),
			'status'    => $status,
		);
		echo '<script type="application/json" id="celestial-cookie-consent-scripts-config">' . wp_json_encode( $config, JSON_HEX_TAG ) . '</script>';
	}
	if ( ! is_readable( __DIR__ . '/build/backend/index.asset.php' ) ) {
		echo '<p>' . esc_html__( 'Build the admin assets with npm run build.', 'celestial-cookie-consent' ) . '</p>';
	}
}

function celestial_cookie_consent_register_banner_block() {
	$block_directory = __DIR__ . '/build/block/banner';
	if ( ! function_exists( 'wp_register_script_module' ) || ! is_readable( $block_directory . '/block.json' ) ) {
		return;
	}

	register_block_type( $block_directory );
	wp_set_script_translations(
		generate_block_asset_handle( 'celestial-cookie-consent/banner', 'editorScript' ),
		'celestial-cookie-consent',
		__DIR__ . '/languages'
	);
}
add_action( 'init', 'celestial_cookie_consent_register_banner_block' );

function celestial_cookie_consent_schedule_banner() {
	if ( is_feed() || ! WP_Block_Type_Registry::get_instance()->is_registered( 'celestial-cookie-consent/banner' ) ) {
		return;
	}

	$render_banner = static function () {
		return do_blocks( '<!-- wp:celestial-cookie-consent/banner /-->' );
	};

	if ( wp_is_block_theme() ) {
		// Block themes render their template before wp_head, which prints the import map.
		$banner = $render_banner();
		$render_banner = static function () use ( $banner ) {
			return $banner;
		};
	}

	add_action(
		'wp_footer',
		static function () use ( $render_banner ) {
			echo $render_banner(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The block escapes its markup when rendered.
		},
		5
	);
}
add_action( 'wp_enqueue_scripts', 'celestial_cookie_consent_schedule_banner' );

function celestial_cookie_consent_default_block_styles( $theme_json ) {
	return $theme_json->update_with(
		array(
			'version' => 2,
			'styles'  => array(
				'blocks' => array(
					'celestial-cookie-consent/banner' => array(
						'border'     => array(
							'color'  => 'color-mix(in srgb, currentColor 18%, transparent)',
							'radius' => '0.5rem',
							'style'  => 'solid',
							'width'  => '1px',
						),
						'shadow'     => '0 0.4rem 1.5rem rgb(0 0 0 / 10%)',
						'spacing'    => array(
							'padding' => array(
								'top'    => '1.25rem',
								'right'  => '1.25rem',
								'bottom' => '1.25rem',
								'left'   => '1.25rem',
							),
						),
						// Preserve the banner's text hierarchy with fluid typography enabled.
						'typography' => array(
							'fontSize'   => 'calc(1rem)',
							'lineHeight' => '1.5',
						),
						'elements'   => array(
							'h2' => array( 'typography' => array( 'fontSize' => 'calc(1.25em)', 'fontWeight' => '700' ) ),
							'h3' => array( 'typography' => array( 'fontSize' => 'calc(1.125em)' ) ),
						),
					),
				),
			),
		)
	);
}
add_filter( 'wp_theme_json_data_default', 'celestial_cookie_consent_default_block_styles' );

function celestial_cookie_consent_prepare_banner_block() {
	static $prepared = false;

	if ( $prepared || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}
	$prepared = true;

	$scripts = get_option( 'celestial_cookie_consent_scripts', array() );
	$version = $scripts ? hash( 'sha256', wp_json_encode( $scripts ) ) : '';
	$items = array();
	foreach ( $scripts as $index => $script ) {
		$items[] = array_merge(
			$script,
			array(
				'index'         => $index,
				'descriptionId' => $script['description'] === '' ? null : 'celestial-cookie-consent-description-' . $index,
			)
		);
	}

	wp_interactivity_state(
		'celestial-cookie-consent',
		array(
			'message'  => get_option( 'celestial_cookie_consent_message' ),
			'policyUrl' => esc_url_raw( get_privacy_policy_url(), array( 'http', 'https' ) ),
			'version'  => $version,
			'scripts'  => $items,
			'labels'   => array(
				'reset'        => __( 'Reset consent', 'celestial-cookie-consent' ),
				'title'        => __( 'Your cookie choices', 'celestial-cookie-consent' ),
				'policy'       => __( 'Privacy policy', 'celestial-cookie-consent' ),
				'customize'    => __( 'Customize', 'celestial-cookie-consent' ),
				'reject'       => __( 'Reject all', 'celestial-cookie-consent' ),
				'accept'       => __( 'Accept all', 'celestial-cookie-consent' ),
				'preferences'  => __( 'Cookie preferences', 'celestial-cookie-consent' ),
				'close'        => __( 'Close preferences', 'celestial-cookie-consent' ),
				'instructions' => __( 'Choose which optional scripts this site can run.', 'celestial-cookie-consent' ),
				'necessary'    => __( 'Necessary', 'celestial-cookie-consent' ),
				'active'       => __( 'Always active', 'celestial-cookie-consent' ),
				'remember'     => __( 'The consent cookie remembers your preferences.', 'celestial-cookie-consent' ),
				'storageError' => __( 'Could not change your cookie preferences. Check your browser settings and try again.', 'celestial-cookie-consent' ),
				'save'         => __( 'Save preferences', 'celestial-cookie-consent' ),
			),
		)
	);

	return true;
}

function celestial_cookie_consent_get_outline_class() {
	// Let WordPress generate the active theme's core/button Outline variation.
	$outline = new WP_HTML_Tag_Processor(
		do_blocks( '<!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><button class="wp-block-button__link wp-element-button">Outline</button></div><!-- /wp:button -->' )
	);

	if ( $outline->next_tag( 'DIV' ) && preg_match( '/(?:^| )(is-style-outline--[0-9]+)(?: |$)/', (string) $outline->get_attribute( 'class' ), $matches ) ) {
		return 'is-style-outline ' . $matches[1];
	}

	return 'is-style-outline';
}
