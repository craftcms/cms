<template>
    <step>
        <template slot="header">
            <h1>{{ "Cart"|t('app') }}</h1>
        </template>

        <template slot="main">
            <h2>{{ "Items in your cart"|t('app') }}</h2>

            <template v-if="cart">
                <template v-if="cartItems.length">
                    <table class="cart-data fullwidth">
                        <thead>
                        <tr>
                            <th></th>
                            <th>{{ "Item"|t('app') }}</th>
                            <th>{{ "Updates"|t('app') }}</th>
                            <th class="w-10"></th>
                        </tr>
                        </thead>
                        <tbody v-for="(item, itemKey) in cartItems" :key="'item' + itemKey">
                            <tr class="item-details">
                                <template v-if="item.lineItem.purchasable.type === 'cms-edition'">
                                    <td class="thin">
                                        <div class="plugin-icon">
                                            <img :src="craftLogo" width="40" height="40" />
                                        </div>
                                    </td>
                                    <td class="item-name">
                                        <strong>Craft CMS</strong>
                                        <edition-badge :name="item.lineItem.purchasable.name"></edition-badge>
                                    </td>
                                </template>

                                <template v-else-if="item.lineItem.purchasable.type === 'plugin-edition'">
                                    <td class="thin">
                                        <div class="plugin-icon">
                                            <img v-if="item.plugin.iconUrl" :src="item.plugin.iconUrl" width="40" height="40" />
                                        </div>
                                    </td>
                                    <td>
                                        <div class="item-name">
                                            <strong>{{ item.plugin.name}}</strong>
                                            <edition-badge :name="item.lineItem.purchasable.name"></edition-badge>
                                        </div>
                                    </td>
                                </template>

                                <td class="expiry-date">
                                    <template v-if="item.lineItem.purchasable.type === 'cms-edition' || (item.lineItem.purchasable.type === 'plugin-edition' && item.lineItem.options.licenseKey.substr(0, 4) === 'new:')">
                                        <dropdown v-model="selectedExpiryDates[itemKey]" :options="itemExpiryDateOptions(itemKey)" @input="onSelectedExpiryDateChange(itemKey)" />
                                    </template>

                                    <spinner v-if="itemLoading(itemKey)"></spinner>
                                </td>
                                <td class="price">
                                    <strong>{{ item.lineItem.price|currency }}</strong>
                                </td>
                            </tr>

                            <template v-for="(adjustment, adjustmentKey) in item.lineItem.adjustments">
                                <tr :key="itemKey + 'adjustment-' + adjustmentKey" class="sub-item">
                                    <td class="blank-cell"></td>
                                    <td class="blank-cell"></td>
                                    <td>
                                        <template v-if="adjustment.sourceSnapshot.type === 'extendedUpdates'">
                                            {{"Updates until {date}"|t('app', {date: $options.filters.formatDate(adjustment.sourceSnapshot.expiryDate)})}}
                                        </template>
                                        <template v-else>
                                            {{adjustment.name}}
                                        </template>
                                    </td>
                                    <td class="price">
                                        {{adjustment.amount|currency}}
                                    </td>
                                </tr>
                            </template>

                            <tr class="sub-item">
                                <td class="blank-cell"></td>
                                <td class="blank-cell"></td>
                                <td class="empty-cell"></td>
                                <td class="price">
                                    <div class="w-16">
                                        <template v-if="!removeFromCartLoading(itemKey)">
                                            <a role="button" @click="removeFromCart(itemKey)">{{ "Remove"|t('app') }}</a>
                                        </template>
                                        <template v-else>
                                            <spinner class="sm"></spinner>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </tbody>

                        <tbody>
                            <tr>
                                <th class="total-price" colspan="3"><strong>{{ "Total Price"|t('app') }}</strong></th>
                                <td class="total-price"><strong>{{cart.totalPrice|currency}}</strong></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="py-4">
                        <btn kind="primary" @click="payment()">{{ "Checkout"|t('app') }}</btn>
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

                <table class="cart-data">
                    <thead>
                    <tr>
                        <th class="thin"></th>
                        <th>{{ "Plugin Name"|t('app') }}</th>
                    </tr>
                    </thead>
                    <tbody v-for="(plugin, key) in pendingActiveTrials" :key="key">
                        <tr>
                            <template v-if="plugin">
                                <td class="thin">
                                    <div class="plugin-icon">
                                        <img v-if="plugin.iconUrl" :src="plugin.iconUrl" height="40" width="40" />
                                        <div class="default-icon" v-else></div>
                                    </div>
                                </td>
                                <td class="item-name">
                                    <strong>{{ plugin.name }}</strong>

                                    <edition-badge v-if="activeTrialPluginEditions[plugin.handle] && plugin.editions.length > 1" :name="activeTrialPluginEditions[plugin.handle].name"></edition-badge>
                                </td>
                                <td><strong v-if="activeTrialPluginEditions[plugin.handle]">{{activeTrialPluginEditions[plugin.handle].price|currency}}</strong></td>
                                <td class="w-1/4">
                                    <div class="text-right">
                                        <template v-if="!activeTrialLoading(plugin.handle)">
                                            <a @click="addToCart(plugin, pluginLicenseInfo[plugin.handle].edition)" :loading="activeTrialLoading(plugin.handle)">{{ "Add to cart"|t('app') }}</a>
                                        </template>
                                        <template v-else>
                                            <spinner size="sm"></spinner>
                                        </template>
                                    </div>
                                </td>
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
    import EditionBadge from '../../EditionBadge'

    export default {
        data() {
            return {
                loadingItems: {},
                loadingActiveTrials: {},
                loadingRemoveFromCart: {},
            }
        },

        components: {
            Step,
            EditionBadge,
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
                this.$set(this.loadingActiveTrials, plugin.handle, true)

                const item = {
                    type: 'plugin-edition',
                    plugin: plugin.handle,
                    edition: editionHandle
                }

                this.$store.dispatch('cart/addToCart', [item])
                    .then(() => {
                        this.$delete(this.loadingActiveTrials, plugin.handle)
                    })
                    .catch(response => {
                        this.$delete(this.loadingActiveTrials, plugin.handle)
                        const errorMessage = response.errors && response.errors[0] && response.errors[0].message ? response.errors[0].message : 'Couldn’t add item to cart.';
                        this.$root.displayError(errorMessage)
                    })
            },

            removeFromCart(itemKey) {
                this.$set(this.loadingRemoveFromCart, itemKey, true)

                this.$store.dispatch('cart/removeFromCart', itemKey)
                    .then(() => {
                        this.$delete(this.loadingRemoveFromCart, itemKey)
                    })
                    .catch(response => {
                        this.$delete(this.loadingRemoveFromCart, itemKey)
                        const errorMessage = response.errors && response.errors[0] && response.errors[0].message ? response.errors[0].message : 'Couldn’t remove item from cart.';
                        this.$root.displayError(errorMessage)
                    })
            },

            addAllToCart() {
                let $store = this.$store
                let items = []

                this.pendingActiveTrials.forEach(plugin => {
                    const edition = this.getActiveTrialPluginEdition(plugin.handle)

                    const item = {
                        type: 'plugin-edition',
                        plugin: plugin.handle,
                        edition: edition.handle
                    }

                    items.push(item)
                })

                $store.dispatch('cart/addToCart', items)
                    .catch(() => {
                        this.$root.displayError(this.$options.filters.t('Couldn’t add all items to the cart.', 'app'))
                    })
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
                    let label = this.$options.filters.t("Updates until {date}", 'app', {date})
                    let price = renewalPrice * (i - selectedOption)

                    if (price !== 0) {
                        let sign = '';

                        if (price > 0) {
                            sign = '+';
                        }

                        price = this.$options.filters.currency(price)
                        label = this.$options.filters.t("Updates until {date} ({sign}{price})", 'app', {date, sign, price})
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

            activeTrialLoading(pluginHandle) {
                if (!this.loadingActiveTrials[pluginHandle]) {
                    return false
                }

                return true
            },

            removeFromCartLoading(itemKey) {
                if (!this.loadingRemoveFromCart[itemKey]) {
                    return false
                }

                return true
            },

            updatesUntil(date) {
                return this.$options.filters.t("Updates until {date}", 'app', {date})
            }
        },
    }
</script>

<style lang="scss" scoped>
    @import "../../../../../../../../../lib/craftcms-sass/mixins";

    .item-name {
        .edition-badge {
            @apply .ml-2;
        }
    }

    .plugin-icon {
        margin-right: 10px !important;

        img {
            max-width: none;
        }
    }

    table.cart-data {
        thead,
        tbody {
            border-bottom: 1px solid #eee;
        }

        tr {
            th, td {
                padding: 7px 0;
            }

            td.expiry-date {
                & > div {
                    display: inline-block;
                    margin-bottom: 0;
                }

                .c-spinner {
                    @apply .relative .ml-4;
                    top: 6px;
                }
            }

            td.thin {
                .c-btn {
                    white-space: nowrap;
                }
            }
        }
    }

    @media (max-width: 991px) {
        table.cart-data {
            border-top: 1px solid #eee;

            thead {
                display: none;
            }


            tr,
            td,
            th {
                display: block;
            }

            tr {
                &.sub-item {
                    td.blank-cell,
                    td.empty-cell {
                        display: none;
                    }
                }
            }
        }
    }

    @media (min-width: 992px) {
        table.cart-data {
            tr {
                &.sub-item {
                    td:not(.blank-cell) {
                        border-top: 1px dotted #eee;
                    }
                }

                th,
                td {
                    padding: 10px 0;

                    &.price {
                        text-align: right;
                    }

                    &.total-price {
                        text-align: right;
                    }
                }

                td.expiry-date {
                    @apply .w-3/5;
                }
            }
        }
    }
</style>
