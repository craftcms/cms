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
            <plugin-index :plugins="plugins"></plugin-index>
        </template>
    </div>
</template>

<script>
    import {mapState, mapGetters,  mapActions} from 'vuex'
    import PluginIndex from '../../components/PluginIndex'
    import SortPlugins from '../../components/SortPlugins'

    export default {
        components: {
            PluginIndex,
            SortPlugins,
        },

        data() {
            return {
                category: null,
                loading: false,
                sortingOptions: {
                    attribute: 'activeInstalls',
                    direction: 'desc',
                },
            }
        },

        computed: {
            ...mapState({
                plugins: state => state.pluginStore.plugins,
            }),

            ...mapGetters({
                getCategoryById: 'pluginStore/getCategoryById',
            }),
        },

        methods: {
            ...mapActions({
                getPluginsByCategory: 'pluginStore/getPluginsByCategory',
            }),
        },

        mounted() {
            const categoryId = this.$route.params.id
            this.category = this.getCategoryById(categoryId)

            this.loading = true

            this.getPluginsByCategory({categoryId})
                .then(() => {
                    this.loading = false
                })
                .catch(() => {
                    this.loading = false
                })
        },
    }
</script>
