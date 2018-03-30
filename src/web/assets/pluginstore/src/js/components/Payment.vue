<template>
	<div>
		<form v-if="!$root.craftIdDataLoading" @submit.prevent="checkout()" class="payment">
			<div class="blocks">
				<div class="block">
					<h2>Payment Method</h2>

					<template v-if="craftIdAccount">
						<p v-if="craftIdAccount && craftIdAccount.card"><label><input type="radio" value="existingCard" v-model="paymentMode" /> Use card <span>{{ craftIdAccount.card.brand }} •••• •••• •••• {{ craftIdAccount.card.last4 }} — {{ craftIdAccount.card.exp_month }}/{{ craftIdAccount.card.exp_year }}</span></label></p>
						<p><label><input type="radio" value="newCard" v-model="paymentMode" /> Use a new credit card</label></p>

						<template v-if="paymentMode === 'newCard'">
							<card-form v-if="!cardToken" ref="newCard" @save="onCardFormSave" @error="onCardFormError"></card-form>
							<p v-else>{{ cardToken.card.brand }} •••• •••• •••• {{ cardToken.card.last4 }} ({{ cardToken.card.exp_month }}/{{ cardToken.card.exp_year }}) <a class="delete icon" @click="cardToken = null"></a></p>
							<checkbox-field id="replaceCard" v-model="replaceCard" label="Save as my new credit card" />
						</template>
					</template>

					<template v-else>
						<card-form ref="guestCard" @save="onGuestCardFormSave" @error="onGuestCardFormError"></card-form>
					</template>

					<h2>Coupon Code</h2>
					<text-field placeholder="XXXXXXX" id="coupon-code" v-model="couponCode" size="9" />
				</div>

				<div class="block">
					<h2>Billing</h2>

					<div class="field">
						<div class="input">
							<div class="multitext">
								<div class="multitextrow">
									<text-input placeholder="First Name" id="first-name" v-model="billingInfo.firstName" />
								</div>
								<div class="multitextrow">
									<text-input placeholder="Last Name" id="last-name" v-model="billingInfo.lastName" :error="billingInfoErrors.lastName" />
								</div>
							</div>
						</div>
					</div>

					<div class="field">
						<div class="input">
							<div class="multitext">
								<div class="multitextrow">
									<text-input placeholder="Business Name" id="business-name" v-model="billingInfo.businessName" />
								</div>
								<div class="multitextrow">
									<text-input placeholder="Business Tax ID" id="business-tax-id" v-model="billingInfo.businessTaxId" :error="billingInfoErrors.businessTaxId" />
								</div>
							</div>
						</div>
					</div>

					<div class="field">
						<div class="input">
							<div class="multitext">
								<div class="multitextrow">
									<text-input placeholder="Address Line 1" id="address-1" v-model="billingInfo.address1" />
								</div>
								<div class="multitextrow">
									<text-input placeholder="Address Line 2" id="address-2" v-model="billingInfo.address2" />
								</div>
								<div class="multitextrow">
									<select-input v-model="billingInfo.country" :options="countryOptions" @input="onCountryChange" />
								</div>
								<div class="multitextrow">
									<select-input v-model="billingInfo.state" :options="stateOptions" />
								</div>
								<div class="multitextrow">
									<text-input placeholder="City" id="city" v-model="billingInfo.city" />
									<text-input placeholder="Zip Code" id="zip-code" v-model="billingInfo.zipCode" />
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<hr>

			<div class="centeralign">
				<p v-if="error" class="error">{{ error }}</p>

				<input type="submit" class="btn submit" :value="'Pay ' + $options.filters.currency(cartTotal)" />
				<div v-if="loading" class="spinner"></div>

				<p>
					<img :src="craftData.poweredByStripe" height="18" />
				</p>
			</div>
		</form>

		<div v-else class="spinner"></div>
	</div>
</template>

<script>
    import CheckboxField from './fields/CheckboxField';
    import TextareaField from './fields/TextareaField';
    import TextField from './fields/TextField';
    import TextInput from './inputs/TextInput';
    import SelectInput from './inputs/SelectInput';
    import CreditCard from './CreditCard';
    import CardForm from './CardForm';
    import {mapGetters} from 'vuex'

    export default {
        components: {
            CheckboxField,
            TextareaField,
            TextField,
            TextInput,
            CreditCard,
            CardForm,
            SelectInput,
        },

        data() {
            return {
                error: false,
                loading: false,
                paymentMode: 'existingCard',
                cardToken: null,
                guestCardToken: null,
                replaceCard: false,
				couponCode: '',

				billingInfo: {
                    firstName: '',
                    lastName: '',
                    businessName: '',
                    businessTaxId: '',
                    address1: '',
                    address2: '',
                    country: '',
                    state: '',
                    city: '',
                    zipCode: '',
				},

                billingInfoErrors: {
                    businessTaxId: false,
                },

				stateOptions: [],
            }
        },

        computed: {

            ...mapGetters({
                cartTotal: 'cartTotal',
                craftIdAccount: 'craftIdAccount',
                countries: 'countries',
                states: 'states',
                cart: 'cart',
                craftData: 'craftData',
            }),

			countryOptions() {
                let options = [];

                for (let iso in this.countries) {
                    if (this.countries.hasOwnProperty(iso)) {
                        options.push({
							label: this.countries[iso].name,
							value: iso,
						})
                    }
                }

				return options;
			},

            billingCountryName() {
                const iso = this.billingInfo.country

				if (!iso) {
                    return
				}

                if(!this.countries[iso]) {
                    return
                }

                return this.countries[iso].name
			}
        },

        watch: {

            craftIdAccount(newVal) {
                if(!newVal.card) {
                    this.paymentMode = 'newCard';
                }

                return newVal;
            }

        },

		methods: {

            savePaymentMethod(cb, cbError) {
                if (this.craftIdAccount) {
                    if(this.paymentMode === 'newCard') {
                        // Save new card
                        if(!this.cardToken) {
                            this.$refs.newCard.save(() => {
                                cb();
							}, () => {
                                cbError();
							});
                        }
                    }
                } else {
                    // Save guest card
					this.$refs.guestCard.save(() => {
						cb();
					}, () => {
						cbError();
					});
                }
			},

			saveBillingInfo(cb, cbError) {
                let cartData = {
                    billingAddress: {
                        firstName: this.billingInfo.firstName,
                        lastName: this.billingInfo.lastName,
                        businessName: this.billingInfo.businessName,
                        businessTaxId: this.billingInfo.businessTaxId,
                        address1: this.billingInfo.address1,
                        address2: this.billingInfo.address2,
                        country: this.billingInfo.country,
                        state: this.billingInfo.state,
                        city: this.billingInfo.city,
                        zipCode: this.billingInfo.zipCode,
                    },
                }

                this.$store.dispatch('saveCart', cartData)
                    .then(response => {
						cb();
					})
                    .catch(response => {
                        cbError();
                    })
			},

            checkout() {
                this.loading = true

                this.savePaymentMethod(() => {
                    this.saveBillingInfo(() => {
                        // Ready to pay
                        let cardToken = null;

                        if (this.craftIdAccount) {
                            switch(this.paymentMode) {
                                case 'newCard':
                                    cardToken = this.cardToken.id;
                                    break;
                                default:
                                    cardToken = this.craftIdAccount.cardToken
                            }
                        } else {
                            cardToken = this.guestCardToken.id;
                        }

                        let checkoutData = {
                            craftId: !!this.craftIdAccount,
                            orderNumber: this.cart.number,
                            token: cardToken,
                            expectedPrice: this.cart.totalPrice,
                            makePrimary: this.replaceCard,
                        }

                        this.$store.dispatch('checkout', checkoutData)
                            .then(response => {
                                this.loading = false;
                                this.error = false;
                                // this.$root.lastOrder = order;
                                this.$root.modalStep = 'thankYou';
                                this.$store.dispatch('resetCart');

                                if(this.replaceCard) {
                                    this.$store.dispatch('getCraftData');
                                }
                            })
                            .catch(response => {
                                this.loading = false;
                                this.error = response.statusText;
                            });
					}, () => {
                        this.loading = false
                        this.$root.displayError("Couldn't save billing informations.");
                    });
                }, () => {
                    this.loading = false
                    this.$root.displayError("Couldn't save payment method.");
				});
			},

            onCardFormSave(card, token) {
				this.cardToken = token;
			},

            onCardFormError(error) {
			},

            onGuestCardFormSave(card, token) {
				this.guestCardToken = token;
			},

            onGuestCardFormError(error) {
			},

			onCountryChange(iso) {
				if (!this.countries[iso]) {
                    this.stateOptions = []
					return
				}

				const country = this.countries[iso]

				if(!country.states) {
                    this.stateOptions = []
					return
				}

				const states = country.states
				let options = []

				for (let iso in states) {
					if (states.hasOwnProperty(iso)) {
						options.push({
							label: states[iso],
							value: iso,
						})
					}
				}

				this.stateOptions = options
			},

		},

    }
</script>
