<template>
    <div v-if="category">
        <plugin-index :plugins="plugins" :columns="4"></plugin-index>
    </div>
</template>

<script>
    import {mapGetters} from 'vuex'
    import PluginIndex from '../components/PluginIndex'

    export default {

        components: {
            PluginIndex,
        },

        data() {
            return {
                categoryId: null,
            }
        },

        computed: {

            ...mapGetters({
                getCategoryById: 'getCategoryById',
                getPluginsByCategory: 'getPluginsByCategory',
            }),

            category() {
                let category = this.getCategoryById(this.categoryId)

                if (category) {
                    this.$root.pageTitle = category.title
                }

                return category
            },

            plugins() {
                return this.getPluginsByCategory(this.categoryId)
            }

        },

        watch: {

            '$route.params.id': function(id) {
                this.categoryId = id
            }

        },

        created() {
            this.$root.crumbs = [
                {
                    label: this.$options.filters.t("Plugin Store", 'app'),
                    path: '/',
                }
            ]

            this.categoryId = this.$route.params.id
        },

    }
</script>