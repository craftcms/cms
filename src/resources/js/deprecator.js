(function($) {

var Deprecator = Garnish.Base.extend(
{
	_modal: null,
	$modalContainerDiv: null,

	init: function()
	{
		this._modal = null;
		this.$modalContainerDiv = null;
		var _this = this;

		$('.deprecatorModal').each(function ()
		{
			$(this).click(function ()
			{
				_this._showModal($(this).attr('href'));
				return false;
			})
		});
	},

	_showModal: function (url)
	{
		if (!this._modal)
		{
			this._modal = new Garnish.Modal();
		}

		if (this.$modalContainerDiv == null) {
			this.$modalContainerDiv = $('<div class="modal trace-modal"></div>').addClass().appendTo(Garnish.$bod);
		}

		var _this = this;
		$.get(url, $.proxy(function (data)
			{
				this.$modalContainerDiv.html(data);
				this.$modalContainerDiv.find('.cancel').click(function () {_this._modal.hide();});
				this._modal.setContainer(this.$modalContainerDiv);
				this._modal.show();
			}, this)
		);
	}
});

new Deprecator();

})(jQuery);
