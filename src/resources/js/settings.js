(function($) {


Craft.Tool = Garnish.Base.extend({

	$trigger: null,
	$form: null,
	$progressBar: null,
	$innerProgressBar: null,

	toolClass: null,
	optionsHtml: null,
	buttonLabel: null,
	hud: null,
	totalActions: null,
	completedActions: null,
	loadingActions: null,
	queue: null,

	init: function(toolClass, optionsHtml, buttonLabel)
	{
		this.toolClass = toolClass;
		this.optionsHtml = optionsHtml;
		this.buttonLabel = buttonLabel;

		this.$trigger = $('#tool-'+toolClass);

		this.addListener(this.$trigger, 'click', 'showHUD');
	},

	showHUD: function(ev)
	{
		ev.stopPropagation();

		if (!this.hud)
		{
			this.$form = $('<form/>').html(this.optionsHtml +
				'<div class="buttons">' +
					'<input type="submit" class="btn submit" value="'+this.buttonLabel+'">' +
				'</div>');

			this.hud = new Garnish.HUD(this.$trigger, this.$form, {
				hudClass: 'hud toolhud',
				triggerSpacing: 10,
				tipWidth: 30
			});

			Craft.initUiElements(this.$form);
			this.addListener(this.$form, 'submit', 'onSubmit');
		}
		else
		{
			this.hud.show();
		}
	},

	onSubmit: function(ev)
	{
		ev.preventDefault();

		if (!this.$progressBar)
		{
			this.$progressBar = $('<div class="progressbar pending"/>').appendTo(this.hud.$body);
			this.$innerProgressBar = $('<div class="progressbar-inner"/>').appendTo(this.$progressBar);
		}
		else
		{
			this.$progressBar.addClass('pending');
		}

		this.totalActions = 1;
		this.completedActions = 0;
		this.queue = [];

		this.loadingActions = 0;
		this.currentBatchQueue = [];

		this.$progressBar.css({
			top: Math.round(this.hud.$body.outerHeight() / 2) - 6
		});

		this.$form.stop().animate({
			left: -200
		}, 'fast');

		this.$progressBar.stop().animate({
			left: 30
		}, 'fast', $.proxy(function() {
			var data = Garnish.getPostData(this.$form);
			data['params[start]'] = true;
			this.loadAction(data);
		}, this));
	},

	updateProgressBar: function()
	{
		this.$progressBar.removeClass('pending');

		var width = (100 * this.completedActions / this.totalActions)+'%';
		this.$innerProgressBar.width(width);
	},

	loadAction: function(data)
	{
		this.loadingActions++;

		data.tool = this.toolClass;

		Craft.postActionRequest('tools/performAction', data, $.proxy(function(response) {

			this.loadingActions--;
			this.completedActions++;

			// Add any new batches to the queue?
			if (response && typeof response.batches != 'undefined' && response.batches)
			{
				for (var i = 0; i < response.batches.length; i++)
				{
					if (response.batches[i].length)
					{
						this.totalActions += response.batches[i].length;
						this.queue.push(response.batches[i]);
					}
				}
			}

			this.updateProgressBar();

			// Load as many additional items in the current batch as possible
			while (this.loadingActions < Craft.Tool.maxConcurrentActions && this.currentBatchQueue.length)
			{
				this.loadNextAction();
			}

			// Was that the end of the batch?
			if (!this.loadingActions)
			{
				// Is there another batch?
				if (this.queue.length)
				{
					this.currentBatchQueue = this.queue.shift();
					this.loadNextAction();
				}
				else
				{
					// Quick delay so things don't look too crazy.
					setTimeout($.proxy(this, 'onComplete'), 300);
				}
			}

		}, this));
	},

	loadNextAction: function()
	{
		var data = this.currentBatchQueue.shift();
		this.loadAction(data);
	},

	onComplete: function()
	{
		if (!this.$allDone)
		{
			this.$allDone = $('<div class="alldone" data-icon="√" />').appendTo(this.hud.$body);
		}

		this.$allDone.css({
			top: Math.round(this.hud.$body.outerHeight() / 2) - 30
		});

		this.$progressBar.animate({
			left: -170
		}, 'fast');

		this.$allDone.animate({
			left: 30
		}, 'fast');
	}

},
{
	maxConcurrentActions: 3
});


})(jQuery);
