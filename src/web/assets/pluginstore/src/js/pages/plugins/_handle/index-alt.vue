<template>
    <div v-if="pluginSnippet" class="plugin-details ps-container">
        <div class="plugin-details-header border-b border-solid border-grey-lighter tw-flex mb-6 pb-6 items-center">
            <div class="plugin-icon mr-6">
                <img v-if="pluginSnippet.iconUrl" :src="pluginSnippet.iconUrl" width="100" />
                <img v-else :src="defaultPluginSvg" width="100" />
            </div>

            <div class="description flex-1">
                <h2>{{ pluginSnippet.name }}</h2>
                <p>{{ pluginSnippet.shortDescription }}</p>
                <p><a @click="viewDeveloper(pluginSnippet)">{{ pluginSnippet.developerName }}</a></p>
            </div>

            <div v-if="cart" class="buttons flex-no-shrink">
                <template v-if="pluginSnippet.editions[0].price != null && pluginSnippet.editions[0].price !== '0.00'">
                    <template v-if="isInstalled(pluginSnippet)">
                        <template v-if="pluginHasLicenseKey(pluginSnippet.handle)">
                            <license-status status="installed" :description="$options.filters.t('Installed', 'app')"></license-status>
                        </template>
                        <template v-else>
                            <license-status status="installed" :description="$options.filters.t('Installed as a trial', 'app')"></license-status>

                            <a v-if="isInCart(pluginSnippet)" class="btn submit disabled">{{ "Added to cart"|t('app') }}</a>
                            <a v-else @click="chooseEdition(pluginSnippet)" class="btn submit" :title="buyBtnTitle">{{ pluginSnippet.editions[0].price|currency }}</a>
                        </template>
                    </template>

                    <template v-else>
                        <template v-if="allowUpdates">
                            <form method="post">
                                <input type="hidden" :name="csrfTokenName" :value="csrfTokenValue">
                                <input type="hidden" name="action" value="pluginstore/install">
                                <input type="hidden" name="packageName" :value="pluginSnippet.packageName">
                                <input type="hidden" name="handle" :value="pluginSnippet.handle">
                                <input type="hidden" name="version" :value="pluginSnippet.version">
                                <input type="submit" class="btn" :value="'Try'|t('app')">
                            </form>

                            <a v-if="isInCart(pluginSnippet)" class="btn submit disabled">{{ "Added to cart"|t('app') }}</a>
                            <a v-else @click="chooseEdition(pluginSnippet)" class="btn submit" :title="buyBtnTitle">{{ pluginSnippet.editions[0].price|currency }}</a>
                        </template>
                    </template>
                </template>
                <div v-else>
                    <a v-if="isInstalled(pluginSnippet)" class="btn submit disabled">{{ "Installed"|t('app') }}</a>

                    <div v-else-if="allowUpdates">
                        <form method="post">
                            <input type="hidden" :name="csrfTokenName" :value="csrfTokenValue">
                            <input type="hidden" name="action" value="pluginstore/install">
                            <input type="hidden" name="packageName" :value="pluginSnippet.packageName">
                            <input type="hidden" name="handle" :value="pluginSnippet.handle">
                            <input type="hidden" name="version" :value="pluginSnippet.version">
                            <input type="submit" class="btn submit" :value="'Install'|t('app')">
                        </form>
                    </div>
                </div>
            </div>
            <div>
                <div v-if="actionsLoading" class="spinner"></div>
            </div>
        </div>

        <div class="plugin-details-body">
            <template v-if="!loading">
                <template v-if="plugin.screenshotUrls && plugin.screenshotUrls.length">
                    <plugin-screenshots :images="plugin.screenshotUrls"></plugin-screenshots>

                    <hr>
                </template>

                <div class="plugin-description">
                    <div v-html="longDescription" class="readable"></div>
                </div>

                <hr>

                <h2 class="mb-4">Informations</h2>
                <div class="plugin-infos">
                    <ul class="plugin-meta">
                        <li><span>{{ "Version"|t('app') }}</span> <strong>{{ plugin.version }}</strong></li>
                        <li><span>{{ "Last update"|t('app') }}</span> <strong>{{ lastUpdate }}</strong></li>
                        <li v-if="plugin.activeInstalls > 0"><span>{{ "Active installs"|t('app') }}</span> <strong>{{ plugin.activeInstalls | formatNumber }}</strong></li>
                        <li><span>{{ "Compatibility"|t('app') }}</span> <strong>{{ plugin.compatibility }}</strong></li>
                        <li v-if="pluginCategories.length > 0">
                            <span>{{ "Categories"|t('app') }}</span>
                            <strong>
                                <template v-for="category, key in pluginCategories">
                                    <a @click="viewCategory(category)">{{ category.title }}</a><template v-if="key < (pluginCategories.length - 1)">, </template>
                                </template>
                            </strong>
                        </li>
                        <li><span>{{ "License"|t('app') }}</span> <strong>{{ licenseLabel }}</strong></li>
                        <li v-if="pluginSnippet.editions[0].renewalPrice">
                            <span>{{ "Renewal price"|t('app') }}</span>
                            <strong>{{ "{price}/year"|t('app', { price: $root.$options.filters.currency(pluginSnippet.editions[0].renewalPrice) }) }}</strong>
                        </li>
                    </ul>
                </div>

                <hr>

                <ul v-if="(plugin.documentationUrl || plugin.changelogUrl)" class="plugin-meta-links">
                    <li v-if="plugin.documentationUrl"><a :href="plugin.documentationUrl" class="btn fullwidth" rel="noopener" target="_blank">{{ "Documentation"|t('app') }}</a></li>
                    <li v-if="plugin.changelogUrl"><a :href="plugin.changelogUrl" class="btn fullwidth" rel="noopener" target="_blank">{{ "Changelog"|t('app') }}</a></li>
                </ul>

            </template>
            <template v-else>
                <div class="plugin-details-loading spinner"></div>
            </template>
        </div>
    </div>
</template>

<script>
    import {mapState, mapGetters, mapActions} from 'vuex'
    import LicenseStatus from '../../../components/LicenseStatus'
    import StatusMessage from '../../../components/StatusMessage'
    import PluginScreenshots from '../../../components/PluginScreenshots'

    export default {

        props: ['pluginId'],

        components: {
            LicenseStatus,
            StatusMessage,
            PluginScreenshots,
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
                cart: state => state.cart.cart,
                defaultPluginSvg: state => state.craft.defaultPluginSvg,
            }),

            ...mapGetters({
                activeTrialPlugins: 'activeTrialPlugins',
                isInCart: 'cart/isInCart',
                isInstalled: 'pluginStore/isInstalled',
                pluginHasLicenseKey: 'craft/pluginHasLicenseKey',
            }),

            longDescription() {
                if (this.plugin.longDescription && this.plugin.longDescription.length > 0) {
                    return this.plugin.longDescription
                }
            },

            buyBtnTitle() {
                return this.$root.$options.filters.t('Buy now for {price}', 'app', {
                    price: this.$root.$options.filters.currency(this.pluginSnippet.editions[0].price)
                });
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

            csrfTokenName() {
                return Craft.csrfTokenName
            },

            csrfTokenValue() {
                return Craft.csrfTokenValue
            },

            allowUpdates() {
                return window.allowUpdates
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

            chooseEdition() {
                this.$root.openModal('plugin-edition')
            },

            buyPlugin(plugin) {
                this.actionsLoading = true

                const item = {
                    type: 'plugin-edition',
                    plugin: plugin.handle,
                    edition: plugin.editions[0].handle,
                    autoRenew: false,
                    cmsLicenseKey: window.cmsLicenseKey,
                }

                this.$store.dispatch('cart/addToCart', [item])
                    .then(() => {
                        this.actionsLoading = false
                        this.$root.openModal('cart')
                    })
            },

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
                        .then(response => {
                            this.loading = false
                        })
                        .catch(response => {
                            this.loading = false
                        })
                }
            }

        },

        mounted() {
            const pluginHandle = this.$route.params.handle
            const plugin = this.$store.getters['pluginStore/getPluginByHandle'](pluginHandle)
            this.$root.pluginId = plugin.id
            this.loadPlugin(plugin.id)
        }

    }
</script>

<style lang="scss">
    @import "../../../../sass/variables";


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