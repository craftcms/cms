<template>
    <div class="ps-container buy-plugin">
        <status-message v-if="loading" :message="statusMessage"></status-message>
    </div>
</template>

<script>
import {mapGetters} from 'vuex'
import StatusMessage from '../components/StatusMessage'

export default {
    data() {
        return {
            loading: false,
            statusMessage: null,
            activeTrialsLoaded: false,
            activeTrialsError: null,
        }
    },

    components: {
        StatusMessage,
    },

    computed: {
        ...mapGetters({
            pendingActiveTrials: 'cart/pendingActiveTrials',
        }),
    },

    methods: {
        buyAllTrials() {
            // load active trial plugins
            this.$store.dispatch('cart/getActiveTrials')
                .then(() => {
                    this.activeTrialsLoaded = true

                    // Add all trials to the cart
                    this.$store.dispatch('cart/addAllTrialsToCart')
                        .then(() => {
                            this.$root.displayNotice(this.$options.filters.t('Active trials added to the cart.', 'app'))

                            this.$router.push({path: '/'})
                            this.$root.openModal('cart')
                        })
                        .catch(() => {
                            this.$root.displayError(this.$options.filters.t('Couldn’t add all items to the cart.', 'app'))
                            this.$router.push({path: '/'})
                        })
                })
                .catch(() => {
                    this.activeTrialsError = this.$options.filters.t('Couldn’t load active trials.', 'app')
                    this.activeTrialsLoaded = true
                })
        }
    },

    mounted() {
        this.loading = true
        this.statusMessage = this.$options.filters.t("Loading Plugin Store…", 'app')

        if (this.$root.allDataLoaded) {
            this.buyAllTrials()
        } else {
            // wait for the cart to be ready
            this.$root.$on('allDataLoaded', function() {
                this.buyAllTrials()
            }.bind(this))
        }
    }
}
</script>

<style lang="scss">
.buy-plugin {
    .status-message {
        height: 100%;
    }
}
</style>
