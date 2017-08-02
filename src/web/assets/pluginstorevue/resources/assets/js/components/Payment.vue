<template>

    <div>
            <h2>Identity</h2>
            <text-field label="Full Name" id="fullName" placeholder="John Smith"></text-field>
            <text-field label="Email" id="email" placeholder="john@example.com"></text-field>

            <h2>Payment Method</h2>
            <text-field label="Card Number" id="card-number"></text-field>
            <text-field label="Card Expiry" id="card-expiry" placeholder="MM/YY"></text-field>
            <text-field label="Card CVC" id="card-cvc" placeholder="XXX"></text-field>

            <h2>Billing</h2>
            <text-field label="Business Name" id="business-name"></text-field>
            <text-field label="Business Tax ID" id="business-tax-id"></text-field>
            <text-field label="Address Line 1" id="address-line-1"></text-field>
            <text-field label="Address Line 2" id="address-line-2"></text-field>
            <text-field label="Country" id="country"></text-field>
            <text-field label="State" id="state"></text-field>
            <text-field label="City" id="city"></text-field>
            <text-field label="Zip Code" id="zip-code"></text-field>

            <hr>

            <div class="buttons">
                <a href="#" class="btn submit">Pay {{ cartTotal()|currency }}</a>
            </div>

            <p>Your payment is safe and secure with Stripe.</p>
        </div>

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
        methods: {
            backToCart() {
                this.$root.openGlobalModal('cart');
            }
        },
        computed: {
            ...mapGetters({
                cartTotal: 'cartTotal',
            }),
        }
    }
</script>
