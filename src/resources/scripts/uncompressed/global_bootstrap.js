/**
 * Uncompressed JS bootstrap
 *
 * The scripts loaded by this file all get compressed into resources/scripts/compressed/blocks.js
 *
 * To use the uncompressed scripts, add this to your config/blocks.php file:
 * $blocksConfig['useCompressedJs'] = false;
 */

(function(){


var scripts = 
[
	'lib/jquery-1.7.1.js',
	'lib/rangy-1.2.3/rangy-core.js',
	'lib/Base.js',
	'lib/history.js-1.7.1/history.js',
	'lib/history.js-1.7.1/history.adapter.jquery.js',

	'blocks.js',

	'ui/Select.js',
	'ui/DragCore.js',
	'ui/Drag.js',
	'ui/DragMove.js',
	'ui/DragSort.js',

	'ui/InputGenerator.js',
	'ui/HandleGenerator.js',
	'ui/EntryUrlFormatGenerator.js',
	'ui/NiceText.js',

	'ui/Modal.js',
	'ui/HUD.js',
	'ui/Menu.js',
	'ui/Pill.js',
	'ui/RTE.js',
	'ui/SelectMenu.js',
	'ui/LightSwitch.js',
	'ui/BlockEditor.js',
	'ui/PasswordInput.js',
	'ui/AdminPane.js'
];

for (var i = 0; i < scripts.length; i++)
{
	document.write('<script type="text/javascript" src="'+b.resourceUrl+'scripts/uncompressed/global/'+scripts[i]+'"></script>');
}


})();
