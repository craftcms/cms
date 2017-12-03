<template>
    <div>
        <h2>{{ "Items in your cart"|t('app') }}</h2>

        <div v-if="cartPlugins.length > 0">
            <table class="data fullwidth">
                <thead>
                <tr>
                    <th class="thin"></th>
                    <th>{{ "Plugin Name"|t('app') }}</th>
                    <th>{{ "Price"|t('app') }}</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(plugin, index) in cartPlugins">
                    <template v-if="plugin">
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
                            <strong>{{ plugin.price|currency }}</strong>
                            <div class="light">{{ "{price} per year for updates"|t('app', { price: $root.$options.filters.currency(plugin.updatePrice) }) }}</div>
                        </td>
                        <td class="thin">
                            <a class="btn" @click="removeFromCart(plugin)">
                                <template v-if="isInTrial(plugin)">{{ "Buy later"|t('app') }}</template>
                                <template v-else>{{ "Remove"|t('app') }}</template>
                            </a>
                        </td>
                    </template>
                </tr>
                </tbody>
            </table>

            <div class="cart-review">
                <p>Renew for 3 years and save $XX.00</p>

                <table class="fullwidth">
                    <tr>
                        <th>{{ "Subtotal"|t('app') }}</th>
                        <td>$XX.00</td>
                    </tr>
                    <tr>
                        <th>{{ "Pro Rate Discount"|t('app') }}</th>
                        <td>$XX.00</td>
                    </tr>
                    <tr>
                        <th><strong>{{ "Total"|t('app') }}</strong></th>
                        <td><strong>{{ cartTotal()|currency }}</strong></td>
                    </tr>
                </table>

                <p><a @click="payment()" class="btn submit">{{ "Process My Order"|t('app') }}</a></p>
            </div>

        </div>

        <div v-else>
            <p>{{ "Your cart is empty."|t('app') }} <a @click="$emit('continue-shopping')">{{ "Continue shopping"|t('app') }}</a></p>
        </div>


        <template v-if="pendingActiveTrials && pendingActiveTrials.length > 0">

            <div v-if="pendingActiveTrials.length > 1" class="right">
                <a @click="addAllToCart()">{{ "Add all to cart"|t('app') }}</a>
            </div>

            <h2>{{ "Active Trials"|t('app') }}</h2>

            <table class="data fullwidth">
                <thead>
                <tr>
                    <th class="thin"></th>
                    <th>{{ "Plugin Name"|t('app') }}</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(plugin, index) in pendingActiveTrials">
                    <template v-if="plugin">
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
                            <strong>{{ plugin.price|currency }}</strong>
                            <div class="light">{{ plugin.updatePrice|currency }} per year for updates</div>
                        </td>
                        <td class="thin">
                            <a class="btn" @click="addToCart(plugin)">{{ "Add to cart"|t('app') }}</a>
                        </td>
                    </template>
                </tr>
                </tbody>
            </table>
        </template>
    </div>
</template>

<script>
    import { mapGetters, mapActions } from 'vuex'

    export default {

        computed: {

            ...mapGetters({
                isInTrial: 'isInTrial',
                cartPlugins: 'cartPlugins',
                activeTrialPlugins: 'activeTrialPlugins',
                cartTotal: 'cartTotal',
            }),

            pendingActiveTrials() {
                return this.activeTrialPlugins.filter(p => {
                    if(p) {
                        return !this.cartPlugins.find(cartP => p.id == cartP.id)
                    }
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

                this.pendingActiveTrials.forEach(activeTrial => {
                    $store.dispatch('addToCart', activeTrial)
                })
            },

            payment() {
                this.$root.openGlobalModal('payment');
            }

        },

    }
</script>
