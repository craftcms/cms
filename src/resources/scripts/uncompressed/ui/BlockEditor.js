(function($) {


/**
 * Block Editor
 */
b.ui.BlockEditor = b.Base.extend({

	$container: null,
	$sidebar: null,
	$addLink: null,
	$settingsContainer: null,

	freshBlockSettingsHtml: null,
	freshBlocktypeSettingsHtml: null,

	inputName: null,

	blocks: null,
	selectedBlock: null,
	totalNewBlocks: 0,

	init: function(container)
	{
		this.$container = $(container);

		// Is this already a block editor?
		if (this.$container.data('blockeditor'))
		{
			b.log('Double-instantiating a blocks select on an element');
			this.$container.data('blockeditor').destroy();
		}

		this.$container.data('blockeditor', this);

		this.inputName = this.$container.attr('data-input-name');

		// Find the DOM nodes
		this.$sidebar = this.$container.children('.sidebar:first');
		this.$addLink = this.$sidebar.children('ul').children('li:last-child').children('a');
		this.$settingsContainer = this.$container.children('.blocksettings:first');

		// Get the fresh block settings HTML
		var $freshBlockSettings = this.$container.children('.freshblocksettings:first').remove().removeClass('freshblocksettings');
		this.freshBlockSettingsHtml = $freshBlockSettings.html();

		// Get the fresh blocktype settings
		this.freshBlocktypeSettingsHtml = {};
		var $freshBlocktypeSettings = this.$container.children('.freshblocktypesettings:first').children();
		for (var i = 0; i < $freshBlocktypeSettings.length; i++)
		{
			var $settings = $($freshBlocktypeSettings[i]),
				blocktype = $settings.attr('data-blocktype-class'),
				settingsHtml = $settings.html();

			this.freshBlocktypeSettingsHtml[blocktype] = settingsHtml;
		}

		// Initialize the blocks
		this.blocks = {};
		var $blockLinks = this.$sidebar.find('a.block-item'),
			$blockSettings = this.$settingsContainer.children();

		for (var i = 0; i < $blockLinks.length; i++)
		{
			var $link = $($blockLinks[i]),
				blockId = $link.attr('data-block-id'),
				$settings = $blockSettings.filter('[data-block-id='+blockId+']:first');

			var block = new b.ui.BlockEditor.Block(this, blockId, $link, $settings);
			this.blocks[blockId] = block;

			// Is this a new block? (Could be if there were validation errors)
			if (blockId.substr(0, 3) == 'new')
				this.totalNewBlocks++;
		}

		this.addListener(this.$addLink, 'click', 'addBlock');
	},

	selectBlock: function(blockId)
	{
		this.blocks[blockId].select();
	},

	addBlock: function()
	{
		this.totalNewBlocks++;

		var blockId = 'new'+(this.totalNewBlocks),
			$li = $('<li/>').insertBefore(this.$addLink.parent()),
			$link = $('<a class="block-item" data-block-id="'+blockId+'">' +
					'<span class="icon icon137"/>' +
					'<span class="block-name">Untitled</span>' +
					'<div class="block-type"></div>' +
					'<input type="hidden" name="'+this.inputName+'[order][]" value="'+blockId+'"/>' +
					'</a>'
				).appendTo($li),
			settingsHtml = this.freshBlockSettingsHtml.replace(/BLOCK_ID/g, blockId),
			$settings = $(settingsHtml).appendTo(this.$settingsContainer),
			$blocktypeSettings = $settings.children('.blocktypesettings:first');
			blocktypeSettingsHtml = this.freshBlocktypeSettingsHtml.PlainText.replace(/BLOCK_ID/g, blockId);

		$blocktypeSettings.html(blocktypeSettingsHtml);

		var block = new b.ui.BlockEditor.Block(this, blockId, $link, $settings);
		this.blocks[blockId] = block;
		block.select();
	}

});


b.ui.BlockEditor.Block = b.Base.extend({

	editor: null,
	blockId: null,

	$link: null,
	$linkNameLabel: null,
	$linkBlocktypeLabel: null,

	$settings: null,
	$blockSettingsContainer: null,
	$blocktypeSettingsContainer: null,

	$nameInput: null,
	$handleInput: null,
	$instructionsInput: null,
	$requiredInput: null,
	$blocktypeSelect: null,
	$blocktypeSelectOptions: null,

	handleGenerator: null,
	niceInstructions: null,
	blocktypeSettings: null,

	blocktype: null,

	init: function(editor, blockId, $link, $settings)
	{
		this.editor = editor;
		this.blockId = blockId;

		this.$link = $link;
		this.$linkNameLabel = this.$link.find('.block-name:first');
		this.$linkBlocktypeLabel = this.$link.find('.block-type:first');

		this.$settings = $settings;
		this.$blockSettingsContainer = this.$settings.children('.blocksettings:first');
		this.$blocktypeSettingsContainer = this.$settings.children('.blocktypesettings:first');

		this.$nameInput = this.$blockSettingsContainer.find('input.name:first');
		this.$handleInput = this.$blockSettingsContainer.find('input.handle:first');
		this.$instructionsInput = this.$blockSettingsContainer.find('textarea.instructions:first');
		this.$requiredInput = this.$blockSettingsContainer.find('.required:first');
		this.$blocktypeSelect = this.$blockSettingsContainer.find('select.blocktype:first');
		this.$blocktypeSelectOptions = this.$blocktypeSelect.children();
		this.updateLinkBlocktypeLabel();

		if (!this.$nameInput.val() && !this.$handleInput.val())
			this.handleGenerator = new b.ui.HandleGenerator(this.$nameInput, this.$handleInput);
		this.niceInstructions = new b.ui.NiceText(this.$instructionsInput);
		this.requiredSwitch = new b.ui.LightSwitch(this.$requiredInput, {
			onChange: $.proxy(function() {
				if (this.requiredSwitch.on)
					this.$linkNameLabel.addClass('required');
				else
					this.$linkNameLabel.removeClass('required');
			}, this)
		});

		// Get the current blocktype
		this.blocktype = this.$blocktypeSelect.val();

		// Get the current blocktype settings
		this.blocktypeSettings = {};
		this.blocktypeSettings[this.blocktype] = this.$blocktypeSettingsContainer.children(':first');
		this.initBlocktypeSettings(this.blocktypeSettings[this.blocktype]);

		this.addListener(this.$link, 'click', 'select');
		this.addListener(this.$nameInput, 'keypress,keyup,change', 'updateLinkNameLabel');
		this.addListener(this.$blocktypeSelect, 'change', 'changeBlocktype');
	},

	select: function()
	{
		if (this.editor.selectedBlock)
			this.editor.selectedBlock.deselect();

		this.editor.selectedBlock = this;

		this.$link.addClass('sel');
		this.$settings.show();

		this.$nameInput.focus();
	},

	deselect: function()
	{
		this.$link.removeClass('sel');
		this.$settings.hide();
	},

	initBlocktypeSettings: function($settings)
	{
		$settings.find('.lightswitch').lightswitch();
		$settings.find('.nicetext').nicetext();
		$settings.find('.pill').pill();
	},

	updateLinkNameLabel: function(event, force)
	{
		// Wait 1ms if this is a keydown
		if (event.type == 'keydown' && !force)
		{
			setTimeout($.proxy(function() {
				this.updateLinkNameLabel(event, true);
			}, this), 1);
			return;
		}

		var name = this.$nameInput.val();
		if (!name)
			name = 'Untitled';

		this.$linkNameLabel.text(name);
	},

	changeBlocktype: function()
	{
		// Hide the current blocktype settings
		this.blocktypeSettings[this.blocktype].detach();

		this.blocktype = this.$blocktypeSelect.val();

		// Is this the first time this blocktype has been selected?
		if (typeof this.blocktypeSettings[this.blocktype] == 'undefined')
		{
			// Do fresh settings exist for this blocktype?
			if (typeof this.editor.freshBlocktypeSettingsHtml[this.blocktype] != 'undefined')
			{
				var blocktypeSettingsHtml = this.editor.freshBlocktypeSettingsHtml[this.blocktype].replace(/BLOCK_ID/g, this.blockId),
					$blocktypeSettings = $(blocktypeSettingsHtml);

				this.initBlocktypeSettings($blocktypeSettings);
			}
			else
				var $blocktypeSettings = $('<div/>');

			this.blocktypeSettings[this.blocktype] = $blocktypeSettings;
		}

		// Show the new blocktype's settings
		this.blocktypeSettings[this.blocktype].appendTo(this.$blocktypeSettingsContainer);

		// Update the link blocktype label
		this.updateLinkBlocktypeLabel();
	},

	updateLinkBlocktypeLabel: function()
	{
		var selectedIndex = this.$blocktypeSelect[0].selectedIndex,
			blocktypeLabel = $(this.$blocktypeSelectOptions[selectedIndex]).text();

		this.$linkBlocktypeLabel.text(blocktypeLabel);
	},

	destroy: function()
	{
		this.base();
		this.handleGenerator.destroy();
		this.niceInstructions.destroy();
	}

});



$.fn.blockeditor = function()
{
	return this.each(function()
	{
		if (!$.data(this, 'blockeditor'))
			new b.ui.BlockEditor(this);
	});
};

b.$document.ready(function()
{
	$('#body .blockeditor').blockeditor();
});


})(jQuery);
