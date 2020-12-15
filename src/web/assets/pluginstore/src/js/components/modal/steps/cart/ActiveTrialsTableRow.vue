<template>
    <tr v-if="plugin">
        <td class="thin">
            <div class="plugin-icon">
                <img v-if="plugin.iconUrl" :src="plugin.iconUrl" height="40" width="40" />
                <div class="default-icon" v-else></div>
            </div>
        </td>
        <td class="item-name">
            <a :title="plugin.name" @click.prevent="navigateToPlugin"><strong>{{ plugin.name }}</strong></a>

            <edition-badge v-if="activeTrialPluginEdition && plugin.editions.length > 1" :name="activeTrialPluginEdition.name"></edition-badge>
        </td>
        <td>
            <template v-if="activeTrialPluginEdition">
                <template v-if="licensedEdition && licensedEdition.handle !== activeTrialPluginEdition.handle && licensedEdition.price > 0 && licenseValidOrAstray">
                    <del class="mr-1">{{activeTrialPluginEdition.price|currency}}</del>
                    <strong>{{(activeTrialPluginEdition.price - licensedEdition.price)|currency}}</strong>
                </template>
                <template v-else>
                    <strong>{{activeTrialPluginEdition.price|currency}}</strong>
                </template>
            </template>
        </td>
        <td class="w-1/4">
            <div class="text-right">
                <template v-if="!activeTrialLoading">
                    <a @click="addToCart(plugin, pluginLicenseInfo.edition)" :loading="activeTrialLoading" :class="{
                        'disabled hover:no-underline': licenseMismatched
                    }">{{ "Add to cart"|t('app') }}</a>
                </template>
                <template v-else>
                    <spinner size="sm"></spinner>
                </template>
            </div>
        </td>
    </tr>
</template>

<script>
    import {mapGetters} from 'vuex'
    import EditionBadge from '../../../EditionBadge';
    import licensesMixin from '../../../../mixins/licenses'


    export default {
        mixins: [licensesMixin],

        components: {EditionBadge},

        props: ['plugin'],

        data() {
            return {
                activeTrialLoading: false,
            }
        },

        computed: {
            ...mapGetters({
                getPluginEdition: 'pluginStore/getPluginEdition',
                getPluginLicenseInfo: 'craft/getPluginLicenseInfo',
            }),

            activeTrialPluginEdition() {
                const pluginLicenseInfo = this.getPluginLicenseInfo(this.plugin.handle)
                const edition = this.getPluginEdition(this.plugin, pluginLicenseInfo.edition)

                return edition
            },

            pluginLicenseInfo() {
                return this.getPluginLicenseInfo(this.plugin.handle)
            },

            licensedEdition() {
                if (!this.pluginLicenseInfo) {
                    return null
                }

                return this.getPluginEdition(this.plugin, this.pluginLicenseInfo.licensedEdition)
            },
        },

        methods: {
            addToCart(plugin, editionHandle) {
                if (this.licenseMismatched) {
                    return false
                }

                this.activeTrialLoading = true

                const item = {
                    type: 'plugin-edition',
                    plugin: plugin.handle,
                    edition: editionHandle
                }

                this.$store.dispatch('cart/addToCart', [item])
                    .then(() => {
                        this.activeTrialLoading = false
                    })
                    .catch(response => {
                        this.activeTrialLoading = false
                        const errorMessage = response.errors && response.errors[0] && response.errors[0].message ? response.errors[0].message : 'Couldn’t add item to cart.';
                        this.$root.displayError(errorMessage)
                    })
            },

            navigateToPlugin() {
                const path = '/' + this.plugin.handle

                this.$root.closeModal()

                if (this.$route.path !== path) {
                    this.$router.push({path})
                }
            }
        }
    }
</script>