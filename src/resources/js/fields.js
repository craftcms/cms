(function($) {


var FieldsAdmin = Garnish.Base.extend({

	$groups: null,
	$selectedGroup: null,

	init: function()
	{
		this.$groups = $('#groups');
		this.$selectedGroup = this.$groups.find('a.sel:first');
		this.addListener($('#newgroupbtn'), 'activate', 'addNewGroup');

		var $groupSettingsBtn = $('#groupsettingsbtn'),
			menuBtn = $groupSettingsBtn.data('menubtn');

		menuBtn.settings.onOptionSelect = $.proxy(function(elem)
		{
			var action = $(elem).data('action');

			switch (action)
			{
				case 'rename':
				{
					this.renameSelectedGroup();
					break;
				}
				case 'delete':
				{
					this.deleteSelectedGroup();
					break;
				}
			}
		}, this);
	},

	addNewGroup: function()
	{
		var name = this.promptForGroupName();

		if (name)
		{
			var data = {
				name: name
			};

			Blocks.postActionRequest('fields/saveGroup', data, $.proxy(function(response)
			{
				if (response.success)
				{
					location.href = Blocks.getUrl('settings/fields/'+response.group.id);
				}
				else
				{
					var errors = this.flattenErrors(response.errors);
					alert(Blocks.t('Could not create the group:')+"\n\n"+errors.join("\n"));
				}

			}, this));
		}
	},

	renameSelectedGroup: function()
	{
		var oldName = this.$selectedGroup.text(),
			newName = this.promptForGroupName(oldName);

		if (newName && newName != oldName)
		{
			var data = {
				id:   this.$selectedGroup.data('id'),
				name: newName
			};

			Blocks.postActionRequest('fields/saveGroup', data, $.proxy(function(response)
			{
				if (response.success)
				{
					this.$selectedGroup.text(response.group.name);
					Blocks.cp.displayNotice(Blocks.t('Group renamed.'));
				}
				else
				{
					var errors = this.flattenErrors(response.errors);
					alert(Blocks.t('Could not rename the group:')+"\n\n"+errors.join("\n"));
				}

			}, this));
		}
	},

	promptForGroupName: function(oldName)
	{
		return prompt(Blocks.t('What do you want to name your group?'), oldName);
	},

	deleteSelectedGroup: function()
	{
		if (confirm(Blocks.t('Are you sure you want to delete this group and all its fields?')))
		{
			var data = {
				id: this.$selectedGroup.data('id')
			};

			Blocks.postActionRequest('fields/deleteGroup', data, $.proxy(function(response)
			{
				if (response.success)
				{
					location.href = Blocks.getUrl('settings/fields');
				}
				else
				{
					alert(Blocks.t('Could not delete the group.'));
				}
			}, this));
		}
	},

	flattenErrors: function(responseErrors)
	{
		var errors = [];

		for (var attribute in responseErrors)
		{
			errors = errors.concat(response.errors[attribute]);
		}

		return errors;
	}
});


Garnish.$doc.ready(function()
{
	Blocks.FieldsAdmin = new FieldsAdmin();
});


})(jQuery);
