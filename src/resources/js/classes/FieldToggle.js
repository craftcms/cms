/**
 * FieldToggle
 */
Craft.FieldToggle = Garnish.Base.extend(
{
	$toggle: null,
	targetPrefix: null,
	targetSelector: null,
	reverseTargetSelector: null,

	_$target: null,
	_$reverseTarget: null,
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

		if (this.type == 'select')
		{
			this.targetPrefix = (this.$toggle.attr('data-target-prefix') || '');
		}
		else
		{
			this.targetSelector = this.normalizeTargetSelector(this.$toggle.data('target'));
			this.reverseTargetSelector = this.normalizeTargetSelector(this.$toggle.data('reverse-target'));
		}

		this.findTargets();

		if (this.type == 'link')
		{
			this.addListener(this.$toggle, 'click', 'onToggleChange');
		}
		else
		{
			this.addListener(this.$toggle, 'change', 'onToggleChange');
		}
	},

	normalizeTargetSelector: function(selector)
	{
		if (selector && !selector.match(/^[#\.]/))
		{
			selector = '#'+selector;
		}

		return selector;
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
		else if (this.$toggle.prop('nodeName') == 'DIV' && this.$toggle.hasClass('lightswitch'))
		{
			return 'lightswitch';
		}
	},

	findTargets: function()
	{
		if (this.type == 'select')
		{
			this._$target = $(this.normalizeTargetSelector(this.targetPrefix+this.getToggleVal()));
		}
		else
		{
			if (this.targetSelector)
			{
				this._$target = $(this.targetSelector);
			}

			if (this.reverseTargetSelector)
			{
				this._$reverseTarget = $(this.reverseTargetSelector);
			}
		}
	},

	getToggleVal: function()
	{
		if (this.type == 'lightswitch')
		{
			return this.$toggle.children('input').val();
		}
		else
		{
			return Garnish.getInputPostVal(this.$toggle);
		}
	},

	onToggleChange: function()
	{
		if (this.type == 'select')
		{
			this.hideTarget(this._$target);
			this.findTargets();
			this.showTarget(this._$target);
		}
		else
		{
			if (this.type == 'link')
			{
				this.onToggleChange._show = this.$toggle.hasClass('collapsed') || !this.$toggle.hasClass('expanded');
			}
			else
			{
				this.onToggleChange._show = !!this.getToggleVal();
			}

			if (this.onToggleChange._show)
			{
				this.showTarget(this._$target);
				this.hideTarget(this._$reverseTarget);
			}
			else
			{
				this.hideTarget(this._$target);
				this.showTarget(this._$reverseTarget);
			}

			delete this.onToggleChange._show;
		}
	},

	showTarget: function($target)
	{
		if ($target && $target.length)
		{
			this.showTarget._currentHeight = $target.height();

			$target.removeClass('hidden');

			if (this.type != 'select')
			{
				if (this.type == 'link')
				{
					this.$toggle.removeClass('collapsed');
					this.$toggle.addClass('expanded');
				}

				$target.height('auto');
				this.showTarget._targetHeight = $target.height();
				$target.css({
					height: this.showTarget._currentHeight,
					overflow: 'hidden'
				});

				$target.velocity('stop');

				$target.velocity({ height: this.showTarget._targetHeight }, 'fast', function()
				{
					$target.css({
						height: '',
						overflow: ''
					});
				});

				delete this.showTarget._targetHeight;
			}

			delete this.showTarget._currentHeight;

			// Trigger a resize event in case there are any grids in the target that need to initialize
			Garnish.$win.trigger('resize');
		}
	},

	hideTarget: function($target)
	{
		if ($target && $target.length)
		{
			if (this.type == 'select')
			{
				$target.addClass('hidden');
			}
			else
			{
				if (this.type == 'link')
				{
					this.$toggle.removeClass('expanded');
					this.$toggle.addClass('collapsed');
				}

				$target.css('overflow', 'hidden');
				$target.velocity('stop');
				$target.velocity({ height: 0 }, 'fast', function()
				{
					$target.addClass('hidden');
				});
			}
		}
	}
});
