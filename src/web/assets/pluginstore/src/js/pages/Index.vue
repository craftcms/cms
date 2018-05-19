<template>
    <div>
        <plugin-search @showResults="showingSearchResults = true" @hideResults="showingSearchResults = false" :plugins="plugins"></plugin-search>

        <a href="#" class="category-selector-btn" @click.prevent="showCategorySelector = !showCategorySelector">All categories</a>

        <div class="category-selector" :class="{ hidden: !showCategorySelector }">
            <div class="category-selector-header">
                <a href="#" @click.prevent="showCategorySelector = false">Hide categories</a>
            </div>

            <div class="category-selector-body">
                <ul class="categories">
                    <li v-if="CraftEdition < CraftPro || licensedEdition < CraftPro">
                        <router-link to="/upgrade-craft">
                            <img src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48c3ZnIHdpZHRoPSIxMDBweCIgaGVpZ2h0PSIxMDBweCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayI+ICAgICAgICA8dGl0bGU+Y3JhZnQ8L3RpdGxlPiAgICA8ZGVzYz5DcmVhdGVkIHdpdGggU2tldGNoLjwvZGVzYz4gICAgPGRlZnM+PC9kZWZzPiAgICA8ZyBpZD0iUGFnZS0xIiBzdHJva2U9Im5vbmUiIHN0cm9rZS13aWR0aD0iMSIgZmlsbD0ibm9uZSIgZmlsbC1ydWxlPSJldmVub2RkIj4gICAgICAgIDxnIGlkPSJjcmFmdCI+ICAgICAgICAgICAgPGNpcmNsZSBpZD0iT3ZhbCIgZmlsbD0iI0RBNUE0NyIgY3g9IjUwIiBjeT0iNTAiIHI9IjUwIj48L2NpcmNsZT4gICAgICAgICAgICA8cGF0aCBkPSJNNjUuMTMxNDQwNCwzNC4yNjI5Njc5IEM2NS40MTUyMjQxLDM0LjQ3NTEzMDEgNjUuNjgyNzkxNywzNC42OTk0NTQ0IDY1Ljk0NDk1MzksMzQuOTI3ODMyOCBMNzAuMTgyNzkxNywzMS42MzA1MzU1IEw3MC4zMTUyMjQxLDMxLjQ2MDI2NTIgQzY5LjY2MDE5NjUsMzAuODAwOTk5IDY4Ljk1ODM2NzUsMzAuMTg5OTQ3IDY4LjIxNTIyNDEsMjkuNjMxODg2OSBDNTguNDg5NTQ4NSwyMi4zNTQ4NTk4IDQzLjc5MjI1MTIsMjUuNDAwODA1OCAzNS4zODgxOTcxLDM2LjQzNTk0MDkgQzI2Ljk4OTU0ODUsNDcuNDY5NzI0NyAyOC4wNjM4NzI4LDYyLjMxMDI2NTIgMzcuNzg4MTk3MSw2OS41ODk5OTUgQzQ1LjczMDA4OSw3NS41MzA1MzU1IDU2Ljk4Mjc5MTcsNzQuNTg3MjkyMyA2NS40MTkyNzgyLDY4LjAzNTk0MDkgTDY1LjQxMjUyMTQsNjguMDE5NzI0NyBMNjEuMzc3Mzg2Myw2NC44ODQ1ODk2IEM1NS4xMjQ2ODM2LDY4Ljg2ODM3MzMgNDcuMzY5Mjc4Miw2OS4xNTQ4NTk4IDQxLjc1ODQ2NzQsNjQuOTU3NTYyNSBDMzQuMjg1NDk0NCw1OS4zNjgzNzMzIDMzLjQ2MTE3MDEsNDcuOTY1NjcwNiAzOS45MTY1NzU1LDM5LjQ4OTk5NSBDNDYuMzY5Mjc4MiwzMS4wMTI5Njc5IDU3LjY1OTgxODcsMjguNjczNzc4OCA2NS4xMzAwODksMzQuMjYyOTY3OSBMNjUuMTMxNDQwNCwzNC4yNjI5Njc5IFoiIGlkPSJQYXRoIiBmaWxsPSIjRkZGRkZGIj48L3BhdGg+ICAgICAgICA8L2c+ICAgIDwvZz48L3N2Zz4=" />
                            {{ "Upgrade Craft CMS"|t('app') }}
                        </router-link>
                    </li>
                    <li v-for="category in categories">
                        <router-link :to="'/categories/'+category.id">
                            <img :src="category.iconUrl" />
                            {{ category.title }}
                        </router-link>
                    </li>
                </ul>
            </div>
        </div>

        <div v-if="!showingSearchResults" class="ps-grid-wrapper has-sidebar">
            <div class="ps-grid-sidebar categories-wrapper">
                <h2>{{ "Categories"|t('app') }}</h2>
                <ul class="categories">
                    <li v-if="CraftEdition < CraftPro || licensedEdition < CraftPro">
                        <router-link to="/upgrade-craft">
                            <img src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48c3ZnIHdpZHRoPSIxMDBweCIgaGVpZ2h0PSIxMDBweCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayI+ICAgICAgICA8dGl0bGU+Y3JhZnQ8L3RpdGxlPiAgICA8ZGVzYz5DcmVhdGVkIHdpdGggU2tldGNoLjwvZGVzYz4gICAgPGRlZnM+PC9kZWZzPiAgICA8ZyBpZD0iUGFnZS0xIiBzdHJva2U9Im5vbmUiIHN0cm9rZS13aWR0aD0iMSIgZmlsbD0ibm9uZSIgZmlsbC1ydWxlPSJldmVub2RkIj4gICAgICAgIDxnIGlkPSJjcmFmdCI+ICAgICAgICAgICAgPGNpcmNsZSBpZD0iT3ZhbCIgZmlsbD0iI0RBNUE0NyIgY3g9IjUwIiBjeT0iNTAiIHI9IjUwIj48L2NpcmNsZT4gICAgICAgICAgICA8cGF0aCBkPSJNNjUuMTMxNDQwNCwzNC4yNjI5Njc5IEM2NS40MTUyMjQxLDM0LjQ3NTEzMDEgNjUuNjgyNzkxNywzNC42OTk0NTQ0IDY1Ljk0NDk1MzksMzQuOTI3ODMyOCBMNzAuMTgyNzkxNywzMS42MzA1MzU1IEw3MC4zMTUyMjQxLDMxLjQ2MDI2NTIgQzY5LjY2MDE5NjUsMzAuODAwOTk5IDY4Ljk1ODM2NzUsMzAuMTg5OTQ3IDY4LjIxNTIyNDEsMjkuNjMxODg2OSBDNTguNDg5NTQ4NSwyMi4zNTQ4NTk4IDQzLjc5MjI1MTIsMjUuNDAwODA1OCAzNS4zODgxOTcxLDM2LjQzNTk0MDkgQzI2Ljk4OTU0ODUsNDcuNDY5NzI0NyAyOC4wNjM4NzI4LDYyLjMxMDI2NTIgMzcuNzg4MTk3MSw2OS41ODk5OTUgQzQ1LjczMDA4OSw3NS41MzA1MzU1IDU2Ljk4Mjc5MTcsNzQuNTg3MjkyMyA2NS40MTkyNzgyLDY4LjAzNTk0MDkgTDY1LjQxMjUyMTQsNjguMDE5NzI0NyBMNjEuMzc3Mzg2Myw2NC44ODQ1ODk2IEM1NS4xMjQ2ODM2LDY4Ljg2ODM3MzMgNDcuMzY5Mjc4Miw2OS4xNTQ4NTk4IDQxLjc1ODQ2NzQsNjQuOTU3NTYyNSBDMzQuMjg1NDk0NCw1OS4zNjgzNzMzIDMzLjQ2MTE3MDEsNDcuOTY1NjcwNiAzOS45MTY1NzU1LDM5LjQ4OTk5NSBDNDYuMzY5Mjc4MiwzMS4wMTI5Njc5IDU3LjY1OTgxODcsMjguNjczNzc4OCA2NS4xMzAwODksMzQuMjYyOTY3OSBMNjUuMTMxNDQwNCwzNC4yNjI5Njc5IFoiIGlkPSJQYXRoIiBmaWxsPSIjRkZGRkZGIj48L3BhdGg+ICAgICAgICA8L2c+ICAgIDwvZz48L3N2Zz4=" />
                            {{ "Upgrade Craft CMS"|t('app') }}
                        </router-link>
                    </li>
                    <li v-for="category in categories">
                        <router-link :to="'/categories/'+category.id">
                            <img :src="category.iconUrl" />
                            {{ category.title }}
                        </router-link>
                    </li>
                </ul>
            </div>

            <div class="ps-grid-main">
                <template v-if="featuredPlugins">
                    <template v-for="featuredPlugin in featuredPlugins">
                        <router-link class="right" :to="'/featured/'+featuredPlugin.id">{{ "See all"|t('app') }}</router-link>
                        <div>
                            <h2>{{ featuredPlugin.title }}</h2>
                            <plugin-grid :plugins="getPluginsByIds(featuredPlugin.plugins.slice(0, featuredPlugin.limit))"></plugin-grid>
                        </div>
                    </template>
                </template>

                <template v-if="activeTrialPlugins.length > 0">
                    <h2>{{ "Active Trials"|t('app') }}</h2>
                    <plugin-grid :plugins="activeTrialPlugins"></plugin-grid>
                </template>
            </div>
        </div>
    </div>
</template>


<script>
    import {mapState, mapGetters} from 'vuex'

    export default {

        components: {
            PluginGrid: require('../components/PluginGrid'),
            PluginSearch: require('../components/PluginSearch'),
        },

        data() {
            return {
                showingSearchResults: false,
                showCategorySelector: false,
            }
        },

        computed: {

            ...mapState({
                categories: state => state.pluginStore.categories,
                featuredPlugins: state => state.pluginStore.featuredPlugins,
                plugins: state => state.pluginStore.plugins,
                licensedEdition: state => state.craft.licensedEdition,
                CraftEdition: state => state.craft.CraftEdition,
                CraftPro: state => state.craft.CraftPro,
            }),

            ...mapGetters({
                activeTrialPlugins: 'activeTrialPlugins',
                getPluginsByIds: 'getPluginsByIds',
            }),

        },

        created() {
            this.$root.pageTitle = this.$options.filters.t("Plugin Store", 'app')
        },

        mounted() {
            this.$root.crumbs = null

            // show a plugin?
            const pluginHandle = this.$route.params.pluginHandle
            if (pluginHandle) {
                this.$router.replace({path: '/'})
                const plugin = this.$store.getters.getPluginByHandle(pluginHandle)

                if (this.$root.pluginStoreDataLoaded) {
                    // show plugin
                    this.$root.showPlugin(plugin)
                } else {
                    // wait for the cart to be ready
                    this.$root.$on('allDataLoaded', function() {
                        // show plugin
                        this.$root.showPlugin(plugin)
                    }.bind(this))
                }
            }
        }

    }
</script>
