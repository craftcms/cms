(function(){


var scripts = 
[
	'lib/jquery-1.7.1.js',
	'lib/Base.js',
	'lib/rangy-1.2.2/rangy-core.js',
	
	'blx.js',
	'ui/BlocksSelect.js',
	'ui/DragCore.js',
	'ui/Drag.js',
	'ui/DragMove.js',
	'ui/DragSort.js',
	'ui/InputGenerator.js',
	'ui/HandleGenerator.js',
	'ui/EntryUrlFormatGenerator.js',
	'ui/HUD.js',
	'ui/Menu.js',
	'ui/Modal.js',
	'ui/Pill.js',
	'ui/RTE.js',
	'ui/Select.js',
	'ui/SelectMenu.js',
	'ui/Switch.js',
	'ui/BlocksSelectModal.js',
	'ui/CreateBlockModal.js'
];

for (var i = 0; i < scripts.length; i++)
{
	document.write('<script type="text/javascript" src="'+resourceUrl+'scripts/uncompressed/'+scripts[i]+'"></script>');
}


})();
