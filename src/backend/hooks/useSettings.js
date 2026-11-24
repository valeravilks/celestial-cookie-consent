import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const key = 'celestial_cookie_consent_message';

const settingsPath = '/wp/v2/settings?_fields=' + key;

export default function useSettings() {
	const [ message, setMessage ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	useEffect( () => {
		apiFetch( { path: settingsPath } )
			.then( ( settings ) => setMessage( settings[ key ] ) )
			.catch( () => setNotice( {
				status: 'error',
				text: __( 'Could not load settings.', 'celestial-cookie-consent' ),
			} ) )
			.finally( () => setLoading( false ) );
	}, [] );

	async function saveSettings() {
		setSaving( true );
		setNotice( null );
		try {
			const settings = await apiFetch( {
				path: settingsPath,
				method: 'POST',
				data: { [ key ]: message },
			} );
			setMessage( settings[ key ] );
			setNotice( { status: 'success', text: __( 'Settings saved.', 'celestial-cookie-consent' ) } );
		} catch {
			setNotice( { status: 'error', text: __( 'Could not save settings.', 'celestial-cookie-consent' ) } );
		} finally {
			setSaving( false );
		}
	}

	return { message, setMessage, loading, saving, notice, setNotice, saveSettings };
}
