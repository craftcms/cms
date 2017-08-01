<template>

    <div>
        <header class="header">
            <p><a @click="backToCart()">&lt; Cart</a></p>
            <h1 class="left">Payment</h1>
        </header>
        <div class="body">
            <h2>Identity</h2>
            <p>No identity informations.</p>

            <h2>Payment Method</h2>
            <p>No payment method informations.</p>

            <h2>Billing</h2>
            <p>No billing informations.</p>

            <div class="buttons">
                <a href="#" class="btn submit">Pay $XXX.00</a>
            </div>

            <p>Your payment is safe and secure with Stripe.</p>
        </div>
    </div>

</template>

<script>
    export default {
        name: 'payment',
        methods: {
            backToCart() {
                this.$root.openGlobalModal('cart');
            }
        }
    }
</script>