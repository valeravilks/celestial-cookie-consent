import { Button, Panel, PanelBody, TextareaControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function ScriptRow( { row, onChange, onRemove } ) {
	return (
		<Panel>
			<PanelBody title={ row.name || __( 'New script', 'celestial-cookie-consent' ) }>
				<TextControl label={ __( 'Name', 'celestial-cookie-consent' ) } value={ row.name } onChange={ ( value ) => onChange( row.id, 'name', value ) } required />
				<TextareaControl label={ __( 'Description', 'celestial-cookie-consent' ) } value={ row.description } onChange={ ( value ) => onChange( row.id, 'description', value ) } />
				<TextareaControl label={ __( 'JavaScript code', 'celestial-cookie-consent' ) } help={ __( 'Paste JavaScript without HTML script tags. It runs after the visitor accepts optional cookies.', 'celestial-cookie-consent' ) } value={ row.code } onChange={ ( value ) => onChange( row.id, 'code', value ) } required />
				<Button variant="tertiary" isDestructive onClick={ () => onRemove( row.id ) }>{ __( 'Remove script', 'celestial-cookie-consent' ) }</Button>
			</PanelBody>
		</Panel>
	);
}
