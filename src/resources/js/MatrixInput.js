(function($){


/**
 * Matrix input class
 */
Craft.MatrixInput = Garnish.Base.extend({

	id: null,
	blockTypeInfo: null,

	inputNamePrefix: null,
	inputIdPrefix: null,

	$container: null,
	$blockContainer: null,
	$newBlockBtns: null,

	blockSort: null,
	totalNewBlocks: 0,

	init: function(id, blockTypeInfo, inputNamePrefix)
	{
		this.id = id
		this.blockTypeInfo = blockTypeInfo;

		this.inputNamePrefix = inputNamePrefix;
		this.inputIdPrefix = Craft.formatInputId(this.inputNamePrefix);

		this.$container = $('#'+this.id);
		this.$blockContainer = this.$container.children('.blocks');
		this.$newBlockBtns = this.$container.children('.buttons').find('.btn');

		this.blockSort = new Garnish.DragSort({
			caboose: '<div/>',
			handle: '> .actions > .move',
			axis: 'y',
			helperOpacity: 0.9
		});

		var $blocks = this.$blockContainer.children();

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

			this.initBlock($block);
		}

		this.addListener(this.$newBlockBtns, 'click', function(ev)
		{
			var type = $(ev.target).data('type');
			this.addBlock(type);
		});
	},

	initBlock: function($block)
	{
		this.blockSort.addItems($block);

		this.addListener($block.find('> .actions > .delete'), 'click', function() {

			if ($block.is(':only-child'))
			{
				var marginBottomDiff = -16;
			}
			else if ($block.is(':last-child'))
			{
				var marginBottomDiff = 16;
			}
			else
			{
				var marginBottomDiff = 0;
			}

			$block.animate({
				opacity: 0,
				'margin-bottom': -($block.outerHeight()-marginBottomDiff)
			}, 'fast', function() {
				$block.remove();
			});
		});
	},

	addBlock: function(type)
	{
		this.totalNewBlocks++;

		var id = 'new'+this.totalNewBlocks;

		var $block = $(
			'<div class="matrixblock" data-id="'+id+'">' +
				'<input type="hidden" name="'+this.inputNamePrefix+'['+id+'][type]" value="'+type+'"/>' +
				'<div class="actions">' +
					'<a class="move icon" title="'+Craft.t('Reorder')+'" role="button"></a> ' +
					'<a class="delete icon" title="'+Craft.t('Delete')+'" role="button"></a>' +
				'</div>' +
			'</div>'
		).appendTo(this.$blockContainer);

		var $fieldsContainer = $('<div class="fields"/>').appendTo($block),
			bodyHtml = this.getParsedBlockHtml(this.blockTypeInfo[type].bodyHtml, id),
			footHtml = this.getParsedBlockHtml(this.blockTypeInfo[type].footHtml, id);

		$(bodyHtml).appendTo($fieldsContainer);

		if ($block.is(':only-child'))
		{
			var marginBottomDiff = -16;
		}
		else
		{
			var marginBottomDiff = 20;
		}

		$block.css({
			opacity: 0,
			'margin-bottom': -($block.outerHeight()-marginBottomDiff)
		}).animate({
			opacity: 1,
			'margin-bottom': 20
		}, 'fast', $.proxy(function()
		{
			$block.css('margin-bottom', '');
			$('body').append(footHtml);
			Craft.initUiElements($fieldsContainer);
			this.initBlock($block);
		}, this));
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


})(jQuery);
