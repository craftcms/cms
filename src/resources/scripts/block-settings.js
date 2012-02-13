(function($) {


var BlockSettingsForm = blx.Base.extend({

	$blockTypeSelect: null,
	$blockTypeSettings: null,
	blockType: null,

	init: function()
	{
		this.$blockTypeSelect = $('#class');

		this.onBlockTypeChange();
		this.addListener(this.$blockTypeSelect, 'change', 'onBlockTypeChange');
	},

	onBlockTypeChange: function()
	{
		if (this.$blockTypeSettings)
			this.$blockTypeSettings.hide();

		this.blockType = this.$blockTypeSelect.val();
		this.$blockTypeSettings = $('#'+this.blockType+'-settings');
		this.$blockTypeSettings.show();
	}

});


blx.blockSettingsForm = new BlockSettingsForm();


})(jQuery);
