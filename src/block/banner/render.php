<?php
/**
 * Renders the cookie consent banner.
 */

defined( 'ABSPATH' ) || exit;

if ( ! celestial_cookie_consent_prepare_banner_block() ) {
	return;
}

$position      = 'right' === ( $attributes['position'] ?? '' ) ? 'right' : 'left';
$outline_class = celestial_cookie_consent_get_outline_class();
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'ccc-position-' . $position ) ); ?> data-wp-interactive="celestial-cookie-consent" data-wp-init="callbacks.initialize" data-wp-bind--hidden="state.isInitializing" hidden>
	<button type="button" data-wp-bind--hidden="state.hideReset" data-wp-on--click="actions.reset" data-wp-text="state.labels.reset" hidden></button>
	<section aria-labelledby="celestial-cookie-consent-title" data-wp-bind--hidden="state.hidePanel" hidden>
		<h2 id="celestial-cookie-consent-title" data-wp-text="state.labels.title"></h2>
		<p><span data-wp-text="state.message"></span> <a data-wp-bind--hidden="state.hidePolicy" data-wp-bind--href="state.policyUrl" data-wp-text="state.labels.policy" hidden></a></p>
		<div class="wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex ccc-actions">
			<div class="wp-block-button <?php echo esc_attr( $outline_class ); ?>">
				<button type="button" class="wp-block-button__link wp-element-button" aria-haspopup="dialog" aria-controls="celestial-cookie-consent-choices" data-wp-on--click="actions.openPreferences" data-wp-text="state.labels.customize"></button>
			</div>
			<div class="wp-block-button">
				<button type="button" class="wp-block-button__link wp-element-button" data-choice="none" data-wp-on--click="actions.save" data-wp-text="state.labels.reject"></button>
			</div>
			<div class="wp-block-button">
				<button type="button" class="wp-block-button__link wp-element-button" data-choice="all" data-wp-on--click="actions.save" data-wp-text="state.labels.accept"></button>
			</div>
		</div>
	</section>
	<dialog id="celestial-cookie-consent-choices" aria-labelledby="celestial-cookie-consent-dialog-title">
		<header>
			<h3 id="celestial-cookie-consent-dialog-title" data-wp-text="state.labels.preferences"></h3>
			<button type="button" data-wp-bind--aria-label="state.labels.close" data-wp-on--click="actions.closePreferences" autofocus>&times;</button>
		</header>
		<div>
			<p data-wp-text="state.labels.instructions"></p>
			<ul>
				<li>
					<div><strong data-wp-text="state.labels.necessary"></strong><span data-wp-text="state.labels.active"></span></div>
					<p data-wp-text="state.labels.remember"></p>
				</li>
				<template data-wp-each="state.scripts" data-wp-each-key="context.item.index">
					<li>
						<p>
							<label>
								<span data-wp-text="context.item.name"></span>
								<input type="checkbox" role="switch" data-wp-bind--checked="state.isChecked" data-wp-on--change="actions.toggleScript" data-wp-bind--aria-describedby="context.item.descriptionId" />
							</label>
						</p>
						<p data-wp-bind--id="context.item.descriptionId" data-wp-bind--hidden="state.hideDescription" data-wp-text="context.item.description"></p>
					</li>
				</template>
			</ul>
		</div>
		<div class="wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex ccc-actions">
			<div class="wp-block-button">
				<button type="button" class="wp-block-button__link wp-element-button" data-choice="none" data-wp-on--click="actions.save" data-wp-text="state.labels.reject"></button>
			</div>
			<div class="wp-block-button">
				<button type="button" class="wp-block-button__link wp-element-button" data-choice="custom" data-wp-on--click="actions.save" data-wp-text="state.labels.save"></button>
			</div>
			<div class="wp-block-button">
				<button type="button" class="wp-block-button__link wp-element-button" data-choice="all" data-wp-on--click="actions.save" data-wp-text="state.labels.accept"></button>
			</div>
		</div>
	</dialog>
</div>
