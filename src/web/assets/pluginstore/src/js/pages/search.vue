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
            <plugin-grid :plugins="plugins"></plugin-grid>
        </template>
    </div>
</template>

<script>
    import {mapState} from 'vuex'
    import PluginGrid from '../components/PluginGrid'
    import SortPlugins from '../components/SortPlugins'

    export default {
        components: {
            PluginGrid,
            SortPlugins,
        },

        data() {
            return {
                loading: false,
                sortingOptions: {
                    attribute: 'activeInstalls',
                    sort: 'desc',
                },
            }
        },

        watch: {
            searchQuery() {
                this.search()
            }
        },

        computed: {
            ...mapState({
                plugins: state => state.pluginStore.plugins,
                searchQuery: state => state.app.searchQuery,
            }),
        },

        methods: {
            search() {
                this.loading = true

                this.$store.dispatch('pluginStore/searchPlugins', {
                        searchQuery: this.searchQuery,
                    })
                    .then(() => {
                        this.loading = false
                    })
                    .catch(() => {
                        this.loading = false
                    })
            }
        },

        mounted() {
            if (!this.searchQuery) {
                this.$router.push({path: '/'})
                return null
            }

            this.search()
        }
    }
</script>
