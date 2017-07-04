<template>
    <div>
        <h2>Items in your cart</h2>

        <table class="data fullwidth">
            <thead>
                <tr>
                    <th class="thin"></th>
                    <th>Plugin Name</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="plugin in plugins">
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
                        <strong>$99.00</strong>
                        <div class="light">$19.00 per year for updates</div>
                    </td>
                    <td class="thin">
                        <a href="#" class="btn">Remove</a>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="cart-review">
            <p>Renew for 3 years and save $XX.00</p>

            <table class="fullwidth">
                <tr>
                    <th>Subtotal</th>
                    <td>$198.00</td>
                </tr>
                <tr>
                    <th>Pro Rate Discount</th>
                    <td>$20.00</td>
                </tr>
                <tr>
                    <th><strong>Total</strong></th>
                    <td><strong>$178.00</strong></td>
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
            <tr v-for="plugin in activeTrials">
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
                    <strong>$99.00</strong>
                    <div class="light">$19.00 per year for updates</div>
                </td>
                <td class="thin">
                    <a href="#" class="btn">Add to cart</a>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
    export default {
        name: 'cart',

        data () {
            return {
                plugins: [],
                activeTrials: [],
            }
        },

        created: function() {
            this.$http.get('https://craftid.dev/api/plugins').then(function(data) {
                var plugins = this.plugins.concat(data.body.data);
                this.plugins = plugins.slice(0,3);
                this.activeTrials = plugins.slice(3,5);
            });
        },
    }
</script>

<style scoped>
    .cart-review { padding: 24px; border: 1px solid #eee; margin-top: 24px; text-align: right; }
    .cart-review table th, .cart-review table td { text-align: right; padding: 3px 0; font-size: 1.2em; }
    .cart-review table td { width: 100px; }
</style>