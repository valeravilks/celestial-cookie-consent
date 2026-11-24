import { useState } from '@wordpress/element';

let nextRowId = 0;

function createRow( script = { name: '', description: '', code: '' } ) {
	return { ...script, id: ++nextRowId };
}

export default function useScriptRows( initialScripts ) {
	const [ rows, setRows ] = useState( () => initialScripts.map( createRow ) );
	const scripts = rows.map( ( { name, description, code } ) => ( { name, description, code } ) );

	function updateRow( id, field, value ) {
		setRows( ( current ) => current.map( ( row ) => row.id === id ? { ...row, [ field ]: value } : row ) );
	}

	function addRow() {
		setRows( ( current ) => [ ...current, createRow() ] );
	}

	function removeRow( id ) {
		setRows( ( current ) => current.filter( ( row ) => row.id !== id ) );
	}

	return { rows, serializedScripts: JSON.stringify( scripts ), addRow, updateRow, removeRow };
}
