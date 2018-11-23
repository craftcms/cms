<template>
    <div v-if="category" class="ps-container">
        <h1>{{category.title}}</h1>
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
                getCategoryById: 'pluginStore/getCategoryById',
                getPluginsByCategory: 'pluginStore/getPluginsByCategory',
            }),

            category() {
                return this.getCategoryById(this.categoryId)
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
            this.categoryId = this.$route.params.id
        },

    }
</script>