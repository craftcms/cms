/**
 * Craft Upgrade Modal
 */
Craft.UpgradeModal = Garnish.Modal.extend(
{
	$container: null,
	$body: null,
	$compareScreen: null,
	$checkoutScreen: null,
	$successScreen: null,

	$checkoutForm: null,
	$checkoutLogo: null,
	$checkoutPrice: null,
	$checkoutSubmitBtn: null,
	$checkoutSpinner: null,
	$checkoutFormError: null,
	$checkoutSecure: null,
	clearCheckoutFormTimeout: null,
	$ccNameInput: null,
	$ccNumInput: null,
	$ccMonthInput: null,
	$ccYearInput: null,
	$ccCvcInput: null,
	submittingPurchase: false,

	editions: null,
	edition: null,

	init: function(settings)
	{
		this.$container = $('<div id="upgrademodal" class="modal loading"/>').appendTo(Garnish.$bod),

		this.base(this.$container, $.extend({
			resizable: true
		}, settings));

		Craft.postActionRequest('app/getUpgradeModal', $.proxy(function(response, textStatus)
		{
			this.$container.removeClass('loading');

			if (textStatus == 'success')
			{
				if (response.success)
				{
					this.editions = response.editions;

					this.$container.append(response.modalHtml);

					this.$compareScreen     = this.$container.children('#upgrademodal-compare');
					this.$checkoutScreen    = this.$container.children('#upgrademodal-checkout');
					this.$successScreen     = this.$container.children('#upgrademodal-success');

					this.$checkoutLogo      = this.$checkoutScreen.find('.logo:first');
					this.$checkoutPrice     = this.$checkoutScreen.find('.price:first');
					this.$checkoutForm      = this.$checkoutScreen.find('form:first');
					this.$checkoutSubmitBtn = this.$checkoutForm.find('.submit:first');
					this.$checkoutSpinner   = this.$checkoutForm.find('.spinner:first');
					this.$ccNameInput       = this.$checkoutForm.find('#cc-name');
					this.$ccNumInput        = this.$checkoutForm.find('#cc-num');
					this.$ccMonthInput      = this.$checkoutForm.find('#cc-month');
					this.$ccYearInput       = this.$checkoutForm.find('#cc-year');
					this.$ccCvcInput        = this.$checkoutForm.find('#cc-cvc');
					this.$checkoutSecure    = this.$checkoutScreen.find('.secure:first');

					var $buyBtns = this.$compareScreen.find('.buybtn');
					this.addListener($buyBtns, 'click', 'onBuyBtnClick');

					var $testBtns = this.$compareScreen.find('.btn.test');
					this.addListener($testBtns, 'click', 'onTestBtnClick');

					this.addListener(this.$checkoutForm, 'submit', 'submitPurchase');

					var $cancelCheckoutBtn = this.$checkoutScreen.find('#upgrademodal-cancelcheckout');
					this.addListener($cancelCheckoutBtn, 'click', 'cancelCheckout');
				}
				else
				{
					if (response.error)
					{
						var error = response.error;
					}
					else
					{
						var error = Craft.t('An unknown error occurred.');
					}

					this.$container.append('<div class="body">'+error+'</div>');
				}

				// Include Stripe.js
				$('<script type="text/javascript" src="https://js.stripe.com/v1/"></script>').appendTo(Garnish.$bod);
			}
		}, this));
	},

	onHide: function()
	{
		this.clearCheckoutFormInABit();
		this.base();
	},

	onBuyBtnClick: function(ev)
	{
		var $btn = $(ev.currentTarget);
		this.edition = $btn.data('edition');

		var editionInfo = this.editions[this.edition],
			width = this.getWidth();

		switch (this.edition)
		{
			case 1:
			{
				this.$checkoutLogo.attr('class', 'logo craftclient').text('Craft Client');
				break;
			}
			case 2:
			{
				this.$checkoutLogo.attr('class', 'logo craftpro').text('Craft Pro');
				break;
			}
		}

		if (editionInfo.salePrice)
		{
			this.$checkoutPrice.html('<span class="listedprice">'+editionInfo.formattedPrice+'</span> '+editionInfo.formattedSalePrice);
		}
		else
		{
			this.$checkoutPrice.html(editionInfo.formattedPrice);
		}

		if (this.clearCheckoutFormTimeout)
		{
			clearTimeout(this.clearCheckoutFormTimeout);
		}

		this.$compareScreen.stop().animateLeft(-width, 'fast', $.proxy(function()
		{
			this.$compareScreen.addClass('hidden');
		}, this));

		this.$checkoutScreen.stop().css(Craft.left, width).removeClass('hidden').animateLeft(0, 'fast');
	},

	onTestBtnClick: function(ev)
	{
		var data = {
			edition: $(ev.currentTarget).data('edition')
		};

		Craft.postActionRequest('app/testUpgrade', data, $.proxy(function(response, textStatus)
		{
			if (textStatus == 'success')
			{
				var width = this.getWidth();

				this.$compareScreen.stop().animateLeft(-width, 'fast', $.proxy(function()
				{
					this.$compareScreen.addClass('hidden');
				}, this));

				this.onUpgrade();
			}
		}, this));
	},

	cancelCheckout: function()
	{
		var width = this.getWidth();

		this.$compareScreen.stop().removeClass('hidden').animateLeft(0, 'fast');
		this.$checkoutScreen.stop().animateLeft(width, 'fast', $.proxy(function()
		{
			this.$checkoutScreen.addClass('hidden');
		}, this))

		this.clearCheckoutFormInABit();
	},

	submitPurchase: function(ev)
	{
		ev.preventDefault();

		if (this.submittingPurchase)
		{
			return;
		}

		this.cleanupCheckoutForm();

		var pkg = ev.data.pkg;

		// Get the CC data
		var ccData = {
			name:      this.$ccNameInput.val(),
		    number:    this.$ccNumInput.val(),
		    exp_month: this.$ccMonthInput.val(),
		    exp_year:  this.$ccYearInput.val(),
		    cvc:       this.$ccCvcInput.val()
		};

		// Validate it
		var validates = true;

		if (!ccData.name)
		{
			validates = false;
			this.$ccNameInput.addClass('error');
		}

		if (!Stripe.validateCardNumber(ccData.number))
		{
			validates = false;
			this.$ccNumInput.addClass('error');
		}

		if (!Stripe.validateExpiry(ccData.exp_month, ccData.exp_year))
		{
			validates = false;
			this.$ccMonthInput.addClass('error');
			this.$ccYearInput.addClass('error');
		}

		if (!Stripe.validateCVC(ccData.cvc))
		{
			validates = false;
			this.$ccCvcInput.addClass('error');
		}

		if (validates)
		{
			this.submittingPurchase = true;

			// Get a CC token from Stripe.js
			this.$checkoutSubmitBtn.addClass('active');
			this.$checkoutSpinner.removeClass('hidden');

			Stripe.setPublishableKey(Craft.UpgradeModal.stripeApiKey);
			Stripe.createToken(ccData, $.proxy(function(status, response)
			{
				if (!response.error)
				{
					// Pass the token along to Elliott to charge the card
					var data = {
						ccTokenId:     response.id,
						edition:       this.edition,
						expectedPrice: (this.editions[this.edition].salePrice ? this.editions[this.edition].salePrice : this.editions[this.edition].price)
					};

					Craft.postActionRequest('app/purchaseUpgrade', data, $.proxy(this, 'onPurchaseUpgrade'));
				}
				else
				{
					this.onPurchaseResponse();
					Garnish.shake(this.$checkoutForm);
				}
			}, this));
		}
		else
		{
			Garnish.shake(this.$checkoutForm);
		}
	},

	onPurchaseResponse: function()
	{
		this.submittingPurchase = false;
		this.$checkoutSubmitBtn.removeClass('active');
		this.$checkoutSpinner.addClass('hidden');
	},

	onPurchaseUpgrade: function(response, textStatus)
	{
		this.onPurchaseResponse();

		if (textStatus == 'success')
		{
			if (response.success)
			{
				var width = this.getWidth();

				this.$checkoutScreen.stop().animateLeft(-width, 'fast', $.proxy(function()
				{
					this.$checkoutScreen.addClass('hidden');
				}, this));

				this.onUpgrade();
			}
			else
			{
				if (response.errors)
				{
					var errorText = '';

					for (var i in response.errors)
					{
						if (errorText)
						{
							errorText += '<br>';
						}

						errorText += response.errors[i];
					}

					this.$checkoutFormError = $('<p class="error centeralign">'+errorText+'</p>').insertBefore(this.$checkoutSecure);
				}

				Garnish.shake(this.$checkoutForm);
			}
		}
	},

	onUpgrade: function()
	{
		this.$successScreen.css(Craft.left, this.getWidth()).removeClass('hidden').animateLeft(0, 'fast');

		var $refreshBtn = this.$successScreen.find('.btn:first');
		this.addListener($refreshBtn, 'click', function()
		{
			location.reload();
		});

		this.trigger('upgrade');
	},

	cleanupCheckoutForm: function()
	{
		this.$checkoutForm.find('.error').removeClass('error');

		if (this.$checkoutFormError)
		{
			this.$checkoutFormError.remove();
			this.$checkoutFormError = null;
		}
	},

	clearCheckoutForm: function()
	{
		this.$ccNameInput.val('');
	    this.$ccNumInput.val('');
	    this.$ccMonthInput.val('');
	    this.$ccYearInput.val('');
	    this.$ccCvcInput.val('');
	},

	clearCheckoutFormInABit: function()
	{
		// Clear the CC info after a period of inactivity
		this.clearCheckoutFormTimeout = setTimeout(
			$.proxy(this, 'clearCheckoutForm'),
			Craft.UpgradeModal.clearCheckoutFormTimeoutDuration
		);
	}
},
{
	stripeApiKey: '@@@stripePublishableKey@@@',
	clearCheckoutFormTimeoutDuration: 30000 // 1000 x 60 x 5
});
