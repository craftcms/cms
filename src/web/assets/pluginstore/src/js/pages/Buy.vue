<template>
    <div>
        <status-message v-if="loading" :message="statusMessage" />
    </div>
</template>

<script>
    import {mapGetters} from 'vuex'

    export default {

        data() {
            return {
                loading: false,
                statusMessage: null,
            }
        },

        components: {
            StatusMessage: require('../components/StatusMessage'),
        },

        methods: {

            buyPlugin(plugin) {
                if (!this.isPluginBuyable(plugin)) {
                    this.loading = false
                    this.$router.push({path: '/'})
                    return;
                }

                if(this.isInCart(plugin)) {
                    this.$router.push({path: '/'})
                    this.$root.openModal('cart')
                } else {
                    const item = {
                        type: 'plugin-edition',
                        plugin: plugin.handle,
                        edition: plugin.editions[0].handle,
                        autoRenew: false,
                        cmsLicenseKey: window.cmsLicenseKey,
                    }

                    this.$store.dispatch('addToCart', [item])
                        .then(() => {
                            this.loading = false
                            this.$router.push({path: '/'})
                            this.$root.openModal('cart')
                        })
                }
            },

            isPluginBuyable(plugin) {
                if (plugin.editions[0].price !== null && plugin.editions[0].price !== '0.00') {
                    if (this.isInstalled(plugin)) {
                        if(!this.pluginHasLicenseKey(plugin.handle)) {
                            return true
                        }
                    } else {
                        return true
                    }
                }

                return false
            },

        },

        computed: {

            ...mapGetters({
                isInCart: 'isInCart',
                isInstalled: 'isInstalled',
                pluginHasLicenseKey: 'pluginHasLicenseKey',
            }),

        },

        mounted() {
            this.loading = true
            this.statusMessage = this.$options.filters.t("Loading Plugin Store…", 'app')

            // retrieve plugin
            const pluginHandle = this.$route.params.pluginHandle
            const plugin = this.$store.getters.getPluginByHandle(pluginHandle)

            if (this.$root.pluginStoreDataLoaded && this.$root.craftIdDataLoaded && this.$root.cartDataLoaded) {
                // buy plugin
                this.buyPlugin(plugin)
            } else {
                // wait for the cart to be ready
                this.$root.$on('allDataLoaded', function() {
                    // buy plugin
                    this.buyPlugin(plugin)
                }.bind(this))
            }
        }

    }
</script>