<template>
    <div>
        <h2>Items</h2>

        <table class="data fullwidth">
            <thead>
            <tr>
                <th class="thin"></th>
                <th>Plugin Name</th>
                <th>Quantity</th>
                <th>Price</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(plugin, index) in products">
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
                    {{ plugin.quantity }}
                </td>
                <td>
                    <strong>${{ plugin.licensePrice }}</strong>
                    <div class="light">$XX.00 per year for updates</div>
                </td>
                <td class="thin">
                    <a class="btn" @click="removeFromCart(index)">Remove</a>
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

        <h2>Active Trials</h2>

        <table class="data fullwidth">
            <thead>
            <tr>
                <th class="thin"></th>
                <th>Plugin Name</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(plugin, index) in activeTrials">
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
                    <strong>$XX.00</strong>
                    <div class="light">$XX.00 per year for updates</div>
                </td>
                <td class="thin">
                    <a class="btn" @click="addToCart(index)">Add to cart</a>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
    import { mapGetters } from 'vuex'

    export default {
        name: 'cart',

        data () {
            return {
                plugins: [],
                activeTrials: [],
            }
        },

        computed: {
            ...mapGetters({
                products: 'cartProducts',
            }),
            total () {
                return this.products.reduce((total, p) => {
                    return total + p.licensePrice * p.quantity
                }, 0)
            }
        },

        created: function() {

        },

        methods: {
            addToCart (index) {
                console.log('add to cart !', index);
            },
            removeFromCart (index) {
                console.log('remove from cart !', index);
            }
        }
    }
</script>

<style scoped>
    .cart-review { padding: 24px; border: 1px solid #eee; margin-top: 24px; text-align: right; }
    .cart-review table th, .cart-review table td { text-align: right; padding: 3px 0; font-size: 1.2em; }
    .cart-review table td { width: 100px; }
</style>