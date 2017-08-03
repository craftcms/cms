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
                                    <p><a href="#" class="btn submit">Connect to your Craft ID</a></p>
                            </template>
                            <template v-else-if="identityMode == 'guest'">
                                    <text-field label="Full Name" id="fullName" placeholder="John Smith" v-model="guestIdentity.fullName"></text-field>
                                    <text-field label="Email" id="email" placeholder="john@example.com" v-model="guestIdentity.email"></text-field>
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
                                    <p><label><input type="radio" value="existingCard" v-model="paymentMode"> Use card {{ existingCard.number }}</label></p>
                                    <p><label><input type="radio" value="newCard" v-model="paymentMode"> Or use a different credit card</label></p>

                                    <template v-if="paymentMode == 'newCard'">
                                            <text-field label="Card Number" id="new-card-number" v-model="card.number"></text-field>
                                            <text-field label="Card Expiry" id="new-card-expiry" placeholder="MM/YY"  v-model="card.expiry"></text-field>
                                            <text-field label="Card CVC" id="new-card-cvc" placeholder="XXX"  v-model="card.cvc"></text-field>
                                    </template>
                            </template>
                            <template v-else>
                                    <text-field label="Card Number" id="card-number" v-model="card.number"></text-field>
                                    <text-field label="Card Expiry" id="card-expiry" placeholder="MM/YY"  v-model="card.expiry"></text-field>
                                    <text-field label="Card CVC" id="card-cvc" placeholder="XXX"  v-model="card.cvc"></text-field>
                            </template>
                    </template>
                    <template v-else>
                            <ul>
                                    <template v-if="identityMode == 'craftid' && paymentMode == 'existingCard'">
                                            <li>{{ existingCard.number }}</li>
                                            <li>{{ existingCard.expiry }}</li>
                                            <li>{{ existingCard.cvc }}</li>
                                    </template>
                                    <template v-else>
                                            <li>{{ card.number }}</li>
                                            <li>{{ card.expiry }}</li>
                                            <li>{{ card.cvc }}</li>
                                    </template>
                            </ul>
                    </template>
            </div>

            <hr>

            <div class="block">
                    <h2>Billing</h2>

                    <a class="block-toggle" v-if="!showBilling" @click="showBilling=true">Edit</a>
                    <a class="block-toggle" v-else @click="showBilling=false">Done</a>

                    <template v-if="showBilling">
                            <text-field label="Business Name" id="business-name" v-model="billing.businessName"></text-field>
                            <text-field label="Business Tax ID" id="business-tax-id" v-model="billing.businessTaxId"></text-field>
                            <text-field label="Address Line 1" id="address-line-1" v-model="billing.addressLine1"></text-field>
                            <text-field label="Address Line 2" id="address-line-2" v-model="billing.addressLine2"></text-field>
                            <text-field label="Country" id="country" v-model="billing.country"></text-field>
                            <text-field label="State" id="state" v-model="billing.state"></text-field>
                            <text-field label="City" id="city" v-model="billing.city"></text-field>
                            <text-field label="Zip Code" id="zip-code" v-model="billing.zipCode"></text-field>
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
                <a href="#" class="btn submit">Pay {{ cartTotal()|currency }}</a>
            </div>

            <p>Your payment is safe and secure with Stripe.</p>
    </div>

</template>

<script>
    import TextField from './TextField';
    import { mapGetters, mapActions } from 'vuex'

    export default {
        name: 'payment',
        components: {
            TextField
        },
        computed: {
            ...mapGetters({
                cartTotal: 'cartTotal',
            }),
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
                card: {
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

<style scoped>
        .block {
                position: relative;
        }
        .block .block-toggle {
                position: absolute;
                top: 0;
                right: 0;
        }
</style> 