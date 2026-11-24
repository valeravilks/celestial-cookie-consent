import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';

import BannerSettingsForm from './components/BannerSettingsForm';
import ScriptsSettingsForm from './components/ScriptsSettingsForm';
import useScriptRows from './hooks/useScriptRows';
import useSettings from './hooks/useSettings';

function BannerSettings() {
	const { message, setMessage, loading, saving, notice, setNotice, saveSettings } = useSettings();

	function handleSubmit( event ) {
		event.preventDefault();
		if ( ! saving ) {
			saveSettings();
		}
	}

	return (
		<BannerSettingsForm
			message={ message }
			loading={ loading }
			saving={ saving }
			notice={ notice }
			showForm={ typeof message === 'string' }
			onMessageChange={ setMessage }
			onDismissNotice={ () => setNotice( null ) }
			onSubmit={ handleSubmit }
		/>
	);
}

function ScriptsSettings( { config } ) {
	const { rows, serializedScripts, addRow, updateRow, removeRow } = useScriptRows( config.scripts );

	return (
		<ScriptsSettingsForm
			actionUrl={ config.actionUrl }
			nonce={ config.nonce }
			status={ config.status }
			rows={ rows }
			serializedScripts={ serializedScripts }
			onAddRow={ addRow }
			onRowChange={ updateRow }
			onRemoveRow={ removeRow }
		/>
	);
}

domReady( () => {
	const container = document.getElementById( 'celestial-cookie-consent-settings' );
	const scriptsConfig = document.getElementById( 'celestial-cookie-consent-scripts-config' );
	createRoot( container ).render(
		<>
			<BannerSettings />
			{ scriptsConfig && <ScriptsSettings config={ JSON.parse( scriptsConfig.textContent ) } /> }
		</>
	);
} );
