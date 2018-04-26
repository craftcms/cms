<template>
    <div>
        <h2>{{ "Items in your cart"|t('app') }}</h2>

        <template v-if="cart">
            <template v-if="cartItems.length">
                <table class="data fullwidth">
                    <thead>
                    <tr>
                        <th></th>
                        <th>Item</th>
                        <th></th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(item, itemKey) in cartItems">
                        <template v-if="item.lineItem.purchasable.type === 'cms-edition'">
                            <td class="thin">
                                <div class="plugin-icon">
                                    <img :src="craftLogo" width="32" height="32" />
                                </div>
                            </td>
                            <td>Craft {{ item.lineItem.purchasable.name }}</td>
                        </template>

                        <template v-else="item.lineItem.purchasable.type === 'plugin-edition'">
                            <td class="thin">
                                <div class="plugin-icon">
                                    <img v-if="item.plugin.iconUrl" :src="item.plugin.iconUrl" height="32" />
                                </div>
                            </td>
                            <td>
                                {{ item.plugin.name}}
                            </td>
                        </template>

                        <td class="rightalign">
                            <strong>{{ item.lineItem.total|currency }}</strong>
                        </td>

                        <td class="thin"><a class="delete icon" role="button" @click="removeFromCart(itemKey)"></a></td>
                    </tr>
                    <tr>
                        <th class="rightalign" colspan="2">Total Price</th>
                        <td class="rightalign"><strong>{{ cart.totalPrice|currency }}</strong></td>
                        <td class="thin"></td>
                    </tr>
                    </tbody>
                </table>

                <p><a @click="payment()" class="btn submit">{{ "Checkout"|t('app') }}</a></p>
            </template>

            <div v-else>
                <p>{{ "Your cart is empty."|t('app') }} <a @click="$emit('continue-shopping')">{{ "Continue shopping"|t('app') }}</a></p>
            </div>
        </template>

        <template v-if="pendingActiveTrials && pendingActiveTrials.length > 0">

            <hr />

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
                <tr v-for="plugin in pendingActiveTrials">
                    <template v-if="plugin">
                        <td class="thin">
                            <div class="plugin-icon">
                                <img v-if="plugin.iconUrl" :src="plugin.iconUrl" height="32" />
                                <div class="default-icon" v-else></div>
                            </div>
                        </td>
                        <td>
                            {{ plugin.name }}
                        </td>
                        <td>
                            <strong>{{ plugin.editions[0].price|currency }}</strong>
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
    import {mapState, mapGetters, mapActions} from 'vuex'

    export default {

        computed: {

            ...mapState({
                cart: state => state.cart.cart,
                craftLogo: state => state.craft.craftLogo,
                craftId: state => state.craft.craftId,
            }),

            ...mapGetters({
                activeTrialPlugins: 'activeTrialPlugins',
                cartItems: 'cartItems',
            }),

            pendingActiveTrials() {
                return this.activeTrialPlugins.filter(p => {
                    if (p) {
                        return !this.cart.lineItems.find(item => {
                            return item.purchasable.pluginId == p.id
                        })
                    }
                })
            },

        },

        methods: {

            ...mapActions([
                'removeFromCart'
            ]),

            addToCart(plugin) {
                const item = {
                    type: 'plugin-edition',
                    plugin: plugin.handle,
                    edition: plugin.editions[0].handle,
                    autoRenew: false,
                    cmsLicenseKey: window.cmsLicenseKey,
                }

                this.$store.dispatch('addToCart', [item])
            },

            addAllToCart() {
                let $store = this.$store
                let items = []

                this.pendingActiveTrials.forEach(activeTrialPlugin => {
                    items.push({
                        type: 'plugin-edition',
                        plugin: activeTrialPlugin.handle,
                        edition: activeTrialPlugin.editions[0].handle,
                        autoRenew: false,
                        cmsLicenseKey: window.cmsLicenseKey,
                    })
                })

                $store.dispatch('addToCart', items)
            },

            payment() {
                if (this.craftId) {
                    this.$root.openGlobalModal('payment')
                } else {
                    this.$root.openGlobalModal('identity')
                }
            }

        },

    }
</script>
