<template>
    <step>
        <template slot="header">
            <h1>{{ "Cart"|t('app') }}</h1>
        </template>

        <template slot="main">
            <h2>{{ "Items in your cart"|t('app') }}</h2>

            <template v-if="cart">
                <template v-if="cartItems.length">
                    <table class="data fullwidth">
                        <thead>
                        <tr>
                            <th></th>
                            <th>{{ "Item"|t('app') }}</th>
                            <th>{{ "Updates"|t('app') }}</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <template v-for="(item, itemKey) in cartItems">
                            <tr :key="'item' + itemKey">
                                <template v-if="item.lineItem.purchasable.type === 'cms-edition'">
                                    <td class="thin">
                                        <div class="plugin-icon">
                                            <img :src="craftLogo" width="40" height="40" />
                                        </div>
                                    </td>
                                    <td>Craft {{ item.lineItem.purchasable.name }}</td>
                                </template>

                                <template v-else-if="item.lineItem.purchasable.type === 'plugin-edition'">
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
                                    <template v-if="item.lineItem.options.licenseKey.substr(0, 4) === 'new:'">
                                        <select-input v-model="selectedExpiryDates[itemKey]" :options="itemExpiryDateOptions(itemKey)" @input="onSelectedExpiryDateChange(itemKey)" />
                                    </template>
                                    <template v-else>
                                        {{ "Updates Until {date}"|t('app', {date: getExpiryDate(selectedExpiryDates[itemKey])}) }}
                                    </template>

                                    <!--if (licenseKey && licenseKey.substr(0, 3) !== 'new') {-->
                                    <!--item.licenseKey = licenseKey-->
                                    <!--}-->

                                    <div v-if="itemLoading(itemKey)" class="spinner"></div>
                                </td>
                                <td class="price">
                                    <strong>{{ item.lineItem.price|currency }}</strong>
                                    <br />
                                    <a role="button" @click="removeFromCart(itemKey)">{{ "Remove"|t('app') }}</a>
                                </td>
                            </tr>

                            <template v-for="(adjustment, adjustmentKey) in item.lineItem.adjustments">
                                <tr :key="itemKey + 'adjustment-' + adjustmentKey">
                                    <td></td>
                                    <td></td>
                                    <td>
                                        {{adjustment.name}}
                                    </td>
                                    <td class="price">
                                        {{adjustment.amount|currency}}
                                    </td>
                                </tr>
                            </template>
                        </template>
                        <tr>
                            <th class="total-price" colspan="3">{{ "Total Price"|t('app') }}</th>
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
                    <tr v-for="(plugin, key) in pendingActiveTrials" :key="key">
                        <template v-if="plugin">
                            <td class="thin">
                                <div class="plugin-icon">
                                    <img v-if="plugin.iconUrl" :src="plugin.iconUrl" height="40" width="40" />
                                    <div class="default-icon" v-else></div>
                                </div>
                            </td>
                            <td>
                                <strong>{{ plugin.name }}</strong>
                                <div class="text-grey-dark">
                                    {{activeTrialPluginEditions[plugin.handle].name}}
                                </div>
                            </td>
                            <td><strong>{{activeTrialPluginEditions[plugin.handle].price|currency}}</strong></td>
                            <td class="thin"><a class="btn" @click="addToCart(plugin, pluginLicenseInfo[plugin.handle].edition)">{{ "Add to cart"|t('app') }}</a></td>
                        </template>
                    </tr>
                    </tbody>
                </table>
            </template>
        </template>
    </step>
</template>

<script>
    /* global Craft */

    import {mapState, mapGetters, mapActions} from 'vuex'
    import Step from '../Step'

    export default {

        data() {
            return {
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

            selectedExpiryDates: {
                get() {
                    return JSON.parse(JSON.stringify(this.$store.state.cart.selectedExpiryDates))
                },
                set(newValue) {
                    this.$store.commit('cart/updateSelectedExpiryDates', newValue)
                }
            },

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
                    const optionValue = expiryDateOption[0]
                    const date = Craft.formatDate(expiryDateOption[1])
                    let label = this.$options.filters.t("Updates Until {date}", 'app', {date})
                    let price = renewalPrice * (i - selectedOption)

                    if (price !== 0) {
                        let sign = '';

                        if (price > 0) {
                            sign = '+';
                        }

                        price = this.$options.filters.currency(price)
                        label = this.$options.filters.t("Updates Until {date} ({sign}{price})", 'app', {date, sign, price})
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
            },

            getExpiryDate(key) {
                const expiryDateOption = this.expiryDateOptions.find(option => option[0] === key)

                if (!expiryDateOption) {
                    return null
                }

                return expiryDateOption[1]
            }
        },

    }
</script>

<style lang="scss" scoped>
    @import "../../../../../../../../../lib/craftcms-sass/mixins";

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
            @apply .w-1/4;
            @include alignright;
        }

        td.total-price,
        th.total-price {
            @include alignright;
        }
    }
</style>