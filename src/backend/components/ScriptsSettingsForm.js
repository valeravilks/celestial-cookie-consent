import { Button, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import ScriptRow from './ScriptRow';

export default function ScriptsSettingsForm( { actionUrl, nonce, status, rows, serializedScripts, onAddRow, onRowChange, onRemoveRow } ) {
	return (
		<form action={ actionUrl } method="post">
			<h2>{ __( 'Optional scripts', 'celestial-cookie-consent' ) }</h2>
			{ status === 'saved' && <Notice status="success" isDismissible={ false }>{ __( 'Scripts saved.', 'celestial-cookie-consent' ) }</Notice> }
			{ status === 'error' && <Notice status="error" isDismissible={ false }>{ __( 'Could not save scripts.', 'celestial-cookie-consent' ) }</Notice> }
			<input type="hidden" name="action" value="celestial_cookie_consent_save_scripts" />
			<input type="hidden" name="_wpnonce" value={ nonce } />
			<input type="hidden" name="scripts" value={ serializedScripts } />
			{ rows.map( ( row ) => (
				<ScriptRow key={ row.id } row={ row } onChange={ onRowChange } onRemove={ onRemoveRow } />
			) ) }
			<p>
				<Button variant="secondary" onClick={ onAddRow }>{ __( 'Add script', 'celestial-cookie-consent' ) }</Button>
				{ ' ' }
				<Button variant="primary" type="submit">{ __( 'Save scripts', 'celestial-cookie-consent' ) }</Button>
			</p>
		</form>
	);
}
