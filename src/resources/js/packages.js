(function($) {


Craft.PackageChooser = Garnish.Base.extend({

	$container: null,
	grid: null,
	btnContainers: null,

	init: function()
	{
		this.$container = $('#packages');

		// Set up the package grid
		this.grid = new Craft.Grid(this.$container, {
			minColWidth:      175,
			percentageWidths: false,
			fillMode:         'grid'
		});

		// Find each of the button containers
		this.btnContainers = {};
		var $btnContainers = this.grid.$items.children('.buttons');

		for (var i = 0; i < $btnContainers.length; i++)
		{
			var $btnContainer = $($btnContainers[i]),
				pkg           = $btnContainer.data('package');

			this.btnContainers[pkg] = $btnContainer;
		}

		// Get their licensed packages
		Craft.postActionRequest('packages/fetchPackageInfo',
			$.proxy(this, 'initPackages'),
			$.proxy(this, 'handleBadResponse')
		);
	},

	initPackages: function(response)
	{
		// Just to be sure...
		if (!response.success)
		{
			this.handleBadResponse();
			return;
		}

		this.pkgInfo = response.packages;

		for (var pkg in this.btnContainers)
		{
			this.createButtons(pkg);
		}
	},

	handleBadResponse: function()
	{
		for (var i in this.btnContainers)
		{
			this.btnContainers[i].children().css('visibility', 'hidden');
		}

		alert(Craft.t('There was a problem determining which packages you’ve purchased.'));
	},

	createButtons: function(pkg)
	{
		var $btnContainer = this.btnContainers[pkg],
			pkgInfo       = this.pkgInfo[pkg];

		$btnContainer.html('');

		if (pkgInfo.licensed)
		{
			if (Craft.hasPackage(pkg))
			{
				var $btn = $('<div class="btn noborder pkg-installed">'+Craft.t('Installed!')+'</a>');
			}
			else
			{
				var $btn = $('<div class="btn noborder pkg-disabled">'+Craft.t('Disabled')+'</a>');
			}
		}
		else
		{
			if (pkgInfo.salePrice)
			{
				var label = '<del class="light">'+pkgInfo.price+'</del> '+pkgInfo.salePrice;
			}
			else
			{
				var label = pkgInfo.price;
			}

			var $btn = $('<div class="btn price">'+label+'</a>');
		}

		$btn.appendTo($btnContainer);

		if (pkgInfo.licensed || Craft.hasPackage(pkg))
		{
			var $menuBtn = $('<div class="btn menubtn settings icon"/>').appendTo($btnContainer),
				$menu    = $('<div class="menu"/>').appendTo($btnContainer),
				$ul      = $('<ul/>').appendTo($menu),
				$li      = $('<li/>').appendTo($ul);

			if (Craft.hasPackage(pkg))
			{
				var label  = Craft.t('Disable'),
					action = 'disablePackage';
			}
			else
			{
				var label  = Craft.t('Enable'),
					action = 'enablePackage';
			}

			var $a = $('<a>'+label+'</a>').appendTo($li)
				.data('package', pkg)
				.data('action',  action);

			new Garnish.MenuBtn($menuBtn, {
				onOptionSelect: $.proxy(this, 'onOptionSelect')
			});
		}
	},

	onOptionSelect: function(option)
	{
		var $option  = $(option),
			pkg      = $option.data('package'),
			action   = $option.data('action');

		// Show the spinner
		var $btnContainer = this.btnContainers[pkg],
			$spinner = $('<div class="spinner"/>').appendTo($btnContainer);

		var data = {
			'package': pkg
		};

		Craft.postActionRequest('packages/'+action, data, $.proxy(function(response) {
			$spinner.hide();

			if (!response.success)
			{
				alert(Craft.t('An unknown error occurred.'));
				return;
			}

			switch (action)
			{
				case 'enablePackage':
				{
					Craft.packages.push(pkg);
					break;
				}

				case 'disablePackage':
				{
					var index = $.inArray(pkg, Craft.packages);
					Craft.packages.splice(index, 1);
					break;
				}
			}

			this.createButtons(pkg);
		}, this));
	}
});

Craft.packageChooser = new Craft.PackageChooser();


})(jQuery);
