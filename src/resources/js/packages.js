(function($) {


Craft.PackageChooser = Garnish.Base.extend({

	settings: null,
	$container: null,
	grid: null,
	packages: null,
	clearCcModalTimeout: null,

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
				$pkgHeading   = $pkgContainer.find('h2:first'),
				pkg           = $pkgContainer.data('package');

			this.packages[pkg] = {
				pkg:           pkg,
				name:          $pkgHeading.text(),
				$container:    $pkgContainer,
				$heading:      $pkgHeading,
				$btnContainer: $pkgContainer.children('.buttons')
			};
		}

		Craft.postActionRequest('app/fetchPackageInfo', $.proxy(this, 'initPackages'));
	},

	initPackages: function(response)
	{
		if (!response.success)
		{
			if (response.error)
			{
				var error = response.error;
			}
			else
			{
				var error = Craft.t('An unknown error occurred.');
			}

			alert(error);
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

	createButtons: function(pkg)
	{
		var pkgInfo = this.packages[pkg];

		if (pkgInfo.$badge)
		{
			pkgInfo.$badge.remove();
			delete pkgInfo.$badge;
		}

		pkgInfo.$btnContainer.html('');

		if (!pkgInfo.price)
		{
			return;
		}

		if (pkgInfo.licensed)
		{
			if (Craft.hasPackage(pkg))
			{
				pkgInfo.$badge = $('<div class="badge installed">'+Craft.t('Installed!')+'</div>');
			}
			else
			{
				pkgInfo.$badge = $('<div class="badge uninstalled">'+Craft.t('Uninstalled')+'</div>');
			}
		}
		else
		{
			if (pkgInfo.trial)
			{
				// Trial badge
				if (Craft.hasPackage(pkg))
				{
					var badgeClass = 'trial';
				}
				else
				{
					var badgeClass = 'uninstalled';
				}

				if (pkgInfo.daysLeftInTrial == 1)
				{
					var badgeLabel = Craft.t('1 day left');
				}
				else
				{
					var badgeLabel = Craft.t('{days} days left', { days: pkgInfo.daysLeftInTrial });
				}

				pkgInfo.$badge = $('<div class="badge '+badgeClass+'">'+badgeLabel+'</div>');
			}
			else if (Craft.hasPackage(pkg))
			{
				// Unlicensed badge
				pkgInfo.$badge = $('<div class="badge unlicensed">'+Craft.t('Unlicensed')+'</div>');
			}

			// Price / Buy button
			if (pkgInfo.salePrice)
			{
				var buyBtnLabel = '<del class="light">'+this.formatPrice(pkgInfo.price)+'</del> '+this.formatPrice(pkgInfo.salePrice);
			}
			else
			{
				var buyBtnLabel = this.formatPrice(pkgInfo.price);
			}

			var $buyBtn = $('<div class="btn buy">'+buyBtnLabel+'<span>'+Craft.t('Buy')+'</span></div>').appendTo(pkgInfo.$btnContainer);
			this.addListener($buyBtn, 'activate', { pkg: pkg }, 'purchasePackage');

			// Try button
			if (pkgInfo.eligibleForTrial)
			{
				var $tryBtn = $('<div class="btn try">'+Craft.t('Try')+'</div>').appendTo(pkgInfo.$btnContainer);
				this.addListener($tryBtn, 'activate', { pkg: pkg }, 'tryPackage');
			}
		}

		if (pkgInfo.$badge)
		{
			pkgInfo.$badge.insertBefore(pkgInfo.$heading);
		}

		if (pkgInfo.licensed || pkgInfo.trial || Craft.hasPackage(pkg))
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
			this.ccModal = new Garnish.Modal($modal, {
				onShow: $.proxy(this, 'onCcModalShow'),
				onHide: $.proxy(this, 'onCcModalHide')
			});

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

		if (!Garnish.isMobileBrowser(true))
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

			Stripe.setPublishableKey(this.settings.stripeApiKey);
			Stripe.createToken(ccData, $.proxy(function(status, response)
			{
				if (!response.error)
				{
					// Pass the token along to Elliott to charge the card
					var data = {
						ccTokenId:     response.id,
						'package':     pkg,
						expectedPrice: (this.packages[pkg].salePrice ? this.packages[pkg].salePrice : this.packages[pkg].price)
					};

					Craft.postActionRequest('app/purchasePackage', data,
						$.proxy(this, 'handleSuccessfulPurchase'),
						$.proxy(this, 'handleUnsuccessfulPurchase')
					);
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

	handleSuccessfulPurchase: function(response)
	{
		if (!response.success)
		{
			this.handleUnsuccessfulPurchase(response.errors);
			return;
		}

		var pkg = response['package'];
		this.packages[pkg].licensed = true;

		if (!Craft.hasPackage(pkg))
		{
			Craft.packages.push(pkg);
		}

		this.onPurchaseResponse();
		this.ccModal.hide();
		this.createButtons(pkg);

		Craft.cp.displayNotice(Craft.t('{package} purchased successfully!', { 'package': this.packages[pkg].name }));
	},

	handleUnsuccessfulPurchase: function(errors)
	{
		this.onPurchaseResponse();

		if (errors)
		{
			var errorText = '';
			for (var attribute in errors)
			{
				for (var i = 0; i < errors[attribute].length; i++)
				{
					if (errorText)
					{
						errorText += '<br>';
					}

					errorText += errors[attribute][i];
				}
			}

			this.$ccModalError = $('<p class="error centeralign">'+errorText+'</p>').insertBefore(this.$ccModalSecure);
		}

		Garnish.shake(this.ccModal.$container);
	},

	onCcModalShow: function()
	{
		if (this.clearCcModalTimeout)
		{
			clearTimeout(this.clearCcModalTimeout);
		}
	},

	onCcModalHide: function()
	{
		// Clear the CC info after a period of inactivity
		this.clearCcModalTimeout = setTimeout($.proxy(function()
		{
			$('#cc-name').val('');
		    $('#cc-num').val('');
		    $('#cc-month').val(''); // Somehow I find it awesome that this works.
		    $('#cc-year').val('');
		    $('#cc-cvc').val('');

		}, this), Craft.PackageChooser.clearCcModalTimeoutDuration);
	},

	tryPackage: function(ev)
	{
		var pkg = ev.data.pkg;

		if (confirm(Craft.t('Start your 30-day {package} trial?', { 'package': this.packages[pkg].name })))
		{
			var data = {
				'package': pkg
			};

			Craft.postActionRequest('app/beginPackageTrial', data, $.proxy(function(response)
			{
				if (!response.success)
				{
					if (response.error)
					{
						alert(response.error);
					}
					else
					{
						alert(Craft.t('An unknown error occurred.'));
					}

					return;
				}

				// Mark it as installed
				Craft.packages.push(pkg);

				// Mark it as in trial
				this.packages[pkg].trial = true;
				this.packages[pkg].eligibleForTrial = false;
				this.packages[pkg].daysLeftInTrial = 30;

				this.createButtons(pkg);
			}, this));
		}
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
		var $btnContainer = this.packages[pkg].$btnContainer;

		var data = {
			'package': pkg
		};

		Craft.postActionRequest('app/'+action+'Package', data, $.proxy(function(response)
		{
			if (!response.success)
			{
				if (response.error)
				{
					alert(response.error);
				}
				else
				{
					alert(Craft.t('An unknown error occurred.'));
				}

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
},
{
	clearCcModalTimeoutDuration: 30000 // 1000 x 60 x 5
});


})(jQuery);
