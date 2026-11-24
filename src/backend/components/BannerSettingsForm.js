import { Button, Notice, Panel, PanelBody, Spinner, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function BannerSettingsForm( { message, loading, saving, notice, showForm, onMessageChange, onDismissNotice, onSubmit } ) {
	return (
		<>
			{ notice && <Notice status={ notice.status } onRemove={ onDismissNotice }>{ notice.text }</Notice> }
			{ loading && <Spinner /> }
			{ showForm && (
				<form onSubmit={ onSubmit }>
					<Panel>
						<PanelBody title={ __( 'Cookie banner', 'celestial-cookie-consent' ) }>
							<TextareaControl label={ __( 'Banner message', 'celestial-cookie-consent' ) } value={ message } onChange={ onMessageChange } disabled={ saving } />
							<Button variant="primary" type="submit" isBusy={ saving } disabled={ saving }>{ __( 'Save settings', 'celestial-cookie-consent' ) }</Button>
						</PanelBody>
					</Panel>
				</form>
			) }
		</>
	);
}
