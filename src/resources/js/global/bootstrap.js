/**
 * Uncompressed JS bootstrap
 *
 * The scripts loaded by this file all get compressed into resources/js/compressed/blocks.js
 *
 * To use the uncompressed scripts, add this to your config/blocks.php file:
 * $blocksConfig['useCompressedJs'] = false;
 */

(function(){

var scripts =
[
	'blocks.js',

	'ui/Select.js',
	'ui/BaseDrag.js',
	'ui/Drag.js',
	'ui/DragMove.js',
	'ui/DragDrop.js',
	'ui/DragSort.js',
	'ui/DataTableSorter.js',

	'ui/InputGenerator.js',
	'ui/HandleGenerator.js',
	'ui/EntryUrlFormatGenerator.js',
	'ui/SlugGenerator.js',
	'ui/NiceText.js',

	'ui/Modal.js',
	'ui/HUD.js',
	'ui/Menu.js',
	'ui/Pill.js',
	'ui/SelectMenu.js',
	'ui/LightSwitch.js',
	'ui/PasswordInput.js',
	'ui/MixedInput.js',
	'ui/AdminPane.js',
	'ui/FieldToggle.js',
	'ui/CheckboxSelect.js',

	'ui/LinksBlock.js',

	'ui/AdminTable.js'
];

for (var i = 0; i < scripts.length; i++)
{
	document.write('<script type="text/javascript" src="'+Blocks.resourceUrl+'js/global/'+scripts[i]+'"></script>');
}


})();
