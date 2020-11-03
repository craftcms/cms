<template>
    <div class="ps-container">
        <plugin-index
                ref="pluginIndex"
                action="pluginStore/searchPlugins"
                :requestData="requestData"
                :plugins="plugins"
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

        watch: {
            searchQuery() {
                this.$router.push({path: '/'})

                this.$nextTick(() => {
                    this.$router.push({path: '/search'})
                })
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

        mounted() {
            if (!this.searchQuery) {
                this.$router.push({path: '/'})
                return null
            }
        }
    }
</script>
