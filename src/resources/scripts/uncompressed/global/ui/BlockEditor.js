(function($) {

/**
 * Block Editor
 */
blx.ui.BlockEditor = blx.Base.extend({

	$container: null,
	$listContainer: null,
	$list: null,
	$newBlockBtn: null,
	$settingsContainer: null,

	freshBlockSettingsHtml: null,
	freshBlocktypeSettingsHtml: null,

	inputName: null,

	blockSort: null,
	blocks: null,
	selectedBlock: null,
	totalNewBlocks: 0,

	init: function(pane)
	{
		this.$container = $(pane);

		// Is this already a block editor?
		if (this.$container.data('blockeditor'))
		{
			blx.log('Double-instantiating a blocks select on an element');
			this.$container.data('blockeditor').destroy();
		}

		this.$container.data('blockeditor', this);

		this.inputName = this.$container.attr('data-input-name');

		// Find the DOM nodes
		this.$listContainer = this.$container.children('.list:first');
		this.$list = this.$listContainer.children('ul:first');
		this.$newBlockBtn = this.$listContainer.children('.new-block:first');
		this.$settingsContainer = this.$container.children('.settings:first');

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

		// Initialize the sorter
		this.blockSort = new blx.ui.DragSort({
			axis: 'y',
			helper: function($li) {
				var $div = $('<div class="sidebar"/>'),
					$ul = $('<ul style="padding:0"/>').appendTo($div);
				$li.appendTo($ul);
				$li.children('a').removeClass('sel');
				return $div;
			}
		});

		// Initialize the blocks
		this.blocks = {};
		var $blockLinks = this.$list.children('li').children('a'),
			$blockSettings = this.$settingsContainer.children();

		for (var j = 0; j < $blockLinks.length; j++)
		{
			var $link = $($blockLinks[j]),
				blockId = $link.attr('data-block-id'),
				$settings = $blockSettings.filter('[data-block-id='+blockId+']:first');

			var block = new blx.ui.BlockEditor.Block(this, blockId, $link, $settings);
			this.blocks[blockId] = block;

			// Is this a new block? (Could be if there were validation errors)
			if (blockId.substr(0, 3) == 'new')
				this.totalNewBlocks++;

			// Add its parent LI to the sorter
			this.blockSort.addItems($link.parent());
		}

		this.addListener(this.$newBlockBtn, 'click', 'addBlock');
	},

	selectBlock: function(blockId)
	{
		this.blocks[blockId].select();
	},

	setHeight: function()
	{
		var height = Math.max(this.$listContainer.height(), this.$settingsContainer.height()) + 60;
		this.$container.height(height);
	},

	addBlock: function()
	{
		this.totalNewBlocks++;

		var blockId = 'new'+(this.totalNewBlocks),
			$li = $('<li/>').appendTo(this.$list),
			$link = $('<a class="block-item" data-block-id="'+blockId+'">' +
					'<div class="name">Untitled</div>' +
					'<div class="type"></div>' +
					'<input type="hidden" name="'+this.inputName+'[order][]" value="'+blockId+'"/>' +
					'</a>'
				).appendTo($li),
			settingsHtml = this.freshBlockSettingsHtml.replace(/BLOCK_ID/g, blockId),
			$settings = $(settingsHtml).appendTo(this.$settingsContainer),
			$blocktypeSettings = $settings.children('.blocktypesettings:first'),
			blocktypeSettingsHtml = this.freshBlocktypeSettingsHtml.PlainText.replace(/BLOCK_ID/g, blockId);

		$blocktypeSettings.html(blocktypeSettingsHtml);

		var block = new blx.ui.BlockEditor.Block(this, blockId, $link, $settings);
		this.blocks[blockId] = block;
		block.select();

		this.blockSort.addItems($li);
	}

});

blx.ui.BlockEditor.Block = blx.Base.extend({

	editor: null,
	blockId: null,
	isNew: null,

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
	$deleteBlockBtn: null,

	handleGenerator: null,
	niceInstructions: null,
	blocktypeSettings: null,

	blocktype: null,

	init: function(editor, blockId, $link, $settings)
	{
		this.editor = editor;
		this.blockId = blockId;
		this.isNew = (this.blockId.substr(0, 3) == 'new');

		this.$link = $link;
		this.$linkNameLabel = this.$link.find('.name:first');
		this.$linkBlocktypeLabel = this.$link.find('.type:first');

		this.$settings = $settings;
		this.$blockSettingsContainer = this.$settings.children('.blocksettings:first');
		this.$blocktypeSettingsContainer = this.$settings.children('.blocktypesettings:first');

		this.$nameInput = this.$blockSettingsContainer.find('input.name:first');
		this.$handleInput = this.$blockSettingsContainer.find('input.handle:first');
		this.$instructionsInput = this.$blockSettingsContainer.find('textarea.instructions:first');
		this.$requiredInput = this.$blockSettingsContainer.find('.blockrequired:first');
		this.$blocktypeSelect = this.$blockSettingsContainer.find('select.blocktype:first');
		this.$blocktypeSelectOptions = this.$blocktypeSelect.children();
		this.$doneBtn = this.$settings.children('.done:first');
		this.$deleteBlockBtn = this.$settings.children('.delete:first');

		this.updateLinkBlocktypeLabel();

		if (!this.$nameInput.val() && !this.$handleInput.val())
			this.handleGenerator = new blx.ui.HandleGenerator(this.$nameInput, this.$handleInput);
		this.niceInstructions = new blx.ui.NiceText(this.$instructionsInput);

		// Get the current blocktype
		this.blocktype = this.$blocktypeSelect.val();

		// Get the current blocktype settings
		this.blocktypeSettings = {};
		this.blocktypeSettings[this.blocktype] = this.$blocktypeSettingsContainer.children(':first');
		this.initBlocktypeSettings(this.blocktypeSettings[this.blocktype]);

		this.addListener(this.$doneBtn, 'click', function() {
			this.deselect();
			this.editor.setHeight();
		});
		this.addListener(this.$deleteBlockBtn, 'click', 'deleteBlock');

		this.addListener(this.$link, 'click', 'select');
		this.addListener(this.$nameInput, 'keypress,keyup,change,blur', 'updateLinkNameLabel');
		this.addListener(this.$blocktypeSelect, 'change', 'changeBlocktype');

		this.addListener(this.$requiredInput, 'change', function() {
			if (blx.getInputPostVal(this.$requiredInput) == 'y')
				this.$linkNameLabel.addClass('required');
			else
				this.$linkNameLabel.removeClass('required');
		});
	},

	select: function()
	{
		if (this.editor.selectedBlock)
			this.editor.selectedBlock.deselect();

		this.editor.selectedBlock = this;

		this.$link.addClass('sel');
		this.$settings.show();

		this.editor.setHeight();

		this.$nameInput.focus();
	},

	deselect: function()
	{
		this.$link.removeClass('sel');
		this.$settings.hide();
		this.editor.selectedBlock = null;
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

		// Update the container height
		this.editor.setHeight();

		// Update the link blocktype label
		this.updateLinkBlocktypeLabel();
	},

	updateLinkBlocktypeLabel: function()
	{
		var selectedIndex = this.$blocktypeSelect.prop('selectedIndex'),
			blocktypeLabel = $(this.$blocktypeSelectOptions[selectedIndex]).text();

		this.$linkBlocktypeLabel.text(blocktypeLabel);
	},

	deleteBlock: function()
	{
		if (confirm('Are you sure you want to delete this content block?'))
		{
			this.$link.remove();
			this.$settings.remove();

			this.editor.setHeight();

			if (!this.isNew)
				$('<input type="hidden" name="'+this.editor.inputName+'[delete][]" value="'+this.blockId+'"/>').appendTo(this.editor.$container);

			this.destroy();
		}
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
			new blx.ui.BlockEditor(this);
	});
};

	blx.$document.ready(function()
{
	$('.blockeditor').blockeditor();
});

})(jQuery);
