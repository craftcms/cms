(function($) {


var EmailMessages = blx.Base.extend({

	messages: null,

	init: function()
	{
		this.messages = [];

		var $container = $('#messages'),
			$messages = $container.find('.message');

		for (var i = 0; i < $messages.length; i++)
		{
			var message = new Message($messages[i]);
			this.messages.push(message);
		}
	}

});


var Message = blx.Base.extend({

	$container: null,
	id: null,
	$subject: null,
	$body: null,
	modal: null,

	init: function(container)
	{
		this.$container = $(container);
		this.id = this.$container.attr('data-id');
		this.$subject = this.$container.find('.subject:first');
		this.$body = this.$container.find('.body:first');

		this.addListener(this.$container, 'click', 'edit');
	},

	edit: function()
	{
		if (!this.modal)
			this.modal = new MessageSettingsModal(this);
		else
			this.modal.show();
	},

	updateHtmlFromModal: function()
	{
		var subject = this.modal.$subjectInput.val(),
			body = this.modal.$bodyInput.val().replace(/\s+/g, ' ');

		this.$subject.html(subject);
		this.$body.html(body);
	}

});


var MessageSettingsModal = blx.ui.Modal.extend({

	message: null,

	$languageSelect: null,
	$subjectInput: null,
	$bodyInput: null,
	$saveBtn: null,
	$cancelBtn: null,
	$spinner: null,

	loading: false,

	init: function(message)
	{
		this.message = message;

		this.base();
		this.loadContainer();
	},

	loadContainer: function(language)
	{
		var data = {
			messageId: this.message.id,
			language: language
		};

		$.post(blx.baseUrl+'settings/email/_message_modal', data, $.proxy(function(response, textStatus, jqXHR) {

			if (!this.$container)
			{
				var $container = $('<form class="modal message-settings">'+response+'</form>').appendTo(blx.$body);
				this.setContainer($container);
				this.show();
			}
			else
			{
				this.$container.html(response);
			}

			this.$languageSelect = this.$container.find('.language:first > select');
			this.$subjectInput = this.$container.find('.subject:first');
			this.$bodyInput = this.$container.find('.body:first');
			this.$saveBtn = this.$container.find('.submit:first');
			this.$cancelBtn = this.$container.find('.cancel:first');
			this.$spinner = this.$container.find('.spinner:first');

			this.addListener(this.$languageSelect, 'change', 'switchLanguage');
			this.addListener(this.$container, 'submit', 'saveMessage');
			this.addListener(this.$cancelBtn, 'click', 'cancel');

		}, this));
	},

	switchLanguage: function()
	{
		var language = this.$languageSelect.val();
		this.loadContainer(language);
	},

	saveMessage: function(event)
	{
		event.preventDefault();

		if (this.loading)
			return;

		var data = {
			messageId: this.message.id,
			language:  (this.$languageSelect.length ? this.$languageSelect.val() : blx.language),
			subject:   this.$subjectInput.val(),
			body:      this.$bodyInput.val()
		};

		this.$subjectInput.removeClass('error');
		this.$bodyInput.removeClass('error');

		if (!data.subject || !data.body)
		{
			if (!data.subject)
				this.$subjectInput.addClass('error');

			if (!data.body)
				this.$bodyInput.addClass('error');

			blx.shake(this.$container);
			return;
		}

		this.loading = true;
		this.$saveBtn.addClass('active');
		this.$spinner.show();

		$.post(blx.actionUrl+'email/saveMessage', data, $.proxy(function(response, textStatus, jqXHR) {

			if (response.success)
			{
				// Only update the page if we're editing the user's preferred language
				if (data.language == blx.language)
					this.message.updateHtmlFromModal();

				this.hide();
				blx.displayNotice(blx.t('Message saved.'))
			}
			else
				blx.displayError(blx.t('Couldn’t save message.'));

			this.$saveBtn.removeClass('active');
			this.$spinner.hide();
			this.loading = false;

		}, this));
	},

	cancel: function()
	{
		this.hide();

		if (this.message)
			this.message.modal = null;
	}

});


new EmailMessages();


})(jQuery);
