import { useEntityRecord } from '@wordpress/core-data';
import { Button, Notice, Panel, PanelBody, Spinner, TextareaControl, TextControl } from '@wordpress/components';
import domReady from '@wordpress/dom-ready';
import { createRoot, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const key = 'celestial_cookie_consent_message';

function SettingsPage() {
	const site = useEntityRecord( 'root', 'site' );
	const message = site.editedRecord?.[ key ];
	const [ saving, setSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	async function save( event ) {
		event.preventDefault();
		setSaving( true );
		setNotice( null );
		try {
			await site.save( { throwOnError: true } );
			setNotice( { status: 'success', text: __( 'Settings saved.', 'celestial-cookie-consent' ) } );
		} catch {
			setNotice( { status: 'error', text: __( 'Could not save settings.', 'celestial-cookie-consent' ) } );
		} finally {
			setSaving( false );
		}
	}

	return (
		<>
			{ notice && <Notice status={ notice.status } onRemove={ () => setNotice( null ) }>{ notice.text }</Notice> }
			{ site.isResolving || ! site.hasResolved ? <Spinner /> : typeof message !== 'string' ? (
				<Notice status="error" isDismissible={ false }>{ __( 'Could not load settings.', 'celestial-cookie-consent' ) }</Notice>
			) : (
				<form onSubmit={ save }>
					<Panel>
						<PanelBody title={ __( 'Cookie banner', 'celestial-cookie-consent' ) }>
							<TextareaControl label={ __( 'Banner message', 'celestial-cookie-consent' ) } value={ message } onChange={ ( value ) => site.edit( { [ key ]: value } ) } disabled={ saving } />
							<Button variant="primary" type="submit" isBusy={ saving } disabled={ saving }>{ __( 'Save settings', 'celestial-cookie-consent' ) }</Button>
						</PanelBody>
					</Panel>
				</form>
			) }
		</>
	);
}

let nextRowId = 0;

function createRow( script = { name: '', description: '', code: '' } ) {
	return { ...script, id: ++nextRowId };
}

function ScriptsSettings( { config } ) {
	const [ rows, setRows ] = useState( () => config.scripts.map( createRow ) );
	const scripts = rows.map( ( { name, description, code } ) => ( { name, description, code } ) );

	function updateRow( id, field, value ) {
		setRows( ( current ) => current.map( ( row ) => row.id === id ? { ...row, [ field ]: value } : row ) );
	}

	return (
		<form action={ config.actionUrl } method="post">
			<h2>{ __( 'Optional scripts', 'celestial-cookie-consent' ) }</h2>
			{ config.status === 'saved' && <Notice status="success" isDismissible={ false }>{ __( 'Scripts saved.', 'celestial-cookie-consent' ) }</Notice> }
			{ config.status === 'error' && <Notice status="error" isDismissible={ false }>{ __( 'Could not save scripts.', 'celestial-cookie-consent' ) }</Notice> }
			<input type="hidden" name="action" value="celestial_cookie_consent_save_scripts" />
			<input type="hidden" name="_wpnonce" value={ config.nonce } />
			<input type="hidden" name="scripts" value={ JSON.stringify( scripts ) } />
			{ rows.map( ( row ) => (
				<Panel key={ row.id }>
					<PanelBody title={ row.name || __( 'New script', 'celestial-cookie-consent' ) }>
						<TextControl label={ __( 'Name', 'celestial-cookie-consent' ) } value={ row.name } onChange={ ( value ) => updateRow( row.id, 'name', value ) } required />
						<TextareaControl label={ __( 'Description', 'celestial-cookie-consent' ) } value={ row.description } onChange={ ( value ) => updateRow( row.id, 'description', value ) } />
						<TextareaControl label={ __( 'JavaScript code', 'celestial-cookie-consent' ) } help={ __( 'Paste JavaScript without HTML script tags. It runs after the visitor accepts optional cookies.', 'celestial-cookie-consent' ) } value={ row.code } onChange={ ( value ) => updateRow( row.id, 'code', value ) } required />
						<Button variant="tertiary" isDestructive onClick={ () => setRows( ( current ) => current.filter( ( item ) => item.id !== row.id ) ) }>{ __( 'Remove script', 'celestial-cookie-consent' ) }</Button>
					</PanelBody>
				</Panel>
			) ) }
			<p>
				<Button variant="secondary" onClick={ () => setRows( ( current ) => [ ...current, createRow() ] ) }>{ __( 'Add script', 'celestial-cookie-consent' ) }</Button>
				{ ' ' }
				<Button variant="primary" type="submit">{ __( 'Save scripts', 'celestial-cookie-consent' ) }</Button>
			</p>
		</form>
	);
}

domReady( () => {
	const container = document.getElementById( 'celestial-cookie-consent-settings' );
	const scriptsConfig = document.getElementById( 'celestial-cookie-consent-scripts-config' );
	createRoot( container ).render(
		<>
			<SettingsPage />
			{ scriptsConfig && <ScriptsSettings config={ JSON.parse( scriptsConfig.textContent ) } /> }
		</>
	);
} );
