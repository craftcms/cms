<template>
    <div v-if="plugin" class="plugin-details">
        <div class="plugin-details-header">
            <div class="plugin-icon-large">
                <img v-if="plugin.iconUrl" :src="plugin.iconUrl" height="60" />
            </div>

            <div class="description">
                <h2>{{ plugin.name }}</h2>

                <p>{{ plugin.shortDescription }}</p>

                <p>
                    <a @click="viewDeveloper(plugin)">{{ plugin.developerName }}</a>
                </p>
            </div>

            <div class="buttons">

                <div v-if="plugin.price != '0.00'">
                    <a v-if="isInTrial(plugin) || isInstalled(plugin)" class="btn disabled">{{ "Installed"|t('app') }}</a>
                    <a v-else @click="tryPlugin(plugin)" class="btn">{{ "Try"|t('app') }}</a>

                    <a v-if="isInCart(plugin)" @click="buyPlugin(plugin)" class="btn submit disabled">{{ "Added to cart"|t('app') }}</a>
                    <a v-else @click="buyPlugin(plugin)" class="btn submit">{{ "Buy {price}"|t('app', { price: $root.$options.filters.currency(plugin.price) }) }}</a>
                </div>
                <div v-else>
                    <a v-if="isInstalled(plugin)" class="btn submit disabled">{{ "Installed"|t('app') }}</a>
                    <a v-else @click="installPlugin(plugin)" class="btn submit">{{ "Install"|t('app') }}</a>
                </div>

            </div>
        </div>

        <div class="plugin-details-body">
            <div class="plugin-description">
                <div v-html="longDescription"></div>

                <div class="screenshots">
                    <img v-for="screenshot in plugin.screenshots" :src="screenshot" />
                </div>
            </div>

            <div class="plugin-sidebar">
                <div class="plugin-meta">
                    <ul>
                        <li><span>{{ "Version"|t('app') }}</span> <strong>X.X.X</strong></li>
                        <li><span>{{ "Last update"|t('app') }}</span> <strong>—</strong></li>
                        <li><span>{{ "Active installs"|t('app') }}</span> <strong>XXX,XXX</strong></li>
                        <li><span>{{ "Compatibility"|t('app') }}</span> <strong>Craft X</strong></li>
                        <li>
                            <span>{{ "Categories"|t('app') }}</span>
                            <strong>
                                <template v-for="category, key in categories">
                                    <a @click="viewCategory(category)">{{ category.title }}</a>
                                    <template v-if="key < (categories.length - 1)">, </template>
                                </template>
                            </strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script>

    import { mapGetters, mapActions } from 'vuex'

    var marked = require('marked');

    export default {
        props: ['plugin'],

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
                    return marked(this.plugin.longDescription, { sanitize: true });
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
                this.$router.push({ path: '/install/'+plugin.id });
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
            }
        },
    }
</script>

<style scoped>

</style>
