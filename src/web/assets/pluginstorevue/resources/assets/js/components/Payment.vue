<template>

	<div>
		<div class="block">
			<h2>Identity</h2>

			<a class="block-toggle" v-if="!showIdentity" @click="showIdentity=true">Edit</a>
			<a class="block-toggle" v-else @click="showIdentity=false">Done</a>

			<template v-if="showIdentity">
				<p><label><input type="radio" value="craftid" v-model="identityMode" /> Use your Craft ID</label></p>
				<p><label><input type="radio" value="guest" v-model="identityMode" /> Continue as guest</label></p>

				<template v-if="identityMode == 'craftid'">
					<p><a class="btn submit" @click="showIdentity=false">Connect to your Craft ID</a></p>
				</template>
				<template v-else-if="identityMode == 'guest'">
					<text-fieldtype label="Full Name" id="fullName" placeholder="John Smith" v-model="guestIdentity.fullName"></text-fieldtype>
					<text-fieldtype label="Email" id="email" placeholder="john@example.com" v-model="guestIdentity.email"></text-fieldtype>

					<a class="btn submit" @click="showIdentity=false">Continue</a>
				</template>
			</template>
			<template v-else>
				<div v-if="identityMode == 'craftid'">
					<ul>
						<li>{{ craftIdIdentity.fullName }} <em>(Craft ID)</em></li>
						<li>{{ craftIdIdentity.email }}</li>
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
					<p><label><input type="radio" value="existingCard" v-model="paymentMode" /> Use card {{ craftIdIdentity.creditCard.number }}</label></p>
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
				<ul>
					<template v-if="identityMode == 'craftid' && paymentMode == 'existingCard'">
						<li>{{ craftIdIdentity.creditCard.number }}</li>
						<li>{{ craftIdIdentity.creditCard.expiry }}</li>
						<li>{{ craftIdIdentity.creditCard.cvc }}</li>
					</template>
					<template v-else>
						<li>{{ creditCard.number }}</li>
						<li>{{ creditCard.expiry }}</li>
						<li>{{ creditCard.cvc }}</li>
					</template>
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
				<text-fieldtype label="Business Name" id="business-name" v-model="billing.businessName"></text-fieldtype>
				<text-fieldtype label="Business Tax ID" id="business-tax-id" v-model="billing.businessTaxId"></text-fieldtype>
				<text-fieldtype label="Address Line 1" id="address-line-1" v-model="billing.addressLine1"></text-fieldtype>
				<text-fieldtype label="Address Line 2" id="address-line-2" v-model="billing.addressLine2"></text-fieldtype>
				<text-fieldtype label="Country" id="country" v-model="billing.country"></text-fieldtype>
				<text-fieldtype label="State" id="state" v-model="billing.state"></text-fieldtype>
				<text-fieldtype label="City" id="city" v-model="billing.city"></text-fieldtype>
				<text-fieldtype label="Zip Code" id="zip-code" v-model="billing.zipCode"></text-fieldtype>

				<a class="btn submit" @click="showBilling=false">Continue</a>
			</template>
			<template v-else>
				<ul>
					<li>{{ billing.businessName }}</li>
					<li>{{ billing.businessTaxId }}</li>
					<li>{{ billing.addressLine1 }}</li>
					<li>{{ billing.addressLine2 }}</li>
					<li><span v-if="billing.city">{{ billing.city }}, </span>{{ billing.state }} {{ billing.zipCode }}</li>
					<li>{{ billing.country }}</li>
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
    import TextFieldtype from './fieldtypes/TextFieldtype';
    import CreditCard from './CreditCard';
    import {mapGetters, mapActions} from 'vuex'

    export default {
        components: {
            TextFieldtype,
            CreditCard,
        },

        computed: {
            ...mapGetters({
                cartTotal: 'cartTotal',
            }),
			readyToPay() {
                if(!this.showIdentity && !this.showPaymentMethod && !this.showBilling) {
                    return true;
				}

				return false;
			}
        },

        data() {
            return {
                identityMode: 'craftid',
                paymentMode: 'existingCard',
                showIdentity: false,
                showPaymentMethod: false,
                showBilling: false,
                craftIdIdentity: {
                    fullName: "Brandon Kelly",
                    email: "brandon@pixelandtonic.com",
					creditCard: {
                        number: 'YYYY EXIS TING YYYY',
                        expiry: '01/20',
                        cvc: '123',
                    },
                },
                guestIdentity: {
                    fullName: "",
                    email: "",
                },
                existingCard: {
                    number: 'XXXX EXIS TING XXXX',
                    expiry: '01/20',
                    cvc: '123',
                },
                creditCard: {
                    number: 'XXXX XXXX XXXX XXXX',
                    expiry: '01/20',
                    cvc: '123',
                },
                billing: {
                    businessName: 'Pixel & Tonic',
                    businessTaxId: '123456789',
                    addressLine1: 'address line 1',
                    addressLine2: 'address line 2',
                    country: 'USA',
                    state: 'OR',
                    city: 'Bend',
                    zipCode: '97700',
                }
            }
        }
    }
</script>
