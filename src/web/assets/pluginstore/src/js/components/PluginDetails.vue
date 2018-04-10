<template>
    <div v-if="pluginSnippet" class="plugin-details">
        <div class="plugin-details-header">
            <div class="plugin-icon-large">
                <img v-if="pluginSnippet.iconUrl" :src="pluginSnippet.iconUrl" height="60" />
            </div>

            <div class="description">
                <h2>{{ pluginSnippet.name }}</h2>

                <p>{{ pluginSnippet.shortDescription }}</p>

                <p>
                    <a @click="viewDeveloper(pluginSnippet)">{{ pluginSnippet.developerName }}</a>
                </p>
            </div>

            <div v-if="cart" class="buttons">
                <template v-if="pluginSnippet.editions[0].price != '0.00' && pluginSnippet.editions[0].price != null">

                    <template v-if="isInstalled(pluginSnippet)">
                        <template v-if="pluginHasLicenseKey(pluginSnippet.handle)">
                            <div class="license-status installed" data-icon="check">{{ "Installed"|t('app') }}</div>
                        </template>
                        <template v-else>
                            <div class="license-status installed" data-icon="check">{{ "Installed as a trial"|t('app') }}</div>

                            <a v-if="isInCart(pluginSnippet)" @click="buyPlugin(pluginSnippet)" class="btn submit disabled">{{ "Added to cart"|t('app') }}</a>
                            <a v-else @click="buyPlugin(pluginSnippet)" class="btn submit">{{ "Buy {price}"|t('app', { price: $root.$options.filters.currency(pluginSnippet.editions[0].price) }) }}</a>
                        </template>
                    </template>

                    <template v-else>
                        <form method="post">
                            <input type="hidden" :name="csrfTokenName" :value="csrfTokenValue">
                            <input type="hidden" name="action" value="pluginstore/install">
                            <input type="hidden" name="packageName" :value="pluginSnippet.packageName">
                            <input type="hidden" name="handle" :value="pluginSnippet.handle">
                            <input type="hidden" name="version" :value="pluginSnippet.version">
                            <input type="submit" class="btn" :value="'Try'|t('app')">
                        </form>

                        <a v-if="isInCart(pluginSnippet)" @click="buyPlugin(pluginSnippet)" class="btn submit disabled">{{ "Added to cart"|t('app') }}</a>
                        <a v-else @click="buyPlugin(pluginSnippet)" class="btn submit">{{ "Buy {price}"|t('app', { price: $root.$options.filters.currency(pluginSnippet.editions[0].price) }) }}</a>
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
                <div v-if="loading" class="spinner"></div>
            </div>
        </div>

        <div class="plugin-details-body">
            <template v-if="plugin">
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
                            <li v-if="categories.length > 0">
                                <span>{{ "Categories"|t('app') }}</span>
                                <strong>
                                    <template v-for="category, key in categories">
                                        <a @click="viewCategory(category)">{{ category.title }}</a><template v-if="key < (categories.length - 1)">, </template>
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
                            <li v-if="plugin.documentationUrl"><a :href="plugin.documentationUrl" class="btn fullwidth" target="_blank">{{ "Documentation"|t('app') }}</a></li>
                            <li v-if="plugin.changelogUrl"><a :href="plugin.changelogUrl" class="btn fullwidth" target="_blank">{{ "Changelog"|t('app') }}</a></li>
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
    import {mapGetters, mapActions} from 'vuex'

    export default {

        props: ['pluginId'],

        data() {
            return {
                plugin: null,
                pluginSnippet: null,
                loading: false,
            }
        },

        computed: {

            ...mapGetters({
                plugins: 'allPlugins',
                activeTrialPlugins: 'activeTrialPlugins',
                isInTrial: 'isInTrial',
                isInCart: 'isInCart',
                isInstalled: 'isInstalled',
                pluginHasLicenseKey: 'pluginHasLicenseKey',
                cart: 'cart',
            }),

            longDescription() {
                if (this.plugin.longDescription && this.plugin.longDescription.length > 0) {
                    return this.plugin.longDescription
                }
            },

            developerUrl() {
                return Craft.getCpUrl('plugin-store/developer/' + this.plugin.developerId)
            },

            categories() {
                return this.$store.getters.getAllCategories().filter(c => {
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
                return Craft.formatDate(this.plugin.lastUpdate)
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

            ...mapActions([
                'addToCart'
            ]),

            buyPlugin(plugin) {
                this.loading = true

                const item = {
                    type: 'plugin-edition',
                    plugin: plugin.handle,
                    edition: plugin.editions[0].handle,
                    autoRenew: false,
                    cmsLicenseKey: window.cmsLicenseKey,
                }

                this.$store.dispatch('addToCart', [item])
                    .then(() => {
                        this.loading = false
                        this.$root.openGlobalModal('cart')
                    })
            },

            viewDeveloper(plugin) {
                this.$root.closeGlobalModal()
                this.$root.pageTitle = this.$options.filters.escapeHtml(plugin.developerName)
                this.$router.push({path: '/developer/' + plugin.developerId})
            },

            viewCategory(category) {
                this.$root.closeGlobalModal()
                this.$root.pageTitle = category.name
                this.$router.push({path: '/categories/' + category.id})
            },

            loadPlugin(pluginId) {
                this.plugin = null
                this.pluginSnippet = this.$store.getters.getPluginById(pluginId)

                this.$store.dispatch('getPluginDetails', pluginId)
                    .then(plugin => {
                        this.plugin = plugin
                    })
                    .catch(response => {
                        console.log('error', response)
                    })
            }

        },

        mounted() {
            this.loadPlugin(this.pluginId)
        }

    }
</script>
