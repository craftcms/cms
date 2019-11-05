<template>
    <div v-if="category" class="ps-container">
        <plugin-index
                action="pluginStore/getPluginsByCategory"
                :requestData="requestData"
                :plugins="plugins"
        >
            <template v-slot:header>
                <h1>{{category.title}}</h1>
            </template>
        </plugin-index>
    </div>
</template>

<script>
    import {mapState, mapGetters,  mapActions} from 'vuex'
    import PluginIndex from '../../components/PluginIndex'

    export default {
        components: {
            PluginIndex,
        },

        data() {
            return {
                category: null,
            }
        },

        computed: {
            ...mapState({
                plugins: state => state.pluginStore.plugins,
            }),

            ...mapGetters({
                getCategoryById: 'pluginStore/getCategoryById',
            }),

            requestData() {
                return {
                    categoryId: this.category.id
                }
            }
        },

        methods: {
            ...mapActions({
                getPluginsByCategory: 'pluginStore/getPluginsByCategory',
            }),
        },

        mounted() {
            const categoryId = this.$route.params.id
            this.category = this.getCategoryById(categoryId)
        },
    }
</script>
