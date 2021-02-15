<template>
    <div v-if="pendingActiveTrials && pendingActiveTrials.length > 0" class="border-t border-solid border-grey-lighter mt-6 pt-6">
        <div v-if="pendingActiveTrials.length > 1" class="right">
            <a @click="addAllTrialsToCart()">{{ "Add all to cart"|t('app') }}</a>
        </div>

        <h2>{{ "Active Trials"|t('app') }}</h2>

        <table class="cart-data">
            <tbody v-for="(activeTrial, key) in pendingActiveTrials" :key="key">
            <active-trials-table-row :activeTrial="activeTrial"></active-trials-table-row>
            </tbody>
        </table>
    </div>
</template>

<script>
import {mapGetters} from 'vuex'
import licensesMixin from '../../../../mixins/licenses'
import ActiveTrialsTableRow from './ActiveTrialsTableRow';

export default {
    mixins: [licensesMixin],

    components: {
        ActiveTrialsTableRow,
    },

    computed: {
        ...mapGetters({
            getActiveTrialPluginEdition: 'cart/getActiveTrialPluginEdition',
            pendingActiveTrials: 'cart/pendingActiveTrials',
        }),
    },

    methods: {
        addAllTrialsToCart() {
            this.$store.dispatch('cart/addAllTrialsToCart')
                .catch(() => {
                    this.$root.displayError(this.$options.filters.t('Couldn’t add all items to the cart.', 'app'))
                })
        }
    }
}
</script>