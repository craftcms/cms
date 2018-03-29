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
						<text-field id="firstName" placeholder="First Name" v-model="guestIdentity.firstName" :errors="guestIdentityErrors.firstName"></text-field>
						<text-field id="lastName" placeholder="Last Name" v-model="guestIdentity.lastName" :errors="guestIdentityErrors.lastName"></text-field>
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
						<template v-if="guestIdentity.firstName && guestIdentity.lastName && guestIdentity.email">
							<ul>
								<li>{{ guestIdentity.firstName }} {{ guestIdentity.lastName }}<em>(Guest)</em></li>
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
									<text-input placeholder="Business Name" id="business-name" v-model="billingInfo.businessName" />
								</div>
								<div class="multitextrow">
									<text-input placeholder="Business Tax ID" id="business-tax-id" v-model="billingInfo.businessTaxId" :error="billingErrors.businessTaxId" />
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
									<div class="text">
										<select-input v-model="billingInfo.country" :options="countryOptions" @input="onCountryChange" />
									</div>
									<div class="text">
										<select-input v-model="billingInfo.state" :options="stateOptions" />
									</div>
								</div>
								<div class="multitextrow">
									<text-input placeholder="City" id="city" v-model="billingInfo.city" />
									<text-input placeholder="Zip Code" id="zip-code" v-model="billingInfo.zipCode" />
								</div>
							</div>
						</div>
					</div>

					<input type="submit" class="btn submit" value="Continue" />
				</form>
				<template v-else>
					<ul>
						<li v-if="billingInfo.businessName">{{ billingInfo.businessName }}</li>
						<li v-if="billingInfo.businessTaxId">{{ billingInfo.businessTaxId }}</li>
						<li v-if="billingInfo.address1">{{ billingInfo.address1 }}</li>
						<li v-if="billingInfo.address2">{{ billingInfo.address2 }}</li>
						<li v-if="billingInfo.city || billingInfo.state || billingInfo.zipCode"><span v-if="billingInfo.city">{{ billingInfo.city }}, </span>{{ billingInfo.state }} {{ billingInfo.zipCode }}</li>
						<li v-if="billingCountryName">{{ billingCountryName }}</li>
					</ul>
				</template>
			</div>

			<hr>

			<p v-if="error" class="error">{{ error }}</p>

			<div class="buttons">
				<a class="btn submit" :class="{ disabled: !readyToPay }" @click="checkout()">Pay {{ cartTotal | currency }}</a>
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
                activeSection: 'identity',

                identityMode: 'craftid',
                guestIdentity: {
                    firstName: "",
                    lastName: "",
                    email: "",
                },

                guestIdentityErrors: {
                    firstName: false,
                    lastName: false,
					email: false,
				},

                paymentMode: 'existingCard',
				cardToken: null,
                guestCardToken: null,
				replaceCard: false,
				paymentMethodLoading: false,

				billingInfo: {
                    businessName: '',
                    businessTaxId: '',
                    address1: '',
                    address2: '',
                    country: '',
                    state: '',
                    city: '',
                    zipCode: '',
				},

				stateOptions: [],

				billingErrors: {
                    businessTaxId: false,
				}
            }
        },

        computed: {

            ...mapGetters({
                cartTotal: 'cartTotal',
                craftIdAccount: 'craftIdAccount',
                countries: 'countries',
                states: 'states',
                cart: 'cart',
            }),

			readyToPay() {
                if(!this.activeSection
					&& this.sectionValidates('identity')
					&& this.sectionValidates('paymentMethod')
					&& this.sectionValidates('billing')
				) {
                    return true;
				}

				return false;
			},

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

			craftIdDataLoading() {
                return this.$root.craftIdDataLoading;
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

            checkout() {
            	if(this.readyToPay) {
            	    this.loading = true

                    let cardToken = null;

                    switch(this.identityMode) {
                        case 'craftid':
                            switch(this.paymentMode) {
                                case 'newCard':
                                    cardToken = this.cardToken.id;
                                    break;
								default:
								    cardToken = this.craftIdAccount.cardToken
                            }
                            break;
                        case 'guest':
                            cardToken = this.guestCardToken.id;
                            break;
                    }

					let data = {
						identityMode: this.identityMode,
						orderNumber: this.cart.number,
						token: cardToken,
						expectedPrice: this.cart.totalPrice,
						makePrimary: this.replaceCard,
					}

					this.$store.dispatch('checkout', data)
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
                        this.guestIdentityErrors.firstName = false;
                        this.guestIdentityErrors.lastName = false;
                        this.guestIdentityErrors.email = false;

                        if(!this.guestIdentity.firstName) {
                            this.guestIdentityErrors.firstName = true;
                        }

                        if(!this.guestIdentity.lastName) {
                            this.guestIdentityErrors.lastName = true;
                        }

                        if(!this.guestIdentity.email) {
                            this.guestIdentityErrors.email = true;
                        }

                        let validates = true;

                        for(let key in this.guestIdentityErrors) {
                            if(!this.guestIdentityErrors.hasOwnProperty(key)) continue;

                            if(this.guestIdentityErrors[key] === true) {
                                validates = false;
                            }
                        }

                        if(validates) {
                            let data = {
                                email: this.guestIdentity.email,
                                billingAddress: {
                                    firstName: this.guestIdentity.firstName,
                                    lastName: this.guestIdentity.lastName,
								},
							}
                            this.$store.dispatch('saveCart', data);
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
              	if(this.sectionValidates('billing')) {
                    let data = {
                        billingAddress: {
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

                    if(this.identityMode === 'craftid') {
						data.billingAddress.firstName = this.craftIdAccount.firstName
						data.billingAddress.lastName = this.craftIdAccount.lastName
                    } else if(this.identityMode === 'guest') {
                        data.billingAddress.firstName = this.guestIdentity.firstName
                        data.billingAddress.lastName = this.guestIdentity.lastName
					}

                    this.$store.dispatch('saveCart', data).then(() => {
                        this.activeSection = null
					})
				}
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
							    if(this.guestIdentity.firstName && this.guestIdentity.lastName && this.guestIdentity.email) {
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
										if(this.craftIdAccount && this.craftIdAccount.card) {
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

					case 'billing':
						this.billingErrors.businessTaxId = false
						this.billingErrors.state = false

						const iso = this.billingInfo.country

						if(!this.countries[iso]) {
							return true
						}

						const billingCountry = this.countries[iso]

						if (billingCountry.euMember && !this.billingInfo.businessTaxId) {
							this.billingErrors.businessTaxId = true
						}

						if (billingCountry.stateRequired && !this.billingInfo.state) {
							this.billingErrors.state = true
						}

						if (this.billingErrors.businessTaxId || this.billingErrors.state) {
							return false
						}
						
						return true
				}

				return false;
			},

            isSectionActive(section) {
                if(this.activeSection === section) {
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

                let url = Craft.getActionUrl('plugin-store/connect', {redirectUrl: Craft.getActionUrl('plugin-store/modal-callback') });
                let name = 'ConnectWithOauth';
                let specs = 'location=0,status=0,width=' + width + ',height=' + height + ',left=' + left + ',top=' + top;

                window.open(url, name, specs);
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

		created() {
            this.guestIdentity.firstName = this.cart.billingAddress.firstName;
            this.guestIdentity.lastName = this.cart.billingAddress.lastName;
            this.guestIdentity.email = this.cart.email;
		}

    }
</script>
