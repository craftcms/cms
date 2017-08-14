<template>

	<div>
		<div class="block">
			<h2>Identity</h2>

			<a class="block-toggle" v-if="!showIdentity" @click="showIdentity=true">Edit</a>
			<a class="block-toggle" v-else @click="showIdentity=false">Done</a>

			<template v-if="showIdentity">
				<p><label><input type="radio" value="craftid" v-model="identityMode" /> Use your Craft ID</label></p>

				<template v-if="identityMode == 'craftid'">
					<template v-if="craftIdAccount">
						<ul>
							<li>{{ craftIdAccount.name }}</li>
							<li>{{ craftIdAccount.email }}</li>
						</ul>
						<p><a class="btn submit" @click="showIdentity=false">Continue</a></p>
					</template>

					<template v-else>
						<p><a class="btn submit" :href="connectCraftIdUrl">Connect to your Craft ID</a></p>
					</template>
				</template>

				<p><label><input type="radio" value="guest" v-model="identityMode" /> Continue as guest</label></p>

				<template v-if="identityMode == 'guest'">
					<text-field id="fullName" placeholder="Full Name" v-model="guestIdentity.fullName"></text-field>
					<text-field id="email" placeholder="Email" v-model="guestIdentity.email"></text-field>

					<a class="btn submit" @click="showIdentity=false">Continue</a>
				</template>
			</template>
			<template v-else>
				<div v-if="identityMode == 'craftid'">
					<ul v-if="craftIdAccount">
						<li>{{ craftIdAccount.name }} <em>(Craft ID)</em></li>
						<li>{{ craftIdAccount.email }}</li>
					</ul>
				</div>
				<div v-if="identityMode == 'guest'">
					<ul>
						<li>{{ guestIdentity.fullName }} <em>(Guest)</em></li>
						<li>{{ guestIdentity.email }}</li>
					</ul>
				</div>
			</template>
		</div>

		<hr>

		<div class="block">
			<h2>Payment Method</h2>

			<a class="block-toggle" v-if="!showPaymentMethod" @click="showPaymentMethod=true">Edit</a>
			<a class="block-toggle" v-else @click="showPaymentMethod=false">Done</a>

			<template v-if="showPaymentMethod">
				<template v-if="identityMode == 'craftid'">
					<p><label><input type="radio" value="existingCard" v-model="paymentMode" /> Use card <span v-if="craftIdAccount">{{ craftIdAccount.cardNumber }}</span></label></p>
					<p><label><input type="radio" value="newCard" v-model="paymentMode" /> Or use a different credit card</label></p>

					<template v-if="paymentMode == 'newCard'">
						<credit-card v-model="creditCard"></credit-card>
					</template>
				</template>
				<template v-else>
					<credit-card v-model="creditCard"></credit-card>
				</template>

				<a class="btn submit" @click="showPaymentMethod=false">Continue</a>
			</template>
			<template v-else>
				<ul v-if="identityMode == 'craftid' && paymentMode == 'existingCard' && craftIdAccount">
					<li>{{ craftIdAccount.cardNumber }}</li>
					<li>{{ craftIdAccount.cardExpiry }}</li>
					<li>{{ craftIdAccount.cardCvc }}</li>
				</ul>

				<ul v-else>
					<li>{{ creditCard.number }}</li>
					<li>{{ creditCard.expiry }}</li>
					<li>{{ creditCard.cvc }}</li>
				</ul>
			</template>
		</div>

		<hr>

		<div class="block">
			<h2>Billing</h2>

			<a class="block-toggle" v-if="!showBilling"
			   @click="showBilling=true">Edit</a>
			<a class="block-toggle" v-else @click="showBilling=false">Done</a>

			<template v-if="showBilling">
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

				<a class="btn submit" @click="showBilling=false">Continue</a>
			</template>
			<template v-else>
				<ul>
					<li>{{ billing.businessName }}</li>
					<li>{{ billing.businessTaxId }}</li>
					<li>{{ billing.businessAddressLine1 }}</li>
					<li>{{ billing.businessAddressLine2 }}</li>
					<li><span v-if="billing.businessCity">{{ billing.businessCity }}, </span>{{ billing.businessState }} {{ billing.businessZipCode }}</li>
					<li>{{ billing.businessCountry }}</li>
				</ul>
			</template>
		</div>

		<hr>

		<div class="buttons">
			<a class="btn submit" :class="{ disabled: !readyToPay }">Pay {{ cartTotal() | currency }}</a>
		</div>

		<p>Your payment is safe and secure with Stripe.</p>
	</div>

</template>

<script>
    import TextField from './fields/TextField';
    import TextInput from './inputs/TextInput';
    import SelectInput from './inputs/SelectInput';
    import CreditCard from './CreditCard';
    import {mapGetters, mapActions} from 'vuex'

    export default {
        components: {
            TextField,
            TextInput,
            CreditCard,
            SelectInput,
        },

        computed: {
            ...mapGetters({
                cartTotal: 'cartTotal',
                craftIdAccount: 'craftIdAccount',
                countries: 'countries',
                states: 'states',
            }),
			readyToPay() {
                if(!this.showIdentity && !this.showPaymentMethod && !this.showBilling) {
                    return true;
				}

				return false;
			},
			csrfInput() {
                return '';
			},
            connectCraftIdUrl() {
                return Craft.getActionUrl('plugin-store/connect', {redirect: Craft.getActionUrl('plugin-store/modal-callback') });
			},
			billing() {
                if(this.identityMode == 'craftid' && this.craftIdAccount) {
                    return this.craftIdAccount;
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
			}
        },

        data() {
            return {
                identityMode: 'guest',
                paymentMode: 'existingCard',
                showIdentity: true,
                showPaymentMethod: false,
                showBilling: false,
                guestIdentity: {
                    fullName: "",
                    email: "",
                },
                creditCard: {
                    number: '',
                    expiry: '',
                    cvc: '',
                },
                guestBilling: {
                    businessName: '',
                    businessTaxId: '',
                    businessAddressLine1: '',
                    businessAddressLine2: '',
                    businessCountry: '',
                    businessState: '',
                    businessCity: '',
                    businessZipCode: '',
                }
            }
        }
    }
</script>
