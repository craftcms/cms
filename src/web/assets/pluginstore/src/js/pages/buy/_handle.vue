<template>
    <div class="ps-container buy-plugin">
        <status-message v-if="loading" :message="statusMessage"></status-message>
    </div>
</template>

<script>
    import {mapGetters} from 'vuex'
    import pluginStoreApi from '../../api/pluginstore'
    import StatusMessage from '../../components/StatusMessage'

    export default {
        data() {
            return {
                loading: false,
                statusMessage: null,
            }
        },

        components: {
            StatusMessage,
        },

        methods: {
            buyPlugin(pluginHandle) {
                pluginStoreApi.getPluginDetailsByHandle(pluginHandle)
                    .then(responseData => {
                        const plugin = responseData

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
                            }

                            this.$store.dispatch('cart/addToCart', [item])
                                .then(() => {
                                    this.loading = false
                                    this.$router.push({path: '/'})
                                    this.$root.openModal('cart')
                                })
                                .catch((error) => {
                                    throw error
                                })
                        }
                    })
                    .catch((error) => {
                        throw error
                    })
            },

            isPluginBuyable(plugin) {
                const price = plugin.editions[0].price

                if (price === null) {
                    return false
                }

                if (parseFloat(price) === 0) {
                    return false
                }

                const pluginLicenseInfo = this.getPluginLicenseInfo(plugin.handle)

                if (this.isPluginInstalled(plugin.handle) && (!pluginLicenseInfo || (pluginLicenseInfo && pluginLicenseInfo.licenseKey))) {
                    return false
                }

                return true
            },
        },

        computed: {
            ...mapGetters({
                isInCart: 'cart/isInCart',
                isPluginInstalled: 'craft/isPluginInstalled',
                getPluginLicenseInfo: 'craft/getPluginLicenseInfo',
            }),
        },

        mounted() {
            this.loading = true
            this.statusMessage = this.$options.filters.t("Loading Plugin Store…", 'app')

            // retrieve plugin
            const pluginHandle = this.$route.params.handle

            if (this.$root.allDataLoaded) {
                // buy plugin
                this.buyPlugin(pluginHandle)
            } else {
                // wait for the cart to be ready
                this.$root.$on('allDataLoaded', function() {
                    // buy plugin
                    this.buyPlugin(pluginHandle)
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
