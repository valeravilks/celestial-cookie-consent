import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { PanelBody, SelectControl } from '@wordpress/components';
import { RawHTML } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import './style.css';
import { BannerButton, ButtonGroup } from './editor-buttons';

function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'ccc-editor-preview' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Cookie banner', 'celestial-cookie-consent' ) }>
					<SelectControl
						label={ __( 'Position', 'celestial-cookie-consent' ) }
						value={ attributes.position }
						options={ [
							{ label: __( 'Bottom left', 'celestial-cookie-consent' ), value: 'left' },
							{ label: __( 'Bottom right', 'celestial-cookie-consent' ), value: 'right' },
						] }
						onChange={ ( position ) => setAttributes( { position } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<h2>{ __( 'Your cookie choices', 'celestial-cookie-consent' ) }</h2>
				<p>{ __( 'The live banner uses the text and scripts configured in Cookie Consent settings.', 'celestial-cookie-consent' ) }</p>
				<ButtonGroup className="ccc-editor-actions">
					<BannerButton outline tabIndex={ -1 }>{ __( 'Customize', 'celestial-cookie-consent' ) }</BannerButton>
					<BannerButton tabIndex={ -1 }>{ __( 'Reject all', 'celestial-cookie-consent' ) }</BannerButton>
					<BannerButton tabIndex={ -1 }>{ __( 'Accept all', 'celestial-cookie-consent' ) }</BannerButton>
				</ButtonGroup>
			</div>
		</>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
	deprecated: [
		{
			apiVersion: metadata.apiVersion,
			supports: metadata.supports,
			attributes: {
				...metadata.attributes,
				legacyMarkup: { type: 'string', source: 'html' },
			},
			// Older WordPress saves may have stripped parts of the static markup.
			// Match it for validation, then carry only the block's position forward.
			save: ( { attributes } ) => <RawHTML>{ attributes.legacyMarkup }</RawHTML>,
			migrate: ( { position } ) => ( { position } ),
		},
	],
} );
