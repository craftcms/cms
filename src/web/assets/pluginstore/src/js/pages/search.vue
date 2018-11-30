<template>
    <div class="ps-container">
        <h1>Showing results for “{{searchQuery}}”</h1>
        <sort-menu-btn :attributes="sortMenuBtnAttributes" :value.sync="sort"></sort-menu-btn>

        <template v-if="loading">
            <div class="spinner"></div>
        </template>
        <template v-else>
            <plugin-grid :plugins="searchResults"></plugin-grid>
        </template>
    </div>
</template>

<script>
    import {mapState} from 'vuex'
    import includes from 'lodash/includes'
    import filter from 'lodash/filter'
    import PluginGrid from '../components/PluginGrid'
    import SortMenuBtn from '../components/SortMenuBtn'

    export default {

        data() {
            return {
                loading: true,
                searchResults: [],
                sort: {
                    attribute: 'activeInstalls',
                    sort: 'desc',
                },
                selectedAttribute: null,
                selectedDirection: null,
                sortMenuBtnAttributes: null,
            }
        },

        components: {
            PluginGrid,
            SortMenuBtn,
        },

        computed: {

            ...mapState({
                plugins: state => state.pluginStore.plugins,
                searchQuery: state => state.app.searchQuery,
            }),

        },

        methods: {

            performSearch() {
                let searchQuery = this.searchQuery

                if (!searchQuery) {
                    this.$emit('hideResults')
                    return []
                }

                this.$emit('showResults')

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
            }

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

            this.sortMenuBtnAttributes = {
                activeInstalls: this.$options.filters.t("Popularity", 'app'),
                lastUpdate: this.$options.filters.t("Last Update", 'app'),
                name: this.$options.filters.t("Name", 'app'),
                price: this.$options.filters.t("Price", 'app'),
            }
        }

    }
</script>