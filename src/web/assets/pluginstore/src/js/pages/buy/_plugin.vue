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
            buyPlugin(pluginHandle, editionHandle) {
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
                            if (!editionHandle) {
                                editionHandle = plugin.editions[0].handle
                            }

                            const item = {
                                type: 'plugin-edition',
                                plugin: plugin.handle,
                                edition: editionHandle,
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

                if (!this.isPluginInstalled(plugin.handle)) {
                    return true
                }

                const pluginLicenseInfo = this.getPluginLicenseInfo(plugin.handle)

                if (!pluginLicenseInfo) {
                    return false
                }

                if (
                    pluginLicenseInfo.licenseKey &&
                    pluginLicenseInfo.licenseKeyStatus !== 'trial' &&
                    pluginLicenseInfo.licenseIssues.indexOf('mismatched') === -1) {
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

            const plugin = this.$route.params.plugin
            const edition = this.$route.params.edition


            if (this.$root.allDataLoaded) {
                this.buyPlugin(plugin, edition)
            } else {
                // wait for the cart to be ready
                this.$root.$on('allDataLoaded', function() {
                    this.buyPlugin(plugin, edition)
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
