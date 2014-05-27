(function($) {


Craft.EntryDraftEditor = Garnish.Base.extend(
{
	$revisionBtn: null,
	$editBtn: null,
	$form: null,
	$nameInput: null,
	$saveBtn: null,
	$spinner: null,

	draftId: null,
	draftName: null,
	draftNotes: null,
	hud: null,
	loading: false,

	init: function(draftId, draftName, draftNotes)
	{
		this.draftId = draftId;
		this.draftName = draftName;
		this.draftNotes = draftNotes;

		this.$revisionBtn = $('#revision-btn');
		this.$editBtn = $('#editdraft-btn');

		this.addListener(this.$editBtn, 'click', 'showHud');
	},

	showHud: function()
	{
		if (!this.hud)
		{
			this.$form = $('<form method="post" accept-charset="UTF-8"/>');

			// Add the Name field
			var $field = $('<div class="field"><div class="heading"><label for="draft-name">'+Craft.t('Draft Name')+'</label></div></div>').appendTo(this.$form),
				$inputContainer = $('<div class="input"/>').appendTo($field);
			this.$nameInput = $('<input type="text" class="text fullwidth" id="draft-name"/>').appendTo($inputContainer).val(this.draftName);

			// Add the Notes field
			var $field = $('<div class="field"><div class="heading"><label for="draft-notes">'+Craft.t('Notes')+'</label></div></div>').appendTo(this.$form),
				$inputContainer = $('<div class="input"/>').appendTo($field);
			this.$notesInput = $('<textarea class="text fullwidth" id="draft-notes" rows="2"/>').appendTo($inputContainer).val(this.draftNotes);

			// Add the button
			var $buttonsContainer = $('<div class="buttons"/>').appendTo(this.$form);
			this.$saveBtn = $('<input type="submit" class="btn submit disabled" value="'+Craft.t('Save')+'"/>').appendTo($buttonsContainer);
			this.$spinner = $('<div class="spinner hidden"/>').appendTo($buttonsContainer);

			this.hud = new Garnish.HUD(this.$editBtn, this.$form);

			new Garnish.NiceText(this.$notesInput);

			this.addListener(this.$notesInput, 'keydown', 'onNotesKeydown');

			this.addListener(this.$nameInput, 'textchange', 'checkValues');
			this.addListener(this.$notesInput, 'textchange', 'checkValues');
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

	onNotesKeydown: function(ev)
	{
		if (ev.keyCode == Garnish.RETURN_KEY)
		{
			ev.preventDefault();
			this.$form.submit();
		}
	},

	hasAnythingChanged: function()
	{
		return (this.$nameInput.val() != this.draftName || this.$notesInput.val() != this.draftNotes);
	},

	checkValues: function()
	{
		if (this.$nameInput.val() && this.hasAnythingChanged())
		{
			this.$saveBtn.removeClass('disabled');
			return true;
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

		if (!this.checkValues())
		{
			this.shakeHud();
			return;
		}

		this.loading = true;
		this.$saveBtn.addClass('active');
		this.$spinner.removeClass('hidden');

		var data = {
			draftId: this.draftId,
			name:    this.$nameInput.val(),
			notes:   this.$notesInput.val()
		};

		Craft.postActionRequest('entryRevisions/updateDraftMeta', data, $.proxy(function(response, textStatus)
		{
			this.loading = false;
			this.$saveBtn.removeClass('active');
			this.$spinner.addClass('hidden');

			if (textStatus == 'success')
			{
				if (response.success)
				{
					this.$revisionBtn.text(data.name);
					this.$revisionBtn.data('menubtn').menu.$options.filter('.sel').text(data.name);
					this.draftName = data.name;
					this.draftNotes = data.notes;
					this.checkValues();
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
