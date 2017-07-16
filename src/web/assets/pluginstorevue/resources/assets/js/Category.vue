<template>
    <div v-if="category">
        <plugin-grid :plugins="categoryPlugins"></plugin-grid>
    </div>

</template>

<script>
    import PluginGrid from './components/PluginGrid';
    import { mapGetters } from 'vuex'

    export default {
        name: 'category',

        components: {
            PluginGrid,
        },

        data () {
            return {
                plugins: [],
                categoryId: null,
            }
        },
        computed: {
            ...mapGetters({
               categories: 'allCategories',
               categoryPlugins: 'categoryPlugins',
            }),
            category() {
                let categoryId = this.$route.params.id;
                let category = this.$store.getters.getCategoryById(categoryId);

                if(category) {
                    this.$root.pageTitle = category.title;
                }

                return category;
            }
        },

        created: function() {
            this.$root.showCrumbs = true;

            this.categoryId = this.$route.params.id;

            this.$store.dispatch('getCategoryPlugins', this.categoryId);
        },
    }
</script>