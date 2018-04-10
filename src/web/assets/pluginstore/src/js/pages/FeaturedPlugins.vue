<template>
	<div v-if="featuredPlugin">
		<plugin-grid :columns="4" :plugins="getPluginsByIds(featuredPlugin.plugins)"></plugin-grid>
	</div>
</template>

<script>
    import {mapGetters} from 'vuex'

    export default {

        components: {
            PluginGrid: require('../components/PluginGrid'),
        },

        computed: {

            ...mapGetters({
                getFeaturedPlugin: 'getFeaturedPlugin',
                getPluginsByIds: 'getPluginsByIds',
            }),

            featuredPlugin() {
                let featuredPlugin = this.getFeaturedPlugin(this.$route.params.id)

                if (featuredPlugin) {
                    this.$root.pageTitle = this.$options.filters.escapeHtml(featuredPlugin.title)
                }

                return featuredPlugin
            }

        },

        created() {
            this.$root.crumbs = [
                {
                    label: this.$options.filters.t("Plugin Store", 'app'),
                    path: '/',
                }
            ]
        },

    }
</script>