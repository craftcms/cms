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
	loadingActions: null,
	completedActions: null,
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
		this.loadingActions = 1;
		this.completedActions = 0;
		this.queue = [];

		this.$progressBar.css({
			top: Math.round(this.hud.$body.outerHeight() / 2) - 6
		});

		this.$form.stop().animate({
			left: -200
		}, 'fast');

		this.$progressBar.stop().animate({
			left: 30
		}, 'fast', $.proxy(function() {
			var params = Garnish.getPostData(this.$form);
			this.postActionRequest(params);
		}, this));
	},

	updateProgressBar: function()
	{
		this.$progressBar.removeClass('pending');

		var width = (100 * this.completedActions / this.totalActions)+'%';
		this.$innerProgressBar.width(width);
	},

	postActionRequest: function(params)
	{
		var data = {
			tool: this.toolClass,
			params: params
		};

		Craft.postActionRequest('tools/performAction', data, $.proxy(function(response) {

			this.loadingActions--;
			this.completedActions++;

			// Add any more to the queue?
			if (typeof response.next != 'undefined' && response.next)
			{
				for (var i = 0; i < response.next.length; i++)
				{
					this.totalActions++;
					this.queue.push(response.next[i]);
				}
			}

			while (this.loadingActions < Craft.Tool.maxConcurrentActions && this.completedActions + this.loadingActions < this.totalActions)
			{
				this.loadingActions++;
				var params = this.queue.shift();
				this.postActionRequest(params);
			}

			this.updateProgressBar();

			// All done?
			if (!this.loadingActions && !this.queue.length)
			{
				this.onComplete();
			}

		}, this));
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
