Craft.WrongEditionModal = Garnish.Modal.extend(
{
	upgradeModal: null,

	init: function($container)
	{
		this.base($container.removeClass('hidden'));

		this.$switchBtn = $('#wrongedition-switchbtn');
		this.$upgradeBtn = $('#wrongedition-upgradebtn');

		this.addListener(this.$switchBtn, 'click', 'switchToLicensedEdition');
		this.addListener(this.$upgradeBtn, 'click', 'showUpgradeModal');
	},

	show: function()
	{
		this.base();

		// Can't get out of this one
		this.removeAllListeners(this.$shade);
		Garnish.escManager.unregister(this);
	},

	switchToLicensedEdition: function()
	{
		this.$switchBtn.addClass('disabled');
		this.$upgradeBtn.addClass('disabled');

		this.removeAllListeners(this.$switchBtn);
		this.removeAllListeners(this.$upgradeBtn);

		Craft.postActionRequest('app/switchToLicensedEdition', $.proxy(function(response, textStatus)
		{
			location.reload();
		}, this))
	},

	showUpgradeModal: function()
	{
		if (!this.upgradeModal)
		{
			this.upgradeModal = new Craft.UpgradeModal({
				closeOtherModals: false
			});

			this.upgradeModal.on('upgrade', $.proxy(function()
			{
				this.hide();
			}, this));
		}
		else
		{
			this.upgradeModal.show();
		}
	}
});
