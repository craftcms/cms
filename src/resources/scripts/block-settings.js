(function($) {


var BlockSettingsForm = blx.Base.extend({

	$blockTypeSelect: null,
	$blockTypeSettings: null,
	blockType: null,

	init: function()
	{
		this.$blockTypeSelect = $('#class');
		this.addListener(this.$blockTypeSelect, 'change', 'onBlockTypeChange');
		this.setBlockType();
	},

	setBlockType: function()
	{
		this.blockType = this.$blockTypeSelect.val();
		this.$blockTypeSettings = $('#'+this.blockType+'-settings');
	},

	onBlockTypeChange: function()
	{
		if (this.$blockTypeSettings)
			this.$blockTypeSettings.hide();

		this.setBlockType();
		this.$blockTypeSettings.show();
	}

});


blx.blockSettingsForm = new BlockSettingsForm();


})(jQuery);
