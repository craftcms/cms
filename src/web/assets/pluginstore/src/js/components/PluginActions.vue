<template>
    <div v-if="plugin" class="plugin-actions">
        <template v-if="!isPluginEditionFree">
            <template v-if="isInCart(plugin, edition)">
                <!-- Already in cart -->
                <btn v-if="allowUpdates" outline type="primary" @click="$root.openModal('cart')" block large><icon icon="check" /> {{ "Already in your cart"|t('app') }}</btn>
            </template>

            <template v-else>
                <!-- Add to cart / Upgrade (from lower edition) -->
                <btn v-if="allowUpdates && isEditionMoreExpensiveThanLicensed" type="primary" @click="addEditionToCart(edition.handle)" block large>{{ "Add to cart"|t('app') }}</btn>

                <!-- Licensed -->
                <btn v-else-if="licensedEdition === edition.handle" type="primary" block large disabled>{{ "Licensed"|t('app') }}</btn>
            </template>
        </template>

        <!-- Install/Try -->
        <template v-if="!isPluginInstalled || (isPluginInstalled && currentEdition !== edition.handle)">
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
                    <input type="hidden" name="edition" :value="edition.handle">
                    <input type="hidden" name="version" :value="plugin.version">
                </template>

                <!-- Install (Free) -->
                <btn-input v-if="isPluginEditionFree" :value="'Install'|t('app')" type="primary" block large></btn-input>

                <template v-else>
                    <template v-if="(isEditionMoreExpensiveThanLicensed && currentEdition === edition.handle) || (licensedEdition === edition.handle && !currentEdition)">
                        <!-- Install (Commercial) -->
                        <btn-input :value="'Install'|t('app')" block large></btn-input>
                    </template>

                    <template v-else-if="isEditionMoreExpensiveThanLicensed && currentEdition !== edition.handle">
                        <!-- Try -->
                        <btn-input :value="'Try'|t('app')" :disabled="!((pluginLicenseInfo && pluginLicenseInfo.isInstalled && pluginLicenseInfo.isEnabled) || !pluginLicenseInfo)" block large></btn-input>
                    </template>

                    <template v-else-if="currentEdition && licensedEdition === edition.handle && currentEdition !== edition.handle">
                        <!-- Reactivate -->
                        <btn-input :value="'Reactivate'|t('app')" block large></btn-input>
                    </template>
                </template>
            </form>
        </template>

        <template v-else>
                <template v-if="currentEdition !== licensedEdition && !isPluginEditionFree">
                    <!-- Installed as a trial -->
                    <button class="c-btn block large" :disabled="true"><icon icon="check" /> {{ "Installed as a trial"|t('app') }}</button>
                </template>

                <template v-else>
                    <!-- Installed -->
                    <button class="c-btn block large" :disabled="true"><icon icon="check" /> {{ "Installed"|t('app') }}</button>
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

            isEditionMoreExpensiveThanLicensed() {
                // A plugin edition is buyable if it’s more expensive than the licensed one
                if(!this.edition) {
                    return false
                }

                if (this.pluginLicenseInfo) {
                    const licensedEditionHandle = this.licensedEdition
                    const licensedEdition = this.plugin.editions.find(edition => edition.handle === licensedEditionHandle)

                    if(licensedEdition && this.edition.price && parseFloat(this.edition.price) <= parseFloat(licensedEdition.price)) {
                        return false
                    }
                }

                return true
            },

            licensedEdition() {
                if (!this.pluginLicenseInfo) {
                    return null
                }

                return this.pluginLicenseInfo.licensedEdition
            },

            currentEdition() {
                if (!this.pluginLicenseInfo) {
                    return null
                }

                return this.pluginLicenseInfo.edition
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
