<template>
    <step>
        <template slot="header">
            <h1>Cart</h1>
        </template>

        <template slot="main">
            <h2>{{ "Items in your cart"|t('app') }}</h2>

            <template v-if="cart">
                <template v-if="cartItems.length">
                    <table class="data fullwidth">
                        <thead>
                        <tr>
                            <th></th>
                            <th>Item</th>
                            <th>Updates</th>
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
                                        <img v-if="item.plugin.iconUrl" :src="item.plugin.iconUrl" width="40" height="40" />
                                    </div>
                                </td>
                                <td>
                                    {{ item.plugin.name}}
                                    <div class="text-grey-dark">
                                        <code>{{item.lineItem.purchasable.handle}}</code>
                                    </div>
                                </td>
                            </template>

                            <td><select-input v-model="itemUpdates[itemKey]" :options="itemUpdateOptions[itemKey]" /></td>
                            <td class="rightalign"><strong>{{ item.lineItem.total|currency }}</strong></td>
                            <td class="thin"><a class="delete icon" role="button" @click="removeFromCart(itemKey)"></a></td>
                        </tr>
                        <tr>
                            <th class="rightalign" colspan="3">Total Price</th>
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
                            <td>{{ plugin.name }}</td>
                            <td><strong>{{ plugin.editions[0].price|currency }}</strong></td>
                            <td class="thin"><a class="btn" @click="addToCart(plugin)">{{ "Add to cart"|t('app') }}</a></td>
                        </template>
                    </tr>
                    </tbody>
                </table>
            </template>
        </template>
    </step>
</template>

<script>
    import {mapState, mapGetters, mapActions} from 'vuex'
    import Step from '../Step'

    export default {

        data() {
            return {
                itemUpdates: {}
            }
        },

        components: {
            Step,
        },

        computed: {

            ...mapState({
                cart: state => state.cart.cart,
                craftLogo: state => state.craft.craftLogo,
                craftId: state => state.craft.craftId,
            }),

            ...mapGetters({
                activeTrialPlugins: 'cart/activeTrialPlugins',
                cartItems: 'cart/cartItems',
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

            itemUpdateOptions() {
                let options = []

                if (this.cartItems) {
                    this.cartItems.forEach(function(item, itemKey) {
                        const renewalPrice = item.lineItem.purchasable.renewalPrice
                        const years = 5
                        options[itemKey] = []

                        for (let i = 1; i <= years; i++) {
                            const currentDate = new Date()
                            const year = currentDate.getFullYear()
                            const month = currentDate.getMonth()
                            const day = currentDate.getDay()
                            const date = new Date(year + i, month, day)
                            const formattedDate = Craft.formatDate(date)
                            const price = renewalPrice * (i - 1);

                            let formattedPrice

                            if(price > 0) {
                                formattedPrice = '+' + this.$options.filters.currency(renewalPrice * (i - 1))
                            } else {
                                formattedPrice = this.$options.filters.t("Free", 'app')
                            }

                            const label = this.$options.filters.t("Updates Until {date} ({price})", 'app', {
                                year: i,
                                date: formattedDate,
                                price: formattedPrice,
                            })

                            options[itemKey].push({
                                label: label,
                                value: i,
                            })
                        }
                    }.bind(this))
                }

                return options
            }
        },

        methods: {

            ...mapActions({
                removeFromCart: 'cart/removeFromCart'
            }),

            addToCart(plugin) {
                const item = {
                    type: 'plugin-edition',
                    plugin: plugin.handle,
                    edition: plugin.editions[0].handle,
                    autoRenew: false,
                    cmsLicenseKey: window.cmsLicenseKey,
                }

                this.$store.dispatch('cart/addToCart', [item])
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

                $store.dispatch('cart/addToCart', items)
            },

            payment() {
                if (this.craftId) {
                    this.$root.openModal('payment')
                } else {
                    this.$root.openModal('identity')
                }
            }

        },

        mounted() {
            this.itemUpdates = {}

            this.cartItems.forEach(function(item, itemKey) {
                this.itemUpdates[itemKey] = 1
            }.bind(this))
        }

    }
</script>

<style lang="scss">
    .plugin-icon {
        img {
            max-width: none;
        }
    }
</style>