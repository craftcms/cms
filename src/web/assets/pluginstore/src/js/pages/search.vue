<template>
    <div class="ps-container">
        <div class="ps-header">
            <h1>{{ "Showing results for “{searchQuery}”"|t('app', {searchQuery}) }}</h1>
            <sort-plugins :sortingOptions.sync="sortingOptions"></sort-plugins>
        </div>

        <template v-if="loading">
            <spinner></spinner>
        </template>
        <template v-else>
            <plugin-grid :plugins="pluginsToRender"></plugin-grid>
        </template>
    </div>
</template>

<script>
    import {mapState} from 'vuex'
    import includes from 'lodash/includes'
    import filter from 'lodash/filter'
    import PluginGrid from '../components/PluginGrid'
    import SortPlugins from '../components/SortPlugins'
    import PluginsHelper from '../helpers/plugins'

    export default {
        data() {
            return {
                loading: true,
                searchResults: [],
                sortingOptions: {
                    attribute: 'activeInstalls',
                    sort: 'desc',
                },
            }
        },

        components: {
            PluginGrid,
            SortPlugins,
        },

        computed: {
            ...mapState({
                plugins: state => state.pluginStore.plugins,
                searchQuery: state => state.app.searchQuery,
            }),

            pluginsToRender() {
                return PluginsHelper.sortPlugins(this.searchResults, this.sortingOptions);
            }
        },

        methods: {
            performSearch() {
                let searchQuery = this.searchQuery

                if (!searchQuery) {
                    return []
                }

                return filter(this.plugins, o => {
                    if (o.packageName && includes(o.packageName.toLowerCase(), searchQuery.toLowerCase())) {
                        return true
                    }

                    if (o.name && includes(o.name.toLowerCase(), searchQuery.toLowerCase())) {
                        return true
                    }

                    if (o.shortDescription && includes(o.shortDescription.toLowerCase(), searchQuery.toLowerCase())) {
                        return true
                    }

                    if (o.description && includes(o.description.toLowerCase(), searchQuery.toLowerCase())) {
                        return true
                    }

                    if (o.developerName && includes(o.developerName.toLowerCase(), searchQuery.toLowerCase())) {
                        return true
                    }

                    if (o.developerUrl && includes(o.developerUrl.toLowerCase(), searchQuery.toLowerCase())) {
                        return true
                    }

                    if (o.keywords.length > 0) {
                        for (let i = 0; i < o.keywords.length; i++) {
                            if (includes(o.keywords[i].toLowerCase(), searchQuery.toLowerCase())) {
                                return true
                            }
                        }
                    }
                })
            },

            search() {
                this.loading = true

                setTimeout(function() {
                    this.searchResults = this.performSearch()
                    this.loading = false
                }.bind(this), 1)
            },
        },

        watch: {
            searchQuery() {
                this.search()
            }
        },

        mounted() {
            if (!this.searchQuery) {
                this.$router.push({path: '/'})
            } else {
                this.search()
            }
        }
    }
</script>
