<template>
    <div>
        <h2>Items</h2>

        <table class="data fullwidth">
            <thead>
            <tr>
                <th class="thin"></th>
                <th>Plugin Name</th>
                <th>Price</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(plugin, index) in cartPlugins">
                <td class="thin">
                    <a href="#">
                        <div class="plugin-icon">
                            <img v-if="plugin.iconUrl" :src="plugin.iconUrl" height="32" />
                            <div class="default-icon" v-else></div>
                        </div>
                    </a>
                </td>
                <td>
                    <a href="#">{{ plugin.name }}</a> <div class="light">{{ plugin.shortDescription }}</div>
                </td>
                <td>
                    <strong>${{ plugin.price }}</strong>
                    <div class="light">${{ plugin.updatePrice }} per year for updates</div>
                </td>
                <td class="thin">
                    <a class="btn" @click="removeFromCart(plugin)">Remove</a>
                </td>
            </tr>
            </tbody>
        </table>

        <div class="cart-review">
            <p>Renew for 3 years and save $XX.00</p>

            <table class="fullwidth">
                <tr>
                    <th>Subtotal</th>
                    <td>$XX.00</td>
                </tr>
                <tr>
                    <th>Pro Rate Discount</th>
                    <td>$XX.00</td>
                </tr>
                <tr>
                    <th><strong>Total</strong></th>
                    <td><strong>${{total}}</strong></td>
                </tr>
            </table>

            <p><a href="#" class="btn submit">Process My Order</a></p>
        </div>

        <template v-if="pendingActiveTrials && pendingActiveTrials.length > 0">

            <div v-if="pendingActiveTrials.length > 1" class="right">
                <a @click="addAllToCart()">Add all to cart</a>
            </div>
            <h2>Active Trials</h2>

            <table class="data fullwidth">
                <thead>
                <tr>
                    <th class="thin"></th>
                    <th>Plugin Name</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(plugin, index) in pendingActiveTrials">
                    <td class="thin">
                        <a href="#">
                            <div class="plugin-icon">
                                <img v-if="plugin.iconUrl" :src="plugin.iconUrl" height="32" />
                                <div class="default-icon" v-else></div>
                            </div>
                        </a>
                    </td>
                    <td>
                        <a href="#">{{ plugin.name }}</a> <div class="light">{{ plugin.shortDescription }}</div>
                    </td>
                    <td>
                        <strong>${{ plugin.price }}</strong>
                        <div class="light">${{ plugin.updatePrice }} per year for updates</div>
                    </td>
                    <td class="thin">
                        <a class="btn" @click="addToCart(plugin)">Add to cart</a>
                    </td>
                </tr>
                </tbody>
            </table>
        </template>
    </div>
</template>

<script>
    import { mapGetters, mapActions } from 'vuex'

    export default {
        name: 'cart',

        computed: {
            ...mapGetters({
                cartPlugins: 'cartPlugins',
                activeTrialPlugins: 'activeTrialPlugins',
            }),
            total () {
                return this.cartPlugins.reduce((total, p) => {
                    return total + parseFloat(p.price)
                }, 0)
            },
            pendingActiveTrials() {
                return this.activeTrialPlugins.filter(p => {
                    return !this.cartPlugins.find(cartP => p.id == cartP.id)
                })
            },
        },

        methods: {
            ...mapActions([
                'addToCart',
                'removeFromCart'
            ]),
            addAllToCart () {
                let $store = this.$store;

                this.pendingActiveTrials.forEach(function(activeTrial) {
                    $store.dispatch('addToCart', activeTrial)
                })
            },
        },
    }
</script>

<style scoped>
    .cart-review { padding: 24px; border: 1px solid #eee; margin-top: 24px; text-align: right; }
    .cart-review table th, .cart-review table td { text-align: right; padding: 3px 0; font-size: 1.2em; }
    .cart-review table td { width: 100px; }
</style>