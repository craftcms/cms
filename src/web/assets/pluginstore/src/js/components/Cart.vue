<template>
    <div>
        <h2>{{ "Items in your cart"|t('app') }}</h2>

        <template v-if="remoteCart">
            <template v-if="remoteCart.lineItems.length">
                <table class="data fullwidth">
                    <thead>
                    <tr>
                        <th>Item</th>
                        <th></th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(lineItem, lineItemKey) in remoteCart.lineItems">
                        <td v-if="lineItem.purchasable.type === 'cms-edition'">Craft {{ lineItem.purchasable.name }}</td>
                        <td v-else="lineItem.purchasable.type === 'plugin-edition'">
                            {{ lineItem.purchasable.fullName }}
                        </td>

                        <td class="rightalign">{{ lineItem.total|currency }}</td>
                        <td class="thin"><a class="delete icon" role="button" @click="removeFromCart(lineItemKey)"></a></td>
                    </tr>
                    <tr>
                        <th class="rightalign">Items Price</th>
                        <td class="rightalign"><strong>{{ remoteCart.itemTotal|currency }}</strong></td>
                        <td class="thin"></td>
                    </tr>
                    <tr>
                        <th class="rightalign">Total Price</th>
                        <td class="rightalign"><strong>{{ remoteCart.totalPrice|currency }}</strong></td>
                        <td class="thin"></td>
                    </tr>
                    </tbody>
                </table>

                <p>Renew for 3 years and save $XX.00</p>

                <p><a @click="payment()" class="btn submit">{{ "Process My Order"|t('app') }}</a></p>
            </template>

            <div v-else>
                <p>{{ "Your cart is empty."|t('app') }} <a @click="$emit('continue-shopping')">{{ "Continue shopping"|t('app') }}</a></p>
            </div>
        </template>

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
                <tr v-for="plugin in pendingActiveTrials">
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
                            <strong>{{ plugin.editions[0].price|currency }}</strong>
                            <div class="light">{{ plugin.editions[0].renewalPrice|currency }} per year for updates</div>
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
                activeTrialPlugins: 'activeTrialPlugins',
                cartTotal: 'cartTotal',
                remoteCart: 'remoteCart',
            }),

            pendingActiveTrials() {
                return this.activeTrialPlugins.filter(p => {
                    if(p) {
                        return !this.remoteCart.lineItems.find(item => {
                            return item.purchasable.pluginId == p.id;
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
                    edition: 'standard',
                    autoRenew: true,
                }

                this.$store.dispatch('addToCart', [item])
            },

            addAllToCart () {
                let $store = this.$store
                let items = []

                this.pendingActiveTrials.forEach(activeTrialPlugin => {
                    items.push({
                        type: 'plugin-edition',
                        plugin: activeTrialPlugin.handle,
                        edition: 'standard',
                        autoRenew: true,
                    })
                })

                $store.dispatch('addToCart', items)
            },

            payment() {
                this.$root.openGlobalModal('payment');
            }

        },

    }
</script>
