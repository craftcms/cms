<template>
    <div v-if="category" class="ps-container">
        <div class="ps-header">
            <h1>{{category.title}}</h1>
            <sort-plugins :sortingOptions.sync="sortingOptions"></sort-plugins>
        </div>

        <template v-if="loading">
            <spinner class="mt-4"></spinner>
        </template>
        <template v-else>
            <plugin-index :plugins="pluginsToRender"></plugin-index>
        </template>
    </div>
</template>

<script>
    import {mapGetters} from 'vuex'
    import PluginIndex from '../../components/PluginIndex'
    import SortPlugins from '../../components/SortPlugins'
    import PluginsHelper from '../../helpers/plugins'

    export default {
        components: {
            PluginIndex,
            SortPlugins,
        },

        data() {
            return {
                category: null,
                loading: true,
                plugins: [],
                sortingOptions: {
                    attribute: 'activeInstalls',
                    direction: 'desc',
                },
            }
        },

        computed: {
            ...mapGetters({
                getCategoryById: 'pluginStore/getCategoryById',
                getPluginsByCategory: 'pluginStore/getPluginsByCategory',
            }),

            pluginsToRender() {
                return PluginsHelper.sortPlugins(this.plugins, this.sortingOptions);
            }
        },

        created() {
            const categoryId = this.$route.params.id
            this.category = this.getCategoryById(categoryId)

            setTimeout(function() {
                this.plugins = this.getPluginsByCategory(categoryId)
                this.loading = false
            }.bind(this), 1)
        },
    }
</script>
