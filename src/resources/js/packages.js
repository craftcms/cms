(function($) {


Craft.PackageChooser = Garnish.Base.extend({

	settings: null,
	$container: null,
	grid: null,
	packages: null,

	ccModal: null,
	$ccModalHeader: null,
	$ccModalSecure: null,
	$ccModalError: null,
	$ccModalCancelBtn: null,
	$ccModalSubmitBtn: null,
	$ccModalSpinner: null,

	init: function(settings)
	{
		this.setSettings(settings);

		this.$container = $('#packages');

		// Set up the package grid
		this.grid = new Craft.Grid(this.$container, {
			minColWidth:      175,
			percentageWidths: false,
			fillMode:         'grid'
		});

		// Find each of the packages
		this.packages = {};

		for (var i = 0; i < this.grid.$items.length; i++)
		{
			var $pkgContainer = $(this.grid.$items[i]),
				pkg           = $pkgContainer.data('package');

			this.packages[pkg] = {
				pkg:           pkg,
				name:          $pkgContainer.find('h2').text(),
				$container:    $pkgContainer,
				$btnContainer: $pkgContainer.children('.buttons')
			};
		}

		// Get their licensed packages
		var data = {
			licenseKey: this.settings.licenseKey
		};

		$.ajax({
			url:     '@@@elliottEndpointUrl@@@actions/licenses/getPackageInfo',
			data:    data,
			success: $.proxy(this, 'initPackages'),
			error:   $.proxy(this, 'handleBadFetchPackageInfoResponse')
		});
	},

	initPackages: function(response)
	{
		// Just to be sure...
		if (!response.success)
		{
			this.handleBadFetchPackageInfoResponse();
			return;
		}

		this.pkgInfo = response.packages;

		for (var pkg in this.packages)
		{
			if (typeof response.packages[pkg] != 'undefined')
			{
				$.extend(this.packages[pkg], response.packages[pkg]);
			}

			this.createButtons(pkg);
		}
	},

	handleBadFetchPackageInfoResponse: function()
	{
		for (var i in this.packages)
		{
			this.packages[i].$btnContainer.children().css('visibility', 'hidden');
		}

		alert(Craft.t('There was a problem determining which packages you’ve purchased.'));
	},

	createButtons: function(pkg)
	{
		var pkgInfo = this.packages[pkg];

		pkgInfo.$btnContainer.html('');

		if (!pkgInfo.price)
		{
			return;
		}

		if (pkgInfo.licensed)
		{
			if (Craft.hasPackage(pkg))
			{
				var $btn = $('<div class="btn noborder pkg-installed">'+Craft.t('Installed!')+'</a>');
			}
			else
			{
				var $btn = $('<div class="btn noborder pkg-uninstalled">'+Craft.t('Uninstalled')+'</a>');
			}
		}
		else
		{
			if (pkgInfo.salePrice)
			{
				var label = '<del class="light">'+this.formatPrice(pkgInfo.price)+'</del> '+this.formatPrice(pkgInfo.salePrice);
			}
			else
			{
				var label = this.formatPrice(pkgInfo.price);
			}

			var $btn = $('<div class="btn price">'+label+'</a>');

			this.addListener($btn, 'activate', { pkg: pkg }, 'purchasePackage');
		}

		$btn.appendTo(pkgInfo.$btnContainer);

		if (pkgInfo.licensed || Craft.hasPackage(pkg))
		{
			var $menuBtn = $('<div class="btn menubtn settings icon"/>').appendTo(pkgInfo.$btnContainer),
				$menu    = $('<div class="menu"/>').appendTo(pkgInfo.$btnContainer),
				$ul      = $('<ul/>').appendTo($menu),
				$li      = $('<li/>').appendTo($ul);

			if (Craft.hasPackage(pkg))
			{
				var label  = Craft.t('Uninstall'),
					action = 'uninstall';
			}
			else
			{
				var label  = Craft.t('Install'),
					action = 'install';
			}

			var $a = $('<a>'+label+'</a>').appendTo($li)
				.data('package', pkg)
				.data('action',  action);

			new Garnish.MenuBtn($menuBtn, {
				onOptionSelect: $.proxy(this, 'onOptionSelect')
			});
		}
	},

	purchasePackage: function(ev)
	{
		var pkg = ev.data.pkg;

		if (!this.ccModal)
		{
			var $modal = $(this.settings.modalHtml).appendTo(document.body);
			this.ccModal = new Garnish.Modal($modal);

			this.$ccModalHeader    = $modal.find('h1:first');
			this.$ccModalSecure    = $modal.find('.secure:first');
			this.$ccModalCancelBtn = $modal.find('.btn.cancel:first');
			this.$ccModalSubmitBtn = $modal.find('.btn.submit:first');
			this.$ccModalSpinner   = $modal.find('.spinner:first');

			this.addListener(this.$ccModalCancelBtn, 'activate', function() {
				this.ccModal.hide();
			});
		}
		else
		{
			// Cleanup from last time
			this.removeListener(this.ccModal.$container, 'submit');
			this.cleanupCcModal();

			this.ccModal.show();
		}

		// Update the header
		var header = Craft.t('Purchase {package}', { 'package': '<em>'+this.packages[pkg].name+'</em>' });
		this.$ccModalHeader.html(header);

		// Attach the event listeners
		this.addListener(this.ccModal.$container, 'submit', { pkg: pkg }, 'submitPackagePurchase');

		if (!Garnish.isMobileBrowser())
		{
			// Focus on the name input once the modal has finished fading in
			setTimeout(function() {
				$('#cc-name').focus();
			}, 500);
		}
	},

	cleanupCcModal: function()
	{
		this.ccModal.$container.find('.error').removeClass('error');

		if (this.$ccModalError)
		{
			this.$ccModalError.remove();
		}
	},

	submitPackagePurchase: function(ev)
	{
		ev.preventDefault();
		this.cleanupCcModal();

		var pkg = ev.data.pkg;

		// Get the CC data
		var ccData = {
			name:      $('#cc-name').val(),
		    number:    $('#cc-num').val(),
		    exp_month: $('#cc-month').val(),
		    exp_year:  $('#cc-year').val(),
		    cvc:       $('#cc-cvc').val()
		};

		// Validate it
		var validates = true;

		if (!ccData.name)
		{
			validates = false;
			$('#cc-name').addClass('error');
		}

		if (!Stripe.validateCardNumber(ccData.number))
		{
			validates = false;
			$('#cc-num').addClass('error');
		}

		if (!Stripe.validateExpiry(ccData.exp_month, ccData.exp_year))
		{
			validates = false;
			$('#cc-month').addClass('error');
			$('#cc-year').addClass('error');
		}

		if (!Stripe.validateCVC(ccData.cvc))
		{
			validates = false;
			$('#cc-cvc').addClass('error');
		}

		if (validates)
		{
			// Get a CC token from Stripe.js
			this.$ccModalSubmitBtn.addClass('active');
			this.$ccModalSpinner.removeClass('hidden');

			Stripe.setPublishableKey('@@@stripePublishableKey@@@');
			Stripe.createToken(ccData, $.proxy(function(status, response)
			{
				if (!response.error)
				{
					// Pass the token along to Elliott to charge the card
					var data = {
						ccTokenId:  response.id,
						'package':  pkg,
						price:      (this.packages[pkg].salePrice ? this.packages[pkg].salePrice : this.packages[pkg].price),
						licenseKey: this.settings.licenseKey,
						email:      this.settings.email,
						version:    this.settings.version,
						build:      this.settings.build
					};

					$.ajax({
						url:     '@@@elliottEndpointUrl@@@actions/licenses/purchasePackage',
						type:    'POST',
						data:    data,

						success: $.proxy(function(response)
						{
							if (!response.success && response.error != 'license_has_package')
							{
								this.handleUnsuccessfulPurchase(response.error);
							}
							else
							{
								this.handleSuccessfulPurchase(pkg);
							}
						}, this),

						error:   $.proxy(this, 'handleUnsuccessfulPurchase')
					});
				}
				else
				{
					this.onPurchaseResponse();
					Garnish.shake(this.ccModal.$container);
				}
			}, this));
		}
		else
		{
			Garnish.shake(this.ccModal.$container);
		}
	},

	onPurchaseResponse: function()
	{
		this.$ccModalSubmitBtn.removeClass('active');
		this.$ccModalSpinner.addClass('hidden');
	},

	handleSuccessfulPurchase: function(pkg)
	{
		this.packages[pkg].licensed = true;

		// Was the package already installed?
		if (Craft.hasPackage(pkg))
		{
			this.createButtons(pkg);
			this.onPurchaseComplete(pkg);
		}
		else
		{
			this.performPackageAction(pkg, 'install', $.proxy(this, 'onPurchaseComplete', pkg));
		}
	},

	onPurchaseComplete: function(pkg)
	{
		this.onPurchaseResponse();
		this.ccModal.hide();
		Craft.cp.displayNotice(Craft.t('{package} purchased successfully!', { 'package': this.packages[pkg].name }));
	},

	handleUnsuccessfulPurchase: function(error)
	{
		this.onPurchaseResponse();

		if (error)
		{
			if (typeof this.settings.errors[error] != 'undefined')
			{
				switch (error)
				{
					case 'incorrect_number':
					case 'invalid_number':
					{
						$('#cc-num').addClass('error');
						break;
					}

					case 'invalid_cvc':
					case 'incorrect_cvc':
					{
						$('#cc-cvc').addClass('error');
						break;
					}
				}

				error = this.settings.errors[error];
			}

			this.$ccModalError = $('<p class="error centeralign">'+error+'</p>').insertBefore(this.$ccModalSecure);
		}

		Garnish.shake(this.ccModal.$container);
	},

	onOptionSelect: function(option)
	{
		var $option  = $(option),
			pkg      = $option.data('package'),
			action   = $option.data('action');

		this.performPackageAction(pkg, action);
	},

	performPackageAction: function(pkg, action, callback)
	{
		// Show the spinner
		var $btnContainer = this.packages[pkg].$btnContainer,
			$spinner = $('<div class="spinner"/>').appendTo($btnContainer);

		var data = {
			'package': pkg
		};

		Craft.postActionRequest('packages/'+action+'Package', data, $.proxy(function(response) {
			$spinner.hide();

			if (!response.success)
			{
				alert(Craft.t('An unknown error occurred.'));
				return;
			}

			switch (action)
			{
				case 'install':
				{
					Craft.packages.push(pkg);
					break;
				}

				case 'uninstall':
				{
					var index = $.inArray(pkg, Craft.packages);
					Craft.packages.splice(index, 1);
					break;
				}
			}

			this.createButtons(pkg);

			if (typeof callback == 'function')
			{
				callback();
			}
		}, this));
	},

	formatPrice: function(price)
	{
		var formattedPrice = this.settings.USD + Math.floor(price/100),
			cents = price % 100;

		if (cents)
		{
			cents = cents.toString();

			while (cents.length < 2)
			{
				cents += '0';
			}

			formattedPrice += '.'+cents;
		}

		return formattedPrice;
	}
});


})(jQuery);
