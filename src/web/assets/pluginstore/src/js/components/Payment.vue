<template>
	<div>
		<div v-if="!craftIdDataLoading">
			<div class="block">
				<h2>Identity</h2>
				<a class="block-toggle" v-if="!(activeSection=='identity')" @click="activeSection = 'identity'">Edit</a>
				<a class="block-toggle" v-else @click="saveIdentity()">Done</a>

				<form v-if="activeSection=='identity'" @submit.prevent="saveIdentity()">
					<p><label><input type="radio" value="craftid" v-model="identityMode" /> Use your Craft ID</label></p>

					<template v-if="identityMode == 'craftid'">
						<template v-if="craftIdAccount">
							<ul>
								<li>{{ craftIdAccount.name }}</li>
								<li>{{ craftIdAccount.email }}</li>
							</ul>
							<p><input type="submit" class="btn submit" value="Continue"></p>
						</template>

						<p v-else><a class="btn submit" @click="connectCraftId">Connect to your Craft ID</a></p>
					</template>

					<p><label><input type="radio" value="guest" v-model="identityMode" /> Continue as guest</label></p>

					<template v-if="identityMode == 'guest'">
						<text-field id="fullName" placeholder="Full Name" v-model="guestIdentity.fullName" :errors="guestIdentityErrors.fullName"></text-field>
						<text-field id="email" placeholder="Email" v-model="guestIdentity.email" :errors="guestIdentityErrors.email"></text-field>
						<input type="submit" class="btn submit" value="Continue" />
					</template>
				</form>
				<template v-else>
					<div v-if="identityMode == 'craftid'">
						<ul v-if="craftIdAccount">
							<li>{{ craftIdAccount.name }} <em>(Craft ID)</em></li>
							<li>{{ craftIdAccount.email }}</li>
						</ul>
						<p v-else class="light">Not connected to Craft ID.</p>
					</div>
					<div v-if="identityMode == 'guest'">
						<template v-if="guestIdentity.fullName && guestIdentity.email">
							<ul>
								<li>{{ guestIdentity.fullName }} <em>(Guest)</em></li>
								<li>{{ guestIdentity.email }}</li>
							</ul>
						</template>

						<p v-else class="light">Missing informations.</p>
					</div>
				</template>
			</div>

			<hr>

			<div class="block">
				<h2>Payment Method</h2>
				<a class="block-toggle" v-if="!(activeSection=='paymentMethod')" @click="activeSection = 'paymentMethod'">Edit</a>
				<a class="block-toggle" v-else @click="savePaymentMethod()">Done</a>

				<form v-if="activeSection=='paymentMethod'" @submit.prevent="savePaymentMethod()">
					<template v-if="identityMode == 'craftid'">
						<p v-if="craftIdAccount && craftIdAccount.card"><label><input type="radio" value="existingCard" v-model="paymentMode" /> Use card <span>{{ craftIdAccount.card.brand }} •••• •••• •••• {{ craftIdAccount.card.last4 }} — {{ craftIdAccount.card.exp_month }}/{{ craftIdAccount.card.exp_year }}</span></label></p>
						<p><label><input type="radio" value="newCard" v-model="paymentMode" /> Use a new credit card</label></p>

						<template v-if="paymentMode == 'newCard'">
							<card-form v-if="!cardToken" ref="newCard" @save="onCardFormSave" @error="onCardFormError"></card-form>
							<p v-else>{{ cardToken.card.brand }} •••• •••• •••• {{ cardToken.card.last4 }} ({{ cardToken.card.exp_month }}/{{ cardToken.card.exp_year }}) <a class="delete icon" @click="cardToken = null"></a></p>
							<checkbox-field id="replaceCard" v-model="replaceCard" label="Save as my new credit card" />
						</template>
					</template>

					<card-form v-else ref="guestCard" @save="onGuestCardFormSave" @error="onGuestCardFormError"></card-form>
					<input type="submit" class="btn submit" value="Continue" />
					<div v-if="paymentMethodLoading" class="spinner"></div>
				</form>

				<template v-else>
					<template v-if="identityMode == 'craftid'">
						<template v-if="craftIdAccount">
							<p v-if="paymentMode == 'existingCard' && craftIdAccount.card">
								{{ craftIdAccount.card.brand }}
								•••• •••• •••• {{ craftIdAccount.card.last4 }}
								({{ craftIdAccount.card.exp_month }}/{{ craftIdAccount.card.exp_year }})
							</p>

							<p v-if="paymentMode == 'newCard' && cardToken && cardToken.card">
								{{ cardToken.card.brand }}
								•••• •••• •••• {{ cardToken.card.last4 }}
								({{ cardToken.card.exp_month }}/{{ cardToken.card.exp_year }})
							</p>
							<p v-if="replaceCard" class="light">Will be saved as your new default card.</p>
						</template>

						<p v-else class="light">Not defined.</p>
					</template>

					<p v-else-if="guestCardToken && guestCardToken.card">
						{{ guestCardToken.card.brand }}
						•••• •••• •••• {{ guestCardToken.card.last4 }}
						({{ guestCardToken.card.exp_month }}/{{ guestCardToken.card.exp_year }})
					</p>
				</template>
			</div>

			<hr>

			<div class="block">
				<h2>Billing</h2>
				<a class="block-toggle" v-if="!(activeSection=='billing')" @click="activeSection = 'billing'">Edit</a>
				<a class="block-toggle" v-else @click="activeSection=null">Done</a>

				<form v-if="activeSection=='billing'" @submit.prevent="saveBilling()">
					<div class="field">
						<div class="input">
							<div class="multitext">
								<div class="multitextrow">
									<text-input placeholder="Business Name" id="business-name" v-model="billing.businessName" />
								</div>
								<div class="multitextrow">
									<text-input placeholder="Business Tax ID" id="business-tax-id" v-model="billing.businessTaxId" />
								</div>
							</div>
						</div>
					</div>

					<div class="field">
						<div class="input">
							<div class="multitext">
								<div class="multitextrow">
									<text-input placeholder="Address Line 1" id="address-line-1" v-model="billing.businessAddressLine1" />
								</div>
								<div class="multitextrow">
									<text-input placeholder="Address Line 2" id="address-line-2" v-model="billing.businessAddressLine2" />
								</div>
								<div class="multitextrow">
									<div class="text">
										<select-input v-model="billing.businessCountry" :options="countryOptions" />
									</div>
									<div class="text">
										<select-input v-model="billing.businessState" :options="stateOptions" />
									</div>
								</div>
								<div class="multitextrow">
									<text-input placeholder="City" id="businessCity" v-model="billing.businessCity" />
									<text-input placeholder="Zip Code" id="zip-code" v-model="billing.businessZipCode" />
								</div>
							</div>
						</div>
					</div>

					<checkbox-field id="replaceBillingInfos" v-model="replaceBillingInfos" label="Save as my new billing informations" />
					<textarea-field placeholder="Notes" id="businessNotes" v-model="billing.businessNotes"></textarea-field>
					<input type="submit" class="btn submit" value="Continue" />
				</form>
				<template v-else>
					<ul>
						<li>{{ billing.businessName }}</li>
						<li>{{ billing.businessTaxId }}</li>
						<li>{{ billing.businessAddressLine1 }}</li>
						<li>{{ billing.businessAddressLine2 }}</li>
						<li><span v-if="billing.businessCity">{{ billing.businessCity }}, </span>{{ billing.businessState }} {{ billing.businessZipCode }}</li>
						<li>{{ billing.businessCountry }}</li>
						<li>{{ billing.businessNotes }}</li>
					</ul>
				</template>
			</div>

			<hr>

			<p v-if="error" class="error">{{ error }}</p>

			<div class="buttons">
				<a class="btn submit" :class="{ disabled: !readyToPay }" @click="checkout()">Pay {{ cartTotal() | currency }}</a>
				<div v-if="loading" class="spinner"></div>
			</div>

			<p>Your payment is safe and secure with Stripe.</p>
		</div>

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
    import {mapGetters, mapActions} from 'vuex'

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
                activeSection: 'identity',

                identityMode: 'craftid',
                guestIdentity: {
                    fullName: "",
                    email: "",
                },

                guestIdentityErrors: {
                    fullName: false,
					email: false,
				},

                paymentMode: 'existingCard',
				cardToken: null,
                guestCardToken: null,
				replaceCard: false,
				paymentMethodLoading: false,

                guestBilling: {
                    businessName: '',
                    businessTaxId: '',
                    businessAddressLine1: '',
                    businessAddressLine2: '',
                    businessCountry: '',
                    businessState: '',
                    businessCity: '',
                    businessZipCode: '',
                    businessNotes: '',
                },
				replaceBillingInfos: false,
            }
        },

        computed: {

            ...mapGetters({
                cartItems: 'cartItems',
                cartTotal: 'cartTotal',
                craftIdAccount: 'craftIdAccount',
                countries: 'countries',
                states: 'states',
            }),

			readyToPay() {
                if(!this.activeSection && this.sectionValidates('identity') && this.sectionValidates('paymentMethod')) {
                    return true;
				}

				return false;
			},

			billing() {
                if(this.identityMode === 'craftid' && this.craftIdAccount) {
					return {
                        businessName: this.craftIdAccount.businessName,
                        businessTaxId: this.craftIdAccount.businessTaxId,
                        businessAddressLine1: this.craftIdAccount.businessAddressLine1,
                        businessAddressLine2: this.craftIdAccount.businessAddressLine2,
                        businessCountry: this.craftIdAccount.businessCountry,
                        businessState: this.craftIdAccount.businessState,
                        businessCity: this.craftIdAccount.businessCity,
                        businessZipCode: this.craftIdAccount.businessZipCode,
                        businessNotes: this.craftIdAccount.businessNotes,
					}
				}
                return this.guestBilling;
			},

			countryOptions() {
                let options = [];

                this.countries.forEach(country => {
                    options.push({
						label: country.name,
						value: country.iso,
					});
				})

				return options;
			},

			stateOptions() {
                let options = [];

                this.states.forEach(state => {
                    options.push({
						label: state.name,
						value: state.abbr,
					});
				})

				return options;
			},

			craftIdDataLoading() {
                return this.$root.craftIdDataLoading;
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

            checkout() {
              	if(this.readyToPay) {
              	    this.loading = true;

              	    let craftId = null;
              	    let identity = null;
                    let cardToken = null;

                    switch(this.identityMode) {
                        case 'craftid':
                            craftId = this.craftIdAccount.id;

                            switch(this.paymentMode) {
								case 'newCard':
								    cardToken = this.cardToken.id;
								    break;
							}
                            break;
                        case 'guest':
                            identity = {
                                fullName: this.guestIdentity.fullName,
                                email: this.guestIdentity.email,
                            };
                            cardToken = this.guestCardToken.id;
                            break;
                    }

              	    let order = {
              	        craftId: craftId,
              	        identity: identity,
						cardToken: cardToken,
						replaceCard: (this.replaceCard ? 1 : 0),
						billingInfos: this.billing,
						replaceBillingInfos: (this.replaceBillingInfos ? 1 : 0),
						cartItems: this.cartItems,
					};

                    this.$store.dispatch('checkout', order)
						.then(response => {
							this.loading = false;
                            this.error = false;
							this.$root.lastOrder = order;
							this.$root.modalStep = 'thankYou';

							this.$store.dispatch('resetCart');
						})
						.catch(response => {
						    this.loading = false;
						    this.error = response.statusText;
						});
				}
			},

            saveIdentity() {
				switch(this.identityMode) {
					case 'craftid':
					    if(this.craftIdAccount) {
                            this.activeSection = 'paymentMethod';
						}
					    break;
					case 'guest':
                        if(!this.guestIdentity.fullName) {
                            this.guestIdentityErrors.fullName = true;
                        } else {
                            this.guestIdentityErrors.fullName = false;
                        }

                        if(!this.guestIdentity.email) {
                            this.guestIdentityErrors.email = true;
                        } else {
                            this.guestIdentityErrors.email = false;
                        }

                        let validates = true;

                        for(let key in this.guestIdentityErrors) {
                            if(!this.guestIdentityErrors.hasOwnProperty(key)) continue;

                            if(this.guestIdentityErrors[key] === true) {
                                validates = false;
                            }
                        }

                        if(validates) {
                            this.activeSection = 'paymentMethod';
                        }
					    break;
				}
			},

            savePaymentMethod() {
				switch(this.identityMode) {
                    case 'craftid':
                        if(this.paymentMode === 'newCard') {
                            // Save new card
                            if(!this.cardToken) {
                                this.paymentMethodLoading = true;
                                this.$refs.newCard.save();
                            } else {
                                this.activeSection = null;
                            }
						} else {
                            this.activeSection = null;
                        }
                        break;

                    case 'guest':
                        // Save guest card
                        if(!this.guestCardToken) {
                            this.paymentMethodLoading = true;
                            this.$refs.guestCard.save();
                        } else {
                            this.activeSection = null;
                        }
                        break;
                }
			},

			saveBilling() {
              	this.activeSection = null;
			},

            onCardFormSave(card, token) {
                this.activeSection = null;
				this.cardToken = token;
                this.paymentMethodLoading = false;
			},

            onCardFormError(error) {
              	this.paymentMethodLoading = false;
			},

            onGuestCardFormSave(card, token) {
                this.activeSection = null;
				this.guestCardToken = token;
                this.paymentMethodLoading = false;
			},

            onGuestCardFormError(error) {
                this.paymentMethodLoading = false;
			},

            sectionValidates(section) {
				switch(section) {
					case 'identity':
						switch(this.identityMode) {
							case 'craftid':
							    if(this.craftIdAccount) {
							        return true;
								}
							    break;
							case 'guest':
							    if(this.guestIdentity.fullName && this.guestIdentity.email) {
							        return true;
								}
							    break;
						}
					    break;
					case 'paymentMethod':
					    switch(this.identityMode) {
							case 'craftid':
								switch(this.paymentMode) {
									case 'existingCard':
										if(this.craftIdAccount.card) {
											return true;
										}
										break;
									case 'newCard':
										if(this.cardToken) {
											return true;
										}
										break;
								}
								break;

							case 'guest':
							    if(this.guestCardToken) {
							        return true;
								}
							    break;
                        }
					    break;
				}

				return false;
			},

            isSectionActive(section) {
                if(this.activeSection == section) {
					return true;
				}

				return false;
			},

            connectCraftId() {
                let width = 800;
                let height = 600;

                let winWidth = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
                let winHeight = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

                let left = ((winWidth / 2) - (width / 2));
                let top = ((winHeight / 2) - (height / 2));

                let url = Craft.getActionUrl('plugin-store/connect', {redirect: Craft.getActionUrl('plugin-store/modal-callback') });
                let name = 'ConnectWithOauth';
                let specs = 'location=0,status=0,width=' + width + ',height=' + height + ',left=' + left + ',top=' + top;

                window.open(url, name, specs);
            },

		},

    }
</script>
