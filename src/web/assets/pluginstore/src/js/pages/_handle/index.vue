<template>
    <div v-if="pluginSnippet" class="plugin-details ps-container">

        <!-- header -->
        <div class="plugin-details-header border-b border-solid border-grey-lighter tw-flex mb-6 pb-6 items-center">
            <div class="plugin-icon">
                <img v-if="pluginSnippet.iconUrl" :src="pluginSnippet.iconUrl" width="100" />
                <img v-else :src="defaultPluginSvg" width="100" />

                <div v-if="showLicenseKeyStatus" class="license-key-status" :class="{valid: isLicenseValid}"></div>
            </div>

            <div class="description flex-1">
                <h2>{{ pluginSnippet.name }}</h2>
                <p>{{ pluginSnippet.shortDescription }}</p>
                <p><a @click="viewDeveloper(pluginSnippet)">{{ pluginSnippet.developerName }}</a></p>
            </div>

            <div v-if="actionsLoading">
                <div class="spinner"></div>
            </div>
        </div>

        <!-- body -->
        <div class="plugin-details-body">
            <template v-if="!loading">

                <template v-if="pluginLicenseInfo && pluginLicenseInfo.licenseIssues.length > 0">
                    <ul>
                        <li v-for="(errorCode, key) in pluginLicenseInfo.licenseIssues" class="error" :key="'license-issue' + key">
                            {{licenseIssue(errorCode)}}
                        </li>
                    </ul>

                    <hr>
                </template>

                <template v-if="plugin.screenshotUrls && plugin.screenshotUrls.length">
                    <plugin-screenshots :images="plugin.screenshotUrls"></plugin-screenshots>

                    <hr>
                </template>

                <div class="lg:flex">
                    <div class="lg:flex-1 lg:pr-8 lg:mr-4">
                        <div v-if="longDescription" v-html="longDescription" class="readable"></div>
                        <div v-else-if="plugin.shortDescription" v-html="plugin.shortDescription" class="readable"></div>
                        <p v-else>No description.</p>
                    </div>
                    <div class="lg:pl-8 lg:ml-4">
                        <ul>
                            <li v-if="plugin.documentationUrl" class="py-1">
                                <a :href="plugin.documentationUrl" rel="noopener" target="_blank">
                                    <icon icon="book" />
                                    {{ "Documentation"|t('app') }}
                                </a>
                            </li>

                            <li><a :href="plugin.repository"><icon icon="link" /> Repository</a></li>
                        </ul>

                    </div>
                </div>

                <hr>

                <div class="py-8">
                    <plugin-editions :plugin="plugin"></plugin-editions>
                </div>

                <hr>

                <div class="max-w-sm mx-auto p-8">
                    <h2 class="mt-0">{{ "Package Name"|t('app') }}</h2>
                    <p>{{ "Copy the package’s name for this plugin."|t('app') }}</p>
                    <copy-package :plugin="plugin"></copy-package>
                </div>

                <hr>

                <h2 class="mb-4">{{ "Information"|t('app') }}</h2>
                <div class="plugin-infos">
                    <ul class="plugin-meta">
                        <li><span>{{ "Version"|t('app') }}</span> <strong>{{ plugin.version }}</strong></li>
                        <li><span>{{ "Last update"|t('app') }}</span> <strong>{{ lastUpdate }}</strong></li>
                        <li v-if="plugin.activeInstalls > 0"><span>{{ "Active installs"|t('app') }}</span> <strong>{{ plugin.activeInstalls|formatNumber }}</strong></li>
                        <li><span>{{ "Compatibility"|t('app') }}</span> <strong>{{ plugin.compatibility }}</strong></li>
                        <li v-if="pluginCategories && pluginCategories.length > 0">
                            <span>{{ "Categories"|t('app') }}</span>
                            <div>
                                <div v-for="(category, key) in pluginCategories" :key="'plugin-category-' + key">
                                    <strong><a @click="viewCategory(category)">{{ category.title }}</a></strong>
                                </div>
                            </div>
                        </li>
                        <li><span>{{ "License"|t('app') }}</span> <strong>{{ licenseLabel }}</strong></li>
                    </ul>
                </div>

                <p>
                    <a :href="'mailto:issues@craftcms.com?subject=' + encodeURIComponent('Issue with ' + plugin.name) + '&body=' + encodeURIComponent('I would like to report the following issue with '+plugin.name+' (https://plugins.craftcms.com/' + plugin.handle + '):\n\n')">{{ "Report an issue"|t('app') }}</a>
                </p>

                <hr>

                <plugin-changelog :pluginId="$root.pluginId"></plugin-changelog>
            </template>
            <template v-else>
                <div class="plugin-details-loading spinner"></div>
            </template>
        </div>
    </div>
</template>

<script>
    /* global Craft */

    import {mapState, mapGetters, mapActions} from 'vuex'
    import PluginScreenshots from '../../components/PluginScreenshots'
    import PluginEditions from '../../components/PluginEditions'
    import PluginChangelog from '../../components/PluginChangelog'
    import CopyPackage from '../../components/CopyPackage'

    export default {

        components: {
            PluginScreenshots,
            PluginEditions,
            PluginChangelog,
            CopyPackage,
        },

        data() {
            return {
                actionsLoading: false,
                loading: false,
                pluginSnippet: null,
            }
        },

        computed: {

            ...mapState({
                categories: state => state.pluginStore.categories,
                plugin: state => state.pluginStore.plugin,
                plugins: state => state.pluginStore.plugins,
                defaultPluginSvg: state => state.craft.defaultPluginSvg,
                showingScreenshotModal: state => state.app.showingScreenshotModal,
            }),

            ...mapGetters({
                getPluginLicenseInfo: 'craft/getPluginLicenseInfo',
                getPluginEdition: 'pluginStore/getPluginEdition',
            }),

            longDescription() {
                if (this.plugin.longDescription && this.plugin.longDescription.length > 0) {
                    return this.plugin.longDescription
                }
            },

            developerUrl() {
                return Craft.getCpUrl('plugin-store/developer/' + this.plugin.developerId)
            },

            pluginCategories() {
                return this.categories.filter(c => {
                    return this.plugin.categoryIds.find(pc => pc == c.id)
                })
            },

            licenseLabel() {
                switch (this.plugin.license) {
                    case 'craft':
                        return 'Craft'

                    case 'mit':
                        return 'MIT'
                }
            },

            lastUpdate() {
                const date = new Date(this.plugin.lastUpdate.replace(/\s/, 'T'))
                return Craft.formatDate(date)
            },

            pluginLicenseInfo() {
                if (!this.plugin) {
                    return null
                }

                return this.getPluginLicenseInfo(this.plugin.handle)
            },

            isLicenseValid() {
                return this.pluginLicenseInfo && this.pluginLicenseInfo.licenseKeyStatus === 'valid' && this.pluginLicenseInfo.licenseIssues.length === 0
            },

            showLicenseKeyStatus() {
                return !this.loading && this.pluginLicenseInfo && this.pluginLicenseInfo.isInstalled && this.pluginLicenseInfo.licenseKey;
            }

        },

        watch: {

            pluginId(pluginId) {
                this.loadPlugin(pluginId)
                return pluginId
            }

        },

        methods: {

            ...mapActions({
                addToCart: 'cart/addToCart'
            }),

            viewDeveloper(plugin) {
                this.$root.closeModal()
                this.$router.push({path: '/developer/' + plugin.developerId})
            },

            viewCategory(category) {
                this.$root.closeModal()
                this.$router.push({path: '/categories/' + category.id})
            },

            loadPlugin(pluginId) {
                this.pluginSnippet = this.$store.getters['pluginStore/getPluginById'](pluginId)

                if(!this.plugin || (this.plugin && this.plugin.id !== pluginId)) {
                    this.loading = true
                    this.$store.commit('pluginStore/updatePluginDetails', null)
                    this.$store.dispatch('pluginStore/getPluginDetails', pluginId)
                        .then(() => {
                            this.loading = false
                        })
                        .catch(() => {
                            this.loading = false
                        })
                }
            },

            licenseIssue(errorCode) {
                switch (errorCode) {
                    case 'wrong_edition': {
                        const currentEdition = this.getPluginEdition(this.plugin.handle, this.pluginLicenseInfo.edition)
                        const licensedEdition = this.getPluginEdition(this.plugin.handle, this.pluginLicenseInfo.licensedEdition)

                        return this.$options.filters.t('Your are currently using the {currentEdition} edition, and your licensed edition is {licensedEdition}.', 'app', {
                            currentEdition: currentEdition.name,
                            licensedEdition: licensedEdition.name,
                        })
                    }

                    case 'mismatched': {
                        return this.$options.filters.t('This license is tied to another Craft install. Purchase a license for this install.', 'app')
                    }

                    default: {
                        return this.$options.filters.t('Your license key is invalid.', 'app')
                    }
                }
            },

        },

        mounted() {
            const pluginHandle = this.$route.params.handle
            const plugin = this.$store.getters['pluginStore/getPluginByHandle'](pluginHandle)

            if (plugin) {
                this.$root.pluginId = plugin.id
                this.loadPlugin(plugin.id)
            } else {
                this.$router.push({path: '/'})
            }
        },

        beforeRouteLeave(to, from, next) {
            if (this.showingScreenshotModal) {
                this.$store.commit('app/updateShowingScreenshotModal', false)
            } else {
                next()
            }
        }

    }
</script>

<style lang="scss">
    @import "../../../sass/variables";
    @import "../../../../../../../../lib/craftcms-sass/mixins";

    .plugin-icon {
        @apply .relative;
        @include margin-right(1.5rem); // .mr-6

        .license-key-status {
            @apply .block .absolute;
            bottom: 0px;
            right: 0;
            width: 32px;
            height: 32px;
            background: no-repeat 0 0 url(~@/images/invalid-icon.svg);
            background-size: 100% 100%;
            
            &.valid {
                background-image: url(~@/images/valid-icon.svg);
            }
        }
    }


    /* Plugin Meta */

    ul.plugin-meta {
        @apply .-mx-4 .flex .flex-wrap;

        li {
            @apply .mb-8 .px-4 .flex-no-shrink .flex-no-grow;
            flex-basis: 50%;

            span {
                @apply .block .text-grey;
            }
        }
    }

    @media only screen and (min-width: 672px) {
        ul.plugin-meta {
            li {
                flex-basis: 33.3333%;
            }
        }
    }

    @media only screen and (min-width: 1400px) {
        ul.plugin-meta {
            li {
                flex-basis: 25%;
            }
        }
    }

    @media only screen and (min-width: $minLargeScreenWidth) {
        ul.plugin-meta {
            li {
                flex-basis: 20%;
            }
        }
    }

</style>