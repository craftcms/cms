<template>

    <div>
        <plugin-search @showResults="showingSearchResults = true" @hideResults="showingSearchResults = false" :plugins="categoryPlugins"></plugin-search>

        <div v-if="!showingSearchResults && category">
            <plugin-grid :columns="4" :plugins="categoryPlugins"></plugin-grid>
        </div>
    </div>

</template>

<script>
    import PluginGrid from './components/PluginGrid';
    import PluginSearch from './components/PluginSearch';
    import { mapGetters } from 'vuex'

    export default {
        name: 'category',
        components: {
            PluginGrid,
            PluginSearch,
        },
        data () {
            return {
                showingSearchResults: false,
                categoryId: null,
            }
        },
        computed: {
            category() {
                let category = this.$store.getters.getCategoryById(this.categoryId);

                if(category) {
                    this.$root.pageTitle = category.title;
                }

                return category;
            },
            categoryPlugins() {
                return this.$store.getters.getPluginsByCategory(this.categoryId);
            }
        },

        created: function() {
            this.$root.showCrumbs = true;

            this.categoryId = this.$route.params.id;
        },
    }
</script>