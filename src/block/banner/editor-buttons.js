export function ButtonGroup( { children, className } ) {
	return <div className={ `wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex ${ className }` }>{ children }</div>;
}

export function BannerButton( { outline, ...props } ) {
	return (
		<div className={ outline ? 'wp-block-button is-style-outline' : 'wp-block-button' }>
			<button type="button" className="wp-block-button__link wp-element-button" { ...props } />
		</div>
	);
}
