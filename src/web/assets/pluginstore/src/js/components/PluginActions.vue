<template>
    <div v-if="plugin" class="plugin-actions">
        <template v-if="!isPluginEditionFree">
            <template v-if="isInCart(plugin, edition)">
                <!-- Already in cart -->
                <btn v-if="allowUpdates" kind="primary" icon="check" block large outline @click="$root.openModal('cart')">{{ "Already in your cart"|t('app') }}</btn>
            </template>

            <template v-else>
                <!-- Add to cart / Upgrade (from lower edition) -->
                <btn v-if="allowUpdates && isEditionMoreExpensiveThanLicensed" kind="primary" @click="addEditionToCart(edition.handle)" :loading="addToCartloading" :disabled="addToCartloading || !plugin.latestCompatibleVersion || licenseMismatched" block large>{{ "Add to cart"|t('app') }}</btn>

                <!-- Licensed -->
                <btn v-else-if="licensedEdition === edition.handle" kind="primary" block large disabled>{{ "Licensed"|t('app') }}</btn>
            </template>
        </template>

        <!-- Install/Try -->
        <template v-if="!isPluginInstalled || currentEdition !== edition.handle">
            <form v-if="allowUpdates || isPluginInstalled" method="post" @submit="onSwitchOrInstallSubmit">
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
                    <input type="hidden" name="version" :value="plugin.latestCompatibleVersion">
                </template>

                <!-- Install (Free) -->
                <template v-if="isPluginEditionFree">
                    <btn kind="primary" type="submit" :loading="loading" :disabled="!plugin.latestCompatibleVersion" block large>{{ "Install"|t('app') }}</btn>
                </template>

                <template v-else>
                    <template v-if="(isEditionMoreExpensiveThanLicensed && currentEdition === edition.handle) || (licensedEdition === edition.handle && !currentEdition)">
                        <!-- Install (Commercial) -->
                        <btn type="submit" :loading="loading" :disabled="!plugin.latestCompatibleVersion" block large>{{ "Install"|t('app') }}</btn>
                    </template>

                    <template v-else-if="isEditionMoreExpensiveThanLicensed && currentEdition !== edition.handle">
                        <!-- Try -->
                        <btn type="submit" :disabled="(!((pluginLicenseInfo && pluginLicenseInfo.isInstalled && pluginLicenseInfo.isEnabled) || !pluginLicenseInfo)) || !plugin.latestCompatibleVersion" :loading="loading" block large>{{ "Try"|t('app') }}</btn>
                    </template>

                    <template v-else-if="currentEdition && licensedEdition === edition.handle && currentEdition !== edition.handle">
                        <!-- Reactivate -->
                        <btn type="submit" :loading="loading" block large>{{ "Reactivate"|t('app') }}</btn>
                    </template>
                </template>
            </form>
        </template>

        <template v-else>
                <template v-if="currentEdition !== licensedEdition && !isPluginEditionFree">
                    <!-- Installed as a trial -->
                    <btn icon="check" :disabled="true" large block> {{ "Installed as a trial"|t('app') }}</btn>
                </template>

                <template v-else>
                    <!-- Installed -->
                    <btn icon="check" :disabled="true" block large> {{ "Installed"|t('app') }}</btn>
                </template>
        </template>

        <template v-if="plugin.latestCompatibleVersion && plugin.latestCompatibleVersion != plugin.version">
            <div class="text-grey mt-4 px-8">
                <p>{{ "Only up to {version} is compatible with your version of Craft."|t('app', {version: plugin.latestCompatibleVersion}) }}</p>
            </div>
        </template>
        <template v-else-if="!plugin.latestCompatibleVersion">
            <div class="text-grey mt-4 px-8">
                <p>{{ "This plugin isn’t compatible with your version of Craft."|t('app') }}</p>
            </div>
        </template>
    </div>
</template>

<script>
    /* global Craft */

    import {mapGetters} from 'vuex'
    import licensesMixin from '../mixins/licenses'

    export default {
        mixins: [licensesMixin],

        props: ['plugin', 'edition'],

        data() {
            return {
                loading: false,
                addToCartloading: false,
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
                return Craft.allowUpdates && Craft.allowAdminChanges
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
                this.addToCartloading = true

                const item = {
                    type: 'plugin-edition',
                    plugin: this.plugin.handle,
                    edition: editionHandle,
                }

                this.$store.dispatch('cart/addToCart', [item])
                    .then(() => {
                        this.addToCartloading = false
                        this.$root.openModal('cart')
                    })
                    .catch(() => {
                        this.addToCartloading = false
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
        .c-spinner {
            position: absolute;
            bottom: -32px;
            left: 50%;
        }

        .c-btn {
            @apply .mt-3;
        }
    }
</style>
