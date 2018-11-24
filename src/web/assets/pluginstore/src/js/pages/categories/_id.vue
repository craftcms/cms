<template>
    <div v-if="category" class="ps-container">
        <h1>{{category.title}}</h1>

        <template v-if="loading">
            <div class="spinner"></div>
        </template>
        <template v-else>
            <plugin-index :plugins="plugins" :columns="4"></plugin-index>
        </template>
    </div>
</template>

<script>
    import {mapGetters} from 'vuex'
    import PluginIndex from '../../components/PluginIndex'

    export default {

        components: {
            PluginIndex,
        },

        data() {
            return {
                category: null,
                loading: true,
                plugins: [],
            }
        },

        computed: {

            ...mapGetters({
                getCategoryById: 'pluginStore/getCategoryById',
                getPluginsByCategory: 'pluginStore/getPluginsByCategory',
            }),

        },

        created() {
            const categoryId = this.$route.params.id
            this.category = this.getCategoryById(categoryId)

            setTimeout(function() {
                this.plugins = this.getPluginsByCategory(categoryId)
                this.loading = false
            }.bind(this), 1)
        }

    }
</script>