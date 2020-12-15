<template>
    <div class="plugin-details ps-container">
        <template v-if="!loading && plugin">
            <!-- header -->
            <div class="plugin-details-header border-b border-solid border-grey-lighter tw-flex mb-6 pb-6 items-center">
                <div class="plugin-icon">
                    <img v-if="plugin.iconUrl" :src="plugin.iconUrl" width="100" />
                    <img v-else :src="defaultPluginSvg" width="100" />
                </div>

                <div class="description flex-1">
                    <h1 class="text-lg font-bold mb-2">{{ plugin.name }}</h1>
                    <p class="mb-2 text-grey-dark">{{ plugin.shortDescription }}</p>
                    <p class="mb-2"><router-link :to="'/developer/' + plugin.developerId" :title="plugin.developerName">{{ plugin.developerName }}</router-link></p>
                </div>

                <div v-if="actionsLoading">
                    <spinner></spinner>
                </div>
            </div>

            <!-- body -->
            <div class="plugin-details-body">
                <template v-if="!loading">
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
                                        <icon icon="book"></icon> {{ "Documentation"|t('app') }}
                                    </a>
                                </li>

                                <li><a :href="plugin.repository" rel="noopener" target="_blank"><icon icon="link" /> Repository</a></li>
                            </ul>

                        </div>
                    </div>

                    <hr>

                    <div class="py-8">
                        <plugin-editions :plugin="plugin"></plugin-editions>

                        <div v-if="licenseMismatched" class="mx-auto max-w-sm px-8">
                            <div class="tw-flex items-center">
                                <svg version="1.1"
                                     xmlns="http://www.w3.org/2000/svg"
                                     x="0px" y="0px" viewBox="0 0 256 448"
                                     xml:space="preserve"
                                     class="text-blue fill-current w-8 h-8 mr-4 flex items-center">
<path fill="currentColor" d="M184,144c0,4.2-3.8,8-8,8s-8-3.8-8-8c0-17.2-26.8-24-40-24c-4.2,0-8-3.8-8-8s3.8-8,8-8C151.2,104,184,116.2,184,144z
M224,144c0-50-50.8-80-96-80s-96,30-96,80c0,16,6.5,32.8,17,45c4.8,5.5,10.2,10.8,15.2,16.5C82,226.8,97,251.8,99.5,280h57
c2.5-28.2,17.5-53.2,35.2-74.5c5-5.8,10.5-11,15.2-16.5C217.5,176.8,224,160,224,144z M256,144c0,25.8-8.5,48-25.8,67
s-40,45.8-42,72.5c7.2,4.2,11.8,12.2,11.8,20.5c0,6-2.2,11.8-6.2,16c4,4.2,6.2,10,6.2,16c0,8.2-4.2,15.8-11.2,20.2
c2,3.5,3.2,7.8,3.2,11.8c0,16.2-12.8,24-27.2,24c-6.5,14.5-21,24-36.8,24s-30.2-9.5-36.8-24c-14.5,0-27.2-7.8-27.2-24
c0-4,1.2-8.2,3.2-11.8c-7-4.5-11.2-12-11.2-20.2c0-6,2.2-11.8,6.2-16c-4-4.2-6.2-10-6.2-16c0-8.2,4.5-16.2,11.8-20.5
c-2-26.8-24.8-53.5-42-72.5S0,169.8,0,144C0,76,64.8,32,128,32S256,76,256,144z"/>
</svg>
                                <div>
                                    <div v-html="licenseMismatchedMessage"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="max-w-xs mx-auto py-8">
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
                                        <strong><router-link :to="'/categories/' + category.id" :title="category.title">{{ category.title }}</router-link></strong>
                                    </div>
                                </div>
                            </li>
                            <li><span>{{ "License"|t('app') }}</span> <strong>{{ licenseLabel }}</strong></li>
                        </ul>
                    </div>

                    <p>
                        <a :href="'mailto:issues@craftcms.com?subject=' + encodeURIComponent('Issue with ' + plugin.name) + '&body=' + encodeURIComponent('I would like to report the following issue with '+plugin.name+' (https://plugins.craftcms.com/' + plugin.handle + '):\n\n')"><icon icon="exclamation-circle" class="mr-2" />{{ "Report an issue"|t('app') }}</a>
                    </p>

                    <hr>

                    <plugin-changelog :pluginId="plugin.id"></plugin-changelog>
                </template>
                <template v-else>
                    <spinner></spinner>
                </template>
            </div>
        </template>
        <template v-else>
            <spinner></spinner>
        </template>
    </div>
</template>

<script>
    /* global Craft */

    import {mapState, mapGetters, mapActions} from 'vuex'
    import CopyPackage from '../../components/CopyPackage'
    import PluginChangelog from '../../components/PluginChangelog'
    import PluginEditions from '../../components/PluginEditions'
    import PluginScreenshots from '../../components/PluginScreenshots'
    import licensesMixin from '../../mixins/licenses'

    export default {
        mixins: [licensesMixin],

        components: {
            CopyPackage,
            PluginChangelog,
            PluginEditions,
            PluginScreenshots,
        },

        data() {
            return {
                actionsLoading: false,
                loading: false,
            }
        },

        computed: {
            ...mapState({
                categories: state => state.pluginStore.categories,
                defaultPluginSvg: state => state.craft.defaultPluginSvg,
                plugin: state => state.pluginStore.plugin,
                showingScreenshotModal: state => state.app.showingScreenshotModal,
            }),

            ...mapGetters({
                getPluginEdition: 'pluginStore/getPluginEdition',
                getPluginLicenseInfo: 'craft/getPluginLicenseInfo',
            }),

            longDescription() {
                if (this.plugin.longDescription && this.plugin.longDescription.length > 0) {
                    return this.plugin.longDescription
                }

                return null
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

                return null
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

            licenseMismatchedMessage() {
                return this.$options.filters.t('This license is tied to another Craft install. Visit {accountLink} to detach it, or buy a new license.', 'app', {
                    accountLink: '<a href="https://id.craftcms.com" rel="noopener" target="_blank">id.craftcms.com</a>',
                })
            }
        },

        methods: {
            ...mapActions({
                addToCart: 'cart/addToCart'
            }),
        },

        mounted() {
            const pluginHandle = this.$route.params.handle

            this.loading = true

            this.$store.dispatch('pluginStore/getPluginDetailsByHandle', pluginHandle)
                .then(() => {
                    this.loading = false
                })
                .catch(() => {
                    this.loading = false
                })
        },

        beforeDestroy() {
            this.$store.dispatch('pluginStore/cancelRequests')
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
    @import "../../../../../../../../node_modules/craftcms-sass/mixins";

    .plugin-icon {
        @apply .relative;
        @include margin-right(1.5rem); // .mr-6
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
