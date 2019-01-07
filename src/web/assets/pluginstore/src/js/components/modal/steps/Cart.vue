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
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(item, itemKey) in cartItems">
                            <template v-if="item.lineItem.purchasable.type === 'cms-edition'">
                                <td class="thin">
                                    <div class="plugin-icon">
                                        <img :src="craftLogo" width="40" height="40" />
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
                                    <strong>{{ item.plugin.name}}</strong>
                                    <div class="text-grey-dark">
                                        {{item.lineItem.purchasable.name}}
                                    </div>
                                </td>
                            </template>

                            <td class="expiry-date">
                                <select-input v-model="selectedExpiryDates[itemKey]" :options="itemExpiryDateOptions(itemKey)" @input="onSelectedExpiryDateChange(itemKey)" />
                                <div v-if="itemLoading(itemKey)" class="spinner"></div>
                            </td>
                            <td class="price">
                                <strong>{{ item.lineItem.total|currency }}</strong>
                                <br />
                                <a role="button" @click="removeFromCart(itemKey)">Remove</a>
                            </td>
                        </tr>
                        <tr>
                            <th class="total-price" colspan="3">Total Price</th>
                            <td class="total-price"><strong>{{cart.totalPrice|currency}}</strong></td>
                        </tr>
                        </tbody>
                    </table>

                    <div class="py-4">
                        <a @click="payment()" class="btn submit">{{ "Checkout"|t('app') }}</a>
                    </div>
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
                                    <img v-if="plugin.iconUrl" :src="plugin.iconUrl" height="40" width="40" />
                                    <div class="default-icon" v-else></div>
                                </div>
                            </td>
                            <td>
                                <strong>{{ plugin.name }}</strong><br />
                                {{activeTrialPluginEditions[plugin.handle].name}}
                            </td>
                            <td><strong>{{activeTrialPluginEditions[plugin.handle].price|currency}}</strong></td>
                            <td class="thin"><a class="btn" @click="addToCart(plugin, pluginLicenseInfo[plugin.handle].currentEdition)">{{ "Add to cart"|t('app') }}</a></td>
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
                selectedExpiryDates: {},
                loadingItems: {},
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
                expiryDateOptions: state => state.pluginStore.expiryDateOptions,
                pluginLicenseInfo: state => state.craft.pluginLicenseInfo,
            }),

            ...mapGetters({
                activeTrialPlugins: 'cart/activeTrialPlugins',
                cartItems: 'cart/cartItems',
                cartItemsData: 'cart/cartItemsData',
                getActiveTrialPluginEdition: 'cart/getActiveTrialPluginEdition',
                activeTrialPluginEditions: 'cart/activeTrialPluginEditions',
                getPluginEdition: 'pluginStore/getPluginEdition',
                getPluginLicenseInfo: 'craft/getPluginLicenseInfo',
            }),

            pendingActiveTrials() {
                return this.activeTrialPlugins.filter(p => {
                    if (p) {
                        if(!this.cart) {
                            return false
                        }

                        return !this.cart.lineItems.find(item => {
                            return item.purchasable.pluginId == p.id
                        })
                    }
                })
            },

        },

        methods: {

            ...mapActions({
                removeFromCart: 'cart/removeFromCart'
            }),

            addToCart(plugin, editionHandle) {
                const item = {
                    type: 'plugin-edition',
                    plugin: plugin.handle,
                    edition: editionHandle,
                    autoRenew: false,
                    cmsLicenseKey: window.cmsLicenseKey,
                }

                const pluginLicenseInfo = this.getPluginLicenseInfo(plugin.handle)

                if (pluginLicenseInfo && pluginLicenseInfo.licenseKey) {
                    item.licenseKey = pluginLicenseInfo.licenseKey
                }

                this.$store.dispatch('cart/addToCart', [item])
            },

            addAllToCart() {
                let $store = this.$store
                let items = []

                this.pendingActiveTrials.forEach(plugin => {
                    const edition = this.getActiveTrialPluginEdition(plugin.handle)
                    const pluginLicenseInfo = this.getPluginLicenseInfo(plugin.handle)

                    const item = {
                        type: 'plugin-edition',
                        plugin: plugin.handle,
                        edition: edition.handle,
                        autoRenew: false,
                        cmsLicenseKey: window.cmsLicenseKey,
                    }

                    if (pluginLicenseInfo.licenseKey) {
                        item.licenseKey = pluginLicenseInfo.licenseKey
                    }

                    items.push(item)
                })

                $store.dispatch('cart/addToCart', items)
            },

            payment() {
                if (this.craftId) {
                    this.$root.openModal('payment')
                } else {
                    this.$root.openModal('identity')
                }
            },

            itemExpiryDateOptions(itemKey) {
                const item = this.cartItems[itemKey]
                const renewalPrice = item.lineItem.purchasable.renewalPrice

                let options = []
                let selectedOption = 0

                this.expiryDateOptions.forEach((option, key) => {
                    if (option === item.lineItem.options.expiryDate) {
                        selectedOption = key
                    }
                })

                for (let i = 0; i < this.expiryDateOptions.length; i++) {
                    const expiryDateOption = this.expiryDateOptions[i]
                    const date = expiryDateOption[1]
                    const optionValue = expiryDateOption[0]

                    let label = "Updates Until "  + Craft.formatDate(date)

                    const price = renewalPrice * (i - selectedOption)

                    if (price !== 0) {
                        let sign = '';

                        if (price > 0) {
                            sign = '+';
                        }

                        label += " (" + sign + this.$options.filters.currency(price) + ")"
                    }

                    options.push({
                        label: label,
                        value: optionValue,
                    })
                }

                return options
            },

            onSelectedExpiryDateChange(itemKey) {
                this.$set(this.loadingItems, itemKey, true)
                let item = this.cartItemsData[itemKey]
                item.expiryDate = this.selectedExpiryDates[itemKey]
                this.$store.dispatch('cart/updateItem', {itemKey, item})
                    .then(() => {
                        this.$delete(this.loadingItems, itemKey)
                    })
            },

            itemLoading(itemKey) {
                if (!this.loadingItems[itemKey]) {
                    return false
                }

                return true
            }
        },

        mounted() {
            this.cartItems.forEach(function(item, key) {
                const expiryDate = item.lineItem.options.expiryDate
                this.$set(this.selectedExpiryDates, key, expiryDate)
            }.bind(this))
        }

    }
</script>

<style lang="scss" scoped>
    .plugin-icon {
        img {
            max-width: none;
        }
    }

    td.expiry-date {
        & > div {
            display: inline-block;
            margin-bottom: 0;
        }

        .spinner {
            @apply .relative .ml-2;
            top: -2px;
        }
    }

    @media (max-width: 991px) {
        thead {
            display: none;
        }

        tr,
        td,
        th {
            display: block !important;
            border: 0 !important;
            padding: 4px 0 !important;
        }

        tr {
            border-top: 1px solid #eee !important;
        }
    }

    @media (min-width: 992px) {
        td.expiry-date {
            @apply .w-1/2;
        }

        td.price {
            @apply .w-1/4 .text-right;
        }

        td.total-price,
        th.total-price {
            @apply .text-right;
        }
    }
</style>