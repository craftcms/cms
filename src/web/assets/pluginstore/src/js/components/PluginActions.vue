<template>
    <div v-if="plugin" class="plugin-actions">
        <template v-if="!isPluginEditionFree">
            <template v-if="isInCart(plugin, edition)">
                <!-- Already in cart -->
                <btn v-if="allowUpdates" outline type="primary" @click="$root.openModal('cart')" block large><font-awesome-icon icon="check"></font-awesome-icon> {{ "Already in your cart"|t('app') }}</btn>
            </template>

            <template v-else>
                <!-- Add to cart / Upgrade (from lower edition) -->
                <btn v-if="allowUpdates && isPluginEditionBuyable" type="primary" @click="addEditionToCart(edition.handle)" block large>{{ "Add to cart"|t('app') }}</btn>

                <!-- Licensed -->
                <btn v-else-if="pluginLicenseInfo.licensedEdition === edition.handle" type="primary" block large disabled>{{ "Licensed"|t('app') }}</btn>
            </template>
        </template>

        <!-- Install/Try -->
        <template v-if="!isPluginInstalled || (isPluginInstalled && pluginLicenseInfo.edition !== edition.handle)">
            <form v-if="allowUpdates" method="post" @submit="onSwitchOrInstallSubmit">
                <input type="hidden" :name="csrfTokenName" :value="csrfTokenValue">

                <template v-if="isPluginInstalled">
                    <!-- Switch -->
                    <input type="hidden" name="action" value="plugins/switch-edition">
                    <input type="hidden" name="pluginHandle" :value="plugin.handle">
                    <input type="hidden" name="edition" :value="edition.handle">
                </template>
                <template v-else>
                    <!-- Install -->
                    <input type="hidden" name="action" value="pluginstore/install">
                    <input type="hidden" name="packageName" :value="plugin.packageName">
                    <input type="hidden" name="handle" :value="plugin.handle">
                    <input type="hidden" name="version" :value="plugin.version">
                </template>

                <!-- Install (Free) -->
                <btn-input v-if="isPluginEditionFree" :value="'Install'|t('app')" type="primary" block large></btn-input>

                <template v-else>
                    <template v-if="(isPluginEditionBuyable && pluginLicenseInfo.edition === edition.handle) || (pluginLicenseInfo.licensedEdition === edition.handle && !pluginLicenseInfo.edition)">
                        <!-- Install (Commercial) -->
                        <btn-input :value="'Install'|t('app')" block large></btn-input>
                    </template>

                    <template v-else-if="isPluginEditionBuyable && pluginLicenseInfo.edition !== edition.handle">
                        <!-- Try -->
                        <btn-input :value="'Try'|t('app')" :disabled="!pluginLicenseInfo.isInstalled || !pluginLicenseInfo.isEnabled" block large></btn-input>
                    </template>

                    <template v-else-if="pluginLicenseInfo.licensedEdition === edition.handle && pluginLicenseInfo.edition && pluginLicenseInfo.edition !== edition.handle">
                        <!-- Reactivate -->
                        <btn-input :value="'Reactivate'|t('app')" block large></btn-input>
                    </template>
                </template>
            </form>
        </template>

        <template v-else>
                <template v-if="pluginLicenseInfo.edition !== pluginLicenseInfo.licensedEdition && !isPluginEditionFree">
                    <!-- Installed as a trial -->
                    <btn-input :value="'Installed as a trial'|t('app')" block large disabled></btn-input>
                </template>

                <template v-else>
                    <!-- Installed -->
                    <btn-input :value="'Installed'|t('app')" block large disabled></btn-input>
                </template>
        </template>

        <div class="spinner" v-if="loading"></div>
    </div>
</template>

<script>
    /* global Craft */

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
                getPluginLicenseInfo: 'craft/getPluginLicenseInfo',
                isInCart: 'cart/isInCart',
            }),

            pluginLicenseInfo() {
                return this.getPluginLicenseInfo(this.plugin.handle)
            },

            isPluginEditionFree() {
                return this.$store.getters['pluginStore/isPluginEditionFree'](this.edition)
            },

            isPluginInstalled() {
                return this.$store.getters['craft/isPluginInstalled'](this.plugin.handle)
            },

            isPluginEditionBuyable() {
                // A plugin edition is buyable if it’s more expensive than the licensed one
                if(!this.edition) {
                    return false
                }

                const licensedEditionHandle = this.pluginLicenseInfo.licensedEdition
                const licensedEdition = this.plugin.editions.find(edition => edition.handle === licensedEditionHandle)

                if(licensedEdition && parseFloat(this.edition.price) <= parseFloat(licensedEdition.price)) {
                    return false
                }

                return true
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
                }

                this.$store.dispatch('cart/addToCart', [item])
                    .then(() => {
                        this.loading = false
                        this.$root.openModal('cart')
                    })
            },

            onSwitchOrInstallSubmit($ev) {
                this.loading = true

                if (this.isPluginInstalled) {
                    // Switch (prevent form submit)

                    $ev.preventDefault()

                    this.$store.dispatch('craft/switchPluginEdition', {
                        pluginHandle: this.plugin.handle,
                        edition: this.edition.handle,
                    })
                        .then(() => {
                            this.loading = false
                            this.$root.displayNotice("Plugin edition changed.")
                        })

                    return false
                }

                // Install (don’t prevent form submit)
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

        .c-btn {
            @apply .mt-3;
        }
    }
</style>