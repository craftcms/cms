<template>
    <div v-if="pluginSnippet" class="plugin-details">
        <div class="plugin-details-header">
            <div class="plugin-icon">
                <img v-if="pluginSnippet.iconUrl" :src="pluginSnippet.iconUrl" />
                <img v-else :src="defaultPluginSvg" />
            </div>

            <div class="description">
                <h2>{{ pluginSnippet.name }}</h2>

                <p>{{ pluginSnippet.shortDescription }}</p>

                <p>
                    <a @click="viewDeveloper(pluginSnippet)">{{ pluginSnippet.developerName }}</a>
                </p>
            </div>

            <div v-if="cart" class="buttons">
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
                <div class="plugin-description">
                    <div v-html="longDescription" class="readable"></div>

                    <div class="screenshots">
                        <img v-for="screenshotUrl in plugin.screenshotUrls" :src="screenshotUrl" />
                    </div>
                </div>

                <div class="plugin-sidebar">
                    <div class="plugin-meta">
                        <ul class="plugin-meta-data">
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

                        <ul v-if="(plugin.documentationUrl || plugin.changelogUrl)" class="plugin-meta-links">
                            <li v-if="plugin.documentationUrl"><a :href="plugin.documentationUrl" class="btn fullwidth" rel="noopener" target="_blank">{{ "Documentation"|t('app') }}</a></li>
                            <li v-if="plugin.changelogUrl"><a :href="plugin.changelogUrl" class="btn fullwidth" rel="noopener" target="_blank">{{ "Changelog"|t('app') }}</a></li>
                        </ul>
                    </div>
                </div>
            </template>
            <template v-else>
                <div class="plugin-details-loading spinner"></div>
            </template>
        </div>
    </div>
</template>

<script>
    import {mapState, mapGetters, mapActions} from 'vuex'
    import LicenseStatus from '../../components/LicenseStatus'

    export default {

        props: ['pluginId'],

        components: {
            LicenseStatus,
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
                this.$root.modalStep = 'plugin-edition'
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

            this.loadPlugin(plugin.id)
        }

    }
</script>

<style lang="scss" scoped>
    @import "../../../../lib/craftcms-sass/mixins";

    .plugin-details {
        @apply .tw-relative .tw-flex .tw-flex-grow .tw-flex-col .tw-min-h-0;

        .plugin-details-header {
            @apply .tw-flex .tw-flex-no-shrink;
            border-bottom: 1px solid #eee;
            padding: 24px;

            .plugin-icon {
                img {
                    max-width: none;
                    width: 80px;
                    height: 80px;
                }
            }

            .description {
                @apply .tw-flex-grow;
                margin-left: 14px;
            }

            .description h2 {
                margin-bottom: 10px;
            }

            .description p {
                margin: 0.4em 0;
            }

            .buttons {
                @apply .tw-whitespace-no-wrap;
                @include margin(0, 0, 0, 24px);

                a {
                    margin-left: 7px;
                }

                .license-status {
                    margin-top: 6px;
                    margin-right: 14px;
                }
            }
        }

        .plugin-details-body {
            @apply .tw-relative .tw-flex .tw-flex-grow .tw-min-h-0 .tw-w-full;
            flex-basis: 100%;

            .plugin-details-loading {
                @apply .tw-absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
            }

            .plugin-description {
                @apply .tw-flex-grow .tw-min-w-0 .tw-overflow-auto .tw-w-full;
                padding: 24px;
                flex-basis: 100%;

                img {
                    @apply .tw-max-w-full;
                }

                pre {
                    @apply .tw-overflow-auto;
                    background: #eee;
                    padding: 24px;
                }
            }

            .plugin-sidebar {
                @apply .tw-overflow-auto;
                flex: 0 0 260px;
                width: 260px;
                background: #fafafa;
                border-left: 1px solid #eee;

                .plugin-meta {
                    border-radius: 4px;
                    padding: 24px;

                    ul.plugin-meta-data {
                        border-bottom: 1px solid #eee;
                        margin-bottom: 14px;

                        li {
                            @apply .tw-flex;
                            border-bottom: 1px solid #eee;
                            padding: 7px 0;

                            &:last-child {
                                @apply .tw-border-b-0;
                            }

                            & span,
                            & strong {
                                @apply .tw-flex-grow;
                            }

                            & strong {
                                @apply .tw-text-right;
                            }
                        }
                    }

                    ul.plugin-meta-links li {
                        padding: 7px 0;
                    }
                }
            }

            .screenshots {
                margin-top: 24px;

                img {
                    @apply .tw-w-full;
                    margin-bottom: 24px;
                }
            }
        }
    }
</style>