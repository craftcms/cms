<template v-if="pendingActiveTrials && pendingActiveTrials.length > 0">
    <div class="border-t border-solid border-grey-lighter mt-6 pt-6">
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
            <active-trials-table-row :plugin="plugin"></active-trials-table-row>
            </tbody>
        </table>
    </div>
</template>

<script>
import {mapState, mapGetters} from 'vuex'
import ActiveTrialsTableRow from './ActiveTrialsTableRow';

export default {
    components: {
        ActiveTrialsTableRow,
    },

    computed: {
        ...mapState({
            activeTrialPlugins: state => state.cart.activeTrialPlugins,
            cart: state => state.cart.cart,
        }),

        ...mapGetters({
            getActiveTrialPluginEdition: 'cart/getActiveTrialPluginEdition',
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
        addAllToCart() {
            let $store = this.$store
            let items = []

            this.pendingActiveTrials.forEach(plugin => {
                const edition = this.getActiveTrialPluginEdition(plugin)

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
    }
}
</script>