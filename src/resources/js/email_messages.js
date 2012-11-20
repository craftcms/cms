(function($) {


var EmailMessages = Blocks.Base.extend({

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


var Message = Blocks.Base.extend({

	$container: null,
	key: null,
	$subject: null,
	$body: null,
	modal: null,

	init: function(container)
	{
		this.$container = $(container);
		this.key = this.$container.attr('data-key');
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
			body = this.modal.$bodyInput.val().replace(/\n/g, '<br>');

		this.$subject.html(subject);
		this.$body.html(body);
	}

});


var MessageSettingsModal = Blocks.ui.Modal.extend({

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
			key: this.message.key,
			language: language
		};

		$.post(Blocks.getUrl('settings/email/_message_modal'), data, $.proxy(function(response, textStatus, jqXHR) {

			if (!this.$container)
			{
				var $container = $('<form class="modal message-settings" accept-charset="UTF-8">'+response+'</form>').appendTo(Blocks.$body);
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

			setTimeout($.proxy(function() {
				this.$subjectInput.focus();
			}, this), 100)

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
			key:       this.message.key,
			language:  (this.$languageSelect.length ? this.$languageSelect.val() : Blocks.language),
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

			Blocks.shake(this.$container);
			return;
		}

		this.loading = true;
		this.$saveBtn.addClass('active');
		this.$spinner.show();

		Blocks.postActionRequest('emailMessages/saveMessage', data, $.proxy(function(response, textStatus, jqXHR) {

			if (response.success)
			{
				// Only update the page if we're editing the user's preferred language
				if (data.language == Blocks.language)
					this.message.updateHtmlFromModal();

				this.hide();
				Blocks.cp.displayNotice(Blocks.t('Message saved.'));
			}
			else
				Blocks.cp.displayError(Blocks.t('Couldn’t save message.'));

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
