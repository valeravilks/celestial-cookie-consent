<?php
/**
 * Plugin Name: Celestial Cookie Consent
 * Description: Lets visitors set their cookie preferences.
 * Version: 0.2.0
 * Text Domain: celestial-cookie-consent
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

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

	check_admin_referer( 'celestial_cookie_consent_save_scripts' );

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
			if ( $hook !== $page || ! is_readable( __DIR__ . '/build/index.asset.php' ) ) {
				return;
			}

			$asset = require __DIR__ . '/build/index.asset.php';
			wp_enqueue_style( 'wp-components' );
			wp_enqueue_script( 'celestial-cookie-consent-admin', plugins_url( 'build/index.js', __FILE__ ), $asset['dependencies'], $asset['version'], true );
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
	if ( ! is_readable( __DIR__ . '/build/index.asset.php' ) ) {
		echo '<p>' . esc_html__( 'Build the admin assets with npm run build.', 'celestial-cookie-consent' ) . '</p>';
	}
}

function celestial_cookie_consent_enqueue_banner() {
	wp_enqueue_script( 'celestial-cookie-consent-banner', plugins_url( 'assets/banner.js', __FILE__ ), array(), '0.2.3', true );
	wp_enqueue_style( 'celestial-cookie-consent-banner', plugins_url( 'assets/banner.css', __FILE__ ), array(), '0.2.2' );
}
add_action( 'wp_enqueue_scripts', 'celestial_cookie_consent_enqueue_banner' );

function celestial_cookie_consent_render_banner() {
	$scripts = get_option( 'celestial_cookie_consent_scripts', array() );
	$version = $scripts ? hash( 'sha256', wp_json_encode( $scripts ) ) : '';
	?>
	<div id="celestial-cookie-consent-banner" data-version="<?php echo esc_attr( $version ); ?>" hidden>
		<button type="button" data-open aria-haspopup="dialog" aria-controls="celestial-cookie-consent-choices" hidden><?php esc_html_e( 'Cookie settings', 'celestial-cookie-consent' ); ?></button>
		<button type="button" data-debug-reset hidden><?php esc_html_e( 'Reset consent', 'celestial-cookie-consent' ); ?></button>
		<section id="celestial-cookie-consent-panel" aria-labelledby="celestial-cookie-consent-title" hidden>
			<h2 id="celestial-cookie-consent-title"><?php esc_html_e( 'Your cookie choices', 'celestial-cookie-consent' ); ?></h2>
			<p><?php echo esc_html( get_option( 'celestial_cookie_consent_message' ) ); ?>
				<?php if ( get_privacy_policy_url() ) : ?>
					<a href="<?php echo esc_url( get_privacy_policy_url() ); ?>"><?php esc_html_e( 'Privacy policy', 'celestial-cookie-consent' ); ?></a>
				<?php endif; ?>
			</p>
			<div class="ccc-actions">
				<button type="button" data-customize aria-haspopup="dialog" aria-controls="celestial-cookie-consent-choices"><?php esc_html_e( 'Customize', 'celestial-cookie-consent' ); ?></button>
				<button type="button" data-choice="none"><?php esc_html_e( 'Reject all', 'celestial-cookie-consent' ); ?></button>
				<button type="button" data-choice="all"><?php esc_html_e( 'Accept all', 'celestial-cookie-consent' ); ?></button>
			</div>
		</section>
		<dialog id="celestial-cookie-consent-choices" aria-labelledby="celestial-cookie-consent-dialog-title">
			<header class="ccc-dialog-header">
				<h2 id="celestial-cookie-consent-dialog-title"><?php esc_html_e( 'Cookie preferences', 'celestial-cookie-consent' ); ?></h2>
				<button type="button" data-close aria-label="<?php esc_attr_e( 'Close preferences', 'celestial-cookie-consent' ); ?>" autofocus>&times;</button>
			</header>
			<div class="ccc-dialog-body">
				<p><?php esc_html_e( 'Choose which optional scripts this site can run.', 'celestial-cookie-consent' ); ?></p>
				<ul class="ccc-options">
					<li>
						<div class="ccc-option-heading"><strong><?php esc_html_e( 'Necessary', 'celestial-cookie-consent' ); ?></strong><span><?php esc_html_e( 'Always active', 'celestial-cookie-consent' ); ?></span></div>
						<p><?php esc_html_e( 'The consent cookie remembers your preferences.', 'celestial-cookie-consent' ); ?></p>
					</li>
					<?php foreach ( $scripts as $index => $script ) : ?>
						<li>
							<label class="ccc-option-heading">
								<strong><?php echo esc_html( $script['name'] ); ?></strong>
								<input type="checkbox" role="switch" data-script-choice<?php if ( '' !== $script['description'] ) : ?> aria-describedby="celestial-cookie-consent-description-<?php echo esc_attr( $index ); ?>"<?php endif; ?>>
							</label>
							<?php if ( '' !== $script['description'] ) : ?>
								<p id="celestial-cookie-consent-description-<?php echo esc_attr( $index ); ?>"><?php echo esc_html( $script['description'] ); ?></p>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="ccc-actions ccc-dialog-actions">
				<button type="button" data-choice="none"><?php esc_html_e( 'Reject all', 'celestial-cookie-consent' ); ?></button>
				<button type="button" data-choice="custom"><?php esc_html_e( 'Save preferences', 'celestial-cookie-consent' ); ?></button>
				<button type="button" data-choice="all"><?php esc_html_e( 'Accept all', 'celestial-cookie-consent' ); ?></button>
			</div>
		</dialog>
		<?php if ( $scripts ) : ?>
			<script type="application/json" data-scripts><?php
				echo wp_json_encode( array_column( $scripts, 'code' ), JSON_HEX_TAG );
			?></script>
		<?php endif; ?>
	</div>
	<?php
}
add_action( 'wp_footer', 'celestial_cookie_consent_render_banner' );
