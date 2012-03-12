(function(){


var scripts = 
[
	'blx.js',

	'ui/Select.js',
	'ui/DragCore.js',
	'ui/Drag.js',
	'ui/DragMove.js',
	'ui/DragSort.js',

	'ui/InputGenerator.js',
	'ui/HandleGenerator.js',
	'ui/EntryUrlFormatGenerator.js',
	'ui/NiceText.js',
	'ui/TitleInput.js',

	'ui/Modal.js',
	'ui/BlocksSelectModal.js',
	'ui/CreateBlockModal.js',

	'ui/HUD.js',
	'ui/Menu.js',
	'ui/Pill.js',
	'ui/RTE.js',
	'ui/SelectMenu.js',
	'ui/LightSwitch.js',
	'ui/BlocksSelect.js',
	'ui/PasswordInput.js'
];

for (var i = 0; i < scripts.length; i++)
{
	document.write('<script type="text/javascript" src="'+resourceUrl+'scripts/uncompressed/'+scripts[i]+'"></script>');
}


})();
