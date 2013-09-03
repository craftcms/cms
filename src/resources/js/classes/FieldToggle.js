/**
 * FieldToggle
 */
Craft.FieldToggle = Garnish.Base.extend({

	$toggle: null,
	reverse: null,
	targetPrefix: null,

	_$target: null,
	type: null,

	init: function(toggle)
	{
		this.$toggle = $(toggle);

		// Is this already a field toggle?
		if (this.$toggle.data('fieldtoggle'))
		{
			Garnish.log('Double-instantiating a field toggle on an element');
			this.$toggle.data('fieldtoggle').destroy();
		}

		this.$toggle.data('fieldtoggle', this);

		this.type = this.getType();
		this.reverse = !!this.$toggle.attr('data-reverse-toggle');

		if (this.type == 'select')
		{
			this.targetPrefix = (this.$toggle.attr('data-target-prefix') || '');
			this.findTarget();
		}

		if (this.type == 'link')
		{
			this.addListener(this.$toggle, 'click', 'onToggleChange');
		}
		else
		{
			this.addListener(this.$toggle, 'change', 'onToggleChange');
		}
	},

	getType: function()
	{
		if (this.$toggle.prop('nodeName') == 'INPUT' && this.$toggle.attr('type').toLowerCase() == 'checkbox')
		{
			return 'checkbox';
		}
		else if (this.$toggle.prop('nodeName') == 'SELECT')
		{
			return 'select';
		}
		else if (this.$toggle.prop('nodeName') == 'A')
		{
			return 'link';
		}
	},

	getTarget: function()
	{
		if (!this._$target)
		{
			this.findTarget();
		}

		return this._$target;
	},

	findTarget: function()
	{
		if (this.type == 'select')
		{
			this._$target = $('#'+this.targetPrefix+this.getToggleVal());
		}
		else
		{
			var targetSelector = this.$toggle.data('target');

			if (!targetSelector.match(/^[#\.]/))
			{
				targetSelector = '#'+targetSelector;
			}

			this._$target = $(targetSelector);
		}
	},

	getToggleVal: function()
	{
		return Garnish.getInputPostVal(this.$toggle);
	},

	onToggleChange: function()
	{
		if (this.type == 'select')
		{
			this.hideTarget();
			this.findTarget();
			this.showTarget();
		}
		else
		{
			if (this.type == 'link')
			{
				var show = this.$toggle.hasClass('collapsed');
			}
			else
			{
				var show = !!this.getToggleVal();
			}

			if (this.reverse)
			{
				show = !show;
			}

			if (show)
			{
				this.showTarget();
			}
			else
			{
				this.hideTarget();
			}
		}
	},

	showTarget: function()
	{
		if (this.getTarget().length)
		{
			this.getTarget().removeClass('hidden');

			if (this.type != 'select')
			{
				if (this.type == 'link')
				{
					this.$toggle.removeClass('collapsed');
					this.$toggle.addClass('expanded');
				}

				var $target = this.getTarget();
				$target.height('auto');
				var height = $target.height();
				$target.height(0);
				$target.stop().animate({height: height}, 'fast', $.proxy(function() {
					$target.height('auto');
				}, this));
			}
		}
	},

	hideTarget: function()
	{
		if (this.getTarget().length)
		{
			if (this.type == 'select')
			{
				this.getTarget().addClass('hidden');
			}
			else
			{
				if (this.type == 'link')
				{
					this.$toggle.removeClass('expanded');
					this.$toggle.addClass('collapsed');
				}

				this.getTarget().stop().animate({height: 0}, 'fast', $.proxy(function() {
					this.getTarget().addClass('hidden');
				}, this));
			}
		}
	}
});
