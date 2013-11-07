(function($){


/**
 * Matrix input class
 */
Craft.MatrixInput = Garnish.Base.extend({

	id: null,
	blockTypes: null,
	blockTypesByHandle: null,

	inputNamePrefix: null,
	inputIdPrefix: null,

	$container: null,
	$blockContainer: null,
	$newBlockBtns: null,

	blockSort: null,
	totalNewBlocks: 0,

	init: function(id, blockTypes, inputNamePrefix)
	{
		this.id = id
		this.blockTypes = blockTypes;

		this.blockTypesByHandle = {};

		for (var i = 0; i < this.blockTypes.length; i++)
		{
			var blockType = this.blockTypes[i];
			this.blockTypesByHandle[blockType.handle] = blockType;
		}

		this.inputNamePrefix = inputNamePrefix;
		this.inputIdPrefix = Craft.formatInputId(this.inputNamePrefix);

		this.$container = $('#'+this.id);
		this.$blockContainer = this.$container.children('.blocks');
		this.$newBlockBtns = this.$container.children('.buttons').find('.btn');

		var $blocks = this.$blockContainer.children();

		this.blockSort = new Garnish.DragSort($blocks, {
			caboose: '<div/>',
			handle: '> .actions > .move',
			axis: 'y',
			helperOpacity: 0.9
		});

		for (var i = 0; i < $blocks.length; i++)
		{
			var $block = $($blocks[i]),
				id = $block.data('id');

			// Is this a new block?
			var newMatch = (typeof id == 'string' && id.match(/new(\d+)/));

			if (newMatch && newMatch[1] > this.totalNewBlocks)
			{
				this.totalNewBlocks = parseInt(newMatch[1]);
			}

			new MatrixBlock(this, $block);
		}

		this.addListener(this.$newBlockBtns, 'click', function(ev)
		{
			var type = $(ev.target).data('type');
			this.addBlock(type);
		});
	},

	addBlock: function(type, $insertBefore)
	{
		this.totalNewBlocks++;

		var id = 'new'+this.totalNewBlocks;

		var html =
			'<div class="matrixblock" data-id="'+id+'">' +
				'<input type="hidden" name="'+this.inputNamePrefix+'['+id+'][type]" value="'+type+'"/>' +
				'<div class="actions">' +
					'<a class="settings icon menubtn" title="'+Craft.t('Actions')+'" role="button"></a>' +
					'<div class="menu">' +
						'<ul>';

		for (var i = 0; i < this.blockTypes.length; i++)
		{
			var blockType = this.blockTypes[i];
			html += '<li><a data-action="add" data-type="'+blockType.handle+'">'+Craft.t('Add {type} above', { type: blockType.name })+'</a></li>';
		}

		html +=
						'</ul>' +
						'<hr/>' +
						'<ul>' +
							'<li><a data-action="delete">'+Craft.t('Delete')+'</a></li>' +
						'</ul>' +
					'</div>' +
					'<a class="move icon" title="'+Craft.t('Reorder')+'" role="button"></a> ' +
				'</div>' +
			'</div>';

		var $block = $(html);

		if ($insertBefore)
		{
			$block.insertBefore($insertBefore);
		}
		else
		{
			$block.appendTo(this.$blockContainer);
		}

		var $fieldsContainer = $('<div class="fields"/>').appendTo($block),
			bodyHtml = this.getParsedBlockHtml(this.blockTypesByHandle[type].bodyHtml, id),
			footHtml = this.getParsedBlockHtml(this.blockTypesByHandle[type].footHtml, id);

		$(bodyHtml).appendTo($fieldsContainer);

		$block.css(this.getHiddenBlockCss($block)).animate({
			opacity: 1,
			'margin-bottom': ($block.is(':last-child') ? 20: 0),
		}, 'fast', $.proxy(function()
		{
			$block.css('margin-bottom', '');
			$('body').append(footHtml);
			Craft.initUiElements($fieldsContainer);
			new MatrixBlock(this, $block);
			this.blockSort.addItems($block);
		}, this));
	},

	getHiddenBlockCss: function($block)
	{
		var marginBottom = -($block.outerHeight());

		if ($block.is(':only-child'))
		{
			marginBottom -= 16;
		}
		else if ($block.is(':last-child'))
		{
			marginBottom += 16;
		}

		return {
			opacity: 0,
			marginBottom: marginBottom
		};
	},

	getParsedBlockHtml: function(html, id)
	{
		if (typeof html == 'string')
		{
			return html.replace(/__BLOCK__/g, id);
		}
		else
		{
			return '';
		}
	}
});


var MatrixBlock = Garnish.Base.extend({

	matrix: null,
	$block: null,

	init: function(matrix, $block)
	{
		this.matrix = matrix;
		this.$block = $block;

		var $menuBtn = this.$block.find('> .actions > .settings'),
			menuBtn = new Garnish.MenuBtn($menuBtn);

		menuBtn.menu.settings.onOptionSelect = $.proxy(this, 'onMenuOptionSelect');
	},

	onMenuOptionSelect: function(option)
	{
		var $option = $(option);

		if ($option.data('action') == 'add')
		{
			var type = $option.data('type');
			this.matrix.addBlock(type, this.$block);
		}
		else
		{
			this.selfDestruct();
		}
	},

	selfDestruct: function()
	{
		this.$block.animate(this.matrix.getHiddenBlockCss(this.$block), 'fast', $.proxy(function() {
			this.$block.remove();
		}, this));
	}
});


})(jQuery);
