<template>

	<div>
		<div class="block">
			<h2>Identity</h2>

			<a class="block-toggle" v-if="!(activeSection=='identity')" @click="activeSection = 'identity'">Edit</a>
			<a class="block-toggle" v-else @click="activeSection=null">Done</a>

			<template v-if="activeSection=='identity'">
				<p><label><input type="radio" value="craftid" v-model="identityMode" /> Use your Craft ID</label></p>

				<template v-if="identityMode == 'craftid'">
					<template v-if="craftIdAccount">
						<ul>
							<li>{{ craftIdAccount.name }}</li>
							<li>{{ craftIdAccount.email }}</li>
						</ul>
						<p><a class="btn submit" @click="activeSection = 'paymentMethod'">Continue</a></p>
					</template>

					<template v-else>
						<p><a class="btn submit" @click="connectCraftId">Connect to your Craft ID</a></p>
					</template>
				</template>

				<p><label><input type="radio" value="guest" v-model="identityMode" /> Continue as guest</label></p>

				<template v-if="identityMode == 'guest'">
					<text-field id="fullName" placeholder="Full Name" v-model="guestIdentity.fullName"></text-field>
					<text-field id="email" placeholder="Email" v-model="guestIdentity.email"></text-field>

					<a class="btn submit" @click="activeSection = 'paymentMethod'">Continue</a>
				</template>
			</template>
			<template v-else>
				<div v-if="identityMode == 'craftid'">
					<ul v-if="craftIdAccount">
						<li>{{ craftIdAccount.name }} <em>(Craft ID)</em></li>
						<li>{{ craftIdAccount.email }}</li>
					</ul>
					<p v-else class="light">Not connected to Craft ID.</p>
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

			<a class="block-toggle" v-if="!(activeSection=='paymentMethod')" @click="activeSection = 'paymentMethod'">Edit</a>
			<a class="block-toggle" v-else @click="activeSection=null">Done</a>

			<template v-if="activeSection=='paymentMethod'">
				<template v-if="identityMode == 'craftid'">
					<p><label><input type="radio" value="existingCard" v-model="paymentMode" /> Use card <span v-if="craftIdAccount">{{ craftIdAccount.card.brand }} •••• •••• •••• {{ craftIdAccount.card.last4 }} — {{ craftIdAccount.card.exp_month }}/{{ craftIdAccount.card.exp_year }}</span></label></p>
					<p><label><input type="radio" value="newCard" v-model="paymentMode" /> Or use a different credit card</label></p>

					<template v-if="paymentMode == 'newCard'">
						<credit-card v-model="creditCard"></credit-card>
						<checkbox-field id="saveCreditCard" label="Save as my new credit card" />
					</template>
				</template>
				<template v-else>
					<credit-card v-model="creditCard"></credit-card>
				</template>

				<a class="btn submit" @click="activeSection=null">Continue</a>
			</template>
			<template v-else>

				<template v-if="identityMode == 'craftid'">
					<template v-if="craftIdAccount">

						<ul v-if="paymentMode == 'existingCard'">
							<li>{{ craftIdAccount.cardNumber }}</li>
							<li>{{ craftIdAccount.cardExpiry }}</li>
							<li>{{ craftIdAccount.cardCvc }}</li>
						</ul>

					</template>

					<p v-else class="light">Not defined.</p>
				</template>

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

			<a class="block-toggle" v-if="!(activeSection=='billing')"
			   @click="activeSection = 'billing'">Edit</a>
			<a class="block-toggle" v-else @click="activeSection=null">Done</a>

			<template v-if="activeSection=='billing'">
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

				<checkbox-field id="saveBillingInfos" label="Save as my new billing informations" />

				<textarea-field placeholder="Notes" id="businessNotes" v-model="billing.businessNotes"></textarea-field>

				<a class="btn submit" @click="activeSection=null">Continue</a>
			</template>
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

		<div class="buttons">
			<a class="btn submit" :class="{ disabled: !readyToPay }">Pay {{ cartTotal() | currency }}</a>
		</div>

		<p>Your payment is safe and secure with Stripe.</p>
	</div>

</template>

<script>
    import CheckboxField from './fields/CheckboxField';
    import TextareaField from './fields/TextareaField';
    import TextField from './fields/TextField';
    import TextInput from './inputs/TextInput';
    import SelectInput from './inputs/SelectInput';
    import CreditCard from './CreditCard';
    import {mapGetters, mapActions} from 'vuex'

    export default {
        components: {
            CheckboxField,
            TextareaField,
            TextField,
            TextInput,
            CreditCard,
            SelectInput,
        },

        data() {
            return {
                activeSection: 'identity',

                identityMode: 'craftid',
                guestIdentity: {
                    fullName: "",
                    email: "",
                },

                paymentMode: 'existingCard',
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
                    businessNotes: '',
                }
            }
        },

        computed: {
            ...mapGetters({
                cartTotal: 'cartTotal',
                craftIdAccount: 'craftIdAccount',
                countries: 'countries',
                states: 'states',
            }),

			readyToPay() {
                if(!this.activeSection && this.sectionValidates('identity')) {
                    return true;
				}

				return false;
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

		methods: {

            sectionValidates(section)
			{
				switch(section) {
					case 'identity':
						switch(this.identityMode) {
							case 'craftid':
							    if(this.craftIdAccount) {
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

            closeSection(section) {
                this.activeSection = null;
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
