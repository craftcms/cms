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

            <div class="buttons">
                <div v-if="pluginSnippet.price != '0.00' && pluginSnippet.price != null">
                    <a v-if="isInTrial(pluginSnippet) || isInstalled(pluginSnippet)" class="btn disabled">{{ "Installed"|t('app') }}</a>
                    <a v-else @click="tryPlugin(pluginSnippet)" class="btn">{{ "Try"|t('app') }}</a>

                    <a v-if="isInCart(pluginSnippet)" @click="buyPlugin(pluginSnippet)" class="btn submit disabled">{{ "Added to cart"|t('app') }}</a>
                    <a v-else @click="buyPlugin(pluginSnippet)" class="btn submit">{{ "Buy {price}"|t('app', { price: $root.$options.filters.currency(pluginSnippet.price) }) }}</a>
                </div>
                <div v-else>
                    <a v-if="isInstalled(pluginSnippet)" class="btn submit disabled">{{ "Installed"|t('app') }}</a>
                    <a v-else @click="installPlugin(pluginSnippet)" class="btn submit">{{ "Install"|t('app') }}</a>
                </div>
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
                        <ul>
                            <li><span>{{ "Version"|t('app') }}</span> <strong>{{ plugin.version }}</strong></li>
                            <li><span>{{ "Last update"|t('app') }}</span> <strong>{{ lastUpdate }}</strong></li>
                            <li v-if="plugin.activeInstalls > 0"><span>{{ "Active installs"|t('app') }}</span> <strong>{{ plugin.activeInstalls }}</strong></li>
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
    import { mapGetters, mapActions } from 'vuex'

    export default {

        props: ['pluginId'],

        data() {
            return {
                plugin: null,
                pluginSnippet: null,
            }
        },

        computed: {

            ...mapGetters({
                plugins: 'allPlugins',
                cartPlugins: 'cartPlugins',
                activeTrialPlugins: 'activeTrialPlugins',
                isInTrial: 'isInTrial',
                isInCart: 'isInCart',
                isInstalled: 'isInstalled',
            }),

            longDescription() {
                if(this.plugin.longDescription && this.plugin.longDescription.length > 0) {
                    return this.plugin.longDescription;
                }
            },

            developerUrl() {
                return Craft.getCpUrl('plugin-store/developer/' + this.plugin.developerId);
            },

            installUrl() {
                return Craft.getCpUrl('plugin-store/install');
            },

            categories() {
                return this.$store.getters.getAllCategories().filter(c => {
                    return this.plugin.categoryIds.find(pc => pc == c.id);
                });
            },

            licenseLabel() {
                switch(this.plugin.license) {
                    case 'craft':
                        return 'Craft';

                    case 'mit':
                        return 'MIT';
                }
            },

            lastUpdate() {
                return Craft.formatDate(this.plugin.lastUpdate);
            }

        },

        watch: {

            pluginId(pluginId) {
                this.loadPlugin(pluginId);
                return pluginId;
            }

        },

        methods: {

            ...mapActions([
               'addToCart'
            ]),

            buyPlugin(plugin) {
                this.$store.dispatch('addToCart', plugin);
                this.$root.openGlobalModal('cart');
            },

            tryPlugin(plugin) {
                this.$root.closeGlobalModal();
                this.$router.push({ path: '/install/'+plugin.id });
            },

            installPlugin(plugin) {
                this.$root.closeGlobalModal();

                window.location.href = Craft.getUrl('plugin-store/install', {name: this.plugin.packageName, handle: this.plugin.handle, version: this.plugin.version});
            },

            viewDeveloper(plugin) {
                this.$root.closeGlobalModal();
                this.$root.pageTitle = plugin.developerName;
                this.$router.push({ path: '/developer/'+plugin.developerId})
            },

            viewCategory(category) {
                this.$root.closeGlobalModal();
                this.$root.pageTitle = category.name;
                this.$router.push({ path: '/categories/'+category.id})
            },

            loadPlugin(pluginId) {
                this.plugin = null;
                this.pluginSnippet = this.$store.getters.getPluginById(pluginId);

                this.$store.dispatch('getPluginDetails', pluginId)
                    .then(plugin => {
                        this.plugin = plugin;
                    })
                    .catch(response => {
                        console.log('error', response);
                    });
            }

        },

        mounted() {
            this.loadPlugin(this.pluginId);
        }

    }
</script>