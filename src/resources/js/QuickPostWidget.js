(function($) {


Craft.QuickPostWidget = Garnish.Base.extend({

	params: null,
	initFields: null,
	$widget: null,
	$form: null,
	$formClone: null,
	$spinner: null,
	$errorList: null,
	loading: false,

	init: function(widgetId, params, initFields)
	{
		this.params = params;
		this.initFields = initFields;
		this.$widget = $('#widget'+widgetId);
		this.$form = this.$widget.find('form:first');
		this.$spinner = this.$form.find('.spinner');

		this.$formClone = this.$form.clone();

		this.initForm();
	},

	initForm: function()
	{
		this.addListener(this.$form, 'submit', 'onSubmit');
		this.initFields();
	},

	onSubmit: function(event)
	{
		event.preventDefault();

		if (this.loading) return;
		this.loading = true;
		this.$spinner.removeClass('hidden');

		var formData = Garnish.getPostData(this.$form),
			data = $.extend({ enabled: 1 }, formData, this.params);

		Craft.postActionRequest('entries/saveEntry', data, $.proxy(function(response, textStatus) {

			this.loading = false;
			this.$spinner.addClass('hidden');

			if (this.$errorList)
			{
				this.$errorList.children().remove();
			}

			if (textStatus == 'success')
			{
				if (response.success)
				{
					Craft.cp.displayNotice(Craft.t('Entry saved.'));

					// Reset the widget
					var $newForm = this.$formClone.clone();
					this.$form.replaceWith($newForm);
					this.$form = $newForm;
					this.initForm();

					// Are there any Recent Entries widgets to notify?
					if (typeof Craft.RecentEntriesWidget != 'undefined')
					{
						for (var i = 0; i < Craft.RecentEntriesWidget.instances.length; i++)
						{
							var widget = Craft.RecentEntriesWidget.instances[i];
							if (!widget.params.sectionId || widget.params.sectionId == this.params.sectionId)
							{
								widget.addEntry({
									url:      response.cpEditUrl,
									title:    response.title,
									postDate: response.postDate,
									username: response.author.username
								});
							}
						}
					}
				}
				else
				{
					Craft.cp.displayError(Craft.t('Couldn’t save entry.'));

					if (response.errors)
					{
						if (!this.$errorList)
						{
							this.$errorList = $('<ul class="errors"/>').insertAfter(this.$form);
						}

						for (var attribute in response.errors)
						{
							for (var i = 0; i < response.errors[attribute].length; i++)
							{
								var error = response.errors[attribute][i];
								$('<li>'+error+'</li>').appendTo(this.$errorList);
							}
						}
					}
				}
			}

		}, this));
	}
});


})(jQuery);
