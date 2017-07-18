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
	$checkoutSubmitBtn: null,
	$checkoutSpinner: null,
	$checkoutFormError: null,
	$checkoutSecure: null,
	clearCheckoutFormTimeout: null,
	$customerNameInput: null,
	$customerEmailInput: null,
	$ccField: null,
	$ccNumInput: null,
	$ccExpInput: null,
	$ccCvcInput: null,
	$businessFieldsToggle: null,
	$businessNameInput: null,
	$businessAddress1Input: null,
	$businessAddress2Input: null,
	$businessCityInput: null,
	$businessStateInput: null,
	$businessCountryInput: null,
	$businessZipInput: null,
	$businessTaxIdInput: null,
	$purchaseNotesInput: null,
	$couponInput: null,
	$couponSpinner: null,
	submittingPurchase: false,

	stripePublicKey: null,
	editions: null,
	countries: null,
	states: null,
	edition: null,
	initializedCheckoutForm: false,

	applyingCouponCode: false,
	applyNewCouponCodeAfterDoneLoading: false,
	couponPrice: null,
	formattedCouponPrice: null,

	init: function(settings)
	{
		this.$container = $('<div id="upgrademodal" class="modal loading"/>').appendTo(Garnish.$bod);

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
					this.stripePublicKey = response.stripePublicKey;
					this.editions = response.editions;
					this.countries = response.countries;
					this.states = response.states;

					this.$container.append(response.modalHtml);
					this.$container.append('<script type="text/javascript" src="'+Craft.getResourceUrl('lib/jquery.payment'+(Craft.useCompressedJs ? '.min' : '')+'.js')+'"></script>');

					this.$compareScreen     = this.$container.children('#upgrademodal-compare');
					this.$checkoutScreen    = this.$container.children('#upgrademodal-checkout');
					this.$successScreen     = this.$container.children('#upgrademodal-success');

					this.$checkoutLogo           = this.$checkoutScreen.find('.logo:first');
					this.$checkoutForm           = this.$checkoutScreen.find('form:first');
					this.$checkoutSubmitBtn      = this.$checkoutForm.find('#pay-button');
					this.$checkoutSpinner        = this.$checkoutForm.find('#pay-spinner');
					this.$customerNameInput      = this.$checkoutForm.find('#customer-name');
					this.$customerEmailInput     = this.$checkoutForm.find('#customer-email');
					this.$ccField                = this.$checkoutForm.find('#cc-inputs');
					this.$ccNumInput             = this.$ccField.find('#cc-num');
					this.$ccExpInput             = this.$ccField.find('#cc-exp');
					this.$ccCvcInput             = this.$ccField.find('#cc-cvc');
					this.$businessFieldsToggle   = this.$checkoutForm.find('.fieldtoggle');
					this.$businessNameInput      = this.$checkoutForm.find('#business-name');
					this.$businessAddress1Input  = this.$checkoutForm.find('#business-address1');
					this.$businessAddress2Input  = this.$checkoutForm.find('#business-address2');
					this.$businessCityInput      = this.$checkoutForm.find('#business-city');
					this.$businessStateInput     = this.$checkoutForm.find('#business-state');
					this.$businessCountryInput   = this.$checkoutForm.find('#business-country');
					this.$businessZipInput       = this.$checkoutForm.find('#business-zip');
					this.$businessTaxIdInput     = this.$checkoutForm.find('#business-taxid');
					this.$purchaseNotesInput     = this.$checkoutForm.find('#purchase-notes');
					this.$checkoutSecure         = this.$checkoutScreen.find('.secure:first');
					this.$couponInput            = this.$checkoutForm.find('#coupon-input');
					this.$couponSpinner          = this.$checkoutForm.find('#coupon-spinner');

					var $buyBtns = this.$compareScreen.find('.buybtn');
					this.addListener($buyBtns, 'click', 'onBuyBtnClick');

					var $testBtns = this.$compareScreen.find('.btn.test');
					this.addListener($testBtns, 'click', 'onTestBtnClick');

					var $cancelCheckoutBtn = this.$checkoutScreen.find('#upgrademodal-cancelcheckout');
					this.addListener($cancelCheckoutBtn, 'click', 'cancelCheckout');
				}
				else
				{
					var error;

					if (response.error)
					{
						error = response.error;
					}
					else
					{
						error = Craft.t('An unknown error occurred.');
					}

					this.$container.append('<div class="body">'+error+'</div>');
				}

				// Include Stripe.js
				$('<script type="text/javascript" src="https://js.stripe.com/v1/"></script>').appendTo(Garnish.$bod);
			}
		}, this));
	},

	initializeCheckoutForm: function()
	{
		this.$ccNumInput.payment('formatCardNumber');
		this.$ccExpInput.payment('formatCardExpiry');
		this.$ccCvcInput.payment('formatCardCVC');

		this.$businessFieldsToggle.fieldtoggle();

		this.$businessCountryInput.selectize({ valueField: 'iso', labelField: 'name', searchField: ['name', 'iso'], dropdownParent: 'body', inputClass: 'selectize-input text' });
		this.$businessCountryInput[0].selectize.addOption(this.countries);
		this.$businessCountryInput[0].selectize.refreshOptions(false);

		this.$businessStateInput.selectize({ valueField: 'abbr', labelField: 'name', searchField: ['name', 'abbr'], dropdownParent: 'body', inputClass: 'selectize-input text', create: true });
		this.$businessStateInput[0].selectize.addOption(this.states);
		this.$businessStateInput[0].selectize.refreshOptions(false);

		this.addListener(this.$couponInput, 'textchange', {delay: 500}, 'applyCoupon');
		this.addListener(this.$checkoutForm, 'submit', 'submitPurchase');
	},

	applyCoupon: function()
	{
		if (this.applyingCouponCode)
		{
			this.applyNewCouponCodeAfterDoneLoading = true;
			return;
		}

		var couponCode = this.$couponInput.val();

		if (couponCode)
		{
			var data = {
				edition: this.edition,
				couponCode: couponCode
			};

			this.applyingCouponCode = true;
			this.$couponSpinner.removeClass('hidden');

			Craft.postActionRequest('app/getCouponPrice', data, $.proxy(function(response, textStatus)
			{
				this.applyingCouponCode = false;

				// Are we just waiting to apply a new code?
				if (this.applyNewCouponCodeAfterDoneLoading)
				{
					this.applyNewCouponCodeAfterDoneLoading = false;
					this.applyCoupon();
				}
				else
				{
					this.$couponSpinner.addClass('hidden');

					if (textStatus == 'success' && response.success)
					{
						this.couponPrice = response.couponPrice;
						this.formattedCouponPrice = response.formattedCouponPrice;
						this.updateCheckoutUi();
					}
				}
			}, this));
		}
		else
		{
			// Clear out the coupon price
			this.couponPrice = null;
			this.updateCheckoutUi();
		}
	},

	onHide: function()
	{
		if (this.initializedCheckoutForm)
		{
			this.$businessCountryInput[0].selectize.blur();
			this.$businessStateInput[0].selectize.blur();
		}

		this.clearCheckoutFormInABit();
		this.base();
	},

	onBuyBtnClick: function(ev)
	{
		var $btn = $(ev.currentTarget);
		this.edition = $btn.data('edition');
		this.couponPrice = null;
		this.formattedCouponPrice = null;

		switch (this.edition)
		{
			case 1:
			{
				this.$checkoutLogo.attr('class', 'logo craftclient').text('Client');
				break;
			}
			case 2:
			{
				this.$checkoutLogo.attr('class', 'logo craftpro').text('Pro');
				break;
			}
		}

		this.updateCheckoutUi();

		if (this.clearCheckoutFormTimeout)
		{
			clearTimeout(this.clearCheckoutFormTimeout);
		}

		// Slide it in

		var width = this.getWidth();

		this.$compareScreen.velocity('stop').animateLeft(-width, 'fast', $.proxy(function()
		{
			this.$compareScreen.addClass('hidden');

			if (!this.initializedCheckoutForm)
			{
				this.initializeCheckoutForm();
				this.initializedCheckoutForm = true;
			}
		}, this));

		this.$checkoutScreen.velocity('stop').css(Craft.left, width).removeClass('hidden').animateLeft(0, 'fast');
	},

	updateCheckoutUi: function()
	{
		// Only show the CC fields if there is a price
		if (this.getPrice() == 0)
		{
			this.$ccField.hide();
		}
		else
		{
			this.$ccField.show();
		}

		// Update the Pay button
		this.$checkoutSubmitBtn.val(Craft.t('Pay {price}', {
			price: this.getFormattedPrice()
		}));
	},

	getPrice: function()
	{
		if (this.couponPrice !== null)
		{
			return this.couponPrice;
		}

		if (this.editions[this.edition].salePrice)
		{
			return this.editions[this.edition].salePrice;
		}

		return this.editions[this.edition].price;
	},

	getFormattedPrice: function()
	{
		if (this.couponPrice !== null)
		{
			return this.formattedCouponPrice;
		}

		if (this.editions[this.edition].salePrice)
		{
			return this.editions[this.edition].formattedSalePrice;
		}

		return this.editions[this.edition].formattedPrice;
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

				this.$compareScreen.velocity('stop').animateLeft(-width, 'fast', $.proxy(function()
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

		this.$compareScreen.velocity('stop').removeClass('hidden').animateLeft(0, 'fast');
		this.$checkoutScreen.velocity('stop').animateLeft(width, 'fast', $.proxy(function()
		{
			this.$checkoutScreen.addClass('hidden');
		}, this));

		this.clearCheckoutFormInABit();
	},

	getExpiryValues: function()
	{
		return this.$ccExpInput.payment('cardExpiryVal');
	},

	submitPurchase: function(ev)
	{
		ev.preventDefault();

		if (this.submittingPurchase)
		{
			return;
		}

		this.cleanupCheckoutForm();

		// Get the price
		var price = this.getPrice();

		// Get the CC data
		var expVal = this.getExpiryValues();
		var ccData = {
			name:      this.$customerNameInput.val(),
			number:    this.$ccNumInput.val(),
			exp_month: expVal.month,
			exp_year:  expVal.year,
			cvc:       this.$ccCvcInput.val()
		};

		// Validate it
		var validates = true;

		if (!ccData.name)
		{
			validates = false;
			this.$customerNameInput.addClass('error');
		}

		if (price != 0)
		{
			if (!Stripe.validateCardNumber(ccData.number))
			{
				validates = false;
				this.$ccNumInput.addClass('error');
			}

			if (!Stripe.validateExpiry(ccData.exp_month, ccData.exp_year))
			{
				validates = false;
				this.$ccExpInput.addClass('error');
			}

			if (!Stripe.validateCVC(ccData.cvc))
			{
				validates = false;
				this.$ccCvcInput.addClass('error');
			}
		}

		if (validates)
		{
			this.submittingPurchase = true;

			// Get a CC token from Stripe.js
			this.$checkoutSubmitBtn.addClass('active');
			this.$checkoutSpinner.removeClass('hidden');

			if (price != 0)
			{
				Stripe.setPublishableKey(this.stripePublicKey);
				Stripe.createToken(ccData, $.proxy(function(status, response)
				{
					if (!response.error)
					{
						this.sendPurchaseRequest(price, response.id);
					}
					else
					{
						this.onPurchaseResponse();
						this.showError(response.error.message);
						Garnish.shake(this.$checkoutForm, 'left');
					}
				}, this));
			}
			else
			{
				this.sendPurchaseRequest(0, null);
			}
		}
		else
		{
			Garnish.shake(this.$checkoutForm, 'left');
		}
	},

	sendPurchaseRequest: function(expectedPrice, ccTokenId)
	{
		// Pass the token along to Elliott to charge the card
		var expVal = expectedPrice != 0 ? this.getExpiryValues() : {month: null, year: null};

		var data = {
			ccTokenId:            ccTokenId,
			expMonth:             expVal.month,
			expYear:              expVal.year,
			edition:              this.edition,
			expectedPrice:        expectedPrice,
			name:                 this.$customerNameInput.val(),
			email:                this.$customerEmailInput.val(),
			businessName:         this.$businessNameInput.val(),
			businessAddress1:     this.$businessAddress1Input.val(),
			businessAddress2:     this.$businessAddress2Input.val(),
			businessCity:         this.$businessCityInput.val(),
			businessState:        this.$businessStateInput.val(),
			businessCountry:      this.$businessCountryInput.val(),
			businessZip:          this.$businessZipInput.val(),
			businessTaxId:        this.$businessTaxIdInput.val(),
			purchaseNotes:        this.$purchaseNotesInput.val(),
			couponCode:           this.$couponInput.val()
		};

		Craft.postActionRequest('app/purchaseUpgrade', data, $.proxy(this, 'onPurchaseUpgrade'));
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

				this.$checkoutScreen.velocity('stop').animateLeft(-width, 'fast', $.proxy(function()
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

					this.showError(errorText);
				}
				else
				{
					var errorText = Craft.t('An unknown error occurred.');
				}

				Garnish.shake(this.$checkoutForm, 'left');
			}
		}
	},

	showError: function(error)
	{
		this.$checkoutFormError = $('<p class="error centeralign">'+error+'</p>').insertBefore(this.$checkoutSecure);
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
		this.$customerNameInput.val('');
		this.$customerEmailInput.val('');
		this.$ccNumInput.val('');
		this.$ccExpInput.val('');
		this.$ccCvcInput.val('');
		this.$businessNameInput.val('');
		this.$businessAddress1Input.val('');
		this.$businessAddress2Input.val('');
		this.$businessCityInput.val('');
		this.$businessStateInput.val('');
		this.$businessCountryInput.val('');
		this.$businessZipInput.val('');
		this.$businessTaxIdInput.val('');
		this.$purchaseNotesInput.val('');
		this.$couponInput.val('');
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
	clearCheckoutFormTimeoutDuration: 30000 // 1000 x 60 x 5
});
