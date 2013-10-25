(function($){


/**
 * Matrix input class
 */
Craft.MatrixInput = Garnish.Base.extend({

	id: null,
	recordTypeInfo: null,

	inputNamePrefix: null,
	inputIdPrefix: null,

	$container: null,
	$recordContainer: null,
	$newRecordBtns: null,

	recordSort: null,
	totalNewRecords: 0,

	init: function(id, recordTypeInfo, inputNamePrefix)
	{
		this.id = id
		this.recordTypeInfo = recordTypeInfo;

		this.inputNamePrefix = inputNamePrefix;
		this.inputIdPrefix = Craft.formatInputId(this.inputNamePrefix);

		this.$container = $('#'+this.id);
		this.$recordContainer = this.$container.children('.records');
		this.$newRecordBtns = this.$container.children('.buttons').find('.btn');

		this.recordSort = new Garnish.DragSort({
			caboose: '<div/>',
			handle: '> .actions > .move',
			axis: 'y',
			helperOpacity: 0.9
		});

		var $records = this.$recordContainer.children();

		for (var i = 0; i < $records.length; i++)
		{
			var $record = $($records[i]),
				id = $record.data('id');

			// Is this a new record?
			var newMatch = (typeof id == 'string' && id.match(/new(\d+)/));

			if (newMatch && newMatch[1] > this.totalNewRecords)
			{
				this.totalNewRecords = parseInt(newMatch[1]);
			}

			this.initRecord($record);
		}

		this.addListener(this.$newRecordBtns, 'click', function(ev)
		{
			var type = $(ev.target).data('type');
			this.addRecord(type);
		});
	},

	initRecord: function($record)
	{
		this.recordSort.addItems($record);

		this.addListener($record.find('> .actions > .delete'), 'click', function() {

			if ($record.is(':only-child'))
			{
				var marginBottomDiff = -16;
			}
			else if ($record.is(':last-child'))
			{
				var marginBottomDiff = 16;
			}
			else
			{
				var marginBottomDiff = 0;
			}

			$record.animate({
				opacity: 0,
				'margin-bottom': -($record.outerHeight()-marginBottomDiff)
			}, 'fast', function() {
				$record.remove();
			});
		});
	},

	addRecord: function(type)
	{
		this.totalNewRecords++;

		var id = 'new'+this.totalNewRecords;

		var $record = $(
			'<div class="matrixrecord" data-id="'+id+'">' +
				'<input type="hidden" name="'+this.inputNamePrefix+'['+id+'][type]" value="'+type+'"/>' +
				'<div class="actions">' +
					'<a class="move icon" title="'+Craft.t('Reorder')+'" role="button"></a> ' +
					'<a class="delete icon" title="'+Craft.t('Delete')+'" role="button"></a>' +
				'</div>' +
			'</div>'
		).appendTo(this.$recordContainer);

		var $fieldsContainer = $('<div class="fields"/>').appendTo($record),
			bodyHtml = this.getParsedRecordHtml(this.recordTypeInfo[type].bodyHtml, id),
			footHtml = this.getParsedRecordHtml(this.recordTypeInfo[type].footHtml, id);

		$(bodyHtml).appendTo($fieldsContainer);

		if ($record.is(':only-child'))
		{
			var marginBottomDiff = -16;
		}
		else
		{
			var marginBottomDiff = 20;
		}

		$record.css({
			opacity: 0,
			'margin-bottom': -($record.outerHeight()-marginBottomDiff)
		}).animate({
			opacity: 1,
			'margin-bottom': 20
		}, 'fast', $.proxy(function()
		{
			$record.css('margin-bottom', '');
			$('body').append(footHtml);
			Craft.initUiElements($fieldsContainer);
			this.initRecord($record);
		}, this));
	},

	getParsedRecordHtml: function(html, id)
	{
		if (typeof html == 'string')
		{
			return html.replace(/__RECORD__/g, id);
		}
		else
		{
			return '';
		}
	}
});


})(jQuery);
