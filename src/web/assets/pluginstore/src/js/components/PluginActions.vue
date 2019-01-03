<template>
    <div v-if="plugin" class="plugin-actions">
        <template v-if="!hasFreeEdition(plugin)">
            <template v-if="isPluginInstalled(plugin.handle)">
                <!-- Installed -->
                <template v-if="pluginHasLicenseKey(plugin.handle) && pluginHasValidLicenseKey(plugin.handle)">
                    <license-status status="installed" :description="$options.filters.t('Installed', 'app')"></license-status>
                </template>
                <template v-else>
                    <!-- Installed as trial -->
                    <div class="mb-4">
                        <license-status status="installed" :description="$options.filters.t('Installed as a trial', 'app')"></license-status>
                    </div>

                    <!-- Added to cart -->
                    <btn v-if="isInCart(plugin)" type="primary" block large disabled>{{ "Added to cart"|t('app') }}</btn>

                    <!-- Add to cart -->
                    <btn v-else type="primary" @click="addEditionToCart(edition.handle)" block large>{{ "Add to cart"|t('app') }}</btn>
                </template>
            </template>

            <template v-else>
                <template v-if="allowUpdates">
                    <!-- Already in cart -->
                    <template v-if="isInCart(plugin, edition)">
                        <btn outline type="primary" @click="$root.openModal('cart')" block large>
                            <font-awesome-icon icon="check"></font-awesome-icon>
                            {{ "Already in your cart"|t('app') }}
                        </btn>
                    </template>

                    <!-- Add to cart -->
                    <template v-else>
                        <btn type="primary" :disabled="isPluginEditionFree(edition)" @click="addEditionToCart(edition.handle)" block large>{{ "Add to cart"|t('app') }}</btn>
                    </template>

                    <!-- Try -->
                    <form method="post" class="mt-3">
                        <input type="hidden" :name="csrfTokenName" :value="csrfTokenValue">
                        <input type="hidden" name="action" value="pluginstore/install">
                        <input type="hidden" name="packageName" :value="plugin.packageName">
                        <input type="hidden" name="handle" :value="plugin.handle">
                        <input type="hidden" name="version" :value="plugin.version">
                        <btn-input :value="'Try'|t('app')" block large></btn-input>
                    </form>
                </template>
            </template>
        </template>
        <div v-else>
            <!-- Installed -->
            <a v-if="isPluginInstalled(plugin.handle)" class="btn submit disabled">{{ "Installed"|t('app') }}</a>

            <!-- Install -->
            <div v-else-if="allowUpdates">
                <form method="post">
                    <input type="hidden" :name="csrfTokenName" :value="csrfTokenValue">
                    <input type="hidden" name="action" value="pluginstore/install">
                    <input type="hidden" name="packageName" :value="plugin.packageName">
                    <input type="hidden" name="handle" :value="plugin.handle">
                    <input type="hidden" name="version" :value="plugin.version">
                    <btn-input :value="'Install'|t('app')" type="primary" block large></btn-input>
                </form>
            </div>
        </div>

        <div class="spinner" v-if="loading"></div>
    </div>
</template>

<script>
    import {mapGetters} from 'vuex'
    import LicenseStatus from './LicenseStatus'

    export default {

        props: ['plugin', 'edition'],

        components: {
            LicenseStatus,
        },

        data() {
            return {
                loading: false,
            }
        },

        computed: {

            ...mapGetters({
                isPluginInstalled: 'craft/isPluginInstalled',
                hasFreeEdition: 'pluginStore/hasFreeEdition',
                isInCart: 'cart/isInCart',
                pluginHasLicenseKey: 'craft/pluginHasLicenseKey',
                pluginHasValidLicenseKey: 'craft/pluginHasValidLicenseKey',
                isPluginEditionFree: 'pluginStore/isPluginEditionFree',
            }),

            buyBtnTitle() {
                let price = 0

                if (this.plugin) {
                    price = this.plugin.editions[0].price
                }

                return this.$root.$options.filters.t('Buy now for {price}', 'app', {
                    price: this.$root.$options.filters.currency(price)
                })
            },

            allowUpdates() {
                return window.allowUpdates
            },

            csrfTokenName() {
                return Craft.csrfTokenName
            },

            csrfTokenValue() {
                return Craft.csrfTokenValue
            },

        },

        methods: {

            addEditionToCart(editionHandle) {
                this.loading = true

                const item = {
                    type: 'plugin-edition',
                    plugin: this.plugin.handle,
                    edition: editionHandle,
                    autoRenew: false,
                    cmsLicenseKey: window.cmsLicenseKey,
                }

                this.$store.dispatch('cart/addToCart', [item])
                    .then(() => {
                        this.loading = false
                        this.$root.openModal('cart')
                    })
            },

        }

    }
</script>

<style lang="scss">
    .plugin-actions {
        position: relative;
        .spinner {
            position: absolute;
            bottom: -32px;
            left: 50%;
        }
    }
</style>