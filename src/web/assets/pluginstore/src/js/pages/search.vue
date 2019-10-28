<template>
    <div class="ps-container">
        <plugin-index
                action="pluginStore/searchPlugins"
                :requestData="requestData"
                :plugins="plugins"
                :force-loading="loading"
        >
            <template v-slot:header>
                <h1>{{ "Showing results for “{searchQuery}”"|t('app', {searchQuery}) }}</h1>
            </template>
        </plugin-index>
    </div>
</template>

<script>
    import {mapState} from 'vuex'
    import PluginIndex from '../components/PluginIndex'

    export default {
        components: {
            PluginIndex,
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

            requestData() {
                return {
                    searchQuery: this.searchQuery,
                }
            }
        },

        methods: {
            search() {
                this.loading = true

                this.$store.commit('pluginStore/updatePlugins', [])
                this.$store.dispatch('pluginStore/searchPlugins', {
                        searchQuery: this.searchQuery,
                        dontAppendData: true,
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
