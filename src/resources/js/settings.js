(function($) {


Craft.Tool = Garnish.Base.extend(
{
	$trigger: null,
	$form: null,
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
			this.$form = $('<div class="form"/>').html(this.optionsHtml +
				'<div class="buttons">' +
					'<input type="submit" class="btn submit" value="'+this.buttonLabel+'">' +
				'</div>');

			this.hud = new Garnish.HUD(this.$trigger, this.$form, {
				orientations: ['top', 'bottom', 'right', 'left'],
				hudClass: 'hud toolhud',
				onSubmit: $.proxy(this, 'onSubmit')
			});

			Craft.initUiElements(this.$form);
		}
		else
		{
			this.hud.show();
		}
	},

	onSubmit: function(ev)
	{
		if (!this.progressBar)
		{
			this.progressBar = new Craft.ProgressBar(this.hud.$main);
		}
		else
		{
			this.progressBar.resetProgressBar();
		}

		this.totalActions = 1;
		this.completedActions = 0;
		this.queue = [];

		this.loadingActions = 0;
		this.currentBatchQueue = [];


		this.progressBar.$progressBar.css({
			top: Math.round(this.hud.$main.outerHeight() / 2) - 6
		})
			.removeClass('hidden');

		this.$form.velocity('stop').animateLeft(-200, 'fast');

		this.progressBar.$progressBar.velocity('stop').animateLeft(30, 'fast', $.proxy(function()
		{
			var postData = Garnish.getPostData(this.$form),
				params = Craft.expandPostArray(postData);
			params.start = true;

			this.loadAction({
				params: params
			});

		}, this));
	},

	updateProgressBar: function()
	{
		var width = (100 * this.completedActions / this.totalActions);
		this.progressBar.setProgressPercentage(width);
	},

	loadAction: function(data)
	{
		this.loadingActions++;

		if (typeof data.confirm != 'undefined' && data.confirm)
		{
			this.showConfirmDialog(data);
		}
		else
		{
			this.postActionRequest(data.params);
		}
	},

	showConfirmDialog: function(data)
	{
		var $modal = $('<form class="modal fitted confirmmodal"/>').appendTo(Garnish.$bod),
			$body = $('<div class="body"/>').appendTo($modal).html(data.confirm),
			$footer = $('<footer class="footer"/>').appendTo($modal),
			$buttons = $('<div class="buttons right"/>').appendTo($footer),
			$cancelBtn = $('<div class="btn">'+Craft.t('Cancel')+'</div>').appendTo($buttons),
			$okBtn = $('<input type="submit" class="btn submit" value="'+Craft.t('OK')+'"/>').appendTo($buttons);

		Craft.initUiElements($body);

		var modal = new Garnish.Modal($modal, {
			onHide: $.proxy(this, 'onActionResponse')
		});

		this.addListener($cancelBtn, 'click', function() {
			modal.hide();
		});

		this.addListener($modal, 'submit', function(ev) {
			ev.preventDefault();

			modal.settings.onHide = $.noop;
			modal.hide();

			var postData = Garnish.getPostData($body);
			var params = Craft.expandPostArray(postData);

			$.extend(params, data.params);

			this.postActionRequest(params);
		});
	},

	postActionRequest: function(params)
	{
		var data = {
			tool: this.toolClass,
			params: params
		};

		Craft.postActionRequest('tools/performAction', data, $.proxy(this, 'onActionResponse'), {
			complete: $.noop
		});
	},

	onActionResponse: function(response, textStatus)
	{
		this.loadingActions--;
		this.completedActions++;

		// Add any new batches to the queue?
		if (textStatus == 'success' && response && response.batches)
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

		if (response && response.error)
		{
			alert(response.error);
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
				if (response && response.backupFile)
				{
					var $iframe = $('<iframe/>', {'src' : Craft.getActionUrl('tools/downloadBackupFile', {'fileName':response.backupFile}) }).hide();
					this.$form.append($iframe);
				}

				// Quick delay so things don't look too crazy.
				setTimeout($.proxy(this, 'onComplete'), 300);
			}
		}
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
			this.$allDone = $('<div class="alldone" data-icon="done" />').appendTo(this.hud.$main);
		}

		this.$allDone.css({
			top: Math.round(this.hud.$main.outerHeight() / 2) - 30
		});

		this.progressBar.$progressBar.animateLeft(-170, 'fast');

		this.$allDone.animateLeft(30, 'fast');

		// Just in case the tool created a new task...
		Craft.cp.runPendingTasks();
	}

},
{
	maxConcurrentActions: 3
});


})(jQuery);
