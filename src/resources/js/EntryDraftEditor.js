(function($) {


Craft.EntryDraftEditor = Garnish.Base.extend(
{
	$revisionBtn: null,
	$editBtn: null,
	$form: null,
	$nameInputContainer: null,
	$nameInput: null,
	$saveBtn: null,
	$spinner: null,

	draftId: null,
	draftName: null,
	hud: null,
	loading: false,

	init: function(draftId, draftName)
	{
		this.draftId = draftId;
		this.draftName = draftName;

		this.$revisionBtn = $('#revision-btn');
		this.$editBtn = $('#editdraft-btn');

		this.addListener(this.$editBtn, 'click', 'showHud');
	},

	showHud: function()
	{
		if (!this.hud)
		{
			this.$form = $('<form method="post" accept-charset="UTF-8"/>');
			var $field = $('<div class="field"><div class="heading"><label for="draft-name">'+Craft.t('Draft Name')+'</label></div></div>').appendTo(this.$form);
			this.$nameInputContainer = $('<div class="input"/>').appendTo($field);
			this.$nameInput = $('<input type="text" class="text" id="draft-name"/>').appendTo(this.$nameInputContainer).val(this.draftName);
			var $buttonsContainer = $('<div class="buttons"/>').appendTo(this.$form);
			this.$saveBtn = $('<input type="submit" class="btn submit disabled" value="'+Craft.t('Save')+'"/>').appendTo($buttonsContainer);
			this.$spinner = $('<div class="spinner hidden"/>').appendTo($buttonsContainer);

			this.hud = new Garnish.HUD(this.$editBtn, this.$form);

			this.addListener(this.$nameInput, 'textchange', 'checkName');
			this.addListener(this.$form, 'submit', 'save');

			this.hud.on('show', $.proxy(this, 'onHudShow'));
			this.hud.on('hide', $.proxy(this, 'onHudHide'));
			this.hud.on('escape', $.proxy(this, 'onHudEscape'));

			this.onHudShow();
		}
		else
		{
			this.hud.show();
		}

		if (!Garnish.isMobileBrowser(true))
		{
			this.$nameInput.focus();
		}
	},

	onHudShow: function()
	{
		this.$editBtn.addClass('active');
	},

	onHudHide: function()
	{
		this.$editBtn.removeClass('active');
	},

	onHudEscape: function()
	{
		this.$nameInput.val(this.draftName);
	},

	checkName: function()
	{
		this.checkName._name = this.$nameInput.val();

		if (this.checkName._name && this.checkName._name != this.draftName)
		{
			this.$saveBtn.removeClass('disabled');
			return this.checkName._name;
		}
		else
		{
			this.$saveBtn.addClass('disabled');
			return false;
		}
	},

	save: function(ev)
	{
		ev.preventDefault();

		if (this.loading)
		{
			return;
		}

		var name = this.checkName();

		if (!name)
		{
			this.shakeHud();
			return;
		}

		this.loading = true;
		this.$saveBtn.addClass('active');
		this.$spinner.removeClass('hidden');

		var data = {
			draftId: this.draftId,
			name:    name
		};

		Craft.postActionRequest('entryRevisions/renameDraft', data, $.proxy(function(response, textStatus)
		{
			this.loading = false;
			this.$saveBtn.removeClass('active');
			this.$spinner.addClass('hidden');

			if (textStatus == 'success')
			{
				if (response.success)
				{
					this.$revisionBtn.text(name);
					this.$revisionBtn.data('menubtn').menu.$options.filter('.sel').text(name);
					this.draftName = name;
					this.checkName();
					this.hud.hide();
				}
				else
				{
					this.shakeHud();
				}
			}
		}, this));
	},

	shakeHud: function()
	{
		Garnish.shake(this.hud.$hud);
	}

});


})(jQuery);
